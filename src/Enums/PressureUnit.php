<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PressureUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum PressureUnit: string {
    case PASCAL         = 'Pa';
    case HECTOPASCAL    = 'hPa';
    case KILOPASCAL     = 'kPa';
    case MEGAPASCAL     = 'MPa';
    case BAR            = 'bar';
    case MILLIBAR       = 'mbar';
    case ATMOSPHERE     = 'atm';        // Physikalische Atmosphäre
    case TECHNICAL_ATM  = 'at';         // Technische Atmosphäre (kgf/cm²)
    case PSI            = 'psi';        // Pound-force per square inch
    case MMHG           = 'mmHg';       // Millimeter Quecksilbersäule / Torr
    case INHG           = 'inHg';       // Inch Quecksilbersäule
    case TORR           = 'Torr';

    /**
     * Gibt den Umrechnungsfaktor in Pascal zurück.
     */
    public function toPascal(): float {
        return match ($this) {
            self::PASCAL        => 1.0,
            self::HECTOPASCAL   => 100.0,
            self::KILOPASCAL    => 1_000.0,
            self::MEGAPASCAL    => 1_000_000.0,
            self::BAR           => 100_000.0,
            self::MILLIBAR      => 100.0,
            self::ATMOSPHERE    => 101_325.0,
            self::TECHNICAL_ATM => 98_066.5,
            self::PSI           => 6_894.757293168,
            self::MMHG          => 133.322387415,
            self::INHG          => 3_386.389,
            self::TORR          => 133.322387415,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::PASCAL        => 'Pascal',
            self::HECTOPASCAL   => 'Hektopascal',
            self::KILOPASCAL    => 'Kilopascal',
            self::MEGAPASCAL    => 'Megapascal',
            self::BAR           => 'Bar',
            self::MILLIBAR      => 'Millibar',
            self::ATMOSPHERE    => 'Atmosphäre (physikalisch)',
            self::TECHNICAL_ATM => 'Atmosphäre (technisch)',
            self::PSI           => 'Pfund pro Quadratzoll (psi)',
            self::MMHG          => 'Millimeter Quecksilbersäule',
            self::INHG          => 'Inch Quecksilbersäule',
            self::TORR          => 'Torr',
        };
    }
}
