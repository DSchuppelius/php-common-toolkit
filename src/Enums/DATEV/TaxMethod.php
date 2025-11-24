<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TaxMethod.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum TaxMethod: string {
    case IST           = 'I'; // Ist-Versteuerung
    case NONE          = 'K'; // Keine Umsatzsteuerrechnung
    case PAUSCHAL      = 'P'; // Pauschalierung (L+F)
    case SOLL          = 'S'; // Soll-Versteuerung

    /**
     * Liefert eine deutschsprachige Bezeichnung.
     */
    public function getLabel(): string {
        return match ($this) {
            self::IST      => 'Ist-Versteuerung',
            self::NONE     => 'Keine Umsatzsteuerrechnung',
            self::PAUSCHAL => 'Pauschalierung',
            self::SOLL     => 'Soll-Versteuerung',
        };
    }

    /**
     * Erzeugt ein Enum aus einem DATEV-Buchungswert (ein Buchstabe).
     */
    public static function fromStringValue(string $value): self {
        return match (strtoupper(trim($value))) {
            'I' => self::IST,
            'K' => self::NONE,
            'P' => self::PAUSCHAL,
            'S' => self::SOLL,
            default => throw new InvalidArgumentException("Ungültige Versteuerungsart: $value"),
        };
    }
}