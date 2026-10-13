<?php

namespace CommonToolkit\Helper\Shell;

use CommonToolkit\Helper\Shell;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Zeichensatzerkennung über das Kommandozeilenwerkzeug chardet.
 *
 * Jeder Aufruf startet chardet neu (über {@see Shell::run()}, also mit
 * Zeitgrenze) und gibt den Text über die Standardeingabe. Einen dauerhaften
 * Prozess gibt es nicht mehr: chardet antwortet erst, wenn es sich sicher ist
 * oder die Eingabe endet, und beendet sich nach der ersten Antwort - ein
 * zweiter Aufruf lief ins Leere, ein unklarer Text hielt den Aufrufer
 * unbegrenzt fest.
 */
class ShellChardet {
    use ErrorLog;

    /** Zeitgrenze je Erkennung in Sekunden. */
    private const TIMEOUT = Shell::TIMEOUT_PROBE;

    /**
     * Früher: startete den dauerhaften chardet-Prozess. Heute ohne Wirkung.
     *
     * @deprecated Jeder Aufruf von {@see detect()} startet chardet selbst.
     */
    public static function start(): void {}

    /**
     * Früher: beendete den dauerhaften chardet-Prozess. Heute ohne Wirkung.
     *
     * @deprecated Jeder Aufruf von {@see detect()} startet chardet selbst.
     */
    public static function stop(): void {}

    /**
     * Detects the encoding of the given text using chardet.
     *
     * @param string $text The text to detect the encoding for.
     * @param bool $usePersistent Ohne Wirkung (frueher: dauerhafter Prozess), siehe Klassenbeschreibung.
     * @return string|false The detected encoding or false on failure.
     */
    public static function detect(string $text, bool $usePersistent = false): string|false {
        $result = Shell::run(['chardet', '-'], self::TIMEOUT, null, null, null, $text);

        if ($result->timedOut) {
            return self::logErrorAndReturn(false, 'chardet abgebrochen: ' . Shell::timeoutMessage(self::TIMEOUT));
        }
        if (!$result->isSuccessful() || trim($result->output) === '') {
            return self::logErrorAndReturn(false, "chardet lieferte keinen Output.");
        }

        return self::normalizeEncoding(trim($result->output));
    }

    /**
     * Normalisiert die Kodierung basierend auf dem Ergebnis von chardet.
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
}
