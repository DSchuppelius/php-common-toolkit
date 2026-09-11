<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TiffFileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\FileTypes\TifFile;
use Tests\Contracts\BaseTestCase;

/**
 * Arbeitet ausschließlich auf temporären Kopien der Fixtures: `repair()`
 * benennt die Datei in place um, `merge()`/`convertToPdf()` legen Dateien
 * daneben an — auf den versionierten `.samples` würde ein abgebrochener Lauf
 * (kein tearDown) veränderte Fixtures im Arbeitsbaum hinterlassen.
 */
class TiffFileTest extends BaseTestCase {
    private string $samplesDir;

    private string $workDir;

    private string $testFile;

    protected function setUp(): void {
        $samplesDir = realpath(__DIR__ . '/../../.samples');
        if ($samplesDir === false) {
            self::fail('.samples-Verzeichnis nicht gefunden');
        }
        $this->samplesDir = $samplesDir;

        $this->workDir = sys_get_temp_dir() . '/tiff-test-' . bin2hex(random_bytes(6));
        if (!mkdir($this->workDir, 0700, true) && !is_dir($this->workDir)) {
            self::fail('Temporäres Arbeitsverzeichnis konnte nicht angelegt werden');
        }

        $this->testFile = $this->copyFixture('fakejpg.tiff');
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->workDir);
    }

    /** Kopiert eine Fixture aus .samples in das Arbeitsverzeichnis und liefert den neuen Pfad. */
    private function copyFixture(string $name): string {
        $source = $this->samplesDir . '/' . $name;
        if (!File::exists($source)) {
            $this->markTestSkipped("Fixture $name nicht gefunden");
        }
        $target = $this->workDir . '/' . $name;
        File::copy($source, $target);

        return $target;
    }

    private function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function test_mime_type(): void {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('image/jpeg', $mimeType);
    }

    public function test_convert_to_tiff(): void {
        $tiffFile = TifFile::repair($this->testFile);
        $this->assertFileExists($tiffFile);
        $this->assertEquals('image/tiff', File::mimeType($tiffFile));
        $this->assertSame($this->workDir, dirname($tiffFile), 'repair() arbeitet nur im Arbeitsverzeichnis');
    }

    public function test_convert_to_pdf(): void {
        $pdfFile = $this->workDir . '/fakejpg.pdf';

        // deleteSourceFile = false, damit die Testdatei erhalten bleibt
        TifFile::convertToPdf($this->testFile, $pdfFile, true, false);

        $this->assertTrue(File::exists($pdfFile), 'Das PDF wurde nicht erfolgreich erstellt.');
    }

    public function test_merge(): void {
        $mergeFile1 = $this->copyFixture('MergeFile_1.tif');
        $mergeFile2 = $this->copyFixture('MergeFile_2.tif');

        $mergedFile = $this->workDir . '/MergedFile.tif';
        $pdfFile = $this->workDir . '/MergedFile.pdf';

        // deleteSourceFiles = false, damit die Quelldateien erhalten bleiben
        TifFile::merge([$mergeFile1, $mergeFile2], $mergedFile, false);

        $this->assertFileExists($mergedFile);
        $this->assertFileExists($mergeFile1);
        $this->assertFileExists($mergeFile2);

        // deleteSourceFile = false, damit die merged-Datei noch existiert für Assertion
        TifFile::convertToPdf($mergedFile, $pdfFile, true, false);

        $this->assertFileExists($pdfFile);
    }

    public function test_fixtures_in_samples_stay_untouched(): void {
        // Die versionierten Fixtures dürfen von keinem Test dieser Klasse angefasst werden.
        foreach (['fakejpg.tiff', 'MergeFile_1.tif', 'MergeFile_2.tif'] as $name) {
            $this->assertFileEquals($this->samplesDir . '/' . $name . '.bak', $this->samplesDir . '/' . $name);
        }
    }
}
