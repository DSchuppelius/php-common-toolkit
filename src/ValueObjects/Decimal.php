<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Decimal.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\{CountryCode, RoundingMode};
use CommonToolkit\Helper\Data\NumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutabler Dezimalwert mit exakter (bcmath-basierter) Arithmetik.
 *
 * Allgemeine Grundlage für exakte Werte an Anwendungsgrenzen: Der Wert wird
 * intern als kanonischer numeric-string (Punkt-Dezimaltrenner) mit genau
 * {@see getScale()} Nachkommastellen gehalten; alle Rechenoperationen laufen
 * präzise über die {@see NumberHelper}-Precise-Suite. Jede Operation liefert
 * eine NEUE Instanz — bestehende bleiben unverändert.
 *
 * `Decimal` ist die technische Grundlage für Prozent-, Mengen- und
 * Kurs-Objekte, aber keine Oberklasse: Diese Objekte komponieren ein Decimal.
 *
 * Konstruktion bewusst OHNE float (Präzision an der Grenze erzwingen):
 * {@see of()} für Dezimal-Strings/Ganzzahlen, {@see ofNullable()} für
 * nullable Grenzen (Importe). {@see ofFloat()} ist der explizite,
 * dokumentierte Ausweg für Werte, die bereits als float vorliegen.
 *
 * @example
 * ```php
 * $a = Decimal::of('0.1');
 * $b = Decimal::of('0.2');
 * $a->plus($b)->getValue();                    // "0.3" — exakt, kein float
 * Decimal::of('1.0')->equals(Decimal::of('1.00')); // true (fachliche Gleichheit)
 * Decimal::of('10')->dividedBy(Decimal::of('3'), 2)->getValue(); // "3.33"
 * ```
 */
final class Decimal implements JsonSerializable, Stringable {
    use ErrorLog;

    /** @var numeric-string Kanonischer Wert mit genau Nachkommastellen. */
    private readonly string $value;

    private readonly int $scale;

