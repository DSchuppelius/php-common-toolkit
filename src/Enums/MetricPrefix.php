<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetricPrefix.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum MetricPrefix: string {
    case PICO   = 'p';
    case NANO   = 'n';
    case MICRO  = 'µ';
    case MILLI  = 'm';
    case CENTI  = 'c';
    case DECI   = 'd';
    case NONE   = '';
    case DEKA   = 'da';
    case HECTO  = 'h';
    case KILO   = 'k';
    case MEGA   = 'M';
    case GIGA   = 'G';
    case TERA   = 'T';

    public static function prefixMap(): array {
        return [
            self::PICO->value  => -12,
            self::NANO->value  => -9,
            self::MICRO->value => -6,
            self::MILLI->value => -3,
            self::CENTI->value => -2,
            self::DECI->value  => -1,
            self::NONE->value  => 0,
            self::DEKA->value  => 1,
            self::HECTO->value => 2,
            self::KILO->value  => 3,
            self::MEGA->value  => 6,
            self::GIGA->value  => 9,
            self::TERA->value  => 12,
        ];
    }
}