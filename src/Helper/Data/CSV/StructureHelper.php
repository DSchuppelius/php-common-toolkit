<?php
/*
 * Created on   : Mon Apr 07 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StructureHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data\CSV;

use ERRORToolkit\Traits\ErrorLog;

/**
 * Helper-Klasse für CSV-Strukturanalyse und -Validierung.
 *
 * Erkennt Spaltenanzahl, Delimiter und Datentypen pro Spalte,
 * um strukturelle Kompatibilität von CSV-Dateien zu prüfen.
 */
final class StructureHelper {
    use ErrorLog;

    /** Spaltentyp-Konstanten */
    public const TYPE_DATE = 'date';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_TEXT = 'text';
    public const TYPE_EMPTY = 'empty';
    public const TYPE_MIXED = 'mixed';

    /** Maximale Zeilen für Typerkennung (Sampling) */
    private const MAX_SAMPLE_ROWS = 50;

    /** Schwellenwert: Mindestanteil eines Typs um als dominant zu gelten */
    private const TYPE_DOMINANCE_THRESHOLD = 0.6;

    /**
     * Analysiert die Struktur eines CSV-Inhalts.
     *
     * @param string $content CSV-Inhalt
     * @param int $headerRows Anzahl der Header-Zeilen (Standard: 1)
     * @return array{
     *     delimiter: string,
     *     columnCount: int,
     *     headerRows: int,
     *     headerFields: string[],
     *     columnTypes: string[],
     *     dataRowCount: int,
     *     consistent: bool,
     *     inconsistentRows: int[]
     * }
     */
    public static function analyze(string $content, int $headerRows = 1): array {
        $delimiter = self::detectDelimiter($content);
        $lines = StringHelper::splitCsvByLogicalLine($content);

        // Leere Zeilen am Ende entfernen
        while (!empty($lines) && trim(end($lines)) === '') {
            array_pop($lines);
        }

        if (empty($lines)) {
            return [
                'delimiter' => $delimiter,
                'columnCount' => 0,
                'headerRows' => $headerRows,
                'headerFields' => [],
                'columnTypes' => [],
                'dataRowCount' => 0,
                'consistent' => true,
                'inconsistentRows' => [],
            ];
        }

        // Header extrahieren
        $headerFields = [];
        $columnCount = 0;
        if ($headerRows > 0) {
            $headerFields = StringHelper::extractFields($lines[0], $delimiter);
            $columnCount = count($headerFields);
        }

        // Datenzeilen analysieren
        $dataLines = array_slice($lines, $headerRows);
        $dataRowCount = count($dataLines);

        // Wenn kein Header: Spaltenanzahl aus erster Datenzeile
        if ($columnCount === 0 && !empty($dataLines)) {
            $firstDataFields = StringHelper::extractFields($dataLines[0], $delimiter);
            $columnCount = count($firstDataFields);
        }

        // Konsistenzprüfung: Haben alle Zeilen die gleiche Spaltenanzahl?
        $inconsistentRows = [];
        foreach ($dataLines as $rowIndex => $line) {
            $fields = StringHelper::extractFields($line, $delimiter);
            if (count($fields) !== $columnCount) {
                $inconsistentRows[] = $rowIndex + $headerRows + 1; // 1-basierte Zeilennummer
            }
        }

        // Spaltentypen erkennen
        $columnTypes = self::detectColumnTypes($dataLines, $delimiter, $columnCount);

        return [
            'delimiter' => $delimiter,
            'columnCount' => $columnCount,
            'headerRows' => $headerRows,
            'headerFields' => $headerFields,
            'columnTypes' => $columnTypes,
            'dataRowCount' => $dataRowCount,
            'consistent' => empty($inconsistentRows),
            'inconsistentRows' => $inconsistentRows,
        ];
    }

