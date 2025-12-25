<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NaturalStackHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderDefinitionAbstract;
use CommonToolkit\Contracts\Interfaces\DATEV\FieldHeaderInterface;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\NaturalStackHeaderField;
use InvalidArgumentException;

/**
 * Definition für DATEV Natürliche Personen-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für Natürliche Personen-Daten.
 */
final class NaturalStackHeaderDefinition extends HeaderDefinitionAbstract {
    public function getVersion(): int {
        return 700;
    }

    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<NaturalStackHeaderField>
     */
    public function getFieldEnum(): string {
        return NaturalStackHeaderField::class;
    }

    /**
     * Liefert alle Felder in der korrekten Reihenfolge.
     *
     * @return FieldHeaderInterface[]
     */
    public function getFields(): array {
        return NaturalStackHeaderField::ordered();
    }

    /**
     * Liefert nur die verpflichtenden Felder.
     *
     * @return FieldHeaderInterface[]
     */
    public function getRequiredFields(): array {
        return NaturalStackHeaderField::required();
    }

    /**
     * Prüft, ob ein Feld in diesem Header gültig ist.
     */
    public function isValidField(FieldHeaderInterface $field): bool {
        return $field instanceof NaturalStackHeaderField;
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
                    "Ungültiges Feld für Natürliche Personen: {$field->value}"
                );
            }
        }
    }
}
