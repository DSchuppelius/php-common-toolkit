<?php
/*
 * Created on   : Fri Sep 25 2026
 * Author       : Daniel Joerg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TimeoutProbeTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Contracts;

use CommonToolkit\Helper\Platform;
use Symfony\Component\Process\Process;

/**
 * Startet tests/scripts/timeout_probe.php in einem Kindprozess, dessen PATH
 * mit Attrappen der Medien-/Office-Executables beginnt. Jede Attrappe schlaeft
 * 5 s (`exec sleep 5`, damit der Abbruch den schlafenden Prozess selbst trifft);
 * die LibreOffice-Attrappe beendet die Profil-Initialisierung sofort, damit nur
 * die eigentliche Konvertierung in die Zeitgrenze laeuft.
 */
trait TimeoutProbeTrait {
    /** @var list<string> Namen, unter denen media_/office_executables.json die Programme suchen. */
    private static array $fakeExecutables = ['ffmpeg', 'whisper', 'piper', 'espeak-ng', 'libreoffice'];

    /**
     * @return array{result: mixed, output: list<string>, returnCode: int, duration: float}
     */
    protected function runTimeoutProbe(string $case, float $timeout = 0.5): array {
        if (Platform::isWindows()) {
            $this->markTestSkipped('Die Attrappen sind sh-Skripte; die Probe laeuft nur unter Unix.');
        }

        $workDir = sys_get_temp_dir() . '/ctk-timeout-probe-' . bin2hex(random_bytes(4));
        $binDir = $workDir . '/bin';
        mkdir($binDir, 0700, true);

        $script = "#!/bin/sh\ncase \"\$*\" in *--terminate_after_init*) exit 0;; esac\nexec sleep 5\n";
        foreach (self::$fakeExecutables as $name) {
            file_put_contents($binDir . '/' . $name, $script);
            chmod($binDir . '/' . $name, 0700);
        }

        try {
            $process = new Process(
                [PHP_BINARY, dirname(__DIR__) . '/scripts/timeout_probe.php', $case, (string) $timeout, $workDir],
                null,
                ['PATH' => $binDir . PATH_SEPARATOR . (getenv('PATH') ?: '/usr/bin:/bin')],
                null,
                30.0,
            );
            $process->run();

            $lines = preg_split('/\R/', trim($process->getOutput())) ?: [];
            $json = (string) end($lines);
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded, "Probe ohne JSON-Ergebnis (Exit {$process->getExitCode()}): " . $process->getOutput() . $process->getErrorOutput());

            /** @var array{result: mixed, output: list<string>, returnCode: int, duration: float} $decoded */
            return $decoded;
        } finally {
            $this->removeProbeDir($workDir);
        }
    }

    private function removeProbeDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeProbeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
