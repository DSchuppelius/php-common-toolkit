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

use CommonToolkit\Enums\CaseType;
use CommonToolkit\Enums\SearchMode;
use CommonToolkit\Helper\Shell\ShellChardet;
use ERRORToolkit\Traits\ErrorLog;
use Throwable;

class StringHelper {
    use ErrorLog;

    public const REGEX_ALLOWED_EXTRAS = ' .,:;!?()"„“«»‚‘’“”€$£¥+=*%&@#<>|^~{}—–…·\-\[\]\/\'' . "\t\\\\";

    /**
     * Konvertiert einen UTF-8-String in ISO-8859-1.
     *
     * @param string $string Der zu konvertierende String.
     * @return string Der konvertierte String.
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

    /**
     * Konvertiert einen String von einer Kodierung in eine andere.
     *
     * @param string|null $text Der zu konvertierende Text.
     * @param string $from Die Quellkodierung (Standard: 'UTF-8').
     * @param string $to Die Zielkodierung (Standard: '437').
     * @param bool $translit Optional: Transliterierung aktivieren (Standard: false).
     * @return string Der konvertierte Text.
     */
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

    /**
     * Entfernt nicht druckbare Zeichen aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String ohne nicht druckbare Zeichen.
     */
    public static function sanitizePrintable(string $input): string {
        return preg_replace('/[[:^print:]]/', ' ', $input) ?? '';
    }

    /**
     * Entfernt nicht-ASCII-Zeichen aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String ohne nicht-ASCII-Zeichen.
     */
    public static function removeNonAscii(string $input): string {
        return preg_replace('/[\x80-\xFF]/', '', $input) ?? '';
    }

    /**
     * Kürzt einen Text auf eine maximale Länge und fügt ein Suffix hinzu.
     *
     * @param string $text Der zu kürzende Text.
     * @param int $maxLength Die maximale Länge des Textes.
     * @param string $suffix Das Suffix, das hinzugefügt wird (Standard: '...').
     * @return string Der gekürzte Text.
     */
    public static function truncate(string $text, int $maxLength, string $suffix = '...'): string {
        return mb_strlen($text) > $maxLength
            ? mb_substr($text, 0, $maxLength - mb_strlen($suffix)) . $suffix
            : $text;
    }

    /**
     * Überprüft, ob ein String ASCII-Zeichen enthält.
     *
     * @param string $input Der zu überprüfende String.
     * @return bool True, wenn der String nur ASCII-Zeichen enthält, sonst false.
     */
    public static function isAscii(string $input): bool {
        return mb_check_encoding($input, 'ASCII');
    }

