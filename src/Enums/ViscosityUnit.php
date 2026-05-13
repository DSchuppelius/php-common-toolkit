<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ViscosityUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Dynamische Viskosität (Einheit: Pascal·Sekunde = Pa·s).
 * Nicht zu verwechseln mit kinematischer Viskosität (m²/s).
 */
enum ViscosityUnit: string {
    case PASCAL_SECOND      = 'Pa·s';
    case MILLIPASCAL_SECOND = 'mPa·s';  // = 1 Centipoise
    case CENTIPOISE         = 'cP';
    case POISE              = 'P';       // CGS-Einheit (1 P = 0,1 Pa·s)
    case POUND_FORCE_SECOND_PER_SQUARE_FOOT = 'lbf·s/ft²';
    case POUND_PER_FOOT_SECOND = 'lb/(ft·s)'; // Poundal-basiert

    /**
     * Gibt den Umrechnungsfaktor in Pascal·Sekunde zurück.
     */
    public function toPascalSeconds(): float {
        return match($this) {
            self::PASCAL_SECOND                       => 1.0,
            self::MILLIPASCAL_SECOND                  => 0.001,
            self::CENTIPOISE                          => 0.001,
            self::POISE                               => 0.1,
            self::POUND_FORCE_SECOND_PER_SQUARE_FOOT  => 47.880259,
            self::POUND_PER_FOOT_SECOND               => 1.4881639,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match($this) {
            self::PASCAL_SECOND                       => 'Pascalsekunde',
            self::MILLIPASCAL_SECOND                  => 'Millipascalsekunde',
            self::CENTIPOISE                          => 'Centipoise',
            self::POISE                               => 'Poise',
            self::POUND_FORCE_SECOND_PER_SQUARE_FOOT  => 'Pound-force·Sekunde / Fuß²',
            self::POUND_PER_FOOT_SECOND               => 'Pfund / (Fuß·Sekunde)',
        };
    }
}
