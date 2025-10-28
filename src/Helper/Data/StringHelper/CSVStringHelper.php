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

namespace CommonToolkit\Helper\Data\StringHelper;

use CommonToolkit\Helper\Data\StringHelper;
use RuntimeException;
use Throwable;

class CSVStringHelper extends StringHelper {
    public static function detectCSVEnclosureRepeat(string $line, string $enclosure = '"', string $delimiter = ',', ?string $started = null, ?string $closed = null, bool $strict = true): int {
        $s = self::stripStartEnd($line, $started, $closed);
        if ($s === '' || $enclosure === '') return 0;

        $dl  = preg_quote($delimiter, '/');
        $enc = preg_quote($enclosure, '/');

        // --- Phase 1: Leere Felder ---
        $reEmpty = '/(?:(?<=^)|(?<=' . $dl . '))\s*(?:' . $enc . '{0,}|' . $enc . '{2,})\s*(?=(?:' . $dl . '|$))/';
        preg_match_all($reEmpty, $s, $emptyMatches, PREG_OFFSET_CAPTURE);

        $emptyRuns = [];
        foreach ($emptyMatches[0] as $match) {
            $count = substr_count($match[0], $enclosure);
            $emptyRuns[] = ($count % 2 === 0) ? intdiv($count, 2) : intdiv($count - 1, 2);
            $s = preg_replace('/' . preg_quote($match[0], '/') . '/', '', $s, 1);
        }

        // --- Phase 2: Nicht-leere gequotete Felder ---
        $reQuoted = '/(?<=^|' . $dl . ')(?:' . $enc . '{1,})([^' . $enc . ']+?)(?:' . $enc . '{1,})(?=' . $dl . '|$)/s';
        preg_match_all($reQuoted, $s, $quotedMatches, PREG_SET_ORDER);

        $quotedRuns = [];
        foreach ($quotedMatches as $match) {
            $count = substr_count($match[0], $enclosure);
            $quotedRuns[] = ($count % 2 === 0) ? intdiv($count, 2) : intdiv($count - 1, 2);
            $s = preg_replace('/' . preg_quote($match[0], '/') . '/', '', $s, 1);
        }

        // --- Phase 3: Nicht-gequotete Felder ---
        $unQuoted = '/(?<=^|' . $dl . ')[^' . $enc . $dl . '\s][^' . $dl . ']*?(?=' . $dl . '|$)/';
        preg_match_all($unQuoted, $s, $unquotedMatches, PREG_SET_ORDER);

        $unquotedRuns = [];
        foreach ($unquotedMatches as $match) {
            $field = $match[0] ?? '';
            if ($field === '') continue;

            // Kein Enclosure → Run 0
            $unquotedRuns[] = 0;
        }

        // --- Phase 4: Kombination & Entscheidung ---
        $allRuns = array_merge($emptyRuns, $quotedRuns, $unquotedRuns);
        $allRuns = array_values(array_filter($allRuns, fn($v) => $v >= 0));

        if (empty($allRuns)) {
            return 0;
        }

        // Nur Runs > 0 berücksichtigen
        $positive = array_filter($allRuns, fn($v) => $v > 0);
        if (empty($positive)) {
            return 0;
        }

        if ($strict) {
            // Strict: kleinster positiver Run
            return min($allRuns);
        }

        // Non-strict: größter positiver Run
        return max($positive);
    }


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

    private static function parseCSVLine(string $line, string $delimiter, string $enclosure, bool $withMeta = false): array {
        if ($delimiter === '') throw new RuntimeException('Delimiter darf nicht leer sein');
        $line = (string)$line;
        if ($line === '') return ['fields' => [], 'enclosed' => 0, 'total' => 0] + ($withMeta ? ['meta' => []] : []);

        // Mehrfach-Quotes glätten
        $norm = self::normalizeRepeatedEnclosures($line, $delimiter, $enclosure);

        // CSV parsen
        $fields = str_getcsv($norm, $delimiter, $enclosure, "\\");

        $total    = count($fields);
        $enclosed = 0;
        $meta     = [];

        if ($withMeta) {
            foreach ($fields as $f) {
                $quoted = ($enclosure !== '' && strlen($f) && ($f !== trim($f, $enclosure)));
                // Näherung: gezählt wird über Heuristik; str_getcsv entfernt Quotes.
                $meta[] = ['quoted' => $quoted, 'openRun' => $quoted ? 1 : 0];
                if ($quoted) $enclosed++;
            }
        }

        $out = ['fields' => $fields, 'enclosed' => $enclosed, 'total' => $total];
        if ($withMeta) $out['meta'] = $meta;
        return $out;
    }



