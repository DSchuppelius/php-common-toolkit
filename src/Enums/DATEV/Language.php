<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Language.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Sprache für Debitoren/Kreditoren (Feld 101).
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum Language: int {
    case GERMAN  = 1;
    case FRENCH  = 4;
    case ENGLISH = 5;
    case SPANISH = 10;
    case ITALIAN = 19;

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::GERMAN  => 'Deutsch',
            self::FRENCH  => 'Französisch',
            self::ENGLISH => 'Englisch',
            self::SPANISH => 'Spanisch',
            self::ITALIAN => 'Italienisch',
        };
    }

    /**
     * ISO 639-1 Sprachcode.
     */
    public function getIsoCode(): string {
        return match ($this) {
            self::GERMAN  => 'de',
            self::FRENCH  => 'fr',
            self::ENGLISH => 'en',
            self::SPANISH => 'es',
            self::ITALIAN => 'it',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1  => self::GERMAN,
            4  => self::FRENCH,
            5  => self::ENGLISH,
            10 => self::SPANISH,
            19 => self::ITALIAN,
            default => throw new InvalidArgumentException("Ungültige Sprache: $value"),
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
