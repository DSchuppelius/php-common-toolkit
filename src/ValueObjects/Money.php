<?php
/*
 * Created on   : Sat Jul 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Money.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\{CurrencyCode, RoundingMode};
use CommonToolkit\Helper\Data\NumberHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutables Geldbetrag-Value-Object mit exakter (bcmath-basierter) Arithmetik.
 *
 * Löst das klassische „Geld als float"-Problem: Der Betrag wird intern als
 * kanonischer numeric-string mit genau {@see getScale()} Nachkommastellen
 * gehalten; alle Rechenoperationen laufen präzise über die
 * {@see NumberHelper}-Precise-Suite (bcmath, echte Rundung). Jede Operation
 * liefert eine NEUE Instanz — bestehende bleiben unverändert.
 *
 * Konstruktion bewusst OHNE float (Präzision an der Grenze erzwingen):
 * {@see of()} für Dezimal-Strings/Ganzzahlen, {@see ofMinor()} für Minor Units
 * (Cent). {@see ofFloat()} ist der explizite, dokumentierte Ausweg für Floats.
 *
 * @example
 * ```php
 * $a = Money::of('19.99', CurrencyCode::Euro);
 * $b = Money::of('5.00', CurrencyCode::Euro);
 * $sum = $a->plus($b);                 // 24.99 EUR
 * $a->equals(Money::ofMinor(1999, CurrencyCode::Euro)); // true
 * [$x, $y, $z] = Money::of('10.00', CurrencyCode::Euro)->allocate(1, 1, 1); // 3.34/3.33/3.33
 * ```
 */
final class Money implements JsonSerializable, Stringable {
    use ErrorLog;

    /** @var numeric-string Kanonischer Betrag mit genau Nachkommastellen. */
    private readonly string $amount;

    private readonly CurrencyCode $currency;

    private readonly int $scale;

    /**
     * @param numeric-string $amount
     */
    private function __construct(string $amount, CurrencyCode $currency, int $scale) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->scale = $scale;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen Betrag aus einem Dezimal-String oder einer Ganzzahl.
     *
     * Akzeptiert deutsche/US-Formate ("1.234,56", "1,234.56", "1234.56") via
     * {@see NumberHelper::normalizeDecimalString()} und rundet auf die
     * Nachkommastellen der Währung (bzw. $scale).
     *
     * @param string|int   $amount   Betrag (Dezimal-String oder Ganzzahl in Haupteinheiten).
     * @param int|null     $scale    Nachkommastellen (null = Währungs-Standard).
     * @param RoundingMode $mode     Rundung, falls $amount mehr Stellen hat (Standard: HalfUp).
     */
    public static function of(string|int $amount, CurrencyCode $currency, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scale ??= $currency->getDefaultFractionDigits();
        if ($scale < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Scale darf nicht negativ sein: $scale");
        }

        // normalizeDecimalString liefert per Vertrag stets einen numeric-string.
        $canonical = NumberHelper::normalizeDecimalString((string) $amount);
        $rounded = NumberHelper::roundPrecise($canonical, $scale, $mode);

        return new self($rounded, $currency, $scale);
    }

    /**
     * Erzeugt einen Betrag aus Minor Units (z.B. Cent): ofMinor(1234, EUR) = 12,34 €.
     *
     * @param int      $minorUnits Betrag in kleinster Einheit.
     * @param int|null $scale      Nachkommastellen (null = Währungs-Standard).
     */
    public static function ofMinor(int $minorUnits, CurrencyCode $currency, ?int $scale = null): self {
        $scale ??= $currency->getDefaultFractionDigits();
        if ($scale < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Scale darf nicht negativ sein: $scale");
        }

        $factor = bcpow('10', (string) $scale); // 10^scale als numeric-string, ohne Überlauf
        $amount = NumberHelper::dividePrecise((string) $minorUnits, $factor, $scale, RoundingMode::Truncate);

        return new self($amount, $currency, $scale);
    }

    /**
     * Konstruiert aus einem float. NUR nutzen, wenn der Wert zwangsläufig als
     * float vorliegt — der float ist bereits vor dem Aufruf potenziell unpräzise.
     * Bevorzugt {@see of()} (String) oder {@see ofMinor()}.
     */
    public static function ofFloat(float $amount, CurrencyCode $currency, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        // Genügend Stellen einfangen, dann präzise auf die Zielskala runden.
        return self::of(sprintf('%.14F', $amount), $currency, $scale, $mode);
    }

    /**
     * Nullbetrag in der angegebenen Währung.
     */
    public static function zero(CurrencyCode $currency, ?int $scale = null): self {
        return self::of('0', $currency, $scale);
    }

    // ========================================================================
    // Arithmetik (liefert stets eine neue Instanz)
    // ========================================================================

    public function plus(self $other): self {
        $this->assertSameCurrency($other);
        return new self(NumberHelper::addPrecise($this->amount, $other->amount, $this->scale), $this->currency, $this->scale);
    }

    public function minus(self $other): self {
        $this->assertSameCurrency($other);
        return new self(NumberHelper::subtractPrecise($this->amount, $other->amount, $this->scale), $this->currency, $this->scale);
    }

    /**
     * Multipliziert mit einem Faktor (Skalar). Das Ergebnis wird auf die
     * Betragsskala gerundet.
     *
     * @param numeric-string|int $factor Multiplikator (z.B. Stückzahl oder Faktor "1.19").
     */
    public function times(string|int $factor, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self(NumberHelper::multiplyPrecise($this->amount, (string) $factor, $this->scale, $mode), $this->currency, $this->scale);
    }

