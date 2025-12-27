<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OutputTarget.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Ausgabeziel für Debitoren/Kreditoren (Feld 106).
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum OutputTarget: int {
    case PRINT = 1; // Druck
    case FAX   = 2; // Telefax
    case EMAIL = 3; // E-Mail

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::PRINT => 'Druck',
            self::FAX   => 'Telefax',
            self::EMAIL => 'E-Mail',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::PRINT,
            2 => self::FAX,
            3 => self::EMAIL,
            default => throw new InvalidArgumentException("Ungültiges Ausgabeziel: $value"),
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
}
