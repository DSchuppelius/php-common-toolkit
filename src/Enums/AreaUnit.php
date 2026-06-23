<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AreaUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum AreaUnit: string {
    case SQUARE_MILLIMETER = 'mm²';
    case SQUARE_CENTIMETER = 'cm²';
    case SQUARE_DECIMETER = 'dm²';
    case SQUARE_METER = 'm²';
    case ARE = 'a';      // 100 m²
    case HECTARE = 'ha';     // 10.000 m²
    case SQUARE_KILOMETER = 'km²';
    case SQUARE_INCH = 'in²';
    case SQUARE_FOOT = 'ft²';
    case SQUARE_YARD = 'yd²';
    case ACRE = 'ac';
    case SQUARE_MILE = 'mi²';

    /**
     * Gibt den Umrechnungsfaktor in Quadratmillimeter zurück.
     */
    public function toSquareMillimeters(): float {
        return match ($this) {
            self::SQUARE_MILLIMETER => 1.0,
            self::SQUARE_CENTIMETER => 100.0,
            self::SQUARE_DECIMETER => 10_000.0,
            self::SQUARE_METER => 1_000_000.0,
            self::ARE => 100_000_000.0,
            self::HECTARE => 10_000_000_000.0,
            self::SQUARE_KILOMETER => 1_000_000_000_000.0,
            self::SQUARE_INCH => 645.16,
            self::SQUARE_FOOT => 92_903.04,
            self::SQUARE_YARD => 836_127.36,
            self::ACRE => 4_046_856_422.4,
            self::SQUARE_MILE => 2_589_988_110_336.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::SQUARE_MILLIMETER => 'Quadratmillimeter',
            self::SQUARE_CENTIMETER => 'Quadratzentimeter',
            self::SQUARE_DECIMETER => 'Quadratdezimeter',
            self::SQUARE_METER => 'Quadratmeter',
            self::ARE => 'Ar',
            self::HECTARE => 'Hektar',
            self::SQUARE_KILOMETER => 'Quadratkilometer',
            self::SQUARE_INCH => 'Quadratzoll',
            self::SQUARE_FOOT => 'Quadratfuß',
            self::SQUARE_YARD => 'Quadratyard',
            self::ACRE => 'Acre',
            self::SQUARE_MILE => 'Quadratmeile',
        };
    }
}
