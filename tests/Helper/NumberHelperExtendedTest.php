<?php
/*
 * Created on   : Wed Jan 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelperExtendedTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Enums\{CurrencyCode, PercentileMethod};
use CommonToolkit\Helper\Data\NumberHelper;
use Tests\Contracts\BaseTestCase;

class NumberHelperExtendedTest extends BaseTestCase {
    public function test_format_currency(): void {
        $this->assertEquals('1.234,56 €', NumberHelper::formatCurrency(1234.56));
        $this->assertEquals('-100,00 €', NumberHelper::formatCurrency(-100));
        $this->assertEquals('$ 1,234.56', NumberHelper::formatCurrency(1234.56, CurrencyCode::USDollar, 2, '.', ',', true));
    }

    public function test_ordinalize(): void {
        // Deutsch
        $this->assertEquals('1.', NumberHelper::ordinalize(1, 'de'));
        $this->assertEquals('2.', NumberHelper::ordinalize(2, 'de'));
        $this->assertEquals('100.', NumberHelper::ordinalize(100, 'de'));

        // Englisch
        $this->assertEquals('1st', NumberHelper::ordinalize(1, 'en'));
        $this->assertEquals('2nd', NumberHelper::ordinalize(2, 'en'));
        $this->assertEquals('3rd', NumberHelper::ordinalize(3, 'en'));
        $this->assertEquals('4th', NumberHelper::ordinalize(4, 'en'));
        $this->assertEquals('11th', NumberHelper::ordinalize(11, 'en'));
        $this->assertEquals('12th', NumberHelper::ordinalize(12, 'en'));
        $this->assertEquals('13th', NumberHelper::ordinalize(13, 'en'));
        $this->assertEquals('21st', NumberHelper::ordinalize(21, 'en'));
        $this->assertEquals('22nd', NumberHelper::ordinalize(22, 'en'));
    }

    public function test_to_words(): void {
        $this->assertEquals('null', NumberHelper::toWords(0));
        $this->assertEquals('eins', NumberHelper::toWords(1));
        $this->assertEquals('zwölf', NumberHelper::toWords(12));
        $this->assertEquals('einundzwanzig', NumberHelper::toWords(21));
        $this->assertEquals('einhundertdreiundzwanzig', NumberHelper::toWords(123));
        $this->assertEquals('eintausend', NumberHelper::toWords(1000));
        $this->assertEquals('eine Million', NumberHelper::toWords(1000000));
        $this->assertEquals('minus fünf', NumberHelper::toWords(-5));
        $this->assertEquals('Zehn', NumberHelper::toWords(10, true));
    }

    public function test_is_even_and_odd(): void {
        $this->assertTrue(NumberHelper::isEven(2));
        $this->assertTrue(NumberHelper::isEven(0));
        $this->assertFalse(NumberHelper::isEven(1));

        $this->assertTrue(NumberHelper::isOdd(1));
        $this->assertTrue(NumberHelper::isOdd(3));
        $this->assertFalse(NumberHelper::isOdd(2));
    }

    public function test_is_positive_negative_zero(): void {
        $this->assertTrue(NumberHelper::isPositive(5));
        $this->assertFalse(NumberHelper::isPositive(-5));
        $this->assertFalse(NumberHelper::isPositive(0));

        $this->assertTrue(NumberHelper::isNegative(-5));
        $this->assertFalse(NumberHelper::isNegative(5));
        $this->assertFalse(NumberHelper::isNegative(0));

        $this->assertTrue(NumberHelper::isZero(0));
        $this->assertTrue(NumberHelper::isZero(0.0));
        $this->assertFalse(NumberHelper::isZero(1));
    }

    public function test_average(): void {
        $this->assertEquals(5.0, NumberHelper::average([1, 5, 9]));
        $this->assertEquals(0.0, NumberHelper::average([]));
        $this->assertEquals(10.0, NumberHelper::average([10]));
    }

    public function test_median(): void {
        $this->assertEquals(5.0, NumberHelper::median([1, 5, 9]));
        $this->assertEquals(3.0, NumberHelper::median([1, 2, 4, 5]));
        $this->assertEquals(0.0, NumberHelper::median([]));
    }

    public function test_percentile_linear_and_nearest_rank(): void {
        $values = [15, 20, 35, 40, 50];
        $this->assertEquals(35.0, NumberHelper::percentile($values, 50));
        $this->assertEqualsWithDelta(29.0, NumberHelper::percentile([50, 15, 40, 20, 35], 40), 0.0001);
        $this->assertEquals(15.0, NumberHelper::percentile($values, 0));
        $this->assertEquals(50.0, NumberHelper::percentile($values, 100));
        $this->assertEquals(20.0, NumberHelper::percentile($values, 30, PercentileMethod::NearestRank));
        $this->assertEquals(50.0, NumberHelper::percentile($values, 95, PercentileMethod::NearestRank));
        $this->assertEquals(0.0, NumberHelper::percentile([], 50));
    }

    public function test_percentile_rejects_out_of_range(): void {
        $this->expectException(\InvalidArgumentException::class);
        NumberHelper::percentile([1, 2], 101);
    }

    public function test_quartiles(): void {
        $this->assertSame(['min' => 1.0, 'q1' => 2.0, 'median' => 3.0, 'q3' => 4.0, 'max' => 5.0], NumberHelper::quartiles([5, 1, 4, 2, 3]));
        $this->assertNull(NumberHelper::quartiles([]));
    }

    public function test_sign(): void {
        $this->assertEquals(1, NumberHelper::sign(10));
        $this->assertEquals(-1, NumberHelper::sign(-10));
        $this->assertEquals(0, NumberHelper::sign(0));
    }

    public function test_format_with_si_prefix(): void {
        $this->assertEquals('1k', NumberHelper::formatWithSiPrefix(1000, 0));
        $this->assertEquals('1.5M', NumberHelper::formatWithSiPrefix(1500000, 1));
        $this->assertEquals('1Ki', NumberHelper::formatWithSiPrefix(1024, 0, true));
    }

    public function test_factorial(): void {
        $this->assertEquals(1.0, NumberHelper::factorial(0));
        $this->assertEquals(1.0, NumberHelper::factorial(1));
        $this->assertEquals(120.0, NumberHelper::factorial(5));
        $this->assertEquals(3628800.0, NumberHelper::factorial(10));
    }

    public function test_factorial_throws_exception_for_negative(): void {
        $this->expectException(\InvalidArgumentException::class);
        NumberHelper::factorial(-1);
    }

    public function test_is_prime(): void {
        $this->assertFalse(NumberHelper::isPrime(0));
        $this->assertFalse(NumberHelper::isPrime(1));
        $this->assertTrue(NumberHelper::isPrime(2));
        $this->assertTrue(NumberHelper::isPrime(3));
        $this->assertFalse(NumberHelper::isPrime(4));
        $this->assertTrue(NumberHelper::isPrime(5));
        $this->assertTrue(NumberHelper::isPrime(97));
        $this->assertFalse(NumberHelper::isPrime(100));
    }

    public function test_gcd(): void {
        $this->assertEquals(6, NumberHelper::gcd(12, 18));
        $this->assertEquals(1, NumberHelper::gcd(17, 23));
        $this->assertEquals(5, NumberHelper::gcd(15, 25));
    }

    public function test_lcm(): void {
        $this->assertEquals(36, NumberHelper::lcm(12, 18));
        $this->assertEquals(391, NumberHelper::lcm(17, 23));
        $this->assertEquals(75, NumberHelper::lcm(15, 25));
        $this->assertEquals(0, NumberHelper::lcm(0, 5));
    }

    public function test_annuity_payment_standard_zero_rate_and_residual(): void {
        $this->assertSame('299.71', NumberHelper::annuityPayment('10000', '5', 36));
        $this->assertSame('1000.00', NumberHelper::annuityPayment('12000', '0', 12));
        $this->assertSame('500.00', NumberHelper::annuityPayment('16000', '0', 20, 12, '6000'));
        $this->assertSame('2309.75', NumberHelper::annuityPayment('10000', '5', 5, 1));

        $this->expectException(\InvalidArgumentException::class);
        NumberHelper::annuityPayment('1000', '5', 0);
    }

    public function test_amortization_schedule_ends_exactly_on_residual(): void {
        $plan = NumberHelper::amortizationSchedule('10000', '5', 36);
        $this->assertCount(36, $plan);
        $this->assertSame(['period' => 1, 'payment' => '299.71', 'interest' => '41.67', 'principal' => '258.04', 'balance' => '9741.96'], $plan[0]);
        $this->assertSame('0.00', $plan[35]['balance']);
        $this->assertSame('10000.00', array_reduce($plan, static fn (string $sum, array $row): string => bcadd($sum, $row['principal'], 2), '0'));

        $lease = NumberHelper::amortizationSchedule('30000', '3', 36, 12, '10000');
        $this->assertSame('10000.00', $lease[35]['balance']);
        $this->assertSame('20000.00', array_reduce($lease, static fn (string $sum, array $row): string => bcadd($sum, $row['principal'], 2), '0'));
    }
}
