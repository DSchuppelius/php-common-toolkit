<?php
/*
 * Created on   : Sat Jul 25 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DecimalTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\{CountryCode, RoundingMode};
use CommonToolkit\ValueObjects\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class DecimalTest extends BaseTestCase {
    // ==================== Konstruktion / Formate ====================

    public function test_of_string_and_int(): void {
        $this->assertSame('12.34', Decimal::of('12.34')->getValue());
        $this->assertSame('5', Decimal::of(5)->getValue());
        $this->assertSame(0, Decimal::of(5)->getScale());
    }

    public function test_of_accepts_german_and_us_format(): void {
        $this->assertSame('1234.56', Decimal::of('1.234,56')->getValue());
        $this->assertSame('1234.56', Decimal::of('1,234.56')->getValue());
        $this->assertSame('1234.56', Decimal::of('1234,56')->getValue());
    }

    public function test_of_country_specific_thousands_separator(): void {
        // Mit Land Deutschland ist "2.000" eindeutig zweitausend ...
        $german = Decimal::of('2.000', null, RoundingMode::HalfUp, CountryCode::Germany);
        $this->assertSame('2000', $german->getValue());
        $this->assertSame(0, $german->getScale());

        // ... ohne Land bleibt es ein Dezimalwert mit drei Nachkommastellen.
        $plain = Decimal::of('2.000');
        $this->assertSame('2.000', $plain->getValue());
        $this->assertSame(3, $plain->getScale());
    }

    public function test_of_rejects_invalid_input(): void {
        $this->expectException(InvalidArgumentException::class);
        Decimal::of('abc');
    }

    public function test_of_rejects_empty_input(): void {
        $this->expectException(InvalidArgumentException::class);
        Decimal::of('');
    }

    public function test_of_rejects_negative_scale(): void {
        $this->expectException(InvalidArgumentException::class);
        Decimal::of('1', -1);
    }

    public function test_of_nullable(): void {
        $this->assertNull(Decimal::ofNullable(null));
        $this->assertNull(Decimal::ofNullable(''));
        $this->assertNull(Decimal::ofNullable('n/a'));

        $value = Decimal::ofNullable('1,5');
        $this->assertNotNull($value);
        $this->assertSame('1.5', $value->getValue());
        // Echte Null bleibt eine echte Null (kein Fallback-Verhalten).
        $zero = Decimal::ofNullable('0');
        $this->assertNotNull($zero);
        $this->assertTrue($zero->isZero());
    }

    public function test_of_float_infers_minimal_scale(): void {
        $value = Decimal::ofFloat(19.99);
        $this->assertSame('19.99', $value->getValue());
        $this->assertSame(2, $value->getScale());
    }

    public function test_of_float_with_explicit_scale(): void {
        $this->assertSame('1.01', Decimal::ofFloat(1.005, 2)->getValue());
        $this->assertSame('2.00', Decimal::ofFloat(2.0, 2)->getValue());
    }

    public function test_zero_and_one(): void {
        $this->assertSame('0.00', Decimal::zero(2)->getValue());
        $this->assertSame('1', Decimal::one()->getValue());
        $this->assertSame('1.0000', Decimal::one(4)->getValue());
        $this->assertTrue(Decimal::zero()->isZero());
    }

    // ==================== Skalenregeln ====================

    public function test_scale_is_inferred_from_input(): void {
        $this->assertSame(4, Decimal::of('12.3400')->getScale());
        $this->assertSame('12.3400', Decimal::of('12.3400')->getValue());
        $this->assertSame(0, Decimal::of('12')->getScale());
    }

    public function test_explicit_scale_rounds(): void {
        $this->assertSame('12.35', Decimal::of('12.345', 2)->getValue());
        $this->assertSame('12.34', Decimal::of('12.344', 2)->getValue());
    }

    #[DataProvider('roundingModeProvider')]
    public function test_rounding_modes(string $input, int $scale, RoundingMode $mode, string $expected): void {
        $this->assertSame($expected, Decimal::of($input, $scale, $mode)->getValue());
    }

    /**
     * @return array<string, array{string, int, RoundingMode, string}>
     */
    public static function roundingModeProvider(): array {
        return [
            'HalfUp 2.5 -> 3' => ['2.5', 0, RoundingMode::HalfUp, '3'],
            'HalfUp -2.5 -> -3' => ['-2.5', 0, RoundingMode::HalfUp, '-3'],
            'HalfDown 2.5 -> 2' => ['2.5', 0, RoundingMode::HalfDown, '2'],
            'HalfEven 2.5 -> 2' => ['2.5', 0, RoundingMode::HalfEven, '2'],
            'HalfEven 3.5 -> 4' => ['3.5', 0, RoundingMode::HalfEven, '4'],
            'Ceil 2.1 -> 3' => ['2.1', 0, RoundingMode::Ceil, '3'],
            'Ceil -2.9 -> -2' => ['-2.9', 0, RoundingMode::Ceil, '-2'],
            'Floor 2.9 -> 2' => ['2.9', 0, RoundingMode::Floor, '2'],
            'Floor -2.1 -> -3' => ['-2.1', 0, RoundingMode::Floor, '-3'],
            'Truncate 2.9 -> 2' => ['2.9', 0, RoundingMode::Truncate, '2'],
            'Truncate -2.9 -> -2' => ['-2.9', 0, RoundingMode::Truncate, '-2'],
        ];
    }

    public function test_with_scale(): void {
        $value = Decimal::of('12.345');
        $this->assertSame('12.35', $value->withScale(2)->getValue());
        $this->assertSame('12.34', $value->withScale(2, RoundingMode::Truncate)->getValue());
        $this->assertSame('12.34500', $value->withScale(5)->getValue());
        // Gleiche Skala: dieselbe Instanz darf zurückgegeben werden.
        $this->assertSame($value, $value->withScale(3));
    }

    // ==================== Arithmetik / Präzision ====================

    public function test_addition_is_precise(): void {
        // Der klassische float-Fehler: 0.1 + 0.2 !== 0.3
        $result = Decimal::of('0.1')->plus(Decimal::of('0.2'));
        $this->assertSame('0.3', $result->getValue());
        $this->assertTrue($result->equals(Decimal::of('0.3')));
    }

    public function test_plus_minus_use_larger_scale(): void {
        $sum = Decimal::of('1.5')->plus(Decimal::of('0.25'));
        $this->assertSame('1.75', $sum->getValue());
        $this->assertSame(2, $sum->getScale());

        $diff = Decimal::of('1')->minus(Decimal::of('0.001'));
        $this->assertSame('0.999', $diff->getValue());
        $this->assertSame(3, $diff->getScale());
    }

    public function test_times_default_scale_is_sum_of_scales(): void {
        $product = Decimal::of('1.5')->times(Decimal::of('1.25'));
        $this->assertSame('1.875', $product->getValue());
        $this->assertSame(3, $product->getScale());

        // Ohne Zielskala geht keine Stelle verloren.
        $this->assertSame('5.61741', Decimal::of('1.23')->times(Decimal::of('4.567'))->getValue());
    }

    public function test_times_with_explicit_scale_and_mode(): void {
        $a = Decimal::of('1.23');
        $b = Decimal::of('4.567');
        $this->assertSame('5.62', $a->times($b, 2)->getValue());
        $this->assertSame('5.61', $a->times($b, 2, RoundingMode::Truncate)->getValue());
    }

    public function test_divided_by_requires_scale(): void {
        $this->assertSame('3.33', Decimal::of('10')->dividedBy(Decimal::of('3'), 2)->getValue());
        $this->assertSame('3.34', Decimal::of('10')->dividedBy(Decimal::of('3'), 2, RoundingMode::Ceil)->getValue());
        $this->assertSame('1.2', Decimal::of('10')->dividedBy(Decimal::of('8'), 1, RoundingMode::HalfEven)->getValue());
        $this->assertSame('1.3', Decimal::of('10')->dividedBy(Decimal::of('8'), 1)->getValue());
    }

    public function test_division_by_zero_throws(): void {
        $this->expectException(RuntimeException::class);
        Decimal::of('1')->dividedBy(Decimal::zero(), 2);
    }

    public function test_negated_and_abs(): void {
        $this->assertSame('-5.00', Decimal::of('5.00')->negated()->getValue());
        $this->assertSame('5.00', Decimal::of('-5.00')->negated()->getValue());
        $this->assertSame('5.00', Decimal::of('-5.00')->abs()->getValue());
        $this->assertSame('5.00', Decimal::of('5.00')->abs()->getValue());
        $this->assertSame('0.00', Decimal::of('0.00')->negated()->getValue());
    }

    public function test_negative_zero_is_canonicalized(): void {
        $this->assertSame('0.00', Decimal::of('-0.004', 2)->getValue());
        $this->assertSame('0.00', Decimal::of('-0,00')->getValue());
        $this->assertFalse(Decimal::of('-0.004', 2)->isNegative());
    }

    public function test_sign_functions(): void {
        $this->assertTrue(Decimal::of('0.00')->isZero());
        $this->assertTrue(Decimal::of('0.01')->isPositive());
        $this->assertTrue(Decimal::of('-0.01')->isNegative());
        $this->assertFalse(Decimal::of('0.00')->isPositive());
        $this->assertFalse(Decimal::of('0.00')->isNegative());
    }

    // ==================== Vergleich / Gleichheit ====================

    public function test_equality_ignores_scale(): void {
        $this->assertTrue(Decimal::of('1.0')->equals(Decimal::of('1.00')));
        $this->assertTrue(Decimal::of('1')->equals(Decimal::of('1.0000')));
        $this->assertFalse(Decimal::of('1.05')->equals(Decimal::of('1.5')));
    }

    public function test_compare_to_uses_full_precision(): void {
        $this->assertSame(1, Decimal::of('1.5')->compareTo(Decimal::of('1.45')));
        $this->assertSame(-1, Decimal::of('1.45')->compareTo(Decimal::of('1.5')));
        $this->assertSame(0, Decimal::of('1.5')->compareTo(Decimal::of('1.50')));

        $this->assertTrue(Decimal::of('2')->greaterThan(Decimal::of('1.999')));
        $this->assertTrue(Decimal::of('2')->greaterThanOrEqual(Decimal::of('2.0')));
        $this->assertTrue(Decimal::of('1.999')->lessThan(Decimal::of('2')));
        $this->assertTrue(Decimal::of('2.0')->lessThanOrEqual(Decimal::of('2')));
    }

    // ==================== Immutabilität ====================

    public function test_immutability(): void {
        $value = Decimal::of('10.00');
        $value->plus(Decimal::of('5'));
        $value->minus(Decimal::of('5'));
        $value->times(Decimal::of('2'));
        $value->dividedBy(Decimal::of('2'), 2);
        $value->negated();
        $value->abs();
        $value->withScale(4);
        $this->assertSame('10.00', $value->getValue(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== sum / min / max ====================

    public function test_sum_uses_largest_scale_and_no_intermediate_rounding(): void {
        $sum = Decimal::sum([Decimal::of('0.1'), Decimal::of('0.25'), Decimal::of(3)]);
        $this->assertSame('3.35', $sum->getValue());
        $this->assertSame(2, $sum->getScale());

        // Erst akkumulieren, dann runden: 0.005 + 0.005 = 0.01 (nicht 0.00/0.02).
        $this->assertSame('0.01', Decimal::sum([Decimal::of('0.005'), Decimal::of('0.005')], 2)->getValue());
    }

    public function test_sum_of_empty_iterable(): void {
        $this->assertSame('0', Decimal::sum([])->getValue());
        $this->assertSame('0.00', Decimal::sum([], 2)->getValue());
    }

    public function test_min_and_max(): void {
        $this->assertSame('1.45', Decimal::min(Decimal::of('1.5'), Decimal::of('1.45'), Decimal::of('2'))->getValue());
        $this->assertSame('2', Decimal::max(Decimal::of('1.5'), Decimal::of('1.45'), Decimal::of('2'))->getValue());
        $this->assertSame('-2', Decimal::min(Decimal::of('-2'), Decimal::of('0'))->getValue());
    }

    // ==================== Formatierung / Serialisierung ====================

    public function test_format_without_float_intermediate(): void {
        $this->assertSame('1.234.567,891', Decimal::of('1234567.891')->format());
        $this->assertSame('-1.234,5', Decimal::of('-1234.5')->format());
        $this->assertSame('1234,5', Decimal::of('1234.5')->format(',', ''));
        $this->assertSame('1.234', Decimal::of('1234')->format());
        // Jenseits der float-Präzision: Gruppierung bleibt exakt.
        $this->assertSame('12.345.678.901.234.567.890,12', Decimal::of('12345678901234567890.12')->format());
    }

    public function test_to_string_returns_canonical_value(): void {
        $this->assertSame('12.34', (string) Decimal::of('12.34'));
        $this->assertSame('-0.5', (string) Decimal::of('-0.5'));
    }

    public function test_to_float(): void {
        $this->assertSame(12.5, Decimal::of('12.5')->toFloat());
    }

    public function test_json_roundtrip(): void {
        $value = Decimal::of('12.34', 4);
        $this->assertSame('{"value":"12.3400","scale":4}', json_encode($value));

        $restored = Decimal::fromArray($value->jsonSerialize());
        $this->assertSame('12.3400', $restored->getValue());
        $this->assertSame(4, $restored->getScale());
        $this->assertTrue($restored->equals($value));
    }

    public function test_from_array_without_scale_infers(): void {
        $restored = Decimal::fromArray(['value' => '7.250']);
        $this->assertSame('7.250', $restored->getValue());
        $this->assertSame(3, $restored->getScale());
    }

    public function test_from_array_with_float_value(): void {
        $this->assertSame('19.99', Decimal::fromArray(['value' => 19.99])->getValue());
    }

    public function test_from_array_without_value_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        Decimal::fromArray(['scale' => 2]);
    }

    // ==================== Große Werte ====================

    public function test_values_beyond_integer_and_float_range(): void {
        $big = Decimal::of('123456789012345678901234567890.55');
        $this->assertSame('123456789012345678901234567890.55', $big->getValue());
        $this->assertSame('123456789012345678901234567891.00', $big->plus(Decimal::of('0.45'))->getValue());
        $this->assertSame('246913578024691357802469135781.10', $big->times(Decimal::of('2'), 2)->getValue());
    }
}
