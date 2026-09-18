<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExcelSeparatorHintTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * Excel-Hinweiszeile `sep=X` am CSV-Rohinhalt entfernen
 * (Ersatz für die FRITZ!Box-/AnyDesk-Kopien `/^\s*sep=.\s*\r?\n/i`).
 */
class ExcelSeparatorHintTest extends BaseTestCase {
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function hintProvider(): array {
        return [
            'lf' => ["sep=;\nA;B\n1;2", "A;B\n1;2", ';'],
            'crlf' => ["sep=,\r\nA,B\r\n", "A,B\r\n", ','],
            'cr' => ["sep=|\rA|B", 'A|B', '|'],
            'tab' => ["sep=\t\nA\tB", "A\tB", "\t"],
            'grossschreibung' => ["SEP=;\nA;B", 'A;B', ';'],
            'gemischt' => ["Sep=;\nA;B", 'A;B', ';'],
            'nachgestellter leerraum' => ["sep=; \t\r\nA;B", 'A;B', ';'],
            'führender leerraum' => ["  sep=;\nA;B", 'A;B', ';'],
            'folgende leerzeilen' => ["sep=;\n\n \r\nA;B", 'A;B', ';'],
            'header-einrückung bleibt' => ["sep=;\n  A;B", '  A;B', ';'],
            'nur hinweiszeile' => ['sep=;', '', ';'],
            'utf8-bom wird mit entfernt' => ["\xEF\xBB\xBFsep=;\nA;B", 'A;B', ';'],
            'mehrbyte-zeichen' => ["sep=§\nA§B", 'A§B', '§'],
        ];
    }

    #[DataProvider('hintProvider')]
    public function test_strips_hint_and_reports_delimiter(string $raw, string $expected, string $expectedDelimiter): void {
        $delimiter = 'vorher';

        $this->assertSame($expected, StringHelper::stripExcelSeparatorHint($raw, $delimiter));
        $this->assertSame($expectedDelimiter, $delimiter);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unchangedProvider(): array {
        return [
            'leer' => [''],
            'ohne hinweis' => ["A;B\n1;2"],
            'bom ohne hinweis' => ["\xEF\xBB\xBFA;B\n1;2"],
            'sep ohne zeichen' => ["sep=\nA;B"],
            'sep ohne zeichen crlf' => ["sep=\r\nA;B"],
            'mehrere zeichen' => ["sep=;;\nA;B"],
            'nicht am anfang' => ["A;B\nsep=;\n1;2"],
            'feldinhalt' => ["sep=;x\nA;B"],
            'anderes präfix' => ["separator=;\nA;B"],
            'ungültiges utf8' => ["\xFF\xFEA;B"],
        ];
    }

    #[DataProvider('unchangedProvider')]
    public function test_returns_input_byte_identical_without_hint(string $raw): void {
        $delimiter = 'vorher';

        $this->assertSame($raw, StringHelper::stripExcelSeparatorHint($raw, $delimiter));
        $this->assertNull($delimiter);
    }

    public function test_delimiter_argument_is_optional(): void {
        $this->assertSame('A;B', StringHelper::stripExcelSeparatorHint("sep=;\nA;B"));
    }

    public function test_only_first_hint_line_is_removed(): void {
        $this->assertSame("sep=,\nA;B", StringHelper::stripExcelSeparatorHint("sep=;\nsep=,\nA;B"));
    }

    public function test_matches_app_regex_for_regular_exports(): void {
        $samples = [
            "sep=;\nTyp;Datum;Name\n1;01.01.26 10:00;Foo\n",
            "sep=,\r\nID,Start\r\n1,2026-01-01\r\n",
            "  SEP=;\r\nA;B",
            "Typ;Datum\n1;2",
        ];
        foreach ($samples as $sample) {
            $legacy = (string) preg_replace('/^\s*sep=.\s*\r?\n/i', '', $sample, 1);
            $this->assertSame($legacy, StringHelper::stripExcelSeparatorHint($sample));
        }
    }
}
