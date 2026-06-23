<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ElectricVoltageUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum ElectricVoltageUnit: string {
    case MICROVOLT = 'µV';
    case MILLIVOLT = 'mV';
    case VOLT = 'V';
    case KILOVOLT = 'kV';
    case MEGAVOLT = 'MV';

    /**
     * Gibt den Umrechnungsfaktor in Volt zurück.
     */
    public function toVolts(): float {
        return match ($this) {
            self::MICROVOLT => 0.000001,
            self::MILLIVOLT => 0.001,
            self::VOLT => 1.0,
            self::KILOVOLT => 1_000.0,
            self::MEGAVOLT => 1_000_000.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::MICROVOLT => 'Mikrovolt',
            self::MILLIVOLT => 'Millivolt',
            self::VOLT => 'Volt',
            self::KILOVOLT => 'Kilovolt',
            self::MEGAVOLT => 'Megavolt',
        };
    }
}
