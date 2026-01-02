<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVFieldAccessorTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Traits\CSV;

use CommonToolkit\Entities\CSV\DataLine;
use RuntimeException;

/**
 * Trait für den Zugriff auf Feldwerte in CSV-Dokumenten.
 * 
 * Bietet geschützte Methoden für:
 * - Lesen von Feldwerten nach Zeilen- und Spaltenindex
 * - Schreiben von Feldwerten mit immutable Pattern
 * 
 * Wird von Document-Subklassen verwendet, die auf Felder zugreifen müssen.
 * 
 * @package CommonToolkit\Traits\CSV
 */
trait CSVFieldAccessorTrait {
    /**
     * Muss die Zeilen zurückgeben.
     * @return DataLine[]
     */
    abstract public function getRows(): array;

    /**
     * Muss das Trennzeichen zurückgeben.
     */
    abstract public function getDelimiter(): string;

    /**
     * Muss das Einschlusszeichen zurückgeben.
     */
    abstract public function getEnclosure(): string;

    /**
     * Gibt den Rohwert eines Feldes zurück.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @return string|null Der Wert oder null wenn nicht vorhanden.
     */
    protected function getFieldValue(int $rowIndex, int $fieldIndex): ?string {
        $rows = $this->getRows();

        if (!isset($rows[$rowIndex])) {
            return null;
        }

        $fields = $rows[$rowIndex]->getFields();
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
            $this->getDelimiter(),
            $this->getEnclosure()
        );
    }
}