    /**
     * Teilt durch einen Divisor (Skalar). Das Ergebnis wird auf die Betragsskala
     * gerundet. Für verlustfreie Aufteilung siehe {@see allocate()}.
     *
     * @param numeric-string|int $divisor Divisor (!= 0).
     */
    public function dividedBy(string|int $divisor, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self(NumberHelper::dividePrecise($this->amount, (string) $divisor, $this->scale, $mode), $this->currency, $this->scale);
    }

    public function negated(): self {
        return new self(NumberHelper::multiplyPrecise($this->amount, '-1', $this->scale, RoundingMode::Truncate), $this->currency, $this->scale);
    }

    public function abs(): self {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Verteilt den Betrag verlustfrei nach ganzzahligen Verhältnissen.
     *
     * Arbeitet in Minor Units, sodass die Summe der Teile exakt dem Original
     * entspricht (keine verlorenen/erzeugten Cent). Restbeträge werden der Reihe
     * nach auf die ersten Anteile verteilt.
     *
     * @param int ...$ratios Positive Verhältniszahlen (mind. eine, Summe > 0).
     * @return list<self> Die Teilbeträge in Reihenfolge der Verhältnisse.
     */
    public function allocate(int ...$ratios): array {
        if ($ratios === []) {
            self::logErrorAndThrow(InvalidArgumentException::class, "allocate() benötigt mindestens ein Verhältnis.");
        }

        $total = 0;
        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                self::logErrorAndThrow(InvalidArgumentException::class, "Verhältniszahlen dürfen nicht negativ sein.");
            }
            $total += $ratio;
        }
        if ($total <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Die Summe der Verhältniszahlen muss größer als 0 sein.");
        }

        $sign = $this->isNegative() ? -1 : 1;
        $amountMinor = abs($this->getMinorAmount());

        $shares = [];
        $allocated = 0;
        foreach ($ratios as $ratio) {
            $share = intdiv($amountMinor * $ratio, $total);
            $shares[] = $share;
            $allocated += $share;
        }

        // Restcent der Reihe nach verteilen (Summe bleibt exakt erhalten).
        $remainder = $amountMinor - $allocated;
        for ($i = 0; $i < $remainder; $i++) {
            $shares[$i] += 1;
        }

        return array_values(array_map(fn (int $minor): self => self::ofMinor($sign * $minor, $this->currency, $this->scale), $shares));
    }

    // ========================================================================
    // Vergleich
    // ========================================================================

    /**
     * Vergleicht zwei Beträge derselben Währung.
     *
     * @return int -1, 0 oder 1.
     */
    public function compareTo(self $other): int {
        $this->assertSameCurrency($other);
        return NumberHelper::comparePrecise($this->amount, $other->amount, $this->scale);
    }

    /**
     * Exakte Gleichheit (Währung UND Betrag). Unterschiedliche Währungen sind
     * nie gleich (kein Fehler).
     */
    public function equals(self $other): bool {
        return $this->currency === $other->currency
            && NumberHelper::comparePrecise($this->amount, $other->amount, max($this->scale, $other->scale)) === 0;
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
        return NumberHelper::comparePrecise($this->amount, '0', $this->scale) === 0;
    }

    public function isPositive(): bool {
        return NumberHelper::comparePrecise($this->amount, '0', $this->scale) > 0;
    }

    public function isNegative(): bool {
        return NumberHelper::comparePrecise($this->amount, '0', $this->scale) < 0;
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Kanonischer Betrag als numeric-string mit genau $scale Nachkommastellen (z.B. "12.34").
     *
     * @return numeric-string
     */
    public function getAmount(): string {
        return $this->amount;
    }

    /**
     * Betrag in Minor Units (Cent). Exakt, da der Betrag genau $scale Stellen hat.
     */
    public function getMinorAmount(): int {
        $factor = bcpow('10', (string) $this->scale); // 10^scale als numeric-string
        return (int) NumberHelper::multiplyPrecise($this->amount, $factor, 0, RoundingMode::Truncate);
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getScale(): int {
        return $this->scale;
    }

    // ========================================================================
    // Formatierung / Serialisierung
    // ========================================================================

    /**
     * Formatiert den Betrag präzise (ohne float-Zwischenschritt).
     *
     * @param bool $withSymbol             Währungssymbol anhängen (Standard: true).
     * @param bool $withThousandsSeparator Tausendertrenner (Standard: true).
     * @param string $decimalSeparator     Dezimaltrenner (Standard: ',').
     * @param string $thousandsSeparator   Tausendertrenner-Zeichen (Standard: '.').
     */
    public function format(bool $withSymbol = true, bool $withThousandsSeparator = true, string $decimalSeparator = ',', string $thousandsSeparator = '.'): string {
        $formatted = $this->formatAmount($decimalSeparator, $withThousandsSeparator ? $thousandsSeparator : '');

        if (!$withSymbol) {
            return $formatted;
        }

        $symbol = $this->currency->getSymbol();
        if ($symbol === '') {
            $symbol = $this->currency->value;
        }

        return $formatted . ' ' . $symbol;
    }

    /**
     * Präzise Gruppierung des kanonischen Betrags-Strings (kein float).
     */
    private function formatAmount(string $decimalSeparator, string $thousandsSeparator): string {
        $negative = str_starts_with($this->amount, '-');
        $abs = ltrim($this->amount, '-');

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
     * Maschinenlesbare Darstellung: "12.34 EUR".
     */
    public function __toString(): string {
        return $this->amount . ' ' . $this->currency->value;
    }

    /**
     * @return array{amount: numeric-string, currency: string}
     */
    public function jsonSerialize(): array {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    private function assertSameCurrency(self $other): void {
        if ($this->currency !== $other->currency) {
            self::logErrorAndThrow(
                InvalidArgumentException::class,
                "Währungen unterscheiden sich: {$this->currency->value} vs. {$other->currency->value}"
            );
        }
    }
}
