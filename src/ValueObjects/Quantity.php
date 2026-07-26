<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Quantity.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use BackedEnum;
use CommonToolkit\Enums\{CountryCode, RoundingMode};
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable, exakte Menge zusammen mit einer Einheit.
 *
 * Unterstützt sowohl physikalische Einheiten ("kg", "h") als auch
 * betriebliche Einheiten ("Stk", "Pauschale", "Karton"). Komponiert ein
 * {@see Decimal} — keine eigene Arithmetik, keine float-Zwischenschritte.
 *
 * In dieser Ausbaustufe findet KEINE automatische Einheitenumrechnung statt:
 * Arithmetik und Vergleich sind nur zwischen identischen, case-sensitiven
 * Einheitencodes zulässig ("STK" ≠ "Stk").
 *
 * @example
 * ```php
 * $hours = Quantity::of('2,5', 'h');
 * $hours->plus(Quantity::of('0.25', 'h'))->format(); // "2,75 h"
 * $hours->plus(Quantity::of(1, 'Stk'));              // InvalidArgumentException
 * Quantity::of('-3.5', 'Stk');                        // Lagerabgang: zulässig
 * ```
 */
final class Quantity implements JsonSerializable, Stringable {
    use ErrorLog;

    private readonly Decimal $value;

    private readonly string $unit;

    private function __construct(Decimal $value, string $unit) {
        $this->value = $value;
        $this->unit = $unit;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine Menge. Negative Werte sind zulässig (z.B. Lagerbewegungen);
     * für Constraints siehe {@see positive()} und {@see zeroOrPositive()}.
     *
     * @param string|int|Decimal $value   Menge (Dezimal-String, Ganzzahl oder Decimal).
     * @param string|BackedEnum  $unit    Einheitencode (String oder string-backed Enum).
     * @param int|null           $scale   Nachkommastellen (null = aus der Eingabe übernehmen).
     * @param RoundingMode       $mode    Rundung, falls $value mehr Stellen hat (Standard: HalfUp).
     * @param CountryCode|null   $country Land für eindeutige Tausendertrenner-Erkennung.
     * @throws InvalidArgumentException Bei ungültigem Wert oder ungültiger Einheit.
     */
    public static function of(string|int|Decimal $value, string|BackedEnum $unit, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): self {
        return new self(self::toDecimal($value, $scale, $mode, $country), self::normalizeUnit($unit));
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder nicht deutbarer
     * Eingabe `null` statt einer Exception. Die Einheit wird weiterhin strikt
     * geprüft (eine ungültige Einheit ist ein Programmierfehler, kein Datenfall).
     */
    public static function tryFrom(string|int|Decimal|null $value, string|BackedEnum $unit, ?int $scale = null): ?self {
        $normalizedUnit = self::normalizeUnit($unit);

        if ($value instanceof Decimal) {
            return new self($scale === null ? $value : $value->withScale($scale), $normalizedUnit);
        }

        $decimal = Decimal::ofNullable($value, $scale);

        return $decimal === null ? null : new self($decimal, $normalizedUnit);
    }

    /**
     * Nullmenge in der angegebenen Einheit.
     */
    public static function zero(string|BackedEnum $unit, int $scale = 0): self {
        return self::of(0, $unit, $scale);
    }

    /**
     * Wie {@see of()}, verlangt aber einen Wert echt größer null.
     *
     * @throws InvalidArgumentException Bei Werten <= 0.
     */
    public static function positive(string|int|Decimal $value, string|BackedEnum $unit, ?int $scale = null): self {
        $quantity = self::of($value, $unit, $scale);
        if (!$quantity->isPositive()) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Menge muss größer als 0 sein: {$quantity->getNumericValue()}");
        }

        return $quantity;
    }

    /**
     * Wie {@see of()}, erlaubt null, aber keine negativen Werte.
     *
     * @throws InvalidArgumentException Bei negativen Werten.
     */
    public static function zeroOrPositive(string|int|Decimal $value, string|BackedEnum $unit, ?int $scale = null): self {
        $quantity = self::of($value, $unit, $scale);
        if ($quantity->isNegative()) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Menge darf nicht negativ sein: {$quantity->getNumericValue()}");
        }

        return $quantity;
    }

    /**
     * Summiert Mengen exakt (eine Summierung, keine Zwischenrundung). Alle
     * Mengen müssen dieselbe Einheit haben.
     *
     * @param iterable<self>         $quantities Zu summierende Mengen.
     * @param string|BackedEnum|null $unit       Einheit für den leeren Fall (sonst aus der ersten Menge).
     * @param int|null               $scale      Zielskala (null = größte vorkommende Skala).
     * @throws InvalidArgumentException Bei gemischten Einheiten oder leerer Liste ohne Einheit.
     */
    public static function sum(iterable $quantities, string|BackedEnum|null $unit = null, ?int $scale = null): self {
        $normalizedUnit = $unit === null ? null : self::normalizeUnit($unit);

        $values = [];
        foreach ($quantities as $quantity) {
            $normalizedUnit ??= $quantity->unit;
            if ($quantity->unit !== $normalizedUnit) {
                self::logErrorAndThrow(InvalidArgumentException::class, "Einheiten unterscheiden sich: '$normalizedUnit' vs. '{$quantity->unit}'");
            }
            $values[] = $quantity->value;
        }

        if ($normalizedUnit === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "sum() benötigt bei leerer Liste eine Einheit.");
        }

