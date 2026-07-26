<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : QuantityTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\{Decimal, Quantity};
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

enum QuantityTestStringUnit: string {
    case Piece = 'Stk';
    case Hour = 'h';
}

enum QuantityTestIntUnit: int {
    case Piece = 1;
}

class QuantityTest extends BaseTestCase {
    // ==================== Konstruktion / Einheiten ====================

    public function test_of_with_string_unit(): void {
        $quantity = Quantity::of('2,5', 'Stk');
        $this->assertSame('2.5', $quantity->getNumericValue());
        $this->assertSame('Stk', $quantity->getUnit());
    }

    public function test_of_with_string_backed_enum_unit(): void {
        $quantity = Quantity::of(3, QuantityTestStringUnit::Piece);
        $this->assertSame('Stk', $quantity->getUnit());
    }

    public function test_of_with_decimal_value(): void {
        $quantity = Quantity::of(Decimal::of('1.2345'), 'kg');
        $this->assertSame('1.2345', $quantity->getNumericValue());

        $scaled = Quantity::of(Decimal::of('1.2345'), 'kg', 2);
        $this->assertSame('1.23', $scaled->getNumericValue());
    }

    public function test_of_rejects_int_backed_enum_unit(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, QuantityTestIntUnit::Piece);
    }

    public function test_unit_is_trimmed_and_case_preserved(): void {
        $this->assertSame('mAh', Quantity::of(1, '  mAh ')->getUnit());
    }

    public function test_empty_unit_is_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, '   ');
    }

    public function test_control_characters_in_unit_are_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, "St\x01k");
    }

    public function test_of_rejects_invalid_value(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of('abc', 'Stk');
    }

    public function test_try_from(): void {
        $this->assertNull(Quantity::tryFrom(null, 'Stk'));
        $this->assertNull(Quantity::tryFrom('', 'Stk'));
        $this->assertNull(Quantity::tryFrom('ungültig', 'Stk'));

        $quantity = Quantity::tryFrom('4,25', 'h');
        $this->assertNotNull($quantity);
        $this->assertSame('4.25', $quantity->getNumericValue());

        $fromDecimal = Quantity::tryFrom(Decimal::of('2'), 'h');
        $this->assertNotNull($fromDecimal);
        $this->assertSame('2', $fromDecimal->getNumericValue());
    }

    public function test_zero(): void {
        $zero = Quantity::zero('Stk', 2);
        $this->assertSame('0.00', $zero->getNumericValue());
        $this->assertSame('Stk', $zero->getUnit());
        $this->assertTrue($zero->isZero());
    }

    public function test_positive_requires_strictly_positive(): void {
        $this->assertSame('0.5', Quantity::positive('0,5', 'h')->getNumericValue());

        $this->expectException(InvalidArgumentException::class);
        Quantity::positive(0, 'h');
    }

    public function test_positive_rejects_negative(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::positive('-1', 'h');
    }

    public function test_zero_or_positive_allows_zero_but_not_negative(): void {
        $this->assertTrue(Quantity::zeroOrPositive(0, 'Stk')->isZero());

        $this->expectException(InvalidArgumentException::class);
        Quantity::zeroOrPositive('-0.01', 'Stk');
    }

    public function test_negative_quantities_allowed_via_of(): void {
        // Lagerabgang: negative Bewegungsmenge ist fachlich zulässig.
        $movement = Quantity::of('-3.5', 'Stk');
        $this->assertTrue($movement->isNegative());
        $this->assertSame('-3.5', $movement->getNumericValue());
    }

    // ==================== Arithmetik ====================

    public function test_exact_arithmetic_with_four_and_more_decimals(): void {
        $sum = Quantity::of('0.0001', 'kg')->plus(Quantity::of('0.0002', 'kg'));
        $this->assertSame('0.0003', $sum->getNumericValue());

        $diff = Quantity::of('1.00005', 'kg')->minus(Quantity::of('0.00005', 'kg'));
        $this->assertSame('1.00000', $diff->getNumericValue());
    }

    public function test_times_and_divided_by_decimal(): void {
        $this->assertSame('7.5', Quantity::of('2.5', 'h')->times(Decimal::of(3))->getNumericValue());
        $this->assertSame('1.25', Quantity::of('2.5', 'h')->dividedBy(Decimal::of(2), 2)->getNumericValue());
        $this->assertSame('h', Quantity::of('2.5', 'h')->times(Decimal::of(3))->getUnit());
    }

    public function test_abs_negated_with_scale(): void {
        $this->assertSame('3.5', Quantity::of('-3.5', 'Stk')->abs()->getNumericValue());
        $this->assertSame('-3.5', Quantity::of('3.5', 'Stk')->negated()->getNumericValue());
        $this->assertSame('3.50', Quantity::of('3.5', 'Stk')->withScale(2)->getNumericValue());
    }

    public function test_incompatible_units_in_plus_throw(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, 'Stk')->plus(Quantity::of(1, 'h'));
    }

    public function test_incompatible_units_in_minus_throw(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, 'Stk')->minus(Quantity::of(1, 'h'));
    }

    public function test_incompatible_units_in_compare_throw(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, 'Stk')->compareTo(Quantity::of(1, 'h'));
    }

    public function test_units_are_case_sensitive(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::of(1, 'STK')->plus(Quantity::of(1, 'Stk'));
    }

    public function test_immutability(): void {
        $quantity = Quantity::of('2.50', 'h');
        $quantity->plus(Quantity::of(1, 'h'));
        $quantity->times(Decimal::of(2));
        $quantity->negated();
        $this->assertSame('2.50', $quantity->getNumericValue(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== sum ====================

    public function test_sum_preserves_total_and_infers_unit(): void {
        $sum = Quantity::sum([
            Quantity::of('0.5', 'h'),
            Quantity::of('0.25', 'h'),
            Quantity::of(2, 'h'),
        ]);
        $this->assertSame('2.75', $sum->getNumericValue());
        $this->assertSame('h', $sum->getUnit());
    }

    public function test_sum_with_explicit_unit_and_empty_list(): void {
        $sum = Quantity::sum([], 'Stk', 2);
        $this->assertSame('0.00', $sum->getNumericValue());
        $this->assertSame('Stk', $sum->getUnit());
    }

    public function test_sum_of_empty_list_without_unit_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::sum([]);
    }

    public function test_sum_with_mixed_units_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        Quantity::sum([Quantity::of(1, 'h'), Quantity::of(1, 'Stk')]);
    }

    // ==================== Vergleich ====================

    public function test_equals_and_is_same_unit(): void {
        $this->assertTrue(Quantity::of('1.0', 'h')->equals(Quantity::of('1.00', 'h')));
        $this->assertFalse(Quantity::of('1', 'h')->equals(Quantity::of('1', 'Stk')));
        $this->assertTrue(Quantity::of(1, 'h')->isSameUnit(Quantity::of(9, 'h')));
        $this->assertFalse(Quantity::of(1, 'h')->isSameUnit(Quantity::of(1, 'Stk')));
        $this->assertSame(1, Quantity::of('1.5', 'h')->compareTo(Quantity::of('1.45', 'h')));
    }

    public function test_sign_functions(): void {
        $this->assertTrue(Quantity::of(0, 'Stk')->isZero());
        $this->assertTrue(Quantity::of(1, 'Stk')->isPositive());
        $this->assertTrue(Quantity::of(-1, 'Stk')->isNegative());
    }

    // ==================== Formatierung / Serialisierung ====================

    public function test_format_and_to_string(): void {
        $this->assertSame('1.234,5 kg', Quantity::of('1234.5', 'kg')->format());
        $this->assertSame('1234,5 kg', Quantity::of('1234.5', 'kg')->format(',', ''));
        $this->assertSame('1234.5 kg', (string) Quantity::of('1234.5', 'kg'));
    }

    public function test_json_roundtrip(): void {
        $quantity = Quantity::of('2.50', 'h');
        $this->assertSame('{"value":"2.50","scale":2,"unit":"h"}', json_encode($quantity));

        $data = $quantity->jsonSerialize();
        $restored = Quantity::of($data['value'], $data['unit'], $data['scale']);
        $this->assertTrue($restored->equals($quantity));
    }
}
