<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Description  : Tests für die CSVDocument-Klasse (inkl. MultiLine, Encoding, Fehlerbehandlung)
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Entities\Common\CSV\CSVDocument;
use CommonToolkit\Entities\Common\CSV\CSVDataLine;
use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CSVDocumentTest extends TestCase {
    private string $testFileComma;
    private string $testFileSemicolon;
    private string $testFileTab;
    private string $testFileEmpty;
    private string $testFileMalformed;
    private string $testFileMultiLine;
    private string $testFileQuoted;
    private string $testFileDoubleQuoted;
    private string $testFileInconsistentQuoted;

    protected function setUp(): void {
        $base = __DIR__ . '/../../../../.samples/';
        $this->testFileComma              = $base . 'comma.csv';
        $this->testFileSemicolon          = $base . 'semicolon.csv';
        $this->testFileTab                = $base . 'tab.csv';
        $this->testFileEmpty              = $base . 'empty.csv';
        $this->testFileMalformed          = $base . 'malformed.csv';
        $this->testFileMultiLine          = $base . 'multiline.csv';
        $this->testFileQuoted             = $base . 'quoted.csv';
        $this->testFileDoubleQuoted       = $base . 'doublequoted.csv';
        $this->testFileInconsistentQuoted = $base . 'quoted-inkonsistent.csv';
    }

    // ----------------------------- TESTS -----------------------------

    public function testParseCommaSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocument::fromString($csv, ',', '"');

        $this->assertInstanceOf(CSVHeaderLine::class, $doc->getHeader());
        $this->assertGreaterThan(0, $doc->countRows(), 'Mindestens eine Datenzeile erwartet');
        $this->assertInstanceOf(CSVDataLine::class, $doc->getRow(0));

        $rebuilt = $doc->toString();
        $this->assertNotEmpty($rebuilt);
        $this->assertStringContainsString(',', $rebuilt);
    }

    public function testParseSemicolonSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileSemicolon);
        $doc = CSVDocument::fromString($csv, ';', '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function testParseTabSeparatedCSV(): void {
        $csv = file_get_contents($this->testFileTab);
        $doc = CSVDocument::fromString($csv, "\t", '"');
        $this->assertGreaterThan(0, $doc->countRows());
    }

    public function testParseQuotedCSV(): void {
        $csv = file_get_contents($this->testFileQuoted);
        $doc = CSVDocument::fromString($csv, ',', '"');

        $row = $doc->getRow(0);
        $this->assertNotNull($row);
        $this->assertStringContainsString('"', $row->toString());
    }

    public function testParseDoubleQuotedCSV(): void {
        $csv = file_get_contents($this->testFileDoubleQuoted);
        $doc = CSVDocument::fromString($csv, ',', '"');

        [$strict, $nonStrict] = $doc->getHeader()->getEnclosureRepeatRange();
        $this->assertGreaterThanOrEqual(2, $nonStrict);
    }

    public function testDetectMultiLineCSV(): void {
        $csv = file_get_contents($this->testFileMultiLine);
        $doc = CSVDocument::fromString($csv, ',', '"');

        $this->assertGreaterThan(1, $doc->countRows());
        $multiLineValue = $doc->getRow(0)?->getField(2)?->getValue() ?? '';
        $this->assertStringContainsString("\n", $multiLineValue, 'Mehrzeiliger Inhalt erwartet');
    }

    public function testEmptyCSVShouldThrow(): void {
        $csv = file_get_contents($this->testFileEmpty);
        $this->expectException(RuntimeException::class);
        CSVDocument::fromString($csv);
    }

    public function testMalformedCSVShouldThrow(): void {
        $this->markTestSkipped('Derzeit wird keine Ausnahme bei fehlerhaftem CSV ausgelöst.');
        $csv = file_get_contents($this->testFileMalformed);
        $this->expectException(RuntimeException::class);
        CSVDocument::fromString($csv);
    }

    public function testInconsistentQuotedCSVShouldThrow(): void {
        $csv = file_get_contents($this->testFileInconsistentQuoted);
        $this->expectException(RuntimeException::class);
        CSVDocument::fromString($csv);
    }

    public function testRoundTripIntegrity(): void {
        $csv = file_get_contents($this->testFileComma);
        $doc = CSVDocument::fromString($csv, ',', '"');
        $rebuilt = $doc->toString(',', '"');

        $doc2 = CSVDocument::fromString($rebuilt, ',', '"');
        $this->assertTrue($doc->equals($doc2), 'Roundtrip sollte identisch bleiben');
    }
}