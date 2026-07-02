<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelperPreciseTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Enums\RoundingMode;
use CommonToolkit\Helper\Data\NumberHelper;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class NumberHelperPreciseTest extends BaseTestCase {
    // ---------------------------------------------------------------- roundPrecise

    public function test_round_precise_half_up(): void {
        $this->assertEquals('2.35', NumberHelper::roundPrecise('2.345', 2));
        $this->assertEquals('2.34', NumberHelper::roundPrecise('2.344', 2));
        $this->assertEquals('2.36', NumberHelper::roundPrecise('2.355', 2));
        $this->assertEquals('3', NumberHelper::roundPrecise('2.5', 0));
        // Away from zero bei negativen Werten
        $this->assertEquals('-2.35', NumberHelper::roundPrecise('-2.345', 2));
        $this->assertEquals('-3', NumberHelper::roundPrecise('-2.5', 0));
    }

    public function test_round_precise_does_not_lose_precision_beyond_half(): void {
        // 2.3456 liegt über der Hälfte → auch HalfDown rundet auf
        $this->assertEquals('2.35', NumberHelper::roundPrecise('2.3456', 2, RoundingMode::HalfDown));
        // exakt die Hälfte → HalfDown Richtung Null
        $this->assertEquals('2.34', NumberHelper::roundPrecise('2.345', 2, RoundingMode::HalfDown));
    }

    public function test_round_precise_half_even(): void {
        $this->assertEquals('2.34', NumberHelper::roundPrecise('2.345', 2, RoundingMode::HalfEven)); // 4 gerade → bleibt
        $this->assertEquals('2.36', NumberHelper::roundPrecise('2.355', 2, RoundingMode::HalfEven)); // 5 ungerade → auf
        $this->assertEquals('2', NumberHelper::roundPrecise('2.5', 0, RoundingMode::HalfEven));
        $this->assertEquals('4', NumberHelper::roundPrecise('3.5', 0, RoundingMode::HalfEven));
        // Über der Hälfte immer auf, unabhängig von Parität
        $this->assertEquals('2.35', NumberHelper::roundPrecise('2.3451', 2, RoundingMode::HalfEven));
    }

    public function test_round_precise_floor_ceil_truncate(): void {
        $this->assertEquals('2.34', NumberHelper::roundPrecise('2.349', 2, RoundingMode::Floor));
        $this->assertEquals('2.35', NumberHelper::roundPrecise('2.341', 2, RoundingMode::Ceil));
        $this->assertEquals('2.34', NumberHelper::roundPrecise('2.349', 2, RoundingMode::Truncate));
        // Floor Richtung -∞, Ceil Richtung Null bei negativen Werten
        $this->assertEquals('-2.35', NumberHelper::roundPrecise('-2.341', 2, RoundingMode::Floor));
        $this->assertEquals('-2.34', NumberHelper::roundPrecise('-2.349', 2, RoundingMode::Ceil));
        $this->assertEquals('-2.34', NumberHelper::roundPrecise('-2.349', 2, RoundingMode::Truncate));
    }

    public function test_round_precise_no_negative_zero(): void {
        $this->assertEquals('0.00', NumberHelper::roundPrecise('-0.001', 2));
        $this->assertEquals('0', NumberHelper::roundPrecise('-0.4', 0));
    }

    public function test_round_precise_pads_to_scale(): void {
        $this->assertEquals('5.00', NumberHelper::roundPrecise('5', 2));
        $this->assertEquals('5.10', NumberHelper::roundPrecise('5.1', 2));
    }

    public function test_round_precise_negative_scale_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        NumberHelper::roundPrecise('1.23', -1);
    }

    // ---------------------------------------------------------- abs / negate / sign

    public function test_abs_precise(): void {
        $this->assertEquals('2.345', NumberHelper::absPrecise('-2.345'));
        $this->assertEquals('2.345', NumberHelper::absPrecise('2.345'));
        $this->assertEquals('0', NumberHelper::absPrecise('0'));
        $this->assertEquals('0.00', NumberHelper::absPrecise('-0.00'));
    }

    public function test_negate_precise(): void {
        $this->assertEquals('-2.50', NumberHelper::negatePrecise('2.50'));
        $this->assertEquals('2.50', NumberHelper::negatePrecise('-2.50'));
        $this->assertEquals('0', NumberHelper::negatePrecise('0'));
        $this->assertEquals('0.00', NumberHelper::negatePrecise('0.00'));
        $this->assertEquals('0.00', NumberHelper::negatePrecise('-0.00'));
    }

    public function test_sign_precise(): void {
        $this->assertSame(1, NumberHelper::signPrecise('0.01'));
        $this->assertSame(-1, NumberHelper::signPrecise('-0.01'));
        $this->assertSame(0, NumberHelper::signPrecise('0.000'));
    }

    public function test_sign_predicates(): void {
        $this->assertTrue(NumberHelper::isPositivePrecise('0.001'));
        $this->assertFalse(NumberHelper::isPositivePrecise('0.001', 2)); // bei Skala 2 gerundet null
        $this->assertTrue(NumberHelper::isZeroPrecise('0.001', 2));
        $this->assertFalse(NumberHelper::isZeroPrecise('0.001'));
        $this->assertTrue(NumberHelper::isNegativePrecise('-0.5'));
        $this->assertFalse(NumberHelper::isNegativePrecise('0.5'));
    }

    // ------------------------------------------------------------ min / max / clamp

    public function test_min_max_precise(): void {
        $this->assertEquals('1.5', NumberHelper::minPrecise('1.5', '1.50001'));
        $this->assertEquals('1.50001', NumberHelper::maxPrecise('1.5', '1.50001'));
        // Bei Skala 2 sind sie gleich → a gewinnt
        $this->assertEquals('1.5', NumberHelper::minPrecise('1.5', '1.50001', 2));
    }

    public function test_clamp_precise(): void {
        $this->assertEquals('5', NumberHelper::clampPrecise('10', '0', '5'));
        $this->assertEquals('0', NumberHelper::clampPrecise('-3', '0', '5'));
        $this->assertEquals('3.2', NumberHelper::clampPrecise('3.2', '0', '5'));
    }

    // -------------------------------------------------------------- Prozent & Step

    public function test_percentage_precise(): void {
        $this->assertEquals('25.00', NumberHelper::percentagePrecise('50', '200'));
        $this->assertEquals('33.33', NumberHelper::percentagePrecise('1', '3'));
        $this->assertEquals('0.00', NumberHelper::percentagePrecise('5', '0')); // skaliert auf Zielskala
    }

    public function test_percent_of_precise(): void {
        $this->assertEquals('19.00', NumberHelper::percentOfPrecise('100', '19'));
        $this->assertEquals('23.94', NumberHelper::percentOfPrecise('126', '19')); // 23.94
        $this->assertEquals('7.00', NumberHelper::percentOfPrecise('100', '7'));
    }

    public function test_round_to_step_precise(): void {
        $this->assertEquals('2.35', NumberHelper::roundToStepPrecise('2.34', '0.05'));
        $this->assertEquals('2.35', NumberHelper::roundToStepPrecise('2.37', '0.05'));
        // 12/25 = 0.48 → HalfUp → 0 → 0·25
        $this->assertEquals('0.00', NumberHelper::roundToStepPrecise('12', '25', 2));
        // 13/25 = 0.52 → HalfUp → 1 → 25
        $this->assertEquals('25.00', NumberHelper::roundToStepPrecise('13', '25', 2));
    }

    public function test_round_to_step_precise_invalid_step(): void {
        $this->expectException(InvalidArgumentException::class);
        NumberHelper::roundToStepPrecise('1.0', '0');
    }

    // ------------------------------------------------------------------- allocate

    public function test_allocate_is_cent_safe(): void {
        // 100 auf 3 gleiche Teile → 33.34 / 33.33 / 33.33, Summe exakt 100.00
        $parts = NumberHelper::allocateEvenly('100', 3, 2);
        $this->assertEquals(['33.34', '33.33', '33.33'], $parts);
        $this->assertEquals('100.00', NumberHelper::sumPrecise($parts, 2));
    }

    public function test_allocate_by_weights_preserves_keys(): void {
        $result = NumberHelper::allocate('10.00', ['a' => 1, 'b' => 1, 'c' => 1], 2);
        $this->assertSame(['a', 'b', 'c'], array_keys($result));
        $this->assertEquals('10.00', NumberHelper::sumPrecise(array_values($result), 2));
    }

    public function test_allocate_proportional(): void {
        // 100 im Verhältnis 1:3 → 25 / 75
        $this->assertEquals(['25.00', '75.00'], NumberHelper::allocate('100', [1, 3], 2));
    }

    public function test_allocate_negative_total(): void {
        $result = NumberHelper::allocateEvenly('-100', 3, 2);
        $this->assertEquals(['-33.34', '-33.33', '-33.33'], $result);
        $this->assertEquals('-100.00', NumberHelper::sumPrecise($result, 2));
    }

    public function test_allocate_zero_weights_falls_back_to_even(): void {
        $result = NumberHelper::allocate('9.00', ['x' => 0, 'y' => 0, 'z' => 0], 2);
        $this->assertEquals('9.00', NumberHelper::sumPrecise(array_values($result), 2));
        $this->assertEquals(['3.00', '3.00', '3.00'], array_values($result));
    }

    public function test_allocate_empty(): void {
        $this->assertSame([], NumberHelper::allocate('10', [], 2));
    }

    public function test_allocate_evenly_invalid_parts(): void {
        $this->expectException(InvalidArgumentException::class);
        NumberHelper::allocateEvenly('10', 0, 2);
    }

    // ---------------------------------------------------- Arithmetik mit Rundung

    public function test_arithmetic_defaults_to_truncation_bc(): void {
        // Standard = Truncate = unverändertes bcmath-Verhalten
        $this->assertEquals('0.66', NumberHelper::dividePrecise('2', '3', 2));
        $this->assertEquals('2.00', NumberHelper::multiplyPrecise('1.416', '1.416', 2)); // 2.005056 → trunk
        $this->assertEquals('3.01', NumberHelper::addPrecise('1.008', '2.007', 2));       // 3.015 → trunk
    }

    public function test_divide_precise_rounds(): void {
        $this->assertEquals('0.67', NumberHelper::dividePrecise('2', '3', 2, RoundingMode::HalfUp));
        $this->assertEquals('0.33', NumberHelper::dividePrecise('1', '3', 2, RoundingMode::HalfUp));
        $this->assertEquals('-0.67', NumberHelper::dividePrecise('-2', '3', 2, RoundingMode::HalfUp));
    }

    public function test_multiply_precise_rounds_exactly(): void {
        // 1.416 · 1.416 = 2.005056 → Truncate 2.00, HalfUp 2.01
        $this->assertEquals('2.01', NumberHelper::multiplyPrecise('1.416', '1.416', 2, RoundingMode::HalfUp));
        // 0.005 · 0.005 = 0.000025 → 0.00
        $this->assertEquals('0.00', NumberHelper::multiplyPrecise('0.005', '0.005', 2, RoundingMode::HalfUp));
    }

    public function test_pow_sqrt_precise_round(): void {
        // 1.5^3 = 3.375 → Truncate 3.37, HalfUp 3.38
        $this->assertEquals('3.37', NumberHelper::powPrecise('1.5', '3', 2));
        $this->assertEquals('3.38', NumberHelper::powPrecise('1.5', '3', 2, RoundingMode::HalfUp));

        // sqrt(3) = 1.73205080… → Truncate 1.7320, HalfUp 1.7321
        $this->assertEquals('1.7320', NumberHelper::sqrtPrecise('3', 4));
        $this->assertEquals('1.7321', NumberHelper::sqrtPrecise('3', 4, RoundingMode::HalfUp));
    }

    public function test_add_subtract_precise_round(): void {
        $this->assertEquals('3.02', NumberHelper::addPrecise('1.008', '2.007', 2, RoundingMode::HalfUp));      // 3.015 → 3.02
        $this->assertEquals('1.00', NumberHelper::subtractPrecise('2.000', '1.005', 2, RoundingMode::HalfUp)); // 0.995 → 1.00
    }

    public function test_sum_precise_rounds_once_no_intermediate_truncation(): void {
        // Truncate würde jeden Summanden auf 2 kürzen → 0.00+0.00+0.00 = 0.00
        $this->assertEquals('0.00', NumberHelper::sumPrecise(['0.004', '0.004', '0.004'], 2));
        // HalfUp akkumuliert exakt (0.012) → 0.01
        $this->assertEquals('0.01', NumberHelper::sumPrecise(['0.004', '0.004', '0.004'], 2, RoundingMode::HalfUp));
    }

    public function test_divide_precise_zero_check_uses_full_precision(): void {
        // "0.4" bei $scale=0 darf NICHT als Division durch Null gelten
        $this->assertEquals('5', NumberHelper::dividePrecise('2', '0.4', 0, RoundingMode::HalfUp));
    }

    public function test_divide_precise_zero_divisor_throws(): void {
        $this->expectException(\RuntimeException::class);
        NumberHelper::dividePrecise('1', '0', 2);
    }

    public function test_mod_precise(): void {
        $this->assertEquals('1', NumberHelper::modPrecise('7', '3'));
        $this->assertEquals('1.5', NumberHelper::modPrecise('7.5', '2', 1));
    }

    public function test_mod_precise_zero_divisor_throws(): void {
        $this->expectException(\RuntimeException::class);
        NumberHelper::modPrecise('7', '0', 2);
    }

    // --------------------------------------------------------------- Aggregation

    public function test_average_precise(): void {
        $this->assertEquals('2.00', NumberHelper::averagePrecise(['1', '2', '3']));
        $this->assertEquals('2.50', NumberHelper::averagePrecise(['1', '2', '3', '4']));
        $this->assertEquals('0.00', NumberHelper::averagePrecise([]));
        $this->assertEquals('0.33', NumberHelper::averagePrecise(['0', '0', '1']));
    }

    public function test_median_precise(): void {
        $this->assertEquals('3.00', NumberHelper::medianPrecise(['5', '1', '3']));
        $this->assertEquals('2.50', NumberHelper::medianPrecise(['1', '2', '3', '4']));
        $this->assertEquals('0.00', NumberHelper::medianPrecise([]));
    }

    public function test_min_max_of_precise(): void {
        $this->assertEquals('-2.5', NumberHelper::minOfPrecise(['1.2', '-2.5', '3.7']));
        $this->assertEquals('3.7', NumberHelper::maxOfPrecise(['1.2', '-2.5', '3.7']));
        $this->assertNull(NumberHelper::minOfPrecise([]));
        $this->assertNull(NumberHelper::maxOfPrecise([]));
    }
}
