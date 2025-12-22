<?php

declare(strict_types=1);

namespace Tests\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\DataField;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class TypedFieldTest extends BaseTestCase {

    public function testIntegerDetectionAndConversion(): void {
        $field = new DataField('42');

        $this->assertFalse($field->isQuoted());
        $this->assertTrue($field->isInt());
        $this->assertEquals(42, $field->getTypedValue());
        $this->assertTrue($field->isFloat()); // Integer sind auch gültige Floats
        $this->assertFalse($field->isBool());
        $this->assertFalse($field->isDateTime());
    }

    public function testFloatDetectionAndConversion(): void {
        $field = new DataField('3.14');

        $this->assertFalse($field->isQuoted());
        $this->assertTrue($field->isFloat());
        $this->assertEquals(3.14, $field->getTypedValue());
        $this->assertFalse($field->isInt());
        $this->assertFalse($field->isBool());
    }

    public function testFloatWithGermanDecimalSeparator(): void {
        $field = new DataField('3,14');

        $this->assertTrue($field->isFloat());
        $this->assertEquals(3.14, $field->getTypedValue());
    }

    public function testBooleanDetectionAndConversion(): void {
        $trueValues = ['true', 'TRUE', 'yes', 'YES', 'on', 'ON'];
        $falseValues = ['false', 'FALSE', 'no', 'NO', 'off', 'OFF'];

        foreach ($trueValues as $value) {
            $field = new DataField($value);
            $this->assertTrue($field->isBool(), "'{$value}' should be detected as boolean");
            $this->assertTrue($field->getTypedValue(), "'{$value}' should convert to true");
        }

        foreach ($falseValues as $value) {
            $field = new DataField($value);
            $this->assertTrue($field->isBool(), "'{$value}' should be detected as boolean");
            $this->assertFalse($field->getTypedValue(), "'{$value}' should convert to false");
        }

        // Numerische Werte werden als Integer erkannt, nicht als Boolean
        $numericField = new DataField('1');
        $this->assertTrue($numericField->isInt());
        $this->assertFalse($numericField->isBool());
        $this->assertSame(1, $numericField->getTypedValue());

        $zeroField = new DataField('0');
        $this->assertTrue($zeroField->isInt());
        $this->assertFalse($zeroField->isBool());
        $this->assertSame(0, $zeroField->getTypedValue());
    }

    public function testDateTimeDetectionAndConversion(): void {
        $testCases = [
            '2025-12-22 15:30:00' => 'Y-m-d H:i:s',
            '2025-12-22T15:30:00' => 'Y-m-d\TH:i:s',
            '2025-12-22' => 'Y-m-d',
            '22.12.2025' => 'd.m.Y',
            '22.12.2025 15:30:00' => 'd.m.Y H:i:s',
            '22/12/2025' => 'd/m/Y',
            '12/22/2025' => 'm/d/Y'
        ];

        foreach ($testCases as $value => $expectedFormat) {
            $field = new DataField($value);
            $this->assertTrue($field->isDateTime(), "'{$value}' should be detected as datetime");

            $dateTime = $field->getTypedValue();
            $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        }
    }

    public function testUnixTimestampDetection(): void {
        $timestamp = '1735225800'; // 2024-12-26 15:30:00 UTC (10-stelliger Timestamp)
        $field = new DataField($timestamp);

        $this->assertTrue($field->isDateTime());
        $dateTime = $field->getTypedValue();
        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertEquals('2024-12-26', $dateTime->format('Y-m-d'));

        // Normale Integer (nicht Timestamp-Länge)
        $normalInt = new DataField('123');
        $this->assertTrue($normalInt->isInt());
        $this->assertFalse($normalInt->isDateTime());

        // Standard DateTime-Format
        $dateField = new DataField('2024-12-26');
        $this->assertTrue($dateField->isDateTime());
        $this->assertInstanceOf(DateTimeImmutable::class, $dateField->getTypedValue());
    }

    public function testQuotedFieldsReturnNull(): void {
        $field = new DataField('"42"');

        $this->assertTrue($field->isQuoted());
        $this->assertEquals('42', $field->getValue()); // String value bleibt verfügbar

        // Type detection gibt false zurück für quoted Fields
        $this->assertFalse($field->isInt());
        $this->assertFalse($field->isFloat());
        $this->assertFalse($field->isBool());
        $this->assertFalse($field->isDateTime());
    }

    public function testEmptyFieldsReturnNull(): void {
        $field = new DataField('');

        $this->assertFalse($field->isQuoted());
        $this->assertTrue($field->isEmpty());

        // Type detection gibt false zurück für leere Fields
        $this->assertFalse($field->isInt());
        $this->assertFalse($field->isFloat());
        $this->assertFalse($field->isBool());
        $this->assertFalse($field->isDateTime());
    }

    public function testCustomDateTimeFormat(): void {
        $field = new DataField('26-12-2025');

        // strtotime erkennt dieses Format, also wird es als DateTime erkannt
        $this->assertTrue($field->isDateTime());
        $this->assertTrue($field->isDateTime('d-m-Y')); // Und auch mit custom Format

        $dateTime = $field->getTypedValue();
        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertEquals('2025-12-26', $dateTime->format('Y-m-d'));
    }

    public function testMixedTypeField(): void {
        // "1" wird als Integer erkannt (höchste Priorität)
        $field = new DataField('1');

        $this->assertTrue($field->isInt());     // Primärer Typ
        $this->assertTrue($field->isFloat());   // Integer sind auch gültige Floats
        $this->assertFalse($field->isBool());  // Wird nicht als Boolean erkannt (Integer hat Vorrang)

        $this->assertSame(1, $field->getTypedValue());

        // Echter Boolean-Wert
        $boolField = new DataField('true');
        $this->assertTrue($boolField->isBool());
        $this->assertFalse($boolField->isInt());
        $this->assertTrue($boolField->getTypedValue());
    }

    public function testInvalidValues(): void {
        $field = new DataField('not-a-number');

        $this->assertFalse($field->isInt());
        $this->assertFalse($field->isFloat());
        $this->assertFalse($field->isBool());
        $this->assertFalse($field->isDateTime());
    }

    public function testTypedValueDirectAccess(): void {
        // Integer
        $field = new DataField('42');
        $this->assertSame(42, $field->getTypedValue());
        $this->assertTrue($field->isInt());

        // Float
        $field = new DataField('3.14');
        $this->assertSame(3.14, $field->getTypedValue());
        $this->assertTrue($field->isFloat());

        // German decimal
        $field = new DataField('3,14');
        $this->assertSame(3.14, $field->getTypedValue());

        // Boolean
        $field = new DataField('true');
        $this->assertSame(true, $field->getTypedValue());
        $this->assertTrue($field->isBool());

        // DateTime
        $field = new DataField('2025-12-22');
        $this->assertInstanceOf(DateTimeImmutable::class, $field->getTypedValue());

        // Quoted stays string
        $field = new DataField('"42"');
        $this->assertSame('42', $field->getTypedValue());
        $this->assertTrue($field->isString());

        // String fallback
        $field = new DataField('not-a-type');
        $this->assertSame('not-a-type', $field->getTypedValue());
        $this->assertTrue($field->isString());
    }
}
