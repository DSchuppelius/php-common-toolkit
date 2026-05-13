<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ElectricResistanceUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum ElectricResistanceUnit: string {
    case MICROOHM    = 'µΩ';
    case MILLIOHM    = 'mΩ';
    case OHM         = 'Ω';
    case KILOOHM     = 'kΩ';
    case MEGAOHM     = 'MΩ';
    case GIGAOHM     = 'GΩ';

    /**
     * Gibt den Umrechnungsfaktor in Ohm zurück.
     */
    public function toOhms(): float {
        return match ($this) {
            self::MICROOHM => 0.000001,
            self::MILLIOHM => 0.001,
            self::OHM      => 1.0,
            self::KILOOHM  => 1_000.0,
            self::MEGAOHM  => 1_000_000.0,
            self::GIGAOHM  => 1_000_000_000.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::MICROOHM => 'Mikroohm',
            self::MILLIOHM => 'Milliohm',
            self::OHM      => 'Ohm',
            self::KILOOHM  => 'Kiloohm',
            self::MEGAOHM  => 'Megaohm',
            self::GIGAOHM  => 'Gigaohm',
        };
    }
}
