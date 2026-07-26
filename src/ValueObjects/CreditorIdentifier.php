<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorIdentifier.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\CreditorIdHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte SEPA-Gläubiger-Identifikationsnummer (Creditor
 * Identifier, CI).
 *
 * Die Konstruktion normalisiert (Großschreibung, ohne Trennzeichen) und
 * verlangt die vollständige Prüfziffernvalidierung über
 * {@see CreditorIdHelper::validateCreditorId()}.
 *
 * SENSIBLER WERT: Die Gläubiger-ID ist ein finanzieller Identifikator
 * (SEPA-Lastschriftmandate). Die Klasse implementiert deshalb bewusst WEDER
 * `Stringable` NOCH `JsonSerializable` — der Klarwert ist ausschließlich
 * über den bewusst aufgerufenen {@see getValue()}-Getter verfügbar; für
 * Anzeigen gibt es {@see masked()}.
 *
 * @example
 * ```php
 * $ci = CreditorIdentifier::of('de 98 zzz 09999999999');
 * $ci->getValue();              // "DE98ZZZ09999999999"
 * $ci->getBusinessAreaCode();   // "ZZZ"
 * $ci->masked();                // "DE98ZZZXXXXXXX9999"
 * ```
 */
final class CreditorIdentifier {
    use ErrorLog;

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
     * Erzeugt eine validierte Gläubiger-ID (vollständige Prüfziffernprüfung).
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige ID gehört nicht ins Log.
     *
     * @throws InvalidArgumentException Bei ungültiger Gläubiger-ID.
     */
    public static function of(string $value): self {
        $creditorId = self::tryFrom($value);
        if ($creditorId === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige Gläubiger-ID (Format-/Prüfziffernprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $creditorId;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = CreditorIdHelper::normalize($value);
        if (!CreditorIdHelper::validateCreditorId($normalized)) {
            return null;
        }

        // Eine gültige Gläubiger-ID muss einem bekannten Land zuordenbar sein.
        $countryCode = CreditorIdHelper::extractCountryCode($normalized);
        $country = $countryCode === null ? null : CountryCode::tryFrom($countryCode);
        if ($country === null) {
            return null;
        }

        return new self($normalized, $country);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (normalisiert, z.B. "DE98ZZZ09999999999") — nur bewusst
     * abrufen; für Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    public function getCountry(): CountryCode {
        return $this->country;
    }

    /**
     * Geschäftsbereichskennung (Stellen 5-7, häufig "ZZZ").
     */
    public function getBusinessAreaCode(): string {
        // Für eine validierte Instanz nie null; Fallback nur für PHPStan.
        return CreditorIdHelper::extractBusinessAreaCode($this->value) ?? '';
    }

    /**
     * Nationales Identifikationsmerkmal (ab Stelle 8).
     */
    public function getNationalIdentifier(): string {
        // Für eine validierte Instanz nie null; Fallback nur für PHPStan.
        return CreditorIdHelper::extractNationalId($this->value) ?? '';
    }

    /**
     * Gruppiert formatiert (z.B. "DE98 ZZZ 0999 9999 999").
     */
    public function formatted(string $separator = ' '): string {
        return CreditorIdHelper::format($this->value, $separator);
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: Es bleiben genau
     * $visibleStart Zeichen am Anfang (Standard: Land + Prüfziffer +
     * Geschäftsbereich) und $visibleEnd am Ende sichtbar, der Rest wird
     * durch 'X' ersetzt (Länge bleibt erhalten).
     *
     * @throws InvalidArgumentException Wenn die Sichtbarkeit (fast) alles offenlegen würde.
     */
    public function masked(int $visibleStart = 7, int $visibleEnd = 4): string {
        $length = strlen($this->value);
        if ($visibleStart < 0 || $visibleEnd < 0 || $visibleStart + $visibleEnd >= $length) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die Gläubiger-ID offenlegen (sichtbar: $visibleStart + $visibleEnd von $length Zeichen).");
        }

        return substr($this->value, 0, $visibleStart)
            . str_repeat('X', $length - $visibleStart - $visibleEnd)
            . substr($this->value, $length - $visibleEnd);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
