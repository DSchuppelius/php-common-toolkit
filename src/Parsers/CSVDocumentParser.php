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
use CommonToolkit\Entities\Common\CSV\{HeaderLine, DataLine};
use CommonToolkit\Helper\Data\CSV\StringHelper;
use CommonToolkit\Helper\Data\StringHelper as DataStringHelper;
use CommonToolkit\Contracts\Interfaces\Common\CSV\{LineInterface, FieldInterface};
use CommonToolkit\Entities\Common\CSV\Document;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use Throwable;

class CSVDocumentParser {
    use ErrorLog;

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
    public static function fromString(string $csv, string $delimiter = LineInterface::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE, bool $hasHeader = true, ?string $encoding = null): Document {
        $csv = trim($csv);
        if ($csv === '') {
            throw new RuntimeException('Leere CSV-Zeichenkette');
        }

        // Encoding-Erkennung und Konvertierung nach UTF-8 für internes Parsing
        $sourceEncoding = $encoding ?? Document::DEFAULT_ENCODING;
        if ($sourceEncoding !== Document::DEFAULT_ENCODING) {
            $csv = DataStringHelper::convertEncoding($csv, $sourceEncoding, Document::DEFAULT_ENCODING);
        }

        $lines = StringHelper::splitCsvByLogicalLine($csv, $enclosure);
        if ($lines === [] || $lines === false) {
            static::logError('CSVDocumentParser::fromString() – keine gültigen Zeilen erkannt');
            throw new RuntimeException('Keine gültigen CSV-Zeilen erkannt');
        }

        $builder = new CSVDocumentBuilder($delimiter, $enclosure, null, $sourceEncoding);

        try {
            if ($hasHeader) {
                $headerLine = array_shift($lines);
                if ($headerLine === null) {
                    static::logError('Header-Zeile fehlt');
                    throw new RuntimeException('Header-Zeile fehlt');
                } elseif (!StringHelper::canParseCompleteCSVDataLine($headerLine, $delimiter, $enclosure)) {
                    static::logError('Inkonsistente Quote-Struktur erkannt');
                    throw new RuntimeException('Inkonsistente Quote-Struktur erkannt');
                }
                $builder->setHeader(HeaderLine::fromString($headerLine, $delimiter, $enclosure));
            }

            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                elseif (!StringHelper::canParseCompleteCSVDataLine($line, $delimiter, $enclosure)) {
                    static::logError('Inkonsistente Quote-Struktur erkannt');
                    throw new RuntimeException('Inkonsistente Quote-Struktur erkannt');
                }

                $builder->addRow(DataLine::fromString($line, $delimiter, $enclosure));
            }
        } catch (Throwable $e) {
            static::logError("Fehler beim Parsen der CSV: " . $e->getMessage());
            throw new RuntimeException("Fehler beim Parsen der CSV: " . $e->getMessage(), 0, $e);
        }

        $result = $builder->build();
        if (!$result->isConsistent()) {
            static::logError('Inkonsistente CSV-Daten: Ungleiche Anzahl an Feldern in den Zeilen');
            throw new RuntimeException('Inkonsistente CSV-Daten: Ungleiche Anzahl an Feldern in den Zeilen');
        }
        return $result;
    }

    /**
     * Parst eine CSV-Datei in ein CSVDocument.
     *
     * @param string $file Der Pfad zur CSV-Datei
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     * @param bool $hasHeader Ob ein Header vorhanden ist
     * @param int $startLine Ab welcher Zeile gelesen werden soll (1-basiert)
     * @param int|null $maxLines Maximale Anzahl zu lesender Zeilen (null = alle)
     * @param bool $skipEmpty Leere Zeilen überspringen
     * @param bool $detectEncoding Automatische Encoding-Erkennung aktivieren
     * @return Document Das geparste CSV-Dokument
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function fromFile(string $file, string $delimiter = LineInterface::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE, bool $hasHeader = true, int $startLine = 1, ?int $maxLines = null, bool $skipEmpty = false, bool $detectEncoding = true): Document {
        if (!File::isReadable($file)) {
            static::logError("CSV-Datei nicht lesbar: $file");
            throw new RuntimeException("CSV-Datei nicht lesbar: $file");
        }

        // Nutze erweiterte FileHelper-Funktionen für effizienten Dateizugriff
        $lines = File::readLinesAsArray($file, $skipEmpty, $maxLines, $startLine);

        if (empty($lines)) {
            static::logError("Keine Zeilen in CSV-Datei gefunden: $file");
            throw new RuntimeException("Keine Zeilen in CSV-Datei gefunden: $file");
        }

        $content = implode("\n", $lines);

        // Encoding-Erkennung wenn aktiviert - nutze File::chardet für Datei-basierte Erkennung
        $encoding = null;
        if ($detectEncoding) {
            $encoding = File::chardet($file);
            if ($encoding === false) {
                $encoding = null;
                static::logWarning("Encoding konnte nicht erkannt werden für: $file");
            } else {
                static::logDebug("Erkanntes Encoding für $file: $encoding");
            }
        }

        return self::fromString($content, $delimiter, $enclosure, $hasHeader, $encoding);
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
     * @return Document Das geparste CSV-Dokument
     * @throws RuntimeException Bei Dateizugriffs- oder Parsing-Fehlern
     */
    public static function fromFileRange(string $file, int $fromLine, int $toLine, string $delimiter = LineInterface::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE, bool $includeHeader = true, bool $detectEncoding = true): Document {
        if ($fromLine > $toLine) {
            throw new RuntimeException("Startzeile ($fromLine) darf nicht größer als Endzeile ($toLine) sein");
        }

        if (!File::isReadable($file)) {
            static::logError("CSV-Datei nicht lesbar: $file");
            throw new RuntimeException("CSV-Datei nicht lesbar: $file");
        }

        $lines = [];

        // Header hinzufügen falls gewünscht
        if ($includeHeader && $fromLine > 1) {
            $headerLines = File::readLinesAsArray($file, false, 1, 1);
            if (!empty($headerLines)) {
                $lines[] = $headerLines[0];
            }
        }

        // Datenzeilen lesen
        $maxLines = $toLine - $fromLine + 1;
        $dataLines = File::readLinesAsArray($file, false, $maxLines, $fromLine);
        $lines = array_merge($lines, $dataLines);

        if (empty($lines)) {
            static::logError("Keine Zeilen im angegebenen Bereich gefunden: $file (Zeilen $fromLine-$toLine)");
            throw new RuntimeException("Keine Zeilen im angegebenen Bereich gefunden");
        }

        $content = implode("\n", $lines);

        // Encoding-Erkennung wenn aktiviert - nutze File::chardet für Datei-basierte Erkennung
        $encoding = null;
        if ($detectEncoding) {
            $encoding = File::chardet($file);
            if ($encoding === false) {
                $encoding = null;
                static::logWarning("Encoding konnte nicht erkannt werden für: $file");
            }
        }

        return self::fromString($content, $delimiter, $enclosure, $includeHeader, $encoding);
    }
}