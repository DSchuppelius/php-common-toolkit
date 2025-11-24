<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum BookingType: string {
    case AA = 'AA'; // Angeforderte Anzahlung / Abschlagsrechnung
    case AG = 'AG'; // Erhaltene Anzahlung (Geldeingang)
    case AV = 'AV'; // Erhaltene Anzahlung (Verbindlichkeit)
    case SR = 'SR'; // Schlussrechnung
    case SU = 'SU'; // Schlussrechnung (Umbuchung)
    case SG = 'SG'; // Schlussrechnung (Geldeingang)
    case SO = 'SO'; // Sonstige

    /**
     * Deutsche Beschriftung für UI / Ausgabe / Fehlertexte.
     */
    public function getLabel(): string {
        return match ($this) {
            self::AA => 'Angeforderte Anzahlung / Abschlagsrechnung',
            self::AG => 'Erhaltene Anzahlung (Geldeingang)',
            self::AV => 'Erhaltene Anzahlung (Verbindlichkeit)',
            self::SR => 'Schlussrechnung',
            self::SU => 'Schlussrechnung (Umbuchung)',
            self::SG => 'Schlussrechnung (Geldeingang)',
            self::SO => 'Sonstige',
        };
    }

    /**
     * Factory für CSV/DATEV-Parser.
     */
    public static function fromStringValue(string $value): self {
        return match (strtoupper(trim($value))) {
            'AA' => self::AA,
            'AG' => self::AG,
            'AV' => self::AV,
            'SR' => self::SR,
            'SU' => self::SU,
            'SG' => self::SG,
            'SO' => self::SO,
            default => throw new InvalidArgumentException("Ungültiger DATEV-Buchungstyp: $value"),
        };
    }
}