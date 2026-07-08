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
use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Helper\Data\CSV\StringHelper;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use Generator;
use Throwable;

/**
 * CSV-Datei-Helfer für schnelle Validierung und Struktur-Checks.
 *
 * Diese Klasse ist für effiziente Datei-Level-Operationen optimiert:
 * - Delimiter-Erkennung
 * - Struktur-Validierung
 * - Header-Pattern-Matching
 *
 * Für vollständiges CSV-Parsing zu Document-Objekten nutze CSVDocumentParser.
 * Für String-Level-Operationen nutze CSV\StringHelper.
 *
 * @see \CommonToolkit\Parsers\CSVDocumentParser Für vollständiges Parsing
 * @see \CommonToolkit\Helper\Data\CSV\StringHelper Für String-Operationen
 */
class CsvFile extends HelperAbstract {
    protected static array $commonDelimiters = [',', ';', "\t", '|'];
    protected static string $defaultEnclosure = '"';

    /**
     * Liest eine CSV-Datei und gibt die Zeilen als Generator zurück.
     * Nutzt intern DataLine für konsistentes Parsing mit dem restlichen CSV-System.
     *
     * @param string $file       Der Pfad zur CSV-Datei.
     * @param string $delimiter  Das Trennzeichen.
     * @return Generator<int, array<string>> Generator mit geparsten Zeilen als String-Arrays
     */
    private static function readLines(string $file, string $delimiter): Generator {
        foreach (self::readLinesAsDataLine($file, $delimiter) as $dataLine) {
            $row = array_map(fn ($f) => $f->getValue(), $dataLine->getFields());
            if (!empty(array_filter($row))) {
                yield $row;
            }
        }
    }

    /**
     * Liest eine CSV-Datei und gibt DataLine-Objekte als Generator zurück.
     * Behandelt Multi-Line-Felder korrekt.
     *
     * @param string $file       Der Pfad zur CSV-Datei.
     * @param string $delimiter  Das Trennzeichen.
     * @return Generator<int, DataLine> Generator mit DataLine-Objekten
     */
    private static function readLinesAsDataLine(string $file, string $delimiter): Generator {
        $buffer = '';

        foreach (File::readLines($file, false) as $line) {
            $buffer .= ($buffer !== '' ? "\n" : '') . $line;

            // Prüfe ob die Zeile komplett ist (alle Quotes geschlossen)
            if (!StringHelper::hasMultilineFields($buffer, $delimiter, self::$defaultEnclosure)) {
                if (trim($buffer) !== '') {
                    yield DataLine::fromString($buffer, $delimiter, self::$defaultEnclosure);
                }
                $buffer = '';
            }
        }

        // Rest-Buffer verarbeiten
        if (trim($buffer) !== '') {
            yield DataLine::fromString($buffer, $delimiter, self::$defaultEnclosure);
        }
    }

    /**
     * Löst den Dateipfad auf und erkennt den Delimiter.
     *
     * @param string $file           Der Pfad zur CSV-Datei.
     * @param string|null $delimiter Das Trennzeichen (optional, wird erkannt wenn null).
     * @return array{0: string, 1: string} [aufgelöster Pfad, Delimiter]
     */
    private static function resolveAndDetect(string $file, ?string $delimiter = null): array {
        $file = self::resolveFile($file);
        $delimiter ??= self::detectDelimiter($file);
        return [$file, $delimiter];
    }

    /**
     * Erkennt das Trennzeichen einer CSV-Datei anhand der häufigsten Vorkommen.
     *
     * @param string $file      Der Pfad zur CSV-Datei.
     * @param int $maxLines     Anzahl der zu prüfenden Zeilen (Standard: 10).
     */
    public static function detectDelimiter(string $file, int $maxLines = 10): string {
        $file = self::resolveFile($file);

        $lines = [];
        foreach (File::readLines($file, true, $maxLines) as $line) {
            $lines[] = $line;
        }

        // String-basierte Kernlogik teilen; '' = kein Treffer → dateispezifische Exception.
        $detectedDelimiter = StringHelper::detectDelimiter(
            implode("\n", $lines),
            self::$commonDelimiters,
            $maxLines,
            ''
        );

        if ($detectedDelimiter === '') {
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
        [$file, $delimiter] = self::resolveAndDetect($file, $delimiter);
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
            'RowCount' => $rowCount,
            'ColumnCount' => $columnCount,
            'Delimiter' => $delimiter,
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
            self::logDebug("CSV-Datei nicht gefunden oder ungültig: " . $e->getMessage());
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
            return self::logDebugAndReturn(false, "CSV-Datei nicht gefunden oder ungültig: " . $e->getMessage());
        }

        $delimiter ??= self::detectDelimiter($file);

        $lines = self::readLines($file, $delimiter);
        $header = $lines->current();

        // @phpstan-ignore identical.alwaysFalse (Generator kann bei leerer Datei null liefern)
        if ($header === null) {
            return self::logDebugAndReturn(false, "Header konnte nicht gelesen werden: $file");
        }

        $headerValid = empty(array_diff($headerPattern, $header)) && empty(array_diff($header, $headerPattern));
        if (!$headerValid) {
            return self::logDebugAndReturn(false, "Header stimmt nicht überein. Erwartet: " . implode(',', $headerPattern) . " / Gefunden: " . implode(',', $header));
        }

        if ($wellFormed) {
            $lines->next();
            while ($lines->valid()) {
                $row = $lines->current();
                if (count($row) !== count($header)) {
                    return self::logDebugAndReturn(false, "Datenzeile hat nicht die gleiche Anzahl Spalten wie der Header.");
                }
                $lines->next();
            }
        }

        return true;
    }

