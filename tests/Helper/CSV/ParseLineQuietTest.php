<?php
/*
 * Created on   : Mon Nov 23 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ParseLineQuietTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use ERRORToolkit\LoggerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;
use Tests\Contracts\BaseTestCase;

/**
 * parseLineToFields() meldet eine ungültige Zeile mit der Ausnahme und
 * schreibt dabei nichts ins Protokoll. Bis v2.18.2 schrieb jede Ablehnung
 * eine Fehlerzeile - auch beim bloßen Ausprobieren: Eine Formaterkennung, die
 * eine Komma-Datei gegen Semikolon-Formate prüft, hinterließ für EINE Datei
 * 293 Fehlerzeilen (Betriebslog 22.11.2026).
 */
class ParseLineQuietTest extends BaseTestCase {
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidLines(): array {
        return [
            // Komma-Zeile, mit Semikolon gelesen: das Anführungszeichen steht mitten im Feld
            'Enclosure mitten im Feld' => ['MUSTER GMBH,USD,"MUSTERBANK, TESTSTADT",1', ';', 'Unerwartetes Enclosure'],
            'Feld nicht geschlossen' => ['"A";"offen ohne Ende', ';', 'Feld nicht geschlossen'],
            'Quote nach Trennzeichen im Feld' => ['"A,"B"', ',', 'Delimiter nach Quote-Ende'],
        ];
    }

    #[DataProvider('invalidLines')]
    public function test_invalid_line_throws_without_log_line(string $line, string $delimiter, string $expected): void {
        $thrown = null;

        $records = $this->captureLog(static function () use ($line, $delimiter, &$thrown): void {
            try {
                StringHelper::parseLineToFields($line, $delimiter, '"');
            } catch (RuntimeException $e) {
                $thrown = $e;
            }
        });

        $this->assertInstanceOf(RuntimeException::class, $thrown, 'Die Zeile ist ungültig und muss abgelehnt werden.');
        $this->assertStringContainsString($expected, $thrown->getMessage());
        $this->assertSame([], $records, 'Die Ablehnung steht in der Ausnahme, nicht im Protokoll.');
    }

    /** Das Zusammenfügen mehrzeiliger Felder probiert Zeilen aus - ohne Fehlerzeilen. */
    public function test_iterate_logical_lines_leaves_no_error_lines(): void {
        $logical = [];

        $records = $this->captureLog(static function () use (&$logical): void {
            $logical = iterator_to_array(StringHelper::iterateLogicalLines(['"B";"Zeile 1', 'Zeile 2"', '"C";"drei"'], ';', '"'), false);
        });

        $this->assertCount(2, $logical);
        $this->assertSame([], array_values(array_filter($records, static fn (string $line): bool => str_starts_with($line, 'error'))));
    }

    /**
     * @return list<string> Zeilen des Protokolls als "stufe: meldung"
     */
    private function captureLog(callable $action): array {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void {
                $this->records[] = $level . ': ' . $message;
            }
        };

        LoggerRegistry::setLogger($logger);
        try {
            $action();
        } finally {
            LoggerRegistry::resetLogger();
        }

        return $logger->records;
    }
}
