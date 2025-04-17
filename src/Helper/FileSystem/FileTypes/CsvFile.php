<?php
/*
 * Created on   : Fri Oct 25 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CsvFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Validation\Validator;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;
use Generator;
use RuntimeException;
use Throwable;

class CsvFile extends HelperAbstract {
    protected static array $commonDelimiters = [',', ';', "\t", '|'];

    private static function resolveFile(string $file): string {
        if (!File::exists($file)) {
            self::logError("Die CSV-Datei $file existiert nicht oder ist nicht lesbar.");
            throw new FileNotFoundException("Die CSV-Datei $file existiert nicht oder ist nicht lesbar.");
        }
        return File::getRealPath($file);
    }

    private static function readLines(string $file, string $delimiter): Generator {
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new RuntimeException("CSV-Datei konnte nicht geöffnet werden: $file");
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!empty(array_filter($row))) {
                yield $row;
            }
        }

        fclose($handle);
    }

    public static function detectDelimiter(string $file, int $maxLines = 10): string {
        $file = self::resolveFile($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::logError("Fehler beim Öffnen der Datei: $file");
            throw new Exception("Fehler beim Öffnen der Datei: $file");
        }

        $delimiterCounts = array_fill_keys(self::$commonDelimiters, 0);
        $lineCount = 0;

        while (($line = fgets($handle)) !== false && $lineCount < $maxLines) {
            foreach (self::$commonDelimiters as $delimiter) {
                $delimiterCounts[$delimiter] += substr_count($line, $delimiter);
            }
            $lineCount++;
        }
        fclose($handle);

        arsort($delimiterCounts);
        $detectedDelimiter = key($delimiterCounts);

        if ($delimiterCounts[$detectedDelimiter] === 0) {
            self::logError("Kein geeignetes Trennzeichen in der Datei $file gefunden.");
            throw new Exception("Kein geeignetes Trennzeichen in der Datei $file gefunden.");
        }

        return $detectedDelimiter;
    }

    public static function getMetaData(string $file, ?string $delimiter = null): array {
        $file = self::resolveFile($file);
        $delimiter ??= self::detectDelimiter($file);
        $lines = self::readLines($file, $delimiter);

        $rowCount = 0;
        $columnCount = 0;

        foreach ($lines as $row) {
            if (!empty(array_filter($row))) {
                $rowCount++;
                $columnCount = max($columnCount, count($row));
            }
        }

        return [
            'RowCount'    => $rowCount,
            'ColumnCount' => $columnCount,
            'Delimiter'   => $delimiter
        ];
    }

    public static function isWellFormed(string $file, ?string $delimiter = null): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            self::logInfo("CSV-Datei nicht gefunden oder ungültig: " . $e->getMessage());
            return false;
        }

        $delimiter ??= self::detectDelimiter($file);
        $lines = self::readLines($file, $delimiter);

        $columnCount = null;
        foreach ($lines as $index => $row) {
            $rowLength = count($row);
            if ($columnCount === null) {
                $columnCount = $rowLength;
            } elseif ($rowLength !== $columnCount) {
                self::logDebug("Fehlerhafte Zeile $index: Spaltenanzahl $rowLength stimmt nicht mit Header ($columnCount) überein.");
                return false;
            }
        }

        return true;
    }

    public static function isValid(string $file, array $headerPattern, ?string $delimiter = null, bool $wellFormed = false): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            self::logInfo("CSV-Datei nicht gefunden oder ungültig: " . $e->getMessage());
            return false;
        }

        $delimiter ??= self::detectDelimiter($file);

        $lines = self::readLines($file, $delimiter);
        $header = $lines->current();

        if ($header === false) {
            self::logInfo("Header konnte nicht gelesen werden: $file");
            return false;
        }

        $headerValid = empty(array_diff($headerPattern, $header)) && empty(array_diff($header, $headerPattern));
        if (!$headerValid) {
            self::logDebug("Header stimmt nicht überein. Erwartet: " . implode(',', $headerPattern) . " / Gefunden: " . implode(',', $header));
            return false;
        }

        if ($wellFormed) {
            foreach ($lines as $index => $row) {
                if (count($row) !== count($header)) {
                    self::logDebug("Zeile $index hat nicht die gleiche Anzahl Spalten wie der Header.");
                    return false;
                }
            }
        }

        return true;
    }

    public static function checkStructureFile(string $file, string $structurePattern, ?string $delimiter = null, ?int $expectedColumns = null, bool $checkAllRows = false, bool $strict = true): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            self::logInfo("Fehler beim Öffnen der Datei: " . $e->getMessage());
            return false;
        }

        $delimiter ??= self::detectDelimiter($file);

        foreach (self::readLines($file, $delimiter) as $row) {
            if (!self::checkStructure($row, $structurePattern, $expectedColumns, $strict)) {
                self::logDebug("Strukturprüfung fehlgeschlagen bei Zeile: " . implode($delimiter, $row));
                return false;
            }

            if (!$checkAllRows) break;
        }

        self::logInfo("CSV-Datei entspricht dem Strukturmuster: $structurePattern");
        return true;
    }

    public static function matchRow(string $file, array $columnPatterns, ?string $delimiter = null, string $encoding = 'UTF-8', ?array &$matchingRow = null, bool $strict = true): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            self::logInfo("Fehler beim Öffnen der Datei: " . $e->getMessage());
            return false;
        }

        $delimiter ??= self::detectDelimiter($file);

        foreach (self::readLines($file, $delimiter) as $row) {
            if (self::matchColumns($row, $columnPatterns, $encoding, $strict)) {
                $matchingRow = $row;
                self::logInfo("Zeile mit Muster gefunden: " . implode($delimiter, $row));
                return true;
            }
        }

        self::logDebug("Keine passende Zeile in $file gefunden.");
        return false;
    }

    public static function matchColumns(?array $row, ?array $patterns, string $encoding = 'UTF-8', bool $strict = true): bool {
        if (!is_array($row) || empty($row)) {
            self::logDebug("matchColumns erwartet ein Array als erste Zeile.");
            return false;
        } elseif (!is_array($patterns) || empty($patterns)) {
            self::logDebug("matchColumns erwartet ein Array als Muster.");
            return false;
        } elseif (implode('', $row) === '') {
            self::logDebug("Leere Zeile erkannt, kein Vergleich notwendig.");
            return false;
        } elseif ($strict && count($row) != count($patterns)) {
            self::logDebug("Spaltenanzahl (" . count($row) . ") enstpricht nicht der Musteranzahl (" . count($patterns) . ").");
            return false;
        } elseif (!$strict && count($row) < count($patterns)) {
            self::logDebug("Spaltenanzahl (" . count($row) . ") ist kleiner als die Musteranzahl (" . count($patterns) . ").");
            return false;
        }

        foreach ($row as $index => $cell) {
            if (!isset($patterns[$index])) break;
            $pattern = $patterns[$index];

            if ($pattern === '*') continue;

            // Encoding berücksichtigen
            $cellUtf8 = mb_convert_encoding($cell ?? '', 'UTF-8', $encoding);
            $patternQuoted = preg_quote($pattern, '/');

            if (!preg_match("/^$patternQuoted/", $cell) && !preg_match("/^$patternQuoted/", $cellUtf8)) {
                self::logDebug("Muster nicht gefunden: »" . $patternQuoted . "« in Spalte[$index] = »" . $cell . "«");
                return false;
            }
        }

        self::logDebug("Alle Muster erfolgreich in den Spalten gefunden.");
        return true;
    }

    /**
     * Prüft eine CSV-Zeile gegen ein Strukturmuster.
     *
     * @param array $row   Die CSV-Zeile als Array.
     * @param string $patterns Ein Strukturmuster (z. B. "dbkti").
     * @param int $columns   Erwartete Spaltenanzahl (optional).
     */
    public static function checkStructure(array $row, string $patterns, ?int $columns = null, bool $strict = true): bool {
        if (!is_null($columns) && count($row) !== $columns) {
            self::logDebug("Strukturprüfung fehlgeschlagen: erwartet $columns Spalten, erhalten: " . count($row));
            return false;
        } elseif ($strict && count($row) != strlen($patterns)) {
            self::logDebug("Strukturprüfung fehlgeschlagen: erwartet " . strlen($patterns) . " Spalten, erhalten: " . count($row));
            return false;
        } elseif (!$strict && count($row) < strlen($patterns)) {
            self::logDebug("Strukturprüfung fehlgeschlagen: erwartet mindestens " . strlen($patterns) . " Spalten, erhalten: " . count($row));
            return false;
        }

        foreach (str_split($patterns) as $index => $symbol) {
            $wert = $row[$index] ?? '';

            // Optionales Datum
            if ($symbol === 'D' && empty(trim($wert))) {
                continue;
            }

            if (!Validator::validateBySymbol($symbol, $wert)) {
                self::logDebug("Spalte $index entspricht nicht dem erwarteten Musterzeichen '$symbol' – Wert: '$wert'");
                return false;
            }
        }

        self::logDebug("Strukturprüfung erfolgreich für Muster: '$patterns'");
        return true;
    }
}