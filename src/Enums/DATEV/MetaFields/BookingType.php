<?php
/*
 * Created on   : Mon Dec 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\MetaFields;

enum BookingType: int {
    case FinancialAccounting      = 1; // Finanzbuchführung (default)
    case AnnualFinancialStatement = 2; // Jahresabschluss

    /**
     * DATEV-Regex-Muster für Buchungstyp.
     */
    public static function pattern(): string {
        return '^[1-2]$';
    }
}