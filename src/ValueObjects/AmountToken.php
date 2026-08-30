<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AmountToken.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\CurrencyCode;

/**
 * Ein Geldbetrag in einer Textzeile, mit Zeichenposition (für Spaltenzuordnung
 * in Layout-Text, z. B. Kontoauszüge).
 */
final class AmountToken {
    public function __construct(
        /** Der Zahlenteil so, wie er in der Zeile stand (z. B. "1.234,56"). */
        public readonly string $raw,
        /** Vorzeichenbehafteter Wert. */
        public readonly float $value,
        /** Trug der Betrag ein Vorzeichen (−/+, S/H, DR/CR, voran- oder nachgestellt)? */
        public readonly bool $hasSign,
        /** Zeichenposition der ersten Ziffer. */
        public readonly int $start,
        /** Zeichenposition hinter der letzten Ziffer (rechte Kante – Beträge sind rechtsbündig). */
        public readonly int $end,
        /**
         * Währung direkt am Betrag als ISO-4217-Code, falls vorhanden ("€" → EUR, "AU$" → AUD);
         * null, wenn der Betrag ohne Währung stand. {@see currencyCode()} liefert sie typisiert.
         */
        public readonly ?string $currency = null,
    ) {}

    public function isZero(): bool {
        return abs($this->value) < 0.005;
    }

    public function abs(): float {
        return abs($this->value);
    }

    public function isNegative(): bool {
        return $this->value < 0;
    }

    /** Währung als Enum; null, wenn der Betrag keine trug oder der Code unbekannt ist. */
    public function currencyCode(): ?CurrencyCode {
        return $this->currency === null ? null : CurrencyCode::tryFrom($this->currency);
    }
}
