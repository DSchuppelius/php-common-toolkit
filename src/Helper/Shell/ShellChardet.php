<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellChardet.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Helper\Shell;

use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class ShellChardet {
    use ErrorLog;

    private static $process = null;
    private static $stdin = null;
    private static $stdout = null;

    public static function start(): void {
        if (self::$process !== null) return;

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException("chardet konnte nicht gestartet werden.");
        }

        self::$process = $process;
        self::$stdin = $pipes[0];
        self::$stdout = $pipes[1];
    }

    public static function detect(string $text): string|false {
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);

        if (!is_resource($process)) {
            self::logError("Konnte chardet nicht starten.");
            return false;
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]); // EOF für chardet signalisieren

        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $result === false) {
            self::logError("chardet lieferte keinen oder fehlerhaften Output.");
            return false;
        }

        $result = trim($result);

        // Deine Nachbearbeitung:
        return match ($result) {
            'ISO-8859-1', 'MacRoman' => 'ISO-8859-15',
            'None' => 'UTF-8',
            default => $result
        };
    }

    public static function stop(): void {
        if (self::$process !== null) {
            fclose(self::$stdin);
            fclose(self::$stdout);
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$stdin = null;
            self::$stdout = null;
            self::$process = null;
        }
    }

    public function __destruct() {
        self::stop();
    }
}