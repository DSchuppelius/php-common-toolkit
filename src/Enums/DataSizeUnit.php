<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataSizeUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Datenmenge – unterstützt sowohl SI-Präfixe (dezimal, 10er-Basis, IEC 80000-13)
 * als auch binäre IEC-Präfixe (2er-Basis, z. B. KiB, MiB).
 */
enum DataSizeUnit: string {
    // Bit-Einheiten (dezimal)
    case BIT        = 'bit';
    case KILOBIT    = 'kbit';
    case MEGABIT    = 'Mbit';
    case GIGABIT    = 'Gbit';
    case TERABIT    = 'Tbit';

    // Byte-Einheiten (dezimal, SI)
    case BYTE       = 'B';
    case KILOBYTE   = 'kB';     // 1.000 B
    case MEGABYTE   = 'MB';     // 1.000.000 B
    case GIGABYTE   = 'GB';
    case TERABYTE   = 'TB';
    case PETABYTE   = 'PB';

    // Byte-Einheiten (binär, IEC)
    case KIBIBYTE   = 'KiB';    // 1.024 B
    case MEBIBYTE   = 'MiB';    // 1.048.576 B
    case GIBIBYTE   = 'GiB';
    case TEBIBYTE   = 'TiB';
    case PEBIBYTE   = 'PiB';

    /**
     * Gibt den Umrechnungsfaktor in Bit zurück.
     */
    public function toBits(): float {
        return match($this) {
            self::BIT       => 1.0,
            self::KILOBIT   => 1_000.0,
            self::MEGABIT   => 1_000_000.0,
            self::GIGABIT   => 1_000_000_000.0,
            self::TERABIT   => 1_000_000_000_000.0,
            self::BYTE      => 8.0,
            self::KILOBYTE  => 8_000.0,
            self::MEGABYTE  => 8_000_000.0,
            self::GIGABYTE  => 8_000_000_000.0,
            self::TERABYTE  => 8_000_000_000_000.0,
            self::PETABYTE  => 8_000_000_000_000_000.0,
            self::KIBIBYTE  => 8.0 * 1024,
            self::MEBIBYTE  => 8.0 * 1024 ** 2,
            self::GIBIBYTE  => 8.0 * 1024 ** 3,
            self::TEBIBYTE  => 8.0 * 1024 ** 4,
            self::PEBIBYTE  => 8.0 * 1024 ** 5,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match($this) {
            self::BIT       => 'Bit',
            self::KILOBIT   => 'Kilobit',
            self::MEGABIT   => 'Megabit',
            self::GIGABIT   => 'Gigabit',
            self::TERABIT   => 'Terabit',
            self::BYTE      => 'Byte',
            self::KILOBYTE  => 'Kilobyte (SI)',
            self::MEGABYTE  => 'Megabyte (SI)',
            self::GIGABYTE  => 'Gigabyte (SI)',
            self::TERABYTE  => 'Terabyte (SI)',
            self::PETABYTE  => 'Petabyte (SI)',
            self::KIBIBYTE  => 'Kibibyte (IEC)',
            self::MEBIBYTE  => 'Mebibyte (IEC)',
            self::GIBIBYTE  => 'Gibibyte (IEC)',
            self::TEBIBYTE  => 'Tebibyte (IEC)',
            self::PEBIBYTE  => 'Pebibyte (IEC)',
        };
    }
}
