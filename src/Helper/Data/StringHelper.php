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
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\SearchMode;
use CommonToolkit\Helper\Shell\ShellChardet;
use DateTimeImmutable;
use ERRORToolkit\Traits\ErrorLog;
use Throwable;

class StringHelper {
    use ErrorLog;

    public const REGEX_ALLOWED_EXTRAS = ' .,:;!?()"„“«»‚‘’“”€$£¥+=*%&@#<>|^~{}—–…·\-\[\]\/\'' . "\t\\\\";

    // BOM (Byte Order Mark) Konstanten
    public const BOM_UTF8     = "\xEF\xBB\xBF";
    public const BOM_UTF16_BE = "\xFE\xFF";
    public const BOM_UTF16_LE = "\xFF\xFE";
    public const BOM_UTF32_BE = "\x00\x00\xFE\xFF";
    public const BOM_UTF32_LE = "\xFF\xFE\x00\x00";

    /**
     * Prüft ob ein String null oder leer ist.
     *
     * @param string|null $value Der zu prüfende Wert.
     * @return bool True wenn null oder leerer String.
     */
    public static function isNullOrEmpty(?string $value): bool {
        return $value === null || $value === '';
    }

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
            return self::logDebugAndReturn($text, "Konvertierung von '$text' von '$from' nach '$to' fehlgeschlagen.");
        }

        return $converted;
    }

    /**
     * Normalisiert Encoding-Namen für konsistente Verwendung mit iconv.
     *
     * Behandelt verschiedene Schreibweisen für DOS-Zeichensätze:
     * - CP437, IBM437, 437 -> CP437
     * - CP850, IBM850, 850 -> CP850
     * - CP1252, Windows-1252 -> Windows-1252
     *
     * @param string $encoding Der Encoding-Name
     * @return string Der normalisierte Encoding-Name
     */
    public static function normalizeEncodingName(string $encoding): string {
        $encoding = strtoupper(trim($encoding));

        return match ($encoding) {
            // DOS-Zeichensätze (US)
            'CP437', 'IBM437', '437', 'DOS', 'OEM-US', 'OEM437' => 'CP437',
            // DOS-Zeichensätze (Multilingual Latin I)
            'CP850', 'IBM850', '850', 'DOS-LATIN1' => 'CP850',
            // DOS-Zeichensätze (Latin 2)
            'CP852', 'IBM852', '852' => 'CP852',
            // Windows-Zeichensätze
            'CP1252', 'WINDOWS-1252', 'WIN-1252' => 'Windows-1252',
            'CP1250', 'WINDOWS-1250', 'WIN-1250' => 'Windows-1250',
            // ISO-Zeichensätze
            'LATIN1', 'LATIN-1', 'ISO-8859-1', 'ISO8859-1' => 'ISO-8859-1',
            'LATIN9', 'LATIN-9', 'ISO-8859-15', 'ISO8859-15' => 'ISO-8859-15',
            // UTF-8 Varianten
            'UTF8', 'UTF-8' => 'UTF-8',
            // Sonst unverändert zurückgeben
            default => $encoding,
        };
    }

    /**
     * Prüft, ob ein Encoding von mb_convert_encoding unterstützt wird.
     *
     * @param string $encoding Der Encoding-Name
     * @return bool True wenn mb_* das Encoding unterstützt
     */
    public static function isMbEncodingSupported(string $encoding): bool {
        $encoding = strtoupper(trim($encoding));
        // DOS-Codepages werden von mb_* nicht unterstützt
        $unsupported = ['CP437', 'IBM437', '437', 'CP850', 'IBM850', '850', 'CP852', 'IBM852', '852', 'CP865', 'IBM865', '865'];
        return !in_array($encoding, $unsupported, true);
    }

    /**
     * Konvertiert Text von einem Encoding nach UTF-8.
     * Verwendet iconv für Encodings die mb_* nicht unterstützt.
     *
     * @param string $text Der zu konvertierende Text
     * @param string $fromEncoding Das Quell-Encoding
     * @return string Der konvertierte Text in UTF-8
     */
    public static function convertToUtf8(string $text, string $fromEncoding): string {
        if ($text === '') return '';

        $fromEncoding = self::normalizeEncodingName($fromEncoding);

        if ($fromEncoding === 'UTF-8' || $fromEncoding === 'ASCII') {
            return $text;
        }

        // Für Encodings die mb_* nicht unterstützt, iconv verwenden
        if (!self::isMbEncodingSupported($fromEncoding)) {
            $result = @iconv($fromEncoding, 'UTF-8//TRANSLIT//IGNORE', $text);
            if ($result !== false) {
                return $result;
            }
            // Fallback ohne TRANSLIT
            $result = @iconv($fromEncoding, 'UTF-8//IGNORE', $text);
            if ($result !== false) {
                return $result;
            }
            self::logWarning("iconv-Konvertierung von $fromEncoding nach UTF-8 fehlgeschlagen");
            return $text;
        }

        // mb_convert_encoding für unterstützte Encodings
        $result = @mb_convert_encoding($text, 'UTF-8', $fromEncoding);
        return $result !== false ? $result : $text;
    }

    /**
     * Erweiterte Heuristik zur Erkennung von Legacy-Encodings.
     *
     * Diese Methode analysiert Byte-Muster, um zwischen verschiedenen
     * problematischen Encodings zu unterscheiden:
     *
     * - CP850/CP437 (DOS): Deutsche Umlaute an anderen Positionen als Windows
     * - Windows-1252: Typografische Zeichen im 0x80-0x9F Bereich
     * - MacRoman: Komplett andere Byte-Zuordnungen (chardet erkennt dies oft fälschlich)
     * - ISO-8859-15: Wie ISO-8859-1, aber mit €-Zeichen an Position 0xA4
     *
     * @param string $data Die zu analysierenden Rohdaten (nicht UTF-8!)
     * @param string|null $chardetHint Optional: Was chardet/uchardet erkannt hat (für Korrektur)
     * @return string|null Das erkannte Encoding oder null wenn nicht unterscheidbar
     */
    public static function detectLegacyEncoding(string $data, ?string $chardetHint = null): ?string {
        if ($data === '') return null;

        // Wenn der Text gültiges UTF-8 ist, brauchen wir keine Unterscheidung
        if (mb_check_encoding($data, 'UTF-8') && preg_match('//u', $data)) {
            // Prüfe ob es wirklich Nicht-ASCII enthält
            if (!preg_match('/[\x80-\xFF]/', $data)) {
                return null; // Reines ASCII
            }
            // Gültiges UTF-8 mit High-Bytes → UTF-8
            return 'UTF-8';
        }

        $len = strlen($data);
        $scores = [
            'CP850' => 0,
            'CP437' => 0,
            'CP852' => 0,
            'Windows-1252' => 0,
            'MacRoman' => 0,
            'ISO-8859-1' => 0,
            'ISO-8859-15' => 0,
        ];

        // Byte-Mappings für verschiedene Encodings
        // =========================================

        // CP850-spezifische Bytes (anders als CP437)
        // CP850 hat mehr europäische Buchstaben, CP437 mehr Grafikzeichen
        // In CP850: á=0xA0, í=0xA1, ó=0xA2, ú=0xA3, ñ=0xA4, Ñ=0xA5
        // In CP437: á=0xA0, í=0xA1, ó=0xA2, ú=0xA3, ñ=0xA4, Ñ=0xA5 (gleich!)
        // Unterschied: CP437 0xB0-0xDF sind Box-Drawing, CP850 hat dort Buchstaben
        $cp437BoxDrawing = [
            0xB0,
            0xB1,
            0xB2,
            0xB3,
            0xB4,
            0xB5,
            0xB6,
            0xB7,
            0xB8,
            0xB9,
            0xBA,
            0xBB,
            0xBC,
            0xBD,
            0xBE,
            0xBF,
            0xC0,
            0xC1,
            0xC2,
            0xC3,
            0xC4,
            0xC5,
            0xC6,
            0xC7,
            0xC8,
            0xC9,
            0xCA,
            0xCB,
            0xCC,
            0xCD,
            0xCE,
            0xCF,
            0xD0,
            0xD1,
            0xD2,
            0xD3,
            0xD4,
            0xD5,
            0xD6,
            0xD7,
            0xD8,
            0xD9,
            0xDA,
            0xDB,
            0xDC,
            0xDD,
            0xDE,
            0xDF
        ];

        // CP852 (Mitteleuropa): andere Umlaut-Positionen
        // ä=0x84 (gleich!), ö=0x94 (gleich!), ü=0x81 (gleich!)
        // Aber: ą=0xA5, ć=0x86, ę=0xA9, ł=0x88, ń=0xE4, ó=0xA2, ś=0x9C, ź=0xAB, ż=0xAF
        // ACHTUNG: 0xE4 ist NICHT in dieser Liste, weil es in ISO-8859-1 = ä ist!
        // 0xE4 muss kontextabhängig unterschieden werden (ä im Deutschen vs ń im Polnischen)
        $cp852Specific = [0x86, 0x88, 0x9C, 0xA5, 0xA9, 0xAB, 0xAF];

        // Konflikt-Bytes: in ISO-8859-1 sind das normale Buchstaben, in CP852 polnische
        // 0xE4 = ä (ISO-8859-1) vs ń (CP852)
        // Diese brauchen Kontextanalyse für deutsche vs polnische Texte
        $conflictBytes = [0xE4];

        // Windows-1252 typografische Zeichen (0x80-0x9F)
        // €=0x80, ‚=0x82, ƒ=0x83, „=0x84, …=0x85, †=0x86, ‡=0x87, ˆ=0x88, ‰=0x89,
        // Š=0x8A, ‹=0x8B, Œ=0x8C, Ž=0x8E, '=0x91, '=0x92, "=0x93, "=0x94,
        // •=0x95, –=0x96, —=0x97, ˜=0x98, ™=0x99, š=0x9A, ›=0x9B, œ=0x9C, ž=0x9E, Ÿ=0x9F
        $win1252Typographic = [
            0x80,
            0x82,
            0x83,
            0x85,
            0x86,
            0x87,
            0x88,
            0x89,
            0x8A,
            0x8B,
            0x8C,
            0x91,
            0x92,
            0x93,
            0x95,
            0x96,
            0x97,
            0x98,
            0x9B,
            0x9C,
            0x9E,
            0x9F
        ];

        // Analyse der Bytes
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($data[$i]);

            // Nur erweiterten Bereich analysieren
            if ($byte < 0x80) continue;

            $before = $i > 0 ? ord($data[$i - 1]) : 0;
            $after = $i < $len - 1 ? ord($data[$i + 1]) : 0;

            // Prüfe verschiedene Kontexte
            $beforeIsLetter = ($before >= 0x41 && $before <= 0x5A) || ($before >= 0x61 && $before <= 0x7A);
            $afterIsLetter = ($after >= 0x41 && $after <= 0x5A) || ($after >= 0x61 && $after <= 0x7A);
            $beforeIsDigit = ($before >= 0x30 && $before <= 0x39);
            $afterIsDigit = ($after >= 0x30 && $after <= 0x39);

            $isLetterContext = $beforeIsLetter || $afterIsLetter;
            $isDigitContext = $beforeIsDigit || $afterIsDigit;

            // ===== DOS Codepages (CP850/CP437) =====
            // Eindeutige DOS-Umlaute (in Windows-1252 undefiniert oder typografisch)
            if (in_array($byte, [0x81, 0x8E, 0x9A, 0xE1], true)) {
                // 0x81=ü, 0x8E=Ä, 0x9A=Ü, 0xE1=ß
                // In Windows-1252: 0x81=undef, 0x8E=Ž, 0x9A=š, 0xE1=á
                if ($isLetterContext) {
                    $scores['CP850'] += 3;
                    $scores['CP437'] += 3;
                }
            }

            // Mehrdeutige Bytes: ä=0x84, ö=0x94, Ö=0x99
            // Windows-1252: „=0x84, "=0x94, ™=0x99
            if (in_array($byte, [0x84, 0x94, 0x99], true)) {
                if ($isLetterContext) {
                    $scores['CP850'] += 2;
                    $scores['CP437'] += 2;
                } else {
                    $scores['Windows-1252'] += 2;
                }
            }

            // Box-Drawing Zeichen (CP437-typisch, selten in Texten)
            if (in_array($byte, $cp437BoxDrawing, true)) {
                // Wenn es wie Text aussieht (viele Box-Drawing hintereinander = Tabelle)
                // ist es eher CP437, sonst eher CP850
                $consecutiveBox = 0;
                for ($j = max(0, $i - 2); $j < min($len, $i + 3); $j++) {
                    if (in_array(ord($data[$j]), $cp437BoxDrawing, true)) {
                        $consecutiveBox++;
                    }
                }
                if ($consecutiveBox >= 3) {
                    $scores['CP437'] += 2;
                } else {
                    // Einzelnes Box-Drawing Zeichen → eher CP850 (dort sind es Buchstaben)
                    $scores['CP850'] += 1;
                }
            }

            // ===== CP852 (Mitteleuropa) =====
            if (in_array($byte, $cp852Specific, true)) {
                $scores['CP852'] += 2;
            }

            // ===== Konflikt-Bytes: 0xE4 = ä (ISO-8859-1) vs ń (CP852) =====
            // In deutschen Texten ist 0xE4 fast immer ä (Latin-1)
            // In polnischen Texten wäre es ń (CP852)
            // Heuristik: Im Buchstabenkontext mit deutschen Mustern → Latin-1
            if (in_array($byte, $conflictBytes, true)) {
                if ($isLetterContext) {
                    // Prüfe ob andere polnische CP852-Zeichen vorhanden sind
                    $hasOtherCp852 = false;
                    for ($j = 0; $j < $len && !$hasOtherCp852; $j++) {
                        if (in_array(ord($data[$j]), $cp852Specific, true)) {
                            $hasOtherCp852 = true;
                        }
                    }
                    if ($hasOtherCp852) {
                        // Andere polnische Zeichen vorhanden → wahrscheinlich CP852
                        $scores['CP852'] += 2;
                    } else {
                        // Keine anderen polnischen Zeichen → wahrscheinlich ä (Latin-1)
                        $scores['ISO-8859-1'] += 3;
                        $scores['Windows-1252'] += 3;
                    }
                }
            }

            // ===== Windows-1252 typografische Zeichen =====
            if (in_array($byte, $win1252Typographic, true)) {
                // Diese sind in DOS-Codepages Umlaute/Buchstaben
                if (!$isLetterContext) {
                    $scores['Windows-1252'] += 3;
                }
            }

            // ===== MacRoman =====
            // 0x80=Ä (in Win-1252: €), 0x85=Ö (in Win-1252: …)
            // 0x86=Ü (in Win-1252: †), 0x8A=ä, 0x9A=ö, 0x9F=ü
            if ($byte === 0x80) {
                // 0x80 = Ä in MacRoman, € in Windows-1252
                // Buchstabenkontext (z.B. "Äpfel") → MacRoman
                // Zahlenkontext (z.B. "€100") → Windows-1252
                if ($isLetterContext && !$isDigitContext) {
                    $scores['MacRoman'] += 3;
                } elseif ($isDigitContext && !$isLetterContext) {
                    $scores['Windows-1252'] += 3;
                }
            }
            if (in_array($byte, [0x85, 0x86], true) && $isLetterContext) {
                // 0x85=Ö, 0x86=Ü in MacRoman
                // In Windows-1252: …, †
                $scores['MacRoman'] += 2;
            }
            if (in_array($byte, [0x8A, 0x9A, 0x9F], true)) {
                // MacRoman: ä, ö, ü
                // In CP850: è, Ù, ÿ
                // Im Buchstabenkontext mit deutschen Wörtern → MacRoman
                if ($isLetterContext) {
                    $scores['MacRoman'] += 2;
                }
            }
            if ($byte === 0xA7 && $isLetterContext) {
                // 0xA7 = ß in MacRoman, § in Latin-1/Windows-1252
                $scores['MacRoman'] += 2;
            }

            // ===== ISO-8859-1 vs ISO-8859-15 =====
            // 0xA4: ¤ vs € - wenn es nach Währung aussieht → ISO-8859-15
            if ($byte === 0xA4) {
                // Prüfe ob es im Kontext von Zahlen steht (Preis)
                $numContext = ($before >= 0x30 && $before <= 0x39) || ($after >= 0x30 && $after <= 0x39);
                if ($numContext) {
                    $scores['ISO-8859-15'] += 3;
                } else {
                    $scores['ISO-8859-1'] += 1;
                }
            }
            if (in_array($byte, [0xA6, 0xA8, 0xB4, 0xB8], true)) {
                // Š, š, Ž, ž in ISO-8859-15 (tschechisch/slowakisch)
                // ¦, ¨, ´, ¸ in ISO-8859-1 (seltenere Zeichen)
                if ($isLetterContext) {
                    $scores['ISO-8859-15'] += 2;
                } else {
                    $scores['ISO-8859-1'] += 1;
                }
            }

            // ===== Allgemeine Latin-1/Windows-1252 Zeichen (0xC0-0xFF) =====
            if ($byte >= 0xC0 && $byte <= 0xFF && !in_array($byte, [0xE1], true)) {
                // Diese sind in ISO-8859-1/Windows-1252 akzentuierte Buchstaben
                // In CP850 sind einige davon Grafikzeichen
                $scores['ISO-8859-1'] += 1;
                $scores['Windows-1252'] += 1;
            }
        }

        // Wenn chardet MacRoman erkannt hat, aber wir DOS-Muster sehen
        if ($chardetHint !== null) {
            $hint = strtoupper($chardetHint);
            if (str_contains($hint, 'MAC') && ($scores['CP850'] > $scores['MacRoman'] || $scores['CP437'] > $scores['MacRoman'])) {
                // chardet hat sich wahrscheinlich geirrt
                self::logDebug("Chardet erkannte $chardetHint, aber DOS-Muster überwiegen");
            }
        }

        // Ermittle das Encoding mit dem höchsten Score
        arsort($scores);
        $topEncodings = array_slice($scores, 0, 2, true);
        $topKeys = array_keys($topEncodings);
        $topValues = array_values($topEncodings);

        // Kein klarer Gewinner?
        if ($topValues[0] === 0) {
            return null;
        }

        // Klarer Gewinner (mindestens doppelt so viele Punkte)?
        if ($topValues[0] > $topValues[1] * 2) {
            return $topKeys[0];
        }

        // CP850 und CP437 gleichauf? → CP850 bevorzugen (häufiger für deutsche Texte)
        if (in_array('CP850', $topKeys, true) && in_array('CP437', $topKeys, true)) {
            return 'CP850';
        }

        // ISO-8859-1 und ISO-8859-15 gleichauf? → ISO-8859-15 bevorzugen (hat €)
        if (in_array('ISO-8859-1', $topKeys, true) && in_array('ISO-8859-15', $topKeys, true)) {
            return 'ISO-8859-15';
        }

        // ISO-8859-1 und Windows-1252 gleichauf? → Windows-1252 bevorzugen (Superset)
        if (in_array('ISO-8859-1', $topKeys, true) && in_array('Windows-1252', $topKeys, true)) {
            return 'Windows-1252';
        }

        // Ansonsten den Gewinner zurückgeben
        return $topKeys[0];
    }

    /**
     * Heuristik zur Unterscheidung von CP850/CP437 vs Windows-1252 für deutsche Texte.
     *
     * @deprecated Verwende detectLegacyEncoding() für umfassendere Erkennung
     * @param string $data Die zu analysierenden Rohdaten (nicht UTF-8!)
     * @return string|null 'CP850', 'Windows-1252', oder null wenn nicht unterscheidbar
     */
    public static function detectDosVsWindowsEncoding(string $data): ?string {
        $result = self::detectLegacyEncoding($data);

        // Nur relevante Ergebnisse zurückgeben
        if ($result === null) return null;

        return match ($result) {
            'CP850', 'CP437' => 'CP850',
            'Windows-1252', 'ISO-8859-1', 'ISO-8859-15' => 'Windows-1252',
            'MacRoman' => 'Windows-1252', // MacRoman als Windows-1252 behandeln für Kompatibilität
            default => null,
        };
    }

    /**
     * Gibt das BOM (Byte Order Mark) für ein bestimmtes Encoding zurück.
     *
     * @param string $encoding Das Encoding
     * @return string|null Das BOM als Byte-String oder null wenn kein BOM für dieses Encoding existiert
     */
    public static function getBomForEncoding(string $encoding): ?string {
        $encoding = strtoupper($encoding);

        return match ($encoding) {
            'UTF-8'                         => self::BOM_UTF8,
            'UTF-16BE', 'UTF-16-BE'         => self::BOM_UTF16_BE,
            'UTF-16LE', 'UTF-16-LE'         => self::BOM_UTF16_LE,
            'UTF-16'                        => self::BOM_UTF16_LE,    // Little-Endian als Default
            'UTF-32BE', 'UTF-32-BE'         => self::BOM_UTF32_BE,
            'UTF-32LE', 'UTF-32-LE'         => self::BOM_UTF32_LE,
            'UTF-32'                        => self::BOM_UTF32_LE,    // Little-Endian als Default
            default                         => null,
        };
    }

    /**
     * Prüft ob ein String mit einem BOM (Byte Order Mark) beginnt und gibt das erkannte Encoding zurück.
     *
     * @param string $content Der zu prüfende String
     * @return string|null Das erkannte Encoding oder null wenn kein BOM gefunden wurde
     */
    public static function detectBomEncoding(string $content): ?string {
        if (str_starts_with($content, self::BOM_UTF8)) {
            return 'UTF-8';
        }
        if (str_starts_with($content, self::BOM_UTF32_LE)) {
            return 'UTF-32LE';
        }
        if (str_starts_with($content, self::BOM_UTF32_BE)) {
            return 'UTF-32BE';
        }
        if (str_starts_with($content, self::BOM_UTF16_LE)) {
            return 'UTF-16LE';
        }
        if (str_starts_with($content, self::BOM_UTF16_BE)) {
            return 'UTF-16BE';
        }
        return null;
    }

    /**
     * Entfernt ein BOM (Byte Order Mark) vom Anfang eines Strings.
     *
     * @param string $content Der zu bereinigende String
     * @return string Der String ohne BOM
     */
    public static function stripBom(string $content): string {
        // UTF-32 zuerst prüfen (4 Bytes)
        if (str_starts_with($content, self::BOM_UTF32_LE) || str_starts_with($content, self::BOM_UTF32_BE)) {
            return substr($content, 4);
        }
        // UTF-8 (3 Bytes)
        if (str_starts_with($content, self::BOM_UTF8)) {
            return substr($content, 3);
        }
        // UTF-16 (2 Bytes)
        if (str_starts_with($content, self::BOM_UTF16_LE) || str_starts_with($content, self::BOM_UTF16_BE)) {
            return substr($content, 2);
        }
        return $content;
    }

    /**
     * Entfernt nicht druckbare Zeichen aus einem String.
     *
     * @param string|null $input Der zu bereinigende String (null wird als leerer String behandelt).
     * @return string Der bereinigte String ohne nicht druckbare Zeichen.
     */
    public static function sanitizePrintable(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return preg_replace('/[[:^print:]]/', ' ', $input) ?? '';
    }

    /**
     * Entfernt nicht-ASCII-Zeichen aus einem String.
     *
     * @param string|null $input Der zu bereinigende String (null wird als leerer String behandelt).
     * @return string Der bereinigte String ohne nicht-ASCII-Zeichen.
     */
    public static function removeNonAscii(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return preg_replace('/[\x80-\xFF]/', '', $input) ?? '';
    }

    /**
     * Kürzt einen Text auf eine maximale Länge und fügt optional ein Suffix hinzu.
     *
     * Für einfaches Kürzen ohne Suffix: truncate($text, $length, '')
     *
     * @param string|null $text Der zu kürzende Text (null wird als leerer String behandelt).
     * @param int $maxLength Die maximale Länge des Textes (inkl. Suffix).
     * @param string $suffix Das Suffix, das hinzugefügt wird (Standard: '...'). Leerer String für kein Suffix.
     * @return string Der gekürzte Text mit Suffix, oder der Original-Text wenn kürzer.
     */
    public static function truncate(?string $text, int $maxLength, string $suffix = '...', bool $trim = false): string {
        if (self::isNullOrEmpty($text)) return '';
        if ($trim) $text = trim($text);

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
     * Der String wird zusätzlich getrimmt. Im Gegensatz zu collapseWhitespace()
     * wird hier auch am Anfang und Ende normalisiert.
     *
     * @param string|null $input Der zu normalisierende String (null wird als leerer String behandelt).
     * @return string Der normalisierte und getrimmte String.
     * @see collapseWhitespace() Für Kollabieren ohne Trimmen.
     * @see normalizeInlineWhitespace() Für Kollabieren unter Erhalt von Zeilenumbrüchen.
     */
    public static function normalizeWhitespace(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return preg_replace('/\s+/', ' ', trim($input)) ?? '';
    }

    /**
     * Normalisiert Whitespace in einem String. Mehrere Leerzeichen oder Tabs werden durch ein einzelnes Leerzeichen ersetzt. Zeilenumbrüche bleiben erhalten.
     *
     * @param string|null $input Der zu normalisierende String (null wird als leerer String behandelt).
     * @return string Der normalisierte String.
     */
    public static function normalizeInlineWhitespace(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return preg_replace('/[ \t]{2,}/u', ' ', $input) ?? '';
    }

    /**
     * Konvertiert einen String in Kleinbuchstaben (UTF-8).
     *
     * @param string|null $input Der zu konvertierende String (null wird als leerer String behandelt).
     * @return string Der konvertierte String in Kleinbuchstaben.
     */
    public static function toLower(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return mb_strtolower($input, 'UTF-8');
    }

    /**
     * Konvertiert einen String in Großbuchstaben (UTF-8).
     *
     * @param string|null $input Der zu konvertierende String (null wird als leerer String behandelt).
     * @return string Der konvertierte String in Großbuchstaben.
     */
    public static function toUpper(?string $input): string {
        if (self::isNullOrEmpty($input)) return '';
        return mb_strtoupper($input, 'UTF-8');
    }



    /**
     * Ermittelt die Zeichenkodierung eines Strings.
     *
     * @param string $text Der zu überprüfende Text.
     * @return string|false Die erkannte Kodierung oder false, wenn keine erkannt wurde.
     */
    public static function detectEncoding(string $text): string|false {
        if (trim($text) === '') {
            return self::logDebugAndReturn(false, "detectEncoding: leerer String übergeben");
        }

        // Versuch über ShellChardet (sofern chardet installiert)
        try {
            $encoding = ShellChardet::detect($text);
            if ($encoding !== false) {
                return self::logDebugAndReturn($encoding, "ShellChardet erkannte: $encoding");
            }
            self::logWarning("ShellChardet konnte keine Kodierung erkennen.");
        } catch (Throwable $e) {
            self::logWarning("ShellChardet-Ausnahme: " . $e->getMessage());
        }

        // Fallback auf mb_detect_encoding
        $fallback = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($fallback !== false) {
            return self::logDebugAndReturn($fallback, "Fallback mit mb_detect_encoding erkannte: $fallback");
        }

        return self::logErrorAndReturn(false, "detectEncoding: Keine Kodierung erkannt");
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

            $found = match ($mode) {
                SearchMode::EXACT       => trim($h) === trim($k),
                SearchMode::CONTAINS    => str_contains($h, $k),
                SearchMode::STARTS_WITH => str_starts_with($h, $k),
                SearchMode::ENDS_WITH   => str_ends_with($h, $k),
                SearchMode::REGEX       => @preg_match($k, $haystack) === 1,
            };

            if ($found) return true;
        }

        return false;
    }

    /**
     * Entfernt unerwünschte Zeichenketten aus einem String und trimmt den Rest auf die angegebenen Trim-Zeichen.
     *
     * @param string $input Der zu bereinigende String.
     * @param array<string> $unwanted Array der zu entfernenden Zeichenketten.
     * @param string $trimChars Zeichen, die am Anfang und Ende getrimmt werden.
     * @return string Der bereinigte String.
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
        while (mb_strlen($text) > 0 && count($blocks) < $maxBlocks) {
            $chunk = self::pad($text, $blockLength, $padChar, STR_PAD_RIGHT);
            $chunk = mb_substr($chunk, 0, $blockLength);
            $blocks[] = $chunk;
            $text = mb_substr($text, $blockLength);
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

    /**
     * Ermittelt den typisierten Wert eines Strings.
     * Priorität: Unix-Timestamp -> Integer -> Float -> Boolean -> DateTime -> String
     *
     * @param string $value Der zu typisierende String
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return mixed Der typisierte Wert
     */
    public static function parseToTypedValue(string $value, CountryCode $country = CountryCode::Germany): mixed {
        if ($value === '') {
            return $value;
        }

        $trimmed = trim($value);

        // Spezialfall: Unix Timestamps (10 Stellen) als DateTime erkennen
        if (ctype_digit($trimmed) && strlen($trimmed) === 10) {
            $timestamp = (int) $trimmed;
            if ($timestamp > 946684800 && $timestamp < 2147483647) { // 2000-2038
                $dateTime = DateTimeImmutable::createFromFormat('U', $trimmed);
                if ($dateTime !== false) {
                    return $dateTime;
                }
            }
        }

        // Integer prüfen (aber nicht bei mehrstelligen Zahlen mit führenden Nullen)
        if (filter_var($trimmed, FILTER_VALIDATE_INT) !== false) {
            // Führende Nullen bei mehrstelligen Zahlen beibehalten
            if (strlen($trimmed) > 1 && str_starts_with($trimmed, '0') && $trimmed !== '0') {
                return $trimmed; // Als String beibehalten
            }
            return (int) $trimmed;
        }

        // Float prüfen (deutsche Komma normalisieren)
        $normalized = str_replace(',', '.', $trimmed);
        if (filter_var($normalized, FILTER_VALIDATE_FLOAT) !== false) {
            // Führende Nullen bei ganzen Zahlen beibehalten (aber nicht bei Dezimalzahlen)
            if (strlen($trimmed) > 1 && str_starts_with($trimmed, '0') && !str_contains($trimmed, '.') && !str_contains($trimmed, ',')) {
                return $trimmed; // Als String beibehalten
            }
            return (float) $normalized;
        }

        // Boolean prüfen
        $lower = strtolower($trimmed);
        if (in_array($lower, ['true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($lower, ['false', 'no', 'off'], true)) {
            return false;
        }

        // DateTime prüfen
        $dateTime = DateHelper::parseDateTime($trimmed, $country);
        if ($dateTime !== null) {
            return $dateTime;
        }

        // Fallback: String
        return $value;
    }

    /**
     * Versucht einen String als DateTime zu parsen.
     *
     * Delegiert an DateHelper::parseDateTime() für konsistente Datums-Verarbeitung.
     *
     * @param string $value Der zu parsende String
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return DateTimeImmutable|null Das DateTime-Objekt oder null
     * @see DateHelper::parseDateTime() Für die vollständige Implementierung.
     */
    public static function parseDateTime(string $value, CountryCode $country = CountryCode::Germany): ?DateTimeImmutable {
        return DateHelper::parseDateTime($value, $country);
    }

    /**
     * Prüft, ob ein String eine gültige Ganzzahl ist.
     */
    public static function isInt(string $value): bool {
        return filter_var(trim($value), FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Prüft, ob ein String eine gültige Fließkommazahl ist.
     */
    public static function isFloat(string $value): bool {
        $normalized = str_replace(',', '.', trim($value));
        return filter_var($normalized, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Prüft, ob ein String ein gültiger Boolean ist.
     */
    public static function isBool(string $value): bool {
        $lower = strtolower(trim($value));
        return in_array($lower, ['true', 'false', 'yes', 'no', 'on', 'off'], true);
    }

    /**
     * Prüft, ob ein String ein gültiges Datum/Zeit ist.
     */
    public static function isDateTime(string $value, ?string $format = null): bool {
        return DateHelper::isDateTime($value, $format);
    }

    /**
     * Erzeugt einen URL-freundlichen Slug aus einem String.
     *
     * @param string $text Der zu konvertierende Text.
     * @param string $separator Trennzeichen (Standard: '-').
     * @param bool $lowercase In Kleinbuchstaben konvertieren (Standard: true).
     * @return string Der erzeugte Slug.
     */
    public static function slugify(string $text, string $separator = '-', bool $lowercase = true): string {
        // Transliteration: Umlaute und Sonderzeichen ersetzen
        $replacements = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            'ñ' => 'n',
            'ç' => 'c',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Å' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ý' => 'Y',
            'Ñ' => 'N',
            'Ç' => 'C',
            '&' => 'und',
            '@' => 'at',
        ];

        $text = strtr($text, $replacements);

        // Nicht-alphanumerische Zeichen durch Separator ersetzen
        $text = preg_replace('/[^a-zA-Z0-9]+/', $separator, $text);

        // Mehrfache Separatoren entfernen
        $text = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $text);

        // Separator am Anfang und Ende entfernen
        $text = trim($text, $separator);

        return $lowercase ? strtolower($text) : $text;
    }

    /**
     * Konvertiert CamelCase zu snake_case.
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @return string Der konvertierte Text.
     */
    public static function camelToSnake(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $text);
        return strtolower($result);
    }

    /**
     * Konvertiert snake_case zu camelCase.
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @param bool $pascalCase PascalCase statt camelCase (Standard: false).
     * @return string Der konvertierte Text.
     */
    public static function snakeToCamel(?string $text, bool $pascalCase = false): string {
        if (self::isNullOrEmpty($text)) return '';
        $result = str_replace('_', '', ucwords($text, '_'));
        return $pascalCase ? $result : lcfirst($result);
    }

    /**
     * Konvertiert kebab-case zu camelCase.
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @param bool $pascalCase PascalCase statt camelCase (Standard: false).
     * @return string Der konvertierte Text.
     */
    public static function kebabToCamel(?string $text, bool $pascalCase = false): string {
        if (self::isNullOrEmpty($text)) return '';
        $result = str_replace('-', '', ucwords($text, '-'));
        return $pascalCase ? $result : lcfirst($result);
    }

    /**
     * Konvertiert camelCase zu kebab-case.
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @return string Der konvertierte Text.
     */
    public static function camelToKebab(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $text);
        return strtolower($result);
    }

    /**
     * Maskiert Teile eines Strings (z.B. für Kreditkartennummern, E-Mails).
     *
     * @param string|null $text Der zu maskierende Text (null wird als leerer String behandelt).
     * @param int $visibleStart Anzahl sichtbarer Zeichen am Anfang (Standard: 0).
     * @param int $visibleEnd Anzahl sichtbarer Zeichen am Ende (Standard: 4).
     * @param string $maskChar Das Maskierungszeichen (Standard: '*').
     * @return string Der maskierte Text.
     */
    public static function mask(?string $text, int $visibleStart = 0, int $visibleEnd = 4, string $maskChar = '*'): string {
        if (self::isNullOrEmpty($text)) return '';
        $length = mb_strlen($text);

        if ($length <= $visibleStart + $visibleEnd) {
            return $text;
        }

        $start = mb_substr($text, 0, $visibleStart);
        $end = $visibleEnd > 0 ? mb_substr($text, -$visibleEnd) : '';
        $maskLength = $length - $visibleStart - $visibleEnd;

        return $start . str_repeat($maskChar, $maskLength) . $end;
    }

    /**
     * Teilt einen Text in ein Array von gleichmäßigen Blöcken auf.
     *
     * @param string|null $text Der zu teilende Text (null wird als leerer String behandelt).
     * @param int $count Die maximale Anzahl der Blöcke (Standard: 14).
     * @param int $length Die maximale Länge pro Block (Standard: 27).
     * @param bool $trimEmpty Leere Blöcke am Ende entfernen (Standard: true).
     * @return string[] Ein Array mit den Textblöcken.
     *
     * @see splitFixedWidth() Für SEPA-konforme Blöcke mit Padding auf feste Länge.
     */
    public static function splitTextToArray(?string $text, int $count = 14, int $length = 27, bool $trimEmpty = true): array {
        if ($text === null || trim($text) === '') {
            return $trimEmpty ? [] : array_fill(0, $count, '');
        }

        $text = trim($text);
        $parts = [];

        for ($i = 0; $i < $count; $i++) {
            $part = mb_substr($text, $i * $length, $length);
            if ($trimEmpty && $part === '') {
                break;
            }
            $parts[] = $part;
        }

        return $parts;
    }

    /**
     * Maskiert eine E-Mail-Adresse (z.B. j***@example.com).
     *
     * @param string|null $email Die zu maskierende E-Mail (null wird als leerer String behandelt).
     * @param string $maskChar Das Maskierungszeichen (Standard: '*').
     * @return string Die maskierte E-Mail.
     */
    public static function maskEmail(?string $email, string $maskChar = '*'): string {
        if (self::isNullOrEmpty($email)) return '';
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return self::mask($email, 1, 1, $maskChar);
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = mb_strlen($local) > 2
            ? mb_substr($local, 0, 1) . str_repeat($maskChar, mb_strlen($local) - 1)
            : $local;

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Zählt die Wörter in einem String.
     *
     * Verwendet einfache Leerzeichen-basierte Worttrennung.
     *
     * @param string $text Der zu zählende Text.
     * @param string $locale Reserviert für zukünftige locale-basierte Worttrennung (derzeit unbenutzt).
     * @return int Anzahl der Wörter.
     */
    public static function wordCount(string $text, string $locale = 'de_DE'): int {
        // Whitespace normalisieren
        $text = preg_replace('/\s+/', ' ', trim($text));

        if ($text === '') {
            return 0;
        }

        // Einfache Wortanzahl basierend auf Leerzeichen
        return count(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Prüft, ob ein String mit einem bestimmten Teilstring beginnt.
     *
     * @param string $haystack Der zu prüfende String.
     * @param string $needle Der gesuchte Anfang.
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: true).
     * @return bool True wenn der String mit needle beginnt.
     */
    public static function startsWith(string $haystack, string $needle, bool $caseSensitive = true): bool {
        if (!$caseSensitive) {
            return str_starts_with(mb_strtolower($haystack), mb_strtolower($needle));
        }
        return str_starts_with($haystack, $needle);
    }

    /**
     * Prüft, ob ein String mit einem bestimmten Teilstring endet.
     *
     * @param string $haystack Der zu prüfende String.
     * @param string $needle Das gesuchte Ende.
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: true).
     * @return bool True wenn der String mit needle endet.
     */
    public static function endsWith(string $haystack, string $needle, bool $caseSensitive = true): bool {
        if (!$caseSensitive) {
            return str_ends_with(mb_strtolower($haystack), mb_strtolower($needle));
        }
        return str_ends_with($haystack, $needle);
    }

    /**
     * Extrahiert einen Textauszug mit Kontext um ein Keyword.
     *
     * @param string $text Der vollständige Text.
     * @param string $keyword Das zu findende Keyword.
     * @param int $contextLength Anzahl Zeichen Kontext vor/nach dem Keyword (Standard: 50).
     * @param string $ellipsis Auslassungszeichen (Standard: '...').
     * @return string|null Der Auszug oder null wenn nicht gefunden.
     */
    public static function excerpt(string $text, string $keyword, int $contextLength = 50, string $ellipsis = '...'): ?string {
        $pos = mb_stripos($text, $keyword);
        if ($pos === false) {
            return null;
        }

        $start = max(0, $pos - $contextLength);
        $length = mb_strlen($keyword) + ($contextLength * 2);

        $excerpt = mb_substr($text, $start, $length);

        // Whitespace normalisieren
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);

        // Ellipsis hinzufügen
        $prefix = $start > 0 ? $ellipsis : '';
        $suffix = ($start + $length) < mb_strlen($text) ? $ellipsis : '';

        return $prefix . trim($excerpt) . $suffix;
    }

    /**
     * Wandelt den ersten Buchstaben jedes Wortes in Großbuchstaben um (Title Case).
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @return string Der konvertierte Text.
     */
    public static function titleCase(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Entfernt mehrfache aufeinanderfolgende Leerzeichen.
     *
     * Im Gegensatz zu normalizeWhitespace() wird der String NICHT getrimmt.
     * Alle Whitespace-Typen (Leerzeichen, Tabs, Zeilenumbrüche) werden zu einem Leerzeichen.
     *
     * @param string|null $text Der zu bereinigende Text (null wird als leerer String behandelt).
     * @return string Der bereinigte Text mit einfachen Leerzeichen.
     * @see normalizeWhitespace() Für Kollabieren mit Trimmen.
     * @see normalizeInlineWhitespace() Für Kollabieren unter Erhalt von Zeilenumbrüchen.
     */
    public static function collapseWhitespace(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * Kehrt einen UTF-8 String um.
     *
     * @param string|null $text Der umzukehrende Text (null wird als leerer String behandelt).
     * @return string Der umgekehrte Text.
     */
    public static function reverse(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        $chars = mb_str_split($text);
        return implode('', array_reverse($chars));
    }

    /**
     * Prüft ob ein String nur Buchstaben enthält.
     *
     * @param string $text Der zu prüfende Text.
     * @return bool True wenn nur Buchstaben enthalten sind.
     */
    public static function isAlpha(string $text): bool {
        return preg_match('/^[\p{L}]+$/u', $text) === 1;
    }

    /**
     * Prüft ob ein String nur Buchstaben und Zahlen enthält.
     *
     * @param string $text Der zu prüfende Text.
     * @return bool True wenn nur alphanumerische Zeichen enthalten sind.
     */
    public static function isAlphanumeric(string $text): bool {
        return preg_match('/^[\p{L}\p{N}]+$/u', $text) === 1;
    }

    /**
     * Prüft ob ein String nur Ziffern enthält.
     *
     * @param string $text Der zu prüfende Text.
     * @return bool True wenn nur Ziffern enthalten sind.
     */
    public static function isNumeric(string $text): bool {
        return ctype_digit($text);
    }

    /**
     * Zählt das Vorkommen eines Teilstrings.
     *
     * @param string $haystack Der zu durchsuchende String.
     * @param string $needle Der zu zählende Teilstring.
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: true).
     * @return int Anzahl der Vorkommen.
     */
    public static function countOccurrences(string $haystack, string $needle, bool $caseSensitive = true): int {
        if (!$caseSensitive) {
            return mb_substr_count(mb_strtolower($haystack), mb_strtolower($needle));
        }
        return mb_substr_count($haystack, $needle);
    }

    /**
     * Füllt einen String auf eine bestimmte Länge auf (multibyte-safe).
     *
     * @param string $text Der zu füllende Text.
     * @param int $length Die Ziellänge.
     * @param string $padString Das Füllzeichen (Standard: ' ').
     * @param int $padType STR_PAD_RIGHT, STR_PAD_LEFT oder STR_PAD_BOTH.
     * @return string Der aufgefüllte Text.
     */
    public static function pad(string $text, int $length, string $padString = ' ', int $padType = STR_PAD_RIGHT): string {
        $textLength = mb_strlen($text);
        $padLength = mb_strlen($padString);

        if ($textLength >= $length || $padLength === 0) {
            return $text;
        }

        $diff = $length - $textLength;

        switch ($padType) {
            case STR_PAD_LEFT:
                $padding = mb_substr(str_repeat($padString, (int) ceil($diff / $padLength)), 0, $diff);
                return $padding . $text;

            case STR_PAD_BOTH:
                $leftPad = (int) floor($diff / 2);
                $rightPad = $diff - $leftPad;
                $leftPadding = mb_substr(str_repeat($padString, (int) ceil($leftPad / $padLength)), 0, $leftPad);
                $rightPadding = mb_substr(str_repeat($padString, (int) ceil($rightPad / $padLength)), 0, $rightPad);
                return $leftPadding . $text . $rightPadding;

            case STR_PAD_RIGHT:
            default:
                $padding = mb_substr(str_repeat($padString, (int) ceil($diff / $padLength)), 0, $diff);
                return $text . $padding;
        }
    }

    /**
     * Kürzt einen String auf eine maximale Länge (multibyte-safe).
     *
     * @param string|null $text Der zu kürzende Text.
     * @param int $maxLength Die maximale Länge.
     * @return string Der gekürzte Text ohne Suffix.
     * @deprecated Verwende stattdessen truncate($text, $maxLength, '') - gleiche Funktionalität.
     */
    public static function limit(?string $text, int $maxLength): string {
        return self::truncate($text, $maxLength, '');
    }

    /**
     * Entfernt alle Ziffern aus einem String.
     *
     * @param string|null $text Der zu bereinigende Text (null wird als leerer String behandelt).
     * @return string Der Text ohne Ziffern.
     */
    public static function removeDigits(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        return preg_replace('/\d/', '', $text);
    }

    /**
     * Extrahiert alle Ziffern aus einem String.
     *
     * @param string|null $text Der zu durchsuchende Text (null wird als leerer String behandelt).
     * @return string Nur die Ziffern.
     */
    public static function extractDigits(?string $text): string {
        if (self::isNullOrEmpty($text)) return '';
        return preg_replace('/[^\d]/', '', $text);
    }

    /**
     * Wandelt Zeilenumbrüche in ein einheitliches Format um.
     *
     * @param string|null $text Der zu konvertierende Text (null wird als leerer String behandelt).
     * @param string $lineEnding Das gewünschte Zeilenende (Standard: PHP_EOL).
     * @return string Der konvertierte Text.
     */
    public static function normalizeLineEndings(?string $text, string $lineEnding = PHP_EOL): string {
        if (self::isNullOrEmpty($text)) return '';
        // Erst alle auf \n normalisieren, dann zum Ziel konvertieren
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return str_replace("\n", $lineEnding, $text);
    }

    /**
     * Bricht einen Text auf eine maximale Zeilenlänge um.
     *
     * Im Gegensatz zu wordwrap() ist diese Methode multibyte-safe.
     *
     * @param string|null $text Der umzubrechende Text (null wird als leerer String behandelt).
     * @param int $width Maximale Zeilenlänge (Standard: 75).
     * @param string $break Zeilenumbruch-Zeichen (Standard: "\n").
     * @param bool $cut Ob Wörter länger als $width geschnitten werden (Standard: false).
     * @return string Der umgebrochene Text.
     */
    public static function wrap(?string $text, int $width = 75, string $break = "\n", bool $cut = false): string {
        if (self::isNullOrEmpty($text)) return '';
        if ($width <= 0) return $text;

        $lines = [];
        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $currentLine = '';

        foreach ($words as $word) {
            // Leerzeichen-Token
            if (trim($word) === '') {
                if ($currentLine !== '' && mb_strlen($currentLine) + mb_strlen($word) <= $width) {
                    $currentLine .= $word;
                }
                continue;
            }

            $wordLength = mb_strlen($word);
            $lineLength = mb_strlen($currentLine);

            // Wort passt in aktuelle Zeile
            if ($lineLength + $wordLength <= $width) {
                $currentLine .= $word;
                continue;
            }

            // Aktuelle Zeile abschließen wenn nicht leer
            if ($currentLine !== '') {
                $lines[] = rtrim($currentLine);
                $currentLine = '';
            }

            // Wort ist zu lang für eine Zeile
            if ($cut && $wordLength > $width) {
                while (mb_strlen($word) > $width) {
                    $lines[] = mb_substr($word, 0, $width);
                    $word = mb_substr($word, $width);
                }
                $currentLine = $word;
            } else {
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = rtrim($currentLine);
        }

        return implode($break, $lines);
    }

    /**
     * Extrahiert den Text zwischen zwei Markern.
     *
     * @param string $text Der zu durchsuchende Text.
     * @param string $start Der Startmarker.
     * @param string $end Der Endmarker.
     * @param bool $includeMarkers Ob die Marker im Ergebnis enthalten sein sollen (Standard: false).
     * @return string|null Der gefundene Text oder null wenn nicht gefunden.
     */
    public static function between(string $text, string $start, string $end, bool $includeMarkers = false): ?string {
        $startPos = mb_strpos($text, $start);
        if ($startPos === false) {
            return null;
        }

        $startPos += $includeMarkers ? 0 : mb_strlen($start);
        $endPos = mb_strpos($text, $end, $startPos + ($includeMarkers ? mb_strlen($start) : 0));

        if ($endPos === false) {
            return null;
        }

        $length = $endPos - $startPos + ($includeMarkers ? mb_strlen($end) : 0);
        return mb_substr($text, $startPos, $length);
    }

    /**
     * Prüft, ob der String mindestens eines der angegebenen Schlüsselwörter enthält.
     *
     * @param string $haystack Der zu durchsuchende String.
     * @param array<string> $needles Array der zu suchenden Teilstrings.
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: true).
     * @return bool True wenn mindestens ein Schlüsselwort gefunden wurde.
     */
    public static function containsAny(string $haystack, array $needles, bool $caseSensitive = true): bool {
        $h = $caseSensitive ? $haystack : mb_strtolower($haystack);

        foreach ($needles as $needle) {
            if ($needle === '') continue;
            $n = $caseSensitive ? $needle : mb_strtolower($needle);
            if (str_contains($h, $n)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft, ob der String alle angegebenen Schlüsselwörter enthält.
     *
     * @param string $haystack Der zu durchsuchende String.
     * @param array<string> $needles Array der zu suchenden Teilstrings.
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: true).
     * @return bool True wenn alle Schlüsselwörter gefunden wurden.
     */
    public static function containsAll(string $haystack, array $needles, bool $caseSensitive = true): bool {
        if (empty($needles)) {
            return true;
        }

        $h = $caseSensitive ? $haystack : mb_strtolower($haystack);

        foreach ($needles as $needle) {
            if ($needle === '') continue;
            $n = $caseSensitive ? $needle : mb_strtolower($needle);
            if (!str_contains($h, $n)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Entfernt HTML-Tags aus einem String und normalisiert Whitespace.
     *
     * @param string|null $html Der zu bereinigende HTML-String (null wird als leerer String behandelt).
     * @param string|null $allowedTags Erlaubte Tags im strip_tags Format (Standard: null = keine).
     * @return string Der bereinigte Text.
     */
    public static function stripHtml(?string $html, ?string $allowedTags = null): string {
        if (self::isNullOrEmpty($html)) return '';
        $text = strip_tags($html, $allowedTags);
        return self::normalizeWhitespace($text);
    }

    /**
     * Wiederholt einen String n-mal.
     *
     * @param string|null $text Der zu wiederholende Text (null wird als leerer String behandelt).
     * @param int $times Anzahl der Wiederholungen.
     * @param string $separator Trennzeichen zwischen den Wiederholungen (Standard: '').
     * @return string Der wiederholte Text.
     */
    public static function repeat(?string $text, int $times, string $separator = ''): string {
        if (self::isNullOrEmpty($text)) return '';
        if ($times <= 0) return '';
        if ($separator === '') {
            return str_repeat($text, $times);
        }
        return implode($separator, array_fill(0, $times, $text));
    }
}