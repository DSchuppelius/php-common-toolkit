<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TimeUnit.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum TimeUnit: string {
    case NANOSECOND  = 'ns';
    case MICROSECOND = 'µs';
    case MILLISECOND = 'ms';
    case SECOND      = 's';
    case MINUTE      = 'min';
    case HOUR        = 'h';
    case DAY         = 'd';
    case WEEK        = 'w';
    case MONTH       = 'mo';    // Durchschnitt: 365.2425 / 12 Tage
    case YEAR        = 'a';     // Julianisches Jahr: 365.2425 Tage
    case DECADE      = 'dec';
    case CENTURY     = 'cent';

    /**
     * Gibt den Umrechnungsfaktor in Sekunden zurück.
     */
    public function toSeconds(): float {
        return match($this) {
            self::NANOSECOND  => 1e-9,
            self::MICROSECOND => 1e-6,
            self::MILLISECOND => 1e-3,
            self::SECOND      => 1.0,
            self::MINUTE      => 60.0,
            self::HOUR        => 3600.0,
            self::DAY         => 86400.0,
            self::WEEK        => 604800.0,
            self::MONTH       => 2629746.0,   // 365.2425 * 86400 / 12
            self::YEAR        => 31556952.0,  // 365.2425 * 86400
            self::DECADE      => 315569520.0,
            self::CENTURY     => 3155695200.0,
        };
    }

    /**
     * Gibt den menschenlesbaren deutschen Namen zurück.
     */
    public function label(): string {
        return match($this) {
            self::NANOSECOND  => 'Nanosekunde',
            self::MICROSECOND => 'Mikrosekunde',
            self::MILLISECOND => 'Millisekunde',
            self::SECOND      => 'Sekunde',
            self::MINUTE      => 'Minute',
            self::HOUR        => 'Stunde',
            self::DAY         => 'Tag',
            self::WEEK        => 'Woche',
            self::MONTH       => 'Monat',
            self::YEAR        => 'Jahr',
            self::DECADE      => 'Jahrzehnt',
            self::CENTURY     => 'Jahrhundert',
        };
    }
}
