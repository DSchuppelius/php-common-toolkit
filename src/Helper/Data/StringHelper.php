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
use RuntimeException;
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

    /**
     * Prüft, ob eine Zeile ausschließlich aus Feldern besteht,
     * die mit N-fachem Enclosure umschlossen sind und per Delimiter getrennt werden.
     */
    public static function hasRepeatedEnclosure(string $line, string $delimiter = ';', string $enclosure = '"', int $enclosureRepeat = 1, bool $strict = true, ?string $started = null, ?string $closed = null): bool {
        $s = trim($line);

        if ($started !== null && str_starts_with($s, $started)) {
            $s = substr($s, strlen($started));
        }
        if ($closed !== null && str_ends_with($s, $closed)) {
            $s = substr($s, 0, -strlen($closed));
        }

        // repeat=0: keine Quotes erlaubt, mind. ein Delimiter
        if ($enclosureRepeat === 0) {
            return !str_contains($s, $enclosure) && substr_count($s, $delimiter) >= 1;
        }

        $n = strlen($s);
        if ($n === 0) return false;

        $delLen = strlen($delimiter);
        if ($delLen === 0) return false;

        $encRun = str_repeat($enclosure, $enclosureRepeat);
        $i = 0;
        $fields = 0;
        $enclosed = 0;

        while ($i < $n) {
            $fieldEnclosed = false;

            // Feldstart
            if ($i + $enclosureRepeat <= $n && substr($s, $i, $enclosureRepeat) === $encRun) {
                $fieldEnclosed = true;
                $i += $enclosureRepeat;

                // Inhalt bis schließende Enclosure-Sequenz
                while (true) {
                    if ($i >= $n) return false; // ungeschlossen

                    // repeat=1: "" als Escape
                    if ($enclosureRepeat === 1 && $s[$i] === $enclosure) {
                        if ($i + 1 < $n && $s[$i + 1] === $enclosure) {
                            $i += 2; // escaped "
                            continue;
                        }
                        // schließendes "
                        $i += 1;
                        break;
                    }

                    // repeat>1: exakte Schließsequenz
                    if (
                        $enclosureRepeat > 1 &&
                        $i + $enclosureRepeat <= $n &&
                        substr($s, $i, $enclosureRepeat) === $encRun
                    ) {
                        $i += $enclosureRepeat;
                        break;
                    }

                    $i++;
                }
            } else {
                // Unquoted Feld: bis Delimiter; Quotes im Rohtext sind invalid
                while (true) {
                    if ($i >= $n) break;
                    if ($delLen && $i + $delLen <= $n && substr($s, $i, $delLen) === $delimiter) {
                        break;
                    }
                    if ($s[$i] === $enclosure) return false; // Quote außerhalb Enclosure
                    $i++;
                }
            }

            $fields++;
            if ($fieldEnclosed) $enclosed++;

            // Nach Feld: Entweder Ende oder Delimiter
            if ($i < $n) {
                if ($delLen && $i + $delLen <= $n && substr($s, $i, $delLen) === $delimiter) {
                    $i += $delLen;
                    // wenn direkt Delimiter am Ende → kein weiteres Feld => bleibt fields<2 → false unten
                    continue;
                }
                // Unerwartetes Zeichen nach Feld (z. B. ']')
                return false;
            }
        }

        if ($fields < 2) return false;

        return $strict ? ($enclosed === $fields) : ($enclosed > 0);
    }

    /**
     * Extrahiert Felder aus einer CSV-ähnlichen Zeile, die mit wiederholten Enclosures
     * und einem Delimiter strukturiert ist.
     *
     * @param string      $line             Eingabezeile
     * @param string      $delimiter        Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure        Enclosure-Zeichen (z. B. '"')
     * @param int         $enclosureRepeat  Anzahl der zu erwartenden Wiederholungen
     * @param ?string     $started          Optionales Startzeichen der Zeile
     * @param ?string     $closed           Optionales Endzeichen der Zeile
     * @return array<string>                Array der Felder
     * @throws \RuntimeException            Wenn die Struktur inkonsistent ist
     */
    public static function extractRepeatedEnclosureFields(
        string $line,
        string $delimiter = ';',
        string $enclosure = '"',
        int $enclosureRepeat = 1,
        ?string $started = null,
        ?string $closed = null
    ): array {
        $s = rtrim($line, "\r\n");

        if ($started !== null && str_starts_with($s, $started)) {
            $s = substr($s, strlen($started));
        }
        if ($closed !== null) {
            $len = strlen($closed);
            if ($len > 0 && substr($s, -$len) === $closed) {
                $s = substr($s, 0, -$len);
            }
        }

        $n = strlen($s);
        if ($n === 0) return [];

        $delLen = strlen($delimiter);
        if ($delLen === 0) throw new \RuntimeException("Delimiter darf nicht leer sein");

        $i = 0;
        $fields = [];

        // helper: count consecutive enclosure chars starting at $pos
        $countRun = static function (string $str, int $pos, string $enc) use ($n): int {
            $k = 0;
            while ($pos + $k < strlen($str) && $str[$pos + $k] === $enc) $k++;
            return $k;
        };

        while ($i < $n) {
            $buf = '';

            if ($s[$i] === $enclosure) {
                // dynamische Öffnung: echte Runlänge erkennen (>=1)
                $openRun = $countRun($s, $i, $enclosure);
                if ($openRun < 1) throw new \RuntimeException("Interner Parserfehler");
                // Mindestanforderung beachten, aber größer zulassen
                if ($openRun < $enclosureRepeat) {
                    // kein valider Start → unquoted Feld
                    goto UNQUOTED_FIELD;
                }
                $i += $openRun;

                while (true) {
                    if ($i >= $n) throw new \RuntimeException("Unvollständig geschlossene Enclosure-Sequenz");

                    if ($openRun === 1) {
                        // Standard CSV: "" → escaped "
                        if ($s[$i] === $enclosure) {
                            if ($i + 1 < $n && $s[$i + 1] === $enclosure) {
                                $buf .= $enclosure;
                                $i += 2;
                                continue;
                            }
                            // schließendes "
                            $i += 1;
                            break;
                        }
                        $buf .= $s[$i++];
                        continue;
                    }

                    // openRun > 1: schließe nur bei exakt gleicher Runlänge
                    if ($s[$i] === $enclosure) {
                        $run = $countRun($s, $i, $enclosure);
                        if ($run >= $openRun) {
                            // schließe mit exakt openRun Quotes, Rest-Quotes sind Inhalt
                            $i += $openRun;
                            if ($run > $openRun) {
                                $buf .= str_repeat($enclosure, $run - $openRun);
                            }
                            break;
                        } else {
                            // kürzere Runlänge gilt als Inhalt
                            $buf .= str_repeat($enclosure, $run);
                            $i += $run;
                            continue;
                        }
                    }

                    $buf .= $s[$i++];
                }
            } else {
                UNQUOTED_FIELD:
                // Unquoted Feld: bis Delimiter
                while (true) {
                    if ($i >= $n) break;
                    if ($delLen && $i + $delLen <= $n && substr($s, $i, $delLen) === $delimiter) {
                        break;
                    }
                    if ($s[$i] === $enclosure) throw new \RuntimeException("Quote außerhalb Enclosure gefunden");
                    $buf .= $s[$i++];
                }
                $buf = trim($buf);
            }

            $fields[] = $buf;

            // Nach Feld: entweder Ende oder genau ein Delimiter
            if ($i < $n) {
                if ($delLen && $i + $delLen <= $n && substr($s, $i, $delLen) === $delimiter) {
                    $i += $delLen;
                    continue;
                }
                throw new \RuntimeException("Unerwartetes Zeichen nach Feld bei Index $i");
            }
        }

        return $fields;
    }
}