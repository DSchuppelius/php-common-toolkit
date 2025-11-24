<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DiscountType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum DiscountType: int {
    case GOODS_PURCHASE       = 1; // Einkauf von Waren
    case MATERIALS_PURCHASE   = 2; // Roh-, Hilfs- und Betriebsstoffe

    /**
     * Liefert eine deutschsprachige Beschreibung.
     */
    public function getLabel(): string {
        return match ($this) {
            self::GOODS_PURCHASE     => 'Einkauf von Waren',
            self::MATERIALS_PURCHASE => 'Erwerb von Roh-, Hilfs- und Betriebsstoffen',
        };
    }

    /**
     * Erzeugt ein Enum aus einem numerischen DATEV-Wert.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::GOODS_PURCHASE,
            2 => self::MATERIALS_PURCHASE,
            default => throw new InvalidArgumentException("Ungültiger Skontotyp: $value"),
        };
    }
}