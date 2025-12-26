<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TimeIntervalType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Zeitintervallart für wiederkehrende Buchungen (Feld 81).
 * Definiert, ob täglich oder monatlich gebucht wird.
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/recurring-bookings
 */
enum TimeIntervalType: string {
    case DAILY   = 'TAG';
    case MONTHLY = 'MON';

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::DAILY   => 'Täglich',
            self::MONTHLY => 'Monatlich',
        };
    }

    /**
     * Factory für String-Werte (case-insensitive).
     */
    public static function fromString(string $value): self {
        $normalized = strtoupper(trim($value, '" '));
        return match ($normalized) {
            'TAG'   => self::DAILY,
            'MON'   => self::MONTHLY,
            default => throw new InvalidArgumentException("Ungültige Zeitintervallart: $value"),
        };
    }

    /**
     * Factory mit null-Rückgabe bei ungültigen Werten.
     */
    public static function tryFromString(string $value): ?self {
        try {
            return self::fromString($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Prüft, ob täglich.
     */
    public function isDaily(): bool {
        return $this === self::DAILY;
    }

    /**
     * Prüft, ob monatlich.
     */
    public function isMonthly(): bool {
        return $this === self::MONTHLY;
    }

    /**
     * Gibt den CSV-Wert zurück (umschlossen in Anführungszeichen).
     */
    public function toCsvValue(): string {
        return '"' . $this->value . '"';
    }
}
