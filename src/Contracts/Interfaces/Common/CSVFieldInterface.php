<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVFieldInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Interfaces\Common;

interface CSVFieldInterface {
    public const DEFAULT_ENCLOSURE = '"';

    public function getValue(): string;
    public function getRaw(): ?string;

    public function isQuoted(): bool;
    public function getEnclosureRepeat(): int;
    public function toString(?string $enclosure = null): string;
}