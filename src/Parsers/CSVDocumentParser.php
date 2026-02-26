<?php
/*
 * Created on   : Fri Oct 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\CSV\{HeaderLine, DataLine};
use CommonToolkit\Helper\Data\CSV\StringHelper;
use CommonToolkit\Helper\Data\StringHelper as DataStringHelper;
use CommonToolkit\Contracts\Interfaces\CSV\{LineInterface, FieldInterface};
use CommonToolkit\Entities\CSV\Document;
use CommonToolkit\Helper\FileSystem\File;
use Generator;
use RuntimeException;
use Throwable;

class CSVDocumentParser extends HelperAbstract {

    /**
     * Parst eine CSV-Zeichenkette in ein CSVDocument.
     *
     * @param string $csv Die CSV-Zeichenkette
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist
     * @param string|null $encoding Das Quell-Encoding. Wenn null, wird UTF-8 angenommen.
     * @return Document Das geparste CSV-Dokument
     * @throws RuntimeException Bei Parsing-Fehlern
     */
    public static function fromString(
        string $csv,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true,
        ?string $encoding = null
    ): Document {
        $csv = trim($csv);
        if ($csv === '') {
            static::logErrorAndThrow(RuntimeException::class, 'Leere CSV-Zeichenkette');
        }

        // Encoding-Konvertierung nach UTF-8 für internes Parsing
        $sourceEncoding = $encoding ?? Document::DEFAULT_ENCODING;
        if ($sourceEncoding !== Document::DEFAULT_ENCODING) {
            $csv = DataStringHelper::convertEncoding($csv, $sourceEncoding, Document::DEFAULT_ENCODING);
        }

        $lines = StringHelper::splitCsvByLogicalLine($csv, $enclosure);
        if ($lines === [] || $lines === false) {
            static::logErrorAndThrow(RuntimeException::class, 'CSVDocumentParser::fromString() – keine gültigen Zeilen erkannt');
        }

        $builder = new CSVDocumentBuilder($delimiter, $enclosure, null, $sourceEncoding);

        try {
            $lineNumber = 0;
            if ($hasHeader) {
                $headerLine = array_shift($lines);
                $lineNumber++;
                if ($headerLine === null) {
                    static::logErrorAndThrow(RuntimeException::class, 'Header-Zeile fehlt');
                }
                $builder->setHeader(self::parseHeaderLine($headerLine, $delimiter, $enclosure, $lineNumber));
            }

            foreach ($lines as $line) {
                $lineNumber++;
                if (trim($line) === '') {
                    continue;
                }
                $builder->addRow(self::parseDataLine($line, $delimiter, $enclosure, $lineNumber));
            }
        } catch (Throwable $e) {
            static::logErrorAndThrow(RuntimeException::class, "Fehler beim Parsen der CSV: " . $e->getMessage());
        }

        $result = $builder->build();
        if (!$result->isConsistent()) {
            static::logErrorAndThrow(RuntimeException::class, 'Inkonsistente CSV-Daten: Ungleiche Anzahl an Feldern in den Zeilen');
        }
        return $result;
    }

    /**
     * Parst eine CSV-Datei in ein CSVDocument.
     * Optimiert: Nutzt direktes Streaming statt Array-Zwischenspeicherung.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist
     * @param int $startLine Ab welcher Zeile gelesen werden soll (1-basiert)
     * @param int|null $maxLines Maximale Anzahl zu lesender Zeilen (null = alle)
     * @param bool $skipEmpty Leere Zeilen überspringen
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding (z.B. 'CP850', 'CP437')
     * @return Document Das geparste CSV-Dokument
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function fromFile(
        string $file,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true,
        int $startLine = 1,
        ?int $maxLines = null,
        bool $skipEmpty = false,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): Document {
        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $builder = new CSVDocumentBuilder($delimiter, $enclosure);
        $linesGenerator = self::createLinesGenerator($file, $skipEmpty, $startLine, $detectEncoding, $sourceEncoding);

        $headerParsed = !$hasHeader;
        $rowCount = 0;

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure, $startLine - 1) as $lineNumber => $logicalLine) {
            // Header parsen
            if (!$headerParsed) {
                $builder->setHeader(self::parseHeaderLine($logicalLine, $delimiter, $enclosure, $lineNumber));
                $headerParsed = true;
                continue;
            }

            // Datenzeile parsen
            $builder->addRow(self::parseDataLine($logicalLine, $delimiter, $enclosure, $lineNumber));
            $rowCount++;

            if ($maxLines !== null && $rowCount >= $maxLines) {
                break;
            }
        }

        $result = $builder->build();

        if ($result->countRows() === 0 && !$result->hasHeader()) {
            static::logErrorAndThrow(RuntimeException::class, "Keine Zeilen in CSV-Datei gefunden: $file");
        }

        if (!$result->isConsistent()) {
            static::logErrorAndThrow(RuntimeException::class, 'Inkonsistente CSV-Daten: Ungleiche Anzahl an Feldern in den Zeilen');
        }

        static::logDebug("CSV-Datei gelesen: $file ($rowCount Zeilen)");
        return $result;
    }

    /**
     * Parst einen Bereich einer CSV-Datei (optimiert für große Dateien).
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param int $fromLine Startzeile (1-basiert, inklusive)
     * @param int $toLine Endzeile (1-basiert, inklusive)
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $includeHeader Ob Header-Zeile aus Zeile 1 mit einbezogen werden soll
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return Document Das geparste CSV-Dokument
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function fromFileRange(
        string $file,
        int $fromLine,
        int $toLine,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $includeHeader = true,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): Document {
        if ($fromLine > $toLine) {
            static::logErrorAndThrow(RuntimeException::class, "Startzeile ($fromLine) darf nicht größer als Endzeile ($toLine) sein");
        }

        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $builder = new CSVDocumentBuilder($delimiter, $enclosure);

        // Header aus Zeile 1 lesen wenn gewünscht und Startzeile > 1
        if ($includeHeader && $fromLine > 1) {
            $headerGen = self::createLinesGenerator($file, false, 1, $detectEncoding, $sourceEncoding);
            foreach ($headerGen as $headerLine) {
                $builder->setHeader(self::parseHeaderLine($headerLine, $delimiter, $enclosure, 1));
                break; // Nur erste Zeile
            }
        }

        // Datenzeilen aus dem Bereich lesen
        $linesGenerator = self::createLinesGenerator($file, false, $fromLine, $detectEncoding, $sourceEncoding);

        $headerParsed = !$includeHeader || $fromLine > 1;
        $rowCount = 0;
        $maxRows = $toLine - $fromLine + 1;

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure, $fromLine - 1) as $lineNumber => $logicalLine) {
            if ($lineNumber > $toLine) {
                break;
            }

            if (!$headerParsed) {
                $builder->setHeader(self::parseHeaderLine($logicalLine, $delimiter, $enclosure, $lineNumber));
                $headerParsed = true;
                continue;
            }

            $builder->addRow(self::parseDataLine($logicalLine, $delimiter, $enclosure, $lineNumber));
            $rowCount++;

            if ($rowCount >= $maxRows) {
                break;
            }
        }

        $result = $builder->build();

        if ($result->countRows() === 0 && !$result->hasHeader()) {
            static::logErrorAndThrow(RuntimeException::class, "Keine Zeilen im angegebenen Bereich gefunden: $file (Zeilen $fromLine-$toLine)");
        }

        return $result;
    }

    /**
     * Streaming-Generator für große CSV-Dateien.
     * Liefert DataLine-Objekte einzeln, ohne die gesamte Datei in den Speicher zu laden.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist (wird übersprungen)
     * @param bool $skipEmpty Leere Zeilen überspringen
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return Generator<int, DataLine> Generator, der DataLine-Objekte liefert
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function streamRows(
        string $file,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true,
        bool $skipEmpty = false,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): Generator {
        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $linesGenerator = self::createLinesGenerator($file, $skipEmpty, 1, $detectEncoding, $sourceEncoding);

        $headerSkipped = !$hasHeader;

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure) as $lineNumber => $logicalLine) {
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }

            yield $lineNumber => self::parseDataLine($logicalLine, $delimiter, $enclosure, $lineNumber);
        }
    }

    /**
     * Streaming-Generator der sowohl Header als auch DataLines liefert.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist
     * @param bool $skipEmpty Leere Zeilen überspringen
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return Generator<int, HeaderLine|DataLine> Generator mit Header (Index 0) und DataLines
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function streamAll(
        string $file,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true,
        bool $skipEmpty = false,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): Generator {
        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $linesGenerator = self::createLinesGenerator($file, $skipEmpty, 1, $detectEncoding, $sourceEncoding);

        $headerYielded = !$hasHeader;

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure) as $lineNumber => $logicalLine) {
            if (!$headerYielded) {
                yield 0 => self::parseHeaderLine($logicalLine, $delimiter, $enclosure, $lineNumber);
                $headerYielded = true;
            } else {
                yield $lineNumber => self::parseDataLine($logicalLine, $delimiter, $enclosure, $lineNumber);
            }
        }
    }

    /**
     * Liest nur den Header einer CSV-Datei.
     * Speichereffizient: Liest nur die erste Zeile.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return HeaderLine Der Header der CSV-Datei
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function readHeader(
        string $file,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): HeaderLine {
        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $linesGenerator = self::createLinesGenerator($file, false, 1, $detectEncoding, $sourceEncoding);

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure) as $logicalLine) {
            return self::parseHeaderLine($logicalLine, $delimiter, $enclosure, 1);
        }

        static::logErrorAndThrow(RuntimeException::class, "Keine Header-Zeile in CSV-Datei gefunden: $file");
    }

    /**
     * Verarbeitet eine große CSV-Datei in Batches.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param callable $callback Callback: function(array<DataLine> $batch, int $batchNumber): void
     * @param int $batchSize Anzahl der Zeilen pro Batch (Standard: 1000)
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return int Gesamtzahl der verarbeiteten Zeilen
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function processBatches(
        string $file,
        callable $callback,
        int $batchSize = 1000,
        string $delimiter = LineInterface::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true,
        bool $detectEncoding = true,
        ?string $sourceEncoding = null
    ): int {
        $batch = [];
        $batchNumber = 0;
        $totalRows = 0;

        foreach (self::streamRows($file, $delimiter, $enclosure, $hasHeader, true, $detectEncoding, $sourceEncoding) as $row) {
            $batch[] = $row;
            $totalRows++;

            if (count($batch) >= $batchSize) {
                $batchNumber++;
                $callback($batch, $batchNumber);
                $batch = [];
            }
        }

        // Letzten Batch verarbeiten
        if (!empty($batch)) {
            $batchNumber++;
            $callback($batch, $batchNumber);
        }

        static::logInfo("CSV-Datei verarbeitet: $totalRows Zeilen in $batchNumber Batches");
        return $totalRows;
    }

    /**
     * Zählt die Zeilen einer CSV-Datei ohne sie vollständig zu laden.
     * Berücksichtigt Multi-Line-Felder in gequoteten Bereichen.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param bool $hasHeader Ob ein Header vorhanden ist (wird nicht mitgezählt)
     * @param bool $skipEmpty Leere Zeilen nicht mitzählen
     * @param string $enclosure Enclosure-Zeichen für Multi-Line-Erkennung
     * @return int Anzahl der Datenzeilen
     * @throws RuntimeException Bei Dateizugriffs-Fehlern
     */
    public static function countRows(string $file, bool $hasHeader = true, bool $skipEmpty = true, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE): int {
        if (!File::isReadable($file)) {
            static::logErrorAndThrow(RuntimeException::class, "CSV-Datei nicht lesbar: $file");
        }

        $count = 0;
        $linesGenerator = File::readLines($file, $skipEmpty);
        $headerSkipped = !$hasHeader;

        foreach (self::resolveLogicalLines($linesGenerator, $enclosure) as $logicalLine) {
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }
            $count++;
        }

        return $count;
    }

    // ===== Private Hilfsmethoden =====

    /**
     * Löst physische Zeilen in logische CSV-Zeilen auf (Multi-Line-Feld-Handling).
     *
     * Fasst aufeinanderfolgende Zeilen zusammen, die unvollständige gequotete Felder
     * enthalten, und liefert nur vollständige logische Zeilen. Leere logische Zeilen
     * werden übersprungen.
     *
     * @param Generator<string> $rawLines Generator der physischen Zeilen
     * @param string $enclosure Enclosure-Zeichen
     * @param int $lineOffset Offset für die Zeilennummerierung (Standard: 0)
     * @return Generator<int, string> Generator: Zeilennummer => logische Zeile
     */
    private static function resolveLogicalLines(Generator $rawLines, string $enclosure, int $lineOffset = 0): Generator {
        $buffer = '';
        $lineNumber = $lineOffset;

        foreach ($rawLines as $line) {
            $lineNumber++;
            $buffer .= ($buffer !== '' ? "\n" : '') . $line;

            if (self::isIncompleteQuotedField($buffer, $enclosure)) {
                continue;
            }

            if (trim($buffer) !== '') {
                yield $lineNumber => $buffer;
            }
            $buffer = '';
        }

        if (trim($buffer) !== '') {
            $lineNumber++;
            yield $lineNumber => $buffer;
        }
    }

    /**
     * Erstellt einen Generator für zeilenweises Lesen mit Encoding-Konvertierung.
     *
     * @param string $file Dateipfad
     * @param bool $skipEmpty Leere Zeilen überspringen
     * @param int $startLine Startzeile (1-basiert)
     * @param bool $detectEncoding Automatische Encoding-Erkennung
     * @param string|null $sourceEncoding Explizites Quell-Encoding
     * @return Generator<string>
     */
    private static function createLinesGenerator(
        string $file,
        bool $skipEmpty,
        int $startLine,
        bool $detectEncoding,
        ?string $sourceEncoding
    ): Generator {
        if ($sourceEncoding !== null) {
            return File::readLinesAsUtf8($file, $skipEmpty, null, $startLine, $sourceEncoding);
        }

        if ($detectEncoding) {
            return File::readLinesAsUtf8($file, $skipEmpty, null, $startLine);
        }

        return File::readLines($file, $skipEmpty, null, $startLine);
    }

    /**
     * Prüft ob eine Zeile ein unvollständiges Multi-Line-Feld enthält.
     * Optimiert: Nutzt einfache Quote-Zählung statt Regex.
     *
     * @param string $buffer Der aktuelle Zeilen-Buffer
     * @param string $enclosure Enclosure-Zeichen
     * @return bool True wenn unvollständig
     */
    private static function isIncompleteQuotedField(string $buffer, string $enclosure): bool {
        // Escaped Quotes entfernen (z.B. "" -> leer)
        $escaped = str_replace($enclosure . $enclosure, '', $buffer);
        // Ungerade Anzahl = unvollständig
        return (substr_count($escaped, $enclosure) % 2) !== 0;
    }

    /**
     * Parst eine Header-Zeile mit Fehlerbehandlung.
     *
     * @param string $line Die Zeile
     * @param string $delimiter Delimiter
     * @param string $enclosure Enclosure
     * @param int $lineNumber Zeilennummer für Fehlermeldungen
     * @return HeaderLine
     * @throws RuntimeException Bei Parsing-Fehlern
     */
    private static function parseHeaderLine(string $line, string $delimiter, string $enclosure, int $lineNumber): HeaderLine {
        try {
            return HeaderLine::fromString($line, $delimiter, $enclosure);
        } catch (Throwable $e) {
            $preview = self::getLinePreview($line);
            static::logErrorAndThrow(RuntimeException::class, "Fehler beim Parsen der Header-Zeile $lineNumber: $preview - " . $e->getMessage());
        }
    }

    /**
     * Parst eine Daten-Zeile mit Fehlerbehandlung.
     *
     * @param string $line Die Zeile
     * @param string $delimiter Delimiter
     * @param string $enclosure Enclosure
     * @param int $lineNumber Zeilennummer für Fehlermeldungen
     * @return DataLine
     * @throws RuntimeException Bei Parsing-Fehlern
     */
    private static function parseDataLine(string $line, string $delimiter, string $enclosure, int $lineNumber): DataLine {
        try {
            return DataLine::fromString($line, $delimiter, $enclosure);
        } catch (Throwable $e) {
            $preview = self::getLinePreview($line);
            static::logErrorAndThrow(RuntimeException::class, "Fehler beim Parsen der Zeile $lineNumber: $preview - " . $e->getMessage());
        }
    }

    /**
     * Erzeugt eine gekürzte Vorschau einer CSV-Zeile für Fehlermeldungen.
     *
     * @param string $line Die Zeile
     * @param int $maxLength Maximale Länge der Vorschau (Standard: 100)
     * @return string Die gekürzte Vorschau
     */
    private static function getLinePreview(string $line, int $maxLength = 100): string {
        // Steuerzeichen und Zeilenumbrüche sichtbar machen
        $preview = preg_replace('/[\x00-\x1F]/', '�', $line) ?? $line;

        if (strlen($preview) > $maxLength) {
            $preview = substr($preview, 0, $maxLength) . '...';
        }

        return '"' . $preview . '"';
    }
}
