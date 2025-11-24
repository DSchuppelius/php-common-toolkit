<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentMethod.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum PaymentMethod: int {
    case DIRECT_DEBIT = 1; // Lastschrift
    case DUNNING      = 2; // Mahnung
    case PAYMENT      = 3; // Zahlung

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::DIRECT_DEBIT => 'Lastschrift',
            self::DUNNING      => 'Mahnung',
            self::PAYMENT      => 'Zahlung',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::DIRECT_DEBIT,
            2 => self::DUNNING,
            3 => self::PAYMENT,
            default => throw new InvalidArgumentException("Ungültige Zahlweise: $value"),
        };
    }
}