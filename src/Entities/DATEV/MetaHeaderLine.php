<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevMetaHeaderLine.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Datev;

use CommonToolkit\Contracts\Interfaces\DATEV\{MetaHeaderInterface, MetaHeaderFieldInterface};
use InvalidArgumentException;

final class MetaHeaderLine {
    private array $values = [];

    public function __construct(private readonly MetaHeaderInterface $definition) {
        foreach ($definition->getFields() as $field) {
            $this->values[$field->name] = $definition->getDefaultValue($field);
        }
    }

    public function set(MetaHeaderFieldInterface $field, mixed $value): self {
        $pattern = $field->pattern();
        if ($pattern && !preg_match('/' . $pattern . '/u', (string)$value)) {
            throw new InvalidArgumentException("Ungültiger Wert für {$field->label()}: {$value}");
        }
        $this->values[$field->name] = $value;
        return $this;
    }

    public function get(MetaHeaderFieldInterface $field): mixed {
        return $this->values[$field->name] ?? null;
    }

    public function toArray(): array {
        return $this->values;
    }

    public function toString(string $delimiter = ';', string $enclosure = '"'): string {
        $ordered = [];
        foreach ($this->definition->getFields() as $field) {
            $val = $this->values[$field->name] ?? '';
            $ordered[] = $enclosure . (string)$val . $enclosure;
        }
        return implode($delimiter, $ordered);
    }
}