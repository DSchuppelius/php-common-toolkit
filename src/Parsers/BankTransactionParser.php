<?php
/*
 * Created on   : Sun Dec 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\CSV\{DataLine, HeaderField};
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Entities\DATEV\Header\ASCII\BankTransactionHeaderLine;
use CommonToolkit\Entities\DATEV\Header\ASCII\BankTransactionHeaderDefinition;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Parsers\CSVDocumentParser;
use RuntimeException;

/**
 * Parser für DATEV ASCII-Weiterverarbeitungsdateien für Bankkontoumsätze.
 * 
 * Eigenständiger Parser für das spezielle ASCII-Format ohne MetaHeader.
 * Dieses Format ist unabhängig vom Standard DATEV-Import/Export-System.
 * 
 * @see https://help-center.apps.datev.de/documents/9226961
 */
class BankTransactionParser {

    /**
     * Einfache Error-Logging Methode.
     */
    private static function logError(string $message): void {
        error_log('[BankTransactionParser] ' . $message);
    }

    /**
     * Parst eine ASCII-Weiterverarbeitungsdatei aus einem String.
     *
     * @param string $csv Der CSV-Inhalt
     * @param string $delimiter CSV-Trennzeichen (Standard: Semikolon)
     * @param string $enclosure CSV-Textbegrenzer (Standard: Anführungszeichen)
     * @param bool $hasHeader Wird ignoriert - ASCII-Dateien haben keinen Header
     * @return BankTransaction Das geparste BankTransaction-Dokument
     * @throws RuntimeException Bei Parsing-Fehlern oder ungültigem Format
     */
    public static function fromString(
        string $csv,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $hasHeader = false
    ): BankTransaction {
        $lines = explode("\n", trim($csv));

        if (empty($lines) || empty(trim($lines[0]))) {
            static::logError('Leere ASCII-Weiterverarbeitungsdatei');
            throw new RuntimeException('Leere ASCII-Weiterverarbeitungsdatei');
        }

        // Erkenne Feldanzahl aus der ersten Zeile für detaillierte Validierung
        $firstDataLine = DataLine::fromString($lines[0], $delimiter, $enclosure);
        $fieldCount = count($firstDataLine->getFields());

        // Erst Feldanzahl-Validierung
        if ($fieldCount < 7 || $fieldCount > 34) {
            static::logError("Falsche Anzahl Felder: $fieldCount (erwartet: 7-34)");
            throw new RuntimeException('Falsche Anzahl Felder (erwartet: 7-34 Felder)');
        }

        // Dann detaillierte Pflichtfeld-Validierung (wird bei leeren Pflichtfeldern fehlschlagen)
        $values = array_map(fn($field) => $field->getValue(), $firstDataLine->getFields());
        $headerDef = new BankTransactionHeaderDefinition();
        if (!$headerDef->matches($values)) {
            static::logError('ASCII-Weiterverarbeitungsdatei-Validierung fehlgeschlagen');
            throw new RuntimeException('ASCII-Weiterverarbeitungsdatei-Validierung fehlgeschlagen');
        }

        // Dann allgemeine Format-Validierung
        if (!self::isValidBankTransactionFormat($lines[0], $delimiter, $enclosure)) {
            static::logError('Ungültiges ASCII-Weiterverarbeitungsformat erkannt');
            throw new RuntimeException('Datei entspricht nicht dem ASCII-Weiterverarbeitungsformat');
        }

        // Header erstellen (virtuell, da ASCII-Dateien keinen Header haben)
        $headerFields = [];
        foreach (BankTransactionHeaderField::ordered() as $field) {
            $headerFields[] = new HeaderField($field->value, $enclosure);
        }
        $header = new BankTransactionHeaderLine($headerFields, $delimiter, $enclosure);

        // Alle Zeilen als Datenzeilen direkt parsen (ohne CSV-Document)
        $dataRows = [];
        $previousDate = null;

        foreach ($lines as $lineIndex => $line) {
            if (empty(trim($line))) {
                continue; // Leere Zeilen überspringen
            }

            $dataLine = DataLine::fromString($line, $delimiter, $enclosure);
            $values = array_map(fn($field) => $field->getValue(), $dataLine->getFields());

            // Feldanzahl prüfen
            if (count($values) !== $fieldCount) {
                throw new RuntimeException('Falsche Anzahl Felder in Zeile ' . ($lineIndex + 1));
            }

            // Datum-Sortierung prüfen (falls Buchungsdatum vorhanden - Index 5)
            if (isset($values[5]) && !empty(trim($values[5], '"'))) {
                $currentDate = trim($values[5], '"');
                if ($previousDate !== null && $currentDate < $previousDate) {
                    throw new RuntimeException('Buchungsdaten sind nicht aufsteigend sortiert');
                }
                $previousDate = $currentDate;
            }

            $dataRows[] = $dataLine;
        }

        // BankTransaction-Document ohne MetaHeader und ohne Header erstellen
        return new BankTransaction($dataRows);
    }

