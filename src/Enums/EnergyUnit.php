<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EnergyUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum EnergyUnit: string {
    case JOULE = 'J';
    case KILOJOULE = 'kJ';
    case MEGAJOULE = 'MJ';
    case GIGAJOULE = 'GJ';
    case WATT_HOUR = 'Wh';
    case KILOWATT_HOUR = 'kWh';
    case MEGAWATT_HOUR = 'MWh';
    case CALORIE = 'cal';    // Thermochemische Kalorie (4,184 J)
    case KILOCALORIE = 'kcal';   // Diätkalorie
    case BTU = 'BTU';    // British Thermal Unit
    case ELECTRON_VOLT = 'eV';
    case FOOT_POUND = 'ft·lb';  // Foot-pound (mechanische Arbeit)
    case THERM = 'thm';    // 1 Therm = 100.000 BTU

    /**
     * Gibt den Umrechnungsfaktor in Joule zurück.
     */
    public function toJoules(): float {
        return match ($this) {
            self::JOULE => 1.0,
            self::KILOJOULE => 1_000.0,
            self::MEGAJOULE => 1_000_000.0,
            self::GIGAJOULE => 1_000_000_000.0,
            self::WATT_HOUR => 3_600.0,
            self::KILOWATT_HOUR => 3_600_000.0,
            self::MEGAWATT_HOUR => 3_600_000_000.0,
            self::CALORIE => 4.184,
            self::KILOCALORIE => 4_184.0,
            self::BTU => 1_055.05585262,
            self::ELECTRON_VOLT => 1.602176634e-19,
            self::FOOT_POUND => 1.3558179483314,
            self::THERM => 105_480_400.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::JOULE => 'Joule',
            self::KILOJOULE => 'Kilojoule',
            self::MEGAJOULE => 'Megajoule',
            self::GIGAJOULE => 'Gigajoule',
            self::WATT_HOUR => 'Wattstunde',
            self::KILOWATT_HOUR => 'Kilowattstunde',
            self::MEGAWATT_HOUR => 'Megawattstunde',
            self::CALORIE => 'Kalorie',
            self::KILOCALORIE => 'Kilokalorie (kcal)',
            self::BTU => 'British Thermal Unit',
            self::ELECTRON_VOLT => 'Elektronenvolt',
            self::FOOT_POUND => 'Fuß-Pfund',
            self::THERM => 'Therm',
        };
    }
}
