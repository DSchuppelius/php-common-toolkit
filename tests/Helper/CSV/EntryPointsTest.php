<?php
/*
 * Created on   : Wed Oct 14 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EntryPointsTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Helper\Data\CSV\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

/**
 * Die Zeilen-Einstiege des CSV-Helpers teilen EINEN Tokenizer
 * (parseLineToFields) und unterscheiden sich nur in der Dekodierung
 * (Scan 13.10.2026 K3-09):
 *
 * - parseLineToFields(): rohe Felder (Round-Trip-Form),
 * - DataLine::fromString(): das Feldmodell über genau diese rohen Felder,
 * - parseLineToValues(): jedes rohe Feld durch decodeField() (RFC 4180),
 * - extractFields(): Werte des Feldmodells - "" bleibt "", ungequoteter
 *   Leerraum wird getrimmt, gemischt gewrappte Zeilen lehnt die
 *   Normalisierung ab.
 *
 * Die Unterschiede sind gewollt (der Core ruft extractFields() über hundertmal);
 * dieser Test hält sie fest, damit niemand die Einstiege "vereinheitlicht"
 * und dabei Werte verändert.
 */
class EntryPointsTest extends BaseTestCase {
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validLines(): array {
        return [
            'gemischt' => ['1234,56;"1.234,56";"A ""q"" t";""60,00"";"";"a;b"', ';'],
            'leerraum um quotes' => ['a, "b", "c" ,d', ','],
            'tab' => ["x\t \"y\" \tz", "\t"],
            'escaptes paar vor trennzeichen' => ['"Er sagte ""Hallo"" ; dann";Z', ';'],
            'zeilenweise umwickelt' => ['"Datum,""Empfänger"",""Betrag"""', ','],
            'doppelt gewrappt' => ['""60,00"";""a""', ';'],
            'mehrzeilig' => ["a;\"Zeile 1\nZeile 2\";b", ';'],
            'json in zelle' => ['"{""order_id"":5227,""x"":""y""}";2', ';'],
            'dreifach gewrappt' => ['"""2000,00""";"""Muster"" GmbH"', ';'],
            'leeres letztes feld' => ['a;b;', ';'],
            'ungequotet mit leerraum' => ['01.01.2025;-12,50;  text  ', ';'],
        ];
    }

    #[DataProvider('validLines')]
    public function test_field_model_and_values_sit_on_the_same_tokenizer(string $line, string $delimiter): void {
        $raw = StringHelper::parseLineToFields($line, $delimiter, '"');

        $this->assertSame($raw, array_map(static fn ($field) => $field->getRaw(), DataLine::fromString($line, $delimiter, '"')->getFields()));
        $this->assertSame(array_map(static fn (string $field): string => StringHelper::decodeField($field), $raw), StringHelper::parseLineToValues($line, $delimiter));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidLines(): array {
        return [
            'text nach dem enclosure' => ['"ab"c;Z', ';'],
            'quote nach delimiter im feld' => ['"A,"B"', ','],
            'offenes feld' => ['"offen;Z', ';'],
            'quote nach text' => ['Monitor;27";x', ';'],
        ];
    }

    #[DataProvider('invalidLines')]
    public function test_every_entry_point_rejects_what_the_tokenizer_rejects(string $line, string $delimiter): void {
        $entryPoints = [
            'parseLineToFields' => static fn () => StringHelper::parseLineToFields($line, $delimiter, '"'),
            'parseLineToValues' => static fn () => StringHelper::parseLineToValues($line, $delimiter),
            'extractFields' => static fn () => StringHelper::extractFields($line, $delimiter),
            'DataLine::fromString' => static fn () => DataLine::fromString($line, $delimiter, '"'),
        ];

        foreach ($entryPoints as $name => $call) {
            try {
                $call();
                $this->fail("$name haette ablehnen muessen");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /** Die gewollten Unterschiede von extractFields() zu parseLineToValues(). */
    public function test_extract_fields_keeps_its_own_value_semantics(): void {
        // "" bleibt "" (Round-Trip-Form des Feldmodells)
        $this->assertSame(['Er sagte ""Hallo"" ; dann', 'Z'], StringHelper::extractFields('"Er sagte ""Hallo"" ; dann";Z', ';'));
        $this->assertSame(['Er sagte "Hallo" ; dann', 'Z'], StringHelper::parseLineToValues('"Er sagte ""Hallo"" ; dann";Z', ';'));

        // Ungequoteter Leerraum wird getrimmt
        $this->assertSame(['01.01.2025', '-12,50', 'text'], StringHelper::extractFields('01.01.2025;-12,50;  text  ', ';'));
        $this->assertSame(['01.01.2025', '-12,50', '  text  '], StringHelper::parseLineToValues('01.01.2025;-12,50;  text  ', ';'));

        // Mehrfach gewrappt: Normalisierung auf die gemeinsame Lauflaenge
        $this->assertSame(['2000,00', 'Muster"" GmbH'], StringHelper::extractFields('"""2000,00""";"""Muster"" GmbH"', ';'));
        $this->assertSame(['"2000,00"', '"Muster" GmbH'], StringHelper::parseLineToValues('"""2000,00""";"""Muster"" GmbH"', ';'));

        // Gleiche Ergebnisse, wo nichts zu dekodieren ist
        $this->assertSame(StringHelper::parseLineToValues('a, "b", "c" ,d', ','), StringHelper::extractFields('a, "b", "c" ,d', ','));
        $this->assertSame(StringHelper::parseLineToValues("a;\"Zeile 1\nZeile 2\";b", ';'), StringHelper::extractFields("a;\"Zeile 1\nZeile 2\";b", ';', multiLineReplacement: "\n"));
    }

    /** Gemischt gewrappte Zeilen liest nur der Tokenizer; die Normalisierung von extractFields() lehnt sie ab. */
    public function test_extract_fields_rejects_mixed_wrapping_that_the_tokenizer_reads(): void {
        $line = '"Datum,""Empfänger"",""Betrag"""';

        $this->assertCount(2, StringHelper::parseLineToFields($line, ',', '"'));
        $this->expectException(RuntimeException::class);
        StringHelper::extractFields($line, ',');
    }
}
