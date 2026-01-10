<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


namespace CommonToolkit\Entities\CSV;

use CommonToolkit\Contracts\Abstracts\TextDocumentAbstract;
use CommonToolkit\Contracts\Interfaces\CSV\FieldInterface;
use CommonToolkit\Generators\CSV\CSVGenerator;
use RuntimeException;

/**
 * CSV-Dokument mit Header und Datenzeilen.
 * Erbt von TextDocumentAbstract für Encoding-, BOM- und Zeilenumbruch-Handling.
 * 
 * Verwendet:
 * - CSVGenerator: Generierung von CSV-Strings
 * 
 * @package CommonToolkit\Entities\CSV
 */
class Document extends TextDocumentAbstract {

    protected ?HeaderLine $header;
    /** @var DataLine[] */
    protected array $rows;

    protected string $delimiter;
    protected string $enclosure;
    protected ?ColumnWidthConfig $columnWidthConfig = null;

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
            $actual = $row->countFields();
            if ($actual !== $expected) {
                return $this->logErrorAndReturn(false, "CSV-Zeile $i hat abweichende Feldanzahl (erwartet: $expected, gefunden: $actual)");
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
        return (new CSVGenerator())->generate($this, $delimiter, $enclosure, $enclosureRepeat, $targetEncoding, $this->exportWithHeader);
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
        (new CSVGenerator())->toFile($this, $file, $delimiter, $enclosure, $enclosureRepeat, $targetEncoding, $withBom, $this->exportWithHeader);
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
     * Delegiert an HeaderLine::getColumnIndex().
     *
     * @param string $columnName Name der Spalte
     * @return int|null Index der Spalte oder null wenn nicht gefunden
     */
    public function getColumnIndex(string $columnName): ?int {
        return $this->header?->getColumnIndex($columnName);
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

        if ($index === null) {
            $available = implode(', ', $this->getColumnNames());
            $this->logErrorAndThrow(RuntimeException::class, "Spalte '$columnName' nicht im Header gefunden. Verfügbar: $available");
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
            $maxIndex = ($this->header?->countFields() ?? 0) - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Spalten-Index '$index' ist ungültig (gültiger Bereich: 0-$maxIndex)");
        }

        $fields = [];
        foreach ($this->rows as $row) {
            $field = $row->getField($index);
            $fields[] = $field;
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
        if ($this->countRows() !== $other->countRows()) return false;
        foreach ($this->rows as $i => $row) {
            if (!$row->equals($other->rows[$i])) return false;
        }
        return true;
    }

    /**
     * Gibt das CSV-Dokument als String zurück.
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Gibt den Wert eines Feldes zurück.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @return string|null Der Wert oder null wenn nicht vorhanden.
     */
    protected function getFieldValue(int $rowIndex, int $fieldIndex): ?string {
        $row = $this->rows[$rowIndex] ?? null;
        if ($row === null) {
            return null;
        }

        $field = $row->getField($fieldIndex);
        return $field?->getValue();
    }

    /**
     * Setzt den Wert eines Feldes.
     * Nutzt das immutable Pattern: Erstellt neue Field- und Line-Objekte.
     * Behält den bisherigen Quoting-Status des Feldes bei.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @param mixed $value Der neue Wert (wird zu String konvertiert).
     * @throws RuntimeException Wenn Zeile oder Feld nicht existiert.
     */
    protected function setFieldValue(int $rowIndex, int $fieldIndex, mixed $value): void {
        // Ermittle den aktuellen Quoting-Status (Validierung erfolgt in setFieldValueWithQuoting)
        $quoted = $this->rows[$rowIndex]?->getField($fieldIndex)?->isQuoted() ?? false;
        $this->setFieldValueWithQuoting($rowIndex, $fieldIndex, $value, $quoted);
    }

    /**
     * Setzt den Wert eines Feldes mit expliziter Quoting-Kontrolle.
     * Nutzt das immutable Pattern: Erstellt neue Field- und Line-Objekte.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @param mixed $value Der neue Wert (wird zu String konvertiert).
     * @param bool $quoted Ob der Wert gequotet sein soll.
     * @throws RuntimeException Wenn Zeile oder Feld nicht existiert.
     */
    protected function setFieldValueWithQuoting(int $rowIndex, int $fieldIndex, mixed $value, bool $quoted): void {
        if (!isset($this->rows[$rowIndex])) {
            $maxRow = $this->countRows() - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Zeile $rowIndex existiert nicht (gültiger Bereich: 0-$maxRow)");
        }

        $row = $this->rows[$rowIndex];
        $fields = $row->getFields();

        if (!isset($fields[$fieldIndex])) {
            $maxField = $row->countFields() - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Feld $fieldIndex existiert nicht in Zeile $rowIndex (gültiger Bereich: 0-$maxField)");
        }

        $stringValue = match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_null($value) => '',
            default => (string)$value,
        };

        $fields[$fieldIndex] = $fields[$fieldIndex]->withValue($stringValue)->withQuoted($quoted);

        $this->rows[$rowIndex] = new DataLine($fields, $this->delimiter, $this->enclosure);
    }
}
