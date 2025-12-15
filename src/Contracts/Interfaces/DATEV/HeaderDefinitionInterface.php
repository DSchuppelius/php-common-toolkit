<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HeaderDefinitionInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

/**
 * Interface für DATEV Header-Definitionen.
 * Definiert die Struktur verschiedener DATEV-Header-Typen (BookingBatch, Stammdaten, etc.).
 */
interface HeaderDefinitionInterface {
    /**
     * Liefert die Versionsnummer des Headers.
     */
    public function getVersion(): int;

    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<FieldHeaderInterface>
     */
    public function getFieldEnum(): string;

    /**
     * Liefert alle Felder in der korrekten Reihenfolge.
     *
     * @return FieldHeaderInterface[]
     */
    public function getFields(): array;

    /**
     * Liefert nur die verpflichtenden Felder.
     *
     * @return FieldHeaderInterface[]
     */
    public function getRequiredFields(): array;

    /**
     * Prüft, ob ein Feld in diesem Header gültig ist.
     */
    public function isValidField(FieldHeaderInterface $field): bool;

    /**
     * Liefert die Anzahl der definierten Felder.
     */
    public function getFieldCount(): int;

    /**
     * Validiert eine Feldliste gegen diese Definition.
     *
     * @param FieldHeaderInterface[] $fields
     * @throws InvalidArgumentException
     */
    public function validateFields(array $fields): void;
}
