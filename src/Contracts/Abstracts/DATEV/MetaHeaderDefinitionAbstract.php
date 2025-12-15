<?php
/*
 * Created on   : Mon Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderDefinitionAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\{MetaHeaderFieldInterface, MetaHeaderDefinitionInterface};
use InvalidArgumentException;

/**
 * Abstrakte Basisklasse für DATEV MetaHeader-Definitionen.
 * Implementiert gemeinsame Logik für alle DATEV-Versionen.
 */
abstract class MetaHeaderDefinitionAbstract implements MetaHeaderDefinitionInterface {
    /**
     * DATEV-Versionsnummer - muss in Kindklassen als Konstante definiert werden.
     */
    protected const VERSION = null;

    public function getVersion(): int {
        $version = static::VERSION;
        if ($version === null) {
            throw new InvalidArgumentException('VERSION-Konstante muss in ' . static::class . ' definiert werden');
        }
        return $version;
    }

    public function countFields(): int {
        return count($this->getFields());
    }

    /**
     * Regex-Pattern für ein Feld aus der Felddefinition.
     * Standardimplementierung delegiert an das Feld selbst.
     */
    public function getValidationPattern(MetaHeaderFieldInterface $field): ?string {
        return $field->pattern();
    }

    /**
     * Abstrakte Methoden - müssen in Kindklassen implementiert werden.
     */

    /** @return class-string<MetaHeaderFieldInterface> */
    abstract public function getFieldEnum(): string;

    /** @return list<MetaHeaderFieldInterface> */
    abstract public function getFields(): array;

    abstract public function getDefaultValue(MetaHeaderFieldInterface $field): mixed;
}