    /**
     * Parst eine ASCII-Weiterverarbeitungsdatei aus einer Datei.
     *
     * @param string $filePath Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen (Standard: Semikolon)
     * @param string $enclosure CSV-Textbegrenzer (Standard: Anführungszeichen)
     * @param bool $hasHeader Wird ignoriert - ASCII-Dateien haben keinen Header
     * @return BankTransaction Das geparste BankTransaction-Dokument
     * @throws RuntimeException Bei Dateizugriff-Fehlern oder Parsing-Fehlern
     */
    public static function fromFile(
        string $filePath,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $hasHeader = false
    ): BankTransaction {
        $content = File::read($filePath);

        return self::fromString($content, $delimiter, $enclosure, $hasHeader);
    }

    /**
     * Prüft ob es sich um eine gültige ASCII-Weiterverarbeitungsdatei handelt.
     */
    public static function isValidBankTransactionFormat(string $firstLine, string $delimiter = ';', string $enclosure = '"'): bool {
        try {
            $dataLine = DataLine::fromString($firstLine, $delimiter, $enclosure);
            $values = array_map(fn($field) => $field->getValue(), $dataLine->getFields());

            // ASCII-Weiterverarbeitungsdateien haben mindestens 7 Felder (bis Umsatz), maximal 34 Felder
            $fieldCount = count($values);
            if ($fieldCount < 7 || $fieldCount > 34) {
                return false;
            }

            // Format-Erkennung über HeaderDefinition
            $definition = new BankTransactionHeaderDefinition();
            return $definition->matches($values);
        } catch (\Exception $e) {
            static::logError('Format-Validierung fehlgeschlagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Analysiert eine CSV-Datei und bestimmt ob es sich um ASCII-Weiterverarbeitungsformat handelt.
     */
    public static function analyzeFormat(string $csvContent, string $delimiter = ';', string $enclosure = '"'): array {
        $lines = explode("\n", trim($csvContent));

        if (empty($lines) || empty(trim($lines[0]))) {
            return [
                'format_type' => null,
                'supported' => false,
                'error' => 'Leere Datei',
                'has_field_header' => false,
                'currencies' => [],
            ];
        }

        $firstLine = trim($lines[0]);
        if (!self::isValidBankTransactionFormat($firstLine, $delimiter, $enclosure)) {
            return [
                'format_type' => null,
                'supported' => false,
                'error' => 'Ungültiges ASCII-Weiterverarbeitungsformat',
                'has_field_header' => false,
                'currencies' => [],
            ];
        }

        // Erkenne Feldanzahl aus der ersten Zeile
        $firstDataLine = DataLine::fromString($firstLine, $delimiter, $enclosure);
        $fieldCount = count($firstDataLine->getFields());

        // Detailanalyse aller Zeilen
        $validRows = 0;
        $invalidRows = [];
        $currencies = ['EUR']; // Standard

        foreach ($lines as $index => $line) {
            if (empty(trim($line))) {
                continue;
            }

            try {
                $dataLine = DataLine::fromString($line, $delimiter, $enclosure);
                $rowFieldCount = count($dataLine->getFields());

                if ($rowFieldCount >= 7 && $rowFieldCount <= 34) {
                    $validRows++;
                } else {
                    $invalidRows[] = $index + 1;
                }
            } catch (\Exception $e) {
                $invalidRows[] = $index + 1;
            }
        }

        return [
            'format_type' => 'ASCII-Weiterverarbeitungsdatei',
            'supported' => true,
            'has_field_header' => false,
            'has_meta_header' => false,
            'currencies' => $currencies,
            'field_count' => $fieldCount,
            'line_count' => count($lines),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];
    }

    /**
     * Konvertiert ein BankTransaction-Dokument zurück zu CSV.
     */
    public static function toCSV(BankTransaction $document, string $delimiter = ';', string $enclosure = '"'): string {
        $lines = [];

        foreach ($document->getRows() as $row) {
            $fieldValues = [];
            foreach ($row->getFields() as $field) {
                // Verwende toString() um Original-Quoting beizubehalten
                $fieldValues[] = $field->toString($enclosure);
            }
            $lines[] = implode($delimiter, $fieldValues);
        }

        return implode("\n", $lines);
    }

    /**
     * Erstellt eine Beispiel-ASCII-Weiterverarbeitungsdatei.
     */
    public static function createSampleFile(): string {
        return implode("\n", [
            '"70030000";"1234567";"433";"29.12.15";"29.12.15";"29.12.15";10.00;"HANS MUSTERMANN";"";"80550000";"7654321";"Kd.Nr. 12345";"RECHNUNG v. 12.12.15";"";"";"";""',
            '"70030000";"1234567";"434";"30.12.15";"30.12.15";"30.12.15";-25.50;"FIRMA ABC GMBH";"MÜNCHEN";"70150000";"1111111";"Miete Januar";"Objekt Muster";"";"";"";""',
        ]);
    }

    /**
     * Prüft ob eine Datei eine ASCII-Weiterverarbeitungsdatei ist.
     */
    public static function canParse(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            return false;
        }

        return self::isValidBankTransactionFormat(rtrim($firstLine, "\r\n"));
    }

    /**
     * Liefert unterstützte Dateierweiterungen.
     */
    public static function getSupportedExtensions(): array {
        return ['.csv', '.txt', '.asc'];
    }

    /**
     * Liefert eine Beschreibung des Formats.
     */
    public static function getFormatDescription(): string {
        return 'DATEV ASCII-Weiterverarbeitungsdatei für Bankkontoumsätze (7-34 Felder ohne Header)';
    }
}
