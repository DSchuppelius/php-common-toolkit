<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVLineInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Interfaces\Common;

interface CSVLineInterface {
    public const DEFAULT_DELIMITER = ',';

    public function getFields(): array;
    public function getField(int $index): ?CSVFieldInterface;
    public function countFields(): int;
    public function getDelimiter(): string;
    public function getEnclosure(): string;
    public function getEnclosureRepeatRange(bool $includeUnquoted = false): array;
    public function toString(?string $delimiter = null, ?string $enclosure = null): string;
    public function equals(CSVLineInterface $other): bool;
}