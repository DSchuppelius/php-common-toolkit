<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Helper\Shell\ShellChardet;
use ERRORToolkit\Traits\ErrorLog;
use Throwable;

class StringHelper {
    use ErrorLog;

    /**
     * Wandelt UTF-8 nach ISO-8859-1 (Latin1) um, ersetzt nicht darstellbare Zeichen mit '?'
     * Nutzt iconv als bevorzugten Weg, fallback auf eigene Implementierung
     */
    public static function utf8ToIso8859_1(string $string): string {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $string);
        if ($converted !== false) {
            return $converted;
        }

        // Fallback: manuelle Konvertierung
        $result = '';
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $char = $string[$i];
            $ord = ord($char);

            if ($ord < 0x80) {
                $result .= $char;
            } elseif (($ord & 0xE0) === 0xC0 && $i + 1 < $len) {
                $next = ord($string[++$i]);
                $code = (($ord & 0x1F) << 6) | ($next & 0x3F);
                $result .= $code < 256 ? chr($code) : '?';
            } elseif (($ord & 0xF0) === 0xE0) {
                $i += 2;
                $result .= '?';
            } elseif (($ord & 0xF8) === 0xF0) {
                $i += 3;
                $result .= '?';
            } else {
                $result .= '?';
            }
        }
        return $result;
    }

    public static function convertEncoding(?string $text, string $from = 'UTF-8', string $to = '437', bool $translit = false): string {
        if ($text === null || trim($text) === '') return '';
        if ($from === '') return $text;

        if (stripos($from, 'UTF-8') === 0) {
            $from = 'UTF-8';
        }

        $converted = @iconv($from, $to, $text);

        if ($converted === false && $translit) {
            $converted = @iconv($from, "$to//TRANSLIT", $text);
        }

        if ($converted === false) {
            $converted = @iconv($from, "$to//TRANSLIT//IGNORE", $text);
        }

        if ($converted === false) {
            self::logDebug("Konvertierung von '$text' von '$from' nach '$to' fehlgeschlagen.");
            return $text;
        }

        return $converted;
    }

    public static function sanitizePrintable(string $input): string {
        return preg_replace('/[[:^print:]]/', ' ', $input) ?? '';
    }

    public static function removeNonAscii(string $input): string {
        return preg_replace('/[\x80-\xFF]/', '', $input) ?? '';
    }

    public static function truncate(string $text, int $maxLength, string $suffix = '...'): string {
        return mb_strlen($text) > $maxLength
            ? mb_substr($text, 0, $maxLength - mb_strlen($suffix)) . $suffix
            : $text;
    }

    public static function isAscii(string $input): bool {
        return mb_check_encoding($input, 'ASCII');
    }

    public static function htmlEntitiesToText(string $input): string {
        return html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function textToHtmlEntities(string $input): string {
        return htmlentities($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function normalizeWhitespace(string $input): string {
        return preg_replace('/\s+/', ' ', trim($input)) ?? '';
    }

    public static function toLower(string $input): string {
        return mb_strtolower($input, 'UTF-8');
    }

    public static function toUpper(string $input): string {
        return mb_strtoupper($input, 'UTF-8');
    }

    public static function removeUtf8Bom(string $input): string {
        return preg_replace('/^\xEF\xBB\xBF/', '', $input) ?? $input;
    }

    public static function detectEncoding(string $text): string|false {
        if (trim($text) === '') {
            self::logDebug("detectEncoding: leerer String übergeben");
            return false;
        }

        // Versuch über ShellChardet (sofern chardet installiert)
        try {
            $encoding = ShellChardet::detect($text);
            if ($encoding !== false) {
                self::logInfo("ShellChardet erkannte: $encoding");
                return $encoding;
            }
            self::logWarning("ShellChardet konnte keine Kodierung erkennen.");
        } catch (Throwable $e) {
            self::logWarning("ShellChardet-Ausnahme: " . $e->getMessage());
        }

        // Fallback auf mb_detect_encoding
        $fallback = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($fallback !== false) {
            self::logInfo("Fallback mit mb_detect_encoding erkannte: $fallback");
            return $fallback;
        }

        self::logError("detectEncoding: Keine Kodierung erkannt");
        return false;
    }
}