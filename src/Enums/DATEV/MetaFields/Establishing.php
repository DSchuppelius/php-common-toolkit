<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Establishing.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\MetaFields;

use CommonToolkit\Traits\LockFlagTrait;

enum Establishing: int {
    use LockFlagTrait;

    case NONE   = 0; // keine Festschreibung
    case LOCKED = 1; // Festschreibung (default)
}