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

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\FileTypes\XmlFile;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;
use Tests\Contracts\BaseTestCase;

class XmlFileTest extends BaseTestCase {
    private string $testValidXml = __DIR__ . '/../../.samples/valid.xml';
    private string $testInvalidXml = __DIR__ . '/../../.samples/invalid.xml';
    private string $testXsd = __DIR__ . '/../../.samples/schema.xsd';
    private string $testEmptyXml = __DIR__ . '/../../.samples/empty.xml';

    public function test_is_well_formed() {
        $this->assertTrue(XmlFile::isWellFormed($this->testValidXml));
        $this->assertFalse(XmlFile::isWellFormed($this->testInvalidXml));
    }

    public function test_get_meta_data() {
        $meta = XmlFile::getMetaData($this->testValidXml);

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('RootElement', $meta);
        $this->assertArrayHasKey('Encoding', $meta);
        $this->assertArrayHasKey('Version', $meta);

        $this->assertEquals('kunden', $meta['RootElement']); // Beispiel-Root
    }

    public function test_is_valid_with_schema() {
        $this->assertTrue(XmlFile::isValid($this->testValidXml, $this->testXsd));
        $this->assertFalse(XmlFile::isValid($this->testInvalidXml, $this->testXsd));
    }

    public function test_count_records_without_element_name() {
        $count = XmlFile::countRecords($this->testValidXml);
        $this->assertEquals(2, $count); // z. B. <kunde> … </kunde> ×2 unter <kunden>
    }

    public function test_count_records_with_element_name() {
        $count = XmlFile::countRecords($this->testValidXml, 'kunde');
        $this->assertEquals(2, $count); // explizit <kunde>
    }

    public function test_count_records_empty_file() {
        $this->expectException(Exception::class);
        XmlFile::countRecords($this->testEmptyXml);
    }

    public function test_file_not_found() {
        $this->expectException(FileNotFoundException::class);
        XmlFile::isWellFormed(__DIR__ . '/not/existing.xml');
    }
}
