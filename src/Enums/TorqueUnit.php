<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TorqueUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum TorqueUnit: string {
    case MILLINEWTON_METER  = 'mN·m';
    case NEWTON_METER       = 'N·m';
    case KILONEWTON_METER   = 'kN·m';
    case NEWTON_CENTIMETER  = 'N·cm';
    case KILOGRAM_FORCE_METER   = 'kgf·m';
    case GRAM_FORCE_CENTIMETER  = 'gf·cm';
    case POUND_FORCE_FOOT   = 'lbf·ft';
    case POUND_FORCE_INCH   = 'lbf·in';
    case OUNCE_FORCE_INCH   = 'ozf·in';

    /**
     * Gibt den Umrechnungsfaktor in Newton·Meter zurück.
     */
    public function toNewtonMeters(): float {
        return match ($this) {
            self::MILLINEWTON_METER      => 0.001,
            self::NEWTON_METER           => 1.0,
            self::KILONEWTON_METER       => 1_000.0,
            self::NEWTON_CENTIMETER      => 0.01,
            self::KILOGRAM_FORCE_METER   => 9.80665,
            self::GRAM_FORCE_CENTIMETER  => 0.0000980665,
            self::POUND_FORCE_FOOT       => 1.3558179483314,
            self::POUND_FORCE_INCH       => 0.1129848290276,
            self::OUNCE_FORCE_INCH       => 0.0070615517266961,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::MILLINEWTON_METER      => 'Millinewtonmeter',
            self::NEWTON_METER           => 'Newtonmeter',
            self::KILONEWTON_METER       => 'Kilonewtonmeter',
            self::NEWTON_CENTIMETER      => 'Newtonzentimeter',
            self::KILOGRAM_FORCE_METER   => 'Kilopondmeter',
            self::GRAM_FORCE_CENTIMETER  => 'Gramm-Kraft·Zentimeter',
            self::POUND_FORCE_FOOT       => 'Pound-force·Fuß',
            self::POUND_FORCE_INCH       => 'Pound-force·Inch',
            self::OUNCE_FORCE_INCH       => 'Ounce-force·Inch',
        };
    }
}
