<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FieldHeaderInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

/**
 * Interface für DATEV Feldheader-Definitionen.
 * Definiert die Spaltenbeschreibungen für verschiedene DATEV-Formate.
 */
interface FieldHeaderInterface {
    /**
     * Liefert alle Felder in der korrekten Reihenfolge.
     * 
     * @return static[]
     */
    public static function ordered(): array;

    /**
     * Liefert die verpflichtenden Felder.
     * 
     * @return static[]
     */
    public static function required(): array;

    /**
     * Prüft, ob das Feld verpflichtend ist.
     */
    public function isRequired(): bool;
}