        return new self(Decimal::sum($values, $scale), $normalizedUnit);
    }

    // ========================================================================
    // Arithmetik (liefert stets eine neue Instanz)
    // ========================================================================

    public function plus(self $other): self {
        $this->assertSameUnit($other);

        return new self($this->value->plus($other->value), $this->unit);
    }

    public function minus(self $other): self {
        $this->assertSameUnit($other);

        return new self($this->value->minus($other->value), $this->unit);
    }

    /**
     * Multipliziert mit einem einheitenlosen Faktor (z.B. Stückzahl × Faktor).
     */
    public function times(Decimal $factor, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self($this->value->times($factor, $scale, $mode), $this->unit);
    }

    /**
     * Teilt durch einen einheitenlosen Divisor.
     *
     * @throws \RuntimeException Bei Division durch null.
     */
    public function dividedBy(Decimal $divisor, int $scale, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self($this->value->dividedBy($divisor, $scale, $mode), $this->unit);
    }

    public function abs(): self {
        return $this->isNegative() ? $this->negated() : $this;
    }

    public function negated(): self {
        if ($this->isZero()) {
            return $this;
        }

        return new self($this->value->negated(), $this->unit);
    }

    /**
     * Gleiche Menge mit anderer Nachkommastellenzahl.
     */
    public function withScale(int $scale, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scaled = $this->value->withScale($scale, $mode);

        return $scaled === $this->value ? $this : new self($scaled, $this->unit);
    }

    // ========================================================================
    // Vergleich
    // ========================================================================

    /**
     * Vergleicht zwei Mengen derselben Einheit.
     *
     * @return int -1, 0 oder 1.
     * @throws InvalidArgumentException Bei unterschiedlichen Einheiten.
     */
    public function compareTo(self $other): int {
        $this->assertSameUnit($other);

        return $this->value->compareTo($other->value);
    }

    /**
     * Fachliche Gleichheit (Einheit UND Wert; 1.0 h = 1.00 h). Unterschiedliche
     * Einheiten sind nie gleich (kein Fehler).
     */
    public function equals(self $other): bool {
        return $this->unit === $other->unit && $this->value->equals($other->value);
    }

    /**
     * Gleiche Einheit? (Vorprüfung, wo Arithmetik sonst eine Exception wirft.)
     */
    public function isSameUnit(self $other): bool {
        return $this->unit === $other->unit;
    }

    public function isZero(): bool {
        return $this->value->isZero();
    }

    public function isPositive(): bool {
        return $this->value->isPositive();
    }

    public function isNegative(): bool {
        return $this->value->isNegative();
    }

    // ========================================================================
    // Zugriff / Formatierung / Serialisierung
    // ========================================================================

    public function getValue(): Decimal {
        return $this->value;
    }

    /**
     * Kanonischer Mengenwert als numeric-string (z.B. "2.50").
     *
     * @return numeric-string
     */
    public function getNumericValue(): string {
        return $this->value->getValue();
    }

    public function getUnit(): string {
        return $this->unit;
    }

    /**
     * Formatiert die Menge präzise, z.B. "1.234,5 kg".
     */
    public function format(string $decimalSeparator = ',', string $thousandsSeparator = '.'): string {
        return $this->value->format($decimalSeparator, $thousandsSeparator) . ' ' . $this->unit;
    }

    /**
     * Maschinenlesbare Darstellung: "2.50 h".
     */
    public function __toString(): string {
        return $this->value->getValue() . ' ' . $this->unit;
    }

    /**
     * @return array{value: numeric-string, scale: int, unit: string}
     */
    public function jsonSerialize(): array {
        return [
            'value' => $this->value->getValue(),
            'scale' => $this->value->getScale(),
            'unit' => $this->unit,
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Wert-Eingabe auf ein Decimal bringen (Decimal wird ggf. umskaliert).
     */
    private static function toDecimal(string|int|Decimal $value, ?int $scale, RoundingMode $mode, ?CountryCode $country): Decimal {
        if ($value instanceof Decimal) {
            return $scale === null ? $value : $value->withScale($scale, $mode);
        }

        return Decimal::of($value, $scale, $mode, $country);
    }

    /**
     * Einheit normalisieren und prüfen: getrimmt, nicht leer, keine
     * Steuerzeichen, Groß-/Kleinschreibung unverändert. BackedEnums sind nur
     * mit String-Backing zulässig.
     */
    private static function normalizeUnit(string|BackedEnum $unit): string {
        if ($unit instanceof BackedEnum) {
            if (!is_string($unit->value)) {
                self::logErrorAndThrow(InvalidArgumentException::class, 'Einheiten-Enum muss string-backed sein: ' . $unit::class);
            }
            $unit = $unit->value;
        }

        $unit = trim($unit);
        if ($unit === '') {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Einheit darf nicht leer sein.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $unit) === 1) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Einheit darf keine Steuerzeichen enthalten.');
        }

        return $unit;
    }

    private function assertSameUnit(self $other): void {
        if ($this->unit !== $other->unit) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Einheiten unterscheiden sich: '{$this->unit}' vs. '{$other->unit}'");
        }
    }
}
