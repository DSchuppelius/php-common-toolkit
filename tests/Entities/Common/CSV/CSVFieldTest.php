<?php
/*
 * Created on   : Thu Oct 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Description  : Unit Tests für CSVField – prüft Analyse, Quoting, Escaping und Wiederaufbau.
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\CSVField;
use PHPUnit\Framework\TestCase;

class CSVFieldTest extends TestCase {
    public function testSimpleQuotedValue(): void {
        $field = new CSVField('"ABC"');
        $this->assertTrue($field->isQuoted(), 'Feld sollte gequotet sein');
        $this->assertSame('ABC', $field->getValue());
        $this->assertSame(1, $field->getEnclosureRepeat());
        $this->assertSame('"ABC"', $field->toString());
    }

    public function testUnquotedValue(): void {
        $field = new CSVField('ABC');
        $this->assertFalse($field->isQuoted());
        $this->assertSame('ABC', $field->getValue());
        $this->assertSame(0, $field->getEnclosureRepeat());
        $this->assertSame('ABC', $field->toString());
    }

    public function testEmptyQuotedValue(): void {
        $field = new CSVField('""');
        $this->assertTrue($field->isQuoted());
        $this->assertSame('', $field->getValue());
        $this->assertSame(1, $field->getEnclosureRepeat());
        $this->assertSame('""', $field->toString());
    }

    public function testRepeatedEnclosures(): void {
        $tests = [
            // reine leere Felder
            ['raw' => '""',        'expected_value' => '',       'expected_repeat' => 1],
            ['raw' => '""""',      'expected_value' => '',       'expected_repeat' => 2],
            ['raw' => '""""""',    'expected_value' => '',       'expected_repeat' => 3],

            // normale Werte
            ['raw' => '""ABC""',       'expected_value' => 'ABC',      'expected_repeat' => 2],
            ['raw' => '"""ABC"""',     'expected_value' => 'ABC',      'expected_repeat' => 3],
            ['raw' => '""""A""""',     'expected_value' => 'A',        'expected_repeat' => 4],
            ['raw' => '""A "B" C""',   'expected_value' => 'A "B" C',  'expected_repeat' => 2],
            ['raw' => '""A ""B"" C""', 'expected_value' => 'A ""B"" C', 'expected_repeat' => 2],
            ['raw' => '""""quoted""""', 'expected_value' => 'quoted',   'expected_repeat' => 4],
            ['raw' => '""""quoted"""', 'expected_value' => '"quoted',  'expected_repeat' => 3],
            ['raw' => '"""quoted""""', 'expected_value' => 'quoted"',  'expected_repeat' => 3],
        ];

        foreach ($tests as $test) {
            $field = new CSVField($test['raw']);

            $this->assertTrue(
                $field->isQuoted(),
                sprintf("Feld sollte quoted sein – Input: %s", $test['raw'])
            );

            $this->assertSame(
                $test['expected_repeat'],
                $field->getEnclosureRepeat(),
                sprintf("Falscher Repeat-Level für %s", $test['raw'])
            );

            $this->assertSame(
                $test['expected_value'],
                $field->getValue(),
                sprintf("Falscher Value für %s", $test['raw'])
            );

            $this->assertSame(
                $test['raw'],
                $field->toString(),
                sprintf("Rebuild-Fehler bei %s", $test['raw'])
            );
        }
    }
    public function testEscapedQuotesInsideValue(): void {
        $field = new CSVField('"A ""quoted"" text"');
        $this->assertTrue($field->isQuoted());
        $this->assertSame('A "quoted" text', $field->getValue());
        $this->assertSame('"A ""quoted"" text"', $field->toString());
    }

    public function testWhitespaceAroundValue(): void {
        $field = new CSVField(' "ABC" ');
        $this->assertTrue($field->isQuoted(), 'Whitespace außen soll erkannt, aber ignoriert werden');
        $this->assertSame('ABC', $field->getValue());
    }

    public function testRawValuePreserved(): void {
        $raw = '"ABC"';
        $field = new CSVField($raw);
        $this->assertSame($raw, $field->getRaw());
    }

    public function testDifferentEnclosureCharacter(): void {
        $field = new CSVField("'XYZ'", "'");
        $this->assertTrue($field->isQuoted());
        $this->assertSame('XYZ', $field->getValue());
        $this->assertSame("'", $field->toString("'")[0]);
    }

    public function testNonMatchingQuoteDoesNotQuote(): void {
        $field = new CSVField('"ABC');
        $this->assertFalse($field->isQuoted(), 'Ungeschlossene Quotes dürfen nicht als Quote erkannt werden');
        $this->assertSame('"ABC', $field->getValue());
        $this->assertSame('"ABC', $field->toString());
    }

    public function testEmptyFieldWithoutQuotes(): void {
        $field = new CSVField('');
        $this->assertFalse($field->isQuoted());
        $this->assertSame('', $field->getValue());
        $this->assertSame(0, $field->getEnclosureRepeat());
        $this->assertSame('', $field->toString());
    }
}