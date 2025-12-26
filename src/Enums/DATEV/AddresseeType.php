<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AddresseeType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Adressatentyp für Debitoren/Kreditoren (Feld 7).
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum AddresseeType: int {
    case NONE           = 0; // keine Angabe (Default = Unternehmen)
    case NATURAL_PERSON = 1; // natürliche Person
    case COMPANY        = 2; // Unternehmen

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NONE           => 'keine Angabe',
            self::NATURAL_PERSON => 'natürliche Person',
            self::COMPANY        => 'Unternehmen',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::NONE,
            1 => self::NATURAL_PERSON,
            2 => self::COMPANY,
            default => throw new InvalidArgumentException("Ungültiger Adressatentyp: $value"),
        };
    }

    /**
     * Factory für String-Werte (quoted in DATEV-Format).
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '"');
        if ($trimmed === '') {
            return null;
        }
        return self::fromInt((int) $trimmed);
    }

    /**
     * Prüft, ob es sich um eine natürliche Person handelt.
     */
    public function isNaturalPerson(): bool {
        return $this === self::NATURAL_PERSON;
    }

    /**
     * Prüft, ob es sich um ein Unternehmen handelt.
     */
    public function isCompany(): bool {
        return $this === self::COMPANY || $this === self::NONE;
    }
}
