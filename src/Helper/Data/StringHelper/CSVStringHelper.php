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

class CSVStringHelper extends StringHelper {
    public static function detectCSVEnclosureRepeat(string $line, string $enclosure = '"', string $delimiter = ',', ?string $started = null, ?string $closed = null, bool $strict = true): int {
        $s = self::stripStartEnd($line, $started, $closed);
        if ($s === '' || $enclosure === '') return 0;

        $len = strlen($s);
        $dl  = preg_quote($delimiter, '/');
        $enc = preg_quote($enclosure, '/');
        $ceilHalf = static fn(int $n): int => max(1, intdiv($n + 1, 2));

        if ($strict) {
            if ($s[0] !== $enclosure) return 0;

            // Anzahl konsekutiver Enclosures am Zeilenanfang
            $q = 0;
            while ($q < $len && $s[$q] === $enclosure) $q++;

            // optionales Whitespace
            $i = $q;
            while ($i < $len && ($s[$i] === ' ' || $s[$i] === "\t")) $i++;

            // Leeres erstes Feld → Sonderabbildung
            if ($i >= $len || $s[$i] === $delimiter) {
                if ($q === 2) return 0;          // "" → 0
                return $ceilHalf($q);             // z.B. """ → 2, """" → 2, """"" → 3
            }

            // Nicht-leer
            return $q;
        }

        // Non-strict: nur Runs an Feldanfängen, leere Felder mit ceilHalf(n) abbilden
        if (!preg_match_all('/(?:^|' . $dl . ')\s*(' . $enc . '+)/', $s, $m, PREG_OFFSET_CAPTURE)) {
            return 0;
        }

        $best = 0;
        foreach ($m[1] as [$runStr, $pos]) {
            $q = strlen($runStr);
            $j = $pos + $q;
            while ($j < $len && ($s[$j] === ' ' || $s[$j] === "\t")) $j++;
            $val = ($j >= $len || $s[$j] === $delimiter) ? $ceilHalf($q) : $q;
            if ($val > $best) $best = $val;
        }
        return $best;
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

        return array_values(array_unique($values));
    }

    private static function normalizeRepeatedEnclosures(string $line, string $delimiter = ',', string $enclosure = '"'): string {
        if ($line === '' || $enclosure === '') return $line;

        $E2 = $enclosure . $enclosure;

        $max = self::detectCSVEnclosureRepeat($line, $enclosure, $delimiter, null, null, false);
        if ($max < 2) return $line;

        // 1) Leere Felder
        for ($r = $max; $r >= 2; $r--) {
            $qq = str_repeat($enclosure, $r) . str_repeat($enclosure, $r);
            $line = str_replace($delimiter . $qq . $delimiter, $delimiter . $E2 . $delimiter, $line);
            $line = str_replace($qq . $delimiter, $E2 . $delimiter, $line);
            $line = str_replace($delimiter . $qq, $delimiter . $E2, $line);
            if ($line === $qq) $line = $E2;
        }

        // 2) Nicht-leere Runs an Feldgrenzen auf einfaches $enclosure reduzieren
        for ($r = $max; $r >= 2; $r--) {
            $qr = str_repeat($enclosure, $r);
            $line = str_replace($delimiter . $qr, $delimiter . $enclosure, $line);
            $line = str_replace($qr . $delimiter, $enclosure . $delimiter, $line);
        }

        // 3) Aufräumen
        $line = str_replace($delimiter . $E2, $delimiter . $enclosure, $line); // ,E2 -> ,"
        $line = str_replace($E2 . $delimiter, $enclosure . $delimiter, $line); // E2, -> ",
        $line = str_replace($delimiter . $enclosure . $delimiter, $delimiter . $delimiter, $line); // ',",' -> ',,'

        // 4) Start/Ende: Mehrfach-Quotes -> ein $enclosure
        for ($r = $max; $r >= 2; $r--) {
            $qq = str_repeat($enclosure, $r);
            if (str_starts_with($line, $qq)) $line = substr_replace($line, $enclosure, 0, strlen($qq));
            if (str_ends_with($line,   $qq)) $line = substr_replace($line, $enclosure, -strlen($qq));
        }

        // 5) Finale Kanten
        $line = str_replace($delimiter . $E2 . $delimiter, $delimiter . $delimiter, $line); // ,E2, -> ,,
        $line = str_replace($delimiter . $enclosure . $delimiter, $delimiter . $delimiter, $line); // ',",' -> ',,'

        return $line;
    }


