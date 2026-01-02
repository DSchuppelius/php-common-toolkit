<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVGenerator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Generators\CSV;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Entities\CSV\Document;
use CommonToolkit\Entities\CSV\HeaderLine;
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\FileSystem\File;

/**
 * Generator für CSV-Dokumente.
 * 
 * Wandelt Document-Objekte in CSV-Strings um mit Unterstützung für:
 * - Benutzerdefinierte Trennzeichen und Einschlusszeichen
 * - Spaltenbreiten-Konfiguration
 * - Encoding-Konvertierung
 * - BOM-Unterstützung
 * 
 * @package CommonToolkit\Generators\CSV
 */
class CSVGenerator extends HelperAbstract {
    /** Standard-Zeilentrennzeichen */
    protected const LINE_SEPARATOR = "\n";

    /**
     * Generiert einen CSV-String aus einem Document.
     *
     * @param Document $document Das CSV-Dokument
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Dokument-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Dokument-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @param bool $includeHeader Ob der Header mit ausgegeben werden soll. Standard: true.
     * @return string Der generierte CSV-String
     */
    public function generate(
        Document $document,
        ?string $delimiter = null,
        ?string $enclosure = null,
        ?int $enclosureRepeat = null,
        ?string $targetEncoding = null,
        bool $includeHeader = true
    ): string {
        $delimiter ??= $document->getDelimiter();
        $enclosure ??= $document->getEnclosure();
        $targetEncoding ??= $document->getEncoding();
        $columnWidthConfig = $document->getColumnWidthConfig();

        // Enclosure-Repeat auf alle Felder anwenden
        if ($enclosureRepeat !== null) {
            $this->applyEnclosureRepeat($document, $enclosureRepeat);
        }

        $lines = [];

        // Header generieren (ohne Spaltenbreiten-Kürzung)
        $header = $document->getHeader();
        if ($header !== null && $includeHeader) {
            $lines[] = $this->generateLine($header, $delimiter, $enclosure);
        }

        // Datenzeilen generieren
        foreach ($document->getRows() as $row) {
            if ($columnWidthConfig !== null) {
                $lines[] = $this->generateLineWithColumnWidth(
                    $row,
                    $header,
                    $columnWidthConfig,
                    $delimiter,
                    $enclosure
                );
            } else {
                $lines[] = $this->generateLine($row, $delimiter, $enclosure);
            }
        }

        $result = implode(self::LINE_SEPARATOR, $lines);

        // Encoding-Konvertierung
        return $this->convertEncoding($result, $targetEncoding);
    }

    /**
     * Generiert eine CSV-Zeile.
     *
     * @param HeaderLine|DataLine $line Die Zeile
     * @param string $delimiter Das Trennzeichen
     * @param string $enclosure Das Einschlusszeichen
     * @return string Die formatierte CSV-Zeile
     */
    protected function generateLine(HeaderLine|DataLine $line, string $delimiter, string $enclosure): string {
        return $line->toString($delimiter, $enclosure);
    }

    /**
     * Generiert eine CSV-Zeile mit Spaltenbreiten-Verarbeitung.
     *
     * @param DataLine $row Die Datenzeile
     * @param HeaderLine|null $header Der Header (für Spaltennamen)
     * @param ColumnWidthConfig $config Die Spaltenbreiten-Konfiguration
     * @param string $delimiter Das Trennzeichen
     * @param string $enclosure Das Einschlusszeichen
     * @return string Die formatierte CSV-Zeile
     */
    protected function generateLineWithColumnWidth(
        DataLine $row,
        ?HeaderLine $header,
        ColumnWidthConfig $config,
        string $delimiter,
        string $enclosure
    ): string {
        $parts = [];

        foreach ($row->getFields() as $index => $field) {
            $columnKey = $this->getColumnKey($header, $index);
            $value = $field->getValue();

            // ColumnWidthConfig kümmert sich um Kürzung UND Padding
            if ($config->hasWidthConfig($columnKey)) {
                $processedValue = $config->truncateValue($value, $columnKey);

                if ($processedValue !== $value) {
                    // Wert wurde modifiziert - temporäres Field erstellen
                    $tempField = clone $field;
                    $tempField->setValue($processedValue);
                    $parts[] = $tempField->toString($enclosure);
                } else {
                    $parts[] = $field->toString($enclosure);
                }
            } else {
                $parts[] = $field->toString($enclosure);
            }
        }

        return implode($delimiter, $parts);
    }