    /**
     * Konvertiert HTML-Entities in einen Text (UTF-8).
     *
     * @param string $input Der zu konvertierende Text.
     * @return string Der konvertierte Text ohne HTML-Entities.
     */
    public static function htmlEntitiesToText(string $input): string {
        return html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Konvertiert einen Text in HTML-Entities (UTF-8).
     *
     * @param string $input Der zu konvertierende Text.
     * @return string Der konvertierte Text mit HTML-Entities.
     */
    public static function textToHtmlEntities(string $input): string {
        return htmlentities($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Normalisiert Whitespace in einem String. Zeilenumbrüche und mehrere Leerzeichen werden durch ein einzelnes Leerzeichen ersetzt.
     *
     * @param string $input Der zu normalisierende String.
     * @return string Der normalisierte String.
     */
    public static function normalizeWhitespace(string $input): string {
        return preg_replace('/\s+/', ' ', trim($input)) ?? '';
    }

    /**
     * Normalisiert Whitespace in einem String. Mehrere Leerzeichen oder Tabs werden durch ein einzelnes Leerzeichen ersetzt. Zeilenumbrüche bleiben erhalten.
     *
     * @param string $input Der zu normalisierende String.
     * @return string Der normalisierte String.
     */
    public static function normalizeInlineWhitespace(string $input): string {
        return preg_replace('/[ \t]{2,}/u', ' ', $input) ?? '';
    }

    /**
     * Konvertiert einen String in Kleinbuchstaben (UTF-8).
     *
     * @param string $input Der zu konvertierende String.
     * @return string Der konvertierte String in Kleinbuchstaben.
     */
    public static function toLower(string $input): string {
        return mb_strtolower($input, 'UTF-8');
    }

    /**
     * Konvertiert einen String in Großbuchstaben (UTF-8).
     *
     * @param string $input Der zu konvertierende String.
     * @return string Der konvertierte String in Großbuchstaben.
     */
    public static function toUpper(string $input): string {
        return mb_strtoupper($input, 'UTF-8');
    }

    /**
     * Entfernt den UTF-8 BOM (Byte Order Mark) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeUtf8Bom(string $input): string {
        return preg_replace('/^\xEF\xBB\xBF/', '', $input) ?? $input;
    }

    /**
     * Entfernt den UTF-16 BOM (Byte Order Mark - Big Endian) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeUtf16BomBE(string $input): string {
        return preg_replace('/^\xFE\xFF/', '', $input) ?? $input;
    }

    /**
     * Entfernt den UTF-16 BOM (Byte Order Mark - Little Endian) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeUtf16BomLE(string $input): string {
        return preg_replace('/^\xFF\xFE/', '', $input) ?? $input;
    }

    /**
     * Entfernt den UTF-32 BOM (Byte Order Mark - Big Endian) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeUtf32BomBE(string $input): string {
        return preg_replace('/^\x00\x00\xFE\xFF/', '', $input) ?? $input;
    }

    /**
     * Entfernt den UTF-32 BOM (Byte Order Mark - Little Endian) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeUtf32BomLE(string $input): string {
        return preg_replace('/^\xFF\xFE\x00\x00/', '', $input) ?? $input;
    }

    /**
     * Entfernt den BOM (Byte Order Mark) aus einem String.
     *
     * @param string $input Der zu bereinigende String.
     * @return string Der bereinigte String.
     */
    public static function removeBom(string $input): string {
        return self::removeUtf8Bom(
            self::removeUtf16BomBE(
                self::removeUtf16BomLE(
                    self::removeUtf32BomBE(
                        self::removeUtf32BomLE($input)
                    )
                )
            )
        );
    }

    /**
     * Ermittelt die Zeichenkodierung eines Strings.
     *
     * @param string $text Der zu überprüfende Text.
     * @return string|false Die erkannte Kodierung oder false, wenn keine erkannt wurde.
     */
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

    /**
     * Überprüft, ob ein String ein bestimmtes Schlüsselwort enthält.
     *
     * @param string $haystack Der zu durchsuchende String.
     * @param array|string $keywords Ein Array oder ein einzelner String mit den Schlüsselwörtern.
     * @param SearchMode $mode Der Suchmodus (EXACT, CONTAINS, STARTS_WITH, ENDS_WITH, REGEX).
     * @param bool $caseSensitive Optional: Groß-/Kleinschreibung beachten (Standard: false).
     * @return bool True, wenn eines der Schlüsselwörter gefunden wurde, sonst false.
     */
    public static function containsKeyword(string $haystack, array|string $keywords, SearchMode $mode = SearchMode::CONTAINS, bool $caseSensitive = false): bool {
        if (!is_array($keywords)) {
            $keywords = [$keywords];
        }

        foreach ($keywords as $keyword) {
            if ($keyword === '') continue;

            $h = $caseSensitive ? $haystack : mb_strtolower($haystack);
            $k = $caseSensitive ? $keyword : mb_strtolower($keyword);

            switch ($mode) {
                case SearchMode::EXACT:
                    if (trim($h) === trim($k)) return true;
                    break;
                case SearchMode::CONTAINS:
                    if (str_contains($h, $k)) return true;
                    break;
                case SearchMode::STARTS_WITH:
                    if (str_starts_with($h, $k)) return true;
                    break;
                case SearchMode::ENDS_WITH:
                    if (str_ends_with($h, $k)) return true;
                    break;
                case SearchMode::REGEX:
                    if (@preg_match($k, $haystack) === 1) return true;
                    break;
            }
        }

        return false;
    }

    /**
     * Entfernt unerwünschte Zeichenketten aus einem String und trimmt den Rest auf die angegebenen Trim-Zeichen.
     *
     * @param string $input
     * @param array $unwanted
     * @param string $trimChars
     * @return void
     */
    public static function cleanFromList(string $input, array $unwanted, string $trimChars = " ;,"): string {
        foreach ($unwanted as $needle) {
            $input = str_replace($needle, '', $input);
        }

        return trim($input, $trimChars);
    }

    /**
     * Zerlegt einen Text in gleichmäßige Blöcke, z. B. für SEPA-Verwendungszwecke.
     *
     * @param string|null $text        Der zu zerlegende Text.
     * @param int         $blockLength Maximale Länge pro Block (Standard: 27).
     * @param int         $maxBlocks   Maximale Anzahl Blöcke (Standard: 14).
     * @param string      $padChar     Zeichen zum Auffüllen, wenn kürzer (Standard: Leerzeichen).
     * @param bool        $normalize   Optional: mehrfache Leerzeichen zusammenfassen.
     * @return string[]   Ein Array mit genau `$maxBlocks` Einträgen.
     */
    public static function splitFixedWidth(?string $text, int $blockLength = 27, int $maxBlocks = 14, string $padChar = ' ', bool $normalize = true): array {
        if ($text === null || trim($text) === '') {
            return array_fill(0, $maxBlocks, '');
        }

        if ($normalize) {
            $text = self::normalizeWhitespace($text);
        }

        $blocks = [];
        while (strlen($text) > 0 && count($blocks) < $maxBlocks) {
            $chunk = substr(str_pad($text, $blockLength, $padChar), 0, $blockLength);
            $blocks[] = $chunk;
            $text = substr($text, $blockLength);
        }

        return array_pad($blocks, $maxBlocks, '');
    }

    /**
     * Überprüft, ob ein String einem bestimmten Zeichensatz (Klein-/Groß-/Camel-/Title-Case) entspricht, wobei bestimmte zusätzliche Zeichen erlaubt sind.
     *
     * @param string $text Der zu überprüfende Text.
     * @param CaseType $case Der zu überprüfende Zeichensatz (LOWER, UPPER, CAMEL, TITLE, LOOSE_CAMEL).
     * @return bool True, wenn der Text dem angegebenen Zeichensatz entspricht, sonst false.
     */
    public static function isCaseWithExtras(string $text, CaseType $case): bool {
        $lower = 'a-zäöüß';
        $upper = 'A-ZÄÖÜẞ';

        $lines = preg_split('/\r\n|\r|\n/u', $text);
        if ($lines === false) return false;

        $lines = array_filter($lines, fn($line) => trim($line) !== '');

        foreach ($lines as $line) {
            $result = match ($case) {
                CaseType::LOWER => preg_match("/^[$lower" . self::REGEX_ALLOWED_EXTRAS . "]+$/u", $line) === 1,
                CaseType::UPPER => preg_match("/^[$upper" . self::REGEX_ALLOWED_EXTRAS . "]+$/u", $line) === 1,
                CaseType::CAMEL => self::isCamelCaseWithExtras($line),
                CaseType::TITLE => self::isTitleCase($line),
                CaseType::LOOSE_CAMEL => self::isLooseCamelCase($line),
            };

            if (!$result) return false;
        }

        return true;
    }

    /**
     * Löscht eine optionale Start- und/oder Endzeichenkette aus einer Zeile.
     *
     * @param string $line
     * @param string|null $start
     * @param string|null $end
     * @return string
     */
    public static function stripStartEnd(string $line, ?string $start = null, ?string $end = null): string {
        $result = trim($line);

        if (!empty($result)) {
            if (!empty($start) && str_starts_with($result, $start)) {
                $result = substr($result, strlen($start));
            }
            if (!empty($end) && str_ends_with($result, $end)) {
                $result = substr($result, 0, -strlen($end));
            }
            $result = trim($result);
        }
        return $result;
    }

    private static function isCamelCaseWithExtras(string $text): bool {
        return preg_match("/^[a-zäöüß]+(?:[A-ZÄÖÜẞ][a-zäöüß]+)+(?:[" . self::REGEX_ALLOWED_EXTRAS . "]*)$/u", $text) === 1;
    }

    private static function isLooseCamelCase(string $text): bool {
        return preg_match("/^[a-zA-ZäöüÄÖÜß]+(?:[A-ZÄÖÜẞ][a-zäöüß]+)*(?:[" . self::REGEX_ALLOWED_EXTRAS . "]*)$/u", $text) === 1;
    }

    private static function isTitleCase(string $text): bool {
        $words = preg_split('/\s+/', trim($text));
        if (!$words) return false;

        foreach ($words as $word) {
            if (!preg_match("/^[A-ZÄÖÜ][a-zäöüß]*(?:[" . self::REGEX_ALLOWED_EXTRAS . "])?$/u", $word)) {
                return false;
            }
        }

        return true;
    }
}