<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AddressType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

/**
 * DATEV Adressart für Debitoren/Kreditoren (Feld 15, 153).
 *
 * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/debitorskreditors
 */
enum AddressType: string {
    case STREET       = 'STR'; // Straße
    case PO_BOX       = 'PF';  // Postfach
    case KEY_ACCOUNT  = 'GK';  // Großkunde

    /**
     * Deutsche Textbezeichnung für UI/Logging.
     */
    public function getLabel(): string {
        return match ($this) {
            self::STREET      => 'Straße',
            self::PO_BOX      => 'Postfach',
            self::KEY_ACCOUNT => 'Großkunde',
        };
    }

    /**
     * Factory für CSV/DATEV-Import (mit Anführungszeichen).
     */
    public static function tryFromString(string $value): ?self {
        $trimmed = trim($value, '" ');
        if ($trimmed === '') {
            return null;
        }
        return match (strtoupper($trimmed)) {
            'STR' => self::STREET,
            'PF'  => self::PO_BOX,
            'GK'  => self::KEY_ACCOUNT,
            default => throw new InvalidArgumentException("Ungültige Adressart: $value"),
        };
    }
}
