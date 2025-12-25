<?php

namespace Tests\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\DataField;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\Common\CSV\HeaderField;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use Tests\Contracts\BaseTestCase;

class DataFieldHeaderReferenceTest extends BaseTestCase {
    public function testDataFieldsKnowTheirHeaderFields(): void {
        // Header erstellen
        $headerLine = new HeaderLine(['Name', 'Age', 'Email']);

        // DataLine mit HeaderLine-Referenz erstellen
        $dataLine = new DataLine(['John', '30', 'john@example.com'], ',', '"', $headerLine);

        $fields = $dataLine->getFields();

        // Prüfe dass DataFields ihre HeaderFields kennen
        $this->assertCount(3, $fields);

        $nameField = $fields[0];
        $ageField = $fields[1];
        $emailField = $fields[2];

        // DataFields sollten HeaderField-Referenzen haben
        $this->assertTrue($nameField->hasHeaderField());
        $this->assertTrue($ageField->hasHeaderField());
        $this->assertTrue($emailField->hasHeaderField());

        // DataFields sollten ihre Spaltennamen kennen
        $this->assertEquals('Name', $nameField->getColumnName());
        $this->assertEquals('Age', $ageField->getColumnName());
        $this->assertEquals('Email', $emailField->getColumnName());

        // HeaderField-Referenzen sollten korrekt sein
        $this->assertSame($headerLine->getField(0), $nameField->getHeaderField());
        $this->assertSame($headerLine->getField(1), $ageField->getHeaderField());
        $this->assertSame($headerLine->getField(2), $emailField->getHeaderField());
    }

    public function testDataFieldsWithoutHeaderLineHaveNoHeaderFields(): void {
        // DataLine ohne HeaderLine
        $dataLine = new DataLine(['John', '30', 'john@example.com']);

        $fields = $dataLine->getFields();

        // DataFields sollten keine HeaderField-Referenzen haben
        $this->assertFalse($fields[0]->hasHeaderField());
        $this->assertFalse($fields[1]->hasHeaderField());
        $this->assertFalse($fields[2]->hasHeaderField());

        // Spaltennamen sollten null sein
        $this->assertNull($fields[0]->getColumnName());
        $this->assertNull($fields[1]->getColumnName());
        $this->assertNull($fields[2]->getColumnName());
    }

    /**
     * Test für die vereinfachte Header-Referenz: Nur HeaderField-Verweis notwendig
     */
    public function testDataFieldsWithSimpleHeaderReference(): void {
        $headerLine = new HeaderLine(['name', 'age', 'email']);
        $dataLine = new DataLine(
            ['John Doe', '30', 'john@example.com'], // fields
            ',', // delimiter  
            '"', // enclosure
            $headerLine // headerLine
        );

        $fields = $dataLine->getFields();
        $expectedHeaderNames = ['name', 'age', 'email'];

        foreach ($fields as $index => $field) {
            $this->assertInstanceOf(DataField::class, $field);

            // HeaderField-Referenz prüfen
            $this->assertTrue($field->hasHeaderField());
            $this->assertInstanceOf(HeaderField::class, $field->getHeaderField());

            // Spaltenname wird über HeaderField-Referenz abgeleitet
            $this->assertEquals($expectedHeaderNames[$index], $field->getColumnName());

            // Direkter Zugriff über HeaderField sollte das gleiche Ergebnis liefern
            $this->assertEquals($field->getHeaderField()->getValue(), $field->getColumnName());
        }
    }
}
