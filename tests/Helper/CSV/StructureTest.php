<?php
/*
 * Created on   : Wed Jul 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StructureTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper\CSV;

use CommonToolkit\Helper\Data\CSV\StringHelper;
use Tests\Contracts\BaseTestCase;

class StructureTest extends BaseTestCase {
    public function test_check_structure_with_field_array(): void {
        $tests = [
            ['row' => ['01.02.2024', '1.234,56', 'Miete Januar'],           'pattern' => 'dbt',   'expected' => true],
            ['row' => ['01.02.2024', '-349,09'],                            'pattern' => 'db',    'expected' => true],
            ['row' => ['Miete Januar', '1.234,56'],                         'pattern' => 'db',    'expected' => false],
            ['row' => ['37040044', '0532013000'],                           'pattern' => 'Bk',    'expected' => true],
            ['row' => ['DE89370400440532013000', 'COBADEFFXXX'],            'pattern' => 'ic',    'expected' => true],
            ['row' => ['DEXX30020900123XXXX456'],                           'pattern' => 'I',     'expected' => true],
            ['row' => ['KDC2ASKF', 'irgendwas !%&', '01.02.2024'],          'pattern' => 'u_d',   'expected' => true],
            // Optionales Datum: leer erlaubt, gefüllt muss gültig sein
            ['row' => ['', '1.234,56'],                                     'pattern' => 'Db',    'expected' => true],
            ['row' => ['01.02.2024', '1.234,56'],                           'pattern' => 'Db',    'expected' => true],
            ['row' => ['kein Datum', '1.234,56'],                           'pattern' => 'Db',    'expected' => false],
            // Pflicht-Datum darf nicht leer sein
            ['row' => ['', '1.234,56'],                                     'pattern' => 'db',    'expected' => false],
        ];

        foreach ($tests as $test) {
            $this->assertSame(
                $test['expected'],
                StringHelper::checkStructure($test['row'], $test['pattern']),
                "Fehler bei Muster '{$test['pattern']}' und Zeile: " . implode('|', $test['row'])
            );
        }
    }

    public function test_check_structure_with_raw_csv_line(): void {
        $line = 'Miete Januar;DE89370400440532013000;foo;bar;01.02.2024';

        $this->assertTrue(StringHelper::checkStructure($line, 'ti__d', null, true, ';'));
        // Delimiter-Auto-Erkennung
        $this->assertTrue(StringHelper::checkStructure($line, 'ti__d'));
        $this->assertFalse(StringHelper::checkStructure($line, 'ti__b'));

        // Gequotete Felder werden vor der Prüfung entpackt
        $quoted = '"01.02.2024";"1.234,56";"Miete Januar"';
        $this->assertTrue(StringHelper::checkStructure($quoted, 'dbt', null, true, ';'));
    }

    public function test_check_structure_column_counts(): void {
        $row = ['01.02.2024', '1.234,56', 'Miete Januar'];

        // strict: Musterlänge muss exakt passen
        $this->assertFalse(StringHelper::checkStructure($row, 'db'));
        $this->assertTrue(StringHelper::checkStructure($row, 'db', null, false));

        // Erwartete Spaltenanzahl
        $this->assertTrue(StringHelper::checkStructure($row, 'db', 3, false));
        $this->assertFalse(StringHelper::checkStructure($row, 'db', 4, false));
    }

    public function test_check_structure_rejects_invalid_pattern(): void {
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024'], ''));
        // Unbekanntes Symbol fällt nicht mehr still durch, sondern schlägt sauber fehl
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024', '1,00'], 'dx'));
        // Verirrte '?' (ohne Symbol davor bzw. doppelt) sind ungültig
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024'], '?d'));
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024', '1,00'], 'd??'));
    }

    public function test_check_structure_optional_modifier(): void {
        // 'b?' = Betrag oder leer; Soll/Haben-Spalten, von denen nur eine gefüllt ist
        $this->assertTrue(StringHelper::checkStructure(['01.02.2024', 'Miete', '-50,00', ''], 'dtb?b?'));
        $this->assertTrue(StringHelper::checkStructure(['01.02.2024', 'Miete', '', '+100,00'], 'dtb?b?'));
        $this->assertTrue(StringHelper::checkStructure(['01.02.2024', 'Miete', '', ''], 'dtb?b?'));
        // Gefüllter Wert muss weiterhin dem Typ entsprechen
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024', 'Miete', 'kein Betrag', ''], 'dtb?b?'));

        // 'd?' entspricht dem Alt-Kürzel 'D'
        $this->assertTrue(StringHelper::checkStructure(['', '1.234,56'], 'd?b'));
        $this->assertTrue(StringHelper::checkStructure(['01.02.2024', '1.234,56'], 'd?b'));
        $this->assertFalse(StringHelper::checkStructure(['kein Datum', '1.234,56'], 'd?b'));

        // Token-Anzahl (nicht String-Länge) bestimmt die erwartete Spaltenanzahl
        $this->assertTrue(StringHelper::checkStructure(['01.02.2024', ''], 'db?'));
        $this->assertFalse(StringHelper::checkStructure(['01.02.2024'], 'db?'));
    }

    public function test_match_columns_with_field_array(): void {
        $row = ['Buchungstag', 'Wertstellung', 'Betrag'];

        $this->assertTrue(StringHelper::matchColumns($row, ['Buchungstag', 'Wertstellung', 'Betrag']));
        // Präfix-Vergleich und Wildcard
        $this->assertTrue(StringHelper::matchColumns($row, ['Buch', '*', 'Bet']));
        $this->assertFalse(StringHelper::matchColumns($row, ['Buchungstag', '*', 'Saldo']));

        // strict: Musteranzahl muss exakt passen
        $this->assertFalse(StringHelper::matchColumns($row, ['Buchungstag']));
        $this->assertTrue(StringHelper::matchColumns($row, ['Buchungstag'], 'UTF-8', false));

        // Leere Zeile
        $this->assertFalse(StringHelper::matchColumns(['', '', ''], ['*', '*', '*']));
    }

    public function test_match_columns_with_raw_csv_line(): void {
        $this->assertTrue(StringHelper::matchColumns('"Buchungstag";"Wertstellung";"Betrag"', ['Buchungstag', '*', 'Betrag']));
        $this->assertTrue(StringHelper::matchColumns('Buchungstag,Wertstellung,Betrag', ['Buchungstag', '*', 'Betrag'], 'UTF-8', true, ','));
        $this->assertFalse(StringHelper::matchColumns('Buchungstag;Wertstellung;Betrag', ['Saldo', '*', '*'], 'UTF-8', true, ';'));
    }

    public function test_match_columns_with_encoding(): void {
        $cell = mb_convert_encoding('Müller', 'ISO-8859-1', 'UTF-8');

        $this->assertTrue(StringHelper::matchColumns([$cell, '35'], ['Müller', '*'], 'ISO-8859-1'));
    }
}
