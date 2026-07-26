<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Gln.php
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
 * Immutable, validierte GLN (Global Location Number, 13-stellig).
 *
 * Identifiziert Unternehmen und Standorte — typischer Einsatz:
 * Peppol-/XRechnung-Adressierung (EAS 0088) und openTRANS-Partnerdaten.
 * Validierung (Mod-10-Prüfziffer) und Formatierung delegieren an
 * {@see CompanyIdHelper}.
 *
 * Eine GLN bezeichnet einen Standort, keine Person — sie darf `Stringable`
 * und `JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $gln = Gln::of('4-012345-00000-9');
 * $gln->getValue();   // "4012345000009"
 * $gln->formatted();  // "4-012345-00000-9"
 * ```
 */
final class Gln implements JsonSerializable, Stringable {
    use ErrorLog;

    /** 13-stellige Ziffernfolge. */
    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte GLN (13 Stellen, Mod-10-Prüfziffer).
     *
     * @throws InvalidArgumentException Bei ungültiger Prüfziffer, Länge oder Nicht-Ziffern.
     */
    public static function of(string $value): self {
        $gln = self::tryFrom($value);
        if ($gln === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige GLN: '" . trim($value) . "'");
        }

        return $gln;
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
        if ($normalized === '' || !ctype_digit($normalized) || !CompanyIdHelper::validateGLN($normalized)) {
            return null;
        }

        return new self($normalized);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * 13-stellige Ziffernfolge (z.B. "4012345000009").
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Prüfziffer (letzte Stelle).
     */
    public function getCheckDigit(): string {
        return substr($this->value, -1);
    }

    /**
     * Gruppiert formatiert (z.B. "4-012345-00000-9"), delegiert an
     * {@see CompanyIdHelper::formatGLN()}.
     */
    public function formatted(): string {
        return CompanyIdHelper::formatGLN($this->value);
    }

    /**
     * Gleichheit der normalisierten Ziffernfolge.
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
