<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header;

use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Contracts\Interfaces\DATEV\{FieldHeaderInterface, HeaderDefinitionInterface};
use CommonToolkit\Entities\Common\CSV\HeaderField;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\Document;
use InvalidArgumentException;

/**
 * DATEV Buchungsstapel Header-Zeile (Spaltenbeschreibungen).
 * Zweite Zeile im DATEV-Format nach dem MetaHeader.
 * Versionsunabhängig - arbeitet mit HeaderDefinitionInterface.
 */
final class BookingHeaderLine extends HeaderLine {
    private HeaderDefinitionInterface $definition;
    private array $fieldIndex = [];

    /**
     * @param HeaderDefinitionInterface $definition Header-Definition (versionsspezifisch)
     * @param string $delimiter CSV-Trennzeichen
     * @param string $enclosure CSV-Textbegrenzer
     */
    public function __construct(
        HeaderDefinitionInterface $definition,
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ) {
        $this->definition = $definition;

        // Alle Felder aus der Definition als Header setzen
        $rawFields = [];
        $fields = $definition->getFields();

        foreach ($fields as $index => $field) {
            $this->fieldIndex[$field->value] = $index;
            // DATEV Header-Felder sind immer in Anführungszeichen
            $rawFields[$index] = '"' . $field->value . '"';
        }

        parent::__construct($rawFields, $delimiter, $enclosure);
    }

    /**
     * Factory-Methode für minimalen Header (nur Pflichtfelder).
     */
    public static function createMinimal(
        HeaderDefinitionInterface $definition,
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ): self {
        $instance = new self($definition, $delimiter, $enclosure);

        // Nur Pflichtfelder setzen
        $requiredFields = $definition->getRequiredFields();
        $rawFields = [];
        $fieldIndex = [];

        foreach ($requiredFields as $index => $field) {
            $rawFields[$index] = '"' . $field->value . '"';
            $fieldIndex[$field->value] = $index;
        }

        // Neu initialisieren mit reduzierten Feldern
        $instance->fields = [];
        $instance->fieldIndex = $fieldIndex;

        foreach ($rawFields as $rawField) {
            $instance->fields[] = new HeaderField($rawField, $enclosure);
        }

        return $instance;
    }

    /**
     * Liefert die Header-Definition.
     */
    public function getDefinition(): HeaderDefinitionInterface {
        return $this->definition;
    }

    /**
     * Prüft, ob ein Feld in diesem Header vorhanden ist.
     */
    public function hasField(FieldHeaderInterface|string $field): bool {
        $fieldName = $field instanceof FieldHeaderInterface ? $field->value : $field;
        return isset($this->fieldIndex[$fieldName]);
    }

    /**
     * Liefert den Index eines Feldes oder -1 wenn nicht gefunden.
     */
    public function getFieldIndex(FieldHeaderInterface|string $field): int {
        $fieldName = $field instanceof FieldHeaderInterface ? $field->value : $field;
        return $this->fieldIndex[$fieldName] ?? -1;
    }

    /**
     * Validiert den Header gegen die Definition.
     */
    public function validate(): void {
        $fieldValues = array_map(fn($f) => trim($f->getValue(), '"'), $this->getFields());
        $this->definition->validateFields($fieldValues);
    }

    /**
     * Prüft, ob dieser Header zu einem bestimmten DATEV-Format passt.
     * Ermittelt anhand des Enums ob der Header zur V700 Buchungsstapel passt.
     */
    public function isCompatibleWithEnum(string $enumClass): bool {
        if (!enum_exists($enumClass)) {
            return false;
        }

        // Alle Header-Feldnamen aus diesem Header
        $headerFields = array_map(fn($f) => trim($f->getValue(), '"'), $this->getFields());

        // Alle möglichen Werte aus dem Enum
        $enumValues = array_map(fn($case) => $case->value, $enumClass::cases());

        // Prüfe ob alle Header-Felder im Enum existieren
        foreach ($headerFields as $headerField) {
            if (!in_array($headerField, $enumValues, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ermittelt zu welchem DATEV-Format dieser Header passt.
     * 
     * @param string[] $candidateEnums Liste der zu prüfenden Enum-Klassen
     * @return string|null Erste passende Enum-Klasse oder null
     */
    public function detectFormat(array $candidateEnums): ?string {
        foreach ($candidateEnums as $enumClass) {
            if ($this->isCompatibleWithEnum($enumClass)) {
                return $enumClass;
            }
        }
        return null;
    }

    /**
     * Convenience-Methode: Prüft ob dieser Header zu V700 Buchungsstapel passt.
     */
    public function isV700BookingHeader(): bool {
        return $this->isCompatibleWithEnum(\CommonToolkit\Enums\DATEV\V700\BookingHeaderField::class);
    }

    protected static function createField(string $rawValue, string $enclosure): FieldInterface {
        return new HeaderField($rawValue, $enclosure);
    }
}
