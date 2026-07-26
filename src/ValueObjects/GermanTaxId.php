<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GermanTaxId.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\TaxNumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte persönliche deutsche Steuer-Identifikationsnummer
 * (IdNr., elf Ziffern).
 *
 * Bildet AUSSCHLIESSLICH die persönliche Steuer-ID ab — nicht die
 * betriebliche Steuernummer (dafür {@see GermanTaxNumber}). Die Konstruktion
 * verwendet {@see TaxNumberHelper::validateIdNr()} (inklusive
 * ISO/IEC-7064-Prüfziffer).
 *
 * SENSIBLER WERT: Die IdNr. ist ein personenbezogener steuerlicher
 * Identifikator. Die Klasse implementiert deshalb bewusst WEDER `Stringable`
 * NOCH `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar; für Anzeigen gibt es
 * {@see masked()}.
 *
 * @example
 * ```php
 * $taxId = GermanTaxId::of('65 929 970 489');
 * $taxId->getValue();  // "65929970489"
 * $taxId->formatted(); // "65 929 970 489"
 * $taxId->masked();    // "XXXXXXXX489"
 * ```
 */
final class GermanTaxId {
    use ErrorLog;

    /** Elf Ziffern ohne Trennzeichen. */
    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte IdNr. (inklusive Prüfziffernprüfung).
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige IdNr. gehört nicht ins Log.
     *
     * @throws InvalidArgumentException Bei ungültiger IdNr.
     */
    public static function of(string $value): self {
        $taxId = self::tryFrom($value);
        if ($taxId === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige Steuer-IdNr. (Format-/Prüfziffernprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $taxId;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = TaxNumberHelper::normalize($value);
        if (!TaxNumberHelper::validateIdNr($normalized)) {
            return null;
        }

        return new self($normalized);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (elf Ziffern, z.B. "65929970489") — nur bewusst abrufen; für
     * Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * In Dreiergruppen formatiert: "65 929 970 489".
     */
    public function formatted(): string {
        return TaxNumberHelper::formatIdNr($this->value);
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
            self::logErrorAndThrow(InvalidArgumentException::class, "Maskierung würde die Steuer-IdNr. offenlegen (sichtbar: $visibleEnd von $length Ziffern).");
        }

        return str_repeat('X', $length - $visibleEnd) . substr($this->value, $length - $visibleEnd);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
