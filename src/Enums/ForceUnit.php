<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ForceUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum ForceUnit: string {
    case NEWTON             = 'N';
    case KILONEWTON         = 'kN';
    case MEGANEWTON         = 'MN';
    case MILLINEWTON        = 'mN';
    case DYN                = 'dyn';        // CGS-Einheit (1 dyn = 10⁻⁵ N)
    case KILOGRAM_FORCE     = 'kgf';        // Kilopond (kp)
    case GRAM_FORCE         = 'gf';
    case POUND_FORCE        = 'lbf';
    case OUNCE_FORCE        = 'ozf';
    case POUNDAL            = 'pdl';        // Britische absolute Krafteinheit

    /**
     * Gibt den Umrechnungsfaktor in Newton zurück.
     */
    public function toNewtons(): float {
        return match ($this) {
            self::NEWTON         => 1.0,
            self::KILONEWTON     => 1_000.0,
            self::MEGANEWTON     => 1_000_000.0,
            self::MILLINEWTON    => 0.001,
            self::DYN            => 1e-5,
            self::KILOGRAM_FORCE => 9.80665,
            self::GRAM_FORCE     => 0.00980665,
            self::POUND_FORCE    => 4.4482216152605,
            self::OUNCE_FORCE    => 0.27801385095378,
            self::POUNDAL        => 0.138254954376,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::NEWTON         => 'Newton',
            self::KILONEWTON     => 'Kilonewton',
            self::MEGANEWTON     => 'Meganewton',
            self::MILLINEWTON    => 'Millinewton',
            self::DYN            => 'Dyn',
            self::KILOGRAM_FORCE => 'Kilopond (kgf)',
            self::GRAM_FORCE     => 'Gramm-Kraft',
            self::POUND_FORCE    => 'Pound-force',
            self::OUNCE_FORCE    => 'Ounce-force',
            self::POUNDAL        => 'Poundal',
        };
    }
}
