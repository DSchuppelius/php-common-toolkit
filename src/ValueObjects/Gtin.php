<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Gtin.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\CompanyIdHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable, validierte GTIN (Global Trade Item Number, EAN/UPC).
 *
 * Unterstützt die Längen 8 (EAN-8), 12 (UPC-A), 13 (EAN-13) und 14
 * (GTIN-14); die Prüfziffernvalidierung delegiert an
 * {@see CompanyIdHelper::validateEAN()}. Typischer Einsatz:
 * Artikelidentifikation in E-Rechnungs-Positionen (XRechnung/ZUGFeRD
 * BT-157), openTRANS und Artikelstammdaten.
 *
 * Eine GTIN bezeichnet einen Artikel, keine Person — sie darf `Stringable`
 * und `JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $gtin = Gtin::of('4006381-333931');
 * $gtin->getValue();   // "4006381333931"
 * $gtin->getLength();  // 13
 * $gtin->toGtin14();   // "04006381333931" (Prüfziffer bleibt gültig)
 * ```
 */
final class Gtin implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Reine Ziffernfolge (8, 12, 13 oder 14 Stellen). */
    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte GTIN (vollständige Mod-10-Prüfziffer).
     *
     * @throws InvalidArgumentException Bei ungültiger Prüfziffer, Länge oder Nicht-Ziffern.
     */
    public static function of(string $value): self {
        $gtin = self::tryFrom($value);
        if ($gtin === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige GTIN: '" . trim($value) . "'");
        }

        return $gtin;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null) {
            return null;
        }

        // Nur übliche Trennzeichen entfernen — Buchstaben bleiben ungültig.
        $normalized = (string) preg_replace('/[\s.\-]/', '', trim($value));
        if ($normalized === '' || !ctype_digit($normalized) || !CompanyIdHelper::validateEAN($normalized)) {
            return null;
        }

        return new self($normalized);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Reine Ziffernfolge (z.B. "4006381333931").
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Länge der GTIN: 8, 12, 13 oder 14.
     */
    public function getLength(): int {
        return strlen($this->value);
    }

    /**
     * Prüfziffer (letzte Stelle).
     */
    public function getCheckDigit(): string {
        return substr($this->value, -1);
    }

    /**
     * GTIN-14-Darstellung (links mit Nullen aufgefüllt). Die Mod-10-Prüfziffer
     * ist rechtsbündig positioniert und bleibt beim Padding gültig.
     */
    public function toGtin14(): self {
        if ($this->getLength() === 14) {
            return $this;
        }

        return new self(str_pad($this->value, 14, '0', STR_PAD_LEFT));
    }

    /**
     * Exakte Gleichheit der normalisierten Ziffernfolge — eine GTIN-13 ist
     * NICHT gleich ihrer gepaddeten GTIN-14-Form. Für GS1-übergreifende
     * Vergleiche {@see toGtin14()}-Werte vergleichen.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }

    // ========================================================================
    // String / JSON
    // ========================================================================

    public function __toString(): string {
        return $this->value;
    }

    public function jsonSerialize(): string {
        return $this->value;
    }
}