    /**
     * Überprüft die Struktur einer CSV-Datei anhand eines Strukturmusters.
     *
     * Die Zeilen-Prüfung übernimmt {@see StringHelper::checkStructure()}.
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
            [$file, $delimiter] = self::resolveAndDetect($file, $delimiter);
        } catch (Throwable $e) {
            return self::logDebugAndReturn(false, "Fehler beim Öffnen der Datei: " . $e->getMessage());
        }

        foreach (self::readLines($file, $delimiter) as $row) {
            if (!StringHelper::checkStructure($row, $structurePattern, $expectedColumns, $strict)) {
                return self::logDebugAndReturn(false, "Strukturprüfung fehlgeschlagen bei Zeile: " . implode($delimiter, $row));
            }

            if (!$checkAllRows) {
                break;
            }
        }

        return self::logDebugAndReturn(true, "CSV-Datei entspricht dem Strukturmuster: $structurePattern");
    }

    /**
     * Sucht eine Zeile in einer CSV-Datei, die mit den angegebenen Mustern übereinstimmt.
     *
     * Die Zeilen-Prüfung übernimmt {@see StringHelper::matchColumns()}.
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
            [$file, $delimiter] = self::resolveAndDetect($file, $delimiter);
        } catch (Throwable $e) {
            return self::logDebugAndReturn(false, "Fehler beim Öffnen der Datei: " . $e->getMessage());
        }

        foreach (self::readLines($file, $delimiter) as $row) {
            if (StringHelper::matchColumns($row, $columnPatterns, $encoding, $strict)) {
                $matchingRow = $row;
                return self::logDebugAndReturn(true, "Zeile mit Muster gefunden: " . implode($delimiter, $row));
            }
        }

        return self::logDebugAndReturn(false, "Keine passende Zeile in $file gefunden.");
    }

    /**
     * Erkennt, ob die CSV-Zeilen überwiegend mit mehrfach gesetztem Enclosure formatiert sind.
     *
     * @param string $file        Pfad zur CSV-Datei.
     * @param string|null $delimiter  Trennzeichen (optional, Standard: auto-detect).
     * @param int $maxLines       Anzahl der zu prüfenden Zeilen (Standard: 5).
     * @param int $enclosureRepeat Wie oft das Enclosure wiederholt wird (Standard: 2 für doppelt).
     */
    public static function hasRepeatedEnclosureColumns(string $file, ?string $delimiter = null, int $maxLines = 5, int $enclosureRepeat = 2): bool {
        [$file, $delimiter] = self::resolveAndDetect($file, $delimiter);

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
            [$file, $delimiter] = self::resolveAndDetect($file, $delimiter);

            $count = 0;
            foreach (self::readLines($file, $delimiter) as $_) {
                $count++;
            }

            $dataRows = $hasHeader ? max(0, $count - 1) : $count;
            return self::logDebugAndReturn($dataRows, "Anzahl der Datenzeilen in $file: $dataRows (Header: " . ($hasHeader ? "ja" : "nein") . ")");
        } catch (Throwable $e) {
            return self::logErrorAndReturn(0, "Fehler beim Ermitteln der Datenzeilen: " . $e->getMessage());
        }
    }

    /**
     * Gibt das Standard-Enclosure-Zeichen zurück.
     *
     * @return string Das Enclosure-Zeichen (Standard: ")
     */
    public static function getDefaultEnclosure(): string {
        return self::$defaultEnclosure;
    }

    /**
     * Gibt die unterstützten Delimiter zurück.
     *
     * @return array<string> Liste der unterstützten Delimiter
     */
    public static function getSupportedDelimiters(): array {
        return self::$commonDelimiters;
    }
}
