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
use CommonToolkit\Helper\Data\CSV\StringHelper;
use CommonToolkit\Helper\Data\Validator;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use Generator;
use RuntimeException;
use Throwable;

class CsvFile extends HelperAbstract {
    protected static array $commonDelimiters = [',', ';', "\t", '|'];
    protected static string $defaultEnclosure = '"';
    protected static string $defaultEscape = '\\';

    /**
     * Liest eine CSV-Datei und gibt die Zeilen als Generator zurück.
     *
     * @param string $file       Der Pfad zur CSV-Datei.
     * @param string $delimiter  Das Trennzeichen (Standard: ',').
     */
    private static function readLines(string $file, string $delimiter): Generator {
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new RuntimeException("CSV-Datei konnte nicht geöffnet werden: $file");
        }

        while (($row = fgetcsv($handle, 0, $delimiter, self::$defaultEnclosure, self::$defaultEscape)) !== false) {
            if (!empty(array_filter($row))) {
                yield $row;
            }
        }

        fclose($handle);
    }

    /**
     * Liest eine CSV-Datei und gibt die Zeilen als Generator zurück.
     *
     * @param string $file       Der Pfad zur CSV-Datei.
     * @param string $delimiter  Das Trennzeichen (Standard: ',').
     */
    public static function detectDelimiter(string $file, int $maxLines = 10): string {
        $file = self::resolveFile($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Öffnen der Datei: $file");
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
            self::logErrorAndThrow(Exception::class, "Kein geeignetes Trennzeichen in der Datei $file gefunden.");
        }

        return $detectedDelimiter;
    }

    /**
     * Liest die Metadaten einer CSV-Datei.
     *
     * @param string $file            Der Pfad zur CSV-Datei.
     * @param string|null $delimiter  Das Trennzeichen (optional).
     */
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

    /**
     * Überprüft, ob die CSV-Datei gut geformt ist.
     *
     * @param string $file            Der Pfad zur CSV-Datei.
     * @param string|null $delimiter  Das Trennzeichen (optional).
     */
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
                return self::logDebugAndReturn(false, "Fehlerhafte Zeile $index: Spaltenanzahl $rowLength stimmt nicht mit Header ($columnCount) überein.");
            }
        }

        return true;
    }

    /**
     * Überprüft, ob die CSV-Datei ein gültiges Header-Muster hat.
     *
     * @param string $file            Der Pfad zur CSV-Datei.
     * @param array $headerPattern    Das erwartete Header-Muster.
     * @param string|null $delimiter  Das Trennzeichen (optional).
     * @param bool $wellFormed       Überprüfen, ob die Datei gut geformt ist (Standard: false).
     */
    public static function isValid(string $file, array $headerPattern, ?string $delimiter = null, bool $wellFormed = false): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            return self::logInfoAndReturn(false, "CSV-Datei nicht gefunden oder ungültig: " . $e->getMessage());
        }

        $delimiter ??= self::detectDelimiter($file);

        $lines = self::readLines($file, $delimiter);
        $header = $lines->current();

        if ($header === false) {
            return self::logInfoAndReturn(false, "Header konnte nicht gelesen werden: $file");
        }

        $headerValid = empty(array_diff($headerPattern, $header)) && empty(array_diff($header, $headerPattern));
        if (!$headerValid) {
            return self::logDebugAndReturn(false, "Header stimmt nicht überein. Erwartet: " . implode(',', $headerPattern) . " / Gefunden: " . implode(',', $header));
        }

        if ($wellFormed) {
            foreach ($lines as $index => $row) {
                if (count($row) !== count($header)) {
                    return self::logDebugAndReturn(false, "Zeile $index hat nicht die gleiche Anzahl Spalten wie der Header.");
                }
            }
        }

        return true;
    }

    /**
     * Überprüft die Struktur einer CSV-Datei anhand eines Strukturmusters.
     *
     * @param string $file            Der Pfad zur CSV-Datei.
     * @param string $structurePattern Das Strukturmuster (z. B. "dbkti").
     * @param string|null $delimiter  Das Trennzeichen (optional).
     * @param int|null $expectedColumns Erwartete Spaltenanzahl (optional).
     * @param bool $checkAllRows      Alle Zeilen überprüfen (Standard: false).
     * @param bool $strict           Strikte Übereinstimmung (Standard: true).
     */
    public static function checkStructureFile(string $file, string $structurePattern, ?string $delimiter = null, ?int $expectedColumns = null, bool $checkAllRows = false, bool $strict = true): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            return self::logInfoAndReturn(false, "Fehler beim Öffnen der Datei: " . $e->getMessage());
        }

        $delimiter ??= self::detectDelimiter($file);

        foreach (self::readLines($file, $delimiter) as $row) {
            if (!self::checkStructure($row, $structurePattern, $expectedColumns, $strict)) {
                return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen bei Zeile: " . implode($delimiter, $row));
            }

            if (!$checkAllRows) break;
        }

        return self::logInfoAndReturn(true, "CSV-Datei entspricht dem Strukturmuster: $structurePattern");
    }

    /**
     * Sucht eine Zeile in einer CSV-Datei, die mit den angegebenen Mustern übereinstimmt.
     *
     * @param string $file            Der Pfad zur CSV-Datei.
     * @param array $columnPatterns   Die Muster für die Spalten.
     * @param string|null $delimiter  Das Trennzeichen (optional).
     * @param string $encoding       Die Zeichenkodierung (Standard: 'UTF-8').
     * @param array|null $matchingRow Referenz auf das gefundene Array (optional).
     * @param bool $strict           Strikte Übereinstimmung (Standard: true).
     */
    public static function matchRow(string $file, array $columnPatterns, ?string $delimiter = null, string $encoding = 'UTF-8', ?array &$matchingRow = null, bool $strict = true): bool {
        try {
            $file = self::resolveFile($file);
        } catch (Throwable $e) {
            return self::logInfoAndReturn(false, "Fehler beim Öffnen der Datei: " . $e->getMessage());
        }

        $delimiter ??= self::detectDelimiter($file);

        foreach (self::readLines($file, $delimiter) as $row) {
            if (self::matchColumns($row, $columnPatterns, $encoding, $strict)) {
                $matchingRow = $row;
                return self::logInfoAndReturn(true, "Zeile mit Muster gefunden: " . implode($delimiter, $row));
            }
        }

        return self::logDebugAndReturn(false, "Keine passende Zeile in $file gefunden.");
    }

    /**
     * Prüft, ob die Spalten einer Zeile mit den angegebenen Mustern übereinstimmen.
     *
     * @param array|null $row       Die CSV-Zeile als Array.
     * @param array|null $patterns  Die Muster für die Spalten.
     * @param string $encoding      Die Zeichenkodierung (Standard: 'UTF-8').
     * @param bool $strict         Strikte Übereinstimmung (Standard: true).
     */
    public static function matchColumns(?array $row, ?array $patterns, string $encoding = 'UTF-8', bool $strict = true): bool {
        if (!is_array($row) || empty($row)) {
            return self::logDebugAndReturn(false, "matchColumns erwartet ein Array als erste Zeile.");
        } elseif (!is_array($patterns) || empty($patterns)) {
            return self::logDebugAndReturn(false, "matchColumns erwartet ein Array als Muster.");
        } elseif (implode('', $row) === '') {
            return self::logDebugAndReturn(false, "Leere Zeile erkannt, kein Vergleich notwendig.");
        } elseif ($strict && count($row) != count($patterns)) {
            return self::logDebugAndReturn(false, "Spaltenanzahl (" . count($row) . ") enstpricht nicht der Musteranzahl (" . count($patterns) . ").");
        } elseif (!$strict && count($row) < count($patterns)) {
            return self::logDebugAndReturn(false, "Spaltenanzahl (" . count($row) . ") ist kleiner als die Musteranzahl (" . count($patterns) . ").");
        }

        foreach ($row as $index => $cell) {
            if (!isset($patterns[$index])) break;
            $pattern = $patterns[$index];

            if ($pattern === '*') continue;

            // Encoding berücksichtigen
            $cellUtf8 = mb_convert_encoding($cell ?? '', 'UTF-8', $encoding);
            $patternQuoted = preg_quote($pattern, '/');

            if (!preg_match("/^$patternQuoted/", $cell) && !preg_match("/^$patternQuoted/", $cellUtf8)) {
                return self::logDebugAndReturn(false, "Muster nicht gefunden: »" . $patternQuoted . "« in Spalte[$index] = »" . $cell . "«");
            }
        }

        return self::logDebugAndReturn(true, "Alle Muster erfolgreich in den Spalten gefunden.");
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
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet $columns Spalten, erhalten: " . count($row));
        } elseif ($strict && count($row) != strlen($patterns)) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet " . strlen($patterns) . " Spalten, erhalten: " . count($row));
        } elseif (!$strict && count($row) < strlen($patterns)) {
            return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen: erwartet mindestens " . strlen($patterns) . " Spalten, erhalten: " . count($row));
        }

        foreach (str_split($patterns) as $index => $symbol) {
            $wert = $row[$index] ?? '';

            // Optionales Datum
            if ($symbol === 'D' && empty(trim($wert))) {
                continue;
            }

            if (!Validator::validateBySymbol($symbol, $wert)) {
                return self::logDebugAndReturn(false, "Spalte $index entspricht nicht dem erwarteten Musterzeichen '$symbol' – Wert: '$wert'");
            }
        }

        return self::logDebugAndReturn(true, "Strukturprüfung erfolgreich für Muster: '$patterns'");
    }

    /**
     * Erkennt, ob die CSV-Zeilen überwiegend mit mehrfach gesetztem Enclosure formatiert sind.
     *
     * @param string $file        Pfad zur CSV-Datei.
     * @param string|null $delimiter  Trennzeichen (optional, Standard: auto-detect).
     * @param int $maxLines       Anzahl der zu prüfenden Zeilen (Standard: 5).
     * @param int $enclosureRepeat Wie oft das Enclosure wiederholt wird (Standard: 2 für doppelt).
     * @return bool
     */
    public static function hasRepeatedEnclosureColumns(string $file, ?string $delimiter = null, int $maxLines = 5, int $enclosureRepeat = 2): bool {
        $file = self::resolveFile($file);
        $delimiter ??= self::detectDelimiter($file);

        $checked = 0;
        $hits = 0;

        foreach (File::readLines($file, true, $maxLines) as $line) {
            $checked++;
            if (StringHelper::hasRepeatedEnclosure($line, $delimiter, self::$defaultEnclosure, $enclosureRepeat)) {
                $hits++;
            }
        }

        return $checked > 0 && $hits >= ($checked / 2);
    }

    /**
     * Gibt die Anzahl der Datenzeilen in der CSV-Datei zurück.
     *
     * @param string $file Der Pfad zur CSV-Datei.
     * @param string|null $delimiter Das Trennzeichen (optional).
     * @param bool $hasHeader Gibt an, ob die Datei eine Header-Zeile enthält (Standard: true).
     * @return int Anzahl der Datenzeilen.
     */
    public static function countDataRows(string $file, ?string $delimiter = null, bool $hasHeader = true): int {
        try {
            $meta = self::getMetaData($file, $delimiter);
            $rowCount = $meta['RowCount'];
            $dataRows = $hasHeader ? max(0, $rowCount - 1) : $rowCount;

            return self::logInfoAndReturn($dataRows, "Anzahl der Datenzeilen in $file: $dataRows (Header: " . ($hasHeader ? "ja" : "nein") . ")");
        } catch (Throwable $e) {
            return self::logErrorAndReturn(0, "Fehler beim Ermitteln der Datenzeilen: " . $e->getMessage());
        }
    }
}
