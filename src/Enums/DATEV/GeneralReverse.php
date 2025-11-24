<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GeneralReverse.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum GeneralReverse: string {
    case NONE   = '0';  // keine Generalumkehr
    case ACTIVE = '1';  // Generalumkehr aktiv (inkl. G)

    /**
     * Deutsche Bezeichnung.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NONE   => 'Keine Generalumkehr',
            self::ACTIVE => 'Generalumkehr',
        };
    }

    /**
     * Normalisiert DATEV-Eingaben auf ein ENUM.
     *
     * G → ACTIVE
     * 1 → ACTIVE
     * 0 → NONE
     */
    public static function fromStringValue(string $value): self {
        $value = strtoupper(trim($value));

        return match ($value) {
            '1', 'G' => self::ACTIVE,
            '0'      => self::NONE,
            default   => throw new InvalidArgumentException("Ungültiges Generalumkehr-Kennzeichen: $value"),
        };
    }

    public function isActive(): bool {
        return $this === self::ACTIVE;
    }

    public function isNone(): bool {
        return $this === self::NONE;
    }
}