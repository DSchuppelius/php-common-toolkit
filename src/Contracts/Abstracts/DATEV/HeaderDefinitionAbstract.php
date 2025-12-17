<?php
/*
 * Created on   : Mon Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HeaderDefinitionAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\{FieldHeaderInterface, HeaderDefinitionInterface};
use InvalidArgumentException;

/**
 * Abstrakte Basisklasse für DATEV V700 Header-Definitionen.
 * Kapselt die gemeinsame Funktionalität aller DATEV-Header-Definitionen.
 */
abstract class HeaderDefinitionAbstract implements HeaderDefinitionInterface {
    /**
     * Liefert die DATEV-Version (immer 700 für V700-Implementierungen).
     */
    public function getVersion(): int {
        return 700;
    }

    /**
     * Liefert den Enum-Typ für die Header-Felder.
     * Muss von konkreten Implementierungen überschrieben werden.
     *
     * @return class-string<FieldHeaderInterface>
     */
    abstract public function getFieldEnum(): string;

    /**
     * Liefert alle Felder in der korrekten Reihenfolge.
     * Verwendet den Enum-Typ der konkreten Implementierung.
     *
     * @return FieldHeaderInterface[]
     */
    public function getFields(): array {
        $enumClass = $this->getFieldEnum();
        return $enumClass::ordered();
    }

    /**
     * Liefert nur die verpflichtenden Felder.
     * Verwendet den Enum-Typ der konkreten Implementierung.
     *
     * @return FieldHeaderInterface[]
     */
    public function getRequiredFields(): array {
        $enumClass = $this->getFieldEnum();
        return $enumClass::required();
    }

    /**
     * Prüft, ob ein Feld in diesem Header gültig ist.
     * Verwendet den Enum-Typ der konkreten Implementierung.
     */
    public function isValidField(FieldHeaderInterface $field): bool {
        $enumClass = $this->getFieldEnum();
        return $field instanceof $enumClass;
    }

    /**
     * Liefert die Anzahl der definierten Felder.
     */
    public function getFieldCount(): int {
        return count($this->getFields());
    }

    /**
     * Liefert die Anzahl der verpflichtenden Felder.
     */
    public function getRequiredFieldCount(): int {
        return count($this->getRequiredFields());
    }

    /**
     * Validiert eine Feldliste gegen diese Definition.
     *
     * @param FieldHeaderInterface[] $fields
     * @throws InvalidArgumentException
     */
    public function validateFields(array $fields): void {
        $requiredFields = $this->getRequiredFields();
        $providedFields = array_map(fn($f) => $f->value, $fields);
        $requiredValues = array_map(fn($f) => $f->value, $requiredFields);

        $missing = array_diff($requiredValues, $providedFields);
        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Verpflichtende Felder fehlen: ' . implode(', ', $missing)
            );
        }

        foreach ($fields as $field) {
            if (!$this->isValidField($field)) {
                throw new InvalidArgumentException(
                    "Ungültiges Feld für {$this->getFormatName()}: {$field->value}"
                );
            }
        }
    }

    /**
     * Liefert den Namen des Formats für Fehlermeldungen.
     * Kann von konkreten Implementierungen überschrieben werden.
     */
    protected function getFormatName(): string {
        $className = static::class;
        $baseName = basename(str_replace('\\', '/', $className));
        return str_replace('HeaderDefinition', '', $baseName);
    }
}