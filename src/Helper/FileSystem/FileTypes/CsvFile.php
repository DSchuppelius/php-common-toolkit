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
use ERRORToolkit\Exceptions\FileNotFoundException;
use Exception;

class CsvFile extends HelperAbstract {
    protected static array $commonDelimiters = [',', ';', "\t", '|'];

    public static function detectDelimiter(string $file): string {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Die Datei $file ist nicht lesbar oder existiert nicht.");
            throw new FileNotFoundException("Die Datei $file ist nicht lesbar oder existiert nicht.");
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            self::$logger->error("Fehler beim Öffnen der Datei: $file");
            throw new Exception("Fehler beim Öffnen der Datei: $file");
        }

        $lineCount = 0;
        $delimiterCounts = array_fill_keys(self::$commonDelimiters, 0);

        while (($line = fgets($handle)) !== false && $lineCount < 5) {
            foreach (self::$commonDelimiters as $delimiter) {
                $delimiterCounts[$delimiter] += substr_count($line, $delimiter);
            }
            $lineCount++;
        }
        fclose($handle);

        arsort($delimiterCounts);
        $detectedDelimiter = key($delimiterCounts);

        if ($delimiterCounts[$detectedDelimiter] === 0) {
            self::$logger->error("Kein geeignetes Trennzeichen in der Datei $file gefunden.");
            throw new Exception("Kein geeignetes Trennzeichen in der Datei $file gefunden.");
        }

        return $detectedDelimiter;
    }

    public static function getMetaData(string $file, ?string $delimiter = null): array {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::$logger->error("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $rowCount = 0;
        $columnCount = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;
            $columnCount = max($columnCount, count($row));
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
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::$logger->error("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $columnCount = null;
        $isWellFormed = true;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (is_null($columnCount)) {
                $columnCount = count($row);
            } elseif (count($row) !== $columnCount) {
                $isWellFormed = false;
                break;
            }
        }
        fclose($handle);

        return $isWellFormed;
    }

    public static function isValid(string $file, array $headerPattern, ?string $delimiter = null): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $delimiter = $delimiter ?? self::detectDelimiter($file);
        $handle = fopen($file, 'r');
        if (!$handle) {
            self::$logger->error("Fehler beim Öffnen der CSV-Datei: $file");
            throw new Exception("Fehler beim Öffnen der CSV-Datei: $file");
        }

        $header = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        if ($header === false) {
            self::$logger->error("Fehler beim Lesen der Kopfzeile in der CSV-Datei: $file");
            throw new Exception("Fehler beim Lesen der Kopfzeile in der CSV-Datei: $file");
        }

        return $header === $headerPattern;
    }
}
