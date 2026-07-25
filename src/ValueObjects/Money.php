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

use CommonToolkit\Enums\{CountryCode, CurrencyCode, RoundingMode};
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
     * @param string|int       $amount   Betrag (Dezimal-String oder Ganzzahl in Haupteinheiten).
     * @param int|null         $scale    Nachkommastellen (null = Währungs-Standard).
     * @param RoundingMode     $mode     Rundung, falls $amount mehr Stellen hat (Standard: HalfUp).
     * @param CountryCode|null $country  Land für eindeutige Tausendertrenner-Erkennung ("2.000" = 2000 statt 2,0).
     */
    public static function of(string|int $amount, CurrencyCode $currency, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): self {
        $scale = self::assertScale($scale, $currency);

        // normalizeDecimalString liefert per Vertrag stets einen numeric-string.
        $canonical = NumberHelper::normalizeDecimalString((string) $amount, $country);
        $rounded = NumberHelper::roundPrecise($canonical, $scale, $mode);

        return new self($rounded, $currency, $scale);
    }

    /**
     * Wie {@see of()}, unterscheidet aber "nicht angegeben" von der echten Null:
     * `null`, Leerstring und nicht deutbare Eingaben ergeben `null` statt 0.
     *
     * Gedacht für Importe, Parser und nullable Datenbankspalten.
     */
    public static function ofNullable(string|int|null $amount, CurrencyCode $currency, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp, ?CountryCode $country = null): ?self {
        if ($amount === null) {
            return null;
        }

        $canonical = NumberHelper::normalizeDecimalStringOrNull((string) $amount, $country);
        if ($canonical === null) {
            return null;
        }

        $scale = self::assertScale($scale, $currency);

        return new self(NumberHelper::roundPrecise($canonical, $scale, $mode), $currency, $scale);
    }

    /**
     * Erzeugt einen Betrag aus Minor Units (z.B. Cent): ofMinor(1234, EUR) = 12,34 €.
     *
     * @param int      $minorUnits Betrag in kleinster Einheit.
     * @param int|null $scale      Nachkommastellen (null = Währungs-Standard).
     */
    public static function ofMinor(int $minorUnits, CurrencyCode $currency, ?int $scale = null): self {
        $scale = self::assertScale($scale, $currency);

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

    /**
     * Rekonstruiert einen Betrag aus seiner Array-Darstellung — Gegenstück zu
     * {@see jsonSerialize()}. Grundlage für JSON-/Datenbank-Grenzen (z.B.
     * Eloquent-Casts), die Betrag UND Währung gemeinsam ablegen.
     *
     * @param array{amount?: string|int|float|null, currency?: string|CurrencyCode|null, scale?: int|null} $data
     * @param CurrencyCode|null $fallbackCurrency Währung, falls im Array keine steht.
     */
    public static function fromArray(array $data, ?CurrencyCode $fallbackCurrency = null, ?int $scale = null): self {
        $rawCurrency = $data['currency'] ?? null;
        $currency = match (true) {
            $rawCurrency instanceof CurrencyCode => $rawCurrency,
            is_string($rawCurrency) && $rawCurrency !== '' => CurrencyCode::fromCode($rawCurrency),
            $fallbackCurrency !== null => $fallbackCurrency,
            default => self::logErrorAndThrow(InvalidArgumentException::class, "Array enthält keine Währung und es wurde keine Fallback-Währung übergeben."),
        };

        $amount = $data['amount'] ?? '0';

        return self::of(is_float($amount) ? self::numericString($amount) : (string) $amount, $currency, $scale ?? ($data['scale'] ?? null));
    }

    /**
     * Summiert Beträge exakt (eine Summierung statt Kette aus plus(), damit keine
     * Zwischenrundung auftritt). Alle Beträge müssen dieselbe Währung haben.
     *
     * @param iterable<self>    $monies   Zu summierende Beträge.
     * @param CurrencyCode|null $currency Währung für den leeren Fall (sonst aus dem ersten Betrag).
     * @param int|null          $scale    Zielskala (null = größte Skala der Beträge).
     */
    public static function sum(iterable $monies, ?CurrencyCode $currency = null, ?int $scale = null): self {
        $amounts = [];
        $itemScale = null;
        foreach ($monies as $money) {
            $currency ??= $money->currency;
            if ($money->currency !== $currency) {
                self::logErrorAndThrow(InvalidArgumentException::class, "Währungen unterscheiden sich: {$currency->value} vs. {$money->currency->value}");
            }
            $itemScale = $itemScale === null ? $money->scale : max($itemScale, $money->scale);
            $amounts[] = $money->amount;
        }

        if ($currency === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "sum() benötigt bei leerer Liste eine Währung.");
        }

        $scale = self::assertScale($scale ?? $itemScale, $currency);

        return new self(NumberHelper::sumPrecise($amounts, $scale, RoundingMode::HalfUp), $currency, $scale);
    }

    /**
     * Kleinster der übergebenen Beträge (gleiche Währung vorausgesetzt).
     */
    public static function min(self $first, self ...$rest): self {
        $result = $first;
        foreach ($rest as $money) {
            if ($money->lessThan($result)) {
                $result = $money;
            }
        }

        return $result;
    }

    /**
     * Größter der übergebenen Beträge (gleiche Währung vorausgesetzt).
     */
    public static function max(self $first, self ...$rest): self {
        $result = $first;
        foreach ($rest as $money) {
            if ($money->greaterThan($result)) {
                $result = $money;
            }
        }

        return $result;
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
     * @param numeric-string|int|float $factor Multiplikator (z.B. Stückzahl oder Faktor "1.19").
     */
    public function times(string|int|float $factor, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self(NumberHelper::multiplyPrecise($this->amount, self::numericString($factor), $this->scale, $mode), $this->currency, $this->scale);
    }

    /**
     * Teilt durch einen Divisor (Skalar). Das Ergebnis wird auf die Betragsskala
     * gerundet. Für verlustfreie Aufteilung siehe {@see allocate()}.
     *
     * @param numeric-string|int|float $divisor Divisor (!= 0).
     */
    public function dividedBy(string|int|float $divisor, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self(NumberHelper::dividePrecise($this->amount, self::numericString($divisor), $this->scale, $mode), $this->currency, $this->scale);
    }

    /**
     * Prozentualer Anteil des Betrags — der Standardweg für Steuer-, Rabatt- und
     * Skonto-Rechnungen: `$net->percentage('19')` ergibt den Steuerbetrag.
     *
     * @param numeric-string|int|float $percent Prozentsatz (19 = 19 %).
     */
    public function percentage(string|int|float $percent, RoundingMode $mode = RoundingMode::HalfUp): self {
        return new self(NumberHelper::percentOfPrecise($this->amount, self::numericString($percent), $this->scale, $mode), $this->currency, $this->scale);
    }

    /**
     * Betrag zuzüglich Prozentsatz (Netto → Brutto): `$net->plusPercentage('19')`.
     *
     * @param numeric-string|int|float $percent Prozentsatz (19 = 19 %).
     */
    public function plusPercentage(string|int|float $percent, RoundingMode $mode = RoundingMode::HalfUp): self {
        return $this->plus($this->percentage($percent, $mode));
    }

    /**
     * Betrag abzüglich Prozentsatz (z.B. Rabatt/Skonto): `$gross->minusPercentage('3')`.
     *
     * @param numeric-string|int|float $percent Prozentsatz (3 = 3 %).
     */
    public function minusPercentage(string|int|float $percent, RoundingMode $mode = RoundingMode::HalfUp): self {
        return $this->minus($this->percentage($percent, $mode));
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

    /**
     * Verteilt den Betrag verlustfrei nach beliebigen (auch dezimalen) Gewichten.
     *
     * Anders als {@see allocate()} sind die Gewichte hier nicht auf Ganzzahlen
     * beschränkt — der typische Fall ist die Verteilung eines Belegrabatts auf
     * Steuersätze oder eines Frachtanteils auf Positionsnettos. Verwendet das
     * Largest-Remainder-Verfahren, die Summe der Teile entspricht exakt dem
     * Original. Array-Schlüssel bleiben erhalten.
     *
     * @param array<array-key, numeric-string|int|float> $weights Gewichte je Position (alle 0 → gleichmäßig).
     * @return array<array-key, self>
     */
    public function allocateByWeights(array $weights): array {
        if ($weights === []) {
            return [];
        }

        return array_map(
            fn (string $part): self => new self($part, $this->currency, $this->scale),
            NumberHelper::allocate($this->amount, $weights, $this->scale)
        );
    }

    /**
     * Teilt den Betrag verlustfrei in gleich große Teile (Restcent auf die ersten).
     *
     * @param int $parts Anzahl Teile (> 0).
     * @return list<self>
     */
    public function split(int $parts): array {
        if ($parts <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Anzahl Teile muss größer als 0 sein: $parts");
        }

        return array_values(array_map(
            fn (string $part): self => new self($part, $this->currency, $this->scale),
            NumberHelper::allocateEvenly($this->amount, $parts, $this->scale)
        ));
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

    /**
     * Gleiche Währung? (Vorprüfung, wo Arithmetik sonst eine Exception wirft.)
     */
    public function isSameCurrency(self $other): bool {
        return $this->currency === $other->currency;
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

    /**
     * Betrag als float — ausschließlich für Grenzen, die keinen exakten Typ
     * annehmen (Alt-APIs, Diagramm-/Statistikbibliotheken, JSON-Zahlen).
     * Innerhalb einer Rechenkette NIE verwenden: ab hier ist die Präzision weg.
     */
    public function toFloat(): float {
        return (float) $this->amount;
    }

    /**
     * Gleicher Betrag mit anderer Nachkommastellenzahl (z.B. 2 → 3 Stellen für
     * Zwischenrechnungen oder 3 → 2 für die Belegausgabe).
     */
    public function withScale(int $scale, RoundingMode $mode = RoundingMode::HalfUp): self {
        $scale = self::assertScale($scale, $this->currency);
        if ($scale === $this->scale) {
            return $this;
        }

        return new self(NumberHelper::roundPrecise($this->amount, $scale, $mode), $this->currency, $scale);
    }

    /**
     * Rechnet mit einem Wechselkurs in eine andere Währung um. Der Kurs ist der
     * Faktor Zielwährung/Ausgangswährung (1 EUR = 0.92 CHF → convertTo(CHF, '0.92')).
     * Die Zielskala folgt standardmäßig der Zielwährung (JPY 0, KWD 3 Stellen).
     *
     * @param numeric-string|int|float $rate Wechselkurs (> 0).
     */
    public function convertTo(CurrencyCode $target, string|int|float $rate, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        $rateString = self::numericString($rate);
        if (NumberHelper::signPrecise($rateString) <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Wechselkurs muss größer als 0 sein: $rateString");
        }

        $scale = self::assertScale($scale, $target);

        // Mit Reserve rechnen und erst am Ende auf die Zielskala runden.
        $work = NumberHelper::multiplyPrecise($this->amount, $rateString, $scale + 6, RoundingMode::Truncate);

        return new self(NumberHelper::roundPrecise($work, $scale, $mode), $target, $scale);
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

    /**
     * Prüft die Skala und löst den Währungs-Standard auf (ISO-4217-Exponent).
     */
    private static function assertScale(?int $scale, CurrencyCode $currency): int {
        $scale ??= $currency->getDefaultFractionDigits();
        if ($scale < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Scale darf nicht negativ sein: $scale");
        }

        return $scale;
    }

    /**
     * Skalare Eingabe (Faktor, Prozentsatz, Kurs) auf kanonischen numeric-string.
     * Floats werden mit voller Stellenzahl eingefangen, statt sie zu kürzen.
     *
     * @param numeric-string|int|float $value
     * @return numeric-string
     */
    private static function numericString(string|int|float $value): string {
        return NumberHelper::normalizeDecimalString(is_float($value) ? sprintf('%.14F', $value) : (string) $value);
    }

    private function assertSameCurrency(self $other): void {
        if ($this->currency !== $other->currency) {
            self::logErrorAndThrow(
                InvalidArgumentException::class,
                "Währungen unterscheiden sich: {$this->currency->value} vs. {$other->currency->value}"
            );
        }
    }
}
