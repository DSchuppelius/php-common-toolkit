<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : InterestCalculationIndicator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Zinsberechnungs-Kennzeichen für Debitoren/Kreditoren (Feld 129).
 *
 * 0 = MPD-Schlüsselung gilt
 * 1 = Fester Zinssatz
 * 2 = Zinssatz über Staffel
 * 9 = Keine Berechnung für diesen Debitor
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum InterestCalculationIndicator: int {
    case MPD_DEFAULT      = 0; // MPD-Schlüsselung gilt
    case FIXED_RATE       = 1; // Fester Zinssatz
    case GRADUATED_RATE   = 2; // Zinssatz über Staffel
    case DISABLED         = 9; // Keine Berechnung für diesen Debitor

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::MPD_DEFAULT    => 'MPD-Schlüsselung gilt',
            self::FIXED_RATE     => 'Fester Zinssatz',
            self::GRADUATED_RATE => 'Zinssatz über Staffel',
            self::DISABLED       => 'Keine Berechnung',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::MPD_DEFAULT,
            1 => self::FIXED_RATE,
            2 => self::GRADUATED_RATE,
            9 => self::DISABLED,
            default => throw new InvalidArgumentException("Ungültiges Zinsberechnungskennzeichen: $value"),
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
     * Prüft, ob Zinsberechnung aktiv ist.
     */
    public function isEnabled(): bool {
        return $this !== self::DISABLED;
    }
}
