<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XLSXDocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\XLSX;

use CommonToolkit\Builders\XLSXDocumentBuilder;
use CommonToolkit\Entities\XLSX\Cell;
use CommonToolkit\Entities\XLSX\Document;
use CommonToolkit\Entities\XLSX\Row;
use CommonToolkit\Entities\XLSX\Sheet;
use CommonToolkit\Generators\XLSX\XLSXGenerator;
use CommonToolkit\Parsers\XLSXDocumentParser;
use Tests\Contracts\BaseTestCase;

class XLSXDocumentTest extends BaseTestCase {
    private string $tempDir;

    protected function setUp(): void {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/xlsx_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void {
        parent::tearDown();
        // Temp-Dateien aufräumen
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testCellBasics(): void {
        $cell = new Cell('Test', 's');
        $this->assertEquals('Test', $cell->getValue());
        $this->assertEquals('Test', $cell->getStringValue());
        $this->assertEquals('s', $cell->getType());
        $this->assertTrue($cell->isString());
        $this->assertFalse($cell->isEmpty());

        $numCell = new Cell(42, 'n');
        $this->assertEquals(42, $numCell->getValue());
        $this->assertTrue($numCell->isNumeric());

        $emptyCell = new Cell(null);
        $this->assertTrue($emptyCell->isEmpty());
    }

    public function testRowFromArray(): void {
        $row = Row::fromArray(['A', 'B', 'C'], 1);

        $this->assertCount(3, $row);
        $this->assertEquals(['A', 'B', 'C'], $row->toArray());
        $this->assertEquals(['A', 'B', 'C'], $row->toStringArray());
        $this->assertFalse($row->isEmpty());
    }

    public function testSheetBasics(): void {
        $header = Row::fromArray(['Name', 'Age', 'City'], 1);
        $row1 = Row::fromArray(['Alice', 30, 'Berlin'], 2);
        $row2 = Row::fromArray(['Bob', 25, 'München'], 3);

        $sheet = new Sheet('Daten', $header, [$row1, $row2], 0);

        $this->assertEquals('Daten', $sheet->getName());
        $this->assertTrue($sheet->hasHeader());
        $this->assertEquals(['Name', 'Age', 'City'], $sheet->getHeaderNames());
        $this->assertCount(2, $sheet);
        $this->assertEquals(3, $sheet->countTotal());
        $this->assertTrue($sheet->isConsistent());
    }

    public function testSheetColumnAccess(): void {
        $header = Row::fromArray(['Name', 'Email'], 1);
        $row1 = Row::fromArray(['Alice', 'alice@example.com'], 2);
        $row2 = Row::fromArray(['Bob', 'bob@example.com'], 3);

        $sheet = new Sheet('Users', $header, [$row1, $row2], 0);

        $this->assertTrue($sheet->hasColumn('Name'));
        $this->assertTrue($sheet->hasColumn('Email'));
        $this->assertFalse($sheet->hasColumn('Phone'));

        $this->assertEquals(0, $sheet->getColumnIndex('Name'));
        $this->assertEquals(1, $sheet->getColumnIndex('Email'));

        $names = $sheet->getColumnByName('Name');
        $this->assertEquals(['Alice', 'Bob'], $names);
    }

    public function testDocumentBasics(): void {
        $sheet1 = new Sheet('Sheet1', null, [], 0);
        $sheet2 = new Sheet('Sheet2', null, [], 1);

        $doc = new Document([$sheet1, $sheet2], 'Tester', 'Test-Dokument');

        $this->assertCount(2, $doc);
        $this->assertEquals(['Sheet1', 'Sheet2'], $doc->getSheetNames());
        $this->assertTrue($doc->hasSheet('Sheet1'));
        $this->assertFalse($doc->hasSheet('Sheet3'));
        $this->assertEquals('Tester', $doc->getCreator());
        $this->assertEquals('Test-Dokument', $doc->getTitle());
    }

    public function testBuilderFluentApi(): void {
        $builder = new XLSXDocumentBuilder();

        $doc = $builder
            ->sheet('Mitarbeiter')
            ->setHeader(['Name', 'Abteilung', 'Gehalt'])
            ->addRow(['Max Mustermann', 'IT', 50000])
            ->addRow(['Anna Schmidt', 'HR', 45000])
            ->setCreator('Test')
            ->setTitle('Mitarbeiterliste')
            ->build();

        $this->assertCount(1, $doc);
        $sheet = $doc->getFirstSheet();
        $this->assertNotNull($sheet);
        $this->assertEquals('Mitarbeiter', $sheet->getName());
        $this->assertTrue($sheet->hasHeader());
        $this->assertCount(2, $sheet);
    }

    public function testBuilderMultipleSheets(): void {
        $builder = new XLSXDocumentBuilder();

        $doc = $builder
            ->sheet('Kunden')
            ->setHeader(['ID', 'Name'])
            ->addRow([1, 'Kunde A'])
            ->addRow([2, 'Kunde B'])
            ->sheet('Produkte')
            ->setHeader(['SKU', 'Bezeichnung', 'Preis'])
            ->addRow(['P001', 'Produkt 1', 29.99])
            ->addRow(['P002', 'Produkt 2', 49.99])
            ->build();

        $this->assertCount(2, $doc);
        $this->assertEquals(['Kunden', 'Produkte'], $doc->getSheetNames());

        $kunden = $doc->getSheetByName('Kunden');
        $this->assertNotNull($kunden);
        $this->assertCount(2, $kunden);

        $produkte = $doc->getSheetByName('Produkte');
        $this->assertNotNull($produkte);
        $this->assertCount(2, $produkte);
    }

    public function testGeneratorAndParser(): void {
        // Dokument erstellen
        $builder = new XLSXDocumentBuilder();
        $doc = $builder
            ->sheet('Test')
            ->setHeader(['Spalte1', 'Spalte2', 'Spalte3'])
            ->addRow(['Wert1', 123, 45.67])
            ->addRow(['Wert2', 456, 78.90])
            ->setCreator('PHPUnit')
            ->setTitle('Test-Export')
            ->build();

        // In Datei schreiben
        $outputPath = $this->tempDir . '/test_output.xlsx';
        $result = XLSXGenerator::toFile($doc, $outputPath);
        $this->assertTrue($result);
        $this->assertFileExists($outputPath);

        // Wieder einlesen
        $parsed = XLSXDocumentParser::fromFile($outputPath, true);

        $this->assertCount(1, $parsed);
        $sheet = $parsed->getFirstSheet();
        $this->assertNotNull($sheet);
        $this->assertEquals('Test', $sheet->getName());
        $this->assertTrue($sheet->hasHeader());
        $this->assertEquals(['Spalte1', 'Spalte2', 'Spalte3'], $sheet->getHeaderNames());
        $this->assertCount(2, $sheet);

        // Werte prüfen
        $row1 = $sheet->getRow(0);
        $this->assertNotNull($row1);
        $this->assertEquals('Wert1', $row1->getCell(0)?->getValue());
    }

    public function testRoundTripWithSpecialCharacters(): void {
        $builder = new XLSXDocumentBuilder();
        $doc = $builder
            ->sheet('Sonderzeichen')
            ->setHeader(['Name', 'Beschreibung'])
            ->addRow(['Müller', 'Größe & Gewicht'])
            ->addRow(['O\'Brien', 'Test "quoted"'])
            ->addRow(['<Script>', 'HTML & XML'])
            ->build();

        $outputPath = $this->tempDir . '/special_chars.xlsx';
        XLSXGenerator::toFile($doc, $outputPath);

        $parsed = XLSXDocumentParser::fromFile($outputPath);
        $sheet = $parsed->getFirstSheet();

        $names = $sheet->getColumnByName('Name');
        $this->assertEquals(['Müller', 'O\'Brien', '<Script>'], $names);

        $descriptions = $sheet->getColumnByName('Beschreibung');
        $this->assertEquals(['Größe & Gewicht', 'Test "quoted"', 'HTML & XML'], $descriptions);
    }

    public function testEmptySheet(): void {
        $sheet = new Sheet('Leer');

        $this->assertEquals('Leer', $sheet->getName());
        $this->assertFalse($sheet->hasHeader());
        $this->assertCount(0, $sheet);
        $this->assertTrue($sheet->isConsistent());
    }

    public function testSheetToArray(): void {
        $header = Row::fromArray(['A', 'B'], 1);
        $row = Row::fromArray([1, 2], 2);

        $sheet = new Sheet('Data', $header, [$row], 0);

        $withHeader = $sheet->toArray(true);
        $this->assertEquals([['A', 'B'], [1, 2]], $withHeader);

        $withoutHeader = $sheet->toArray(false);
        $this->assertEquals([[1, 2]], $withoutHeader);
    }

    public function testDocumentIteration(): void {
        $sheet1 = new Sheet('S1', null, [Row::fromArray([1])], 0);
        $sheet2 = new Sheet('S2', null, [Row::fromArray([2])], 1);

        $doc = new Document([$sheet1, $sheet2]);

        $names = [];
        foreach ($doc as $sheet) {
            $names[] = $sheet->getName();
        }

        $this->assertEquals(['S1', 'S2'], $names);
    }

    public function testSheetIteration(): void {
        $rows = [
            Row::fromArray(['A', 1], 1),
            Row::fromArray(['B', 2], 2),
        ];

        $sheet = new Sheet('Test', null, $rows, 0);

        $values = [];
        foreach ($sheet as $row) {
            $values[] = $row->getCell(0)?->getValue();
        }

        $this->assertEquals(['A', 'B'], $values);
    }
}
