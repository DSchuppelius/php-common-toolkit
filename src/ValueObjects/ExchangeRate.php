<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExchangeRate.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Enums\{CurrencyCode, RoundingMode};
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutabler Wechselkurs mit expliziter Ausgangs- und Zielwährung.
 *
 * Die Richtung des Faktors ist damit eindeutig — ein versehentlich
 * invertierter Kurs wird vermieden. Die Bedeutung lautet stets:
 *
 *     1 Einheit sourceCurrency = rate Einheiten targetCurrency
 *
 * {@see convert()} delegiert an {@see Money::convertTo()} und dupliziert
 * dessen Rundungslogik nicht. Ein Gültigkeitszeitpunkt gehört bewusst nicht
 * in dieses Value Object — ein historisches Kursangebot ist ein späteres,
 * separates Domänenobjekt.
 *
 * @example
 * ```php
 * $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
 * $rate->convert(Money::of('100.00', CurrencyCode::Euro)); // 93,85 CHF
 * $rate->inverse();                                        // 1 CHF = 1.0655301012 EUR
 * ```
 */
final class ExchangeRate implements JsonSerializable, Stringable {
    use ErrorLog;

    private readonly CurrencyCode $sourceCurrency;

    private readonly CurrencyCode $targetCurrency;

    private readonly Decimal $rate;

    private function __construct(CurrencyCode $sourceCurrency, CurrencyCode $targetCurrency, Decimal $rate) {
        $this->sourceCurrency = $sourceCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->rate = $rate;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen Wechselkurs: 1 $sourceCurrency = $rate $targetCurrency.
     *
     * @param CurrencyCode       $sourceCurrency Ausgangswährung.
     * @param CurrencyCode       $targetCurrency Zielwährung.
     * @param string|int|Decimal $rate           Kurs (echt größer null).
     * @param int|null           $scale          Nachkommastellen des Kurses (null = aus der Eingabe übernehmen).
     * @param RoundingMode       $mode           Rundung, falls $rate mehr Stellen hat (Standard: HalfUp).
     * @throws InvalidArgumentException Bei Kurs <= 0 oder identischen Währungen mit Kurs != 1.
     */
    public static function of(CurrencyCode $sourceCurrency, CurrencyCode $targetCurrency, string|int|Decimal $rate, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): self {
        if ($rate instanceof Decimal) {
            $decimalRate = $scale === null ? $rate : $rate->withScale($scale, $mode);
        } else {
            $decimalRate = Decimal::of($rate, $scale, $mode);
        }

        if (!$decimalRate->isPositive()) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Wechselkurs muss größer als 0 sein: {$decimalRate->getValue()}");
        }

        if ($sourceCurrency === $targetCurrency && !$decimalRate->equals(Decimal::one())) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Bei identischer Ausgangs- und Zielwährung ({$sourceCurrency->value}) ist nur Kurs 1 zulässig: {$decimalRate->getValue()}");
        }

        return new self($sourceCurrency, $targetCurrency, $decimalRate);
    }

    /**
     * Rekonstruiert einen Kurs aus seiner Array-Darstellung — Gegenstück zu
     * {@see jsonSerialize()}.
     *
     * @param array{source?: string|CurrencyCode|null, target?: string|CurrencyCode|null, rate?: string|int|float|null, scale?: int|null} $data
     * @throws InvalidArgumentException Wenn Währungen oder Kurs fehlen.
     */
    public static function fromArray(array $data): self {
        $source = self::currencyFromArrayValue($data['source'] ?? null, 'source');
        $target = self::currencyFromArrayValue($data['target'] ?? null, 'target');

        $rate = $data['rate'] ?? null;
        if ($rate === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Array enthält keinen Kurs ('rate' fehlt).");
        }

        $scale = $data['scale'] ?? null;

        return self::of($source, $target, is_float($rate) ? Decimal::ofFloat($rate, $scale) : $rate, $scale);
    }

    // ========================================================================
    // Verhalten
    // ========================================================================

    /**
     * Rechnet einen Geldbetrag in die Zielwährung um. Akzeptiert nur Beträge
     * in der Ausgangswährung; die Zielskala folgt ohne explizite Angabe dem
     * ISO-4217-Standard der Zielwährung. Delegiert an {@see Money::convertTo()}.
     *
     * @throws InvalidArgumentException Bei falscher Ausgangswährung.
     */
    public function convert(Money $money, ?int $scale = null, RoundingMode $mode = RoundingMode::HalfUp): Money {
        if ($money->getCurrency() !== $this->sourceCurrency) {
            self::logErrorAndThrow(
                InvalidArgumentException::class,
                "Kurs gilt für {$this->sourceCurrency->value} → {$this->targetCurrency->value}, Betrag ist in {$money->getCurrency()->value}."
            );
        }

        return $money->convertTo($this->targetCurrency, $this->rate->getValue(), $scale, $mode);
    }

    /**
     * Umgekehrter Kurs: Währungen getauscht, Kurs = 1 / rate mit der
     * angegebenen Skala.
     */
    public function inverse(int $scale = 10, RoundingMode $mode = RoundingMode::HalfUp): self {
        return self::of($this->targetCurrency, $this->sourceCurrency, Decimal::one()->dividedBy($this->rate, $scale, $mode));
    }

    /**
     * Deckt dieser Kurs die angefragte Richtung ab?
     */
    public function supports(CurrencyCode $sourceCurrency, CurrencyCode $targetCurrency): bool {
        return $this->sourceCurrency === $sourceCurrency && $this->targetCurrency === $targetCurrency;
    }

    /**
     * Fachliche Gleichheit: gleiche Richtung und gleicher Kurswert
     * (Skala unerheblich).
     */
    public function equals(self $other): bool {
        return $this->sourceCurrency === $other->sourceCurrency
            && $this->targetCurrency === $other->targetCurrency
            && $this->rate->equals($other->rate);
    }

    // ========================================================================
    // Zugriff / Serialisierung
    // ========================================================================

    public function getSourceCurrency(): CurrencyCode {
        return $this->sourceCurrency;
    }

    public function getTargetCurrency(): CurrencyCode {
        return $this->targetCurrency;
    }

    public function getRate(): Decimal {
        return $this->rate;
    }

    /**
     * Maschinenlesbare Darstellung: "1 EUR = 0.9385 CHF".
     */
    public function __toString(): string {
        return "1 {$this->sourceCurrency->value} = {$this->rate->getValue()} {$this->targetCurrency->value}";
    }

    /**
     * @return array{source: string, target: string, rate: numeric-string, scale: int}
     */
    public function jsonSerialize(): array {
        return [
            'source' => $this->sourceCurrency->value,
            'target' => $this->targetCurrency->value,
            'rate' => $this->rate->getValue(),
            'scale' => $this->rate->getScale(),
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Währung aus einem Array-Wert auflösen (String-Code oder Enum).
     */
    private static function currencyFromArrayValue(string|CurrencyCode|null $value, string $key): CurrencyCode {
        return match (true) {
            $value instanceof CurrencyCode => $value,
            is_string($value) && $value !== '' => CurrencyCode::fromCode($value),
            default => self::logErrorAndThrow(InvalidArgumentException::class, "Array enthält keine gültige Währung ('$key' fehlt)."),
        };
    }
}
