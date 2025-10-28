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

use CommonToolkit\Entities\Common\CSV\CSVLine;
use CommonToolkit\Helper\Data\StringHelper;
use RuntimeException;
use Throwable;

class CSVStringHelper extends StringHelper {
    public static function detectCSVEnclosureRepeat(string $line, string $enclosure = '"', string $delimiter = ',', ?string $started = null, ?string $closed = null, bool $strict = true): int {
        $s = self::stripStartEnd($line, $started, $closed);
        if (empty($s)) return 0;

        $csvLine = CSVLine::fromString($s, $delimiter, $enclosure);
        $fields = $csvLine->getFields();

        $repeats = [];

        foreach ($fields as $field) {
            $repeats[] = $field->getEnclosureRepeat();
        }

        return $strict ? min($repeats) : max($repeats);
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

    /**
     * Parst eine CSV-Zeile in Felder.
     *
     * @param string $line
     * @param string $delimiter
     * @param string $enclosure
     * @param bool   $withMeta
     * @return array{fields:array<int,string>,enclosed:int,total:int,meta?:array<int,array{quoted:bool,repeat:int,raw:string}>}
     * @throws RuntimeException
     */
    private static function parseCSVLine(string $line, string $delimiter, string $enclosure, bool $withMeta = false): array {
        if ($delimiter === '') throw new RuntimeException('Delimiter darf nicht leer sein');
        if (empty(trim($line))) {
            return ['fields' => [], 'enclosed' => 0, 'total' => 0] + ($withMeta ? ['meta' => []] : []);
        }

        $fields = CSVLine::fromString($line, $delimiter, $enclosure)->getFields();
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
        if ($s === '' || $enclosure === '') {
            return false;
        }

        // --- Keine Quotes erlaubt ---
        if ($repeat === 0) {
            return !str_contains($s, $enclosure)
                && substr_count($s, $delimiter) >= 1;
        }

        try {
            $csvLine = CSVLine::fromString($s, $delimiter, $enclosure);
        } catch (Throwable) {
            return false; // Ungültige CSV-Struktur
        }

        // --- Range aus CSVLine übernehmen ---
        [$minRepeat, $maxRepeat] = $csvLine->getEnclosureRepeatRange(true);
        $quotedCount = $csvLine->countQuotedFields();

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

    public static function canParseCompleteCSVLine(string $line, string $delimiter = ',', string $enclosure = '"', ?string $started = null, ?string $closed = null): bool {
        // Start/End entfernen, aber originalen Vergleich sichern
        $trimmed = self::stripStartEnd($line, $started, $closed);
        if (empty($trimmed)) return false;

        try {
            // Versuch, die Zeile über CSVLine zu parsen
            $csvLine = CSVLine::fromString($trimmed, $delimiter, $enclosure);
            $rebuilt = $csvLine->toString($delimiter, $enclosure);

            // Wenn gleich → valide CSV-Struktur
            if ($trimmed === $rebuilt) {
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