<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IlluminanceUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Beleuchtungsstärke – Lichtmenge pro Fläche.
 * Basiseinheit: Lux (lx) = Lumen / Meter²
 */
enum IlluminanceUnit: string {
    case LUX            = 'lx';
    case MILLILUX       = 'mlx';
    case KILOLUX        = 'klx';
    case FOOT_CANDLE    = 'fc';     // 1 fc = 10,763910 lx
    case PHOT           = 'ph';     // CGS-Einheit (1 ph = 10.000 lx)
    case NOX            = 'nox';    // 1 nox = 0,001 lx (Dunkelheitsmaß)

    /**
     * Gibt den Umrechnungsfaktor in Lux zurück.
     */
    public function toLux(): float {
        return match ($this) {
            self::LUX         => 1.0,
            self::MILLILUX    => 0.001,
            self::KILOLUX     => 1_000.0,
            self::FOOT_CANDLE => 10.76391041671,
            self::PHOT        => 10_000.0,
            self::NOX         => 0.001,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match ($this) {
            self::LUX         => 'Lux',
            self::MILLILUX    => 'Millilux',
            self::KILOLUX     => 'Kilolux',
            self::FOOT_CANDLE => 'Foot-candle',
            self::PHOT        => 'Phot',
            self::NOX         => 'Nox',
        };
    }
}
