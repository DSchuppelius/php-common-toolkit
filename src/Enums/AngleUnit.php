<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AngleUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum AngleUnit: string {
    case DEGREE = '°';
    case RADIAN = 'rad';
    case GRADIAN = 'gon';    // Neugrad / Gon (400 Gon = 360°)
    case ARCMINUTE = "'";      // Winkelminute (1° = 60')
    case ARCSECOND = '"';      // Winkelsekunde (1° = 3600'')
    case TURN = 'tr';     // Vollkreis (1 tr = 360°)
    case MILLIRADIAN = 'mrad';   // Militärische Anwendungen

    /**
     * Gibt den Umrechnungsfaktor in Grad (Dezimalgrad) zurück.
     */
    public function toDegrees(): float {
        return match ($this) {
            self::DEGREE => 1.0,
            self::RADIAN => 180.0 / M_PI,
            self::GRADIAN => 0.9,               // 360 / 400
            self::ARCMINUTE => 1.0 / 60.0,
            self::ARCSECOND => 1.0 / 3600.0,
            self::TURN => 360.0,
            self::MILLIRADIAN => 180.0 / (M_PI * 1000.0),
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::DEGREE => 'Grad',
            self::RADIAN => 'Radiant',
            self::GRADIAN => 'Gon (Neugrad)',
            self::ARCMINUTE => 'Winkelminute',
            self::ARCSECOND => 'Winkelsekunde',
            self::TURN => 'Vollkreis',
            self::MILLIRADIAN => 'Milliradiant',
        };
    }
}