    private static function parseCSVLine(
        string $line,
        string $delimiter,
        string $enclosure,
        int $enclosureRepeat = 1,
        bool $withMeta = false
    ): array {
        if ($delimiter === '') throw new RuntimeException("Delimiter darf nicht leer sein");
        if ($line === '') return ['fields' => [], 'enclosed' => 0, 'total' => 0] + ($withMeta ? ['meta' => []] : []);

        // Kein Enclosure → simpler Split
        if ($enclosure === '') {
            $parts = explode($delimiter, $line);
            $parts = array_map(static fn($v) => trim($v), $parts);
            $out = ['fields' => $parts, 'enclosed' => 0, 'total' => count($parts)];
            if ($withMeta) $out['meta'] = array_fill(0, count($parts), ['quoted' => false, 'openRun' => 0]);
            return $out;
        }

        // Nur normalisieren, wenn wirklich Wiederhol-Quotes vorkommen
        $s = $line;
        if (self::detectCSVEnclosureRepeat($s, $enclosure, $delimiter, null, null, false) >= 2) {
            $s = self::normalizeRepeatedEnclosures($s, $delimiter, $enclosure);
        }

        // WICHTIG: Escape deaktivieren, sonst mischt \ als Escape ins Parsing
        // PHP: escape-Parameter mit "\0" (NUL) „deaktivieren“
        $fields = str_getcsv($s, $delimiter, $enclosure, "\0") ?? [];

        return ['fields' => $fields, 'enclosed' => 0, 'total' => count($fields)];
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
        $parsed = self::parseCSVLine($lines, $delimiter, $enclosure, 1, true);

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


    /**
     * Prüft, ob eine Zeile ausschließlich aus Feldern besteht,
     * die mit N-fachem Enclosure umschlossen sind und per Delimiter getrennt werden.
     *
     * @param string $line            Eingabezeile
     * @param string $delimiter       Spaltentrennzeichen (z. B. "," oder ";")
     * @param string $enclosure       Enclosure-Zeichen (z. B. '"')
     * @param int    $enclosureRepeat Anzahl der zu erwartenden Wiederholungen
     * @param bool   $strict          Wenn true, müssen alle Felder enclosed sein, sonst reicht eines
     * @param ?string $started        Optionales Startzeichen der Zeile
     * @param ?string $closed         Optionales Endzeichen der Zeile
     * @return bool                   True, wenn die Zeile der Struktur entspricht, sonst false
     */
    public static function hasRepeatedEnclosure(string $line, string $delimiter = ';', string $enclosure = '"', int $repeat = 1, bool $strict = true, ?string $started = null, ?string $closed = null): bool {
        $s = self::stripStartEnd($line, $started, $closed);
        $s = self::normalizeRepeatedEnclosures($s, $delimiter, $enclosure);

        // leere Zeile oder endet mit Delimiter → false
        if (empty($s) || ($strict && !empty($delimiter) && str_ends_with($s, $delimiter))) return false;

        // repeat==0: keine Quotes erlaubt, mind. ein Delimiter, kein trailing Delimiter
        if ($repeat === 0) {
            if (str_contains($s, $enclosure)) return false;
            if (str_ends_with($s, $delimiter)) return false;
            return substr_count($s, $delimiter) >= 1;
        }

        try {
            // parse, tolerant, mit Meta
            $parsed = self::parseCSVLine($s, $delimiter, $enclosure, 1, true);
        } catch (RuntimeException) {
            return false;
        }

        $total = $parsed['total'] ?? 0;
        if ($total < 2) return false;

        /** @var array<int,array{quoted:bool,openRun:int}> $meta */
        $meta = $parsed['meta'] ?? [];
        $quotedCount = 0;

        foreach ($meta as $m) {
            if ($m['quoted']) {
                $quotedCount++;
                // alle gequoteten Felder müssen exakt 'repeat' Quotes nutzen
                if ($m['openRun'] !== $repeat) return false;
            } else {
                // ungequotete Felder nur erlaubt, wenn strict=false
                if ($strict) return false;
            }
        }

        // strict: alle Felder gequotet und korrekt
        // non-strict: mindestens ein gequotetes Feld und alle gequoteten korrekt
        return $strict ? ($quotedCount === $total) : ($quotedCount > 0);
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