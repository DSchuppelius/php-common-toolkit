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
use CommonToolkit\Helper\Data\StringHelper as BaseStringHelper;
use RuntimeException;
use Throwable;

final class StringHelper extends BaseStringHelper {
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

        $expectedStart = str_repeat($enclosure, $minRepeat);
        $expectedEnd   = str_repeat($enclosure, $minRepeat);

        return str_starts_with($value, $expectedStart) && str_ends_with($value, $expectedEnd);
    }

    /**
     * Erkennt die Anzahl der wiederholten Enclosures in einer CSV-Zeile.
     *
     * @param string      $line       Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $enclosure  Enclosure-Zeichen (z. B. '"')
     * @param string      $delimiter  Spaltentrennzeichen (z. B. "," oder ";")
     * @param string|null $started    Optional: Startzeichen der Zeile
     * @param string|null $closed     Optional: Endzeichen der Zeile
     * @param bool        $strict     Ob der strikte (min) oder non-strikte (max) Wert zurückgegeben werden soll
     * @return int                    Die erkannte Anzahl der wiederholten Enclosures
     */
    public static function detectCSVEnclosureRepeat(string $line, string $enclosure = '"', string $delimiter = ',', ?string $started = null, ?string $closed = null, bool $strict = true): int {
        $s = self::stripStartEnd($line, $started, $closed);
        if (empty($s)) return 0;

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
    private static function genEmptyValuesFromCSVString(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null, bool $withDelimiter = true): array {
        $strictRepeat    = self::detectCSVEnclosureRepeat($line, $enclosure, $delimiter, $started, $closed, true);
        $nonStrictRepeat = self::detectCSVEnclosureRepeat($line, $enclosure, $delimiter, $started, $closed, false);

        $repeats = array_unique(
            array_filter([$strictRepeat, $nonStrictRepeat], fn($v) => $v > 0)
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
        usort($result, fn($a, $b) => strlen($b) <=> strlen($a));

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
    private static function normalizeRepeatedEnclosures(string $line, string $delimiter = ',', string $enclosure = '"'): string {
        if ($line === '' || $enclosure === '') return $line;

        $max = self::detectCSVEnclosureRepeat($line, $enclosure, $delimiter, null, null, false);
        if ($max < 2) return $line;

        $with = self::genEmptyValuesFromCSVString($line, $delimiter, $enclosure, null, null, true);

        // 1) Leere Felder an Feldgrenzen auf doppeltes $enclosure normalisieren
        foreach ($with as $v) {
            if ($v === $delimiter . $delimiter) continue;
            if (str_contains($line, $v)) {
                while (true) {
                    $newLine = str_replace($v, $delimiter . $delimiter, $line);
                    if ($newLine === $line) break;
                    $line = $newLine;
                }
            }
        }

        // Nicht-leere Felder an Feldgrenzen auf einfaches $enclosure reduzieren
        for ($r = $max; $r >= 2; $r--) {
            $qq = str_repeat($enclosure, $r);
            $line = str_replace($delimiter . $qq, $delimiter . $enclosure, $line);
            $line = str_replace($qq . $delimiter, $enclosure . $delimiter, $line);

            if (str_starts_with($line, $qq)) $line = substr_replace($line, $enclosure, 0, strlen($qq));
            if (str_ends_with($line,   $qq)) $line = substr_replace($line, $enclosure, -strlen($qq));
        }

        return $line;
    }

    /**
     * Parst eine CSV-Zeile in Felder.
     *
     * @param string $line          Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string $delimiter     Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure     Enclosure-Zeichen (z. B. '"')
     * @param bool   $withMeta      Ob Metadaten zu den Feldern zurückgegeben werden sollen
     * @return array{fields:array<int,string>,enclosed:int,total:int,meta?:array<int,array{quoted:bool,repeat:int,raw:string}>}
     * @throws RuntimeException
     */
    private static function parseCSVLine(string $line, string $delimiter, string $enclosure, bool $withMeta = false): array {
        if ($delimiter === '') self::logErrorAndThrow(RuntimeException::class, 'Delimiter darf nicht leer sein');
        if (empty(trim($line))) {
            return ['fields' => [], 'enclosed' => 0, 'total' => 0] + ($withMeta ? ['meta' => []] : []);
        }

        $fields = DataLine::fromString($line, $delimiter, $enclosure)->getFields();
        $meta   = [];

        foreach ($fields as $field) {
            $meta[] = [
                'quoted'   => $field->isQuoted(),
                'repeat'   => $field->getEnclosureRepeat(),
                'raw'      => $field->getRaw(),
            ];
        }

        $enclosed = count(array_filter($fields, fn($f) => $f->isQuoted()));

        $out = [
            'fields'   => array_map(fn($f) => $f->getValue(), $fields),
            'enclosed' => $enclosed,
            'total'    => count($fields),
        ];
        if ($withMeta) $out['meta'] = $meta;

        return $out;
    }

    /**
     * Ersetzt Zeilenumbrüche in gequoteten Feldern durch einen Ersatzstring.
     *
     * @param array<int,string> $fields      Die Felder der CSV-Zeile.
     * @param array<int,array{quoted:bool,repeat:int,raw:string}> $meta Metadaten zu den Feldern.
     * @param string            $replacement Der Ersatzstring für Zeilenumbrüche.
     * @return array<int,string>             Die Felder mit ersetzten Zeilenumbrüchen.
     */
    private static function replaceNewlinesInQuoted(array $fields, array $meta, string $replacement): array {
        $nlRe = "/\r\n|\r|\n/u";
        foreach ($fields as $i => $val) {
            if (($meta[$i]['quoted'] ?? false) && $val !== '') {
                $fields[$i] = preg_replace($nlRe, $replacement, $val) ?? $val;
            }
        }
        return $fields;
    }

    /**
     * Wrapper für CSV mit optionalem Newline-Ersatz in gequoteten Feldern.
     *
     * @param string      $lines          Eingabezeilen (z. B. aus einer CSV-Datei)
     * @param string      $delimiter      Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure      Enclosure-Zeichen (z. B. '"')
     * @param string|null $nlReplacement  Ersatz für \r,\n,\r\n in gequoteten Feldern.
     *                                    null = nicht ersetzen (Default: ' ').
     * @return array{fields:array<int,string>,enclosed:int,total:int}
     */
    private static function parseCSVMultiLine(string $lines, string $delimiter = ',', string $enclosure = '"', ?string $nlReplacement = ' '): array {
        $parsed = self::parseCSVLine($lines, $delimiter, $enclosure, true);
        $fields = $nlReplacement === null
            ? $parsed['fields']
            : self::replaceNewlinesInQuoted($parsed['fields'], $parsed['meta'] ?? [], $nlReplacement);

        return [
            'fields'   => $fields,
            'enclosed' => $parsed['enclosed'],
            'total'    => $parsed['total'],
        ];
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
        if (empty($trimmed)) return false;

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

            // Falls normalisierte Variante (z. B. durch doppelte Quotes) übereinstimmt
            $normalizedInput2  = self::normalizeRepeatedEnclosures($trimmed, $delimiter, $enclosure);
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
     * @param string $enclosure Enclosure-Zeichen (z. B. '"')
     * @return array<string>    Array der logischen CSV-Zeilen
     */
    public static function splitCsvByLogicalLine(string $csv, string $enclosure = '"'): array {
        $lines = preg_split('/\r\n|\r|\n/', $csv);
        $result = [];
        $buffer = '';

        foreach ($lines as $line) {
            $buffer .= ($buffer !== '' ? "\n" : '') . $line;

            if (!self::hasMultilineFields($buffer, ',', $enclosure)) {
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
                $current  = '';
                continue;
            }

            if (str_contains($current, $delimiter . $enclosure)) {
                self::logErrorAndThrow(RuntimeException::class, 'Ungültige CSV-Zeile – Delimiter nach Quote-Ende ohne neues Feld');
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
     * @param string      $line             Eingabezeile (z. B. aus einer CSV-Datei)
     * @param string      $delimiter        Spaltentrennzeichen (z. B. "," oder ";")
     * @param string      $enclosure        Enclosure-Zeichen (z. B. '"')
     * @param int         $enclosureRepeat  Anzahl der zu erwartenden Wiederholungen
     * @param ?string     $started          Optionales Startzeichen der Zeile
     * @param ?string     $closed           Optionales Endzeichen der Zeile
     * @return array<string>                Array der Felder
     * @throws RuntimeException             Wenn die Struktur inkonsistent ist
     */
    public static function extractFields(array|string $lines, string $delimiter = ';', string $enclosure = '"', ?string $started = null, ?string $closed = null, string $multiLineReplacement = " "): array {
        $raw = is_array($lines) ? implode("\n", $lines) : (string)$lines;
        $s   = self::stripStartEnd($raw, $started, $closed);

        // Nur normalisieren, wenn es wiederholte Enclosures gibt (>=2)
        if (self::detectCSVEnclosureRepeat($s, $enclosure, $delimiter, null, null, false) >= 2) {
            $s = self::normalizeRepeatedEnclosures($s, $delimiter, $enclosure);
        }

        if (self::hasMultilineFields($s, $delimiter, $enclosure)) {
            $parsed = self::parseCSVMultiLine($s, $delimiter, $enclosure, $multiLineReplacement);
            $fields = $parsed['fields'];
        } else {
            $parsed  = self::parseCSVLine($s, $delimiter, $enclosure); // Regex-Parser
            $fields  = $parsed['fields'] ?? [];
        }

        return $fields;
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
}
