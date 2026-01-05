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

namespace Tests\CommonToolkit\Parsers;

use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Entities\CSV\HeaderLine;
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
        $this->testFileComma              = $base . 'comma.csv';
        $this->testFileSemicolon          = $base . 'semicolon.csv';
        $this->testFileTab                = $base . 'tab.csv';
        $this->testFileEmpty              = $base . 'empty.csv';
        $this->testFileMalformed          = $base . 'malformed.csv';
        $this->testFileMultiLine          = $base . 'multiline.csv';
        $this->testFileQuoted             = $base . 'quoted.csv';
        $this->testFileDoubleQuoted       = $base . 'doublequoted.csv';
        $this->testFileInconsistentQuoted = $base . 'quoted-inkonsistent.csv';
        $this->testFileAnsi               = $base . 'ansi.csv';
        $this->testFileIso                = $base . 'iso.csv';
    }

    public function testParseCommaSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertInstanceOf(HeaderLine::class, $doc->getHeader());
        $this->assertGreaterThan(0, $doc->countRows(), 'Mindestens eine Datenzeile erwartet');
        $this->assertInstanceOf(DataLine::class, $doc->getRow(0));

        $rebuilt = $doc->toString();
        $this->assertNotEmpty($rebuilt);
        $this->assertStringContainsString(',', $rebuilt);
    }

    public function testParseSemicolonSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileSemicolon);
        $doc = CSVDocumentParser::fromString($csv, ';', '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function testParseTabSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileTab);
        $doc = CSVDocumentParser::fromString($csv, "\t", '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function testParseQuotedCSV(): void {
        $csv = file_get_contents($this->testFileQuoted);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $this->assertStringContainsString('"', $row->toString());
    }

    public function testParseDoubleQuotedCSV(): void {
        $csv = file_get_contents($this->testFileDoubleQuoted);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        [$strict, $nonStrict] = $doc->getHeader()->getEnclosureRepeatRange();
        $this->assertGreaterThanOrEqual(2, $nonStrict);
    }

    public function testDetectMultiLineCSV(): void {
        $csv = file_get_contents($this->testFileMultiLine);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertGreaterThan(1, $doc->countRows());
        $multiLineValue = $doc->getRow(0)?->getField(2)?->getValue() ?? '';
        $this->assertStringContainsString("\n", $multiLineValue, 'Mehrzeiliger Inhalt erwartet');
    }

    public function testEmptyCSVShouldThrow(): void {
        $csv = file_get_contents($this->testFileEmpty);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function testMalformedCSVShouldThrow(): void {
        $csv = file_get_contents($this->testFileMalformed);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function testInconsistentQuotedCSVShouldThrow(): void {
        $csv = file_get_contents($this->testFileInconsistentQuoted);
        $this->expectException(RuntimeException::class);
        CSVDocumentParser::fromString($csv);
    }

    public function testRoundTripIntegrity(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocumentParser::fromString($csv, ',', '"');
        $rebuilt = $doc->toString(',', '"');

        $doc2 = CSVDocumentParser::fromString($rebuilt, ',', '"');
        $this->assertTrue($doc->equals($doc2), 'Roundtrip sollte identisch bleiben');
    }

    public function testParseWithoutHeader(): void {
        $doc = CSVDocumentParser::fromString('"Alice","alice@example.com"', ',', '"', false);

        $this->assertFalse($doc->hasHeader());
        $this->assertEquals(1, $doc->countRows());

        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $this->assertEquals('Alice', $row->getField(0)->getValue());
        $this->assertEquals('alice@example.com', $row->getField(1)->getValue());
    }

    public function testFromFileMethod(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileComma, ',', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function testFromFileWithStartLine(): void {
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

    public function testFromFileRange(): void {
        if (!file_exists($this->testFileComma)) {
            $this->markTestSkipped('Test-Datei nicht gefunden');
        }

        // Teste Zeilen 1-3
        $doc = CSVDocumentParser::fromFileRange($this->testFileComma, 1, 3, ',', '"', true);

        $this->assertTrue($doc->hasHeader());
        $this->assertLessThanOrEqual(2, $doc->countRows()); // Header + max 2 Datenzeilen
    }

    public function testFromFileRangeInvalidRange(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Startzeile (5) darf nicht größer als Endzeile (3) sein');

        CSVDocumentParser::fromFileRange($this->testFileComma, 5, 3);
    }

    public function testFromFileWithAnsiEncoding(): void {
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

    public function testFromFileWithAnsiEncodingAllRows(): void {
        if (!file_exists($this->testFileAnsi)) {
            $this->markTestSkipped('ansi.csv Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileAnsi, ';', '"', true);

        // Alle Datenzeilen durchgehen und Umlaute prüfen
        $expectedUmlauts = ['München', 'Köln'];
        $foundUmlauts = [];

        for ($i = 0; $i < $doc->countRows(); $i++) {
            $row = $doc->getRow($i);
            if ($row === null) continue;

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

    public function testFromFileWithIsoEncoding(): void {
        if (!file_exists($this->testFileIso)) {
            $this->markTestSkipped('iso.csv Test-Datei nicht gefunden');
        }

        $doc = CSVDocumentParser::fromFile($this->testFileIso, ';', '"', true);

        $this->assertTrue($doc->hasHeader());

        // Prüfen, dass der gesamte Inhalt gültiges UTF-8 ist
        $docString = $doc->toString();
        $this->assertTrue(mb_check_encoding($docString, 'UTF-8'), 'Gesamtes Dokument muss gültiges UTF-8 sein');
    }

    public function testFromFileRangeWithAnsiEncoding(): void {
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

    public function testFromStringWithEncodingParameter(): void {
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
}
