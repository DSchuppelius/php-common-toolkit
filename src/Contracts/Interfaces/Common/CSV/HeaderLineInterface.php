<?php
/*
 * Created on   : Wed Dec 25 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HeaderLineInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Interfaces\Common\CSV;

interface HeaderLineInterface extends LineInterface {
    /**
     * Liefert die Spaltennamen als String-Array zurück.
     *
     * @return string[]
     */
    public function getColumnNames(): array;

    /**
     * Prüft ob eine Spalte mit dem gegebenen Namen existiert.
     *
     * @param string $columnName
     * @return bool
     */
    public function hasColumn(string $columnName): bool;

    /**
     * Liefert den Index einer Spalte anhand des Namens.
     *
     * @param string $columnName
     * @return int|null
     */
    public function getColumnIndex(string $columnName): ?int;
}