    /**
     * @param numeric-string $value
     */
    private function __construct(string $value, int $scale) {
        $this->value = $value;
        $this->scale = $scale;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen Dezimalwert aus einem Dezimal-String oder einer Ganzzahl.
     *
     * Akzeptiert deutsche/US-Formate ("1.234,56", "1,234.56", "1234.56") via
     * {@see NumberHelper::normalizeDecimalStringOrNull()}. Nicht deutbare
     * Eingaben werfen eine Exception — sie werden niemals still zu 0.
     *
     * @param string|int       $value   Wert (Dezimal-String oder Ganzzahl).
     * @param int|null         $scale   Nachkommastellen (null = aus der Eingabe übernehmen).
     * @param RoundingMode     $mode    Rundung, falls $value mehr Stellen hat (Standard: HalfUp).
     * @param CountryCode|null $country Land für eindeutige Tausendertrenner-Erkennung ("2.000" = 2000 statt 2,0).
     * @throws InvalidArgumentException Bei nicht deutbarer Eingabe oder negativer Skala.
     */
    public static function of(string|int $value, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): self {
        $canonical = NumberHelper::normalizeDecimalStringOrNull((string) $value, $country);
        if ($canonical === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger Dezimalwert: '$value'");
        }

        return self::fromCanonical($canonical, $scale, $mode);
    }

    /**
     * Wie {@see of()}, unterscheidet aber "nicht angegeben" von der echten Null:
     * `null`, Leerstring und nicht deutbare Eingaben ergeben `null` statt einer
     * Exception. Gedacht für Importe, Parser und nullable Datenbankspalten.
     */
    public static function ofNullable(string|int|null $value, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): ?self {
        if ($value === null) {
            return null;
        }

        $canonical = NumberHelper::normalizeDecimalStringOrNull((string) $value, $country);
        if ($canonical === null) {
            return null;
        }

        return self::fromCanonical($canonical, $scale, $mode);
    }

    /**
     * Konstruiert aus einem float. NUR nutzen, wenn der Wert zwangsläufig als
     * float vorliegt — der float ist bereits vor dem Aufruf potenziell
     * unpräzise. Bevorzugt {@see of()} (String).
     *
     * Ohne Zielskala wird der float mit 14 Nachkommastellen eingefangen und
     * die minimale Skala abgeleitet (nachlaufende Nullen zählen nicht):
     * `ofFloat(19.99)` ergibt Skala 2, nicht 14.
     */
    public static function ofFloat(float $value, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        $captured = sprintf('%.14F', $value);
        $scale ??= self::inferScale(rtrim(rtrim($captured, '0'), '.'));

        return self::of($captured, $scale, $mode);
    }

    /**
     * Nullwert mit der angegebenen Skala.
     */
    public static function zero(int $scale = 0): self {
        return self::of('0', $scale);
    }

    /**
     * Einswert mit der angegebenen Skala.
     */
    public static function one(int $scale = 0): self {
        return self::of('1', $scale);
    }

    /**
     * Rekonstruiert einen Wert aus seiner Array-Darstellung — Gegenstück zu
     * {@see jsonSerialize()} (verlustfreier Roundtrip).
     *
     * @param array{value?: string|int|float|null, scale?: int|null} $data
     * @throws InvalidArgumentException Wenn das Array keinen Wert enthält.
     */
    public static function fromArray(array $data): self {
        $value = $data['value'] ?? null;
        if ($value === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Array enthält keinen Wert ('value' fehlt).");
        }

        $scale = $data['scale'] ?? null;

        return is_float($value)
            ? self::ofFloat($value, $scale)
            : self::of($value, $scale);
    }

    /**
     * Summiert Werte exakt (eine Summierung statt Kette aus plus(), damit keine
     * Zwischenrundung auftritt).
     *
     * @param iterable<self> $values Zu summierende Werte.
     * @param int|null       $scale  Zielskala (null = größte vorkommende Skala; leere Liste → 0).
     */
    public static function sum(iterable $values, ?int $scale = null): self {
        $amounts = [];
        $maxScale = 0;
        foreach ($values as $value) {
            $amounts[] = $value->value;
            $maxScale = max($maxScale, $value->scale);
        }

        $scale = self::assertScale($scale ?? $maxScale);

        if ($amounts === []) {
            return self::of('0', $scale);
        }

        return new self(NumberHelper::sumPrecise($amounts, $scale, RoundingMode::HalfUp), $scale);
    }

    /**
     * Kleinster der übergebenen Werte.
     */
    public static function min(self $first, self ...$rest): self {
        $result = $first;
        foreach ($rest as $value) {
            if ($value->lessThan($result)) {
                $result = $value;
            }
        }

        return $result;
    }

    /**
     * Größter der übergebenen Werte.
     */
    public static function max(self $first, self ...$rest): self {
        $result = $first;
        foreach ($rest as $value) {
            if ($value->greaterThan($result)) {
                $result = $value;
            }
        }

        return $result;
    }

    // ========================================================================
    // Arithmetik (liefert stets eine neue Instanz)
    // ========================================================================

    /**
     * Addition. Die Ergebnis-Skala ist die größere Skala beider Operanden.
     */
    public function plus(self $other): self {
        $scale = max($this->scale, $other->scale);

        return new self(NumberHelper::addPrecise($this->value, $other->value, $scale), $scale);
    }

    /**
     * Subtraktion. Die Ergebnis-Skala ist die größere Skala beider Operanden.
     */
    public function minus(self $other): self {
        $scale = max($this->scale, $other->scale);

        return new self(self::withoutNegativeZero(NumberHelper::subtractPrecise($this->value, $other->value, $scale)), $scale);
    }

    /**
     * Multiplikation. Ohne explizite Zielskala wird die Summe der
     * Operanden-Skalen verwendet — dabei geht keine Stelle verloren.
     */
    public function times(self $other, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scale = self::assertScale($scale ?? ($this->scale + $other->scale));

        return new self(self::withoutNegativeZero(NumberHelper::multiplyPrecise($this->value, $other->value, $scale, $mode)), $scale);
    }

    /**
     * Division. Verlangt bewusst eine Zielskala, weil periodische Ergebnisse
     * keine natürliche endliche Skala besitzen.
     *
     * @throws \RuntimeException Bei Division durch null (Verhalten von {@see NumberHelper::dividePrecise()}).
     */
    public function dividedBy(self $other, int $scale, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scale = self::assertScale($scale);

        return new self(self::withoutNegativeZero(NumberHelper::dividePrecise($this->value, $other->value, $scale, $mode)), $scale);
    }

    /**
     * Vorzeichenwechsel (skalen-erhaltend, keine negative Null).
     */
    public function negated(): self {
        if ($this->isZero()) {
            return $this;
        }

        return new self(NumberHelper::negatePrecise($this->value), $this->scale);
    }

    /**
     * Betrag (absoluter Wert).
     */
    public function abs(): self {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Gleicher Wert mit anderer Nachkommastellenzahl.
     */
    public function withScale(int $scale, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scale = self::assertScale($scale);
        if ($scale === $this->scale) {
            return $this;
        }

        return new self(self::withoutNegativeZero(NumberHelper::roundPrecise($this->value, $scale, $mode)), $scale);
    }

    // ========================================================================
    // Vergleich
    // ========================================================================

    /**
     * Vergleicht zwei Werte in voller Präzision (größere Skala beider Operanden).
     *
     * @return int -1, 0 oder 1.
     */
    public function compareTo(self $other): int {
        return NumberHelper::comparePrecise($this->value, $other->value, max($this->scale, $other->scale));
    }

    /**
     * Fachliche Gleichheit: 1.0 und 1.00 sind gleich (Skala ist unerheblich).
     */
    public function equals(self $other): bool {
        return $this->compareTo($other) === 0;
    }

    public function greaterThan(self $other): bool {
        return $this->compareTo($other) > 0;
    }

    public function greaterThanOrEqual(self $other): bool {
        return $this->compareTo($other) >= 0;
    }

    public function lessThan(self $other): bool {
        return $this->compareTo($other) < 0;
    }

    public function lessThanOrEqual(self $other): bool {
        return $this->compareTo($other) <= 0;
    }

    public function isZero(): bool {
        return NumberHelper::comparePrecise($this->value, '0', $this->scale) === 0;
    }

    public function isPositive(): bool {
        return NumberHelper::comparePrecise($this->value, '0', $this->scale) > 0;
    }

    public function isNegative(): bool {
        return NumberHelper::comparePrecise($this->value, '0', $this->scale) < 0;
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonischer Wert als numeric-string mit genau $scale Nachkommastellen (z.B. "12.34").
     *
     * @return numeric-string
     */
    public function getValue(): string {
        return $this->value;
    }

    public function getScale(): int {
        return $this->scale;
    }

    /**
     * Wert als float — ausschließlich für Grenzen, die keinen exakten Typ
     * annehmen (Alt-APIs, Diagramm-/Statistikbibliotheken, JSON-Zahlen).
     * Innerhalb einer Rechenkette NIE verwenden: ab hier ist die Präzision weg.
     */
    public function toFloat(): float {
        return (float) $this->value;
    }

    // ========================================================================
    // Formatierung / Serialisierung
    // ========================================================================

    /**
     * Formatiert den Wert präzise (ohne float-Zwischenschritt).
     *
     * @param string $decimalSeparator   Dezimaltrenner (Standard: ',').
     * @param string $thousandsSeparator Tausendertrenner-Zeichen ('' = keine Gruppierung).
     */
    public function format(string $decimalSeparator = ',', string $thousandsSeparator = '.'): string {
        $negative = str_starts_with($this->value, '-');
        $abs = ltrim($this->value, '-');

        $parts = explode('.', $abs);
        $integer = $parts[0];
        $fraction = $parts[1] ?? '';

        if ($thousandsSeparator !== '') {
            $integer = strrev(implode($thousandsSeparator, str_split(strrev($integer), 3)));
        }

        $result = $integer;
        if ($this->scale > 0) {
            $result .= $decimalSeparator . str_pad($fraction, $this->scale, '0');
        }

        return ($negative ? '-' : '') . $result;
    }

    /**
     * Maschinenlesbare Darstellung: der kanonische Wert (z.B. "12.34").
     */
    public function __toString(): string {
        return $this->value;
    }

    /**
     * @return array{value: numeric-string, scale: int}
     */
    public function jsonSerialize(): array {
        return [
            'value' => $this->value,
            'scale' => $this->scale,
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Baut die kanonische Instanz aus einem bereits normalisierten Wert:
     * Skala ableiten bzw. prüfen, auf die Skala runden, negative Null tilgen.
     *
     * @param numeric-string $canonical
     */
    private static function fromCanonical(string $canonical, ?int $scale, RoundingMode $mode): self {
        $scale = self::assertScale($scale ?? self::inferScale($canonical));

        return new self(self::withoutNegativeZero(NumberHelper::roundPrecise($canonical, $scale, $mode)), $scale);
    }

    /**
     * Anzahl der Nachkommastellen eines kanonischen Punkt-Dezimal-Strings.
     */
    private static function inferScale(string $canonical): int {
        $dot = strpos($canonical, '.');

        return $dot === false ? 0 : strlen($canonical) - $dot - 1;
    }

    /**
     * Prüft, dass die Skala nicht negativ ist.
     */
    private static function assertScale(int $scale): int {
        if ($scale < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Scale darf nicht negativ sein: $scale");
        }

        return $scale;
    }

    /**
     * Kanonisiert eine negative Null ("-0.00") zur positiven Null.
     *
     * @param numeric-string $value
     * @return numeric-string
     */
    private static function withoutNegativeZero(string $value): string {
        if (str_starts_with($value, '-') && NumberHelper::isZeroPrecise($value)) {
            return NumberHelper::absPrecise($value);
        }

        return $value;
    }
}
