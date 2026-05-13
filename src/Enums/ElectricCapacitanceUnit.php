<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ElectricCapacitanceUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum ElectricCapacitanceUnit: string {
    case PICOFARAD  = 'pF';
    case NANOFARAD  = 'nF';
    case MICROFARAD = 'µF';
    case MILLIFARAD = 'mF';
    case FARAD      = 'F';
    case KILOFARAD  = 'kF';

    /**
     * Gibt den Umrechnungsfaktor in Farad zurück.
     */
    public function toFarads(): float {
        return match ($this) {
            self::PICOFARAD  => 1e-12,
            self::NANOFARAD  => 1e-9,
            self::MICROFARAD => 1e-6,
            self::MILLIFARAD => 0.001,
            self::FARAD      => 1.0,
            self::KILOFARAD  => 1_000.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::PICOFARAD  => 'Picofarad',
            self::NANOFARAD  => 'Nanofarad',
            self::MICROFARAD => 'Mikrofarad',
            self::MILLIFARAD => 'Millifarad',
            self::FARAD      => 'Farad',
            self::KILOFARAD  => 'Kilofarad',
        };
    }
}
