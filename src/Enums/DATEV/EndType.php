<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EndType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Endetyp für wiederkehrende Buchungen (Feld 87).
 * Definiert, wie die Wiederholung endet.
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/recurring-bookings
 */
enum EndType: int {
    case NO_END     = 1; // Kein Ende (unbegrenzt)
    case BY_COUNT   = 2; // Endet nach Anzahl Wiederholungen
    case BY_DATE    = 3; // Endet an einem bestimmten Datum

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NO_END   => 'Kein Ende',
            self::BY_COUNT => 'Nach Anzahl',
            self::BY_DATE  => 'Am Datum',
        };
    }

    /**
     * Ausführliche Beschreibung für UI-Tooltips.
     */
    public function getDescription(): string {
        return match ($this) {
            self::NO_END   => 'Die wiederkehrende Buchung läuft unbegrenzt weiter',
            self::BY_COUNT => 'Die wiederkehrende Buchung endet nach einer bestimmten Anzahl von Wiederholungen',
            self::BY_DATE  => 'Die wiederkehrende Buchung endet an einem bestimmten Datum',
        };
    }

    /**
     * Factory für Integer-Werte.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::NO_END,
            2 => self::BY_COUNT,
            3 => self::BY_DATE,
            default => throw new InvalidArgumentException("Ungültiger Endetyp: $value"),
        };
    }

    /**
     * Factory mit null-Rückgabe bei ungültigen Werten.
     */
    public static function tryFromInt(int $value): ?self {
        try {
            return self::fromInt($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Factory für String-Werte.
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '" ');
        if ($trimmed === '' || !is_numeric($trimmed)) {
            return null;
        }
        return self::tryFromInt((int) $trimmed);
    }

    /**
     * Prüft, ob unbegrenzt.
     */
    public function isUnlimited(): bool {
        return $this === self::NO_END;
    }

    /**
     * Prüft, ob nach Anzahl.
     */
    public function isByCount(): bool {
        return $this === self::BY_COUNT;
    }

    /**
     * Prüft, ob nach Datum.
     */
    public function isByDate(): bool {
        return $this === self::BY_DATE;
    }

    /**
     * Prüft, ob eine Endbedingung definiert ist.
     */
    public function hasEndCondition(): bool {
        return $this !== self::NO_END;
    }

    /**
     * Prüft, ob das Feld "Anzahl" (Feld 88) relevant ist.
     */
    public function requiresCount(): bool {
        return $this === self::BY_COUNT;
    }

    /**
     * Prüft, ob das Feld "Endedatum" (Feld 89) relevant ist.
     */
    public function requiresEndDate(): bool {
        return $this === self::BY_DATE;
    }
}
