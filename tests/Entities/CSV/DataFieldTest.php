<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataFieldTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\CSV;

use CommonToolkit\Entities\CSV\DataField;
use Tests\Contracts\BaseTestCase;

class DataFieldTest extends BaseTestCase {
    public function testSimpleQuotedValue(): void {
        $field = new DataField('"ABC"');
        $this->assertTrue($field->isQuoted(), 'Feld sollte gequotet sein');
        $this->assertSame('ABC', $field->getValue());
        $this->assertSame(1, $field->getEnclosureRepeat());
        $this->assertSame('"ABC"', $field->toString());
    }

    public function testUnquotedValue(): void {
        $field = new DataField('ABC');
        $this->assertFalse($field->isQuoted());
        $this->assertSame('ABC', $field->getValue());
        $this->assertSame(0, $field->getEnclosureRepeat());
        $this->assertSame('ABC', $field->toString());
    }

    public function testEmptyQuotedValue(): void {
        $field = new DataField('""');
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
            $field = new DataField($test['raw']);

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
        $field = new DataField('"A ""quoted"" text"');
        $this->assertTrue($field->isQuoted());
        $this->assertSame('A ""quoted"" text', $field->getValue());
        $this->assertSame('"A ""quoted"" text"', $field->toString());
    }

    public function testWhitespaceAroundValue(): void {
        $field = new DataField(' "ABC" ');
        $this->assertTrue($field->isQuoted(), 'Whitespace außen soll erkannt, aber ignoriert werden');
        $this->assertSame('ABC', $field->getValue());
    }

    public function testRawValuePreserved(): void {
        $raw = '"ABC"';
        $field = new DataField($raw);
        $this->assertSame($raw, $field->getRaw());
    }

    public function testDifferentEnclosureCharacter(): void {
        $field = new DataField("'XYZ'", "'");
        $this->assertTrue($field->isQuoted());
        $this->assertSame('XYZ', $field->getValue());
        $this->assertSame("'XYZ'", $field->toString("'"));
    }

    public function testNonMatchingQuoteDoesNotQuote(): void {
        $field = new DataField('"ABC');
        $this->assertFalse($field->isQuoted(), 'Ungeschlossene Quotes dürfen nicht als Quote erkannt werden');
        $this->assertSame('"ABC', $field->getValue());
        $this->assertSame('"ABC', $field->toString());
    }

    public function testEmptyFieldWithoutQuotes(): void {
        $field = new DataField('');
        $this->assertFalse($field->isQuoted());
        $this->assertSame('', $field->getValue());
        $this->assertSame(0, $field->getEnclosureRepeat());
        $this->assertSame('', $field->toString());
    }

    // ========== Tests für setValue() ==========

    public function testSetValueChangesValueMutably(): void {
        $field = new DataField('"ABC"');
        $this->assertSame('ABC', $field->getValue());

        $field->setValue('XYZ');
        $this->assertSame('XYZ', $field->getValue());
        $this->assertTrue($field->isQuoted(), 'Quote-Status bleibt erhalten');
    }

    public function testSetValueWithUnquotedFieldDetectsTypes(): void {
        $field = new DataField('test');
        $this->assertFalse($field->isQuoted());

        $field->setValue('42');
        $this->assertSame(42, $field->getTypedValue(), 'Integer sollte erkannt werden');

        $field->setValue('3.14');
        $this->assertEquals(3.14, $field->getTypedValue(), 'Float sollte erkannt werden');

        $field->setValue('true');
        $this->assertTrue($field->getTypedValue(), 'Boolean sollte erkannt werden');
    }

    public function testSetValueWithQuotedFieldPreservesStringType(): void {
        $field = new DataField('"test"');
        $this->assertTrue($field->isQuoted());

        $field->setValue('42');
        $this->assertSame('42', $field->getTypedValue(), 'Bei quoted bleibt String erhalten');
    }

    // ========== Tests für withValue() ==========

    public function testWithValueReturnsNewInstance(): void {
        $original = new DataField('"ABC"');
        $new = $original->withValue('XYZ');

        $this->assertNotSame($original, $new, 'withValue muss neue Instanz zurückgeben');
        $this->assertSame('ABC', $original->getValue(), 'Original bleibt unverändert');
        $this->assertSame('XYZ', $new->getValue(), 'Neue Instanz hat neuen Wert');
    }

    public function testWithValuePreservesQuotedStatus(): void {
        $quoted = new DataField('"ABC"');
        $newQuoted = $quoted->withValue('XYZ');
        $this->assertTrue($newQuoted->isQuoted(), 'Quote-Status muss erhalten bleiben');

        $unquoted = new DataField('ABC');
        $newUnquoted = $unquoted->withValue('XYZ');
        $this->assertFalse($newUnquoted->isQuoted(), 'Unquoted-Status muss erhalten bleiben');
    }

