<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LockFlagTrait.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Traits;

use InvalidArgumentException;

/**
 * Gemeinsame Logik für alle 0/1 Sperr-Enums.
 */
trait LockFlagTrait {
    public function isLocked(): bool {
        return $this === self::LOCKED;
    }

    public function isNone(): bool {
        return $this === self::NONE;
    }

    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::NONE,
            1 => self::LOCKED,
            default => throw new InvalidArgumentException("Invalid lock value: $value"),
        };
    }
}