    /**
     * Wrapper für CSV mit optionalem Newline-Ersatz in gequoteten Feldern.
     *
     * @param string      $lines
     * @param string      $delimiter
     * @param string      $enclosure
     * @param string|null $nlReplacement  Ersatz für \r,\n,\r\n in gequoteten Feldern.
     *                                    null = nicht ersetzen (Default: ' ').
     * @return array{fields:array<int,string>,enclosed:int,total:int}
     */
    private static function parseCSVMultiLine(string $lines, string $delimiter = ',', string $enclosure = '"', ?string $nlReplacement = ' '): array {
        // Mit Meta parsen
        $parsed = self::parseCSVLine($lines, $delimiter, $enclosure, true);

        if ($nlReplacement === null) {
            return [
                'fields'   => $parsed['fields'],
                'enclosed' => $parsed['enclosed'],
                'total'    => $parsed['total'],
            ];
        }

        $fields = $parsed['fields'];
        $meta   = $parsed['meta'] ?? [];
        $nlRe   = "/\r\n|\r|\n/u";

        foreach ($fields as $i => $val) {
            if (!empty($meta[$i]['quoted']) && $val !== '') {
                $fields[$i] = preg_replace($nlRe, $nlReplacement, $val) ?? $val;
            }
        }

        return [
            'fields'   => $fields,
            'enclosed' => $parsed['enclosed'],
            'total'    => $parsed['total'],
        ];
    }


    public static function hasRepeatedEnclosure(string $line, string $delimiter = ',', string $enclosure = '"', int $repeat = 1, bool $strict = true, ?string $started = null, ?string $closed = null): bool {
        $s = self::stripStartEnd($line, $started, $closed);
        if ($s === '' || $enclosure === '') return false;

        // repeat==0: keine Quotes erlaubt, mind. ein Delimiter, kein trailing Delimiter
        if ($repeat === 0) {
            return !str_contains($s, $enclosure)
                && substr_count($s, $delimiter) >= 1;
        }

        $strictRun = self::detectCSVEnclosureRepeat($s, $enclosure, $delimiter, $started, $closed, true);
        $looseRun  = self::detectCSVEnclosureRepeat($s, $enclosure, $delimiter, $started, $closed, false);

        if (!self::canParseCompleteCSVLine($s, $delimiter, $enclosure, $started, $closed)) {
            return false; // ungültige CSV-Struktur
        }

        if ($looseRun === 0 && $strictRun === 0) return false; // keine Quotes gefunden

        if ($strict) {
            // alle Felder gleich gequotet: gleicher Run überall und exakt == repeat
            return ($strictRun === $repeat) && ($looseRun === $repeat);
        }

        // non-strict: irgendwo mind. repeat-fach gequotet
        return $looseRun >= $repeat;
    }

    public static function canParseCompleteCSVLine(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null): bool {
        $s = self::stripStartEnd($line, $started, $closed);

        if (self::detectCSVEnclosureRepeat($s, $enclosure, $delimiter, null, null, false) >= 2) {
            $s = self::normalizeRepeatedEnclosures($s, $delimiter, $enclosure);
        }

        if (self::hasOutsideCharacters($s, $delimiter, $enclosure)) return false;

        try {
            $parsed = self::parseCSVLine($s, $delimiter, $enclosure);
        } catch (Throwable) {
            return false;
        }

        return is_array($parsed['fields']);
    }

    public static function hasOutsideCharacters(string $line, string $delimiter = ',', string $enclosure = '"'): bool {
        $enc = preg_quote($enclosure, '/');
        $dl  = preg_quote($delimiter, '/');

        // --- 1️⃣ Zeichen direkt vor einem Quote, das kein Delimiter oder Whitespace ist (z. B. [ oder a" )
        if (preg_match('/(?<!^)[^' . $dl . '\s]' . $enc . '/', $line)) {
            return true;
        }

        // --- 2️⃣ Zeichen direkt nach einem Quote, das kein Delimiter, kein Whitespace und kein Zeilenende ist (z. B. "]" oder "x")
        if (preg_match('/' . $enc . '(?!' . $enc . ')[^' . $dl . '\s$]/', $line)) {
            return true;
        }

        // --- 3️⃣ Whitespace vor erstem oder nach letztem Quote (z. B. ' "Feld1"' oder '"Feld3" ')
        if (preg_match('/^\s*' . $enc . '|' . $enc . '\s*$/', $line) && trim($line) !== $line) {
            return true;
        }

        return false;
    }


    /**
     * Prüft, ob eine CSV-Zeile Felder mit Zeilenumbrüchen enthält.
     *
     * @param string $csv                 Eingabe-CSV-Zeile
     * @param string $delimiter           Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure           Enclosure-Zeichen (z. B. '"')
     * @param bool   $allowWithoutQuotes  Optional: erkenne Multiline auch ohne Quotes (unsicher)
     * @return bool                       True, wenn Multiline-Felder erkannt wurden, sonst false
     */
    public static function hasMultilineFields(string $csv, string $delimiter = ',', string $enclosure = '"', bool $allowWithoutQuotes = false): bool {
        // Prüfe auf Multiline innerhalb von Enclosures
        $pattern = sprintf(
            '/(?:^|%2$s)%1$s(?:(?!%1$s).)*\R(?:(?!%1$s).)*%1$s(?:%2$s|$)/su',
            preg_quote($enclosure, '/'),
            preg_quote($delimiter, '/')
        );

        if (preg_match($pattern, $csv)) {
            return true;
        }

        // Optional: erkenne Multiline auch ohne Quotes (unsicher)
        if ($allowWithoutQuotes && str_contains($csv, "\n")) {
            return true;
        }

        return false;
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
     * @throws RuntimeException            Wenn die Struktur inkonsistent ist
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
}