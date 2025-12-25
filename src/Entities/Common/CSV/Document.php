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
use CommonToolkit\Contracts\Interfaces\Common\CSV\HeaderLineInterface;
use CommonToolkit\Contracts\Abstracts\Common\CSV\DataLineAbstract;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class Document {
    use ErrorLog;

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

    public function __construct(?HeaderLine $header = null, array $rows = [], string $delimiter = ',', string $enclosure = '"', ?ColumnWidthConfig $columnWidthConfig = null) {
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->columnWidthConfig   = $columnWidthConfig;
        $this->header              = $header;
        $this->rows                = $rows;

        // Verknüpfe DataLines mit HeaderLine
        $this->linkDataLinesToHeader();
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
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null): string {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;

        if ($enclosureRepeat !== null) {
            foreach (array_merge($this->rows, $this->header ? [$this->header] : []) as $line) {
                foreach ($line->getFields() as $field) {
                    $field->setEnclosureRepeat($enclosureRepeat);
                }
            }
        }

        $lines = [];

        if ($this->header && $this->exportWithHeader) {
            // Header wird NICHT gekürzt - kein columnWidthConfig übergeben
            $lines[] = $this->header->toString($delimiter, $enclosure);
        }
        foreach ($this->rows as $row) {
            // Nur Datenzeilen bekommen ColumnWidthConfig für Kürzung
            $lines[] = $row->toString($delimiter, $enclosure, $this->columnWidthConfig);
        }

        return implode("\n", $lines);
    }

    /**
     * Schreibt das gesamte CSV-Dokument in eine Datei.
     *
     * @param string      $file      Der Pfad zur Zieldatei.
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @return void
     *
     * @throws RuntimeException
     */
    public function toFile(string $file, ?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null): void {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;

        $csv = $this->toString($delimiter, $enclosure, $enclosureRepeat);

        $result = @file_put_contents($file, $csv);
        if ($result === false) {
            static::logError("Fehler beim Schreiben der CSV-Datei: $file");
            throw new RuntimeException("Fehler beim Schreiben der CSV-Datei: $file");
        }
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
     * Baut ein Array der Spaltenbreiten für die toString-Methode auf.
     *
     * @param array<string> $columnNames Die Spaltennamen
     * @return Headerline Spaltenbreiten-Array
     */

    /**
     * Verknüpft alle DataLines mit der HeaderLine.
     */
    private function linkDataLinesToHeader(): void {
        if ($this->header === null) {
            return;
        }

        foreach ($this->rows as $row) {
            if ($row instanceof DataLineAbstract) {
                $row->setHeaderLine($this->header);
            }
        }
    }
}