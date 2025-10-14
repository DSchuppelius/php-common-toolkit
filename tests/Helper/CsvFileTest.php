<?php
/*
 * Created on   : Sat Mar 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CsvFileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\FileSystem\FileTypes\CsvFile;
use Tests\Contracts\BaseTestCase;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;

class CsvFileTest extends BaseTestCase {
    private $testFileComma = __DIR__ . '/../../.samples/comma.csv';
    private $testFileSemicolon = __DIR__ . '/../../.samples/semicolon.csv';
    private $testFileTab = __DIR__ . '/../../.samples/tab.csv';
    private $testFileEmpty = __DIR__ . '/../../.samples/empty.csv';
    private $testFileMalformed = __DIR__ . '/../../.samples/malformed.csv';
    private $testFileISO = __DIR__ . '/../../.samples/iso.csv';
    private $testFileMultiLine = __DIR__ . '/../../.samples/multiline.csv';
    private $testFileQuoted = __DIR__ . '/../../.samples/quoted.csv';
    private $testFileDoubleQuoted = __DIR__ . '/../../.samples/doublequoted.csv';
    private $testFileInconsistentQuoted = __DIR__ . '/../../.samples/quoted-inkonsistent.csv';

    public function testDetectDelimiter() {
        $this->assertEquals(',', CsvFile::detectDelimiter($this->testFileComma));
        $this->assertEquals(';', CsvFile::detectDelimiter($this->testFileSemicolon));
        $this->assertEquals("\t", CsvFile::detectDelimiter($this->testFileTab));
    }

    public function testGetMetaData() {
        $meta = CsvFile::getMetaData($this->testFileComma);
        $this->assertEquals(3, $meta['RowCount']); // 2 Datenzeilen
        $this->assertEquals(3, $meta['ColumnCount']); // 3 Spalten
        $this->assertEquals(',', $meta['Delimiter']);

        $meta = CsvFile::getMetaData($this->testFileSemicolon);
        $this->assertEquals(';', $meta['Delimiter']);
    }

    public function testIsWellFormed() {
        $this->assertTrue(CsvFile::isWellFormed($this->testFileComma));
        $this->assertFalse(CsvFile::isWellFormed($this->testFileMalformed));
    }

    public function testIsValid() {
        $expectedHeader = ['ID', 'Name', 'Alter'];

        // Header ist korrekt -> true
        $this->assertTrue(CsvFile::isValid($this->testFileComma, $expectedHeader));

        // Header ist korrekt, aber Zeilen fehlerhaft -> true (weil `welformed` default `false` ist)
        $this->assertTrue(CsvFile::isValid($this->testFileMalformed, $expectedHeader));

        // Header + Well-formed-Check -> false für malformed.csv
        $this->assertFalse(CsvFile::isValid($this->testFileMalformed, $expectedHeader, null, true));
    }

    public function testFileNotFound() {
        $this->expectException(FileNotFoundException::class);
        CsvFile::detectDelimiter(__DIR__ . '/../../.samples/commas.csv');
    }

    public function testEmptyFile() {
        $this->expectException(Exception::class);
        CsvFile::detectDelimiter($this->testFileEmpty);
    }

    public function testMatchRowSuccess() {
        $row = null;
        $result = CsvFile::matchRow($this->testFileComma, ['1', '*', '*'], ',', 'UTF-8', $row);

        $this->assertTrue($result);
        $this->assertIsArray($row);
        $this->assertEquals('1', $row[0]);
    }

    public function testMatchRowNoMatch() {
        $row = null;
        $result = CsvFile::matchRow($this->testFileComma, ['NichtVorhanden', '*', '*'], ',', 'UTF-8', $row);

        $this->assertFalse($result);
        $this->assertNull($row);
    }

    public function testMatchRowWithEncoding() {
        // Datei mit Umlauten oder ISO-8859-1 kodiertem Inhalt wäre hier ideal
        $row = null;
        $result = CsvFile::matchRow($this->testFileISO, ['*', 'Müller', '35'], ',', 'ISO-8859-1', $row);

        $this->assertTrue($result);
        $this->assertEquals('35', end($row));
    }

    public function testMatchRowOnEmptyFile() {
        $row = null;
        $result = CsvFile::matchRow($this->testFileEmpty, ['*', '*', '*'], ',', 'UTF-8', $row);

        $this->assertFalse($result);
        $this->assertNull($row);
    }

    public function testQuotedCsvFile() {
        $row = null;
        $result = CsvFile::matchRow($this->testFileQuoted, ['*', '*', '*', '46', '*', 'Bargeldauszahlung', '*', '*', '*', '-349,09', '*', '*', '2000,00', '*', '*', 'EUR'], null, 'UTF-8', $row);

        $this->assertTrue($result);
        $this->assertIsArray($row);
        $this->assertEquals('46', $row[3]);
        $this->assertEquals('-349,09', $row[9]);
    }

    public function testHasDoubleQuotedFields() {
        // $this->assertTrue(CsvFile::hasRepeatedEnclosureColumns($this->testFileDoubleQuoted));
        $this->assertFalse(CsvFile::hasRepeatedEnclosureColumns($this->testFileInconsistentQuoted));
        $this->assertFalse(CsvFile::hasRepeatedEnclosureColumns($this->testFileComma));
    }

    public function testMatchRowWithWrongPatternLength() {
        $row = null;
        // Test mit zu vielen Mustern
        $result = CsvFile::matchRow($this->testFileComma, ['*', '*', '*', '*'], ',', 'UTF-8', $row);

        $this->assertFalse($result);
        $this->assertNull($row);
    }

    public function testCountDataRowsWithHeader() {
        $count = CsvFile::countDataRows($this->testFileComma, ',', true);
        $this->assertEquals(2, $count); // 3 Zeilen - 1 Header = 2 Datenzeilen
    }

    public function testCountDataRowsWithoutHeader() {
        $count = CsvFile::countDataRows($this->testFileComma, ',', false);
        $this->assertEquals(3, $count); // alle Zeilen zählen
    }

    public function testCountDataRowsEmptyFile() {
        $count = CsvFile::countDataRows($this->testFileEmpty, ',', true);
        $this->assertEquals(0, $count); // keine Datenzeilen
    }
}