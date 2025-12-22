<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderFieldInterface;
use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderDefinitionInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\Common\CSV\DataField;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use InvalidArgumentException;

final class MetaHeaderLine extends DataLine {
    /**
     * Mapping von Enum-Name auf Feldindex in $this->fields.
     *
     * @var array<string, int>
     */
    private array $fieldIndex = [];

    /**
     * Header-Definition für versionsneutrale Feldlokalisierung.
     */
    private MetaHeaderDefinitionInterface $definition;

    /**
     * @param MetaHeaderDefinitionInterface $definition Definition des DATEV-Meta-Headers
     * @param string              $delimiter  CSV-Trennzeichen
     * @param string              $enclosure  CSV-Textbegrenzer
     */
    public function __construct(MetaHeaderDefinitionInterface $definition, string $delimiter = Document::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE) {
        $this->definition = $definition;
        $rawFields = [];

        foreach ($definition->getFields() as $index => $field) {
            // Name → Index
            $this->fieldIndex[$field->name] = $index;

            // Defaultwert
            $default = $definition->getDefaultValue($field);
            $rawFields[$index] = $default === null ? '' : (string) $default;
        }

        parent::__construct($rawFields, $delimiter, $enclosure);
    }

    public function set(MetaHeaderFieldInterface $field, mixed $value): self {
        $pattern = $field->pattern();

        if ($pattern && !preg_match('/' . $pattern . '/u', (string) $value)) {
            throw new InvalidArgumentException(
                "Ungültiger Wert für {$field->label()} ({$field->name}): \"{$value}\" (Pattern: {$pattern})"
            );
        }

        if (!array_key_exists($field->name, $this->fieldIndex)) {
            throw new InvalidArgumentException("Unbekanntes MetaHeader-Feld: {$field->name}");
        }

        $index = $this->fieldIndex[$field->name];

        // Einfache Feld-Erstellung - nutze die Standard-CSV-Logik der Parent-Klasse
        $this->fields[$index] = static::createField((string) $value, $this->enclosure);

        return $this;
    }

    /**
     * Setzt einen Feldwert mit expliziter Quote-Information.
     * Wird für das korrekte Roundtrip-Verhalten bei DATEV-Importen verwendet.
     */
    public function setWithQuoteInfo(MetaHeaderFieldInterface $field, mixed $value, bool $wasQuoted): self {
        $pattern = $field->pattern();
        if ($pattern && !preg_match('/' . $pattern . '/u', (string) $value)) {
            throw new InvalidArgumentException(
                "Ungültiger Wert für {$field->label()} ({$field->name}): \"{$value}\" (Pattern: {$pattern})"
            );
        }

        if (!array_key_exists($field->name, $this->fieldIndex)) {
            throw new InvalidArgumentException("Unbekanntes MetaHeader-Feld: {$field->name}");
        }

        $index = $this->fieldIndex[$field->name];
        $stringValue = (string) $value;

        // Verwende die explizite Quote-Information aus der Original-Analyse
        if ($wasQuoted) {
            $quotedValue = $this->enclosure . $stringValue . $this->enclosure;
            $this->fields[$index] = new DataField($quotedValue, $this->enclosure);
        } else {
            $this->fields[$index] = new DataField($stringValue, $this->enclosure);
        }

        return $this;
    }

    public function get(MetaHeaderFieldInterface $field): mixed {
        $index = $this->fieldIndex[$field->name] ?? null;
        if ($index === null || !isset($this->fields[$index])) {
            return null;
        }

        return $this->fields[$index]->getValue();
    }

    /**
     * Assoziatives Array: Enum-Name => Wert.
     */
    public function toArray(): array {
        $result = [];

        // Reihenfolge kommt aus fieldIndex (wie im Konstruktor befüllt)
        foreach ($this->fieldIndex as $name => $index) {
            $result[$name] = isset($this->fields[$index])
                ? $this->fields[$index]->getValue()
                : null;
        }

        return $result;
    }

    /**
     * Typisierte Getter für häufig verwendete Felder - eliminiert Casts im Parser-Code.
     * Versionsneutral durch dynamische Feldlokalisierung über Definition.
     */

    public function getKennzeichen(): string {
        return (string)($this->getFieldByName('Kennzeichen') ?? '');
    }

    public function getVersionsnummer(): int {
        return (int)($this->getFieldByName('Versionsnummer') ?? 0);
    }

    public function getFormatkategorie(): ?Category {
        $value = (int)($this->getFieldByName('Formatkategorie') ?? 0);
        return Category::tryFrom($value);
    }

    public function getFormatname(): string {
        return (string)($this->getFieldByName('Formatname') ?? '');
    }

    public function getFormatversion(): int {
        return (int)($this->getFieldByName('Formatversion') ?? 0);
    }

    /**
     * Lokalisiert ein Feld anhand seines Namens über alle verfügbaren Felder der Definition.
     * Versionsneutral und robust gegen verschiedene DATEV-Versionen.
     */
    private function getFieldByName(string $fieldName): ?string {
        foreach ($this->definition->getFields() as $field) {
            if ($field->label() === $fieldName || $field->name === $fieldName) {
                return $this->get($field);
            }
        }
        return null;
    }

    /**
     * Convenience-Fabrik: aus Werteliste (Index 0..N) MetaHeaderLine bauen.
     *
     * @param MetaHeaderDefinitionInterface $definition
     * @param array<int, string|null>       $values
     */
    public static function fromValues(MetaHeaderDefinitionInterface $definition, array $values, string $delimiter = Document::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,): self {
        $line = new self($definition, $delimiter, $enclosure);

        foreach ($definition->getFields() as $index => $field) {
            if (array_key_exists($index, $values)) {
                $line->set($field, $values[$index]);
            }
        }

        return $line;
    }
}
