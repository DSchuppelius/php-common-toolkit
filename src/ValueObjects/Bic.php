<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Bic.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\BankHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutabler, validierter BIC (Business Identifier Code, ISO 9362).
 *
 * Die Normalisierung erfolgt auf Großschreibung ohne Whitespace, die
 * Strukturprüfung delegiert an {@see BankHelper::isBIC()}. BIC8 und die
 * dazugehörige BIC11 mit "XXX"-Filialcode gelten fachlich als gleich —
 * intern wird deshalb kanonisch BIC11 gespeichert.
 *
 * Ein BIC bezeichnet ein Institut, kein Konto — er darf deshalb (anders als
 * {@see Iban}) `Stringable` und `JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $bic = Bic::of('deutdeff');
 * $bic->getValue();       // "DEUTDEFFXXX" (kanonisch BIC11)
 * $bic->getCountry();     // CountryCode::Germany
 * $bic->getBranchCode();  // null (XXX = Hauptsitz)
 * $bic->equals(Bic::of('DEUTDEFFXXX')); // true
 * ```
 */
final class Bic implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Kanonische BIC11-Darstellung. */
    private readonly string $value;

    private readonly CountryCode $country;

    private function __construct(string $value, CountryCode $country) {
        $this->value = $value;
        $this->country = $country;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen validierten BIC (BIC8 oder BIC11).
     *
     * @throws InvalidArgumentException Bei ungültiger Struktur oder unbekanntem Land.
     */
    public static function of(string $value): self {
        $bic = self::tryFrom($value);
        if ($bic === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger BIC: '" . trim($value) . "'");
        }

        return $bic;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper((string) preg_replace('/\s+/', '', $value));
        if ($normalized === '' || !BankHelper::isBIC($normalized)) {
            return null;
        }

        // Ein gültiger BIC muss einem bekannten Land zuordenbar sein (Stellen 5-6).
        $country = CountryCode::tryFrom(substr($normalized, 4, 2));
        if ($country === null) {
            return null;
        }

        // Kanonisch BIC11: BIC8 entspricht der BIC11 mit "XXX"-Filialcode.
        if (strlen($normalized) === 8) {
            $normalized .= 'XXX';
        }

        return new self($normalized, $country);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonische BIC11-Darstellung (z.B. "DEUTDEFFXXX").
     */
    public function getValue(): string {
        return $this->value;
    }

    public function getCountry(): CountryCode {
        return $this->country;
    }

    /**
     * Institutscode (Stellen 1-4, z.B. "DEUT").
     */
    public function getInstitutionCode(): string {
        return substr($this->value, 0, 4);
    }

    /**
     * Ortscode (Stellen 7-8, z.B. "FF").
     */
    public function getLocationCode(): string {
        return substr($this->value, 6, 2);
    }

    /**
     * Filialcode (Stellen 9-11) oder null beim Hauptsitz ("XXX").
     */
    public function getBranchCode(): ?string {
        $branch = substr($this->value, 8, 3);

        return $branch === 'XXX' ? null : $branch;
    }

    /**
     * Explizite BIC11-Darstellung (identisch mit {@see getValue()}).
     */
    public function asBic11(): string {
        return $this->value;
    }

    /**
     * Fachliche Gleichheit über die kanonische BIC11 — BIC8 und BIC11 mit
     * "XXX" sind gleich.
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
