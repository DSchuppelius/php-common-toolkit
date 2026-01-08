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
use Tests\Contracts\BaseTestCase;

class FileTest extends BaseTestCase {
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

    public function testMimeEncoding() {
        $encoding = File::mimeEncoding($this->testFile);
        $this->assertIsString($encoding);
        $this->assertNotEmpty($encoding);
    }

    public function testChardet() {
        $encoding = File::chardet($this->testFile);
        $this->assertIsString($encoding);
        $this->assertNotEmpty($encoding);
    }

    public function testMimeTypeFailure() {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::mimeType($invalidFile));
    }

    public function testMimeEncodingFailure() {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::mimeEncoding($invalidFile));
    }

    public function testChardetFailure() {
        $invalidFile = '/path/to/nonexistent/file';
        $this->assertFalse(File::chardet($invalidFile));
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

    public function testReadReturnsContent() {
        $content = File::read($this->testFile);
        $this->assertEquals('This is a test file.', $content);
    }

    public function testWriteOverwritesFile() {
        File::write($this->testFile, 'Updated content');
        $this->assertEquals('Updated content', file_get_contents($this->testFile));
    }

    public function testDeleteRemovesFile() {
        File::delete($this->testFile);
        $this->assertFileDoesNotExist($this->testFile);
    }

    public function testSizeReturnsCorrectFileSize() {
        $size = File::size($this->testFile);
        $this->assertEquals(strlen('This is a test file.'), $size);
    }

    public function testIsReadableReturnsTrue() {
        $this->assertTrue(File::isReadable($this->testFile));
    }

    public function testIsReadyReturnsTrue() {
        $this->assertTrue(File::isReady($this->testFile));
    }

    public function testWait4ReadyReturnsTrueImmediately() {
        $this->assertTrue(File::wait4Ready($this->testFile));
    }

    public function testCreateNewFile() {
        $newFile = sys_get_temp_dir() . '/created_test_file.txt';
        File::create($newFile, 0644, 'Hello world');
        $this->assertFileExists($newFile);
        $this->assertEquals('Hello world', file_get_contents($newFile));
        unlink($newFile);
    }

    public function testRenameFile() {
        $newName = $this->testFile . '_renamed';
        File::rename($this->testFile, $newName);
        $this->assertFileExists($newName);
        $this->assertFileDoesNotExist($this->testFile);
        unlink($newName);
    }

    public function testCopyFile() {
        $copyTarget = $this->testFile . '_copy';
        File::copy($this->testFile, $copyTarget);
        $this->assertFileExists($copyTarget);
        $this->assertEquals(file_get_contents($this->testFile), file_get_contents($copyTarget));
        unlink($copyTarget);
    }

    public function testMoveFile() {
        $moveTarget = $this->testFile . '_moved';
        File::move($this->testFile, sys_get_temp_dir(), basename($moveTarget));
        $this->assertFileExists($moveTarget);
        $this->assertFileDoesNotExist($this->testFile);
        unlink($moveTarget);
    }

    public function testContainsKeywordFindsMatch() {
        $this->assertTrue(File::containsKeyword($this->testFile, 'test'));
    }

    public function testContainsKeywordReturnsFalseOnNoMatch() {
        $this->assertFalse(File::containsKeyword($this->testFile, 'xyz123'));
    }

    public function testLineCount() {
        file_put_contents($this->testFile, "line1\nline2\nline3\n");
        $this->assertEquals(4, File::lineCount($this->testFile));
    }

    public function testCharCount() {
        file_put_contents($this->testFile, "abc123");
        $this->assertEquals(6, File::charCount($this->testFile));
    }

    public function testIsWindowsReservedNameDetectsNUL() {
        $this->assertTrue(File::isWindowsReservedName('/path/to/NUL'));
        $this->assertTrue(File::isWindowsReservedName('/path/to/nul'));
        $this->assertTrue(File::isWindowsReservedName('/path/to/NUL.txt'));
        $this->assertTrue(File::isWindowsReservedName('NUL'));
    }

    public function testIsWindowsReservedNameDetectsAllReservedNames() {
        foreach (File::WINDOWS_RESERVED_NAMES as $name) {
            $this->assertTrue(File::isWindowsReservedName("/path/to/$name"), "Failed for $name");
            $this->assertTrue(File::isWindowsReservedName("/path/to/$name.txt"), "Failed for $name.txt");
        }
    }

    public function testIsWindowsReservedNameReturnsFalseForNormalFiles() {
        $this->assertFalse(File::isWindowsReservedName('/path/to/normal.txt'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/file'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/NULLABLE.txt'));
        $this->assertFalse(File::isWindowsReservedName('/path/to/connect.log'));
    }

    public function testExistsReturnsFalseForWindowsReservedNames() {
        $this->assertFalse(File::exists('/tmp/NUL'));
        $this->assertFalse(File::exists('/tmp/CON'));
        $this->assertFalse(File::exists('/tmp/PRN'));
    }

    public function testWriteThrowsExceptionForWindowsReservedName() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::write('/tmp/NUL', 'test');
    }

    public function testCreateThrowsExceptionForWindowsReservedName() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::create('/tmp/NUL.txt');
    }

    public function testRenameThrowsExceptionForWindowsReservedName() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Windows-reservierter Gerätename');
        File::rename($this->testFile, '/tmp/NUL');
    }

    public function testReadAsUtf8WithAnsiCsv(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';
        $this->assertFileExists($ansiFile, 'ansi.csv Testdatei muss existieren');

        // Encoding der Originaldatei prüfen (sollte ISO-8859-x sein)
        $encoding = File::chardet($ansiFile);
        $this->assertNotFalse($encoding, 'Encoding muss erkannt werden');
        $this->assertStringContainsString('ISO-8859', $encoding, 'Datei sollte als ISO-8859 erkannt werden');

        // Als UTF-8 lesen
        $content = File::readAsUtf8($ansiFile);

        // Prüfen, dass der Inhalt jetzt gültiges UTF-8 ist
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'), 'Inhalt muss gültiges UTF-8 sein');

        // Prüfen, dass Umlaute korrekt konvertiert wurden
        $this->assertStringContainsString('München', $content, 'München muss korrekt konvertiert sein');
        $this->assertStringContainsString('Köln', $content, 'Köln muss korrekt konvertiert sein');
        $this->assertStringContainsString('Straße', $content, 'Straße muss korrekt konvertiert sein');
    }

    public function testReadLinesAsUtf8WithAnsiCsv(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';
        $this->assertFileExists($ansiFile, 'ansi.csv Testdatei muss existieren');

        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile));

        $this->assertGreaterThan(0, count($lines), 'Mindestens eine Zeile erwartet');

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $i => $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), "Zeile $i muss gültiges UTF-8 sein");
        }

        // Umlaute in den Zeilen prüfen
        $allContent = implode("\n", $lines);
        $this->assertStringContainsString('München', $allContent, 'München muss korrekt konvertiert sein');
        $this->assertStringContainsString('Straße', $allContent, 'Straße muss im Header korrekt konvertiert sein');
    }

    public function testReadLinesAsUtf8WithSkipEmpty(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $linesAll = iterator_to_array(File::readLinesAsUtf8($ansiFile, false));
        $linesNoEmpty = iterator_to_array(File::readLinesAsUtf8($ansiFile, true));

        // Bei skipEmpty=true sollten keine leeren Zeilen enthalten sein
        foreach ($linesNoEmpty as $line) {
            $this->assertNotEmpty(trim($line), 'Keine leeren Zeilen bei skipEmpty=true');
        }
    }

    public function testReadLinesAsUtf8WithMaxLines(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile, false, 2));

        $this->assertCount(2, $lines, 'Genau 2 Zeilen erwartet bei maxLines=2');
        $this->assertTrue(mb_check_encoding($lines[0], 'UTF-8'), 'Erste Zeile muss gültiges UTF-8 sein');
        $this->assertTrue(mb_check_encoding($lines[1], 'UTF-8'), 'Zweite Zeile muss gültiges UTF-8 sein');
    }

    public function testReadLinesAsUtf8WithStartLine(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        // Ab Zeile 2 lesen (überspringt Header)
        $lines = iterator_to_array(File::readLinesAsUtf8($ansiFile, false, null, 2));
        $allLines = iterator_to_array(File::readLinesAsUtf8($ansiFile));

        $this->assertCount(count($allLines) - 1, $lines, 'Eine Zeile weniger bei startLine=2');

        // Erste Zeile sollte Daten enthalten, nicht den Header
        $this->assertStringNotContainsString('Hausnummer', $lines[0], 'Header sollte übersprungen sein');
    }

    public function testReadLinesAsArrayUtf8(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = File::readLinesAsArrayUtf8($ansiFile);

        $this->assertIsArray($lines);
        $this->assertGreaterThan(0, count($lines));

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'Zeile muss gültiges UTF-8 sein');
        }

        $allContent = implode("\n", $lines);
        $this->assertStringContainsString('München', $allContent);
        $this->assertStringContainsString('Köln', $allContent);
    }

    public function testTailReturnsLastLines(): void {
        // Erstelle Testdatei mit mehreren Zeilen
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\nZeile 2\nZeile 3\nZeile 4\nZeile 5\nZeile 6\nZeile 7\nZeile 8\nZeile 9\nZeile 10";
        file_put_contents($testFile, $content);

        try {
            // Lese die letzten 3 Zeilen
            $lines = File::tail($testFile, 3);
            $this->assertCount(3, $lines);
            $this->assertEquals('Zeile 8', $lines[0]);
            $this->assertEquals('Zeile 9', $lines[1]);
            $this->assertEquals('Zeile 10', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }

    public function testTailWithDefaultLines(): void {
        // Erstelle Testdatei mit mehr als 10 Zeilen
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $lines = [];
        for ($i = 1; $i <= 15; $i++) {
            $lines[] = "Zeile $i";
        }
        file_put_contents($testFile, implode("\n", $lines));

        try {
            // Standard: 10 Zeilen
            $result = File::tail($testFile);
            $this->assertCount(10, $result);
            $this->assertEquals('Zeile 6', $result[0]);
            $this->assertEquals('Zeile 15', $result[9]);
        } finally {
            unlink($testFile);
        }
    }

    public function testTailWithSkipEmpty(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\n\nZeile 2\n\n\nZeile 3\nZeile 4\n\nZeile 5";
        file_put_contents($testFile, $content);

        try {
            // Ohne skipEmpty
            $lines = File::tail($testFile, 5, false);
            $this->assertContains('', $lines);

            // Mit skipEmpty
            $linesSkipped = File::tail($testFile, 5, true);
            $this->assertNotContains('', $linesSkipped);
            $this->assertCount(5, $linesSkipped);
        } finally {
            unlink($testFile);
        }
    }

    public function testTailWithEmptyFile(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        file_put_contents($testFile, '');

        try {
            $lines = File::tail($testFile, 10);
            $this->assertCount(0, $lines);
        } finally {
            unlink($testFile);
        }
    }

    public function testTailWithFewerLinesThanRequested(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\nZeile 2\nZeile 3";
        file_put_contents($testFile, $content);

        try {
            // Fordere 10 Zeilen an, aber nur 3 vorhanden
            $lines = File::tail($testFile, 10);
            $this->assertCount(3, $lines);
            $this->assertEquals('Zeile 1', $lines[0]);
            $this->assertEquals('Zeile 2', $lines[1]);
            $this->assertEquals('Zeile 3', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }

    public function testTailThrowsExceptionForInvalidLines(): void {
        $this->expectException(\InvalidArgumentException::class);
        File::tail($this->testFile, 0);
    }

    public function testTailThrowsExceptionForNonExistingFile(): void {
        $this->expectException(\ERRORToolkit\Exceptions\FileSystem\FileNotFoundException::class);
        File::tail('/path/to/nonexistent/file.txt', 10);
    }

    public function testTailAsUtf8(): void {
        $ansiFile = __DIR__ . '/../../.samples/ansi.csv';

        $lines = File::tailAsUtf8($ansiFile, 5);

        $this->assertIsArray($lines);
        $this->assertLessThanOrEqual(5, count($lines));

        // Alle Zeilen müssen gültiges UTF-8 sein
        foreach ($lines as $line) {
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), 'Zeile muss gültiges UTF-8 sein');
        }
    }

    public function testTailWithWindowsLineEndings(): void {
        $testFile = tempnam(sys_get_temp_dir(), 'tail_test');
        $content = "Zeile 1\r\nZeile 2\r\nZeile 3\r\nZeile 4\r\nZeile 5";
        file_put_contents($testFile, $content);

        try {
            $lines = File::tail($testFile, 3);
            $this->assertCount(3, $lines);
            // Prüfe, dass \r entfernt wurde
            $this->assertEquals('Zeile 3', $lines[0]);
            $this->assertEquals('Zeile 4', $lines[1]);
            $this->assertEquals('Zeile 5', $lines[2]);
        } finally {
            unlink($testFile);
        }
    }
}