    /**
     * Prüft ob zwei CSV-Strukturen kompatibel sind.
     *
     * @param array $structureA Ergebnis von analyze() für Datei A
     * @param array $structureB Ergebnis von analyze() für Datei B
     * @return array{
     *     compatible: bool,
     *     reasons: string[],
     *     columnCountMatch: bool,
     *     delimiterMatch: bool,
     *     headerMatch: bool,
     *     typeCompatibility: float
     * }
     */
    public static function checkCompatibility(array $structureA, array $structureB): array {
        $reasons = [];
        $columnCountMatch = $structureA['columnCount'] === $structureB['columnCount'];
        $delimiterMatch = $structureA['delimiter'] === $structureB['delimiter'];

        // Spaltenanzahl muss übereinstimmen
        if (!$columnCountMatch) {
            $reasons[] = sprintf(
                'Unterschiedliche Spaltenanzahl: %d vs. %d',
                $structureA['columnCount'],
                $structureB['columnCount']
            );
        }

        // Delimiter-Warnung (kein Ausschlussgrund, aber Hinweis)
        if (!$delimiterMatch) {
            $reasons[] = sprintf(
                'Unterschiedliche Trennzeichen: "%s" vs. "%s"',
                self::delimiterToName($structureA['delimiter']),
                self::delimiterToName($structureB['delimiter'])
            );
        }

        // Header-Vergleich (nur wenn beide Header haben und gleiche Spaltenanzahl)
        $headerMatch = true;
        if ($columnCountMatch && !empty($structureA['headerFields']) && !empty($structureB['headerFields'])) {
            $headerMatch = self::headersMatch($structureA['headerFields'], $structureB['headerFields']);
            if (!$headerMatch) {
                $reasons[] = 'Unterschiedliche Header-Spalten';
            }
        }

        // Datentyp-Kompatibilität prüfen (nur bei gleicher Spaltenanzahl)
        $typeCompatibility = 1.0;
        if ($columnCountMatch && !empty($structureA['columnTypes']) && !empty($structureB['columnTypes'])) {
            $typeCompatibility = self::calculateTypeCompatibility(
                $structureA['columnTypes'],
                $structureB['columnTypes']
            );
            if ($typeCompatibility < 0.5) {
                $reasons[] = sprintf(
                    'Geringe Datentyp-Kompatibilität: %.0f%% (mindestens 50%% erwartet)',
                    $typeCompatibility * 100
                );
            }
        }

        $compatible = $columnCountMatch && empty($reasons);

        return [
            'compatible' => $compatible,
            'reasons' => $reasons,
            'columnCountMatch' => $columnCountMatch,
            'delimiterMatch' => $delimiterMatch,
            'headerMatch' => $headerMatch,
            'typeCompatibility' => $typeCompatibility,
        ];
    }

    /**
     * Validiert ob mehrere CSV-Inhalte strukturell kompatibel sind.
     *
     * @param string[] $contents CSV-Inhalte
     * @param int $headerRows Anzahl der Header-Zeilen
     * @return array{
     *     compatible: bool,
     *     reasons: string[],
     *     structures: array[]
     * }
     */
    public static function validateMultiple(array $contents, int $headerRows = 1): array {
        if (count($contents) < 2) {
            return ['compatible' => true, 'reasons' => [], 'structures' => []];
        }

        $structures = [];
        foreach ($contents as $index => $content) {
            $structures[$index] = self::analyze($content, $headerRows);
        }

        $reasons = [];
        $referenceStructure = $structures[0];

        for ($i = 1; $i < count($structures); $i++) {
            $compatibility = self::checkCompatibility($referenceStructure, $structures[$i]);
            if (!$compatibility['compatible']) {
                foreach ($compatibility['reasons'] as $reason) {
                    $reasons[] = sprintf('Datei %d vs. Datei %d: %s', 1, $i + 1, $reason);
                }
            }
        }

        return [
            'compatible' => empty($reasons),
            'reasons' => $reasons,
            'structures' => $structures,
        ];
    }

    /**
     * Erkennt den Delimiter einer CSV-Datei anhand des Inhalts.
     *
     * Hinweis: CsvFile::detectDelimiter() ist dateibasiert,
     * hier wird eine String-basierte Erkennung benötigt.
     */
    public static function detectDelimiter(string $content): string {
        $firstLine = strtok($content, "\n") ?: '';
        // Reset strtok state
        strtok('', '');

        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach (array_keys($delimiters) as $d) {
            $delimiters[$d] = substr_count($firstLine, $d);
        }

        arsort($delimiters);
        $detected = array_key_first($delimiters);

        return $delimiters[$detected] > 0 ? $detected : ';';
    }

    /**
     * Erkennt die Datentypen der Spalten basierend auf Sampling der Datenzeilen.
     *
     * @param string[] $dataLines Datenzeilen (ohne Header)
     * @param string $delimiter CSV-Trennzeichen
     * @param int $columnCount Erwartete Spaltenanzahl
     * @return string[] Datentyp pro Spalte (TYPE_DATE, TYPE_NUMERIC, TYPE_TEXT, TYPE_EMPTY, TYPE_MIXED)
     */
    private static function detectColumnTypes(array $dataLines, string $delimiter, int $columnCount): array {
        if ($columnCount === 0 || empty($dataLines)) {
            return [];
        }

        // Sampling: Nur erste MAX_SAMPLE_ROWS Zeilen analysieren
        $sampleLines = array_slice($dataLines, 0, self::MAX_SAMPLE_ROWS);
        $sampleCount = count($sampleLines);

        // Typ-Zähler pro Spalte initialisieren
        $typeCounts = [];
        for ($col = 0; $col < $columnCount; $col++) {
            $typeCounts[$col] = [
                self::TYPE_DATE => 0,
                self::TYPE_NUMERIC => 0,
                self::TYPE_TEXT => 0,
                self::TYPE_EMPTY => 0,
            ];
        }

        // Jeden Wert analysieren
        foreach ($sampleLines as $line) {
            $fields = StringHelper::extractFields($line, $delimiter);
            for ($col = 0; $col < $columnCount; $col++) {
                $value = trim($fields[$col] ?? '');
                $type = self::detectValueType($value);
                $typeCounts[$col][$type]++;
            }
        }

        // Dominanten Typ pro Spalte bestimmen
        $columnTypes = [];
        for ($col = 0; $col < $columnCount; $col++) {
            $columnTypes[$col] = self::determineDominantType($typeCounts[$col], $sampleCount);
        }

        return $columnTypes;
    }

