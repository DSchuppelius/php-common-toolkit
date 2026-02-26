<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\CSV;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Entities\CSV\HeaderLine;
use CommonToolkit\Entities\CSV\Document;
use CommonToolkit\Enums\CountryCode;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class DocumentTest extends BaseTestCase {
    private function createTestDocument(): Document {
        $builder = new CSVDocumentBuilder();

        // Header erstellen
        $header = HeaderLine::fromString('"Name","Email","Age"', ',', '"');
        $builder->setHeader($header);

        // Datenzeilen hinzufügen
        $builder->addRow(DataLine::fromString('"Alice","alice@example.com","30"', ',', '"'));
        $builder->addRow(DataLine::fromString('"Bob","bob@example.com","25"', ',', '"'));
        $builder->addRow(DataLine::fromString('"Charlie","charlie@example.com","35"', ',', '"'));

        return $builder->build();
    }

    public function testDocumentConstruction(): void {
        $doc = $this->createTestDocument();

        $this->assertTrue($doc->hasHeader());
        $this->assertEquals(3, $doc->countRows());
        $this->assertInstanceOf(HeaderLine::class, $doc->getHeader());
        $this->assertInstanceOf(DataLine::class, $doc->getRow(0));
    }

    public function testGetColumnByName(): void {
        $doc = $this->createTestDocument();

        // Test existierende Spalten
        $names = $doc->getColumnByName('Name');
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);

        $emails = $doc->getColumnByName('Email');
        $this->assertEquals(['alice@example.com', 'bob@example.com', 'charlie@example.com'], $emails);

        $ages = $doc->getColumnByName('Age');
        $this->assertEquals(['30', '25', '35'], $ages);
    }

    public function testGetColumnByNameThrowsExceptionForNonExistentColumn(): void {
        $doc = $this->createTestDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Spalte 'NonExistent' nicht im Header gefunden");
        $doc->getColumnByName('NonExistent');
    }

    public function testGetColumnByIndex(): void {
        $doc = $this->createTestDocument();

        // Test Index 0 (Name)
        $column0 = $doc->getColumnByIndex(0);
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $column0);

        // Test Index 1 (Email)
        $column1 = $doc->getColumnByIndex(1);
        $this->assertEquals(['alice@example.com', 'bob@example.com', 'charlie@example.com'], $column1);

        // Test Index 2 (Age)
        $column2 = $doc->getColumnByIndex(2);
        $this->assertEquals(['30', '25', '35'], $column2);
    }

    public function testGetColumnByIndexThrowsExceptionForInvalidIndex(): void {
        $doc = $this->createTestDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Spalten-Index '-1' ist ungültig");
        $doc->getColumnByIndex(-1);
    }

    public function testGetColumnIndex(): void {
        $doc = $this->createTestDocument();

        // Test existierende Spalten
        $this->assertEquals(0, $doc->getColumnIndex('Name'));
        $this->assertEquals(1, $doc->getColumnIndex('Email'));
        $this->assertEquals(2, $doc->getColumnIndex('Age'));

        // Test nicht existierende Spalte
        $this->assertNull($doc->getColumnIndex('NonExistent'));
    }

    public function testHasColumn(): void {
        $doc = $this->createTestDocument();

        // Test existierende Spalten
        $this->assertTrue($doc->hasColumn('Name'));
        $this->assertTrue($doc->hasColumn('Email'));
        $this->assertTrue($doc->hasColumn('Age'));

        // Test nicht existierende Spalte
        $this->assertFalse($doc->hasColumn('NonExistent'));
        $this->assertFalse($doc->hasColumn(''));
    }

    public function testGetColumnNames(): void {
        $doc = $this->createTestDocument();

        $columnNames = $doc->getColumnNames();
        $this->assertEquals(['Name', 'Email', 'Age'], $columnNames);
    }

    public function testColumnFunctionsWithoutHeader(): void {
        // Dokument ohne Header erstellen
        $builder = new CSVDocumentBuilder();
        $builder->addRow(DataLine::fromString('"Alice","alice@example.com"', ',', '"'));
        $doc = $builder->build();

        // Sollte leeres Array zurückgeben
        $this->assertEquals([], $doc->getColumnNames());
        $this->assertFalse($doc->hasColumn('Name'));
        $this->assertNull($doc->getColumnIndex('Name'));
    }

    public function testColumnFunctionsWithQuotedHeaders(): void {
        $builder = new CSVDocumentBuilder();

        $header = HeaderLine::fromString('"Full Name","E-Mail Address","Years Old"', ',', '"');
        $builder->setHeader($header);

        $builder->addRow(DataLine::fromString('"Alice Smith","alice@example.com","25"', ',', '"'));
        $builder->addRow(DataLine::fromString('"Bob Jones","bob@example.com","30"', ',', '"'));

        $doc = $builder->build();

        // Test mit Anführungszeichen in Header-Namen
        $names = $doc->getColumnByName('Full Name');
        $this->assertEquals(['Alice Smith', 'Bob Jones'], $names);

        $this->assertTrue($doc->hasColumn('E-Mail Address'));
        $this->assertEquals(2, $doc->getColumnIndex('Years Old'));
    }

    public function testColumnFunctionsWithEmptyFields(): void {
        $builder = new CSVDocumentBuilder();

        $header = HeaderLine::fromString('"Name","Email","Phone"', ',', '"');
        $builder->setHeader($header);

        $builder->addRow(DataLine::fromString('"Alice","alice@example.com",""', ',', '"'));
        $builder->addRow(DataLine::fromString('"Bob","","555-1234"', ',', '"'));
        $builder->addRow(DataLine::fromString('"","carol@example.com","555-5678"', ',', '"'));

        $doc = $builder->build();

        // Test mit leeren Feldern
        $names = $doc->getColumnByName('Name');
        $this->assertEquals(['Alice', 'Bob', ''], $names);

        $emails = $doc->getColumnByName('Email');
        $this->assertEquals(['alice@example.com', '', 'carol@example.com'], $emails);

        $phones = $doc->getColumnByName('Phone');
        $this->assertEquals(['', '555-1234', '555-5678'], $phones);
    }

    // =========================================================================
    // sumColumnByIndex / sumColumnByName Tests
    // =========================================================================

    private function createNumericDocument(): Document {
        $builder = new CSVDocumentBuilder();
        $header = HeaderLine::fromString('Artikel;Menge;Preis', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Widget;3;10.50', ';', '"'));
        $builder->addRow(DataLine::fromString('Gadget;7;25.99', ';', '"'));
        $builder->addRow(DataLine::fromString('Gizmo;2;5.00', ';', '"'));
        return $builder->build();
    }

    public function testSumColumnByNameSimple(): void {
        $doc = $this->createNumericDocument();

        $sum = $doc->sumColumnByName('Preis', 2);
        $this->assertEquals('41.49', $sum);
    }

    public function testSumColumnByNameInteger(): void {
        $doc = $this->createNumericDocument();

        $sum = $doc->sumColumnByName('Menge', 0);
        $this->assertEquals('12', $sum);
    }

    public function testSumColumnByIndexSimple(): void {
        $doc = $this->createNumericDocument();

        // Index 2 = Preis
        $sum = $doc->sumColumnByIndex(2, 2);
        $this->assertEquals('41.49', $sum);
    }

    public function testSumColumnByNameGermanFormat(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Bezeichnung;Betrag', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Miete;"1.200,50"', ';', '"'));
        $builder->addRow(DataLine::fromString('Strom;"85,30"', ';', '"'));
        $builder->addRow(DataLine::fromString('Wasser;"42,20"', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByName('Betrag', 2, CountryCode::Germany);
        $this->assertEquals('1328.00', $sum);
    }

    public function testSumColumnByNameMixedFormats(): void {
        // Simuliert ApoBank-Szenario: gemischte Formate in einer Spalte
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Buchung;Saldo', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Gehalt;"6.068,16"', ';', '"'));
        $builder->addRow(DataLine::fromString('Abbuchung;\'-902.36', ';', '"'));
        $builder->addRow(DataLine::fromString('Zinsen;"2.000,00"', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByName('Saldo', 2, CountryCode::Germany);
        $this->assertEquals('7165.80', $sum);
    }

    public function testSumColumnByNameWithNegativeValues(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Posten;Betrag', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Einnahme;"500,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('Ausgabe;"-200,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('Ausgabe;"-150,50"', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByName('Betrag', 2, CountryCode::Germany);
        $this->assertEquals('149.50', $sum);
    }

    public function testSumColumnByNameSkipsNonNumeric(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Name;Wert', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('A;100', ';', '"'));
        $builder->addRow(DataLine::fromString('B;N/A', ';', '"'));
        $builder->addRow(DataLine::fromString('C;200', ';', '"'));
        $doc = $builder->build();

        // skipNonNumeric=true (Standard) → N/A wird übersprungen
        $sum = $doc->sumColumnByName('Wert', 2, null, true);
        $this->assertEquals('300.00', $sum);
    }

    public function testSumColumnByNameSkipsEmptyValues(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Name;Wert', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('A;100', ';', '"'));
        $builder->addRow(DataLine::fromString('B;', ';', '"'));
        $builder->addRow(DataLine::fromString('C;300', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByName('Wert', 2, null, true);
        $this->assertEquals('400.00', $sum);
    }

    public function testSumColumnByNameThrowsOnNonExistentColumn(): void {
        $doc = $this->createNumericDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Spalte 'Gewinn' nicht im Header gefunden");
        $doc->sumColumnByName('Gewinn');
    }

    public function testSumColumnByNameHighPrecision(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('ID;Wert', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('1;0,001', ';', '"'));
        $builder->addRow(DataLine::fromString('2;0,002', ';', '"'));
        $builder->addRow(DataLine::fromString('3;0,003', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByName('Wert', 4, CountryCode::Germany);
        $this->assertEquals('0.0060', $sum);
    }

    public function testSumColumnByIndexWithoutHeader(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $builder->addRow(DataLine::fromString('100;200;300', ';', '"'));
        $builder->addRow(DataLine::fromString('10;20;30', ';', '"'));
        $doc = $builder->build();

        $sum = $doc->sumColumnByIndex(0, 0);
        $this->assertEquals('110', $sum);

        $sum = $doc->sumColumnByIndex(2, 0);
        $this->assertEquals('330', $sum);
    }

    public function testSumColumnEmptyDocument(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Name;Wert', ';', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        // Leeres Dokument → Summe 0
        $sum = $doc->sumColumnByName('Wert', 2);
        $this->assertEquals('0.00', $sum);
    }
}
