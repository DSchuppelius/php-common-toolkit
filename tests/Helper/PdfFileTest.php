<?php
/*
 * Created on   : Sun Oct 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PdfFileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\FileSystem\FileTypes\PdfFile;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\InvalidPasswordException;
use Tests\Contracts\BaseTestCase;

final class PdfFileTest extends BaseTestCase {
    private string $testFile = __DIR__ . '/../../.samples/dummy.pdf';
    private string $outputDir;

    protected function setUp(): void {
        $this->outputDir = sys_get_temp_dir() . '/pdftest';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir);
        }

        if (!is_file($this->testFile)) {
            $this->markTestSkipped('.samples/dummy.pdf fehlt');
        }
    }

    public function testGetMetaData(): void {
        $meta = PdfFile::getMetaData($this->testFile);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('Pages', $meta);
        $this->assertArrayHasKey('Producer', $meta);
    }

    public function testIsValid(): void {
        $this->assertTrue(PdfFile::isValid($this->testFile));
    }

    public function testIsEncrypted(): void {
        $this->assertFalse(PdfFile::isEncrypted($this->testFile));
    }

    public function testEncryptAndDecryptWithoutPassword(): void {
        $outputEnc = $this->outputDir . '/encrypted.pdf';
        $outputDec = $this->outputDir . '/decrypted.pdf';

        $this->assertTrue(PdfFile::encrypt($this->testFile, $outputEnc));
        $this->assertFileExists($outputEnc);
        $this->assertTrue(PdfFile::isEncrypted($outputEnc));

        $this->assertTrue(PdfFile::decrypt($outputEnc, $outputDec));
        $this->assertFileExists($outputDec);
        $this->assertFalse(PdfFile::isEncrypted($outputDec));
    }

    public function testEncryptAndDecryptWithPassword(): void {
        $outputEnc = $this->outputDir . '/enc_pw.pdf';
        $outputDec = $this->outputDir . '/dec_pw.pdf';

        $this->assertTrue(PdfFile::encrypt($this->testFile, $outputEnc, '1234'));
        $this->assertTrue(PdfFile::isEncrypted($outputEnc));
        $meta = PdfFile::getMetaData($outputEnc, '1234');
        $this->assertIsArray($meta);

        $this->expectException(InvalidPasswordException::class);
        $meta = PdfFile::getMetaData($outputEnc);

        $this->assertTrue(PdfFile::decrypt($outputEnc, $outputDec, '1234'));
        $this->assertFalse(PdfFile::isEncrypted($outputDec));
        $this->assertFileExists($outputDec);
    }

    public function testFileNotFoundThrows(): void {
        $this->expectException(FileNotFoundException::class);
        PdfFile::getMetaData(__DIR__ . '/../../.samples/notfound.pdf');
    }

    protected function tearDown(): void {
        foreach (glob($this->outputDir . '/*.pdf') as $f) {
            @unlink($f);
        }
        @rmdir($this->outputDir);
    }
}