<?php
/*
 * Created on   : Sun Aug 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WasteCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable, validierter Abfallschlüssel nach dem Europäischen
 * Abfallverzeichnis (EWC; in Deutschland: AVV — Abfallverzeichnis-Verordnung).
 *
 * Ein Schlüssel besteht aus sechs Ziffern in drei Paaren
 * (Kapitel, Gruppe, Abfallart) mit optionalem Stern-Suffix `*`, das den
 * Abfall als gefährlich kennzeichnet (strengere Nachweispflicht).
 * Kapitel sind im Verzeichnis 01–20 vergeben; das AVV kennt keine
 * Prüfziffer — die Validierung ist formal (Struktur + Kapitelbereich).
 *
 * Ein Abfallschlüssel bezeichnet eine Abfallart, keine Person — er darf
 * `Stringable` und `JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $code = WasteCode::of('200135*');
 * $code->getValue();     // "20 01 35*"
 * $code->getChapter();   // "20"
 * $code->isHazardous();  // true
 * ```
 */
final class WasteCode implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Reine Ziffernfolge (6 Stellen, ohne Stern). */
    private readonly string $digits;

    private readonly bool $hazardous;

    private function __construct(string $digits, bool $hazardous) {
        $this->digits = $digits;
        $this->hazardous = $hazardous;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen validierten Abfallschlüssel.
     *
     * @throws InvalidArgumentException Bei falscher Struktur oder Kapitel außerhalb 01–20.
     */
    public static function of(string $value): self {
        $code = self::tryFrom($value);
        if ($code === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger Abfallschlüssel: '" . trim($value) . "'");
        }

        return $code;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null) {
            return null;
        }

        // Übliche Trennzeichen entfernen — Buchstaben bleiben ungültig.
        $normalized = (string) preg_replace('/[\s.\-]/', '', trim($value));
        if ($normalized === '') {
            return null;
        }

        $hazardous = str_ends_with($normalized, '*');
        if ($hazardous) {
            $normalized = substr($normalized, 0, -1);
        }

        if (strlen($normalized) !== 6 || !ctype_digit($normalized)) {
            return null;
        }

        $chapter = (int) substr($normalized, 0, 2);
        if ($chapter < 1 || $chapter > 20) {
            return null;
        }

        return new self($normalized, $hazardous);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonische Darstellung in drei Ziffernpaaren, gefährliche Abfälle mit
     * Stern-Suffix (z.B. "20 01 35*").
     */
    public function getValue(): string {
        return implode(' ', str_split($this->digits, 2)) . ($this->hazardous ? '*' : '');
    }

    /**
     * Reine Ziffernfolge ohne Stern (z.B. "200135").
     */
    public function getDigits(): string {
        return $this->digits;
    }

    /**
     * Kapitel des Abfallverzeichnisses (erstes Ziffernpaar, "01"–"20").
     */
    public function getChapter(): string {
        return substr($this->digits, 0, 2);
    }

    /**
     * Gruppe innerhalb des Kapitels (erstes + zweites Ziffernpaar, z.B. "2001").
     */
    public function getGroup(): string {
        return substr($this->digits, 0, 4);
    }

    /**
     * Stern-Kennzeichnung: gefährlicher Abfall im Sinne des Verzeichnisses.
     */
    public function isHazardous(): bool {
        return $this->hazardous;
    }

    /**
     * Exakte Gleichheit von Ziffernfolge und Gefährlichkeits-Kennzeichen —
     * "20 01 35*" ist NICHT gleich "20 01 35".
     */
    public function equals(self $other): bool {
        return $this->digits === $other->digits && $this->hazardous === $other->hazardous;
    }

    // ========================================================================
    // String / JSON
    // ========================================================================

    public function __toString(): string {
        return $this->getValue();
    }

    public function jsonSerialize(): string {
        return $this->getValue();
    }
}