    /**
     * Erkennt den Datentyp eines einzelnen Wertes.
     */
    private static function detectValueType(string $value): string {
        if ($value === '') {
            return self::TYPE_EMPTY;
        }

        // Datum: DD.MM.YYYY, DD.MM.YY, YYYY-MM-DD, DD/MM/YYYY
        if (preg_match('/^\d{2}[\.\/\-]\d{2}[\.\/\-]\d{2,4}$/', $value)) {
            return self::TYPE_DATE;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return self::TYPE_DATE;
        }

        // Numerisch: Ganzzahlen, Dezimalzahlen (deutsch/englisch), Beträge mit Vorzeichen
        if (preg_match('/^[+\-]?\d{1,3}(\.\d{3})*(,\d+)?$/', $value)) {
            return self::TYPE_NUMERIC; // Deutsches Format: 1.234,56
        }
        if (preg_match('/^[+\-]?\d{1,3}(,\d{3})*(\.\d+)?$/', $value)) {
            return self::TYPE_NUMERIC; // Englisches Format: 1,234.56
        }
        if (preg_match('/^[+\-]?\d+([,\.]\d+)?$/', $value)) {
            return self::TYPE_NUMERIC; // Einfache Zahlen
        }

        return self::TYPE_TEXT;
    }

    /**
     * Bestimmt den dominanten Typ einer Spalte basierend auf Zählern.
     *
     * @param array<string, int> $counts Typ-Zähler
     * @param int $totalRows Gesamtanzahl der Zeilen
     */
    private static function determineDominantType(array $counts, int $totalRows): string {
        if ($totalRows === 0) {
            return self::TYPE_EMPTY;
        }

        // Leere Werte ignorieren bei der Typ-Bestimmung
        $nonEmptyCount = $totalRows - $counts[self::TYPE_EMPTY];
        if ($nonEmptyCount === 0) {
            return self::TYPE_EMPTY;
        }

        // Prüfe ob ein Typ dominiert (>= Schwellenwert der nicht-leeren Werte)
        foreach ([self::TYPE_DATE, self::TYPE_NUMERIC, self::TYPE_TEXT] as $type) {
            $ratio = $counts[$type] / $nonEmptyCount;
            if ($ratio >= self::TYPE_DOMINANCE_THRESHOLD) {
                return $type;
            }
        }

        return self::TYPE_MIXED;
    }

    /**
     * Berechnet die Datentyp-Kompatibilität zwischen zwei Spaltentyp-Arrays.
     *
     * @param string[] $typesA Spaltentypen Datei A
     * @param string[] $typesB Spaltentypen Datei B
     * @return float Kompatibilität von 0.0 (komplett unterschiedlich) bis 1.0 (identisch)
     */
    private static function calculateTypeCompatibility(array $typesA, array $typesB): float {
        $count = min(count($typesA), count($typesB));
        if ($count === 0) {
            return 1.0;
        }

        $matches = 0;
        for ($i = 0; $i < $count; $i++) {
            $typeA = $typesA[$i] ?? self::TYPE_TEXT;
            $typeB = $typesB[$i] ?? self::TYPE_TEXT;

            if ($typeA === $typeB) {
                $matches++;
            } elseif ($typeA === self::TYPE_EMPTY || $typeB === self::TYPE_EMPTY) {
                // Leere Spalten sind mit allem kompatibel
                $matches++;
            } elseif ($typeA === self::TYPE_MIXED || $typeB === self::TYPE_MIXED) {
                // Gemischte Spalten sind teilweise kompatibel
                $matches += 0.5;
            }
        }

        return $matches / $count;
    }

    /**
     * Prüft ob zwei Header-Zeilen übereinstimmen.
     *
     * @param string[] $headerA Header-Felder Datei A
     * @param string[] $headerB Header-Felder Datei B
     * @return bool True wenn mindestens 80% der Header-Spalten übereinstimmen
     */
    private static function headersMatch(array $headerA, array $headerB): bool {
        if (count($headerA) !== count($headerB)) {
            return false;
        }

        $count = count($headerA);
        if ($count === 0) {
            return true;
        }

        $matches = 0;
        foreach ($headerA as $i => $fieldA) {
            if (isset($headerB[$i]) && strtolower(trim($fieldA)) === strtolower(trim($headerB[$i]))) {
                $matches++;
            }
        }

        return ($matches / $count) >= 0.8;
    }

    /**
     * Gibt einen lesbaren Namen für ein Trennzeichen zurück.
     */
    private static function delimiterToName(string $delimiter): string {
        return match ($delimiter) {
            ';' => 'Semikolon',
            ',' => 'Komma',
            "\t" => 'Tab',
            '|' => 'Pipe',
            default => $delimiter,
        };
    }
}
