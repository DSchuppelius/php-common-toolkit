<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RoundingMode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Rundungsverfahren für präzisionswahrende (bcmath-)Berechnungen.
 *
 * bcmath selbst rundet nie – es schneidet Richtung Null ab. Diese Modi steuern
 * das Verhalten von {@see \CommonToolkit\Helper\Data\NumberHelper::roundPrecise()}
 * und der darauf aufbauenden Methoden.
 */
enum RoundingMode: string {
    /** Kaufmännisch: bei .5 vom Betrag weg (2,5 → 3; -2,5 → -3). Standard. */
    case HalfUp = 'half_up';
    /** Bei .5 zum Betrag hin (2,5 → 2; -2,5 → -2). */
    case HalfDown = 'half_down';
    /** Banker's Rounding: bei .5 zur nächsten geraden Ziffer (2,5 → 2; 3,5 → 4). */
    case HalfEven = 'half_even';
    /** Immer Richtung +∞ (2,1 → 3; -2,9 → -2). */
    case Ceil = 'ceil';
    /** Immer Richtung −∞ (2,9 → 2; -2,1 → -3). */
    case Floor = 'floor';
    /** Abschneiden Richtung Null, kein Runden (2,9 → 2; -2,9 → -2). */
    case Truncate = 'truncate';
}
