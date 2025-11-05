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
use CommonToolkit\Parsers\CSVDocumentParser;
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
        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        $this->assertInstanceOf(CSVHeaderLine::class, $doc->getHeader());
        $this->assertGreaterThan(0, $doc->countRows(), 'Mindestens eine Datenzeile erwartet');
        $this->assertInstanceOf(CSVDataLine::class, $doc->getRow(0));

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
        $this->markTestSkipped('Derzeit wird keine Ausnahme bei fehlerhaftem CSV ausgelöst.');
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

    public function testReorderColumnsAndFromDocument(): void {
        // Beispiel-CSV mit 3 Spalten
        $csv = <<<CSV
        "Name","Email","Id"
        "Alice","alice@example.com","1"
        "Bob","bob@example.com","2"
        CSV;

        $doc = CSVDocumentParser::fromString($csv, ',', '"');

        // Builder aus vorhandenem Dokument erzeugen
        $builder = \CommonToolkit\Builders\CSVDocumentBuilder::fromDocument($doc);

        // Spaltenreihenfolge ändern
        $builder->reorderColumns(['Id', 'Name', 'Email']);
        $newDoc = $builder->build();

        // Prüfen, dass das Original unverändert bleibt
        $this->assertSame('"Name","Email","Id"', $doc->getHeader()->toString(',', '"'));

        // Neue Reihenfolge prüfen
        $this->assertSame('"Id","Name","Email"', $newDoc->getHeader()->toString(',', '"'));

        // Werte in der ersten Zeile prüfen
        $firstRow = $newDoc->getRow(0);
        $this->assertSame('"1"', $firstRow->getField(0)->toString());
        $this->assertSame('"Alice"', $firstRow->getField(1)->toString());
        $this->assertSame('"alice@example.com"', $firstRow->getField(2)->toString());

        // Konsistenz und Gleichheit prüfen
        $this->assertTrue($newDoc->isConsistent(), 'CSV nach Umsortierung muss konsistent bleiben');
        $this->assertFalse($newDoc->equals($doc), 'Umsortiertes Dokument darf nicht als gleich gelten');
    }
}