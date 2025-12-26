<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyControl.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Währungssteuerung für Debitoren/Kreditoren (Feld 107).
 *
 * 0 = Zahlungen in Eingabewährung
 * 2 = Ausgabe in EUR
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum CurrencyControl: int {
    case INPUT_CURRENCY = 0; // Zahlungen in Eingabewährung
    case OUTPUT_EUR     = 2; // Ausgabe in EUR

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::INPUT_CURRENCY => 'Zahlungen in Eingabewährung',
            self::OUTPUT_EUR     => 'Ausgabe in EUR',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::INPUT_CURRENCY,
            2 => self::OUTPUT_EUR,
            default => throw new InvalidArgumentException("Ungültige Währungssteuerung: $value"),
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
     * Prüft, ob EUR als Ausgabewährung verwendet wird.
     */
    public function isEuroOutput(): bool {
        return $this === self::OUTPUT_EUR;
    }
}
