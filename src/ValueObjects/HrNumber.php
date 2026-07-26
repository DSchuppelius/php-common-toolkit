<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HrNumber.php
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
 * Immutable, validierte deutsche Registernummer.
 *
 * Deckt die vom {@see CompanyIdHelper} unterstützten Register ab: HRA/HRB
 * (Handelsregister), GNR (Genossenschaftsregister), PR
 * (Partnerschaftsregister) und VR (Vereinsregister) — jeweils 1-6 Ziffern
 * mit optionalem Buchstaben-Suffix. Typischer Einsatz: Pflichtangaben auf
 * Belegen und Stammdaten.
 *
 * Das Registergericht ist bewusst NICHT Teil dieses Value Objects — es ist
 * ein Kontextdatum der Anwendung. Eine Registernummer ist eine öffentliche
 * Angabe und darf `Stringable`/`JsonSerializable` implementieren.
 *
 * @example
 * ```php
 * $hr = HrNumber::of('hrb12345b');
 * $hr->getValue();        // "HRB 12345 B" (kanonisch)
 * $hr->getRegisterType(); // "HRB"
 * $hr->getSuffix();       // "B"
 * ```
 */
final class HrNumber implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Kanonische Form: "PRÄFIX NUMMER[ SUFFIX]". */
    private readonly string $value;

    private readonly string $registerType;

    private readonly string $number;

    private readonly ?string $suffix;

    private function __construct(string $value, string $registerType, string $number, ?string $suffix) {
        $this->value = $value;
        $this->registerType = $registerType;
        $this->number = $number;
        $this->suffix = $suffix;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte Registernummer.
     *
     * @throws InvalidArgumentException Bei unbekanntem Präfix oder ungültiger Struktur.
     */
    public static function of(string $value): self {
        $hrNumber = self::tryFrom($value);
        if ($hrNumber === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültige Registernummer: '" . trim($value) . "'");
        }

        return $hrNumber;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (!CompanyIdHelper::isHRNumber($value)) {
            return null;
        }

        $parts = CompanyIdHelper::parseHRNumber($value);
        if ($parts['prefix'] === null || $parts['prefix'] === '' || $parts['number'] === null) {
            return null;
        }

        return new self(CompanyIdHelper::formatHRNumber($value), $parts['prefix'], $parts['number'], $parts['suffix']);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonische Form (z.B. "HRB 12345 B").
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Registertyp: "HRA", "HRB", "GNR", "PR" oder "VR".
     */
    public function getRegisterType(): string {
        return $this->registerType;
    }

    /**
     * Laufende Nummer (z.B. "12345").
     */
    public function getNumber(): string {
        return $this->number;
    }

    /**
     * Optionaler Buchstaben-Suffix (z.B. "B") oder null.
     */
    public function getSuffix(): ?string {
        return $this->suffix;
    }

    /**
     * Formatierte Darstellung — identisch mit der kanonischen Form.
     */
    public function formatted(): string {
        return $this->value;
    }

    /**
     * Gleichheit der kanonischen Form (Typ, Nummer UND Suffix).
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
