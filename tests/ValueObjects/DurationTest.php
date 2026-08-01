<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DurationTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\{DateTimeRange, Duration};
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class DurationTest extends BaseTestCase {
    // ==================== Konstruktion ====================

    public function test_factories(): void {
        $this->assertSame(0, Duration::zero()->getTotalSeconds());
        $this->assertSame(90, Duration::ofSeconds(90)->getTotalSeconds());
        $this->assertSame(-90, Duration::ofSeconds(-90)->getTotalSeconds());
        $this->assertSame(300, Duration::ofMinutes(5)->getTotalSeconds());
        $this->assertSame(7200, Duration::ofHours(2)->getTotalSeconds());
        $this->assertSame(30600, Duration::of(8, 30)->getTotalSeconds());
        $this->assertSame(30615, Duration::of(8, 30, 15)->getTotalSeconds());
    }

    public function test_of_rejects_negative_components(): void {
        $this->expectException(InvalidArgumentException::class);
        Duration::of(8, -30);
    }

    // ==================== ISO 8601 ====================

    public function test_from_iso8601(): void {
        $this->assertSame(30600, Duration::fromIso8601('PT8H30M')->getTotalSeconds());
        $this->assertSame(93600, Duration::fromIso8601('P1DT2H')->getTotalSeconds());
        $this->assertSame(0, Duration::fromIso8601('PT0S')->getTotalSeconds());
        $this->assertSame(-900, Duration::fromIso8601('-PT15M')->getTotalSeconds());
    }

    public function test_from_iso8601_rejects_years(): void {
        $this->expectException(InvalidArgumentException::class);
        Duration::fromIso8601('P1Y');
    }

    public function test_from_iso8601_rejects_months(): void {
        // Monate haben keine feste Sekundenlänge.
        $this->expectException(InvalidArgumentException::class);
        Duration::fromIso8601('P1M');
    }

    public function test_from_iso8601_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        Duration::fromIso8601('acht Stunden');
    }

    public function test_iso8601_roundtrip(): void {
        foreach ([Duration::of(8, 30), Duration::ofSeconds(-900), Duration::zero(), Duration::ofSeconds(465900)] as $duration) {
            $this->assertTrue(Duration::fromIso8601($duration->toIso8601())->equals($duration), $duration->toIso8601());
        }
    }

    public function test_to_iso8601(): void {
        $this->assertSame('PT8H30M', Duration::of(8, 30)->toIso8601());
        $this->assertSame('-PT15M', Duration::ofSeconds(-900)->toIso8601());
        $this->assertSame('PT0S', Duration::zero()->toIso8601());
        $this->assertSame('PT129H25M30S', Duration::ofSeconds(465930)->toIso8601());
    }

    // ==================== between / DateTimeRange ====================

    public function test_between(): void {
        $duration = Duration::between(new DateTimeImmutable('2026-07-01 08:00:00'), new DateTimeImmutable('2026-07-01 09:30:00'));
        $this->assertSame(5400, $duration->getTotalSeconds());
    }

    public function test_between_can_be_negative(): void {
        $duration = Duration::between(new DateTimeImmutable('2026-07-01 09:30:00'), new DateTimeImmutable('2026-07-01 08:00:00'));
        $this->assertSame(-5400, $duration->getTotalSeconds());
    }

    public function test_between_over_dst_change_is_real_elapsed_time(): void {
        // DST-Beginn Europe/Berlin am 29.03.2026: der Kalendertag hat real 23 Stunden.
        $berlin = new DateTimeZone('Europe/Berlin');
        $duration = Duration::between(
            new DateTimeImmutable('2026-03-29 00:00:00', $berlin),
            new DateTimeImmutable('2026-03-30 00:00:00', $berlin)
        );
        $this->assertSame(23 * 3600, $duration->getTotalSeconds());
    }

    public function test_date_time_range_duration(): void {
        $range = DateTimeRange::between(new DateTimeImmutable('2026-07-01 08:00:00'), new DateTimeImmutable('2026-07-01 12:00:00'));
        $this->assertTrue($range->duration()->equals(Duration::ofHours(4)));
    }

    // ==================== Arithmetik ====================

    public function test_arithmetic(): void {
        $this->assertSame(5400, Duration::ofHours(1)->plus(Duration::ofMinutes(30))->getTotalSeconds());
        $this->assertSame(1800, Duration::ofHours(1)->minus(Duration::ofMinutes(30))->getTotalSeconds());
        $this->assertSame(-1800, Duration::ofMinutes(30)->minus(Duration::ofHours(1))->getTotalSeconds());
        $this->assertSame(9000, Duration::ofMinutes(30)->times(5)->getTotalSeconds());
        $this->assertSame(-300, Duration::ofMinutes(5)->negated()->getTotalSeconds());
        $this->assertSame(300, Duration::ofMinutes(-5)->abs()->getTotalSeconds());
    }

    public function test_sum(): void {
        $sum = Duration::sum([Duration::of(0, 50), Duration::of(0, 25), Duration::ofSeconds(-300)]);
        $this->assertSame(4200, $sum->getTotalSeconds()); // 50m + 25m - 5m = 70m
        $this->assertTrue(Duration::sum([])->isZero());
    }

    public function test_immutability(): void {
        $duration = Duration::ofMinutes(30);
        $duration->plus(Duration::ofMinutes(30));
        $duration->negated();
        $this->assertSame(1800, $duration->getTotalSeconds(), 'Ursprung darf sich nicht ändern.');
    }

    // ==================== Vergleich ====================

    public function test_compare_and_sign(): void {
        $this->assertTrue(Duration::ofMinutes(90)->equals(Duration::of(1, 30)));
        $this->assertSame(1, Duration::ofMinutes(31)->compareTo(Duration::ofMinutes(30)));
        $this->assertSame(-1, Duration::ofMinutes(29)->compareTo(Duration::ofMinutes(30)));
        $this->assertTrue(Duration::zero()->isZero());
        $this->assertTrue(Duration::ofSeconds(1)->isPositive());
        $this->assertTrue(Duration::ofSeconds(-1)->isNegative());
    }

    // ==================== Zerlegung / Formatierung ====================

    public function test_total_minutes_truncates_toward_zero(): void {
        $this->assertSame(90, Duration::ofSeconds(5430)->getTotalMinutes());
        $this->assertSame(-90, Duration::ofSeconds(-5430)->getTotalMinutes());
    }

    public function test_to_parts_uses_uniform_sign(): void {
        $this->assertSame(['hours' => 8, 'minutes' => 30, 'seconds' => 15], Duration::of(8, 30, 15)->toParts());
        $this->assertSame(['hours' => -1, 'minutes' => -1, 'seconds' => -1], Duration::ofSeconds(-3661)->toParts());
    }

    public function test_to_decimal_hours(): void {
        $this->assertSame(1.5, Duration::ofMinutes(90)->toDecimalHours());
        $this->assertSame(-0.25, Duration::ofMinutes(-15)->toDecimalHours());
        $this->assertSame(0.0, Duration::zero()->toDecimalHours());
        $this->assertEqualsWithDelta(0.0002777, Duration::ofSeconds(1)->toDecimalHours(), 1e-7, 'Sekundengenau, keine Rundung.');
    }

    public function test_to_clock(): void {
        $this->assertSame('8:30', Duration::of(8, 30)->toClock());
        $this->assertSame('8:30:15', Duration::of(8, 30, 15)->toClock(true));
        $this->assertSame('-0:15', Duration::ofSeconds(-900)->toClock());
        $this->assertSame('129:05', Duration::ofSeconds(129 * 3600 + 5 * 60)->toClock(), 'Kein Tagesumbruch über 24 h.');
        $this->assertSame('0:00', Duration::zero()->toClock());
    }

    // ==================== Serialisierung ====================

    public function test_to_string_and_json(): void {
        $duration = Duration::of(8, 30);
        $this->assertSame('PT8H30M', (string) $duration);
        $this->assertSame('{"seconds":30600}', json_encode($duration));
    }
}
