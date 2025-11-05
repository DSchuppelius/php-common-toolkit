<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDataLineTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\CSVDataLine;
use CommonToolkit\Entities\Common\CSV\CSVDataField;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class CSVDataLineTest extends TestCase {
    public function testSimpleQuotedFields(): void {
        $line = CSVDataLine::fromString('"A","B","C"');

        $this->assertCount(3, $line->getFields(), '3 Felder erwartet');

        foreach ($line->getFields() as $field) {
            $this->assertInstanceOf(CSVDataField::class, $field);
            $this->assertTrue($field->isQuoted(), 'Feld sollte gequotet sein');
            $this->assertSame(1, $field->getEnclosureRepeat(), 'Einfaches Quote erwartet');
        }

        $this->assertSame('"A","B","C"', $line->toString());
    }

    public function testConstructorAcceptsRawStringsAndCreatesCSVDataFields(): void {
        $line = new CSVDataLine(['foo', 'bar', 'baz'], ',', '"');

        $fields = $line->getFields();
        $this->assertCount(3, $fields, 'Es sollten drei Felder erzeugt werden');

        foreach ($fields as $field) {
            $this->assertInstanceOf(
                CSVDataField::class,
                $field,
                'Jedes Feld sollte eine Instanz von CSVDataField sein'
            );
        }

        $this->assertSame('foo,bar,baz', $line->toString(), 'CSV-Zeile sollte korrekt serialisiert werden');
    }

    public function testConstructorAcceptsQuotedRawStringsAndCreatesCSVDataFields(): void {
        $line = new CSVDataLine(['"foo"', '"bar"', '"baz"'], ',', '"');

        $fields = $line->getFields();
        $this->assertCount(3, $fields, 'Es sollten drei Felder erzeugt werden');

        foreach ($fields as $field) {
            $this->assertInstanceOf(
                CSVDataField::class,
                $field,
                'Jedes Feld sollte eine Instanz von CSVDataField sein'
            );
        }

        $this->assertSame('"foo","bar","baz"', $line->toString(), 'CSV-Zeile sollte korrekt serialisiert werden');
    }

    public function testMixedQuotedAndUnquotedFields(): void {
        $line = CSVDataLine::fromString('"A",B,"C"');
        $fields = $line->getFields();

        $this->assertTrue($fields[0]->isQuoted());
        $this->assertFalse($fields[1]->isQuoted());
        $this->assertTrue($fields[2]->isQuoted());

        $this->assertSame('"A",B,"C"', $line->toString());
    }

    public function testThrowsOrHandlesInvalidQuotes(): void {
        $tests = [
            '"A","B","C',
            'A","B","C"',
            '"A,"B","C"',
            '"A","B",C"',
            '"A",B",C',
            '"A","B,C',
            '"A",B"C"',
        ];

        foreach ($tests as $t) {
            $rebuilt = null;
            try {
                $line = CSVDataLine::fromString($t, ',', '"');
                $rebuilt = $line->toString(',', '"');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('ungültig', strtolower($e->getMessage()));
            } catch (Throwable $e) {
                $this->assertTrue(true);
            }
            $this->assertNotSame($t, $rebuilt, sprintf("Ungültige Zeile wurde fälschlich akzeptiert: %s", $t));
        }
    }

    public function testEnclosureRepeatDetection(): void {
        $tests = [
            ['line' => ',',                               'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => ',""',                             'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => '"",',                             'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => ',""""',                           'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"""",',                           'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => 'A,B,C',                           'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => '""abc""',                         'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => '"A","B","C"',                     'expected_strict' => 1, 'expected_non_strict' => 1],
            ['line' => '"",,""20,00""',                   'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"abc",""def""',                   'expected_strict' => 1, 'expected_non_strict' => 2],
            ['line' => '"""","""abc"""',                  'expected_strict' => 2, 'expected_non_strict' => 3],
            ['line' => '"""""",""""abc"""',               'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => '""A"",""""B"""","C"',             'expected_strict' => 1, 'expected_non_strict' => 4],
            ['line' => '"""0,00""","""abc"""',            'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => ',"""0,00""","""abc"""',           'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => ',,"""0,00""",,"""abc"""',         'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => '"""","""0,00""","""abc"""',       'expected_strict' => 2, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","""abc"""',     'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => '"""""","""""","""""","""""""',    'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","","""abc"""',  'expected_strict' => 1, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","","""abc"""',  'expected_strict' => 1, 'expected_non_strict' => 3],
            ['line' => ',,"""0,00""","","""","""abc"""',  'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => ',"""""","","""","""""",""""""",', 'expected_strict' => 0, 'expected_non_strict' => 3],
        ];

        foreach ($tests as $test) {
            $line   = CSVDataLine::fromString($test['line']);
            $fields = $line->getFields();

            // alle enclosureRepeats erfassen
            $repeats = array_map(fn($f) => $f->getEnclosureRepeat(), $fields);
            $positive = array_filter($repeats, fn($v) => $v > 0);
            $strict = 0;
            if (!empty($positive) && count($positive) === count($repeats)) {
                $strict = min($positive);
            }
            $nonStrict = empty($positive) ? 0 : max($positive);

            $this->assertSame(
                $test['expected_strict'],
                $strict,
                sprintf(
                    "Fehler (strict) bei Zeile: %s – erhalten %d (Repeats: %s)",
                    $test['line'],
                    $strict,
                    json_encode($repeats)
                )
            );

            $this->assertSame(
                $test['expected_non_strict'],
                $nonStrict,
                sprintf(
                    "Fehler (non-strict) bei Zeile: %s – erhalten %d (Repeats: %s)",
                    $test['line'],
                    $nonStrict,
                    json_encode($repeats)
                )
            );
        }
    }

    public function testLongStrings(): void {
        $longValues = [
            '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000,00"",""2000,00"",""0,00"",""EUR""',
            '""KDC2ASKF"",""21.12.2024 17:55:41"",""c832c84d-4940-484d-a7fb-4bc98cff6a88"","""",""ich@irgendwo.com"",""Schlussbilanz"","""","""","""","""","""","""",""2000"",""2000"",""0"",""EUR""'
        ];

        foreach ($longValues as $longValue) {
            $line = CSVDataLine::fromString($longValue);
            $fields = $line->getFields();

            $this->assertEquals(16, $line->countFields());
            $this->assertSame('KDC2ASKF', $fields[0]->getValue());
            $this->assertSame('21.12.2024 17:55:41', $fields[1]->getValue());
            $this->assertSame('c832c84d-4940-484d-a7fb-4bc98cff6a88', $fields[2]->getValue());
            $this->assertSame($longValue, $line->toString());
        }
    }

    public function testEmptyAndWhitespaceFields(): void {
        $line = CSVDataLine::fromString('"A",,"C"');
        $fields = $line->getFields();

        $this->assertSame('A', $fields[0]->getValue());
        $this->assertSame('', $fields[1]->getValue());
        $this->assertSame('C', $fields[2]->getValue());
        $this->assertSame('"A",,"C"', $line->toString());
    }

    public function testEscapedQuotesInValue(): void {
        $line = CSVDataLine::fromString('"A ""quoted"" text","B"');
        $fields = $line->getFields();

        $this->assertSame('A ""quoted"" text', $fields[0]->getValue());
        $this->assertSame('"A ""quoted"" text","B"', $line->toString());
    }

    public function testRawFieldPreserved(): void {
        $line = CSVDataLine::fromString('"A","B","C"');
        $field = $line->getField(1);

        $this->assertNotNull($field);
        $this->assertSame('"B"', $field->getRaw());
    }

    public function testRoundTripWithDelimiterSemicolon(): void {
        $line = CSVDataLine::fromString('"A";"B";"C"', ';');
        $rebuilt = $line->toString(';');

        $this->assertSame('"A";"B";"C"', $rebuilt);
    }

    public function testUnquotedFields(): void {
        $line = CSVDataLine::fromString('A,B,C');
        $fields = $line->getFields();

        foreach ($fields as $field) {
            $this->assertFalse($field->isQuoted(), 'Feld sollte unquoted sein');
        }

        $this->assertSame('A,B,C', $line->toString());
    }

    public function testQuotedFieldWithDelimiterInside(): void {
        $line = CSVDataLine::fromString('"A,B",C');
        $fields = $line->getFields();

        $this->assertSame('A,B', $fields[0]->getValue());
        $this->assertTrue($fields[0]->isQuoted());
        $this->assertSame('"A,B",C', $line->toString());
    }
}