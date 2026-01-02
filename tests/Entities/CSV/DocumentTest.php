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
        $this->assertEquals(-1, $doc->getColumnIndex('NonExistent'));
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
        $this->assertEquals(-1, $doc->getColumnIndex('Name'));
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
}