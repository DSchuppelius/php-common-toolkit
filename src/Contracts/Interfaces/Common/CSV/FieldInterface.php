<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FieldInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Interfaces\Common\CSV;

use DateTimeImmutable;

interface FieldInterface {
    public const DEFAULT_ENCLOSURE = '"';

    public function getValue(): string;
    public function getTypedValue(): mixed;
    public function getRaw(): ?string;

    public function isQuoted(): bool;
    public function getEnclosureRepeat(): int;
    public function toString(?string $enclosure = null): string;

    // Type-Detection Methoden
    public function isInt(): bool;
    public function isFloat(): bool;
    public function isBool(): bool;
    public function isDateTime(?string $format = null): bool;
    public function isString(): bool;
}