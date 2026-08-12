<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MagneticFluxDensityUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum MagneticFluxDensityUnit: string {
    case NANOTESLA = 'nT';
    case MICROTESLA = 'µT';
    case MILLITESLA = 'mT';
    case TESLA = 'T';
    case GAUSS = 'G';      // CGS-Einheit (1 G = 0,0001 T)
    case MILLIGAUSS = 'mG';

    /**
     * Gibt den Umrechnungsfaktor in Tesla zurück.
     */
    public function toTesla(): float {
        return match ($this) {
            self::NANOTESLA => 1e-9,
            self::MICROTESLA => 1e-6,
            self::MILLITESLA => 0.001,
            self::TESLA => 1.0,
            self::GAUSS => 1e-4,
            self::MILLIGAUSS => 1e-7,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::NANOTESLA => 'Nanotesla',
            self::MICROTESLA => 'Mikrotesla',
            self::MILLITESLA => 'Millitesla',
            self::TESLA => 'Tesla',
            self::GAUSS => 'Gauß',
            self::MILLIGAUSS => 'Milligauß',
        };
    }
}
