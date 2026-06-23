<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LengthUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum LengthUnit: string {
    case NANOMETER = 'nm';
    case MICROMETER = 'µm';
    case MILLIMETER = 'mm';
    case CENTIMETER = 'cm';
    case DECIMETER = 'dm';
    case METER = 'm';
    case KILOMETER = 'km';
    case INCH = 'in';
    case FOOT = 'ft';
    case YARD = 'yd';
    case MILE = 'mi';
    case NAUTICAL_MILE = 'nmi';
    case LIGHT_YEAR = 'ly';
    case ASTRONOMICAL_UNIT = 'au';

    /**
     * Gibt den Umrechnungsfaktor in Millimeter zurück.
     */
    public function toMillimeters(): float {
        return match ($this) {
            self::NANOMETER => 0.000001,
            self::MICROMETER => 0.001,
            self::MILLIMETER => 1.0,
            self::CENTIMETER => 10.0,
            self::DECIMETER => 100.0,
            self::METER => 1000.0,
            self::KILOMETER => 1_000_000.0,
            self::INCH => 25.4,
            self::FOOT => 304.8,
            self::YARD => 914.4,
            self::MILE => 1_609_344.0,
            self::NAUTICAL_MILE => 1_852_000.0,
            self::LIGHT_YEAR => 9.4607304725808e18,
            self::ASTRONOMICAL_UNIT => 1.495978707e14,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::NANOMETER => 'Nanometer',
            self::MICROMETER => 'Mikrometer',
            self::MILLIMETER => 'Millimeter',
            self::CENTIMETER => 'Zentimeter',
            self::DECIMETER => 'Dezimeter',
            self::METER => 'Meter',
            self::KILOMETER => 'Kilometer',
            self::INCH => 'Inch (Zoll)',
            self::FOOT => 'Fuß',
            self::YARD => 'Yard',
            self::MILE => 'Meile',
            self::NAUTICAL_MILE => 'Seemeile',
            self::LIGHT_YEAR => 'Lichtjahr',
            self::ASTRONOMICAL_UNIT => 'Astronomische Einheit',
        };
    }
}
