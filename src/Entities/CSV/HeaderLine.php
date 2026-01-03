<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\CSV;

use CommonToolkit\Contracts\Abstracts\CSV\LineAbstract;
use CommonToolkit\Contracts\Interfaces\CSV\FieldInterface;
use CommonToolkit\Contracts\Interfaces\CSV\LineInterface;
use CommonToolkit\Enums\CountryCode;

class HeaderLine extends LineAbstract {
    public function __construct(array $fields, string $delimiter = self::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE) {
        // HeaderLine erbt direkt von LineAbstract
        parent::__construct($fields, $delimiter, $enclosure);
    }

    protected static function createField(string $rawValue, string $enclosure): FieldInterface {
        return new HeaderField($rawValue, $enclosure, CountryCode::Germany);
    }

    /**
     * Liefert die Spaltennamen als String-Array zurück.
     *
     * @return string[]
     */
    public function getColumnNames(): array {
        return array_map(fn(FieldInterface $field) => $field->getValue(), $this->fields);
    }

    /**
     * Prüft ob eine Spalte mit dem gegebenen Namen existiert.
     *
     * @param string $columnName
     * @return bool
     */
    public function hasColumn(string $columnName): bool {
        return in_array($columnName, $this->getColumnNames(), true);
    }

    /**
     * Liefert den Index einer Spalte anhand des Namens.
     *
     * @param string $columnName
     * @return int|null
     */
    public function getColumnIndex(string $columnName): ?int {
        $index = array_search($columnName, $this->getColumnNames(), true);
        return $index !== false ? $index : null;
    }

    /**
     * Retrieves the trimmed value of a specific field from a data row by index.
     * 
     * Ermöglicht den Zugriff auf Feldwerte einer Datenzeile über den Spaltenindex.
     * Subklassen können typsichere Wrapper-Methoden mit Enum-Parametern anbieten.
     * 
     * @param LineInterface $row Die Datenzeile
     * @param int $index Der Spaltenindex (0-basiert)
     * @return string|null Der getrimmte Feldwert oder null wenn das Feld nicht existiert
     */
    public function getValueByIndex(LineInterface $row, int $index): ?string {
        $fieldObj = $row->getField($index);

        if ($fieldObj === null) {
            return null;
        }

        return trim($fieldObj->getValue());
    }

    /**
     * Checks if a specific field exists and has a non-empty value in the data row.
     * 
     * @param LineInterface $row Die Datenzeile
     * @param int $index Der Spaltenindex (0-basiert)
     * @return bool True wenn das Feld existiert und einen nicht-leeren Wert hat
     */
    public function hasValueByIndex(LineInterface $row, int $index): bool {
        $value = $this->getValueByIndex($row, $index);
        return $value !== null && $value !== '';
    }

    /**
     * Retrieves the trimmed value of a specific field from a data row by column name.
     * 
     * Ermöglicht den Zugriff auf Feldwerte einer Datenzeile über den Spaltennamen.
     * 
     * @param LineInterface $row Die Datenzeile
     * @param string $columnName Der Spaltenname
     * @return string|null Der getrimmte Feldwert oder null wenn die Spalte nicht existiert
     */
    public function getValueByName(LineInterface $row, string $columnName): ?string {
        $index = $this->getColumnIndex($columnName);

        if ($index === null) {
            return null;
        }

        return $this->getValueByIndex($row, $index);
    }

    /**
     * Checks if a specific field exists and has a non-empty value by column name.
     * 
     * @param LineInterface $row Die Datenzeile
     * @param string $columnName Der Spaltenname
     * @return bool True wenn die Spalte existiert und einen nicht-leeren Wert hat
     */
    public function hasValueByName(LineInterface $row, string $columnName): bool {
        $value = $this->getValueByName($row, $columnName);
        return $value !== null && $value !== '';
    }
}