<?php
/*
 * Created on   : Fri Sep 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PercentileMethod.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

/**
 * Verfahren für {@see \CommonToolkit\Helper\Data\NumberHelper::percentile()}.
 */
enum PercentileMethod: string {
    /** Lineare Interpolation zwischen den Nachbarwerten (Hyndman-Fan Typ 7, wie Excel QUANTIL.INKL). */
    case Linear = 'linear';
    /** Nächster Rang: kleinster Wert, unter dem mindestens p % der Werte liegen (kein Zwischenwert). */
    case NearestRank = 'nearest_rank';
}
