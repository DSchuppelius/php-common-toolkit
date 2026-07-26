<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Lei.php
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
 * Immutabler, validierter LEI (Legal Entity Identifier, ISO 17442).
 *
 * 20-stelliger alphanumerischer Code: 4 Zeichen vergebende Stelle (LOU),
 * 14 Zeichen Entity-Teil, 2 Prüfziffern (Mod-97-10). Validierung und
 * Formatierung delegieren an {@see CompanyIdHelper::validateLEI()} bzw.
 * {@see CompanyIdHelper::formatLEI()}. Typischer Einsatz: ISO-20022-/
 * CAMT-Felder und Geschäftspartner-Stammdaten.
 *
 * Ein LEI ist ein öffentlicher Unternehmens-Identifikator — er darf
 * `Stringable` und `JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $lei = Lei::of('hwupkr0mpou8fgxbt394');
 * $lei->getValue();    // "HWUPKR0MPOU8FGXBT394"
 * $lei->getLouCode();  // "HWUP"
 * $lei->formatted();   // "HWUP KR0M POU8 FGXB T394"
 * ```
 */
final class Lei implements JsonSerializable, Stringable {
    use ErrorLog;

    /** 20 Zeichen, Großschreibung. */
    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen validierten LEI (vollständige Mod-97-10-Prüfung).
     *
     * @throws InvalidArgumentException Bei ungültiger Prüfsumme oder Struktur.
     */
    public static function of(string $value): self {
        $lei = self::tryFrom($value);
        if ($lei === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger LEI: '" . trim($value) . "'");
        }

        return $lei;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(CompanyIdHelper::normalizeLEI(trim($value)));
        if ($normalized === '' || !CompanyIdHelper::validateLEI($normalized)) {
            return null;
        }

        return new self($normalized);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonischer LEI (20 Zeichen, Großschreibung).
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Kennung der vergebenden Stelle (LOU, Stellen 1-4).
     */
    public function getLouCode(): string {
        return substr($this->value, 0, 4);
    }

    /**
     * Entity-Teil (Stellen 5-18).
     */
    public function getEntityPart(): string {
        return substr($this->value, 4, 14);
    }

    /**
     * Prüfziffern (Stellen 19-20).
     */
    public function getCheckDigits(): string {
        return substr($this->value, 18, 2);
    }

    /**
     * In Viererblöcke gruppiert (z.B. "HWUP KR0M POU8 FGXB T394"),
     * delegiert an {@see CompanyIdHelper::formatLEI()}.
     */
    public function formatted(): string {
        return CompanyIdHelper::formatLEI($this->value);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
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
