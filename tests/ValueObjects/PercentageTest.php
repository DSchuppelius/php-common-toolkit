<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PercentageTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\{CountryCode, CurrencyCode, RoundingMode};
use CommonToolkit\ValueObjects\{Decimal, Money, Percentage};
use InvalidArgumentException;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class PercentageTest extends BaseTestCase {
    // ==================== Konstruktion ====================

    public function test_of_accepts_string_and_int(): void {
        $this->assertSame('19', Percentage::of(19)->getNumericValue());
        $this->assertSame('19.00', Percentage::of('19,00')->getNumericValue());
        $this->assertSame('7.5', Percentage::of('7.5')->getNumericValue());
    }

    public function test_of_accepts_german_format_with_country(): void {
        $this->assertSame('1000', Percentage::of('1.000', null, RoundingMode::HalfUp, CountryCode::Germany)->getNumericValue());
    }

    public function test_of_allows_negative_and_above_hundred(): void {
        $this->assertSame('-3.5', Percentage::of('-3,5')->getNumericValue());
        $this->assertSame('150', Percentage::of(150)->getNumericValue());
    }

    public function test_of_rejects_invalid_input(): void {
        $this->expectException(InvalidArgumentException::class);
        Percentage::of('abc');
    }

    public function test_try_from(): void {
        $this->assertNull(Percentage::tryFrom(null));
        $this->assertNull(Percentage::tryFrom(''));
        $this->assertNull(Percentage::tryFrom('ungültig'));

        $value = Percentage::tryFrom('19');
        $this->assertNotNull($value);
        $this->assertSame('19', $value->getNumericValue());
    }

    public function test_between_zero_and_hundred_accepts_bounds(): void {
        $this->assertSame('0', Percentage::betweenZeroAndHundred(0)->getNumericValue());
        $this->assertSame('100', Percentage::betweenZeroAndHundred(100)->getNumericValue());
        $this->assertSame('19.5', Percentage::betweenZeroAndHundred('19,5')->getNumericValue());
    }

    public function test_between_zero_and_hundred_rejects_negative(): void {
        $this->expectException(InvalidArgumentException::class);
        Percentage::betweenZeroAndHundred('-0.01');
    }

    public function test_between_zero_and_hundred_rejects_above_hundred(): void {
        $this->expectException(InvalidArgumentException::class);
        Percentage::betweenZeroAndHundred('100.01');
    }

    public function test_from_ratio_rounds_only_at_target_scale(): void {
        $ratio = Percentage::fromRatio(Decimal::of('1'), Decimal::of('3'));
        $this->assertSame('33.3333', $ratio->getNumericValue());

        $this->assertSame('66.67', Percentage::fromRatio(Decimal::of('2'), Decimal::of('3'), 2)->getNumericValue());
    }

    public function test_from_ratio_rejects_division_by_zero(): void {
        $this->expectException(RuntimeException::class);
        Percentage::fromRatio(Decimal::of('1'), Decimal::zero());
    }

    // ==================== Geld-Anbindung ====================

    public function test_amount_of_money(): void {
        // 19 % von 8,15 EUR = 1,5485 -> kaufmännisch 1,55 EUR
        $tax = Percentage::of(19)->amountOf(Money::of('8.15', CurrencyCode::Euro));
        $this->assertSame('1.55', $tax->getAmount());
        $this->assertSame(CurrencyCode::Euro, $tax->getCurrency());
    }

    public function test_add_to_and_subtract_from_preserve_currency(): void {
        $net = Money::of('100.00', CurrencyCode::USDollar);

        $gross = Percentage::of(19)->addTo($net);
        $this->assertSame('119.00', $gross->getAmount());
        $this->assertSame(CurrencyCode::USDollar, $gross->getCurrency());

        $discounted = Percentage::of('3')->subtractFrom($net);
        $this->assertSame('97.00', $discounted->getAmount());
        $this->assertSame(CurrencyCode::USDollar, $discounted->getCurrency());
    }

    public function test_as_factor(): void {
        $this->assertSame('0.19000000', Percentage::of(19)->asFactor()->getValue());
        $this->assertSame('0.07', Percentage::of(7)->asFactor(2)->getValue());
    }

    // ==================== Arithmetik / Vergleich ====================

    public function test_plus_and_minus(): void {
        $this->assertSame('26.0', Percentage::of('19,0')->plus(Percentage::of(7))->getNumericValue());
        $this->assertSame('12', Percentage::of(19)->minus(Percentage::of(7))->getNumericValue());
    }

    public function test_compare_and_equals(): void {
        $this->assertTrue(Percentage::of('19.0')->equals(Percentage::of('19.00')));
        $this->assertFalse(Percentage::of('19')->equals(Percentage::of('7')));
        $this->assertSame(1, Percentage::of('19')->compareTo(Percentage::of('7')));
        $this->assertSame(-1, Percentage::of('7')->compareTo(Percentage::of('19')));
    }

    public function test_sign_and_range_checks(): void {
        $this->assertTrue(Percentage::of(0)->isZero());
        $this->assertTrue(Percentage::of(19)->isPositive());
        $this->assertTrue(Percentage::of(-3)->isNegative());
        $this->assertTrue(Percentage::of(100)->isWithinZeroAndHundred());
        $this->assertTrue(Percentage::of(0)->isWithinZeroAndHundred());
        $this->assertFalse(Percentage::of(-1)->isWithinZeroAndHundred());
        $this->assertFalse(Percentage::of('100.01')->isWithinZeroAndHundred());
    }

    public function test_immutability(): void {
        $value = Percentage::of('19.00');
        $value->plus(Percentage::of(7));
        $value->minus(Percentage::of(7));
        $this->assertSame('19.00', $value->getNumericValue(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== Formatierung / Serialisierung ====================

    public function test_get_value_returns_decimal(): void {
        $decimal = Percentage::of('19.5')->getValue();
        $this->assertSame('19.5', $decimal->getValue());
        $this->assertSame(1, $decimal->getScale());
    }

    public function test_format_and_to_string(): void {
        $this->assertSame('19,5 %', Percentage::of('19.5')->format());
        $this->assertSame('19.5 %', Percentage::of('19.5')->format('.'));
        $this->assertSame('19.5 %', (string) Percentage::of('19.5'));
    }

    public function test_json_roundtrip(): void {
        $value = Percentage::of('19.00');
        $this->assertSame('{"value":"19.00","scale":2}', json_encode($value));

        $data = $value->jsonSerialize();
        $restored = Percentage::of($data['value'], $data['scale']);
        $this->assertTrue($restored->equals($value));
    }
}
