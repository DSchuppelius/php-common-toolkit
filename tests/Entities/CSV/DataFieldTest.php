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
        $raw = ' "ABC" ';
        $field = new DataField($raw);
        $this->assertTrue($field->isQuoted(), 'Whitespace außen soll erkannt werden');
        $this->assertSame('ABC', $field->getValue());
        // Bei quoted Fields wird Whitespace außerhalb der Quotes ignoriert
        $this->assertSame('"ABC"', $field->toString(), 'toString() ignoriert äußeren Whitespace bei quoted');
        $this->assertSame('"ABC"', $field->toString(null, true), 'toString(trimmed: true) identisch');
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

    // ========== Tests für Whitespace-Erhaltung und toString() mit trimmed Parameter ==========

    public function testUnquotedFieldPreservesTrailingWhitespace(): void {
        $raw = 'Aussenanlage               ';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame('Aussenanlage', $field->getValue(), 'getValue() liefert getrimmten Wert');
        $this->assertSame($raw, $field->toString(), 'toString() erhält Whitespace für Round-Trip');
        $this->assertSame($raw, $field->getRaw(), 'getRaw() liefert Original');
    }

    public function testUnquotedFieldPreservesLeadingWhitespace(): void {
        $raw = '   ABC';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame('ABC', $field->getValue());
        $this->assertSame($raw, $field->toString(), 'toString() erhält Leading-Whitespace');
    }

    public function testUnquotedFieldPreservesBothWhitespaces(): void {
        $raw = '  Value mit Spaces  ';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame('Value mit Spaces', $field->getValue());
        $this->assertSame($raw, $field->toString(), 'toString() erhält beide Whitespaces');
    }

    public function testToStringTrimmedForUnquotedField(): void {
        $raw = '  Aussenanlage               ';
        $field = new DataField($raw);

        $this->assertSame($raw, $field->toString(), 'toString() ohne Parameter erhält Whitespace');
        $this->assertSame('Aussenanlage', $field->toString(null, true), 'toString(trimmed: true) liefert getrimmten Wert');
    }

    public function testToStringTrimmedForQuotedField(): void {
        $raw = '"  Wert mit Spaces  "';
        $field = new DataField($raw);

        $this->assertTrue($field->isQuoted());
        $this->assertSame('  Wert mit Spaces  ', $field->getValue(), 'getValue() liefert inneren Wert mit Spaces');
        $this->assertSame($raw, $field->toString(), 'toString() erhält Original');
        // trimmed hat bei quoted Fields keine Auswirkung (kein äußerer Whitespace)
        $this->assertSame($raw, $field->toString(null, true), 'toString(trimmed: true) identisch');
    }

    public function testToStringTrimmedWithCustomEnclosure(): void {
        $field = new DataField("'  Value  '", "'");

        $this->assertTrue($field->isQuoted());
        $this->assertSame("'  Value  '", $field->toString("'"), 'toString() erhält inneren Whitespace');
        // trimmed hat bei quoted Fields keine Auswirkung
        $this->assertSame("'  Value  '", $field->toString("'", true), 'toString(trimmed: true) identisch');
    }

    public function testWhitespaceFieldRoundTrip(): void {
        // Ein Feld das nur aus Whitespace besteht
        // Bei reinem Whitespace werden Leading und Trailing separat gespeichert,
        // da der Wert nach trim() leer ist
        $raw = '   ';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame('', $field->getValue(), 'getValue() ist leer nach Trim');
        // Whitespace-only Fields: getRaw() zeigt das Original
        $this->assertSame($raw, $field->getRaw(), 'getRaw() liefert Original');
        $this->assertSame('', $field->toString(null, true), 'toString(trimmed: true) ist leer');
    }

    public function testTypedValueWithWhitespace(): void {
        // Integer mit Leading/Trailing Whitespace
        $raw = '  42  ';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame(42, $field->getTypedValue(), 'Integer wird erkannt trotz Whitespace');
        $this->assertSame('42', $field->getValue());
        $this->assertSame($raw, $field->toString(), 'toString() erhält Whitespace');
        $this->assertSame('42', $field->toString(null, true), 'toString(trimmed: true) ohne Whitespace');
    }

    public function testFloatWithWhitespace(): void {
        // Float mit Trailing Whitespace
        $raw = '3,14   ';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertEquals(3.14, $field->getTypedValue(), 'Float wird erkannt');
        $this->assertSame($raw, $field->toString(), 'toString() erhält Trailing-Whitespace');
        $this->assertSame('3,14', $field->toString(null, true), 'toString(trimmed: true) ohne Whitespace');
    }

    public function testDateWithWhitespace(): void {
        // Datum mit Leading Whitespace
        $raw = '   2025-12-26';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $field->getTypedValue(), 'Datum wird erkannt');
        $this->assertSame('2025-12-26', $field->getValue());
        $this->assertSame($raw, $field->toString(), 'toString() erhält Leading-Whitespace');
        $this->assertSame('2025-12-26', $field->toString(null, true), 'toString(trimmed: true) ohne Whitespace');
    }

    public function testEmptyFieldPreservation(): void {
        $raw = '';
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted());
        $this->assertSame('', $field->getValue());
        $this->assertSame('', $field->toString());
        $this->assertSame('', $field->toString(null, true));
    }

    public function testRepeatedEnclosureWithTrimmed(): void {
        $raw = '""  Inner Value  ""';
        $field = new DataField($raw);

        $this->assertTrue($field->isQuoted());
        $this->assertSame(2, $field->getEnclosureRepeat());
        $this->assertSame('  Inner Value  ', $field->getValue());
        $this->assertSame($raw, $field->toString(), 'toString() erhält Original');
        // trimmed hat bei quoted Fields keine Auswirkung
        $this->assertSame($raw, $field->toString(null, true), 'toString(trimmed: true) identisch');
    }

    public function testQuotedFieldWithOuterWhitespace(): void {
        // Quoted Field mit Whitespace außerhalb der Quotes - wird ignoriert
        $raw = '   "Quoted Value"   ';
        $field = new DataField($raw);

        $this->assertTrue($field->isQuoted());
        $this->assertSame('Quoted Value', $field->getValue());
        // Äußerer Whitespace bei quoted Fields wird ignoriert
        $this->assertSame('"Quoted Value"', $field->toString(), 'toString() ohne äußeren Whitespace');
        $this->assertSame('"Quoted Value"', $field->toString(null, true), 'toString(trimmed: true) identisch');
    }

    public function testQuotedFieldWithInnerAndOuterWhitespace(): void {
        // Quoted Field mit Whitespace innen UND außen
        // Äußerer Whitespace wird ignoriert, innerer bleibt erhalten
        $raw = '  "  Inner Spaces  "  ';
        $field = new DataField($raw);

        $this->assertTrue($field->isQuoted());
        $this->assertSame('  Inner Spaces  ', $field->getValue(), 'Innerer Whitespace bleibt im Value');
        // Äußerer Whitespace wird ignoriert, innerer bleibt
        $this->assertSame('"  Inner Spaces  "', $field->toString(), 'toString() ohne äußeren Whitespace');
        $this->assertSame('"  Inner Spaces  "', $field->toString(null, true), 'toString(trimmed: true) identisch');
    }

    public function testEmptyQuotedFieldWithOuterWhitespace(): void {
        // Leeres quoted Field mit Whitespace außen
        // Hinweis: Reines Quote-Feld wird als Sonderfall behandelt (vor Whitespace-Extraktion)
        $raw = '  ""  ';
        $field = new DataField($raw);

        $this->assertTrue($field->isQuoted());
        $this->assertSame('', $field->getValue());
        // Bei reinen Quote-Feldern wird der äußere Whitespace nicht gespeichert (Sonderfall)
        $this->assertSame('""', $field->toString(), 'toString() gibt nur Quotes zurück');
        $this->assertSame('""', $field->toString(null, true), 'toString(trimmed: true) ohne äußeren Whitespace');
    }

    public function testUnquotedFieldWithOnlyWhitespace(): void {
        // Unquoted Field, das nur aus Whitespace besteht (z.B. 27 Leerzeichen)
        // Wichtig: Round-Trip muss exakt funktionieren, ohne Verdoppelung des Whitespace
        $raw = str_repeat(' ', 27);
        $field = new DataField($raw);

        $this->assertFalse($field->isQuoted(), 'Nur-Whitespace-Feld ist nicht gequotet');
        $this->assertSame('', $field->getValue(), 'getValue() gibt leeren String zurück');
        $this->assertSame($raw, $field->getRaw(), 'getRaw() gibt Original zurück');
        $this->assertSame($raw, $field->toString(), 'toString() muss exakt dem Original entsprechen');
        $this->assertSame(27, strlen($field->toString()), 'toString() Länge muss 27 sein (nicht 54)');
    }

    public function testUnquotedFieldWithVariousWhitespaceOnlyContent(): void {
        // Verschiedene Whitespace-Varianten testen
        $tests = [
            str_repeat(' ', 1),   // 1 Leerzeichen
            str_repeat(' ', 5),   // 5 Leerzeichen
            str_repeat(' ', 27),  // 27 Leerzeichen (Original-Problem)
            str_repeat(' ', 100), // 100 Leerzeichen
            "\t",                 // Tab
            "\t\t\t",             // Mehrere Tabs
            "   \t   ",           // Gemischt Leerzeichen und Tabs
        ];

        foreach ($tests as $raw) {
            $field = new DataField($raw);

            $this->assertFalse($field->isQuoted(), sprintf('Whitespace-Feld (%d Zeichen) sollte nicht gequotet sein', strlen($raw)));
            $this->assertSame($raw, $field->toString(), sprintf('Round-Trip muss für "%s" (Länge %d) exakt funktionieren', addcslashes($raw, "\t"), strlen($raw)));
        }
    }
}
