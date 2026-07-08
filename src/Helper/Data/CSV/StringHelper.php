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

namespace CommonToolkit\Helper\Data\CSV;

use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Enums\Common\CSV\QuotingStyle;
use CommonToolkit\Helper\Data\{StringHelper as BaseStringHelper, Validator};
use RuntimeException;
use Throwable;

final class StringHelper extends BaseStringHelper {
    /** Standard-Kandidaten für die Trennzeichen-Erkennung (Reihenfolge = Tie-Priorität). */
    public const DEFAULT_DELIMITERS = [';', ',', "\t", '|'];

    /**
     * Erkennt das Spaltentrennzeichen eines CSV-Inhalts (string-basiert) anhand der
     * häufigsten Vorkommen pro Zeile.
     *
     * Zentrale Implementierung für alle string-basierten Delimiter-Erkennungen
     * (vgl. dateibasiert {@see \CommonToolkit\Helper\FileSystem\FileTypes\CsvFile::detectDelimiter()}).
     *
     * @param string   $content         CSV-Inhalt (eine oder mehrere Zeilen)
     * @param string[] $candidates      Kandidaten-Trennzeichen; Array-Reihenfolge entscheidet bei Gleichstand
     * @param int      $sampleLines     Anzahl der zu prüfenden Kopfzeilen (<=0 = alle)
     * @param string   $default         Rückgabe, wenn kein Kandidat die Schwelle erreicht ('' = "kein Treffer")
     * @param bool     $requirePerLine  Verlangt mindestens ein Vorkommen pro geprüfter Zeile (statt insgesamt ≥1)
     * @return string                   Erkanntes Trennzeichen oder $default
     */
    public static function detectDelimiter(
        string $content,
        array $candidates = self::DEFAULT_DELIMITERS,
        int $sampleLines = 1,
        string $default = ';',
        bool $requirePerLine = false
    ): string {
        if ($candidates === []) {
            return $default;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        if ($sampleLines > 0) {
            $lines = array_slice($lines, 0, $sampleLines);
        }

        $counts = array_fill_keys($candidates, 0);
        foreach ($lines as $line) {
            foreach ($candidates as $delimiter) {
                $counts[$delimiter] += substr_count($line, $delimiter);
            }
        }

        arsort($counts);
        // $candidates ist hier garantiert nicht leer (Early-Return oben) → kein null-Key.
        $best = array_key_first($counts);

        // Schwelle: ein Vorkommen pro Zeile (requirePerLine) bzw. mindestens eines insgesamt.
        $threshold = $requirePerLine ? max(1, count($lines)) : 1;

        return $counts[$best] >= $threshold ? (string) $best : $default;
    }

    /**
     * Prüft, ob ein String von Enclosure-Zeichen umschlossen ist.
     *
     * @param string $value         Der zu prüfende String
     * @param string $enclosure     Enclosure-Zeichen (z. B. '"')
     * @param int    $minRepeat     Minimale Anzahl der Enclosure-Wiederholungen (Standard: 1)
     * @return bool                 True, wenn der String von Enclosures umschlossen ist
     */
    public static function hasStringEnclosure(string $value, string $enclosure = '"', int $minRepeat = 1): bool {
        if ($value === '' || $enclosure === '' || $minRepeat < 1) {
            return false;
        }

        $encLen = strlen($enclosure);
        $valLen = strlen($value);

        // Mindestlänge: 2 * minRepeat * enclosure-Länge
        if ($valLen < 2 * $minRepeat * $encLen) {
            return false;
        }

        $expected = str_repeat($enclosure, $minRepeat);

        return str_starts_with($value, $expected) && str_ends_with($value, $expected);
    }

    /**
     * Erkennt die Anzahl der wiederholten Enclosures in einer CSV-Zeile.
     *
     * @param string      $line       Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $delimiter  Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure  Enclosure-Zeichen (z. B. '"')
     * @param string|null $started    Optional: Startzeichen der Zeile
     * @param string|null $closed     Optional: Endzeichen der Zeile
     * @param bool        $strict     Ob der strikte (min) oder non-strikte (max) Wert zurückgegeben werden soll
     * @return int                    Die erkannte Anzahl der wiederholten Enclosures
     */
    public static function detectCSVEnclosureRepeat(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null, bool $strict = true): int {
        $s = self::stripStartEnd($line, $started, $closed);
        if (empty($s)) {
            return 0;
        }

        $repeats = [];

        foreach (DataLine::fromString($s, $delimiter, $enclosure)->getFields() as $field) {
            $repeats[] = $field->getEnclosureRepeat();
        }

        return $strict ? min($repeats) : max($repeats);
    }

    /**
     * Generiert mögliche leere Feldwerte mit wiederholten Enclosures.
     *
     * @param string      $line          Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $delimiter     Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure     Enclosure-Zeichen (z. B. '"')
     * @param string|null $started       Optional: Startzeichen der Zeile
     * @param string|null $closed        Optional: Endzeichen der Zeile
     * @param bool        $withDelimiter Ob die generierten Werte mit Delimiter zurückgegeben werden sollen
     * @return array<string>             Array der generierten leeren Feldwerte
     */
    private static function genEmptyValuesFromCSVString(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null, bool $withDelimiter = true, ?int $strictRepeat = null, ?int $nonStrictRepeat = null): array {
        $strictRepeat ??= self::detectCSVEnclosureRepeat($line, $delimiter, $enclosure, $started, $closed, true);
        $nonStrictRepeat ??= self::detectCSVEnclosureRepeat($line, $delimiter, $enclosure, $started, $closed, false);

        $repeats = array_unique(
            array_filter([$strictRepeat, $nonStrictRepeat], fn ($v) => $v > 0)
        );

        // Wenn keine Quotes gefunden → Standard leer
        if (empty($repeats)) {
            return $withDelimiter ? [$delimiter . $delimiter] : [''];
        }

        $values = [];

        foreach ($repeats as $r) {
            $empty = str_repeat($enclosure, $r * 2);
            if ($withDelimiter) {
                $values[] = $delimiter . $empty . $delimiter;
            } else {
                $values[] = $empty;
            }
        }

        // Immer die einfache Leerfeld-Variante anhängen
        if ($withDelimiter) {
            array_unshift($values, $delimiter . $delimiter);
        } else {
            array_unshift($values, '');
        }

        $result = array_values(array_unique($values));
        usort($result, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $result;
    }

    /**
     * Normalisiert eine CSV-Zeile, indem wiederholte Enclosures reduziert werden.
     *
     * @param string $line      Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string $delimiter Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure Enclosure-Zeichen (z. B. '"')
     * @return string           Normalisierte CSV-Zeile
     */
    private static function normalizeRepeatedEnclosures(string $line, string $delimiter = ',', string $enclosure = '"', ?int $maxRepeat = null, ?int $minRepeat = null): string {
        if ($line === '' || $enclosure === '') {
            return $line;
        }

        $max = $maxRepeat ?? self::detectCSVEnclosureRepeat($line, $delimiter, $enclosure, null, null, false);
        if ($max < 2) {
            return $line;
        }

        $min = $minRepeat ?? self::detectCSVEnclosureRepeat($line, $delimiter, $enclosure, null, null, true);

        $with = self::genEmptyValuesFromCSVString($line, $delimiter, $enclosure, null, null, true, $min, $max);

        // 1) Leere Felder an Feldgrenzen auf doppeltes $enclosure normalisieren
        foreach ($with as $v) {
            if ($v === $delimiter . $delimiter) {
                continue;
            }
            if (str_contains($line, $v)) {
                while (true) {
                    $newLine = str_replace($v, $delimiter . $delimiter, $line);
                    if ($newLine === $line) {
                        break;
                    }
                    $line = $newLine;
                }
            }
        }

        // Nicht-leere Felder an Feldgrenzen auf einfaches $enclosure reduzieren
        for ($r = $max; $r >= 2; $r--) {
            $qq = str_repeat($enclosure, $r);
            $line = str_replace($delimiter . $qq, $delimiter . $enclosure, $line);
            $line = str_replace($qq . $delimiter, $enclosure . $delimiter, $line);

            if (str_starts_with($line, $qq)) {
                $line = substr_replace($line, $enclosure, 0, strlen($qq));
            }
            if (str_ends_with($line, $qq)) {
                $line = substr_replace($line, $enclosure, -strlen($qq));
            }
        }

        return $line;
    }

    /**
     * Prüft, ob eine CSV-Zeile Felder mit einer bestimmten Anzahl von
     * wiederholten Enclosures enthält.
     *
     * @param string      $line       Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $delimiter  Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure  Enclosure-Zeichen (z. B. '"')
     * @param int         $repeat     Anzahl der zu prüfenden Wiederholungen
     * @param bool        $strict     Ob alle gequoteten Felder die exakte Anzahl haben müssen
     * @param string|null $started    Optional: Startzeichen der Zeile
     * @param string|null $closed     Optional: Endzeichen der Zeile
     * @return bool                   True, wenn Felder mit der angegebenen Anzahl von wiederholten Enclosures gefunden wurden, sonst false
     */
    public static function hasRepeatedEnclosure(string $line, string $delimiter = ',', string $enclosure = '"', int $repeat = 1, bool $strict = true, ?string $started = null, ?string $closed = null): bool {
        $s = self::stripStartEnd($line, $started, $closed);
        if ($s === '' || $enclosure === '') {
            return false;
        }

        // --- Keine Quotes erlaubt ---
        if ($repeat === 0) {
            return !str_contains($s, $enclosure)
                && substr_count($s, $delimiter) >= 1;
        }

        try {
            $CSVDataLine = DataLine::fromString($s, $delimiter, $enclosure);
        } catch (Throwable) {
            return false; // Ungültige CSV-Struktur
        }

        // --- Range aus CSVDataLine übernehmen ---
        [$minRepeat, $maxRepeat] = $CSVDataLine->getEnclosureRepeatRange(true);
        $quotedCount = $CSVDataLine->countQuotedFields();

        if ($quotedCount === 0) {
            return false; // keine gequoteten Felder vorhanden
        }

        if ($strict) {
            // alle Felder gleich gequotet
            return $minRepeat === $repeat && $maxRepeat === $repeat;
        }

        // non-strict: mind. ein Feld mit entsprechendem oder höherem Repeat
        return $maxRepeat >= $repeat;
    }

    /**
     * Prüft, ob eine CSV-Zeile komplett geparst werden kann.
     *
     * @param string      $line       Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $delimiter  Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure  Enclosure-Zeichen (z. B. '"')
     * @param string|null $started    Optional: Startzeichen der Zeile
     * @param string|null $closed     Optional: Endzeichen der Zeile
     * @return bool                   True, wenn die Zeile komplett geparst werden kann, sonst false
     */
    public static function canParseCompleteCSVDataLine(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null): bool {
        // Start/End entfernen, aber originalen Vergleich sichern
        $trimmed = self::stripStartEnd($line, $started, $closed);
        if (empty($trimmed)) {
            return false;
        }

        try {
            // Versuch, die Zeile über DataLine zu parsen
            $CSVDataLine = DataLine::fromString($trimmed, $delimiter, $enclosure);
            $rebuilt = $CSVDataLine->toString($delimiter, $enclosure);

            // Wenn gleich → valide CSV-Struktur
            if ($trimmed === $rebuilt) {
                return true;
            }

            // Prüfe auf Excel-Exponentialformat-Manipulation (z.B. "3,21001E+13" → "32100100000000")
            if (self::hasExcelExponentialNotation($trimmed)) {
                self::logWarning('CSV enthält Excel-Exponentialformat - Daten wurden möglicherweise durch Excel manipuliert: ' . $trimmed);
                return true;
            }

            // Repeat-Info aus bereits geparseter DataLine nutzen (kein erneutes Parsen)
            [$minRepeat, $maxRepeat] = $CSVDataLine->getEnclosureRepeatRange(true);

            // Falls normalisierte Variante (z. B. durch doppelte Quotes) übereinstimmt
            $normalizedInput2 = self::normalizeRepeatedEnclosures($trimmed, $delimiter, $enclosure, $maxRepeat, $minRepeat);
            $normalizedRebuilt2 = self::normalizeRepeatedEnclosures($rebuilt, $delimiter, $enclosure);

            return $normalizedInput2 === $normalizedRebuilt2;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Prüft, ob eine Zeichenkette Excel-Exponentialnotation enthält.
     * Excel konvertiert große Zahlen automatisch in wissenschaftliche Notation (z.B. 3,21001E+13).
     * Dies führt oft zu Datenverlust bei Referenznummern, IBANs, etc.
     *
     * @param string $value Die zu prüfende Zeichenkette
     * @return bool True, wenn Exponentialnotation gefunden wurde
     */
    public static function hasExcelExponentialNotation(string $value): bool {
        // Deutsche Notation: 3,21001E+13 oder 3,21001E-13
        // Englische Notation: 3.21001E+13 oder 3.21001E-13
        return (bool) preg_match('/\d+[,\.]\d+E[+-]\d+/i', $value);
    }

    /**
     * Prüft, ob eine CSV-Zeile Felder mit Zeilenumbrüchen enthält.
     *
     * @param string $csv                 Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string $delimiter           Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure           Enclosure-Zeichen (z. B. '"')
     * @param bool   $allowWithoutQuotes  Optional: erkenne Multiline auch ohne Quotes (unsicher)
     * @return bool                       True, wenn Multiline-Felder erkannt wurden, sonst false
     */
    public static function hasMultilineFields(string $csv, string $delimiter = ',', string $enclosure = '"', bool $allowWithoutQuotes = false): bool {
        // Prüfe auf Multiline innerhalb von Enclosures durch Zählung statt Regex
        $escaped = preg_replace('/' . preg_quote($enclosure, '/') . '{2}/', '', $csv);
        $quoteCount = substr_count($escaped, $enclosure);

        // ungerade Quote-Anzahl ⇒ unvollständig ⇒ Multiline
        if ($quoteCount % 2 !== 0) {
            return true;
        }

        // Optional: erkenne Multiline auch ohne Quotes (unsicher)
        return $allowWithoutQuotes && str_contains($csv, "\n");
    }

    /**
     * Teilt eine CSV-Zeichenkette in logische Zeilen auf, die
     * Felder mit Zeilenumbrüchen berücksichtigen.
     *
     * @param string $csv       Eingabe-CSV-Zeichenkette
     * @param string $delimiter Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure Enclosure-Zeichen (z. B. '"')
     * @return array<string>    Array der logischen CSV-Zeilen
     */
    public static function splitCsvByLogicalLine(string $csv, string $delimiter = ',', string $enclosure = '"'): array {
        $lines = preg_split('/\r\n|\r|\n/', $csv);
        $result = [];
        $buffer = '';

        foreach ($lines as $line) {
            $buffer .= ($buffer !== '' ? "\n" : '') . $line;

            if (!self::hasMultilineFields($buffer, $delimiter, $enclosure)) {
                $result[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $result[] = $buffer;
        }

        return $result;
    }

    /**
     * Parst eine CSV-Zeile in Felder.
     *
     * @param string $line          Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string $delimiter     Das Trennzeichen.
     * @param string $enclosure     Das Einschlusszeichen.
     * @return array                Der Array der Felder.
     * @throws RuntimeException     Wenn die CSV-Zeile ungültig ist.
     */
    public static function parseLineToFields(string $line, string $delimiter, string $enclosure): array {
        $result = [];
        $current = '';
        $inQuotes = false;
        $quoteRun = 0;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            $next = $line[$i + 1] ?? '';
            $prev = $i > 0 ? $line[$i - 1] : '';

            $current .= $char;

            if ($char === $enclosure) {
                $quoteRun++;
                if (!$inQuotes && ($prev === '' || $prev === $delimiter)) {
                    $inQuotes = true;
                    $quoteRun = 1;
                    continue;
                }
                if ($inQuotes && ($next === $delimiter || $next === '' || $next === "\r" || $next === "\n")) {
                    $inQuotes = false;
                    $quoteRun = 0;
                    continue;
                }
                // Einzelnes Quote DIREKT nach einem Delimiter im gequoteten Feld,
                // das KEIN doppeltes Quote ("") einleitet → ungültig (z. B. '"A,"B"').
                // Ein doppeltes Quote ("",  next === enclosure) ist hingegen erlaubt:
                // RFC4180-escaptes Quote bzw. doppelt-gewrappte Felder ("",""…) sowie
                // Exporte, die einfach- und doppelt-gequotete Felder mischen (PayPal).
                if ($inQuotes && $prev === $delimiter && $next !== $enclosure) {
                    self::logErrorAndThrow(RuntimeException::class, 'Ungültige CSV-Zeile – Delimiter nach Quote-Ende ohne neues Feld');
                }
            }

            if (!$inQuotes && $char === $enclosure && ($prev !== $delimiter && $prev !== '')) {
                $errormsg = sprintf(
                    'Ungültige CSV-Zeile – Unerwartetes Enclosure bei Index %d (%s)',
                    $i,
                    substr($line, max(0, $i - 10), 20)
                );
                self::logErrorAndThrow(RuntimeException::class, $errormsg);
            }

            if ($char === $delimiter && !$inQuotes) {
                $result[] = substr($current, 0, -1);
                $current = '';
                continue;
            }
        }

        if ($inQuotes) {
            self::logErrorAndThrow(RuntimeException::class, 'Ungültige CSV-Zeile – Feld nicht geschlossen (fehlendes Enclosure am Ende)');
        }

        if ($current !== '' || str_ends_with($line, $delimiter)) {
            $result[] = $current;
        }

        return $result;
    }

    /**
     * Extrahiert Felder aus einer CSV-ähnlichen Zeile, die mit wiederholten Enclosures
     * und einem Delimiter strukturiert ist.
     *
     * @param array|string $lines                Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string       $delimiter            Spaltentrennzeichen (z. B. "," oder ";")
     * @param string       $enclosure            Enclosure-Zeichen (z. B. '"')
     * @param ?string      $started              Optionales Startzeichen der Zeile
     * @param ?string      $closed               Optionales Endzeichen der Zeile
     * @param string       $multiLineReplacement Optional: Ersetzung für Zeilenumbrüche in gequoteten Feldern (Standard: " ")
     * @return array<string>                     Array der Felder
     * @throws RuntimeException                  Wenn die Struktur inkonsistent ist
     */
    public static function extractFields(array|string $lines, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null, string $multiLineReplacement = " "): array {
        $raw = is_array($lines) ? implode("\n", $lines) : (string) $lines;
        $s = self::stripStartEnd($raw, $started, $closed);

        if (empty(trim($s))) {
            return [];
        }

        // DataLine einmal parsen und Enclosure-Repeat-Info daraus extrahieren
        $dataLine = DataLine::fromString($s, $delimiter, $enclosure);
        [$minRepeat, $maxRepeat] = $dataLine->getEnclosureRepeatRange(true);

        // Nur normalisieren, wenn es wiederholte Enclosures gibt (>=2)
        if ($maxRepeat >= 2) {
            $s = self::normalizeRepeatedEnclosures($s, $delimiter, $enclosure, $maxRepeat, $minRepeat);
            // Nach Normalisierung neu parsen
            $dataLine = DataLine::fromString($s, $delimiter, $enclosure);
        }

        // Felder direkt aus DataLine extrahieren (kein redundantes parseCSVLine)
        $fields = array_map(fn ($f) => $f->getValue(), $dataLine->getFields());

        // Multiline-Replacement in gequoteten Feldern anwenden
        if (self::hasMultilineFields($s, $delimiter, $enclosure)) {
            $nlRe = "/\r\n|\r|\n/u";
            foreach ($dataLine->getFields() as $i => $field) {
                if ($field->isQuoted() && ($fields[$i] ?? '') !== '') {
                    $fields[$i] = preg_replace($nlRe, $multiLineReplacement, $fields[$i]) ?? $fields[$i];
                }
            }
        }

        return $fields;
    }

    /**
     * Wandelt eine Zeile (Feld-Array oder rohe CSV-Zeile) in ein Feld-Array um.
     *
     * @param array<int|string, scalar|null>|string $row Feld-Array oder rohe CSV-Zeile
     * @param string|null $delimiter Trennzeichen für String-Eingaben (null = automatisch erkennen)
     * @param string      $enclosure Enclosure-Zeichen für String-Eingaben
     * @return array<int, string>|null Feld-Array oder null, wenn die Zeile nicht parsebar ist
     */
    private static function rowToFields(array|string $row, ?string $delimiter, string $enclosure): ?array {
        if (is_array($row)) {
            return array_map(static fn ($value) => (string) ($value ?? ''), array_values($row));
        }

        try {
            $delimiter ??= self::detectDelimiter($row);
            return self::extractFields($row, $delimiter, $enclosure);
        } catch (Throwable $e) {
            self::logDebug("Zeile konnte nicht als CSV geparst werden: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prüft eine CSV-Zeile gegen ein Struktur-/Typmuster (z. B. "ti__d").
     *
     * Jedes Zeichen des Musters beschreibt den erwarteten Typ einer Spalte
     * ({@see Validator::validateBySymbol()}, Symbole: {@see Validator::STRUCTURE_SYMBOLS}):
     * d = Datum, D = optionales Datum, b = Betrag, B = Bankleitzahl, k = Kontonummer,
     * i = IBAN, I = maskierte IBAN, c = BIC, t = Text, u = alphanumerisch (Großschreibung),
     * _ = beliebig.
     *
     * Ein an ein Symbol angehängtes '?' erlaubt zusätzlich leere Werte
     * (z. B. 'b?' = Betrag oder leer); 'D' ist das Alt-Kürzel für 'd?'.
     * Die erwartete Spaltenanzahl bemisst sich an der Token-Anzahl des Musters.
     *
     * @param array<int|string, scalar|null>|string $row Zeile als Feld-Array oder rohe CSV-Zeile
     * @param string      $pattern   Das Strukturmuster (z. B. "dbkti" oder "d_b?b?")
     * @param int|null    $columns   Erwartete Spaltenanzahl (optional)
     * @param bool        $strict    Spaltenanzahl muss exakt der Token-Anzahl entsprechen (Standard: true)
     * @param string|null $delimiter Trennzeichen für String-Eingaben (null = automatisch erkennen)
     * @param string      $enclosure Enclosure-Zeichen für String-Eingaben
     */
    public static function checkStructure(array|string $row, string $pattern, ?int $columns = null, bool $strict = true, ?string $delimiter = null, string $enclosure = '"'): bool {
        if ($pattern === '') {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: leeres Strukturmuster.");
        }

        // Muster tokenisieren: Symbol + optionaler '?'-Modifikator (leerer Wert erlaubt).
        // Verirrte '?' werden zu eigenen Tokens und scheitern an der Symbol-Prüfung.
        preg_match_all('/(.)(\??)/su', $pattern, $matches, PREG_SET_ORDER);
        $tokens = array_map(static fn ($m) => [$m[1], $m[2] === '?'], $matches);

        $unknown = array_diff(array_unique(array_column($tokens, 0)), Validator::STRUCTURE_SYMBOLS);
        if ($unknown !== []) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: unbekannte Mustersymbole '" . implode("', '", $unknown) . "' in '$pattern'.");
        }

        $fields = self::rowToFields($row, $delimiter, $enclosure);
        if ($fields === null) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: Zeile nicht parsebar.");
        }

        $expected = count($tokens);
        if (!is_null($columns) && count($fields) !== $columns) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet $columns Spalten, erhalten: " . count($fields));
        } elseif ($strict && count($fields) !== $expected) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet $expected Spalten, erhalten: " . count($fields));
        } elseif (!$strict && count($fields) < $expected) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet mindestens $expected Spalten, erhalten: " . count($fields));
        }

        foreach ($tokens as $index => [$symbol, $optional]) {
            $value = $fields[$index] ?? '';

            // Optionales Feld: '?'-Modifikator bzw. Alt-Kürzel 'D' erlauben leere Werte
            if (($optional || $symbol === 'D') && trim($value) === '') {
                continue;
            }

            if (!Validator::validateBySymbol($symbol, $value)) {
                return self::logDebugAndReturn(false, "Spalte $index entspricht nicht dem erwarteten Musterzeichen '$symbol' – Wert: '$value'");
            }
        }

        return self::logDebugAndReturn(true, "Strukturprüfung erfolgreich für Muster: '$pattern'");
    }

    /**
     * Prüft, ob die Spalten einer Zeile mit den angegebenen Mustern beginnen.
     *
     * Jedes Muster wird als Präfix-Vergleich auf die jeweilige Spalte angewendet,
     * '*' überspringt eine Spalte.
     *
     * @param array<int|string, scalar|null>|string $row Zeile als Feld-Array oder rohe CSV-Zeile
     * @param array<int, string> $patterns Die Muster für die Spalten
     * @param string      $encoding  Zeichenkodierung der Eingabe (Standard: 'UTF-8')
     * @param bool        $strict    Spaltenanzahl muss exakt der Musteranzahl entsprechen (Standard: true)
     * @param string|null $delimiter Trennzeichen für String-Eingaben (null = automatisch erkennen)
     * @param string      $enclosure Enclosure-Zeichen für String-Eingaben
     */
    public static function matchColumns(array|string $row, array $patterns, string $encoding = 'UTF-8', bool $strict = true, ?string $delimiter = null, string $enclosure = '"'): bool {
        if (empty($patterns)) {
            return self::logDebugAndReturn(false, "matchColumns erwartet mindestens ein Muster.");
        }

        $fields = self::rowToFields($row, $delimiter, $enclosure);
        if ($fields === null || $fields === []) {
            return self::logDebugAndReturn(false, "matchColumns erwartet eine nicht-leere Zeile.");
        }

        if (implode('', $fields) === '') {
            return self::logDebugAndReturn(false, "Leere Zeile erkannt, kein Vergleich notwendig.");
        } elseif ($strict && count($fields) !== count($patterns)) {
            return self::logDebugAndReturn(false, "Spaltenanzahl (" . count($fields) . ") entspricht nicht der Musteranzahl (" . count($patterns) . ").");
        } elseif (!$strict && count($fields) < count($patterns)) {
            return self::logDebugAndReturn(false, "Spaltenanzahl (" . count($fields) . ") ist kleiner als die Musteranzahl (" . count($patterns) . ").");
        }

        foreach (array_values($patterns) as $index => $pattern) {
            $pattern = (string) $pattern;
            if ($pattern === '*') {
                continue;
            }

            $cell = $fields[$index] ?? '';
            // Encoding berücksichtigen
            $cellUtf8 = mb_convert_encoding($cell, 'UTF-8', $encoding);
            $patternQuoted = preg_quote($pattern, '/');

            if (!preg_match("/^$patternQuoted/", $cell) && !preg_match("/^$patternQuoted/", $cellUtf8)) {
                return self::logDebugAndReturn(false, "Muster nicht gefunden: »" . $pattern . "« in Spalte[$index] = »" . $cell . "«");
            }
        }

        return self::logDebugAndReturn(true, "Alle Muster erfolgreich in den Spalten gefunden.");
    }

    /**
     * Entfernt das Excel-Textpräfix (führendes einfaches Anführungszeichen) von numerischen Werten.
     *
     * Excel verwendet ein führendes `'` um Zellen als Text zu kennzeichnen.
     * Beim CSV-Export können negative Zahlen als `'-902.36'` erscheinen.
     * Diese Methode entfernt solche Anführungszeichen, damit der Wert korrekt geparst werden kann.
     *
     * Beispiele:
     * - `'-902.36'` → `-902.36`
     * - `'123`      → `123`
     * - `'-1.234,56'` → `-1.234,56`
     * - `hello`     → `hello` (unverändert, kein Anführungszeichen)
     *
     * @param string $value Der zu bereinigende Wert.
     * @return string Der bereinigte Wert oder der Originalwert wenn kein Textpräfix vorhanden.
     */
    public static function stripExcelTextPrefix(string $value): string {
        if ($value === '' || $value === "'") {
            return $value;
        }

        // Führendes ' entfernen wenn Rest numerisch aussieht
        if (str_starts_with($value, "'")) {
            $stripped = substr($value, 1);
            // Trailing ' ebenfalls entfernen falls vorhanden
            if (str_ends_with($stripped, "'")) {
                $stripped = substr($stripped, 0, -1);
            }
            // Nur übernehmen wenn der Rest wie eine Zahl aussieht (mit optionalen Trennern)
            $check = str_replace(['.', ',', ' ', '-', '+'], '', $stripped);
            if ($check !== '' && ctype_digit($check)) {
                static::logInfo("Excel Textpräfix gefunden. Wurde die Datei bearbeitet?");
                return $stripped;
            }
        }

        return $value;
    }

    /**
     * Encodiert einen einzelnen CSV-Feldwert (RFC 4180) für die Ausgabe.
     *
     * Gegenstück zu {@see parseField()}. Das Quoting-Verhalten steuert
     * {@see QuotingStyle}:
     * - {@see QuotingStyle::MINIMAL}: Nur quoten, wenn das Feld Trennzeichen,
     *   Enclosure oder Zeilenumbruch (\n / \r) enthält; Enclosures werden verdoppelt.
     * - {@see QuotingStyle::ALWAYS}: Jedes Feld wird immer umschlossen.
     * - {@see QuotingStyle::FPUTCSV}: Byte-kompatibel zu PHPs fputcsv(): quotet
     *   zusätzlich bei Escape-Zeichen ($escape), Tab (\t) und Leerzeichen;
     *   Enclosures werden nur verdoppelt, wenn sie NICHT unmittelbar auf das
     *   Escape-Zeichen folgen (fputcsv-Escape-Semantik). Byte-Parität gilt für
     *   Ein-Zeichen-Delimiter/-Enclosure/-Escape ($escape = '' entspricht
     *   fputcsv mit deaktiviertem Escape).
     *
     * Ist $enclosure leer, wird der Wert unverändert zurückgegeben.
     *
     * @param string            $value     Der zu encodierende Feldwert.
     * @param string            $delimiter Das Spaltentrennzeichen.
     * @param string            $enclosure Das Enclosure-Zeichen ('' = kein Quoting).
     * @param bool|QuotingStyle $quoting   Quoting-Strategie. Bool ist der rückwärtskompatible
     *                                     Alias des früheren $forceEnclosure-Parameters
     *                                     (true = ALWAYS, false = MINIMAL) und gilt als deprecated.
     * @param string            $escape    Escape-Zeichen für QuotingStyle::FPUTCSV
     *                                     (Standard '\\' wie bei fputcsv; '' = kein Escape).
     * @return string                      Das encodierte Feld.
     */
    public static function encodeField(string $value, string $delimiter = ',', string $enclosure = '"', bool|QuotingStyle $quoting = QuotingStyle::MINIMAL, string $escape = '\\'): string {
        if ($enclosure === '') {
            return $value;
        }

        $style = is_bool($quoting) ? ($quoting ? QuotingStyle::ALWAYS : QuotingStyle::MINIMAL) : $quoting;

        if ($style === QuotingStyle::FPUTCSV) {
            return self::encodeFieldFputcsv($value, $delimiter, $enclosure, $escape);
        }

        $needsEnclosure = $style === QuotingStyle::ALWAYS
            || str_contains($value, $delimiter)
            || str_contains($value, $enclosure)
            || str_contains($value, "\n")
            || str_contains($value, "\r");

        if (!$needsEnclosure) {
            return $value;
        }

        return $enclosure . str_replace($enclosure, $enclosure . $enclosure, $value) . $enclosure;
    }

    /**
     * Encodiert ein Feld byte-kompatibel zu PHPs fputcsv().
     *
     * Nachbildung der fputcsv-Schreiblogik (php_fputcsv, ext/standard/file.c):
     * Gequotet wird, wenn das Feld Delimiter, Enclosure, Escape-Zeichen,
     * \n, \r, \t oder Leerzeichen enthält. Beim Schreiben wird ein Enclosure
     * nur verdoppelt, wenn es nicht unmittelbar auf das Escape-Zeichen folgt.
     *
     * @param string $value     Der zu encodierende Feldwert.
     * @param string $delimiter Das Spaltentrennzeichen (1 Zeichen).
     * @param string $enclosure Das Enclosure-Zeichen (1 Zeichen).
     * @param string $escape    Das Escape-Zeichen (1 Zeichen, '' = kein Escape).
     * @return string           Das encodierte Feld.
     */
    private static function encodeFieldFputcsv(string $value, string $delimiter, string $enclosure, string $escape): string {
        $needsEnclosure = str_contains($value, $delimiter)
            || str_contains($value, $enclosure)
            || ($escape !== '' && str_contains($value, $escape))
            || str_contains($value, "\n")
            || str_contains($value, "\r")
            || str_contains($value, "\t")
            || str_contains($value, ' ');

        if (!$needsEnclosure) {
            return $value;
        }

        $result = $enclosure;
        $escaped = false;
        $len = strlen($value);

        for ($i = 0; $i < $len; $i++) {
            $char = $value[$i];
            if ($escape !== '' && $char === $escape) {
                $escaped = true;
            } elseif (!$escaped && $char === $enclosure) {
                $result .= $enclosure; // Enclosure verdoppeln (RFC-4180-Escape)
            } else {
                $escaped = false;
            }
            $result .= $char;
        }

        return $result . $enclosure;
    }

    /**
     * Encodiert eine komplette CSV-Zeile aus Feldwerten (ohne Zeilenumbruch).
     *
     * Nutzt {@see encodeField()} je Feld und verbindet mit dem Trennzeichen.
     * `null`-Werte werden zu ''. Für streamende Exporte den Zeilenumbruch
     * (z. B. "\r\n") selbst anhängen.
     *
     * Terminator-Konvention: fputcsv() hängt standardmäßig "\n" an die Zeile an,
     * encodeLine() bewusst NICHT. Für Byte-Parität mit fputcsv() daher
     * encodeLine(..., QuotingStyle::FPUTCSV) . "\n" verwenden.
     *
     * @param array<int|string, scalar|null> $fields    Feldwerte in Spaltenreihenfolge.
     * @param string                         $delimiter Das Spaltentrennzeichen.
     * @param string                         $enclosure Das Enclosure-Zeichen.
     * @param bool|QuotingStyle              $quoting   Quoting-Strategie (bool = deprecated
     *                                                  Alias für $forceEnclosure, s. {@see encodeField()}).
     * @param string                         $escape    Escape-Zeichen für QuotingStyle::FPUTCSV.
     * @return string                                   Die encodierte Zeile ohne Zeilenumbruch.
     */
    public static function encodeLine(array $fields, string $delimiter = ',', string $enclosure = '"', bool|QuotingStyle $quoting = QuotingStyle::MINIMAL, string $escape = '\\'): string {
        $parts = [];
        foreach (array_values($fields) as $value) {
            $parts[] = self::encodeField($value === null ? '' : (string) $value, $delimiter, $enclosure, $quoting, $escape);
        }

        return implode($delimiter, $parts);
    }
}
