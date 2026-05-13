<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VolumeUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum VolumeUnit: string {
    case MICROLITER         = 'µl';
    case MILLILITER         = 'ml';
    case CENTILITER         = 'cl';
    case DECILITER          = 'dl';
    case LITER              = 'l';
    case CUBIC_METER        = 'm³';
    case CUBIC_CENTIMETER   = 'cm³';    // entspricht Milliliter
    case CUBIC_DECIMETER    = 'dm³';    // entspricht Liter
    case US_TEASPOON        = 'tsp_us';
    case US_TABLESPOON      = 'tbsp_us';
    case US_FLUID_OUNCE     = 'fl_oz_us';
    case US_CUP             = 'cup_us';
    case US_PINT            = 'pt_us';
    case US_QUART           = 'qt_us';
    case US_GALLON          = 'gal_us';
    case IMP_TEASPOON       = 'tsp_uk';
    case IMP_TABLESPOON     = 'tbsp_uk';
    case IMP_FLUID_OUNCE    = 'fl_oz_uk';
    case IMP_PINT           = 'pt_uk';
    case IMP_QUART          = 'qt_uk';
    case IMP_GALLON         = 'gal_uk';

    /**
     * Gibt den Umrechnungsfaktor in Milliliter zurück.
     */
    public function toMilliliters(): float {
        return match ($this) {
            self::MICROLITER        => 0.001,
            self::MILLILITER        => 1.0,
            self::CENTILITER        => 10.0,
            self::DECILITER         => 100.0,
            self::LITER             => 1000.0,
            self::CUBIC_METER       => 1_000_000.0,
            self::CUBIC_CENTIMETER  => 1.0,
            self::CUBIC_DECIMETER   => 1000.0,
            self::US_TEASPOON       => 4.92892159375,
            self::US_TABLESPOON     => 14.78676478125,
            self::US_FLUID_OUNCE    => 29.5735295625,
            self::US_CUP            => 236.5882365,
            self::US_PINT           => 473.176473,
            self::US_QUART          => 946.352946,
            self::US_GALLON         => 3785.411784,
            self::IMP_TEASPOON      => 5.91939,
            self::IMP_TABLESPOON    => 17.7582,
            self::IMP_FLUID_OUNCE   => 28.4130625,
            self::IMP_PINT          => 568.26125,
            self::IMP_QUART         => 1136.5225,
            self::IMP_GALLON        => 4546.09,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::MICROLITER        => 'Mikroliter',
            self::MILLILITER        => 'Milliliter',
            self::CENTILITER        => 'Centiliter',
            self::DECILITER         => 'Deziliter',
            self::LITER             => 'Liter',
            self::CUBIC_METER       => 'Kubikmeter',
            self::CUBIC_CENTIMETER  => 'Kubikzentimeter',
            self::CUBIC_DECIMETER   => 'Kubikdezimeter',
            self::US_TEASPOON       => 'Teelöffel (US)',
            self::US_TABLESPOON     => 'Esslöffel (US)',
            self::US_FLUID_OUNCE    => 'Fluid Ounce (US)',
            self::US_CUP            => 'Cup (US)',
            self::US_PINT           => 'Pint (US)',
            self::US_QUART          => 'Quart (US)',
            self::US_GALLON         => 'Gallone (US)',
            self::IMP_TEASPOON      => 'Teelöffel (britisch)',
            self::IMP_TABLESPOON    => 'Esslöffel (britisch)',
            self::IMP_FLUID_OUNCE   => 'Fluid Ounce (britisch)',
            self::IMP_PINT          => 'Pint (britisch)',
            self::IMP_QUART         => 'Quart (britisch)',
            self::IMP_GALLON        => 'Gallone (britisch)',
        };
    }
}
