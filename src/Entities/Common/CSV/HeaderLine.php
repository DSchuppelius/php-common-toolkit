<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSV\LineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
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
}
