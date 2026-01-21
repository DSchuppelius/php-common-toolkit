<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Sheet.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XLSX;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Repräsentiert ein Arbeitsblatt in einem XLSX-Dokument.
 * 
 * @implements IteratorAggregate<int, Row>
 */
class Sheet implements Countable, IteratorAggregate {
    protected string $name;
    protected ?Row $header;
    /** @var Row[] */
    protected array $rows;
    protected int $sheetIndex;

    /**
     * @param string   $name       Der Name des Arbeitsblatts
     * @param Row|null $header     Die Header-Zeile (optional)
     * @param Row[]    $rows       Die Datenzeilen
     * @param int      $sheetIndex Der 0-basierte Sheet-Index
     */
    public function __construct(string $name = 'Sheet1', ?Row $header = null, array $rows = [], int $sheetIndex = 0) {
        $this->name       = $name;
        $this->header     = $header;
        $this->rows       = array_values($rows);
        $this->sheetIndex = $sheetIndex;
    }

    /**
     * Gibt den Namen des Arbeitsblatts zurück.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Setzt den Namen des Arbeitsblatts.
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * Prüft ob ein Header vorhanden ist.
     */
    public function hasHeader(): bool {
        return $this->header !== null;
    }

    /**
     * Gibt die Header-Zeile zurück.
     */
    public function getHeader(): ?Row {
        return $this->header;
    }

    /**
     * Gibt die Header-Werte als String-Array zurück.
     *
     * @return array<string>
     */
    public function getHeaderNames(): array {
        if ($this->header === null) {
            return [];
        }
        return $this->header->toStringArray();
    }

    /**
     * Gibt alle Datenzeilen zurück.
     *
     * @return Row[]
     */
    public function getRows(): array {
        return $this->rows;
    }

    /**
     * Gibt eine Zeile nach Index zurück (0-basiert).
     */
    public function getRow(int $index): ?Row {
        return $this->rows[$index] ?? null;
    }

    /**
     * Gibt den Sheet-Index zurück (0-basiert).
     */
    public function getSheetIndex(): int {
        return $this->sheetIndex;
    }

    /**
     * Gibt die Anzahl der Datenzeilen zurück (ohne Header).
     */
    public function count(): int {
        return count($this->rows);
    }

    /**
     * Gibt die Gesamtanzahl der Zeilen zurück (mit Header).
     */
    public function countTotal(): int {
        return count($this->rows) + ($this->header !== null ? 1 : 0);
    }

    /**
     * Prüft ob das Header mit den Datenzeilen konsistent ist.
     */
    public function isConsistent(): bool {
        if ($this->header === null || empty($this->rows)) {
            return true;
        }

        $headerCount = count($this->header);
        foreach ($this->rows as $row) {
            if (count($row) !== $headerCount) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gibt eine Spalte nach Index zurück (0-basiert).
     *
     * @return array<mixed>
     */
    public function getColumn(int $index): array {
        $column = [];
        foreach ($this->rows as $row) {
            $cell = $row->getCell($index);
            $column[] = $cell?->getValue();
        }
        return $column;
    }

    /**
     * Gibt eine Spalte nach Header-Name zurück.
     *
     * @return array<mixed>
     */
    public function getColumnByName(string $name): array {
        if ($this->header === null) {
            return [];
        }

        $headerNames = $this->getHeaderNames();
        $index = array_search($name, $headerNames, true);

        if ($index === false) {
            return [];
        }

        return $this->getColumn((int) $index);
    }

    /**
     * Prüft ob eine Spalte mit dem gegebenen Namen existiert.
     */
    public function hasColumn(string $name): bool {
        if ($this->header === null) {
            return false;
        }
        return in_array($name, $this->getHeaderNames(), true);
    }

    /**
     * Gibt den Spaltenindex für einen Header-Namen zurück.
     *
     * @return int|null Der 0-basierte Spaltenindex oder null
     */
    public function getColumnIndex(string $name): ?int {
        if ($this->header === null) {
            return null;
        }

        $index = array_search($name, $this->getHeaderNames(), true);
        return $index !== false ? (int) $index : null;
    }

    /**
     * Konvertiert das Sheet in ein 2D-Array.
     *
     * @param bool $includeHeader Ob der Header einbezogen werden soll
     * @return array<array<mixed>>
     */
    public function toArray(bool $includeHeader = true): array {
        $result = [];

        if ($includeHeader && $this->header !== null) {
            $result[] = $this->header->toArray();
        }

        foreach ($this->rows as $row) {
            $result[] = $row->toArray();
        }

        return $result;
    }

    /**
     * @return Traversable<int, Row>
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->rows);
    }
}
