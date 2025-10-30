<?php
/*
 * Created on   : Wed Oct 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVStringHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper\CSVStringHelper;
use Tests\Contracts\BaseTestCase;

class CSVStringHelperTest extends BaseTestCase {
    public function testDetectEnclosureRepeat(): void {
        $tests = [

            ['line' => '',                            'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => ',',                           'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => ',,',                          'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => ',""',                         'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => ',"",',                        'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => ',"",,',                       'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => ',""""',                       'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => ',"""",',                      'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => ',"""",,',                     'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => ',"",""""',                    'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"",,""""',                    'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"""",""""',                   'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => '"",,""20,00""',               'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"",,""20,00"","""2000,00"""', 'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => '"abc","def"',                 'expected_strict' => 1, 'expected_non_strict' => 1],
            ['line' => 'abc,"def"',                   'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => '"abc",""def""',               'expected_strict' => 1, 'expected_non_strict' => 2],
            ['line' => '""abc"",,""def""',            'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => ',""abc"",""def""',            'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '""abc"",,""20,00""',          'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"abc",""def"","""ghi"""',     'expected_strict' => 1, 'expected_non_strict' => 3],
            ['line' => 'abc,""def"","""ghi"""',       'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => '""abc""',                     'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => 'abc,def',                     'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => '"""",""abc""',                'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => '"""",""20,20"",""abc""',      'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => '"""""","""abc"""',            'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000,00"",""2000,00"",""0,00"",""EUR""', 'expected_strict' => 2, 'expected_non_strict' => 2],
        ];

        foreach ($tests as $test) {
            $strictResult = CSVStringHelper::detectCSVEnclosureRepeat($test['line'], '"', ',', null, null, true);
            $nonStrictResult = CSVStringHelper::detectCSVEnclosureRepeat($test['line'], '"', ',', null, null, false);

            $this->assertSame(
                $test['expected_strict'],
                $strictResult,
                "Fehler (strict) bei Zeile: {$test['line']}"
            );

            $this->assertSame(
                $test['expected_non_strict'],
                $nonStrictResult,
                "Fehler (non-strict) bei Zeile: {$test['line']}"
            );
        }
    }

    public function testValidLinesShouldReturnTrue(): void {
        $tests = [
            ['line' => '"A","B","C"', 'delimiter' => ','],
            ['line' => '"A";"B";"C"', 'delimiter' => ';'],
            ['line' => '""A"","""B""","C"', 'delimiter' => ','],  // Mehrfache Enclosures
            ['line' => '"A,1","B,2","C,3"', 'delimiter' => ','],  // Kommas in Quotes
            ['line' => '"","B","C"', 'delimiter' => ','],         // Leeres Feld
            ['line' => '""KDC2ASKF"","""Datum"""', 'delimiter' => ','],
            ['line' => '"A ""quoted"" text","B","C"', 'delimiter' => ','], // Escaped Quotes
        ];

        foreach ($tests as $t) {
            $this->assertTrue(
                CSVStringHelper::canParseCompleteCSVDataLine($t['line'], $t['delimiter'], '"'),
                sprintf("Zeile sollte gültig sein, war es aber nicht: %s", $t['line'])
            );
        }
    }

    public function testInvalidLinesShouldReturnFalse(): void {
        $tests = [
            '"A","B","C',
            '"A",B"C"',
            'A","B","C"',
            '1","2","3"',
            '1,0","2","3"',
            'A,B,"C',
            '"A","B","C"]',
            '"A,"B","C"',
        ];

        foreach ($tests as $line) {
            $this->assertFalse(
                CSVStringHelper::canParseCompleteCSVDataLine($line, ',', '"'),
                sprintf("Zeile sollte ungültig sein, war aber gültig: %s", $line)
            );
        }
    }

    public function testInvalidLinesShouldReturnTrue(): void {
        $tests = [
            ['line' => '"Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'closed' => ']'],
            ['line' => '["Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'started' => '[', 'closed' => ']'],
        ];

        foreach ($tests as $line) {
            $this->assertTrue(
                CSVStringHelper::canParseCompleteCSVDataLine($line['line'], $line['delimiter'], $line['enclosure'], $line['started'] ?? null, $line['closed'] ?? null),
                sprintf("Zeile sollte gültig sein, war aber ungültig: %s", $line['line'])
            );
        }
    }

    public function testHasRepeatedEnclosure(): void {
        $tests = [
            ['line' => '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000,00"",""2000,00"",""0,00"",""EUR""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 2, 'strict' => true, 'expected' => true],
            ['line' => '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000"",""2000"",""0"",""EUR""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 2, 'strict' => true, 'expected' => true],
            ['line' => '"Feld1",,"Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => '"Feld1","Feld2",', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'expected' => true],
            ['line' => '"Feld1","Feld2",', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => ',"Feld2",', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => ',"Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => '"Feld1","Feld2",""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => true],
            ['line' => '"","Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => true],
            ['line' => '"Feld1","Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => true],
            ['line' => '""Feld1"",""Feld2"",""Feld3""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 2, 'expected' => true],
            ['line' => '""Feld1"","""",""Feld3""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 2, 'expected' => true],
            ['line' => '"""Feld1""","""Feld2""","""Feld3"""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 3, 'expected' => true],
            ['line' => '"Feld1","Feld2",Feld3', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 2, 'expected' => false],
            ['line' => '"Feld1";"Feld2";"Feld3"', 'delimiter' => ';', 'enclosure' => '"', 'repeat' => 1, 'expected' => true],
            ['line' => 'Feld1;Feld2;Feld3', 'delimiter' => ';', 'enclosure' => '"', 'repeat' => 0, 'expected' => true],
            ['line' => 'Feld1;Feld2;Feld3', 'delimiter' => ';', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => '"Feld1";"Feld2";"Feld3"', 'delimiter' => ';', 'enclosure' => '"', 'repeat' => 0, 'expected' => false],
            ['line' => '"Feld1","Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 3, 'expected' => false],
            ['line' => 'Feld1,Feld2,Feld3', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'expected' => false],
            ['line' => '""Feld1"",""Feld2"",""Feld3"";', 'delimiter' => ';', 'enclosure' => '"', 'repeat' => 2, 'expected' => false],
            ['line' => '"Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'closed' => ']', 'expected' => true],
            ['line' => '"Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'closed' => null, 'expected' => false],
            ['line' => '["Feld1","Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'started' => '[', 'expected' => true],
            ['line' => '["Feld1","Feld2","Feld3"', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'started' => null, 'expected' => false],
            ['line' => '["Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'started' => '[', 'closed' => ']', 'expected' => true],
            ['line' => '["Feld1","Feld2","Feld3"]', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'started' => '[', 'closed' => null, 'expected' => false],
            ['line' => '"Feld1,"Feld2","Feld3""', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'started' => '"', 'closed' => '"', 'expected' => false],
            ['line' => '"Feld1,"Feld2","Feld3"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '","Feld2","Feld3"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '"Feld1,"2000,00","3000,00"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '"Feld1,"2000,00",3000,00";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '"Feld1,2000,00,"3000,00"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '"Feld1,"2000,00,3000,00"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => true],
            ['line' => '"Feld1,Feld2","Feld3"";', 'delimiter' => ',', 'enclosure' => '"', 'repeat' => 1, 'strict' => false, 'started' => '"', 'closed' => '";', 'expected' => false],

        ];

        foreach ($tests as $test) {
            $result = CSVStringHelper::hasRepeatedEnclosure($test['line'], $test['delimiter'], $test['enclosure'], $test['repeat'], $test['strict'] ?? true, $test['started'] ?? null, $test['closed'] ?? null);
            $this->assertSame(
                $test['expected'],
                $result,
                sprintf(
                    "Fehlgeschlagen bei '%s' mit Delimiter '%s', Enclosure '%s', Repeat %d – erwartet %s, erhalten %s",
                    $test['line'],
                    $test['delimiter'],
                    $test['enclosure'],
                    $test['repeat'],
                    $test['expected'] ? 'true' : 'false',
                    $result ? 'true' : 'false'
                )
            );
        }
    }

    public function testParseCSVMultiLine(): void {
        $csv = <<<CSV
            06-08-2019,"650,01","Gutschrift
            PAYPAL EUROPE SARL ET CIE SCA
            22-24 BOULEVARD ROY
            PP.8902.PP ABBUCHUNG
            VOM PAYPAL-KONTO
            AWV-MELDEPFLICHT BEACHTEN
            HOTLINE BUNDESBANK: (0800) 1234-111
            YYW4BMJ2AT6YUREA PP.8902.PP PAYPAL",""
            CSV;
        $csv1 = <<<CSV
            06-08-2019,"650,01",Gutschrift
            PAYPAL EUROPE SARL ET CIE SCA
            22-24 BOULEVARD ROY
            PP.8902.PP ABBUCHUNG
            VOM PAYPAL-KONTO
            AWV-MELDEPFLICHT BEACHTEN
            HOTLINE BUNDESBANK: (0800) 1234-111
            YYW4BMJ2AT6YUREA PP.8902.PP PAYPAL,""
            CSV;

        $result = CSVStringHelper::extractFields($csv, ',', '"', null, null, "\n");
        $result1 = CSVStringHelper::extractFields($csv1, ',', '"');

        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        $this->assertIsArray($result1);
        $this->assertCount(4, $result1);

        $this->assertSame($result, $result1);
    }

    public function testExtractRepeatedEnclosureFields(): void {
        $tests = [
            [
                'line'     => '"KDC2ASKF","21.12.2024 17:55:41","c832c84d-4940-484d-a7fb-4bc98cff6a88","","ich@irgendwo.com","Schlussbilanz","","","","","","","","2000","200000","0","EUR"',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'expected' => ['KDC2ASKF', '21.12.2024 17:55:41', 'c832c84d-4940-484d-a7fb-4bc98cff6a88', '', 'ich@irgendwo.com', 'Schlussbilanz', '', '', '', '', '', '', '', '2000', '200000', '0', 'EUR']
            ],
            [
                'line'     => '"KDC2ASKF","21.12.2024 17:55:41","c832c84d-4940-484d-a7fb-4bc98cff6a88","","ich@irgendwo.com","Schlussbilanz","","","","","","","","2000,00","2000,00","0,00","EUR"',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'expected' => ['KDC2ASKF', '21.12.2024 17:55:41', 'c832c84d-4940-484d-a7fb-4bc98cff6a88', '', 'ich@irgendwo.com', 'Schlussbilanz', '', '', '', '', '', '', '', '2000,00', '2000,00', '0,00', 'EUR']
            ],
            [
                'line'     => '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000,00"",""2000,00"",""0,00"",""EUR""',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'expected' => ['KDC2ASKF', '21.12.2024 17:55:41', 'c832c84d-4940-484d-a7fb-4bc98cff6a88', '', 'ich@irgendwo.com', 'Schlussbilanz', '', '', '', '', '', '', '2000,00', '2000,00', '0,00', 'EUR']
            ],
            [
                'line'     => '"""KDC2ASKF""","""21.12.2024 17:55:41""","""c832c84d-4940-484d-a7fb-4bc98cff6a88""","""""","""ich@irgendwo.com""","""Schlussbilanz""","""""","""""","""""","""""","""""","""""","""2000,00""","""2000,00""","""0,00""","""EUR"""',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'expected' => ['KDC2ASKF', '21.12.2024 17:55:41', 'c832c84d-4940-484d-a7fb-4bc98cff6a88', '', 'ich@irgendwo.com', 'Schlussbilanz', '', '', '', '', '', '', '2000,00', '2000,00', '0,00', 'EUR']
            ],
            [
                'line'     => '"Feld1","Feld2",""',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', '']
            ],
            [
                'line'     => '"Feld1","Feld2",',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', '']
            ],
            [
                'line'     => ',"Feld2",',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['', 'Feld2', '']
            ],
            [
                'line'     => '"Feld1",,""',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', '', '']
            ],
            [
                'line'     => '"Feld1","Feld2","","rr"',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', '', 'rr']
            ],
            [
                'line'     => '"Feld1","Feld2","Feld3"',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => '""Feld1"",""Feld2"",""Feld3""',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => '"""Feld1""","""Feld2""","""Feld3"""',
                'delimiter' => ',',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => '"Feld1";"Feld2";"Feld3"',
                'delimiter' => ';',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => 'Feld1;Feld2;Feld3',
                'delimiter' => ';',
                'enclosure' => '"',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => '""KDC2ASKF"",""21.12.2024 17:55:41"",""2000,00"";',
                'delimiter' => ',',
                'enclosure' => '"',
                'closed'   => ';',
                'expected' => ['KDC2ASKF', '21.12.2024 17:55:41', '2000,00']
            ],
            [
                'line'     => '"Feld1,""2000,00"",3000,00";',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'started'  => '"',
                'closed'   => '";',
                'expected' => ['Feld1', '2000,00', '3000', '00']
            ],
            [
                'line'     => '"Feld1,"2000,00,3000,00"";',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'started'  => '"',
                'closed'   => '";',
                'expected' => ['Feld1', '2000,00,3000,00']
            ],
            [
                'line'     => '["Feld1","Feld2","Feld3"]',
                'delimiter' => ',',
                'enclosure' => '"',
                'started'  => '[',
                'closed'   => ']',
                'expected' => ['Feld1', 'Feld2', 'Feld3']
            ],
            [
                'line'     => '"Feld1,2000,00,"3000,00"";',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'started'  => '"',
                'closed'   => '";',
                'expected' => ['Feld1', '2000', '00', '3000,00']
            ],
            [
                'line'     => '"Feld1,"2000,00","3000,00"";',
                'delimiter' => ',',
                'enclosure' => '"',
                'strict'   => false,
                'started'  => '"',
                'closed'   => '";',
                'expected' => ['Feld1', '2000,00', '3000,00']
            ],
        ];

        foreach ($tests as $test) {
            $result = CSVStringHelper::extractFields(
                $test['line'],
                $test['delimiter'],
                $test['enclosure'],
                $test['started'] ?? null,
                $test['closed'] ?? null
            );

            $this->assertSame(
                $test['expected'],
                $result,
                sprintf(
                    "Fehlgeschlagen bei Zeile '%s' mit Delimiter '%s', Enclosure '%s' – erwartet %s, erhalten %s",
                    $test['line'],
                    $test['delimiter'],
                    $test['enclosure'],
                    json_encode($test['expected']),
                    json_encode($result)
                )
            );
        }
    }
}