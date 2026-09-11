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
use CommonToolkit\Entities\XLSX\{Cell, Document, Row, Sheet};
use CommonToolkit\Exceptions\Parsers\DocumentLimitExceededException;
use CommonToolkit\Generators\XLSX\XLSXGenerator;
use CommonToolkit\Parsers\XLSXDocumentParser;
use DateTimeImmutable;
use DateTimeInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use Tests\Contracts\BaseTestCase;
use ZipArchive;

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
            $files = glob($this->tempDir . '/*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function test_cell_basics(): void {
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

    public function test_row_from_array(): void {
        $row = Row::fromArray(['A', 'B', 'C'], 1);

        $this->assertCount(3, $row);
        $this->assertEquals(['A', 'B', 'C'], $row->toArray());
        $this->assertEquals(['A', 'B', 'C'], $row->toStringArray());
        $this->assertFalse($row->isEmpty());
    }

    public function test_sheet_basics(): void {
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

    public function test_sheet_column_access(): void {
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

    /**
     * Regression: getHeaderNames()/toStringArray() casteten mit (string) und stürzten
     * über einer Datumszelle (DateTimeImmutable ist nicht string-castbar).
     */
    public function test_header_names_with_date_cell_in_first_row(): void {
        $header = Row::fromArray(['Kunde', new DateTimeImmutable('2026-02-03'), new DateTimeImmutable('2026-02-03 07:30:15'), 45.67, true, null], 1);
        $sheet = new Sheet('Zeiten', $header, [Row::fromArray(['A', 1, 2, 3, 4, 5], 2)], 0);

        $this->assertSame(['Kunde', '2026-02-03', '2026-02-03 07:30:15', '45.67', '1', ''], $sheet->getHeaderNames());
        $this->assertSame(['Kunde', '2026-02-03', '2026-02-03 07:30:15', '45.67', '1', ''], $header->toStringArray());
        $this->assertSame(1, $sheet->getColumnIndex('2026-02-03'));
        $this->assertSame([1], $sheet->getColumnByName('2026-02-03'));

        // Floats bleiben beim bisherigen (string)-Cast, nur Datum wird gesondert behandelt
        $this->assertSame((string) (1 / 3), (new Cell(1 / 3, 'n'))->getStringValue());
        $this->assertSame('2026-02-03', (new Cell(new DateTimeImmutable('2026-02-03'), 'd'))->getStringValue());
    }

    public function test_parser_header_row_with_date_cell(): void {
        // Zeile 1 enthält ein Datum (z.B. Monatsspalten "Kunde | 01.01.2026 | 01.02.2026")
        $doc = (new XLSXDocumentBuilder)
            ->sheet('Monate')
            ->setHeaderRow(Row::fromArray(['Kunde', new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-02-01')], 1))
            ->addRow(['Alpha', 10, 20])
            ->build();

        $path = $this->tempDir . '/date_header.xlsx';
        XLSXGenerator::toFile($doc, $path);

        $sheet = XLSXDocumentParser::fromFile($path, true)->getFirstSheet();
        $this->assertNotNull($sheet);
        $this->assertInstanceOf(DateTimeImmutable::class, $sheet->getHeader()?->getCell(1)?->getValue());
        $this->assertSame(['Kunde', '2026-01-01', '2026-02-01'], $sheet->getHeaderNames());
        $this->assertSame([20], $sheet->getColumnByName('2026-02-01'));
    }

    public function test_sheet_column_index_normalized_and_by_aliases(): void {
        $header = Row::fromArray(["\u{FEFF}Kunde", ' Beginn ', "Ende\u{00A0}(Datum)", 'Dauer  in   Stunden'], 1);
        $sheet = new Sheet('Zeiten', $header, [Row::fromArray(['A', '08:00', '17:00', 9], 2)], 0);

        // Exakter Vergleich bleibt der Standard
        $this->assertNull($sheet->getColumnIndex('Beginn'));
        $this->assertSame(1, $sheet->getColumnIndex(' Beginn '));

        // Toleranter Vergleich: Trim, Groß-/Kleinschreibung, BOM, Whitespace-Kollaps, NBSP
        $this->assertSame(1, $sheet->getColumnIndex('Beginn', true));
        $this->assertSame(1, $sheet->getColumnIndex('BEGINN', true));
        $this->assertSame(0, $sheet->getColumnIndex('kunde', true));
        $this->assertSame(2, $sheet->getColumnIndex('Ende (Datum)', true));
        $this->assertSame(3, $sheet->getColumnIndex('dauer in stunden', true));
        $this->assertNull($sheet->getColumnIndex('Beginn Ende', true));

        // Alias-Liste: erster passender Alias gewinnt
        $this->assertSame(1, $sheet->getColumnIndexByAliases(['Start', 'Beginn', 'Von']));
        $this->assertSame(2, $sheet->getColumnIndexByAliases(['ende (datum)', 'Kunde']));
        $this->assertNull($sheet->getColumnIndexByAliases(['Start', 'Von']));
        $this->assertNull($sheet->getColumnIndexByAliases(['Beginn'], false));
        $this->assertSame(1, $sheet->getColumnIndexByAliases([' Beginn '], false));

        // Ohne Header: immer null
        $noHeader = new Sheet('Leer', null, [], 0);
        $this->assertNull($noHeader->getColumnIndex('Beginn', true));
        $this->assertNull($noHeader->getColumnIndexByAliases(['Beginn']));
    }

    public function test_document_basics(): void {
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

    public function test_builder_fluent_api(): void {
        $builder = new XLSXDocumentBuilder;

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

    public function test_builder_multiple_sheets(): void {
        $builder = new XLSXDocumentBuilder;

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

    public function test_generator_and_parser(): void {
        // Dokument erstellen
        $builder = new XLSXDocumentBuilder;
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

    private function writeLimitFixture(): string {
        $doc = (new XLSXDocumentBuilder)
            ->sheet('Daten')
            ->setHeader(['Kunde', 'Beginn', 'Ende'])
            ->addRow(['Alpha', '2026-01-01', '2026-01-31'])
            ->addRow(['Beta', '2026-02-01', '2026-02-28'])
            ->build();

        $path = $this->tempDir . '/limits.xlsx';
        $this->assertTrue(XLSXGenerator::toFile($doc, $path));

        return $path;
    }

    /** Summe der entpackten Größen aller Einträge laut Central Directory. */
    private function uncompressedSize(string $path): int {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $this->assertNotFalse($stat);
            $total += (int) $stat['size'];
        }
        $zip->close();

        return $total;
    }

    public function test_parser_respects_uncompressed_size_limit(): void {
        $path = $this->writeLimitFixture();
        $size = $this->uncompressedSize($path);
        $this->assertGreaterThan(0, $size);

        // (a) Unter der Grenze (Standard 256 MiB, explizit knapp darüber, und null = unbegrenzt) parst
        $this->assertCount(2, XLSXDocumentParser::fromFile($path)->getFirstSheet() ?? []);
        $this->assertCount(2, XLSXDocumentParser::fromFile($path, true, null, $size)->getFirstSheet() ?? []);
        $this->assertCount(2, XLSXDocumentParser::fromFile($path, true, null, null)->getFirstSheet() ?? []);

        // (b) Grenze unter der Fixture-Größe wirft – bevor irgendein Eintrag entpackt wird
        try {
            XLSXDocumentParser::fromFile($path, true, null, $size - 1);
            $this->fail('RuntimeException erwartet');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('XLSX überschreitet die erlaubte entpackte Größe: ', $e->getMessage());
            $this->assertStringContainsString(' > ' . ($size - 1) . ' Bytes', $e->getMessage());
            $this->assertStringContainsString('limits.xlsx', $e->getMessage());
            $this->assertStringNotContainsString($this->tempDir, $e->getMessage(), 'Kein Serverpfad in der Meldung');
        }
    }

    public function test_parser_respects_max_rows(): void {
        $path = $this->writeLimitFixture();

        // (c) maxRows = 1 bei 2 Datenzeilen wirft (kein stilles Kürzen), maxRows = 2 passt genau
        $this->assertCount(2, XLSXDocumentParser::fromFile($path, true, null, null, 2)->getFirstSheet() ?? []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("XLSX-Blatt 'Daten' überschreitet die erlaubte Zeilenzahl: mehr als 1 Datenzeilen");
        XLSXDocumentParser::fromFile($path, true, null, null, 1);
    }

    public function test_limit_exceptions_carry_kind_limit_and_document(): void {
        $path = $this->writeLimitFixture();

        try {
            XLSXDocumentParser::fromFile($path, true, null, null, 1);
            $this->fail('Zeilenlimit muss werfen');
        } catch (DocumentLimitExceededException $e) {
            $this->assertSame(DocumentLimitExceededException::KIND_ROWS, $e->getKind());
            $this->assertSame(1, $e->getLimit());
            $this->assertNull($e->getActual());
            $this->assertSame(basename($path), $e->getDocument());
        }

        try {
            XLSXDocumentParser::fromFile($path, true, null, 16);
            $this->fail('Byte-Limit muss werfen');
        } catch (DocumentLimitExceededException $e) {
            $this->assertSame(DocumentLimitExceededException::KIND_BYTES, $e->getKind());
            $this->assertSame(16, $e->getLimit());
            $this->assertGreaterThan(16, $e->getActual());
            $this->assertSame(basename($path), $e->getDocument());
            $this->assertStringNotContainsString(dirname($path), $e->getMessage(), 'kein Serverpfad in der Meldung');
        }
    }

    public function test_parser_max_rows_counts_header_row_without_header_mode(): void {
        $path = $this->writeLimitFixture();

        // Ohne Header-Modus zählt die Kopfzeile als Datenzeile: 3 Zeilen → Grenze 3 passt, 2 wirft
        $this->assertCount(3, XLSXDocumentParser::fromFile($path, false, null, null, 3)->getFirstSheet() ?? []);

        $this->expectException(RuntimeException::class);
        XLSXDocumentParser::fromFile($path, false, null, null, 2);
    }

    public function test_parser_rejects_non_positive_limits(): void {
        $path = $this->writeLimitFixture();

        try {
            XLSXDocumentParser::fromFile($path, true, null, 0);
            $this->fail('InvalidArgumentException erwartet');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('maxUncompressedBytes muss > 0 sein', $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxRows muss > 0 sein');
        XLSXDocumentParser::fromFile($path, true, null, null, -5);
    }

    /**
     * Referenzwerte des Excel-1900-Datumssystems (inkl. Lotus-1-2-3-Schaltjahr-Bug):
     *   Serial 1     = 01.01.1900
     *   Serial 59    = 28.02.1900
     *   Serial 60    = fiktiver 29.02.1900 (existiert real nicht, 1900 war kein Schaltjahr)
     *   Serial 61    = 01.03.1900
     *   Serial 46204 = 01.07.2026
     * Regression: dateTimeToExcel() korrigierte den Lotus-Bug doppelt (Epoche
     * 1899-12-30 UND +1 ab Serial 60) — alle Daten ab dem 01.03.1900 waren um
     * einen Tag verschoben (2026-07-01 → 46205 statt 46204).
     */
    public function test_date_time_to_excel_reference_values(): void {
        $generator = new class extends XLSXGenerator {
            public function serial(DateTimeInterface $date): float {
                return $this->dateTimeToExcel($date);
            }
        };

        $this->assertSame(1.0, $generator->serial(new DateTimeImmutable('1900-01-01')));
        $this->assertSame(59.0, $generator->serial(new DateTimeImmutable('1900-02-28')));
        $this->assertSame(61.0, $generator->serial(new DateTimeImmutable('1900-03-01')));
        $this->assertSame(46204.0, $generator->serial(new DateTimeImmutable('2026-07-01')));

        // Zeitanteil als Tagesbruchteil
        $this->assertSame(46204.5, $generator->serial(new DateTimeImmutable('2026-07-01 12:00:00')));
    }

    /**
     * Regression: generateStyles() schrieb nur einen einzigen cellXf, obwohl
     * generateCell() für Datumszellen Style-Index 1 referenziert — Datumszellen
     * verloren ihr Datumsformat (Excel zeigte die rohe Serial-Zahl).
     */
    public function test_generated_date_cell_references_date_style(): void {
        $doc = (new XLSXDocumentBuilder)
            ->sheet('Termine')
            ->setHeader(['Datum'])
            ->addRow([new DateTimeImmutable('2026-07-01')])
            ->build();

        $outputPath = $this->tempDir . '/date_style.xlsx';
        XLSXGenerator::toFile($doc, $outputPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($outputPath));

        // styles.xml: Index 1 der cellXfs muss der Datums-Style (Built-in numFmtId 14) sein
        $styles = new DOMDocument;
        $styles->loadXML((string) $zip->getFromName('xl/styles.xml'));
        $xpath = new DOMXPath($styles);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $cellXfs = $xpath->query('//s:cellXfs/s:xf');
        $this->assertNotFalse($cellXfs);
        $this->assertGreaterThanOrEqual(2, $cellXfs->length, 'cellXfs muss neben dem Standard- auch den Datums-Style enthalten');

        $dateXf = $cellXfs->item(1);
        $this->assertInstanceOf(DOMElement::class, $dateXf);
        $this->assertSame('14', $dateXf->getAttribute('numFmtId'));

        // sheet1.xml: Datumszelle referenziert Style-Index 1 und trägt die korrekte Serial
        $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('s="1"', $sheetXml);
        $this->assertStringContainsString('<v>46204</v>', $sheetXml);
    }

    public function test_round_trip_with_date_cells(): void {
        $doc = (new XLSXDocumentBuilder)
            ->sheet('Termine')
            ->setHeader(['Bezeichnung', 'Datum'])
            ->addRow(['Frist', new DateTimeImmutable('2026-07-01')])
            ->addRow(['Übergabe', new DateTimeImmutable('2026-07-01 15:30:00')])
            ->addRow(['Nach Lotus-Schalttag', new DateTimeImmutable('1900-03-01')])
            ->addRow(['Vor Lotus-Schalttag', new DateTimeImmutable('1900-01-01')])
            ->build();

        $outputPath = $this->tempDir . '/dates.xlsx';
        XLSXGenerator::toFile($doc, $outputPath);

        $parsed = XLSXDocumentParser::fromFile($outputPath);
        $sheet = $parsed->getFirstSheet();
        $this->assertNotNull($sheet);

        $dates = $sheet->getColumnByName('Datum');
        $this->assertCount(4, $dates);
        $this->assertContainsOnlyInstancesOf(DateTimeImmutable::class, $dates);

        $this->assertSame('2026-07-01 00:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 15:30:00', $dates[1]->format('Y-m-d H:i:s'));
        $this->assertSame('1900-03-01', $dates[2]->format('Y-m-d'));
        $this->assertSame('1900-01-01', $dates[3]->format('Y-m-d'));
    }

    public function test_round_trip_with_special_characters(): void {
        $builder = new XLSXDocumentBuilder;
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
        if ($sheet === null) {
            self::fail('XLSX sollte ein Sheet enthalten');
        }

        $names = $sheet->getColumnByName('Name');
        $this->assertEquals(['Müller', 'O\'Brien', '<Script>'], $names);

        $descriptions = $sheet->getColumnByName('Beschreibung');
        $this->assertEquals(['Größe & Gewicht', 'Test "quoted"', 'HTML & XML'], $descriptions);
    }

    public function test_empty_sheet(): void {
        $sheet = new Sheet('Leer');

        $this->assertEquals('Leer', $sheet->getName());
        $this->assertFalse($sheet->hasHeader());
        $this->assertCount(0, $sheet);
        $this->assertTrue($sheet->isConsistent());
    }

    public function test_sheet_to_array(): void {
        $header = Row::fromArray(['A', 'B'], 1);
        $row = Row::fromArray([1, 2], 2);

        $sheet = new Sheet('Data', $header, [$row], 0);

        $withHeader = $sheet->toArray(true);
        $this->assertEquals([['A', 'B'], [1, 2]], $withHeader);

        $withoutHeader = $sheet->toArray(false);
        $this->assertEquals([[1, 2]], $withoutHeader);
    }

    public function test_document_iteration(): void {
        $sheet1 = new Sheet('S1', null, [Row::fromArray([1])], 0);
        $sheet2 = new Sheet('S2', null, [Row::fromArray([2])], 1);

        $doc = new Document([$sheet1, $sheet2]);

        $names = [];
        foreach ($doc as $sheet) {
            $names[] = $sheet->getName();
        }

        $this->assertEquals(['S1', 'S2'], $names);
    }

    public function test_sheet_iteration(): void {
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
