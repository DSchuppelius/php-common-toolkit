<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderDefinitionInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

interface MetaHeaderDefinitionInterface {
    public function getDefaultValue(MetaHeaderFieldInterface $field): mixed;

    public function countFields(): int;

    /** @return class-string<MetaHeaderFieldInterface> */
    public function getFieldEnum(): string;

    /** @return list<MetaHeaderFieldInterface> */
    public function getFields(): array;

    public function getVersion(): int;
}