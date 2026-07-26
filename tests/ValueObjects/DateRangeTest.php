<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateRangeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\DateRange;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class DateRangeTest extends BaseTestCase {
    // ==================== Konstruktion ====================

    public function test_between_multi_day_range(): void {
        $range = DateRange::between(new DateTimeImmutable('2026-07-01'), new DateTimeImmutable('2026-07-31'));
        $this->assertSame('2026-07-01', $range->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-07-31', $range->getTo()->format('Y-m-d'));
    }

    public function test_single_day_range_is_valid(): void {
        $range = DateRange::singleDay(new DateTimeImmutable('2026-07-15 13:45:00'));
        $this->assertSame('2026-07-15', $range->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-07-15', $range->getTo()->format('Y-m-d'));
        $this->assertSame(1, $range->calendarDays());
    }

    public function test_time_parts_are_normalized_to_midnight(): void {
        $range = DateRange::between(
            new DateTimeImmutable('2026-07-01 08:15:30.123456'),
            new DateTimeImmutable('2026-07-02 23:59:59')
        );
        $this->assertSame('00:00:00.000000', $range->getFrom()->format('H:i:s.u'));
        $this->assertSame('00:00:00.000000', $range->getTo()->format('H:i:s.u'));
    }

    public function test_reversed_bounds_are_rejected_not_swapped(): void {
        $this->expectException(InvalidArgumentException::class);
        DateRange::between(new DateTimeImmutable('2026-07-31'), new DateTimeImmutable('2026-07-01'));
    }

    public function test_from_strings(): void {
        $range = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $this->assertSame('2026-07-01', $range->getFrom()->format('Y-m-d'));

        $zoned = DateRange::fromStrings('2026-07-01', '2026-07-31', new DateTimeZone('Europe/Berlin'));
        $this->assertSame('Europe/Berlin', $zoned->getFrom()->getTimezone()->getName());
    }

    public function test_from_strings_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        DateRange::fromStrings('kein Datum', '2026-07-31');
    }

    // ==================== contains (inklusive Grenzen) ====================

    public function test_contains_is_inclusive_on_both_bounds(): void {
        $range = DateRange::fromStrings('2026-07-01', '2026-07-31');

        $this->assertTrue($range->contains(new DateTimeImmutable('2026-07-01')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-07-31')));
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-07-15 23:59:00')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-06-30')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-08-01')));
    }

    // ==================== overlaps / touches ====================

    public function test_overlapping_separate_and_adjacent_ranges(): void {
        $july = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $midJuly = DateRange::fromStrings('2026-07-15', '2026-08-15');
        $august = DateRange::fromStrings('2026-08-01', '2026-08-31');
        $october = DateRange::fromStrings('2026-10-01', '2026-10-31');

        // Überlappend
        $this->assertTrue($july->overlaps($midJuly));
        $this->assertTrue($midJuly->overlaps($july));

        // Direkt benachbart: 31.07. und 01.08. — keine Überlappung, aber Berührung
        $this->assertFalse($july->overlaps($august));
        $this->assertTrue($july->touches($august));
        $this->assertTrue($august->touches($july));

        // Getrennt
        $this->assertFalse($july->overlaps($october));
        $this->assertFalse($july->touches($october));

        // Überlappung ist keine Berührung
        $this->assertFalse($july->touches($midJuly));
    }

    // ==================== intersection / span ====================

    public function test_intersection_with_result(): void {
        $a = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $b = DateRange::fromStrings('2026-07-15', '2026-08-15');

        $intersection = $a->intersection($b);
        $this->assertNotNull($intersection);
        $this->assertSame('2026-07-15', $intersection->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-07-31', $intersection->getTo()->format('Y-m-d'));
    }

    public function test_intersection_without_result(): void {
        $a = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $b = DateRange::fromStrings('2026-08-01', '2026-08-31');
        $this->assertNull($a->intersection($b));
    }

    public function test_intersection_of_shared_boundary_day(): void {
        // Inklusive Grenzen: gemeinsamer Randtag ist eine echte Schnittmenge.
        $a = DateRange::fromStrings('2026-07-01', '2026-07-15');
        $b = DateRange::fromStrings('2026-07-15', '2026-07-31');

        $intersection = $a->intersection($b);
        $this->assertNotNull($intersection);
        $this->assertSame(1, $intersection->calendarDays());
        $this->assertSame('2026-07-15', $intersection->getFrom()->format('Y-m-d'));
    }

    public function test_span_includes_gap(): void {
        $a = DateRange::fromStrings('2026-07-01', '2026-07-10');
        $b = DateRange::fromStrings('2026-09-01', '2026-09-10');

        $span = $a->span($b);
        $this->assertSame('2026-07-01', $span->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-09-10', $span->getTo()->format('Y-m-d'));
    }

    // ==================== shiftDays / calendarDays ====================

    public function test_shift_days(): void {
        $range = DateRange::fromStrings('2026-07-01', '2026-07-31');

        $shifted = $range->shiftDays(31);
        $this->assertSame('2026-08-01', $shifted->getFrom()->format('Y-m-d'));
        $this->assertSame('2026-08-31', $shifted->getTo()->format('Y-m-d'));

        $back = $range->shiftDays(-1);
        $this->assertSame('2026-06-30', $back->getFrom()->format('Y-m-d'));

        // Ursprung unverändert
        $this->assertSame('2026-07-01', $range->getFrom()->format('Y-m-d'));
    }

    public function test_calendar_days_counts_both_bounds(): void {
        $this->assertSame(31, DateRange::fromStrings('2026-07-01', '2026-07-31')->calendarDays());
        $this->assertSame(2, DateRange::fromStrings('2026-07-01', '2026-07-02')->calendarDays());
        $this->assertSame(1, DateRange::fromStrings('2026-07-01', '2026-07-01')->calendarDays());
    }

    public function test_month_year_and_leap_year_transitions(): void {
        // Jahreswechsel
        $newYear = DateRange::fromStrings('2026-12-30', '2027-01-02');
        $this->assertSame(4, $newYear->calendarDays());

        // Schaltjahr: Februar 2028 hat 29 Tage
        $leap = DateRange::fromStrings('2028-02-01', '2028-02-29');
        $this->assertSame(29, $leap->calendarDays());
        $this->assertTrue($leap->contains(new DateTimeImmutable('2028-02-29')));

        // Kein Schaltjahr: 2027
        $this->assertSame(28, DateRange::fromStrings('2027-02-01', '2027-02-28')->calendarDays());

        // Über den Schalttag hinweg verschieben
        $shifted = DateRange::fromStrings('2028-02-28', '2028-02-28')->shiftDays(1);
        $this->assertSame('2028-02-29', $shifted->getFrom()->format('Y-m-d'));
    }

    public function test_timezones_stay_deterministic(): void {
        // Gleicher Kalendertag in unterschiedlichen Zeitzonen: Kalenderlogik zählt.
        $berlin = DateRange::fromStrings('2026-07-01', '2026-07-31', new DateTimeZone('Europe/Berlin'));
        $tokyo = DateRange::fromStrings('2026-07-01', '2026-07-31', new DateTimeZone('Asia/Tokyo'));

        $this->assertSame(31, $berlin->calendarDays());
        $this->assertSame(31, $tokyo->calendarDays());
        $this->assertTrue($berlin->equals($tokyo), 'Kalenderbereiche sind zeitzonenunabhängig gleich.');

        // DST-Wechsel (29.03.2026 Europe/Berlin): Tageszählung bleibt kalendarisch.
        $dst = DateRange::fromStrings('2026-03-28', '2026-03-30', new DateTimeZone('Europe/Berlin'));
        $this->assertSame(3, $dst->calendarDays());
    }

    // ==================== equals / Serialisierung ====================

    public function test_equals(): void {
        $a = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $b = DateRange::between(new DateTimeImmutable('2026-07-01 15:30:00'), new DateTimeImmutable('2026-07-31 08:00:00'));
        $c = DateRange::fromStrings('2026-07-01', '2026-07-30');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_json_and_array_roundtrip(): void {
        $range = DateRange::fromStrings('2026-07-01', '2026-07-31');
        $this->assertSame('{"from":"2026-07-01","to":"2026-07-31"}', json_encode($range));

        $restored = DateRange::fromArray($range->jsonSerialize());
        $this->assertTrue($restored->equals($range));
    }

    public function test_from_array_rejects_missing_fields(): void {
        $this->expectException(InvalidArgumentException::class);
        DateRange::fromArray(['from' => '2026-07-01']);
    }

    public function test_to_string(): void {
        $this->assertSame('2026-07-01/2026-07-31', (string) DateRange::fromStrings('2026-07-01', '2026-07-31'));
    }
}
