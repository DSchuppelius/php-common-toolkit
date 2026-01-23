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

class TiffFileTest extends BaseTestCase {
    private string $testFile;
    private string $testFileBak;
    private string $samplesDir;

    protected function setUp(): void {
        // Absolute Pfade verwenden
        $this->samplesDir = realpath(__DIR__ . '/../../.samples');
        $this->testFileBak = $this->samplesDir . '/fakejpg.tiff.bak';
        $this->testFile = $this->samplesDir . '/fakejpg.tiff';

        // Stelle sicher, dass die Testdatei existiert
        if (!File::exists($this->testFile) && File::exists($this->testFileBak)) {
            File::copy($this->testFileBak, $this->testFile);
        }
    }

    protected function tearDown(): void {
        // Cleanup: Entferne alle von Tests erstellte Dateien
        $filesToClean = [
            $this->samplesDir . '/fakejpg.jpg',      // Durch repair() erstellt
            $this->samplesDir . '/fakejpg.pdf',      // Durch convertToPdf() erstellt
            $this->samplesDir . '/MergedFile.tif',   // Durch merge() erstellt
            $this->samplesDir . '/MergedFile.pdf',   // Durch convertToPdf() erstellt
        ];

        foreach ($filesToClean as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Stelle die Testdateien aus dem Backup wieder her
        $this->restoreFromBackup($this->testFile, $this->testFileBak);
        $this->restoreFromBackup(
            $this->samplesDir . '/MergeFile_1.tif',
            $this->samplesDir . '/MergeFile_1.tif.bak'
        );
        $this->restoreFromBackup(
            $this->samplesDir . '/MergeFile_2.tif',
            $this->samplesDir . '/MergeFile_2.tif.bak'
        );
    }

    private function restoreFromBackup(string $file, string $backup): void {
        // Lösche existierende Datei (könnte verändert worden sein)
        if (File::exists($file)) {
            File::delete($file);
        }
        // Stelle aus Backup wieder her
        if (File::exists($backup)) {
            File::copy($backup, $file);
        }
    }

    public function testMimeType(): void {
        $mimeType = File::mimeType($this->testFile);
        $this->assertEquals('image/jpeg', $mimeType);
    }

    public function testConvertToTiff(): void {
        $tiffFile = TifFile::repair($this->testFile);
        $this->assertFileExists($tiffFile);
        $this->assertEquals('image/tiff', File::mimeType($tiffFile));
        // Wiederherstellung erfolgt in tearDown()
    }

    public function testConvertToPDF(): void {
        if (!File::exists($this->testFile)) {
            $this->markTestSkipped('Test file not found');
        }

        $pdfFile = $this->samplesDir . '/fakejpg.pdf';

        // deleteSourceFile = false, damit die Testdatei erhalten bleibt
        TifFile::convertToPdf($this->testFile, $pdfFile, true, false);

        $this->assertTrue(File::exists($pdfFile), "Das PDF wurde nicht erfolgreich erstellt.");
        // Cleanup erfolgt in tearDown()
    }

    public function testMerge(): void {
        $mergeFile1 = $this->samplesDir . '/MergeFile_1.tif';
        $mergeFile2 = $this->samplesDir . '/MergeFile_2.tif';
        $bakFile1 = $this->samplesDir . '/MergeFile_1.tif.bak';
        $bakFile2 = $this->samplesDir . '/MergeFile_2.tif.bak';

        // Stelle sicher, dass Testdateien existieren
        $this->restoreFromBackup($mergeFile1, $bakFile1);
        $this->restoreFromBackup($mergeFile2, $bakFile2);

        if (!File::exists($mergeFile1) || !File::exists($mergeFile2)) {
            $this->markTestSkipped('Merge test files not found');
        }

        $tiffFiles = [$mergeFile1, $mergeFile2];
        $mergedFile = $this->samplesDir . '/MergedFile.tif';
        $pdfFile = $this->samplesDir . '/MergedFile.pdf';

        // Vorher aufräumen
        File::delete($mergedFile);
        File::delete($pdfFile);

        // deleteSourceFiles = false, damit die Quelldateien erhalten bleiben
        TifFile::merge($tiffFiles, $mergedFile, false);

        $this->assertFileExists($mergedFile);

        // deleteSourceFile = false, damit die merged-Datei noch existiert für Assertion
        TifFile::convertToPdf($mergedFile, $pdfFile, true, false);

        $this->assertFileExists($pdfFile);
        // Cleanup erfolgt in tearDown()
    }
}
