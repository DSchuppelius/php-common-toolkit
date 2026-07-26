<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VatNumber.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\VatNumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte Umsatzsteuer-Identifikationsnummer (USt-IdNr.).
 *
 * Die Konstruktion normalisiert (Großschreibung, ohne Trennzeichen) und
 * validiert über {@see VatNumberHelper::validateVatId()} — im strikten Modus
 * inklusive Prüfziffer, wo das Land eine besitzt. {@see getCountryCode()}
 * liefert bewusst `string`, weil vorhandene gültige Präfixe wie "CHE"
 * (Schweiz) und "XI" (Nordirland) keine durchgehenden
 * ISO-3166-Alpha-2-Fälle sind.
 *
 * SENSIBLER WERT: Eine USt-IdNr. ist ein steuerlicher Identifikator. Die
 * Klasse implementiert deshalb bewusst WEDER `Stringable` NOCH
 * `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar; für Anzeigen gibt es
 * {@see masked()}.
 *
 * @example
 * ```php
 * $vat = VatNumber::of('de 136 695 976');
 * $vat->getValue();       // "DE136695976"
 * $vat->formatted();      // "DE 136 695 976"
 * $vat->masked();         // "DEXXXXX5976"
 * $vat->getCountryCode(); // "DE"
 * ```
 */
final class VatNumber {
    use ErrorLog;

    private readonly string $value;

    private readonly string $countryCode;

    private function __construct(string $value, string $countryCode) {
        $this->value = $value;
        $this->countryCode = $countryCode;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte USt-IdNr.
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige Nummer gehört nicht ins Log.
     *
     * @param string $value  USt-IdNr. (mit oder ohne Trennzeichen).
     * @param bool   $strict true = inklusive Prüfziffernvalidierung (Standard), false = nur Formatprüfung.
     * @throws InvalidArgumentException Bei ungültiger Nummer.
     */
    public static function of(string $value, bool $strict = true): self {
        $vatNumber = self::tryFrom($value, $strict);
        if ($vatNumber === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige USt-IdNr. (Format-/Prüfziffernprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $vatNumber;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value, bool $strict = true): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = VatNumberHelper::normalize($value);
        if (!VatNumberHelper::validateVatId($normalized, $strict)) {
            return null;
        }

        // Eine gültige USt-IdNr. muss ein bekanntes Länderpräfix besitzen.
        $countryCode = VatNumberHelper::extractCountryCode($normalized);
        if ($countryCode === null) {
            return null;
        }

        return new self($normalized, $countryCode);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (normalisiert, z.B. "DE136695976") — nur bewusst abrufen; für
     * Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Länderpräfix als String (z.B. "DE", "CHE", "XI") — bewusst kein
     * CountryCode-Enum, weil nicht alle Präfixe ISO-3166-Alpha-2 sind.
     */
    public function getCountryCode(): string {
        return $this->countryCode;
    }

    /**
     * Nationaler Nummernteil ohne Länderpräfix (z.B. "136695976").
     */
    public function getNationalNumber(): string {
        return VatNumberHelper::extractNumber($this->value);
    }

    /**
     * Länderspezifisch formatiert (z.B. "DE 136 695 976").
     */
    public function formatted(): string {
        return VatNumberHelper::format($this->value);
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: Das Länderpräfix bleibt
     * sichtbar, vom nationalen Teil nur die letzten $visibleEnd Zeichen —
     * der Rest wird durch 'X' ersetzt (Länge bleibt erhalten).
     *
     * @throws InvalidArgumentException Wenn die Sichtbarkeit (fast) alles offenlegen würde.
     */
    public function masked(int $visibleEnd = 4): string {
        $national = $this->getNationalNumber();
        $length = strlen($national);
        if ($visibleEnd < 0 || $visibleEnd >= $length) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die USt-IdNr. offenlegen (sichtbar: $visibleEnd von $length Zeichen).");
        }

        return $this->countryCode
            . str_repeat('X', $length - $visibleEnd)
            . substr($national, $length - $visibleEnd);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
