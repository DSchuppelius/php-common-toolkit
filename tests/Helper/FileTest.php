<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {

    private $testFile;

    protected function setUp(): void {
        $this->testFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($this->testFile, 'This is a test file.');
    }

    protected function tearDown(): void {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testMimeType() {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('text/plain', $mimeType);
    }

    public function testMimeTypeFailure() {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertEmpty(File::mimeType($invalidFile));
    }

    public function testFileExistsReturnsTrueForExistingFile() {
        $testFile = tempnam(sys_get_temp_dir(), 'test');
        $this->assertTrue(File::exists($testFile));

        unlink($testFile);
    }

    public function testFileExistsReturnsFalseForNonExistingFile() {
        $nonExistingFile = '/path/to/non/existing/file.txt';
        $this->assertFalse(File::exists($nonExistingFile));
    }
}
