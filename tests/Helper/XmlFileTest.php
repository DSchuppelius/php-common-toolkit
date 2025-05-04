<?php
/*
 * Created on   : Sa Mai 04 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlFileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\FileSystem\FileTypes\XmlFile;
use Tests\Contracts\BaseTestCase;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;

class XmlFileTest extends BaseTestCase {
    private string $testValidXml = __DIR__ . '/../../.samples/valid.xml';
    private string $testInvalidXml = __DIR__ . '/../../.samples/invalid.xml';
    private string $testXsd = __DIR__ . '/../../.samples/schema.xsd';
    private string $testEmptyXml = __DIR__ . '/../../.samples/empty.xml';

    public function testIsWellFormed() {
        $this->assertTrue(XmlFile::isWellFormed($this->testValidXml));
        $this->assertFalse(XmlFile::isWellFormed($this->testInvalidXml));
    }

    public function testGetMetaData() {
        $meta = XmlFile::getMetaData($this->testValidXml);

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('RootElement', $meta);
        $this->assertArrayHasKey('Encoding', $meta);
        $this->assertArrayHasKey('Version', $meta);

        $this->assertEquals('kunden', $meta['RootElement']); // Beispiel-Root
    }

    public function testIsValidWithSchema() {
        $this->assertTrue(XmlFile::isValid($this->testValidXml, $this->testXsd));
        $this->assertFalse(XmlFile::isValid($this->testInvalidXml, $this->testXsd));
    }

    public function testCountRecordsWithoutElementName() {
        $count = XmlFile::countRecords($this->testValidXml);
        $this->assertEquals(2, $count); // z. B. <kunde> … </kunde> ×2 unter <kunden>
    }

    public function testCountRecordsWithElementName() {
        $count = XmlFile::countRecords($this->testValidXml, 'kunde');
        $this->assertEquals(2, $count); // explizit <kunde>
    }

    public function testCountRecordsEmptyFile() {
        $this->expectException(Exception::class);
        XmlFile::countRecords($this->testEmptyXml);
    }

    public function testFileNotFound() {
        $this->expectException(FileNotFoundException::class);
        XmlFile::isWellFormed(__DIR__ . '/not/existing.xml');
    }
}