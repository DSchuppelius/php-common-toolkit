<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocumentParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\CSV\{DataLine, HeaderLine};
use CommonToolkit\Parsers\CSVDocumentParser;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class CSVDocumentParserTest extends BaseTestCase {
    private string $testFileComma;
    private string $testFileSemicolon;
    private string $testFileTab;
    private string $testFileEmpty;
    private string $testFileMalformed;
    private string $testFileMultiLine;
    private string $testFileQuoted;
    private string $testFileDoubleQuoted;
    private string $testFileInconsistentQuoted;
    private string $testFileAnsi;
    private string $testFileIso;

    protected function setUp(): void {
        $base = dirname(__DIR__, 2) . '/.samples/';
        $this->testFileComma = $base . 'comma.csv';
        $this->testFileSemicolon = $base . 'semicolon.csv';
        $this->testFileTab = $base . 'tab.csv';
        $this->testFileEmpty = $base . 'empty.csv';
        $this->testFileMalformed = $base . 'malformed.csv';
        $this->testFileMultiLine = $base . 'multiline.csv';
        $this->testFileQuoted = $base . 'quoted.csv';
        $this->testFileDoubleQuoted = $base . 'doublequoted.csv';
        $this->testFileInconsistentQuoted = $base . 'quoted-inkonsistent.csv';
        $this->testFileAnsi = $base . 'ansi.csv';
        $this->testFileIso = $base . 'iso.csv';
    }

    public function test_parse_comma_separated_csv(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertInstanceOf(HeaderLine::class, $doc->getHeader());
        $this->assertGreaterThan(0, $doc->countRows(), 'Mindestens eine Datenzeile erwartet');
        $this->assertInstanceOf(DataLine::class, $doc->getRow(0));

        $rebuilt = $doc->toString();
        $this->assertNotEmpty($rebuilt);
        $this->assertStringContainsString(',', $rebuilt);
    }

    /**
     * Mehrzeiliger Export, der je Zeile einfach-gequotete Felder mit einem Feld
     * mischt, das RFC4180-escapte Quotes (eingebettetes JSON) enthält – z. B.
     * PayPal-Aktivitäten-CSV. Muss vollständig (Header + alle Zeilen) parsen.
     */
    public function test_parse_mixed_single_and_escaped_quoted_csv(): void {
        $csv = "\"Datum\",\"Betrag\",\"Meta\"\n"
            . "\"09.01.2023\",\"60,00\",\"{\"\"order_id\"\":5227,\"\"order_key\"\":\"\"wc_a\"\"}\"\n"
            . "\"10.01.2023\",\"-2,18\",\"{\"\"order_id\"\":5228,\"\"note\"\":\"\"x,y\"\"}\"\n";

        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertSame(2, $doc->countRows());
        $row0 = $doc->getRow(0);
        $this->assertNotNull($row0);
        $this->assertSame(3, $row0->countFields());
        $this->assertSame('60,00', $row0->getField(1)?->getValue());
        $this->assertSame('{""order_id"":5227,""order_key"":""wc_a""}', $row0->getField(2)?->getValue());
        $this->assertSame('-2,18', $doc->getRow(1)?->getField(1)?->getValue());
    }

    public function test_parse_semicolon_separated_csv(): void {
        $csv = file_get_contents($this->testFileSemicolon);
        $doc = CSVDocumentParser::fromString($csv, ';', '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function test_parse_tab_separated_csv(): void {
        $csv = file_get_contents($this->testFileTab);
        $doc = CSVDocumentParser::fromString($csv, "\t", '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function test_parse_quoted_csv(): void {
        $csv = file_get_contents($this->testFileQuoted);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $this->assertStringContainsString('"', $row->toString());
    }

    public function test_parse_double_quoted_csv(): void {
        $csv = file_get_contents($this->testFileDoubleQuoted);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        [$strict, $nonStrict] = $doc->getHeader()->getEnclosureRepeatRange();
        $this->assertGreaterThanOrEqual(2, $nonStrict);
    }

    public function test_detect_multi_line_csv(): void {
        $csv = file_get_contents($this->testFileMultiLine);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertGreaterThan(1, $doc->countRows());
        $multiLineValue = $doc->getRow(0)?->getField(2)?->getValue() ?? '';
        $this->assertStringContainsString("\n", $multiLineValue, 'Mehrzeiliger Inhalt erwartet');
    }

    public function test_empty_csv_should_throw(): void {
        $csv = file_get_contents($this->testFileEmpty);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function test_malformed_csv_should_throw(): void {
        $csv = file_get_contents($this->testFileMalformed);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function test_inconsistent_quoted_csv_should_throw(): void {
        $csv = file_get_contents($this->testFileInconsistentQuoted);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function test_round_trip_integrity(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');
        $rebuilt = $doc->toString(',', '"');

        $doc2 = CSVDocumentParser::fromString($rebuilt, ',', '"');
        $this->assertTrue($doc->equals($doc2), 'Roundtrip sollte identisch bleiben');
    }

    public function test_parse_without_header(): void {
        $doc = CSVDocumentParser::fromString('"Alice","alice@example.com"', ',', '"', false);

        $this->assertFalse($doc->hasHeader());
        $this->assertEquals(1, $doc->countRows());

        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $this->assertEquals('Alice', $row->getField(0)->getValue());
        $this->assertEquals('alice@example.com', $row->getField(1)->getValue());
    }

    public function test_from_file_method(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function test_from_file_with_start_line(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        // Teste ab Zeile 2 (überspringt Header)
        $doc = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true, 2);

        $this->assertTrue($doc->hasHeader());
        // Sollte weniger Zeilen haben als normale Parsing
        $normalDoc = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true);
        $this->assertLessThan($normalDoc->countRows(), $doc->countRows());
    }

    public function test_from_file_range(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        // Teste Zeilen 1-3
        $doc = CSVDocumentParser::fromFileRange($this->testFileComma, 1, 3, ',', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertLessThanOrEqual(2, $doc->countRows()); // Header + max 2 Datenzeilen
    }

    public function test_from_file_range_invalid_range(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Startzeile (5) darf nicht größer als Endzeile (3) sein');

        CSVDocumentParser::fromFileRange($this->testFileComma, 5, 3);
    }

    public function test_from_file_with_ansi_encoding(): void {
        if (!file_exists($this->testFileAnsi)) {
            $this->markTestSkipped('ansi.csv Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileAnsi, ';', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertGreaterThan(0, $doc->countRows());

        // Header prüfen - Umlaute müssen korrekt konvertiert sein
        $header = $doc->getHeader();
        $this->assertNotNull($header);
        $headerString = $header->toString();
        $this->assertTrue(mb_check_encoding($headerString, 'UTF-8'), 'Header muss gültiges UTF-8 sein');
        $this->assertStringContainsString('Straße', $headerString, 'Straße muss im Header korrekt konvertiert sein');

        // Datenzeilen prüfen - Umlaute müssen korrekt konvertiert sein
        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $rowString = $row->toString();
        $this->assertTrue(mb_check_encoding($rowString, 'UTF-8'), 'Datenzeile muss gültiges UTF-8 sein');
        $this->assertStringContainsString('München', $rowString, 'München muss in Datenzeile korrekt konvertiert sein');
    }

    public function test_from_file_with_ansi_encoding_all_rows(): void {
        if (!file_exists($this->testFileAnsi)) {
            $this->markTestSkipped('ansi.csv Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileAnsi, ';', '"', true);

        // Alle Datenzeilen durchgehen und Umlaute prüfen
        $expectedUmlauts = ['München', 'Köln'];
        $foundUmlauts = [];

        for ($i = 0; $i < $doc->countRows(); $i++) {
            $row = $doc->getRow($i);
            if ($row === null) {
                continue;
            }

            $rowString = $row->toString();
            $this->assertTrue(mb_check_encoding($rowString, 'UTF-8'), "Zeile $i muss gültiges UTF-8 sein");

            foreach ($expectedUmlauts as $umlaut) {
                if (str_contains($rowString, $umlaut)) {
                    $foundUmlauts[$umlaut] = true;
                }
            }
        }

        $this->assertCount(count($expectedUmlauts), $foundUmlauts, 'Alle erwarteten Umlaute müssen gefunden werden');
    }

    public function test_from_file_with_iso_encoding(): void {
        if (!file_exists($this->testFileIso)) {
            $this->markTestSkipped('iso.csv Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileIso, ';', '"', true);

        $this->assertTrue($doc->hasHeader());

        // Prüfen, dass der gesamte Inhalt gültiges UTF-8 ist
        $docString = $doc->toString();
        $this->assertTrue(mb_check_encoding($docString, 'UTF-8'), 'Gesamtes Dokument muss gültiges UTF-8 sein');
    }

    public function test_from_file_range_with_ansi_encoding(): void {
        if (!file_exists($this->testFileAnsi)) {
            $this->markTestSkipped('ansi.csv Test-Datei nicht gefunden');
        }

        // Teste Zeilen 1-2 (Header + erste Datenzeile)
        $doc = CSVDocumentParser::fromFileRange($this->testFileAnsi, 1, 2, ';', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertEquals(1, $doc->countRows(), 'Sollte genau eine Datenzeile haben');

        // Umlaute prüfen
        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $rowString = $row->toString();
        $this->assertTrue(mb_check_encoding($rowString, 'UTF-8'), 'Datenzeile muss gültiges UTF-8 sein');
        $this->assertStringContainsString('München', $rowString, 'München muss korrekt konvertiert sein');
    }

    public function test_from_string_with_encoding_parameter(): void {
        // Simuliere ISO-8859-1 kodierten Inhalt
        $isoContent = mb_convert_encoding("Name;Stadt\nTest;München", 'ISO-8859-1', 'UTF-8');

        $doc = CSVDocumentParser::fromString($isoContent, ';', '"', true, 'ISO-8859-1');

        $this->assertTrue($doc->hasHeader());
        $row = $doc->getRow(0);
        $this->assertNotNull($row);

        $rowString = $row->toString();
        $this->assertTrue(mb_check_encoding($rowString, 'UTF-8'), 'Datenzeile muss gültiges UTF-8 sein');
        $this->assertStringContainsString('München', $rowString, 'München muss korrekt konvertiert sein');
    }

    // ===== Streaming-Tests für große CSV-Dateien =====

    public function test_stream_rows(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $rowCount = 0;
        foreach (CSVDocumentParser::streamRows($this->testFileComma, ',', '"', true) as $lineNumber => $row) {
            $this->assertInstanceOf(DataLine::class, $row);
            $this->assertGreaterThan(0, $lineNumber);
            $rowCount++;
        }

        $this->assertGreaterThan(0, $rowCount, 'Mindestens eine Datenzeile erwartet');
    }

    public function test_stream_rows_without_header(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $rowCountWithHeader = 0;
        foreach (CSVDocumentParser::streamRows($this->testFileComma, ',', '"', true) as $row) {
            $rowCountWithHeader++;
        }

        $rowCountWithoutHeader = 0;
        foreach (CSVDocumentParser::streamRows($this->testFileComma, ',', '"', false) as $row) {
            $rowCountWithoutHeader++;
        }

        // Ohne Header sollte eine Zeile mehr geben (Header wird als DataLine behandelt)
        $this->assertEquals($rowCountWithHeader + 1, $rowCountWithoutHeader);
    }

    public function test_stream_all(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $headerFound = false;
        $rowCount = 0;

        foreach (CSVDocumentParser::streamAll($this->testFileComma, ',', '"', true) as $index => $line) {
            if ($index === 0) {
                $this->assertInstanceOf(HeaderLine::class, $line, 'Erste Zeile sollte HeaderLine sein');
                $headerFound = true;
            } else {
                $this->assertInstanceOf(DataLine::class, $line);
                $rowCount++;
            }
        }

        $this->assertTrue($headerFound, 'Header sollte gefunden werden');
        $this->assertGreaterThan(0, $rowCount, 'Mindestens eine Datenzeile erwartet');
    }

    public function test_stream_rows_with_multi_line_fields(): void {
        if (!file_exists($this->testFileMultiLine)) {
            $this->markTestSkipped('multiline.csv Test-Datei nicht gefunden');
        }

        $foundMultiLine = false;
        foreach (CSVDocumentParser::streamRows($this->testFileMultiLine, ',', '"', true) as $row) {
            $this->assertInstanceOf(DataLine::class, $row);

            // Prüfe ob eine Zeile einen Zeilenumbruch enthält
            foreach ($row->getFields() as $field) {
                if (str_contains($field->getValue(), "\n")) {
                    $foundMultiLine = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($foundMultiLine, 'Mehrzeiliges Feld sollte erkannt werden');
    }

    public function test_read_header(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $header = CSVDocumentParser::readHeader($this->testFileComma, ',', '"');

        $this->assertInstanceOf(HeaderLine::class, $header);
        $this->assertGreaterThan(0, $header->countFields());
    }

    public function test_process_batches(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $batchesProcessed = 0;
        $totalRowsFromBatches = 0;

        $totalRows = CSVDocumentParser::processBatches(
            $this->testFileComma,
            function (array $batch, int $batchNumber) use (&$batchesProcessed, &$totalRowsFromBatches) {
                $batchesProcessed++;
                $totalRowsFromBatches += count($batch);

                // Prüfe dass alle Elemente DataLines sind
                foreach ($batch as $row) {
                    $this->assertInstanceOf(DataLine::class, $row);
                }
            },
            batchSize: 2, // Kleine Batch-Größe für Tests
            delimiter: ',',
            enclosure: '"',
            hasHeader: true
        );

        $this->assertGreaterThan(0, $batchesProcessed, 'Mindestens ein Batch sollte verarbeitet werden');
        $this->assertEquals($totalRows, $totalRowsFromBatches, 'Gesamtzeilen sollten übereinstimmen');
    }

    public function test_stream_rows_with_encoding_conversion(): void {
        if (!file_exists($this->testFileAnsi)) {
            $this->markTestSkipped('ansi.csv Test-Datei nicht gefunden');
        }

        $foundMuenchen = false;
        foreach (CSVDocumentParser::streamRows($this->testFileAnsi, ';', '"', true, false, true) as $row) {
            $rowString = $row->toString();
            $this->assertTrue(mb_check_encoding($rowString, 'UTF-8'), 'Zeile muss gültiges UTF-8 sein');

            if (str_contains($rowString, 'München')) {
                $foundMuenchen = true;
            }
        }

        $this->assertTrue($foundMuenchen, 'München sollte korrekt konvertiert gefunden werden');
    }

    public function test_stream_rows_compared_to_from_file(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        // Vergleiche Streaming mit normalem Parsing
        $docFromFile = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true);

        $streamedRows = [];
        foreach (CSVDocumentParser::streamRows($this->testFileComma, ',', '"', true) as $row) {
            $streamedRows[] = $row;
        }

        $this->assertEquals($docFromFile->countRows(), count($streamedRows), 'Anzahl der Zeilen sollte übereinstimmen');

        // Vergleiche einzelne Zeilen
        for ($i = 0; $i < min(5, count($streamedRows)); $i++) {
            $fromFileRow = $docFromFile->getRow($i);
            $streamedRow = $streamedRows[$i];

            $this->assertNotNull($fromFileRow);
            $this->assertEquals(
                $fromFileRow->toString(),
                $streamedRow->toString(),
                "Zeile $i sollte übereinstimmen"
            );
        }
    }

    public function test_count_rows(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        // Zähle mit Header
        $countWithHeader = CSVDocumentParser::countRows($this->testFileComma, true);

        // Zähle ohne Header (Header wird mitgezählt)
        $countWithoutHeader = CSVDocumentParser::countRows($this->testFileComma, false);

        // Mit Header sollte eine Zeile weniger sein
        $this->assertEquals($countWithHeader + 1, $countWithoutHeader);

        // Vergleiche mit normalem Parsing
        $doc = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true);
        $this->assertEquals($doc->countRows(), $countWithHeader, 'countRows sollte gleiche Anzahl wie Document liefern');
    }
}
