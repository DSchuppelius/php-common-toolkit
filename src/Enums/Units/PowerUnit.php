<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PowerUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum PowerUnit: string {
    case WATT = 'W';
    case KILOWATT = 'kW';
    case MEGAWATT = 'MW';
    case GIGAWATT = 'GW';
    case MILLIWATT = 'mW';
    case METRIC_HP = 'PS';     // Pferdestärke (metrisch, DE/EU)
    case MECHANICAL_HP = 'hp';     // Horsepower (britisch/US)
    case BTU_PER_HOUR = 'BTU/h';
    case FOOT_POUND_PER_SEC = 'ft·lb/s';
    case CALORIE_PER_SECOND = 'cal/s';

    /**
     * Gibt den Umrechnungsfaktor in Watt zurück.
     */
    public function toWatts(): float {
        return match ($this) {
            self::WATT => 1.0,
            self::KILOWATT => 1_000.0,
            self::MEGAWATT => 1_000_000.0,
            self::GIGAWATT => 1_000_000_000.0,
            self::MILLIWATT => 0.001,
            self::METRIC_HP => 735.49875,
            self::MECHANICAL_HP => 745.69987158227,
            self::BTU_PER_HOUR => 0.29307107017,
            self::FOOT_POUND_PER_SEC => 1.3558179483314,
            self::CALORIE_PER_SECOND => 4.184,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::WATT => 'Watt',
            self::KILOWATT => 'Kilowatt',
            self::MEGAWATT => 'Megawatt',
            self::GIGAWATT => 'Gigawatt',
            self::MILLIWATT => 'Milliwatt',
            self::METRIC_HP => 'Pferdestärke (PS)',
            self::MECHANICAL_HP => 'Horsepower (hp)',
            self::BTU_PER_HOUR => 'BTU pro Stunde',
            self::FOOT_POUND_PER_SEC => 'Fuß-Pfund pro Sekunde',
            self::CALORIE_PER_SECOND => 'Kalorie pro Sekunde',
        };
    }
}
