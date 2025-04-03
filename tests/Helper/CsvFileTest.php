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
}
