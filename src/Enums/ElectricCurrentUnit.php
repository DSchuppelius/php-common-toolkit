<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ElectricCurrentUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum ElectricCurrentUnit: string {
    case NANOAMPERE  = 'nA';
    case MICROAMPERE = 'µA';
    case MILLIAMPERE = 'mA';
    case AMPERE      = 'A';
    case KILOAMPERE  = 'kA';

    /**
     * Gibt den Umrechnungsfaktor in Ampere zurück.
     */
    public function toAmperes(): float {
        return match($this) {
            self::NANOAMPERE  => 0.000000001,
            self::MICROAMPERE => 0.000001,
            self::MILLIAMPERE => 0.001,
            self::AMPERE      => 1.0,
            self::KILOAMPERE  => 1_000.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match($this) {
            self::NANOAMPERE  => 'Nanoampere',
            self::MICROAMPERE => 'Mikroampere',
            self::MILLIAMPERE => 'Milliampere',
            self::AMPERE      => 'Ampere',
            self::KILOAMPERE  => 'Kiloampere',
        };
    }
}
