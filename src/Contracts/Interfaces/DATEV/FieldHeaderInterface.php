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

use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

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

    /**
     * Liefert die DATEV-Kategorie für dieses Header-Format.
     */
    public static function getCategory(): Category;

    /**
     * Liefert die DATEV-Version für dieses Header-Format.
     */
    public static function getVersion(): int;

    /**
     * Liefert die Anzahl der definierten Felder.
     */
    public static function getFieldCount(): int;

    /**
     * Prüft, ob ein Feldwert gültig ist (im Enum enthalten).
     */
    public static function isValidFieldValue(string $value): bool;

    /**
     * Gibt an, ob das Feld im FieldHeader gequotet werden soll.
     * Standardmäßig false - DATEV FieldHeader sind nicht gequotet.
     */
    public function isQuotedHeader(): bool;

    /**
     * Gibt an, ob Datenwerte für dieses Feld gequotet werden sollen.
     * Basiert auf dem Datentyp: Text-Felder = gequotet, numerische Felder = nicht gequotet.
     */
    public function isQuotedValue(): bool;
}
