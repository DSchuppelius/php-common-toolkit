<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PhoneNumber.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\PhoneNumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte Telefonnummer.
 *
 * Intern wird ausschließlich E.164 gespeichert ("+498912345678"); die
 * Umwandlung delegiert an {@see PhoneNumberHelper::toE164WithCountryCode()}.
 * Nationale Eingaben werden über das Default-Land aufgelöst.
 *
 * SENSIBLER WERT: Eine Telefonnummer ist ein personenbezogener
 * Identifikator. Die Klasse implementiert deshalb bewusst WEDER `Stringable`
 * NOCH `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar; für Anzeigen gibt es
 * {@see masked()}.
 *
 * @example
 * ```php
 * $phone = PhoneNumber::of('089 / 12 34 56 78');
 * $phone->getValue();      // "+498912345678" (E.164)
 * $phone->international(); // "+49 891 2345678"
 * $phone->masked();        // "+XXXXXXXXXX678"
 * ```
 */
final class PhoneNumber {
    use ErrorLog;

    /** E.164-Darstellung ("+498912345678"). */
    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte Telefonnummer (kanonisch E.164).
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige Nummer gehört nicht ins Log.
     *
     * @param string      $value          Telefonnummer (national oder international).
     * @param CountryCode $defaultCountry Land zur Auflösung nationaler Eingaben (Standard: Deutschland).
     * @throws InvalidArgumentException Bei nicht deutbarer Nummer.
     */
    public static function of(string $value, CountryCode $defaultCountry = CountryCode::Germany): self {
        $phone = self::tryFrom($value, $defaultCountry);
        if ($phone === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige Telefonnummer (E.164-Umwandlung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $phone;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder nicht deutbarer
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value, CountryCode $defaultCountry = CountryCode::Germany): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $e164 = PhoneNumberHelper::toE164WithCountryCode($value, $defaultCountry);
        if ($e164 === null) {
            return null;
        }

        return new self($e164);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert in E.164 (z.B. "+498912345678") — nur bewusst abrufen; für
     * Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Land der Nummer anhand der Ländervorwahl (null, wenn die Vorwahl
     * keinem unterstützten Land zuordenbar ist).
     */
    public function getCountry(): ?CountryCode {
        return PhoneNumberHelper::extractCountryEnum($this->value);
    }

    /**
     * International formatiert (z.B. "+49 891 2345678").
     */
    public function international(): string {
        return PhoneNumberHelper::formatInternational($this->value);
    }

    /**
     * National formatiert (z.B. "0891 2345678"). Ohne explizites Land wird
     * das Land der Nummer verwendet (Fallback: Deutschland).
     */
    public function national(?CountryCode $country = null): string {
        $country ??= $this->getCountry() ?? CountryCode::Germany;

        return PhoneNumberHelper::formatNational($this->value, $country->value);
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: führendes "+" bleibt, nur die
     * letzten $visibleEnd Ziffern sind sichtbar, der Rest wird durch 'X'
     * ersetzt (Länge bleibt erhalten).
     *
     * @throws InvalidArgumentException Wenn die Sichtbarkeit (fast) alles offenlegen würde.
     */
    public function masked(int $visibleEnd = 3): string {
        $digits = substr($this->value, 1); // ohne führendes "+"
        $length = strlen($digits);
        if ($visibleEnd < 0 || $visibleEnd >= $length) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die Telefonnummer offenlegen (sichtbar: $visibleEnd von $length Ziffern).");
        }

        return '+' . str_repeat('X', $length - $visibleEnd) . substr($digits, $length - $visibleEnd);
    }

    /**
     * Stammt die Nummer aus dem angegebenen Land?
     */
    public function isFromCountry(CountryCode $country): bool {
        return PhoneNumberHelper::matchesCountry($this->value, $country);
    }

    /**
     * Gleichheit der E.164-Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
