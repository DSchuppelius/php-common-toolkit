<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GermanTaxNumber.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\TaxNumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte deutsche betriebliche Steuernummer (StNr.).
 *
 * Der Name ist absichtlich länderspezifisch — ein generisches "TaxNumber"
 * würde eine internationale Semantik vortäuschen, die der zugrunde liegende
 * {@see TaxNumberHelper} nicht besitzt. Für die persönliche
 * Steuer-Identifikationsnummer siehe {@see GermanTaxId}.
 *
 * Unterstützt beide Darstellungen:
 * - Landesformat (10-11 Ziffern, z.B. "21/815/08150"), optional mit
 *   Bundesland-Kürzel ("HH"),
 * - bundeseinheitliches 13-stelliges Format (ELSTER), aus dem das
 *   Bundesland automatisch abgeleitet wird.
 *
 * SENSIBLER WERT: Die Steuernummer ist ein steuerlicher Identifikator. Die
 * Klasse implementiert deshalb bewusst WEDER `Stringable` NOCH
 * `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar; für Anzeigen gibt es
 * {@see masked()}.
 *
 * @example
 * ```php
 * $stNr = GermanTaxNumber::of('21/815/08150', 'HH');
 * $stNr->getValue();         // "2181508150"
 * $stNr->getFederalState();  // "HH"
 *
 * $unified = GermanTaxNumber::of('9181081508155');
 * $unified->isUnifiedFormat(); // true
 * $unified->getFederalState(); // "BY" (abgeleitet)
 * ```
 */
final class GermanTaxNumber {
    use ErrorLog;

    /** Normalisierte Ziffernfolge ohne Trennzeichen. */
    private readonly string $value;

    /** Bundesland-Kürzel (z.B. "BY") oder null, wenn nicht bekannt. */
    private readonly ?string $federalState;

    private readonly bool $unifiedFormat;

    private function __construct(string $value, ?string $federalState, bool $unifiedFormat) {
        $this->value = $value;
        $this->federalState = $federalState;
        $this->unifiedFormat = $unifiedFormat;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte Steuernummer.
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige Nummer gehört nicht ins Log.
     *
     * @param string      $value        Steuernummer (Landesformat oder 13-stellig einheitlich).
     * @param string|null $federalState Bundesland-Kürzel (z.B. "BY"); wird normalisiert und geprüft.
     * @throws InvalidArgumentException Bei ungültiger Nummer, unbekanntem Bundesland oder Widerspruch zum einheitlichen Format.
     */
    public static function of(string $value, ?string $federalState = null): self {
        $taxNumber = self::tryFrom($value, $federalState);
        if ($taxNumber === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige Steuernummer (Formatprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $taxNumber;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * NUMMER `null` statt einer Exception. Ein ungültiges Bundesland-Kürzel
     * wirft weiterhin — das ist ein Programmierfehler, kein Datenfall.
     */
    public static function tryFrom(?string $value, ?string $federalState = null): ?self {
        $normalizedState = self::normalizeFederalState($federalState);

        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = TaxNumberHelper::normalize($value);

        if (TaxNumberHelper::isUnifiedStNr($normalized)) {
            $derivedState = TaxNumberHelper::getFederalStateFromStNr($normalized);
            if ($normalizedState !== null && $derivedState !== null && $normalizedState !== $derivedState) {
                self::logErrorAndThrow(
                    InvalidArgumentException::class,
                    "Bundesland-Angabe ($normalizedState) widerspricht dem einheitlichen Format ($derivedState)."
                );
            }

            return new self($normalized, $derivedState ?? $normalizedState, true);
        }

        if (!TaxNumberHelper::isStNr($normalized)) {
            return null;
        }

        return new self($normalized, $normalizedState, false);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (normalisierte Ziffernfolge, z.B. "2181508150") — nur bewusst
     * abrufen; für Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Bundesland-Kürzel (z.B. "BY") oder null, wenn nicht bekannt.
     */
    public function getFederalState(): ?string {
        return $this->federalState;
    }

    /**
     * Liegt die Nummer im bundeseinheitlichen 13-stelligen Format vor?
     */
    public function isUnifiedFormat(): bool {
        return $this->unifiedFormat;
    }

    /**
     * Formatiert die Nummer (einheitliches Format gruppiert, z.B.
     * "9181/0815/08155"); delegiert an {@see TaxNumberHelper::formatStNr()}.
     */
    public function formatted(): string {
        // Beim einheitlichen Format kein Bundesland übergeben — der Helper
        // gruppiert 13-Steller nur auf dem länderneutralen Pfad korrekt.
        return TaxNumberHelper::formatStNr($this->value, $this->unifiedFormat ? null : $this->federalState);
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: nur die letzten $visibleEnd
     * Ziffern bleiben sichtbar, der Rest wird durch 'X' ersetzt (Länge
     * bleibt erhalten).
     *
     * @throws InvalidArgumentException Wenn die Sichtbarkeit (fast) alles offenlegen würde.
     */
    public function masked(int $visibleEnd = 3): string {
        $length = strlen($this->value);
        if ($visibleEnd < 0 || $visibleEnd >= $length) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die Steuernummer offenlegen (sichtbar: $visibleEnd von $length Ziffern).");
        }

        return str_repeat('X', $length - $visibleEnd) . substr($this->value, $length - $visibleEnd);
    }

    /**
     * Fachliche Gleichheit: gleiche Ziffernfolge UND gleiches Bundesland —
     * dieselben Ziffern in verschiedenen Bundesländern sind verschiedene
     * Steuernummern.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value && $this->federalState === $other->federalState;
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Bundesland-Kürzel normalisieren (trim, Großschreibung) und gegen die
     * vom Helper unterstützten Länder prüfen ({@see TaxNumberHelper::getFederalStates()}
     * ist die Single Source of Truth — keine duplizierte Liste).
     *
     * @throws InvalidArgumentException Bei unbekanntem Kürzel.
     */
    private static function normalizeFederalState(?string $federalState): ?string {
        if ($federalState === null) {
            return null;
        }

        $normalized = strtoupper(trim($federalState));
        if ($normalized === '') {
            return null;
        }

        if (!array_key_exists($normalized, TaxNumberHelper::getFederalStates())) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Unbekanntes Bundesland-Kürzel: '$normalized'");
        }

        return $normalized;
    }
}
