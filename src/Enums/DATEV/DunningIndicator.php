<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DunningIndicator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Mahnungs-Kennzeichen für Debitoren/Kreditoren (Feld 121).
 *
 * 0 = Keine Angaben
 * 1 = 1. Mahnung
 * 2 = 2. Mahnung
 * 3 = 1. + 2. Mahnung
 * 4 = 3. Mahnung
 * 5 = (nicht vergeben)
 * 6 = 2. + 3. Mahnung
 * 7 = 1., 2. + 3. Mahnung
 * 9 = keine Mahnung
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum DunningIndicator: int {
    case NONE        = 0; // Keine Angaben
    case LEVEL_1     = 1; // 1. Mahnung
    case LEVEL_2     = 2; // 2. Mahnung
    case LEVEL_1_2   = 3; // 1. + 2. Mahnung
    case LEVEL_3     = 4; // 3. Mahnung
        // 5 ist nicht vergeben
    case LEVEL_2_3   = 6; // 2. + 3. Mahnung
    case LEVEL_1_2_3 = 7; // 1., 2. + 3. Mahnung
    case DISABLED    = 9; // keine Mahnung

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::NONE        => 'Keine Angaben',
            self::LEVEL_1     => '1. Mahnung',
            self::LEVEL_2     => '2. Mahnung',
            self::LEVEL_1_2   => '1. + 2. Mahnung',
            self::LEVEL_3     => '3. Mahnung',
            self::LEVEL_2_3   => '2. + 3. Mahnung',
            self::LEVEL_1_2_3 => '1., 2. + 3. Mahnung',
            self::DISABLED    => 'keine Mahnung',
        };
    }

    /**
     * Factory für CSV/DATEV-Import.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            0 => self::NONE,
            1 => self::LEVEL_1,
            2 => self::LEVEL_2,
            3 => self::LEVEL_1_2,
            4 => self::LEVEL_3,
            6 => self::LEVEL_2_3,
            7 => self::LEVEL_1_2_3,
            9 => self::DISABLED,
            default => throw new InvalidArgumentException("Ungültiges Mahnungskennzeichen: $value (Wert 5 ist nicht vergeben)"),
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
     * Prüft, ob Mahnung(en) aktiv ist/sind.
     */
    public function hasDunning(): bool {
        return $this !== self::NONE && $this !== self::DISABLED;
    }

    /**
     * Prüft, ob 1. Mahnung enthalten ist.
     */
    public function hasFirstLevel(): bool {
        return in_array($this, [self::LEVEL_1, self::LEVEL_1_2, self::LEVEL_1_2_3], true);
    }

    /**
     * Prüft, ob 2. Mahnung enthalten ist.
     */
    public function hasSecondLevel(): bool {
        return in_array($this, [self::LEVEL_2, self::LEVEL_1_2, self::LEVEL_2_3, self::LEVEL_1_2_3], true);
    }

    /**
     * Prüft, ob 3. Mahnung enthalten ist.
     */
    public function hasThirdLevel(): bool {
        return in_array($this, [self::LEVEL_3, self::LEVEL_2_3, self::LEVEL_1_2_3], true);
    }
}
