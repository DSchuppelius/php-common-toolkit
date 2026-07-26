<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Iban.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\BankHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte IBAN.
 *
 * Konstruktion normalisiert (Großschreibung, ohne Whitespace) und verlangt
 * die vollständige Prüfsummenprüfung ({@see BankHelper::validateIBAN()} im
 * strikten Modus). Maskierte oder anonymisierte IBANs sind keine gültigen
 * Instanzen. Validierung und Extraktion delegieren an {@see BankHelper} —
 * hier wird kein Algorithmus dupliziert.
 *
 * SENSIBLER WERT: Eine IBAN ist ein finanzieller Identifikator. Die Klasse
 * implementiert deshalb bewusst WEDER `Stringable` NOCH `JsonSerializable` —
 * der Klarwert ist ausschließlich über den bewusst aufgerufenen
 * {@see getValue()}-Getter verfügbar; für Anzeigen gibt es {@see masked()}.
 *
 * @example
 * ```php
 * $iban = Iban::of('de89 3704 0044 0532 0130 00');
 * $iban->formatted();  // "DE89 3704 0044 0532 0130 00"
 * $iban->masked();     // "DE89XXXXXXXXXXXXXX3000"
 * $iban->getCountry(); // CountryCode::Germany
 * ```
 */
final class Iban {
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
     * Erzeugt eine validierte IBAN (normalisiert, vollständige Prüfsumme).
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige IBAN gehört nicht ins Log.
     *
     * @throws InvalidArgumentException Bei ungültiger, maskierter oder nicht zuordenbarer IBAN.
     */
    public static function of(string $value): self {
        $iban = self::tryFrom($value);
        if ($iban === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige IBAN (Format-/Prüfsummenprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $iban;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = BankHelper::normalizeIBAN($value);
        if ($normalized === null || BankHelper::isIBANAnon($normalized) || !BankHelper::validateIBAN($normalized, true)) {
            return null;
        }

        // Eine gültige IBAN muss einem bekannten Land zuordenbar sein.
        $country = BankHelper::getCountryCodeFromIBAN($normalized);
        if ($country === null) {
            return null;
        }

        return new self($normalized, $country);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (normalisiert, z.B. "DE89370400440532013000") — nur bewusst
     * abrufen; für Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Von links in Viererblöcke gruppiert: "DE89 3704 0044 0532 0130 00".
     */
    public function formatted(string $separator = ' '): string {
        return implode($separator, str_split($this->value, 4));
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: Es bleiben genau
     * $visibleStart Zeichen am Anfang und $visibleEnd am Ende sichtbar,
     * der Rest wird durch 'X' ersetzt (Länge bleibt erhalten).
     *
     * @throws InvalidArgumentException Wenn die Sichtbarkeit (fast) alles offenlegen würde.
     */
    public function masked(int $visibleStart = 4, int $visibleEnd = 4): string {
        $length = strlen($this->value);
        if ($visibleStart < 0 || $visibleEnd < 0 || $visibleStart + $visibleEnd >= $length) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die IBAN offenlegen (sichtbar: $visibleStart + $visibleEnd von $length Zeichen).");
        }

        return substr($this->value, 0, $visibleStart)
            . str_repeat('X', $length - $visibleStart - $visibleEnd)
            . substr($this->value, $length - $visibleEnd);
    }

    /**
     * Land der IBAN (für eine gültige Instanz immer bekannt).
     */
    public function getCountry(): CountryCode {
        return $this->country;
    }

    /**
     * Stammt die IBAN aus einem SEPA-Teilnehmerland?
     */
    public function isSepa(): bool {
        return $this->country->isSEPA();
    }

    /**
     * Nationale Bankleitzahl (falls für das Land extrahierbar).
     */
    public function getBankCode(): ?string {
        return BankHelper::getBankCodeFromIBAN($this->value);
    }

    /**
     * Nationale Kontonummer (falls für das Land extrahierbar).
     */
    public function getAccountNumber(): ?string {
        return BankHelper::getAccountNumberFromIBAN($this->value);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
