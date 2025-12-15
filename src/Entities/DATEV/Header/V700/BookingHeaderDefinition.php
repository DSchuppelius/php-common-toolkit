<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\{FieldHeaderInterface, HeaderDefinitionInterface};
use CommonToolkit\Enums\DATEV\V700\BookingHeaderField;
use InvalidArgumentException;

/**
 * Definition für DATEV Buchungsstapel-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für Buchungsdaten.
 */
final class BookingHeaderDefinition implements HeaderDefinitionInterface {
    public function getVersion(): int {
        return 700;
    }

    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<BookingHeaderField>
     */
    public function getFieldEnum(): string {
        return BookingHeaderField::class;
    }

    /**
     * Liefert alle Felder in der korrekten Reihenfolge.
     *
     * @return FieldHeaderInterface[]
     */
    public function getFields(): array {
        return BookingHeaderField::ordered();
    }

    /**
     * Liefert nur die verpflichtenden Felder.
     *
     * @return FieldHeaderInterface[]
     */
    public function getRequiredFields(): array {
        return BookingHeaderField::required();
    }

    /**
     * Prüft, ob ein Feld in diesem Header gültig ist.
     */
    public function isValidField(FieldHeaderInterface $field): bool {
        return $field instanceof BookingHeaderField;
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
                    "Ungültiges Feld für Buchungsstapel: {$field->value}"
                );
            }
        }
    }
}
