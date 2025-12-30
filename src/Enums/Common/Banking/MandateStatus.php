<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateStatus.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Mandate Status für SEPA-Mandate.
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum MandateStatus: string {
    case ACCEPTED = 'ACCP';      // Akzeptiert
    case PENDING = 'PDNG';       // Ausstehend
    case REJECTED = 'RJCT';      // Abgelehnt
    case CANCELLED = 'CANC';     // Storniert
    case SUSPENDED = 'SUSP';     // Ausgesetzt
    case EXPIRED = 'EXPD';       // Abgelaufen
    case AMENDED = 'AMND';       // Geändert

    public function isActive(): bool {
        return match ($this) {
            self::ACCEPTED,
            self::AMENDED => true,
            default => false,
        };
    }

    public function isTerminal(): bool {
        return match ($this) {
            self::REJECTED,
            self::CANCELLED,
            self::EXPIRED => true,
            default => false,
        };
    }

    public function description(): string {
        return match ($this) {
            self::ACCEPTED => 'Akzeptiert',
            self::PENDING => 'Ausstehend',
            self::REJECTED => 'Abgelehnt',
            self::CANCELLED => 'Storniert',
            self::SUSPENDED => 'Ausgesetzt',
            self::EXPIRED => 'Abgelaufen',
            self::AMENDED => 'Geändert',
        };
    }
}
