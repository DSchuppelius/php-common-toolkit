<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Percentage.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\{CountryCode, RoundingMode};
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutabler, exakter Prozentwert für Steuern, Rabatte, Skonto, Zuschläge und
 * statistische Raten.
 *
 * Komponiert ein {@see Decimal} — keine eigene Arithmetik, keine
 * float-Zwischenschritte. Grundsätzlich NICHT auf 0..100 begrenzt, weil
 * negative Raten und Werte über 100 fachlich möglich sind; wer den Bereich
 * erzwingen will, nutzt {@see betweenZeroAndHundred()}.
 *
 * Die Geld-Anbindung ({@see amountOf()}, {@see addTo()},
 * {@see subtractFrom()}) delegiert an die präzisen {@see Money}-Methoden —
 * es wird keine Geldarithmetik dupliziert.
 *
 * @example
 * ```php
 * $vat = Percentage::of(19);
 * $vat->amountOf(Money::of('8.15', CurrencyCode::Euro)); // 1,55 EUR
 * $vat->addTo(Money::of('100.00', CurrencyCode::Euro));  // 119,00 EUR
 * Percentage::fromRatio(Decimal::of('1'), Decimal::of('3')); // 33,3333 %
 * ```
 */
final class Percentage implements JsonSerializable, Stringable {
    use ErrorLog;

    private readonly Decimal $value;

    private function __construct(Decimal $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen Prozentwert (19 = 19 %). Negative Werte und Werte über 100
     * sind zulässig. Nicht deutbare Eingaben werfen eine Exception.
     *
     * @param string|int       $value   Prozentwert (Dezimal-String oder Ganzzahl).
     * @param int|null         $scale   Nachkommastellen (null = aus der Eingabe übernehmen).
     * @param RoundingMode     $mode    Rundung, falls $value mehr Stellen hat (Standard: HalfUp).
     * @param CountryCode|null $country Land für eindeutige Tausendertrenner-Erkennung.
     * @throws InvalidArgumentException Bei nicht deutbarer Eingabe oder negativer Skala.
     */
    public static function of(string|int $value, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): self {
        return new self(Decimal::of($value, $scale, $mode, $country));
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder nicht deutbarer
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(string|int|null $value, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): ?self {
        $decimal = Decimal::ofNullable($value, $scale, $mode, $country);

        return $decimal === null ? null : new self($decimal);
    }

    /**
     * Wie {@see of()}, erzwingt aber den Bereich 0..100 (beide Grenzen
     * inklusive) — z.B. für Anteils- oder Auslastungswerte.
     *
     * @throws InvalidArgumentException Bei Werten außerhalb von 0..100.
     */
    public static function betweenZeroAndHundred(string|int $value, ?int $scale = null): self {
        $percentage = self::of($value, $scale);
        if (!$percentage->isWithinZeroAndHundred()) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Prozentwert außerhalb von 0..100: {$percentage->getNumericValue()}");
        }

        return $percentage;
    }

    /**
     * Prozentsatz aus einem Verhältnis: fromRatio(1, 3) = 33,3333 %.
     * Gerundet wird ausschließlich auf die Zielskala.
     *
     * @throws \RuntimeException Bei $whole = 0 (Division durch null).
     */
    public static function fromRatio(Decimal $part, Decimal $whole, int $scale = 4, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self($part->times(Decimal::of(100))->dividedBy($whole, $scale, $mode));
    }

    // ========================================================================
    // Geld-Anbindung (delegiert an Money)
    // ========================================================================

    /**
     * Prozentualer Anteil eines Betrags: 19 % von 8,15 EUR = 1,55 EUR.
     */
    public function amountOf(Money $money, RoundingMode $mode = RoundingMode::HalfUp): Money {
        return $money->percentage($this->value->getValue(), $mode);
    }

    /**
     * Betrag zuzüglich dieses Prozentsatzes (Netto → Brutto).
     */
    public function addTo(Money $money, RoundingMode $mode = RoundingMode::HalfUp): Money {
        return $money->plusPercentage($this->value->getValue(), $mode);
    }

    /**
     * Betrag abzüglich dieses Prozentsatzes (z.B. Rabatt/Skonto).
     */
    public function subtractFrom(Money $money, RoundingMode $mode = RoundingMode::HalfUp): Money {
        return $money->minusPercentage($this->value->getValue(), $mode);
    }

    /**
     * Prozentwert als Faktor: 19 % → 0.19 (für Multiplikationen).
     */
    public function asFactor(int $scale = 8, RoundingMode $mode = RoundingMode::HalfUp): Decimal {
        return $this->value->dividedBy(Decimal::of(100), $scale, $mode);
    }

    // ========================================================================
    // Arithmetik / Vergleich
    // ========================================================================

    public function plus(self $other): self {
        return new self($this->value->plus($other->value));
    }

    public function minus(self $other): self {
        return new self($this->value->minus($other->value));
    }

    /**
     * @return int -1, 0 oder 1.
     */
    public function compareTo(self $other): int {
        return $this->value->compareTo($other->value);
    }

    /**
     * Fachliche Gleichheit: 19.0 % und 19.00 % sind gleich.
     */
    public function equals(self $other): bool {
        return $this->value->equals($other->value);
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

    /**
     * Liegt der Wert im Bereich 0..100 (beide Grenzen inklusive)?
     */
    public function isWithinZeroAndHundred(): bool {
        return !$this->value->isNegative() && $this->value->lessThanOrEqual(Decimal::of(100));
    }

    // ========================================================================
    // Zugriff / Formatierung / Serialisierung
    // ========================================================================

    public function getValue(): Decimal {
        return $this->value;
    }

    /**
     * Kanonischer Prozentwert als numeric-string (z.B. "19.00").
     *
     * @return numeric-string
     */
    public function getNumericValue(): string {
        return $this->value->getValue();
    }

    /**
     * Formatiert den Prozentwert präzise, z.B. "19,5 %".
     */
    public function format(string $decimalSeparator = ','): string {
        return $this->value->format($decimalSeparator, '') . ' %';
    }

    /**
     * Maschinenlesbare Darstellung: "19.5 %".
     */
    public function __toString(): string {
        return $this->value->getValue() . ' %';
    }

    /**
     * @return array{value: numeric-string, scale: int}
     */
    public function jsonSerialize(): array {
        return $this->value->jsonSerialize();
    }
}
