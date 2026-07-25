<?php
/*
 * Created on   : Sat Jul 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MoneyTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\{CountryCode, CurrencyCode, RoundingMode};
use CommonToolkit\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class MoneyTest extends BaseTestCase {
    private CurrencyCode $eur;

    protected function setUp(): void {
        parent::setUp();
        $this->eur = CurrencyCode::Euro;
    }

    // ==================== Konstruktion ====================

    public function test_of_string_and_int(): void {
        $this->assertSame('12.34', Money::of('12.34', $this->eur)->getAmount());
        $this->assertSame('5.00', Money::of(5, $this->eur)->getAmount());
        $this->assertSame('0.00', Money::zero($this->eur)->getAmount());
    }

    public function test_of_accepts_german_and_us_format(): void {
        $this->assertSame('1234.56', Money::of('1.234,56', $this->eur)->getAmount());
        $this->assertSame('1234.56', Money::of('1,234.56', $this->eur)->getAmount());
    }

    public function test_of_rounds_to_currency_scale(): void {
        $this->assertSame('12.35', Money::of('12.345', $this->eur)->getAmount());
        $this->assertSame('12.34', Money::of('12.344', $this->eur)->getAmount());
        // 1.005 ist als float nicht exakt darstellbar -> präzise Rundung liefert 1.01
        $this->assertSame('1.01', Money::of('1.005', $this->eur)->getAmount());
    }

    public function test_of_minor_round_trips(): void {
        $money = Money::ofMinor(1999, $this->eur);
        $this->assertSame('19.99', $money->getAmount());
        $this->assertSame(1999, $money->getMinorAmount());
        $this->assertSame(-1999, Money::ofMinor(-1999, $this->eur)->getMinorAmount());
    }

    public function test_of_float_is_precise_after_rounding(): void {
        $this->assertTrue(Money::ofFloat(19.99, $this->eur)->equals(Money::ofMinor(1999, $this->eur)));
    }

    // ==================== Arithmetik / Präzision ====================

    public function test_addition_is_precise(): void {
        // Der klassische float-Fehler: 0.1 + 0.2 !== 0.3
        $result = Money::of('0.1', $this->eur)->plus(Money::of('0.2', $this->eur));
        $this->assertTrue($result->equals(Money::of('0.3', $this->eur)));
        $this->assertSame('0.30', $result->getAmount());
    }

    public function test_minus_times_divided(): void {
        $this->assertSame('14.99', Money::of('19.99', $this->eur)->minus(Money::of('5.00', $this->eur))->getAmount());
        $this->assertSame('9.99', Money::of('3.33', $this->eur)->times(3)->getAmount());
        $this->assertSame('3.33', Money::of('10.00', $this->eur)->dividedBy(3, RoundingMode::HalfUp)->getAmount());
    }

    public function test_negated_and_abs(): void {
        $this->assertSame('-5.00', Money::of('5.00', $this->eur)->negated()->getAmount());
        $this->assertSame('5.00', Money::of('-5.00', $this->eur)->abs()->getAmount());
        $this->assertSame('5.00', Money::of('5.00', $this->eur)->abs()->getAmount());
    }

    public function test_immutability(): void {
        $a = Money::of('10.00', $this->eur);
        $a->plus(Money::of('5.00', $this->eur));
        $this->assertSame('10.00', $a->getAmount(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== allocate: Summen-Invariante ====================

    /**
     * @param list<int> $ratios
     */
    #[DataProvider('allocateProvider')]
    public function test_allocate_preserves_total(string $amount, array $ratios, int $expectedParts): void {
        $money = Money::of($amount, $this->eur);
        $parts = $money->allocate(...$ratios);

        $this->assertCount($expectedParts, $parts);

        $sum = Money::zero($this->eur);
        foreach ($parts as $part) {
            $sum = $sum->plus($part);
        }
        $this->assertTrue($sum->equals($money), "Summe der Teile ({$sum->getAmount()}) muss dem Original ({$money->getAmount()}) entsprechen.");
    }

    /**
     * @return array<string, array{string, list<int>, int}>
     */
    public static function allocateProvider(): array {
        return [
            'gleich, unteilbar' => ['10.00', [1, 1, 1], 3],
            'kleiner Restcent' => ['0.05', [1, 1, 1], 3],
            'gewichtet' => ['100.00', [70, 20, 10], 3],
            'negativ' => ['-10.01', [1, 1, 1], 3],
            'zwei Teile' => ['0.10', [3, 7], 2],
            'ein Teil' => ['9.99', [1], 1],
        ];
    }

    public function test_allocate_rejects_invalid_ratios(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::of('10.00', $this->eur)->allocate(0, 0);
    }

    // ==================== Vergleich ====================

    public function test_comparison(): void {
        $a = Money::of('10.00', $this->eur);
        $b = Money::of('20.00', $this->eur);

        $this->assertTrue($a->lessThan($b));
        $this->assertTrue($b->greaterThan($a));
        $this->assertTrue($a->lessThanOrEqual(Money::of('10.00', $this->eur)));
        $this->assertSame(-1, $a->compareTo($b));
        $this->assertSame(0, $a->compareTo(Money::of('10.00', $this->eur)));
    }

    public function test_sign_predicates(): void {
        $this->assertTrue(Money::zero($this->eur)->isZero());
        $this->assertTrue(Money::of('0.01', $this->eur)->isPositive());
        $this->assertTrue(Money::of('-0.01', $this->eur)->isNegative());
    }

    public function test_equals_is_false_across_currencies(): void {
        $usd = CurrencyCode::from('USD');
        $this->assertFalse(Money::of('10.00', $this->eur)->equals(Money::of('10.00', $usd)));
    }

    public function test_arithmetic_across_currencies_throws(): void {
        $usd = CurrencyCode::from('USD');
        $this->expectException(InvalidArgumentException::class);
        Money::of('10.00', $this->eur)->plus(Money::of('10.00', $usd));
    }

    // ==================== Formatierung / Serialisierung ====================

    public function test_format_is_precise(): void {
        $this->assertSame('1.234.567,50 €', Money::of('1234567.5', $this->eur)->format());
        $this->assertSame('1234567,50', Money::of('1234567.5', $this->eur)->format(false, false));
        $this->assertSame('-89,90', Money::of('-89.9', $this->eur)->format(false, false));
    }

    public function test_to_string_and_json(): void {
        $money = Money::of('12.34', $this->eur);
        $this->assertSame('12.34 EUR', (string) $money);
        $this->assertSame('{"amount":"12.34","currency":"EUR"}', json_encode($money));
    }

    // ==================== Währungs-Nachkommastellen ====================

    public function test_currency_fraction_digits(): void {
        $this->assertSame(2, CurrencyCode::Euro->getDefaultFractionDigits());
        $this->assertSame(0, CurrencyCode::from('JPY')->getDefaultFractionDigits());
        $this->assertSame(3, CurrencyCode::from('KWD')->getDefaultFractionDigits());
    }

    public function test_jpy_uses_zero_scale(): void {
        $jpy = CurrencyCode::from('JPY');
        $money = Money::of('1234.7', $jpy);
        $this->assertSame('1235', $money->getAmount());
        $this->assertSame(0, $money->getScale());
        $this->assertSame('1.235 ¥', $money->format());
    }

    // ==================== ofNullable / fromArray ====================

    public function test_of_nullable_separates_missing_from_zero(): void {
        $this->assertNull(Money::ofNullable(null, $this->eur));
        $this->assertNull(Money::ofNullable('', $this->eur));
        $this->assertNull(Money::ofNullable('n/a', $this->eur));
        $this->assertSame('0.00', Money::ofNullable('0', $this->eur)?->getAmount());
        $this->assertSame('12.34', Money::ofNullable('12,34', $this->eur)?->getAmount());
    }

    public function test_of_with_country_resolves_german_thousands(): void {
        $this->assertSame('2000.00', Money::of('2.000', $this->eur, null, RoundingMode::HalfUp, CountryCode::Germany)->getAmount());
    }

    public function test_from_array_round_trips_json(): void {
        $money = Money::of('1234.56', $this->eur);
        /** @var array{amount: string, currency: string} $data */
        $data = json_decode((string) json_encode($money), true);

        $this->assertTrue(Money::fromArray($data)->equals($money));
        $this->assertTrue(Money::fromArray(['amount' => '5.00'], $this->eur)->equals(Money::of(5, $this->eur)));
    }

    public function test_from_array_without_currency_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::fromArray(['amount' => '5.00']);
    }

    // ==================== sum / min / max ====================

    public function test_sum_is_exact_and_avoids_intermediate_rounding(): void {
        $monies = [Money::of('0.1', $this->eur), Money::of('0.2', $this->eur), Money::of('0.3', $this->eur)];
        $this->assertSame('0.60', Money::sum($monies)->getAmount());
        $this->assertSame('0.00', Money::sum([], $this->eur)->getAmount());
    }

    public function test_sum_uses_largest_scale(): void {
        $sum = Money::sum([Money::of('1.005', $this->eur, 3), Money::of('1.00', $this->eur)]);
        $this->assertSame(3, $sum->getScale());
        $this->assertSame('2.005', $sum->getAmount());
    }

    public function test_sum_rejects_mixed_currencies(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::sum([Money::of('1.00', $this->eur), Money::of('1.00', CurrencyCode::from('USD'))]);
    }

    public function test_sum_on_empty_list_without_currency_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::sum([]);
    }

    public function test_min_and_max(): void {
        $a = Money::of('10.00', $this->eur);
        $b = Money::of('-2.50', $this->eur);
        $c = Money::of('99.99', $this->eur);

        $this->assertSame('-2.50', Money::min($a, $b, $c)->getAmount());
        $this->assertSame('99.99', Money::max($a, $b, $c)->getAmount());
        $this->assertSame('10.00', Money::min($a)->getAmount());
    }

    // ==================== Prozentrechnung ====================

    public function test_percentage_matches_tax_expectations(): void {
        $net = Money::of('100.00', $this->eur);
        $this->assertSame('19.00', $net->percentage('19')->getAmount());
        $this->assertSame('119.00', $net->plusPercentage(19)->getAmount());
        $this->assertSame('97.00', $net->minusPercentage(3.0)->getAmount());
        // 8,15 € * 19 % = 1,5485 -> kaufmännisch 1,55 (float ergäbe 1,54)
        $this->assertSame('1.55', Money::of('8.15', $this->eur)->percentage('19')->getAmount());
    }

    // ==================== allocateByWeights / split ====================

    public function test_allocate_by_weights_preserves_total_and_keys(): void {
        $discount = Money::of('10.00', $this->eur);
        $parts = $discount->allocateByWeights(['19.00' => '84.03', '7.00' => '15.97']);

        $this->assertSame(['19.00', '7.00'], array_keys($parts));
        $this->assertTrue(Money::sum($parts)->equals($discount));
    }

    public function test_allocate_by_weights_handles_zero_weights_and_empty(): void {
        $parts = Money::of('1.00', $this->eur)->allocateByWeights([0, 0, 0]);
        $this->assertTrue(Money::sum($parts)->equals(Money::of('1.00', $this->eur)));
        $this->assertSame([], Money::of('1.00', $this->eur)->allocateByWeights([]));
    }

    public function test_split_preserves_total(): void {
        $parts = Money::of('10.00', $this->eur)->split(3);
        $this->assertCount(3, $parts);
        $this->assertTrue(Money::sum($parts)->equals(Money::of('10.00', $this->eur)));
    }

    public function test_split_rejects_zero_parts(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::of('10.00', $this->eur)->split(0);
    }

    // ==================== Skala / Währungsumrechnung / Austritt ====================

    public function test_with_scale(): void {
        $money = Money::of('1.005', $this->eur, 3);
        $this->assertSame('1.01', $money->withScale(2)->getAmount());
        $this->assertSame('1.00', $money->withScale(2, RoundingMode::Floor)->getAmount());
        $this->assertSame($money, $money->withScale(3), 'Gleiche Skala darf keine neue Instanz erzwingen.');
    }

    public function test_convert_to_uses_target_currency_scale(): void {
        $chf = CurrencyCode::from('CHF');
        $converted = Money::of('100.00', $this->eur)->convertTo($chf, '0.9385');
        $this->assertSame('93.85', $converted->getAmount());
        $this->assertSame($chf, $converted->getCurrency());

        $jpy = CurrencyCode::from('JPY');
        $this->assertSame('16234', Money::of('100.00', $this->eur)->convertTo($jpy, '162.3421')->getAmount());
    }

    public function test_convert_to_rejects_non_positive_rate(): void {
        $this->expectException(InvalidArgumentException::class);
        Money::of('100.00', $this->eur)->convertTo(CurrencyCode::from('CHF'), '0');
    }

    public function test_to_float_and_same_currency(): void {
        $this->assertSame(12.34, Money::of('12.34', $this->eur)->toFloat());
        $this->assertTrue(Money::of('1.00', $this->eur)->isSameCurrency(Money::of('2.00', $this->eur)));
        $this->assertFalse(Money::of('1.00', $this->eur)->isSameCurrency(Money::of('1.00', CurrencyCode::from('USD'))));
    }
}
