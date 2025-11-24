<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DiscountLock.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use CommonToolkit\Traits\LockFlagTrait;

enum DiscountLock: int {
    use LockFlagTrait;

    case NONE   = 0; // keine Skontosperre
    case LOCKED = 1; // Skontosperre aktiv
}