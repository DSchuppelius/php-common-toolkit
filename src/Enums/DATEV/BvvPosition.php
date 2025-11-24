<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BvvPosition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV;

use InvalidArgumentException;

enum BvvPosition: int {
    case CAPITAL_ADJUSTMENT      = 1; // Kapitalanpassung
    case WITHDRAWAL_DISTRIBUTION = 2; // Entnahme / Ausschüttung lfd. WJ
    case CONTRIBUTION_INJECTION  = 3; // Einlage / Kapitalzuführung lfd. WJ
    case SECTION6B_TRANSFER      = 4; // Übertragung § 6b Rücklage
    case RECLASSIFICATION        = 5; // Umbuchung (keine Zuordnung)

    /**
     * Liefert die deutsche Bezeichnung der BVV-Position.
     */
    public function getLabel(): string {
        return match ($this) {
            self::CAPITAL_ADJUSTMENT      => 'Kapitalanpassung',
            self::WITHDRAWAL_DISTRIBUTION => 'Entnahme / Ausschüttung lfd. Wirtschaftsjahr',
            self::CONTRIBUTION_INJECTION  => 'Einlage / Kapitalzuführung lfd. Wirtschaftsjahr',
            self::SECTION6B_TRANSFER      => 'Übertragung § 6b Rücklage',
            self::RECLASSIFICATION        => 'Umbuchung (keine Zuordnung)',
        };
    }

    /**
     * Factory für CSV/DATEV-Parser.
     */
    public static function fromInt(int $value): self {
        return match ($value) {
            1 => self::CAPITAL_ADJUSTMENT,
            2 => self::WITHDRAWAL_DISTRIBUTION,
            3 => self::CONTRIBUTION_INJECTION,
            4 => self::SECTION6B_TRANSFER,
            5 => self::RECLASSIFICATION,
            default => throw new InvalidArgumentException("Ungültige BVV-Position: $value"),
        };
    }
}