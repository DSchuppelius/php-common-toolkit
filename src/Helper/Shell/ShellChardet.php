<?php

namespace CommonToolkit\Helper\Shell;

use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class ShellChardet {
    use ErrorLog;

    private static $process = null;
    private static $stdin = null;
    private static $stdout = null;
    private static $stderr = null;

    public static function start(): void {
        if (self::$process !== null) return;

        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            self::logError("Konnte chardet-Prozess nicht starten.");
            throw new RuntimeException("chardet konnte nicht gestartet werden.");
        }

        self::$process = $process;
        self::$stdin = $pipes[0];
        self::$stdout = $pipes[1];
        self::$stderr = $pipes[2];
    }

    public static function stop(): void {
        if (self::$process !== null) {
            @fclose(self::$stdin);
            @fclose(self::$stdout);
            @fclose(self::$stderr);
            @proc_terminate(self::$process);
            @proc_close(self::$process);
            self::$stdin = null;
            self::$stdout = null;
            self::$stderr = null;
            self::$process = null;
        }
    }

    public static function detect(string $text, bool $usePersistent = false): string|false {
        if ($usePersistent) {
            return self::detectWithPersistentProcess($text);
        } else {
            return self::detectWithTemporaryProcess($text);
        }
    }

    private static function detectWithTemporaryProcess(string $text): string|false {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            self::logError("Konnte temporären chardet-Prozess nicht starten.");
            return false;
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 || $result === false) {
            self::logError("Temporärer chardet-Prozess lieferte keinen Output.");
            return false;
        }

        return self::normalizeEncoding(trim($result));
    }

    private static function detectWithPersistentProcess(string $text): string|false {
        self::start();

        if (!is_resource(self::$stdin) || !is_resource(self::$stdout)) {
            self::logError("Persistente chardet-Streams sind nicht verfügbar.");
            return false;
        }

        fwrite(self::$stdin, $text . "\n");
        fflush(self::$stdin);

        // Lese eine Zeile vom Output
        $result = fgets(self::$stdout);
        if ($result === false) {
            self::logError("Persistente chardet-Instanz hat nichts zurückgegeben.");
            return false;
        }

        return self::normalizeEncoding(trim($result));
    }

    private static function normalizeEncoding(string $result): string {
        if (preg_match('/:\s*([a-zA-Z0-9\-\_]+)\s+with\s+confidence/i', $result, $matches)) {
            $result = $matches[1];
        }

        return match (strtolower($result)) {
            'iso-8859-1', 'macroman' => 'ISO-8859-15',
            'none' => 'UTF-8',
            default => $result
        };
    }

    public function __destruct() {
        self::stop();
    }
}