<?php
/*
 * Created on   : Thu Apr 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CaseType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

use InvalidArgumentException;

enum CreditDebit: string {
    case CREDIT = 'Credit'; // Gutschrift
    case DEBIT = 'Debit';   // Lastschrift

    public function toMt940Code(): string {
        return $this === self::CREDIT ? 'C' : 'D';
    }

    public function toCamt053Code(): string {
        return $this === self::CREDIT ? 'CRDT' : 'DBIT';
    }

    public function getSymbol(): string {
        return $this === self::CREDIT ? '+' : '-';
    }

    public function getLabel(): string {
        return $this === self::CREDIT ? 'Gutschrift' : 'Lastschrift';
    }

    public static function fromMt940Code(string $code): self {
        return match (strtoupper($code)) {
            'C' => self::CREDIT,
            'D' => self::DEBIT,
            default => throw new \InvalidArgumentException("Ungültiger MT940-Code: $code"),
        };
    }

    public static function fromCamt053Code(string $code): self {
        return match (strtoupper($code)) {
            'CRDT' => self::CREDIT,
            'DBIT' => self::DEBIT,
            default => throw new \InvalidArgumentException("Ungültiger CAMT.053-Code: $code"),
        };
    }
}
