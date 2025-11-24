<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AccountingPurpose.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

enum AccountingPurpose: int {
    case INDEPENDENT       = 0;  // Unabhängig
    case TAX_LAW           = 30; // Steuerrecht
    case CALCULATION       = 40; // Kalkulatorik
    case COMMERCIAL_LAW    = 50; // Handelsrecht
    case IFRS              = 64; // IFRS
}