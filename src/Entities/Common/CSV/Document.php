<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class Document {
    use ErrorLog;

    /** Standard-Encoding für CSV-Dokumente */
    public const DEFAULT_ENCODING = 'UTF-8';

    protected ?HeaderLine $header;
    /** @var DataLine[] */
    protected array $rows;

    protected string $delimiter;
    protected string $enclosure;
    protected ?ColumnWidthConfig $columnWidthConfig = null;

    /**
     * Die Zeichenkodierung des Dokuments.
     * Wird beim Parsen erkannt und beim Export verwendet.
     */
    protected string $encoding = self::DEFAULT_ENCODING;

    /**
     * Steuert ob beim CSV-Export der Header mit ausgegeben werden soll.
     * Standard ist true - kann in Subklassen überschrieben werden.
     */
    protected bool $exportWithHeader = true;

    public function __construct(?HeaderLine $header = null, array $rows = [], string $delimiter = ',', string $enclosure = '"', ?ColumnWidthConfig $columnWidthConfig = null, string $encoding = self::DEFAULT_ENCODING) {
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->columnWidthConfig   = $columnWidthConfig;
        $this->encoding            = $encoding;
        $this->header              = $header;
        $this->rows                = $rows;
    }

    /**
     * Prüft ob eine Spalte mit dem gegebenen Namen existiert.
     *
     * @param string $columnName Name der Spalte
     * @return bool True wenn die Spalte existiert
     */
    public function hasColumn(string $columnName): bool {
        return $this->header?->hasColumn($columnName) ?? false;
    }

    /**
     * Prüft ob ein Header vorhanden ist.
     *
     * @return bool
     */
    public function hasHeader(): bool {
        return $this->header !== null;
    }

    /** @return HeaderLine|null */
    public function getHeader(): ?HeaderLine {
        return $this->header;
    }

    /** @return DataLine[] */
    public function getRows(): array {
        return array_values($this->rows);
    }

    /** @return DataLine|null */
    public function getRow(int $index): ?DataLine {
        return $this->rows[$index] ?? null;
    }

    /** @return int */
    public function countRows(): int {
        return count($this->rows);
    }

    /** @return string */
    public function getDelimiter(): string {
        return $this->delimiter;
    }

    /** @return string */
    public function getEnclosure(): string {
        return $this->enclosure;
    }

    /**
     * Gibt die Zeichenkodierung des Dokuments zurück.
     *
     * @return string Die Zeichenkodierung (z.B. 'UTF-8', 'ISO-8859-1')
     */
    public function getEncoding(): string {
        return $this->encoding;
    }

    /**
     * Setzt die Zeichenkodierung des Dokuments.
     *
     * @param string $encoding Die Zeichenkodierung (z.B. 'UTF-8', 'ISO-8859-1')
     * @return void
     */
    public function setEncoding(string $encoding): void {
        $this->encoding = $encoding;
    }

    /**
     * Setzt die Spaltenbreiten-Konfiguration.
     *
     * @param ColumnWidthConfig|null $config
     * @return void
     */
    public function setColumnWidthConfig(?ColumnWidthConfig $config): void {
        $this->columnWidthConfig = $config;
    }

    /**
     * Gibt die Spaltenbreiten-Konfiguration zurück.
     *
     * @return ColumnWidthConfig|null
     */
    public function getColumnWidthConfig(): ?ColumnWidthConfig {
        return $this->columnWidthConfig;
    }

    /**
     * Überprüft, ob alle Zeilen die gleiche Anzahl an Feldern haben wie der Header (falls vorhanden) oder die erste Zeile.
     *
     * @return bool
     */
    public function isConsistent(): bool {
        if ($this->rows === []) return true;
        $expected = $this->header?->countFields() ?? $this->rows[0]->countFields();

        foreach ($this->rows as $i => $row) {
            if ($row->countFields() !== $expected) {
                static::logError("CSV-Zeile $i hat abweichende Feldanzahl");
                return false;
            }
        }
        return true;
    }

    /**
     * Wandelt das gesamte CSV-Dokument in eine rohe CSV-Zeichenkette um.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null, ?string $targetEncoding = null): string {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;
        $targetEncoding ??= $this->encoding;

        if ($enclosureRepeat !== null) {
            foreach (array_merge($this->rows, $this->header ? [$this->header] : []) as $line) {
                foreach ($line->getFields() as $field) {
                    $field->setEnclosureRepeat($enclosureRepeat);
                }
            }
        }

        $lines = [];

        // Header wird NIEMALS gekürzt
        if ($this->header && $this->exportWithHeader) {
            $lines[] = $this->header->toString($delimiter, $enclosure);
        }

        // DataLines mit ColumnWidth-Verarbeitung
        foreach ($this->rows as $row) {
            if ($this->columnWidthConfig) {
                $lines[] = $this->applyColumnWidthToRow($row, $delimiter, $enclosure);
            } else {
                $lines[] = $row->toString($delimiter, $enclosure);
            }
        }

        $result = implode("\n", $lines);

        // Encoding-Konvertierung falls nötig - nutze StringHelper
        if ($targetEncoding !== self::DEFAULT_ENCODING) {
            return StringHelper::convertEncoding($result, self::DEFAULT_ENCODING, $targetEncoding);
        }

        return $result;
    }

    /**
     * Wendet ColumnWidthConfig auf eine DataLine an mit korrekter TruncationStrategy.
     *
     * @param DataLine $row Die Datenzeile
     * @param string $delimiter Das Trennzeichen
     * @param string $enclosure Das Einschlusszeichen
     * @return string Die formatierte CSV-Zeile
     */
    private function applyColumnWidthToRow(DataLine $row, string $delimiter, string $enclosure): string {
        $parts = [];
        foreach ($row->getFields() as $index => $field) {
            $columnKey = $this->getColumnKeyForIndex($index);
            $value = $field->getValue();

            // ColumnWidthConfig kümmert sich um Kürzung UND Padding
            if ($this->columnWidthConfig->hasWidthConfig($columnKey)) {
                $processedValue = $this->columnWidthConfig->truncateValue($value, $columnKey);

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
     * Bestimmt den ColumnKey für einen Field-Index.
     *
     * @param int $index Field-Index
     * @return string|int Spaltenname (falls Header vorhanden) oder Index
     */
    private function getColumnKeyForIndex(int $index): string|int {
        if ($this->header) {
            $headerField = $this->header->getField($index);
            if ($headerField) {
                return $headerField->getValue();
            }
        }
        return $index;
    }

    /**
     * Schreibt das gesamte CSV-Dokument in eine Datei.
     *
     * @param string      $file            Der Pfad zur Zieldatei.
     * @param string|null $delimiter       Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure       Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null    $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding  Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @param bool        $withBom         Ob ein BOM (Byte Order Mark) am Anfang der Datei geschrieben werden soll (Standard: true für bessere Encoding-Erkennung).
     * @return void
     *
     * @throws RuntimeException
     */
    public function toFile(string $file, ?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null, ?string $targetEncoding = null, bool $withBom = true): void {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;
        $targetEncoding ??= $this->encoding;

        $csv = $this->toString($delimiter, $enclosure, $enclosureRepeat, $targetEncoding);

        // BOM (Byte Order Mark) hinzufügen wenn gewünscht
        if ($withBom) {
            $bom = StringHelper::getBomForEncoding($targetEncoding);
            if ($bom !== null) {
                $csv = $bom . $csv;
            }
        }

        File::write($file, $csv);
    }

    /**
     * Wandelt das CSV-Dokument in ein assoziatives Array um.
     *
     * @return array
     */
    public function toAssoc(): array {
        if (!$this->header) return [];
        $keys = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $assoc = [];
        foreach ($this->rows as $row) {
            $values = array_map(fn($f) => $f->getValue(), $row->getFields());
            $assoc[] = array_combine($keys, $values);
        }
        return $assoc;
    }

    /**
     * Findet den Index einer Spalte anhand des Header-Namens.
     *
     * @param string $columnName Name der Spalte
     * @return int Index der Spalte oder -1 wenn nicht gefunden
     */
    public function getColumnIndex(string $columnName): int {
        if (!$this->header) {
            return -1;
        }

        return $this->header->getColumnIndex($columnName) ?? -1;
    }

    /**
     * Liefert alle Field-Objekte einer Spalte anhand des Header-Namens.
     * Bietet vollständigen Zugriff auf Field-Metadaten (Wert, Raw-Wert, Quote-Info, etc.).
     *
     * @param string $columnName Name der Spalte
     * @return FieldInterface[] Array mit allen Field-Objekten der Spalte
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function getFieldsByName(string $columnName): array {
        $index = $this->getColumnIndex($columnName);

        if ($index === -1) {
            static::logError("Spalte '$columnName' nicht im Header gefunden");
            throw new RuntimeException("Spalte '$columnName' nicht im Header gefunden");
        }

        return $this->getFieldsByIndex($index);
    }

    /**
     * Liefert alle Werte einer Spalte anhand des Header-Namens.
     *
     * @param string $columnName Name der Spalte
     * @return array Array mit allen Werten der Spalte
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function getColumnByName(string $columnName): array {
        $fields = $this->getFieldsByName($columnName);
        return array_map(fn($field) => $field ? $field->getValue() : '', $fields);
    }

    /**
     * Liefert alle Field-Objekte einer Spalte anhand des Index.
     * Bietet vollständigen Zugriff auf Field-Metadaten (Wert, Raw-Wert, Quote-Info, etc.).
     *
     * @param int $index Index der Spalte
     * @return FieldInterface[] Array mit allen Field-Objekten der Spalte
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function getFieldsByIndex(int $index): array {
        if ($index < 0) {
            static::logError("Spalten-Index '$index' ist ungültig");
            throw new RuntimeException("Spalten-Index '$index' ist ungültig");
        }

        $fields = [];
        foreach ($this->rows as $row) {
            $field = $row->getField($index);
            $fields[] = $field; // Kann null sein - Caller entscheidet, wie damit umgegangen wird
        }

        return $fields;
    }

    /**
     * Liefert alle Werte einer Spalte anhand des Index.
     *
     * @param int $index Index der Spalte
     * @return array Array mit allen Werten der Spalte
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function getColumnByIndex(int $index): array {
        $fields = $this->getFieldsByIndex($index);
        return array_map(fn($field) => $field ? $field->getValue() : '', $fields);
    }

    /**
     * Liefert alle Header-Namen als Array.
     *
     * @return array Array mit allen Spalten-Namen
     */
    public function getColumnNames(): array {
        return $this->header?->getColumnNames() ?? [];
    }

    /**
     * Vergleicht dieses CSV-Dokument mit einem anderen auf Gleichheit.
     *
     * @param Document $other Das andere CSV-Dokument zum Vergleichen.
     * @return bool
     */
    public function equals(Document $other): bool {
        if ($this->delimiter !== $other->delimiter) return false;
        if ($this->enclosure !== $other->enclosure) return false;
        if (($this->header && !$other->header) || (!$this->header && $other->header)) return false;
        if ($this->header && !$this->header->equals($other->header)) return false;
        if (count($this->rows) !== count($other->rows)) return false;
        foreach ($this->rows as $i => $row) {
            if (!$row->equals($other->rows[$i])) return false;
        }
        return true;
    }

    /**
     * Gibt den Rohwert eines Feldes zurück.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @return string|null Der Wert oder null wenn nicht vorhanden.
     */
    protected function getFieldValue(int $rowIndex, int $fieldIndex): ?string {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (!isset($fields[$fieldIndex])) {
            return null;
        }

        return $fields[$fieldIndex]->getValue();
    }

    /**
     * Setzt den Wert eines Feldes.
     * Nutzt das immutable Pattern: Erstellt neue Field- und Line-Objekte.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @param string $value Der neue Wert.
     * @throws RuntimeException Wenn Zeile oder Feld nicht existiert.
     */
    protected function setFieldValue(int $rowIndex, int $fieldIndex, string $value): void {
        if (!isset($this->rows[$rowIndex])) {
            throw new RuntimeException("Zeile $rowIndex existiert nicht");
        }

        $oldRow = $this->rows[$rowIndex];
        $fields = $oldRow->getFields();

        if (!isset($fields[$fieldIndex])) {
            throw new RuntimeException("Feld $fieldIndex existiert nicht in Zeile $rowIndex");
        }

        // Neues Field mit neuem Wert erstellen (behält quoted, enclosureRepeat, etc.)
        $fields[$fieldIndex] = $fields[$fieldIndex]->withValue($value);

        // Neue DataLine erstellen
        $this->rows[$rowIndex] = new DataLine(
            $fields,
            $this->delimiter,
            $this->enclosure
        );
    }

    /**
     * Gibt das CSV-Dokument als String zurück.
     */
    public function __toString(): string {
        return $this->toString();
    }
}
