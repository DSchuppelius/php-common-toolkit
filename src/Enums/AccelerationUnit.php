<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AccelerationUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum AccelerationUnit: string {
    case METER_PER_SECOND_SQUARED = 'm/s²';
    case STANDARD_GRAVITY = 'g₀';    // Erdbeschleunigung (9,80665 m/s²)
    case FOOT_PER_SECOND_SQUARED = 'ft/s²';
    case INCH_PER_SECOND_SQUARED = 'in/s²';
    case GAL = 'Gal';    // CGS-Einheit (1 Gal = 0,01 m/s²)
    case MILLIGAL = 'mGal';
    case KNOT_PER_SECOND = 'kn/s';

    /**
     * Gibt den Umrechnungsfaktor in Meter pro Sekunde² zurück.
     */
    public function toMetersPerSecondSquared(): float {
        return match ($this) {
            self::METER_PER_SECOND_SQUARED => 1.0,
            self::STANDARD_GRAVITY => 9.80665,
            self::FOOT_PER_SECOND_SQUARED => 0.3048,
            self::INCH_PER_SECOND_SQUARED => 0.0254,
            self::GAL => 0.01,
            self::MILLIGAL => 0.00001,
            self::KNOT_PER_SECOND => 0.514444,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::METER_PER_SECOND_SQUARED => 'Meter pro Sekunde²',
            self::STANDARD_GRAVITY => 'Erdbeschleunigung (g₀)',
            self::FOOT_PER_SECOND_SQUARED => 'Fuß pro Sekunde²',
            self::INCH_PER_SECOND_SQUARED => 'Inch pro Sekunde²',
            self::GAL => 'Gal',
            self::MILLIGAL => 'Milligal',
            self::KNOT_PER_SECOND => 'Knoten pro Sekunde',
        };
    }
}
