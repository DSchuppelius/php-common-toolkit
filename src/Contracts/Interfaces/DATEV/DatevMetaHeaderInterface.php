<?php

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

interface DatevMetaHeaderInterface {
    public function getVersion(): int;

    /** @return class-string<DatevMetaHeaderFieldInterface> */
    public function getFieldEnum(): string;

    /** @return list<DatevMetaHeaderFieldInterface> */
    public function getFields(): array;

    public function getDefaultValue(DatevMetaHeaderFieldInterface $field): mixed;
}
