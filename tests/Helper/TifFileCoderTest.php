<?php
/*
 * Created on   : Mon Oct 05 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TifFileCoderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Platform;
use Symfony\Component\Process\Process;
use Tests\Contracts\BaseTestCase;

/**
 * TifFile::repair() uebergibt die Eingabe mit dem Coder des zuvor geprueften
 * MIME-Typs an ImageMagick ("jpeg:<datei>" bzw. "tiff:<datei>"), statt
 * ImageMagick den Coder aus Inhalt und Endung waehlen zu lassen.
 *
 * Eine Attrappe von `convert` protokolliert ihre Argumente; in einem frischen
 * Prozess loest tiff_executables.json "convert" ueber PATH auf sie auf. Die
 * Fixtures werden wie in TiffFileTest nur als Kopie angefasst.
 */
class TifFileCoderTest extends BaseTestCase {
    private string $dir;

    protected function setUp(): void {
        parent::setUp();
        if (Platform::isWindows()) {
            $this->markTestSkipped('Die Attrappe ist ein sh-Skript; der Test laeuft nur unter Unix.');
        }

        $this->dir = sys_get_temp_dir() . '/ctk-tif-coder-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/bin', 0700, true);
        file_put_contents($this->dir . '/bin/convert', "#!/bin/sh\nfor a in \"\$@\"; do printf '%s\\n' \"\$a\" >> \"\$CTK_ARGV_LOG\"; done\nexit 0\n");
        chmod($this->dir . '/bin/convert', 0700);
    }

    protected function tearDown(): void {
        if (isset($this->dir) && is_dir($this->dir)) {
            foreach (array_merge(glob($this->dir . '/bin/*') ?: [], glob($this->dir . '/*') ?: []) as $file) {
                is_dir($file) ? @rmdir($file) : @unlink($file);
            }
            @rmdir($this->dir);
        }
        parent::tearDown();
    }

    public function test_jpeg_mit_tiff_endung_wird_als_jpeg_gelesen(): void {
        $file = $this->copyFixture('fakejpg.tiff');

        $argv = $this->repairWithRecordingConvert($file, false);

        $this->assertSame(['jpeg:' . $this->dir . '/fakejpg.jpg', $file], $argv);
    }

    public function test_erzwungene_reparatur_liest_die_eingabe_als_tiff(): void {
        $file = $this->copyFixture('MergeFile_1.tif');

        $argv = $this->repairWithRecordingConvert($file, true);

        $this->assertSame(['tiff:' . $this->dir . '/MergeFile_1.original.tif', '-monochrome', $file], $argv);
    }

    /**
     * Ruft TifFile::repair() in einem Kindprozess auf, dessen PATH mit der Attrappe beginnt.
     *
     * @return list<string> Die Argumente, mit denen `convert` gestartet wurde.
     */
    private function repairWithRecordingConvert(string $file, bool $force): array {
        $log = $this->dir . '/argv.log';
        $script = sprintf(
            'require %s; CommonToolkit\Helper\FileSystem\FileTypes\TifFile::repair($argv[1], $argv[2] === "1");',
            var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true),
        );
        $process = new Process(
            [PHP_BINARY, '-r', $script, $file, $force ? '1' : '0'],
            null,
            ['PATH' => $this->dir . '/bin' . PATH_SEPARATOR . (getenv('PATH') ?: '/usr/bin:/bin'), 'CTK_ARGV_LOG' => $log],
            null,
            60.0,
        );
        $process->run();

        $this->assertFileExists($log, 'Die Attrappe wurde nicht gestartet: ' . $process->getOutput() . $process->getErrorOutput());

        return array_values(array_filter(explode("\n", $this->readFile($log)), static fn (string $arg): bool => $arg !== ''));
    }

    /** Kopiert eine Fixture aus .samples in das Arbeitsverzeichnis und liefert den neuen Pfad. */
    private function copyFixture(string $name): string {
        $source = dirname(__DIR__, 2) . '/.samples/' . $name;
        if (!is_file($source)) {
            $this->markTestSkipped("Fixture $name nicht gefunden");
        }
        $target = $this->dir . '/' . $name;
        copy($source, $target);

        return $target;
    }
}
