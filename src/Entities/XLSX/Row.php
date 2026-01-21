<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Row.php
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
 * Repräsentiert eine Zeile in einem XLSX-Arbeitsblatt.
 * 
 * @implements IteratorAggregate<int, Cell>
 */
class Row implements Countable, IteratorAggregate {
    /** @var Cell[] */
    protected array $cells;
    protected int $rowIndex;

    /**
     * @param Cell[] $cells    Die Zellen der Zeile
     * @param int    $rowIndex Der 1-basierte Zeilenindex
     */
    public function __construct(array $cells = [], int $rowIndex = 1) {
        $this->cells    = array_values($cells);
        $this->rowIndex = $rowIndex;
    }

    /**
     * Erstellt eine Zeile aus einem Array von Werten.
     *
     * @param array<mixed> $values   Die Zellwerte
     * @param int          $rowIndex Der 1-basierte Zeilenindex
     * @return self
     */
    public static function fromArray(array $values, int $rowIndex = 1): self {
        $cells = array_map(
            fn($value) => $value instanceof Cell ? $value : new Cell($value),
            $values
        );
        return new self($cells, $rowIndex);
    }

    /**
     * Gibt alle Zellen zurück.
     *
     * @return Cell[]
     */
    public function getCells(): array {
        return $this->cells;
    }

    /**
     * Gibt eine Zelle nach Index zurück (0-basiert).
     */
    public function getCell(int $index): ?Cell {
        return $this->cells[$index] ?? null;
    }

    /**
     * Gibt den Zeilenindex zurück (1-basiert).
     */
    public function getRowIndex(): int {
        return $this->rowIndex;
    }

    /**
     * Gibt die Anzahl der Zellen zurück.
     */
    public function count(): int {
        return count($this->cells);
    }

    /**
     * Gibt alle Zellwerte als Array zurück.
     *
     * @return array<mixed>
     */
    public function toArray(): array {
        return array_map(fn(Cell $cell) => $cell->getValue(), $this->cells);
    }

    /**
     * Gibt alle Zellwerte als String-Array zurück.
     *
     * @return array<string>
     */
    public function toStringArray(): array {
        return array_map(fn(Cell $cell) => $cell->getStringValue(), $this->cells);
    }

    /**
     * Prüft ob die Zeile leer ist (alle Zellen leer).
     */
    public function isEmpty(): bool {
        foreach ($this->cells as $cell) {
            if (!$cell->isEmpty()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return Traversable<int, Cell>
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->cells);
    }
}
