<?php
/*
 * Created on   : Tue Jan 21 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ZipFileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\Folder;
use CommonToolkit\Helper\FileSystem\FileTypes\ZipFile;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use Tests\Contracts\BaseTestCase;

class ZipFileTest extends BaseTestCase {
    private string $tempDir;
    private array $tempFiles = [];
    private static bool $zipAvailable;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$zipAvailable = class_exists('ZipArchive');
    }

    protected function setUp(): void {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zipfile_test_' . uniqid();
        Folder::create($this->tempDir, 0755, true);
    }

    protected function skipIfNoZipExtension(): void {
        if (!self::$zipAvailable) {
            $this->markTestSkipped('PHP ZipArchive-Erweiterung ist nicht verfügbar.');
        }
    }

    protected function tearDown(): void {
        // Aufräumen: Temporäre Dateien und Verzeichnisse löschen
        foreach ($this->tempFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        if (Folder::exists($this->tempDir)) {
            Folder::delete($this->tempDir, true);
        }

        parent::tearDown();
    }

    private function createTempFile(string $name, string $content = ''): string {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    public function testHasZipExtension(): void {
        $this->assertTrue(ZipFile::hasZipExtension('test.zip'));
        $this->assertTrue(ZipFile::hasZipExtension('path/to/file.zip'));
        $this->assertTrue(ZipFile::hasZipExtension('FILE.ZIP'));
        $this->assertFalse(ZipFile::hasZipExtension('test.txt'));
        $this->assertFalse(ZipFile::hasZipExtension('test.zip.txt'));
        $this->assertFalse(ZipFile::hasZipExtension('noextension'));
    }

    public function testCreateAndExtract(): void {
        $this->skipIfNoZipExtension();

        // Erstelle Testdateien
        $file1 = $this->createTempFile('test1.txt', 'Inhalt Datei 1');
        $file2 = $this->createTempFile('test2.txt', 'Inhalt Datei 2');

        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'test.zip';
        $extractDir = $this->tempDir . DIRECTORY_SEPARATOR . 'extracted';

        // Erstelle ZIP
        $result = ZipFile::create([$file1, $file2], $zipPath);
        $this->assertTrue($result);
        $this->assertTrue(File::exists($zipPath));

        // Validiere ZIP
        $this->assertTrue(ZipFile::isValid($zipPath));

        // Extrahiere ZIP (ohne Quelldatei zu löschen)
        ZipFile::extract($zipPath, $extractDir, false);

        // Prüfe extrahierte Dateien
        $this->assertTrue(File::exists($extractDir . DIRECTORY_SEPARATOR . 'test1.txt'));
        $this->assertTrue(File::exists($extractDir . DIRECTORY_SEPARATOR . 'test2.txt'));
        $this->assertEquals('Inhalt Datei 1', file_get_contents($extractDir . DIRECTORY_SEPARATOR . 'test1.txt'));
        $this->assertEquals('Inhalt Datei 2', file_get_contents($extractDir . DIRECTORY_SEPARATOR . 'test2.txt'));

        // Aufräumen
        Folder::delete($extractDir, true);
        File::delete($zipPath);
    }

    public function testCreateWithCustomArchiveNames(): void {
        $this->skipIfNoZipExtension();

        $file1 = $this->createTempFile('original.txt', 'Test content');

        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'custom_names.zip';
        $extractDir = $this->tempDir . DIRECTORY_SEPARATOR . 'extracted_custom';

        // Erstelle ZIP mit benutzerdefiniertem Archivnamen
        $files = [
            ['path' => $file1, 'archiveName' => 'subdir/renamed.txt'],
        ];
        ZipFile::create($files, $zipPath);

        // Liste Inhalte und prüfe Namen
        $contents = ZipFile::listContents($zipPath);
        $this->assertCount(1, $contents);
        $this->assertEquals('subdir/renamed.txt', $contents[0]['name']);

        // Extrahiere und prüfe
        ZipFile::extract($zipPath, $extractDir, false);
        $this->assertTrue(File::exists($extractDir . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'renamed.txt'));

        // Aufräumen
        Folder::delete($extractDir, true);
        File::delete($zipPath);
    }

    public function testCreateFromDirectory(): void {
        $this->skipIfNoZipExtension();

        // Erstelle Testverzeichnisstruktur
        $sourceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'source';
        Folder::create($sourceDir . DIRECTORY_SEPARATOR . 'subdir', 0755, true);
        file_put_contents($sourceDir . DIRECTORY_SEPARATOR . 'root.txt', 'Root file');
        file_put_contents($sourceDir . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'nested.txt', 'Nested file');

        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'from_dir.zip';

        // Erstelle ZIP aus Verzeichnis
        ZipFile::createFromDirectory($sourceDir, $zipPath);

        // Prüfe Inhalte
        $contents = ZipFile::listContents($zipPath);
        $names = array_column($contents, 'name');
        $this->assertContains('root.txt', $names);

        // Aufräumen
        Folder::delete($sourceDir, true);
        File::delete($zipPath);
    }

    public function testCreateFromDirectoryWithBaseName(): void {
        $this->skipIfNoZipExtension();

        $sourceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'source2';
        Folder::create($sourceDir, 0755, true);
        file_put_contents($sourceDir . DIRECTORY_SEPARATOR . 'file.txt', 'Content');

        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'with_base.zip';

        // Erstelle ZIP mit Basis-Ordnername
        ZipFile::createFromDirectory($sourceDir, $zipPath, 'myproject');

        // Prüfe dass der Basisname im Pfad enthalten ist
        $contents = ZipFile::listContents($zipPath);
        $this->assertNotEmpty($contents);
        $this->assertStringStartsWith('myproject/', $contents[0]['name']);

        // Aufräumen
        Folder::delete($sourceDir, true);
        File::delete($zipPath);
    }

    public function testListContents(): void {
        $this->skipIfNoZipExtension();

        $file1 = $this->createTempFile('list_test.txt', 'Content for size test');
        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'list_test.zip';

        ZipFile::create([$file1], $zipPath);

        $contents = ZipFile::listContents($zipPath);

        $this->assertCount(1, $contents);
        $this->assertEquals('list_test.txt', $contents[0]['name']);
        $this->assertArrayHasKey('size', $contents[0]);
        $this->assertArrayHasKey('compressedSize', $contents[0]);
        $this->assertArrayHasKey('isDirectory', $contents[0]);
        $this->assertFalse($contents[0]['isDirectory']);
        $this->assertEquals(strlen('Content for size test'), $contents[0]['size']);

        File::delete($zipPath);
    }

    public function testIsZipFile(): void {
        $this->skipIfNoZipExtension();

        // Erstelle eine echte ZIP-Datei
        $file = $this->createTempFile('mimetype_test.txt', 'test');
        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'mimetype_test.zip';
        ZipFile::create([$file], $zipPath);

        $this->assertTrue(ZipFile::isZipFile($zipPath));
        $this->assertFalse(ZipFile::isZipFile($file)); // Textdatei ist kein ZIP
        $this->assertFalse(ZipFile::isZipFile('/nonexistent/file.zip'));

        File::delete($zipPath);
    }

    public function testIsValidWithInvalidFile(): void {
        $this->skipIfNoZipExtension();

        $invalidFile = $this->createTempFile('not_a_zip.zip', 'This is not a valid ZIP file');

        $this->assertFalse(ZipFile::isValid($invalidFile));
    }

    public function testExtractDeletesSourceFile(): void {
        $this->skipIfNoZipExtension();

        $file = $this->createTempFile('delete_test.txt', 'content');
        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'delete_source.zip';
        $extractDir = $this->tempDir . DIRECTORY_SEPARATOR . 'extract_delete';

        ZipFile::create([$file], $zipPath);
        $this->assertTrue(File::exists($zipPath));

        // Extrahiere mit Löschen der Quelldatei (Standard)
        ZipFile::extract($zipPath, $extractDir, true);

        $this->assertFalse(File::exists($zipPath));
        $this->assertTrue(File::exists($extractDir . DIRECTORY_SEPARATOR . 'delete_test.txt'));

        Folder::delete($extractDir, true);
    }

    public function testExtractNonExistentFileThrows(): void {
        $this->skipIfNoZipExtension();

        $this->expectException(FileNotFoundException::class);
        ZipFile::extract('/nonexistent/file.zip', $this->tempDir);
    }

    public function testListContentsNonExistentFileThrows(): void {
        $this->skipIfNoZipExtension();

        $this->expectException(FileNotFoundException::class);
        ZipFile::listContents('/nonexistent/file.zip');
    }

    public function testCreateFromDirectoryNonExistentThrows(): void {
        $this->expectException(FolderNotFoundException::class);
        ZipFile::createFromDirectory('/nonexistent/directory', $this->tempDir . '/test.zip');
    }

    public function testGetErrorMessage(): void {
        $this->skipIfNoZipExtension();

        $this->assertStringContainsString('ZIP', ZipFile::getErrorMessage(\ZipArchive::ER_NOZIP));
        $this->assertStringContainsString('Speicher', ZipFile::getErrorMessage(\ZipArchive::ER_MEMORY));
        $this->assertStringContainsString('CRC', ZipFile::getErrorMessage(\ZipArchive::ER_CRC));
        $this->assertStringContainsString('Code:', ZipFile::getErrorMessage(9999)); // Unbekannter Code
    }

    public function testCreateSkipsMissingFiles(): void {
        $this->skipIfNoZipExtension();

        $existingFile = $this->createTempFile('exists.txt', 'content');
        $zipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'skip_missing.zip';

        // Erstelle ZIP mit existierender und nicht-existierender Datei
        $result = ZipFile::create([
            $existingFile,
            '/nonexistent/missing.txt'
        ], $zipPath);

        $this->assertTrue($result);

        $contents = ZipFile::listContents($zipPath);
        $this->assertCount(1, $contents);
        $this->assertEquals('exists.txt', $contents[0]['name']);

        File::delete($zipPath);
    }
}
