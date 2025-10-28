<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Description  : Unit Tests für CSVLine und CSVField Klassen.
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\CSVLine;
use CommonToolkit\Entities\Common\CSV\CSVField;
use PHPUnit\Framework\TestCase;

class CSVLineTest extends TestCase {
    public function testSimpleQuotedFields(): void {
        $line = CSVLine::fromString('"A","B","C"');

        $this->assertCount(3, $line->getFields(), '3 Felder erwartet');

        foreach ($line->getFields() as $field) {
            $this->assertInstanceOf(CSVField::class, $field);
            $this->assertTrue($field->isQuoted(), 'Feld sollte gequotet sein');
            $this->assertSame(1, $field->getEnclosureRepeat(), 'Einfaches Quote erwartet');
        }

        $this->assertSame('"A","B","C"', $line->toString());
    }

    public function testMixedQuotedAndUnquotedFields(): void {
        $line = CSVLine::fromString('"A",B,"C"');
        $fields = $line->getFields();

        $this->assertTrue($fields[0]->isQuoted());
        $this->assertFalse($fields[1]->isQuoted());
        $this->assertTrue($fields[2]->isQuoted());

        $this->assertSame('"A",B,"C"', $line->toString());
    }

    public function testEnclosureRepeatDetection(): void {
        $tests = [
            ['line' => '""A"",""""B"""","C"',             'expected_strict' => 1, 'expected_non_strict' => 4],
            ['line' => '"A","B","C"',                     'expected_strict' => 1, 'expected_non_strict' => 1],
            ['line' => 'A,B,C',                           'expected_strict' => 0, 'expected_non_strict' => 0],
            ['line' => ',""',                             'expected_strict' => 0, 'expected_non_strict' => 1],
            ['line' => ',""""',                           'expected_strict' => 0, 'expected_non_strict' => 2],
            ['line' => '"abc",""def""',                   'expected_strict' => 1, 'expected_non_strict' => 2],
            ['line' => '""abc""',                         'expected_strict' => 2, 'expected_non_strict' => 2],
            ['line' => '"""","""abc"""',                  'expected_strict' => 2, 'expected_non_strict' => 3],
            ['line' => '"""0,00""","""abc"""',            'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => ',"""0,00""","""abc"""',           'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => ',,"""0,00""",,"""abc"""',         'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => '"""","""0,00""","""abc"""',       'expected_strict' => 2, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","""abc"""',     'expected_strict' => 3, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","","""abc"""',  'expected_strict' => 1, 'expected_non_strict' => 3],
            ['line' => '"""""","""0,00""","","""abc"""',  'expected_strict' => 1, 'expected_non_strict' => 3],
            ['line' => ',,"""0,00""","","""","""abc"""',  'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => ',"""""","","""","""""",""""""",', 'expected_strict' => 0, 'expected_non_strict' => 3],
            ['line' => '"""""","""""","""""","""""""',    'expected_strict' => 3, 'expected_non_strict' => 3],
        ];

        foreach ($tests as $test) {
            $line   = CSVLine::fromString($test['line']);
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

    public function testEmptyAndWhitespaceFields(): void {
        $line = CSVLine::fromString('"A",,"C"');
        $fields = $line->getFields();

        $this->assertSame('A', $fields[0]->getValue());
        $this->assertSame('', $fields[1]->getValue());
        $this->assertSame('C', $fields[2]->getValue());
        $this->assertSame('"A",,"C"', $line->toString());
    }

    public function testEscapedQuotesInValue(): void {
        $line = CSVLine::fromString('"A ""quoted"" text","B"');
        $fields = $line->getFields();

        $this->assertSame('A "quoted" text', $fields[0]->getValue());
        $this->assertSame('"A ""quoted"" text","B"', $line->toString());
    }

    public function testRawFieldPreserved(): void {
        $line = CSVLine::fromString('"A","B","C"');
        $field = $line->getField(1);

        $this->assertNotNull($field);
        $this->assertSame('"B"', $field->getRaw());
    }

    public function testRoundTripWithDelimiterSemicolon(): void {
        $line = CSVLine::fromString('"A";"B";"C"', ';');
        $rebuilt = $line->toString(';');

        $this->assertSame('"A";"B";"C"', $rebuilt);
    }

    public function testUnquotedFields(): void {
        $line = CSVLine::fromString('A,B,C');
        $fields = $line->getFields();

        foreach ($fields as $field) {
            $this->assertFalse($field->isQuoted(), 'Feld sollte unquoted sein');
        }

        $this->assertSame('A,B,C', $line->toString());
    }

    public function testQuotedFieldWithDelimiterInside(): void {
        $line = CSVLine::fromString('"A,B",C');
        $fields = $line->getFields();

        $this->assertSame('A,B', $fields[0]->getValue());
        $this->assertTrue($fields[0]->isQuoted());
        $this->assertSame('"A,B",C', $line->toString());
    }
}