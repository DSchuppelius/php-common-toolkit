<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SoBilFlag.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use CommonToolkit\Traits\LockFlagTrait;

enum SoBilFlag: int {
    use LockFlagTrait;

    case NONE   = 0; // Keine SoBil-Buchung erzeugt
    case LOCKED = 1; // SoBil-Buchung erzeugt
}