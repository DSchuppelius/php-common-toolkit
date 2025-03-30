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
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;

class CsvFile extends HelperAbstract {
    protected static array $commonDelimiters = [',', ';', "\t", '|'];

    public static function detectDelimiter(string $file, int $maxLines = 10): string {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Die Datei $file existiert nicht oder ist nicht lesbar.");
            throw new FileNotFoundException("Die Datei $file existiert nicht oder ist nicht lesbar.");
        }

        $file = File::getRealPath($file);

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
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $file = File::getRealPath($file);

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::logError("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $rowCount = 0;
        $columnCount = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!empty(array_filter($row))) { // Leere Zeilen ignorieren
                $rowCount++;
                $columnCount = max($columnCount, count($row));
            }
        }
        fclose($handle);

        return [
            'RowCount' => $rowCount,
            'ColumnCount' => $columnCount,
            'Delimiter' => $delimiter
        ];
    }

    public static function isWellFormed(string $file, ?string $delimiter = null): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $file = File::getRealPath($file);

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::logError("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $columnCount = null;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!empty(array_filter($row))) {
                if (is_null($columnCount)) {
                    $columnCount = count($row);
                } elseif (count($row) !== $columnCount) {
                    fclose($handle);
                    return false;
                }
            }
        }
        fclose($handle);
        return true;
    }

    public static function isValid(string $file, array $headerPattern, ?string $delimiter = null, bool $welformed = false): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $file = File::getRealPath($file);

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::logError("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $header = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        if ($header === false) {
            self::logError("Fehler beim Lesen der Kopfzeile in der CSV-Datei: $file");
            throw new Exception("Fehler beim Lesen der Kopfzeile in der CSV-Datei: $file");
        }

        // Header-Check
        $headerValid = empty(array_diff($headerPattern, $header)) && empty(array_diff($header, $headerPattern));

        // Falls zusätzlich `isWellFormed()` geprüft werden soll
        if ($welformed) {
            return $headerValid && self::isWellFormed($file, $delimiter);
        }

        return $headerValid;
    }
}