    public function testWithValueDetectsTypesForUnquotedFields(): void {
        $field = new DataField('123'); // unquoted integer field

        $intField = $field->withValue('456');
        $this->assertSame(456, $intField->getTypedValue());

        $floatField = $field->withValue('3,14');
        $this->assertEquals(3.14, $floatField->getTypedValue());

        $boolField = $field->withValue('yes');
        $this->assertTrue($boolField->getTypedValue());
    }

    public function testWithValuePreservesEnclosureRepeat(): void {
        $field = new DataField('""ABC""');
        $this->assertSame(2, $field->getEnclosureRepeat());

        $new = $field->withValue('XYZ');
        $this->assertSame(2, $new->getEnclosureRepeat(), 'Enclosure-Repeat muss erhalten bleiben');
    }

    // ========== Tests für withTypedValue() ==========

    public function testWithTypedValueReturnsNewInstance(): void {
        $original = new DataField('100');
        $new = $original->withTypedValue(200);

        $this->assertNotSame($original, $new);
        $this->assertSame(100, $original->getTypedValue());
        $this->assertSame(200, $new->getTypedValue());
    }

    public function testWithTypedValueAcceptsInteger(): void {
        $field = new DataField('0');
        $new = $field->withTypedValue(42);

        $this->assertSame(42, $new->getTypedValue());
        $this->assertSame('42', $new->getValue());
    }

    public function testWithTypedValueAcceptsFloat(): void {
        $field = new DataField('0');
        $new = $field->withTypedValue(3.14159);

        $this->assertEquals(3.14159, $new->getTypedValue());
        $this->assertSame('3.14159', $new->getValue());
    }

    public function testWithTypedValueAcceptsBoolean(): void {
        $field = new DataField('0');

        $trueField = $field->withTypedValue(true);
        $this->assertTrue($trueField->getTypedValue());
        $this->assertSame('1', $trueField->getValue(), 'Boolean true wird zu "1" konvertiert');

        $falseField = $field->withTypedValue(false);
        $this->assertFalse($falseField->getTypedValue());
        $this->assertSame('', $falseField->getValue(), 'Boolean false wird zu leerem String konvertiert');
    }

    public function testWithTypedValueAcceptsNull(): void {
        $field = new DataField('test');
        $new = $field->withTypedValue(null);

        $this->assertNull($new->getTypedValue());
        $this->assertSame('', $new->getValue());
    }

    public function testWithTypedValueAcceptsDateTimeImmutable(): void {
        $field = new DataField('2025-01-01');
        $date = new \DateTimeImmutable('2025-12-26');
        $new = $field->withTypedValue($date);

        $this->assertInstanceOf(\DateTimeImmutable::class, $new->getTypedValue());
        // Das originalFormat vom Quell-Feld bestimmt das Ausgabeformat
        $this->assertSame('2025-12-26', $new->getValue());
    }

    public function testWithTypedValuePreservesQuotedStatus(): void {
        $quoted = new DataField('"100"');
        $newQuoted = $quoted->withTypedValue(200);
        $this->assertTrue($newQuoted->isQuoted());

        $unquoted = new DataField('100');
        $newUnquoted = $unquoted->withTypedValue(200);
        $this->assertFalse($newUnquoted->isQuoted());
    }

    public function testWithTypedValueNoTypeAnalysis(): void {
        // withTypedValue setzt den Wert direkt, ohne Analyse
        $field = new DataField('text');
        $new = $field->withTypedValue('42'); // String "42", nicht Integer

        $this->assertSame('42', $new->getTypedValue(), 'String muss String bleiben');
        $this->assertSame('42', $new->getValue());
    }

    // ========== Kombinations-Tests ==========

    public function testImmutabilityChain(): void {
        $original = new DataField('10');
        $step1 = $original->withTypedValue(20);
        $step2 = $step1->withTypedValue(30);
        $step3 = $step2->withTypedValue(40);

        $this->assertSame(10, $original->getTypedValue());
        $this->assertSame(20, $step1->getTypedValue());
        $this->assertSame(30, $step2->getTypedValue());
        $this->assertSame(40, $step3->getTypedValue());
    }

    public function testMutableVsImmutableBehavior(): void {
        $field = new DataField('original');

        // Mutable: ändert das Objekt
        $field->setValue('mutable');
        $this->assertSame('mutable', $field->getValue());

        // Immutable: gibt neues Objekt zurück
        $newField = $field->withValue('immutable');
        $this->assertSame('mutable', $field->getValue(), 'Original unverändert');
        $this->assertSame('immutable', $newField->getValue(), 'Neues Objekt hat neuen Wert');
    }
}