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

    /**
     * Startet den chardet-Prozess und öffnet die Pipes.
     *
     * @throws RuntimeException Wenn der Prozess nicht gestartet werden kann.
     */
    public static function start(): void {
        if (self::$process !== null) return;

        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            self::logErrorAndThrow(RuntimeException::class, "Konnte chardet-Prozess nicht starten.");
        }

        self::$process = $process;
        self::$stdin = $pipes[0];
        self::$stdout = $pipes[1];
        self::$stderr = $pipes[2];
    }

    /**
     * Stoppt den chardet-Prozess und schließt die Pipes.
     */
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

    /**
     * Detects the encoding of the given text using chardet.
     *
     * @param string $text The text to detect the encoding for.
     * @param bool $usePersistent Whether to use a persistent chardet process.
     * @return string|false The detected encoding or false on failure.
     */
    public static function detect(string $text, bool $usePersistent = false): string|false {
        if ($usePersistent) {
            return self::detectWithPersistentProcess($text);
        } else {
            return self::detectWithTemporaryProcess($text);
        }
    }

    /**
     * Detects the encoding of the given text using a temporary chardet process.
     *
     * @param string $text The text to detect the encoding for.
     * @return string|false The detected encoding or false on failure.
     */
    private static function detectWithTemporaryProcess(string $text): string|false {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open('chardet -', $descriptorspec, $pipes);
        if (!is_resource($process)) {
            return self::logErrorAndReturn(false, "Konnte temporären chardet-Prozess nicht starten.");
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 || $result === false) {
            return self::logErrorAndReturn(false, "Temporärer chardet-Prozess lieferte keinen Output.");
        }

        return self::normalizeEncoding(trim($result));
    }

    /**
     * Detects the encoding of the given text using a persistent chardet process.
     *
     * @param string $text The text to detect the encoding for.
     * @return string|false The detected encoding or false on failure.
     */
    private static function detectWithPersistentProcess(string $text): string|false {
        self::start();

        if (!is_resource(self::$stdin) || !is_resource(self::$stdout)) {
            return self::logErrorAndReturn(false, "Persistente chardet-Streams sind nicht verfügbar.");
        }

        fwrite(self::$stdin, $text . "\n");
        fflush(self::$stdin);

        // Lese eine Zeile vom Output
        $result = fgets(self::$stdout);
        if ($result === false) {
            return self::logErrorAndReturn(false, "Persistente chardet-Instanz hat nichts zurückgegeben.");
        }

        return self::normalizeEncoding(trim($result));
    }

    /**
     * Normalisiert die Kodierung basierend auf dem Ergebnis von chardet.
     *
     * @param string $result
     * @return string
     */
    private static function normalizeEncoding(string $result): string {
        if (preg_match('/:\s*([a-zA-Z0-9\-\_]+)\s+with\s+confidence/i', $result, $matches)) {
            $result = strtoupper($matches[1]);
        }

        return match ($result) {
            'ISO-8859-1', 'MACROMAN' => 'ISO-8859-15',
            'NONE' => 'UTF-8',
            default => $result
        };
    }

    public function __destruct() {
        self::stop();
    }
}
