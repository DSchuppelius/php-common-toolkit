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
    // getFirstRow / getLastRow Tests
    // =========================================================================

    public function testGetFirstRow(): void {
        $doc = $this->createTestDocument();

        $firstRow = $doc->getFirstRow();
        $this->assertNotNull($firstRow);
        $this->assertEquals('Alice', $firstRow->getField(0)?->getValue());
    }

    public function testGetLastRow(): void {
        $doc = $this->createTestDocument();

        $lastRow = $doc->getLastRow();
        $this->assertNotNull($lastRow);
        $this->assertEquals('Charlie', $lastRow->getField(0)?->getValue());
    }

    public function testGetFirstRowEmptyDocument(): void {
        $builder = new CSVDocumentBuilder();
        $header = HeaderLine::fromString('"Name","Email"', ',', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        $this->assertNull($doc->getFirstRow());
    }

    public function testGetLastRowEmptyDocument(): void {
        $builder = new CSVDocumentBuilder();
        $header = HeaderLine::fromString('"Name","Email"', ',', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        $this->assertNull($doc->getLastRow());
    }

    public function testGetFirstAndLastRowSingleRow(): void {
        $builder = new CSVDocumentBuilder();
        $builder->addRow(DataLine::fromString('"Only","Row"', ',', '"'));
        $doc = $builder->build();

        $first = $doc->getFirstRow();
        $last = $doc->getLastRow();

        $this->assertNotNull($first);
        $this->assertNotNull($last);
        $this->assertTrue($first->equals($last));
    }

    // =========================================================================
    // diffColumnsByName / diffColumnsByIndex Tests
    // =========================================================================

    private function createSollHabenDocument(): Document {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Buchung;Soll;Haben', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Gehalt;0,00;3000,00', ';', '"'));
        $builder->addRow(DataLine::fromString('Miete;800,00;0,00', ';', '"'));
        $builder->addRow(DataLine::fromString('Strom;120,50;0,00', ';', '"'));
        $builder->addRow(DataLine::fromString('Gutschrift;0,00;50,00', ';', '"'));
        return $builder->build();
    }

    public function testDiffColumnsByName(): void {
        $doc = $this->createSollHabenDocument();

        // Soll - Haben: 920,50 - 3050,00 = -2129,50
        $diff = $doc->diffColumnsByName('Soll', 'Haben', 2, CountryCode::Germany);
        $this->assertEquals('-2129.50', $diff);
    }

    public function testDiffColumnsByNameReversed(): void {
        $doc = $this->createSollHabenDocument();

        // Haben - Soll: 3050,00 - 920,50 = 2129,50
        $diff = $doc->diffColumnsByName('Haben', 'Soll', 2, CountryCode::Germany);
        $this->assertEquals('2129.50', $diff);
    }

    public function testDiffColumnsByIndex(): void {
        $doc = $this->createSollHabenDocument();

        // Index 1 (Soll) - Index 2 (Haben)
        $diff = $doc->diffColumnsByIndex(1, 2, 2, CountryCode::Germany);
        $this->assertEquals('-2129.50', $diff);
    }

    public function testDiffColumnsByNameThrowsOnNonExistentColumn(): void {
        $doc = $this->createSollHabenDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Spalte 'Gewinn' nicht im Header gefunden");
        $doc->diffColumnsByName('Gewinn', 'Haben');
    }

    public function testDiffColumnsByNameEqualColumns(): void {
        $doc = $this->createSollHabenDocument();

        // Soll - Soll = 0
        $diff = $doc->diffColumnsByName('Soll', 'Soll', 2, CountryCode::Germany);
        $this->assertEquals('0.00', $diff);
    }

    public function testDiffColumnsByNameEmptyDocument(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Soll;Haben', ';', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        $diff = $doc->diffColumnsByName('Soll', 'Haben', 2);
        $this->assertEquals('0.00', $diff);
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

    // =========================================================================
    // filterRows Tests
    // =========================================================================

    public function testFilterRowsByValue(): void {
        $doc = $this->createTestDocument();

        // Nur Zeilen mit "Bob"
        $filtered = $doc->filterRows(fn(DataLine $row) => $row->getField(0)?->getValue() === 'Bob');

        $this->assertEquals(1, $filtered->countRows());
        $this->assertEquals('Bob', $filtered->getFirstRow()?->getField(0)?->getValue());
        // Header bleibt erhalten
        $this->assertTrue($filtered->hasHeader());
    }

    public function testFilterRowsByNumericCondition(): void {
        $doc = $this->createNumericDocument();

        // Nur Zeilen mit Preis > 10 (getTypedValue() liefert den korrekten float)
        $filtered = $doc->filterRows(fn(DataLine $row) => ($row->getField(2)?->getTypedValue() ?? 0) > 10);

        $this->assertEquals(2, $filtered->countRows());
    }

    public function testFilterRowsReturnsEmptyDocument(): void {
        $doc = $this->createTestDocument();

        $filtered = $doc->filterRows(fn(DataLine $row) => false);

        $this->assertEquals(0, $filtered->countRows());
        $this->assertTrue($filtered->hasHeader());
    }

    public function testFilterRowsReturnsAllRows(): void {
        $doc = $this->createTestDocument();

        $filtered = $doc->filterRows(fn(DataLine $row) => true);

        $this->assertEquals(3, $filtered->countRows());
    }

    public function testFilterRowsWithIndex(): void {
        $doc = $this->createTestDocument();

        // Nur gerade Indizes (0, 2)
        $filtered = $doc->filterRows(fn(DataLine $row, int $index) => $index % 2 === 0);

        $this->assertEquals(2, $filtered->countRows());
        $this->assertEquals('Alice', $filtered->getRow(0)?->getField(0)?->getValue());
        $this->assertEquals('Charlie', $filtered->getRow(1)?->getField(0)?->getValue());
    }

    // =========================================================================
    // sliceRows Tests
    // =========================================================================

    public function testSliceRowsFromStart(): void {
        $doc = $this->createTestDocument();

        $sliced = $doc->sliceRows(0, 2);

        $this->assertEquals(2, $sliced->countRows());
        $this->assertEquals('Alice', $sliced->getFirstRow()?->getField(0)?->getValue());
        $this->assertEquals('Bob', $sliced->getLastRow()?->getField(0)?->getValue());
    }

    public function testSliceRowsFromMiddle(): void {
        $doc = $this->createTestDocument();

        $sliced = $doc->sliceRows(1, 1);

        $this->assertEquals(1, $sliced->countRows());
        $this->assertEquals('Bob', $sliced->getFirstRow()?->getField(0)?->getValue());
    }

    public function testSliceRowsFromEnd(): void {
        $doc = $this->createTestDocument();

        // Letzte 2 Zeilen
        $sliced = $doc->sliceRows(-2);

        $this->assertEquals(2, $sliced->countRows());
        $this->assertEquals('Bob', $sliced->getFirstRow()?->getField(0)?->getValue());
        $this->assertEquals('Charlie', $sliced->getLastRow()?->getField(0)?->getValue());
    }

    public function testSliceRowsToEnd(): void {
        $doc = $this->createTestDocument();

        // Ab Zeile 1 bis Ende
        $sliced = $doc->sliceRows(1);

        $this->assertEquals(2, $sliced->countRows());
    }

    public function testSliceRowsPreservesHeader(): void {
        $doc = $this->createTestDocument();

        $sliced = $doc->sliceRows(0, 1);

        $this->assertTrue($sliced->hasHeader());
        $this->assertEquals(['Name', 'Email', 'Age'], $sliced->getColumnNames());
    }

    // =========================================================================
    // sortByColumn Tests
    // =========================================================================

    public function testSortByColumnAlphabeticalAsc(): void {
        $doc = $this->createTestDocument();

        $sorted = $doc->sortByColumn('Name', ascending: true);

        $this->assertEquals('Alice', $sorted->getRow(0)?->getField(0)?->getValue());
        $this->assertEquals('Bob', $sorted->getRow(1)?->getField(0)?->getValue());
        $this->assertEquals('Charlie', $sorted->getRow(2)?->getField(0)?->getValue());
    }

    public function testSortByColumnAlphabeticalDesc(): void {
        $doc = $this->createTestDocument();

        $sorted = $doc->sortByColumn('Name', ascending: false);

        $this->assertEquals('Charlie', $sorted->getRow(0)?->getField(0)?->getValue());
        $this->assertEquals('Bob', $sorted->getRow(1)?->getField(0)?->getValue());
        $this->assertEquals('Alice', $sorted->getRow(2)?->getField(0)?->getValue());
    }

    public function testSortByColumnNumeric(): void {
        $doc = $this->createNumericDocument();

        $sorted = $doc->sortByColumn('Preis', ascending: true, numeric: true);

        // getValue() liefert deutsches Zahlenformat (DataField default: Germany)
        $this->assertEquals('Gizmo', $sorted->getRow(0)?->getField(0)?->getValue());
        $this->assertEquals('Widget', $sorted->getRow(1)?->getField(0)?->getValue());
        $this->assertEquals('Gadget', $sorted->getRow(2)?->getField(0)?->getValue());
    }

    public function testSortByColumnNumericDesc(): void {
        $doc = $this->createNumericDocument();

        $sorted = $doc->sortByColumn('Preis', ascending: false, numeric: true);

        $this->assertEquals('Gadget', $sorted->getRow(0)?->getField(0)?->getValue());
        $this->assertEquals('Gizmo', $sorted->getRow(2)?->getField(0)?->getValue());
    }

    public function testSortByColumnPreservesHeader(): void {
        $doc = $this->createTestDocument();

        $sorted = $doc->sortByColumn('Name');

        $this->assertTrue($sorted->hasHeader());
        $this->assertEquals(['Name', 'Email', 'Age'], $sorted->getColumnNames());
    }

    public function testSortByColumnThrowsOnNonExistent(): void {
        $doc = $this->createTestDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Spalte 'Foo' nicht im Header gefunden");
        $doc->sortByColumn('Foo');
    }

    // =========================================================================
    // uniqueColumn Tests
    // =========================================================================

    public function testUniqueColumnByName(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Kategorie;Betrag', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('Miete;800', ';', '"'));
        $builder->addRow(DataLine::fromString('Strom;120', ';', '"'));
        $builder->addRow(DataLine::fromString('Miete;800', ';', '"'));
        $builder->addRow(DataLine::fromString('Wasser;42', ';', '"'));
        $builder->addRow(DataLine::fromString('Strom;85', ';', '"'));
        $doc = $builder->build();

        $unique = $doc->uniqueColumnByName('Kategorie');

        $this->assertEquals(['Miete', 'Strom', 'Wasser'], $unique);
    }

    public function testUniqueColumnByIndex(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $builder->addRow(DataLine::fromString('A;1', ';', '"'));
        $builder->addRow(DataLine::fromString('B;2', ';', '"'));
        $builder->addRow(DataLine::fromString('A;3', ';', '"'));
        $doc = $builder->build();

        $unique = $doc->uniqueColumnByIndex(0);

        $this->assertEquals(['A', 'B'], $unique);
    }

    public function testUniqueColumnThrowsOnNonExistent(): void {
        $doc = $this->createTestDocument();

        $this->expectException(RuntimeException::class);
        $doc->uniqueColumnByName('Nope');
    }

    // =========================================================================
    // minColumn / maxColumn Tests
    // =========================================================================

    public function testMinColumnByName(): void {
        $doc = $this->createNumericDocument();

        $min = $doc->minColumnByName('Preis', 2);
        $this->assertEquals('5.00', $min);
    }

    public function testMaxColumnByName(): void {
        $doc = $this->createNumericDocument();

        $max = $doc->maxColumnByName('Preis', 2);
        $this->assertEquals('25.99', $max);
    }

    public function testMinColumnByIndex(): void {
        $doc = $this->createNumericDocument();

        $min = $doc->minColumnByIndex(1, 0);  // Menge
        $this->assertEquals('2', $min);
    }

    public function testMaxColumnByIndex(): void {
        $doc = $this->createNumericDocument();

        $max = $doc->maxColumnByIndex(1, 0);  // Menge
        $this->assertEquals('7', $max);
    }

    public function testMinColumnWithNegativeValues(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Posten;Betrag', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('A;"500,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('B;"-200,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('C;"100,00"', ';', '"'));
        $doc = $builder->build();

        $min = $doc->minColumnByName('Betrag', 2, CountryCode::Germany);
        $this->assertEquals('-200.00', $min);

        $max = $doc->maxColumnByName('Betrag', 2, CountryCode::Germany);
        $this->assertEquals('500.00', $max);
    }

    public function testMinMaxColumnEmptyDocument(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Name;Wert', ';', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        $this->assertNull($doc->minColumnByName('Wert'));
        $this->assertNull($doc->maxColumnByName('Wert'));
    }

    // =========================================================================
    // avgColumn Tests
    // =========================================================================

    public function testAvgColumnByName(): void {
        $doc = $this->createNumericDocument();

        // (10.50 + 25.99 + 5.00) / 3 = 13.83
        $avg = $doc->avgColumnByName('Preis', 2);
        $this->assertEquals('13.83', $avg);
    }

    public function testAvgColumnByIndex(): void {
        $doc = $this->createNumericDocument();

        // Menge: (3 + 7 + 2) / 3 = 4
        $avg = $doc->avgColumnByIndex(1, 0);
        $this->assertEquals('4', $avg);
    }

    public function testAvgColumnEmptyDocument(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Name;Wert', ';', '"');
        $builder->setHeader($header);
        $doc = $builder->build();

        $avg = $doc->avgColumnByName('Wert', 2);
        $this->assertEquals('0.00', $avg);
    }

    public function testAvgColumnWithGermanFormat(): void {
        $builder = new CSVDocumentBuilder(';', '"');
        $header = HeaderLine::fromString('Posten;Betrag', ';', '"');
        $builder->setHeader($header);
        $builder->addRow(DataLine::fromString('A;"1.000,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('B;"2.000,00"', ';', '"'));
        $builder->addRow(DataLine::fromString('C;"3.000,00"', ';', '"'));
        $doc = $builder->build();

        // (1000 + 2000 + 3000) / 3 = 2000.00
        $avg = $doc->avgColumnByName('Betrag', 2, CountryCode::Germany);
        $this->assertEquals('2000.00', $avg);
    }
}
