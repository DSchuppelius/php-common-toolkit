<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SpeedUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum SpeedUnit: string {
    case METER_PER_SECOND       = 'm/s';
    case KILOMETER_PER_HOUR     = 'km/h';
    case MILE_PER_HOUR          = 'mph';
    case FOOT_PER_SECOND        = 'ft/s';
    case KNOT                   = 'kn';
    case MACH                   = 'Ma';     // Schallgeschwindigkeit bei 20 °C (343 m/s)
    case SPEED_OF_LIGHT         = 'c';      // Lichtgeschwindigkeit im Vakuum

    /**
     * Gibt den Umrechnungsfaktor in Meter pro Sekunde zurück.
     */
    public function toMetersPerSecond(): float {
        return match ($this) {
            self::METER_PER_SECOND   => 1.0,
            self::KILOMETER_PER_HOUR => 1.0 / 3.6,
            self::MILE_PER_HOUR      => 0.44704,
            self::FOOT_PER_SECOND    => 0.3048,
            self::KNOT               => 0.514444,
            self::MACH               => 343.0,
            self::SPEED_OF_LIGHT     => 299_792_458.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::METER_PER_SECOND   => 'Meter pro Sekunde',
            self::KILOMETER_PER_HOUR => 'Kilometer pro Stunde',
            self::MILE_PER_HOUR      => 'Meile pro Stunde',
            self::FOOT_PER_SECOND    => 'Fuß pro Sekunde',
            self::KNOT               => 'Knoten',
            self::MACH               => 'Mach',
            self::SPEED_OF_LIGHT     => 'Lichtgeschwindigkeit',
        };
    }
}
