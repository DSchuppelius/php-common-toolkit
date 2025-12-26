<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DunningSubject.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Sachverhalt für OPOS (Feld 24).
 * Identifiziert Buchungen/Posten als Mahnzins oder Mahngebühr.
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/recurring-bookings
 */
enum DunningSubject: int {
    case NONE           = 0;  // Keine Angabe
    case DUNNING_INTEREST = 31; // Mahnzins
    case DUNNING_FEE    = 40; // Mahngebühr

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NONE           => 'Keine Angabe',
            self::DUNNING_INTEREST => 'Mahnzins',
            self::DUNNING_FEE    => 'Mahngebühr',
        };
    }

    /**
     * Factory für Integer-Werte.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0  => self::NONE,
            31 => self::DUNNING_INTEREST,
            40 => self::DUNNING_FEE,
            default => throw new InvalidArgumentException("Ungültiger Sachverhalt: $value"),
        };
    }

    /**
     * Factory für String-Werte mit null-Rückgabe bei ungültigen Werten.
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '" ');
        if ($trimmed === '') {
            return null;
        }

        try {
            return self::fromInt((int) $trimmed);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Prüft, ob es sich um einen Mahnzins handelt.
     */
    public function isDunningInterest(): bool {
        return $this === self::DUNNING_INTEREST;
    }

    /**
     * Prüft, ob es sich um eine Mahngebühr handelt.
     */
    public function isDunningFee(): bool {
        return $this === self::DUNNING_FEE;
    }

    /**
     * Prüft, ob ein Mahn-Sachverhalt vorliegt.
     */
    public function isDunningRelated(): bool {
        return $this === self::DUNNING_INTEREST || $this === self::DUNNING_FEE;
    }
}
