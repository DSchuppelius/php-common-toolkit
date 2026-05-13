<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FrequencyUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum FrequencyUnit: string {
    case HERTZ          = 'Hz';
    case KILOHERTZ      = 'kHz';
    case MEGAHERTZ      = 'MHz';
    case GIGAHERTZ      = 'GHz';
    case TERAHERTZ      = 'THz';
    case RPM            = 'rpm';    // Umdrehungen pro Minute
    case BPM            = 'bpm';    // Schläge pro Minute (Musik/Medizin)
    case MILLIHERTZ     = 'mHz';

    /**
     * Gibt den Umrechnungsfaktor in Hertz zurück.
     */
    public function toHertz(): float {
        return match ($this) {
            self::HERTZ      => 1.0,
            self::KILOHERTZ  => 1_000.0,
            self::MEGAHERTZ  => 1_000_000.0,
            self::GIGAHERTZ  => 1_000_000_000.0,
            self::TERAHERTZ  => 1_000_000_000_000.0,
            self::RPM        => 1.0 / 60.0,
            self::BPM        => 1.0 / 60.0,
            self::MILLIHERTZ => 0.001,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::HERTZ      => 'Hertz',
            self::KILOHERTZ  => 'Kilohertz',
            self::MEGAHERTZ  => 'Megahertz',
            self::GIGAHERTZ  => 'Gigahertz',
            self::TERAHERTZ  => 'Terahertz',
            self::RPM        => 'Umdrehungen pro Minute',
            self::BPM        => 'Schläge pro Minute',
            self::MILLIHERTZ => 'Millihertz',
        };
    }
}
