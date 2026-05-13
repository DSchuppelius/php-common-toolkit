<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WeightUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum WeightUnit: string {
    case MICROGRAM   = 'µg';
    case MILLIGRAM   = 'mg';
    case GRAM        = 'g';
    case KILOGRAM    = 'kg';
    case METRIC_TON  = 't';
    case OUNCE       = 'oz';       // Avoirdupois-Unze
    case POUND       = 'lb';
    case STONE       = 'st';
    case SHORT_TON   = 'ton_us';   // US-Tonne (2000 lb)
    case LONG_TON    = 'ton_uk';   // Britische Tonne (2240 lb)
    case CARAT       = 'ct';       // Metrisches Karat (0,2 g)
    case GRAIN       = 'gr';       // Grain (64,79891 mg)

    /**
     * Gibt den Umrechnungsfaktor in Gramm zurück.
     */
    public function toGrams(): float {
        return match ($this) {
            self::MICROGRAM   => 0.000001,
            self::MILLIGRAM   => 0.001,
            self::GRAM        => 1.0,
            self::KILOGRAM    => 1000.0,
            self::METRIC_TON  => 1_000_000.0,
            self::OUNCE       => 28.349523125,
            self::POUND       => 453.59237,
            self::STONE       => 6350.29318,
            self::SHORT_TON   => 907184.74,
            self::LONG_TON    => 1_016_046.9088,
            self::CARAT       => 0.2,
            self::GRAIN       => 0.06479891,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::MICROGRAM   => 'Mikrogramm',
            self::MILLIGRAM   => 'Milligramm',
            self::GRAM        => 'Gramm',
            self::KILOGRAM    => 'Kilogramm',
            self::METRIC_TON  => 'Tonne (metrisch)',
            self::OUNCE       => 'Unze',
            self::POUND       => 'Pfund',
            self::STONE       => 'Stone',
            self::SHORT_TON   => 'Tonne (US)',
            self::LONG_TON    => 'Tonne (britisch)',
            self::CARAT       => 'Karat',
            self::GRAIN       => 'Grain',
        };
    }
}