    /**
     * Bestimmt den Spalten-Key für einen Field-Index.
     *
     * @param HeaderLine|null $header Der Header
     * @param int $index Field-Index
     * @return string|int Spaltenname (falls Header vorhanden) oder Index
     */
    protected function getColumnKey(?HeaderLine $header, int $index): string|int {
        if ($header !== null) {
            $headerField = $header->getField($index);
            if ($headerField !== null) {
                return $headerField->getValue();
            }
        }

        return $index;
    }

    /**
     * Wendet Enclosure-Repeat auf alle Felder an.
     *
     * @param Document $document Das Dokument
     * @param int $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen
     */
    protected function applyEnclosureRepeat(Document $document, int $enclosureRepeat): void {
        $header = $document->getHeader();

        if ($header !== null) {
            foreach ($header->getFields() as $field) {
                $field->setEnclosureRepeat($enclosureRepeat);
            }
        }

        foreach ($document->getRows() as $row) {
            foreach ($row->getFields() as $field) {
                $field->setEnclosureRepeat($enclosureRepeat);
            }
        }
    }

    /**
     * Konvertiert den String in das Ziel-Encoding.
     *
     * @param string $content Der Inhalt
     * @param string $targetEncoding Das Ziel-Encoding
     * @return string Der konvertierte String
     */
    protected function convertEncoding(string $content, string $targetEncoding): string {
        if ($targetEncoding !== Document::DEFAULT_ENCODING) {
            return StringHelper::convertEncoding($content, Document::DEFAULT_ENCODING, $targetEncoding);
        }

        return $content;
    }

    /**
     * Generiert einen CSV-String mit BOM (Byte Order Mark).
     *
     * @param Document $document Das CSV-Dokument
     * @param string|null $targetEncoding Das Ziel-Encoding
     * @param string|null $delimiter Das Trennzeichen
     * @param string|null $enclosure Das Einschlusszeichen
     * @return string Der CSV-String mit BOM
     */
    public function generateWithBom(
        Document $document,
        ?string $targetEncoding = null,
        ?string $delimiter = null,
        ?string $enclosure = null
    ): string {
        $targetEncoding ??= $document->getEncoding();
        $csv = $this->generate($document, $delimiter, $enclosure, null, $targetEncoding);

        $bom = StringHelper::getBomForEncoding($targetEncoding);
        if ($bom !== null) {
            return $bom . $csv;
        }

        return $csv;
    }

    /**
     * Schreibt das CSV-Dokument in eine Datei.
     *
     * @param Document $document Das CSV-Dokument
     * @param string $file Der Pfad zur Zieldatei
     * @param string|null $delimiter Das Trennzeichen
     * @param string|null $enclosure Das Einschlusszeichen
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen
     * @param string|null $targetEncoding Das Ziel-Encoding
     * @param bool $withBom Ob ein BOM geschrieben werden soll
     * @param bool $includeHeader Ob der Header mit ausgegeben werden soll
     * @return void
     */
    public function toFile(
        Document $document,
        string $file,
        ?string $delimiter = null,
        ?string $enclosure = null,
        ?int $enclosureRepeat = null,
        ?string $targetEncoding = null,
        bool $withBom = true,
        bool $includeHeader = true
    ): void {
        $targetEncoding ??= $document->getEncoding();

        if ($withBom) {
            $csv = $this->generateWithBom($document, $targetEncoding, $delimiter, $enclosure);
        } else {
            $csv = $this->generate($document, $delimiter, $enclosure, $enclosureRepeat, $targetEncoding, $includeHeader);
        }

        File::write($file, $csv);
    }
}
