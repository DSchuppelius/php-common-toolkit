<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StatementIndicator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Kontoauszugs-Kennzeichen für Debitoren/Kreditoren (Feld 122).
 *
 * 1 = Kontoauszug für alle Posten
 * 2 = Auszug nur dann, wenn ein Posten mahnfähig ist
 * 3 = Auszug für alle mahnfälligen Posten
 * 9 = kein Kontoauszug
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum StatementIndicator: int {
    case ALL_ITEMS        = 1; // Kontoauszug für alle Posten
    case DUNNABLE_ONLY    = 2; // Auszug nur dann, wenn ein Posten mahnfähig ist
    case ALL_DUNNABLE     = 3; // Auszug für alle mahnfälligen Posten
    case DISABLED         = 9; // kein Kontoauszug

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::ALL_ITEMS     => 'Kontoauszug für alle Posten',
            self::DUNNABLE_ONLY => 'Auszug nur bei mahnfähigem Posten',
            self::ALL_DUNNABLE  => 'Auszug für alle mahnfälligen Posten',
            self::DISABLED      => 'kein Kontoauszug',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::ALL_ITEMS,
            2 => self::DUNNABLE_ONLY,
            3 => self::ALL_DUNNABLE,
            9 => self::DISABLED,
            default => throw new InvalidArgumentException("Ungültiges Kontoauszugskennzeichen: $value"),
        };
    }

    /**
     * Factory für String-Werte.
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        return self::fromInt((int) $trimmed);
    }

    /**
     * Prüft, ob Kontoauszug aktiviert ist.
     */
    public function isEnabled(): bool {
        return $this !== self::DISABLED;
    }

    /**
     * Prüft, ob es sich um einen mahnungsbezogenen Auszug handelt.
     */
    public function isDunningRelated(): bool {
        return $this === self::DUNNABLE_ONLY || $this === self::ALL_DUNNABLE;
    }
}
