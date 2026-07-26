<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateTimeRangeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\DateTimeRange;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class DateTimeRangeTest extends BaseTestCase {
    // ==================== Konstruktion ====================

    public function test_between(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00'),
            new DateTimeImmutable('2026-07-01 09:30:00')
        );
        $this->assertSame('08:00:00', $range->getStart()->format('H:i:s'));
        $this->assertSame('09:30:00', $range->getEnd()->format('H:i:s'));
    }

    public function test_zero_duration_interval_is_rejected(): void {
        $instant = new DateTimeImmutable('2026-07-01 08:00:00');

        $this->expectException(InvalidArgumentException::class);
        DateTimeRange::between($instant, $instant);
    }

    public function test_reversed_bounds_are_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 09:00:00'),
            new DateTimeImmutable('2026-07-01 08:00:00')
        );
    }

    // ==================== contains: [start, end) ====================

    public function test_start_included_end_excluded(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00'),
            new DateTimeImmutable('2026-07-01 09:00:00')
        );

        $this->assertTrue($range->contains(new DateTimeImmutable('2026-07-01 08:00:00')), 'Start ist enthalten.');
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-07-01 08:59:59')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-07-01 09:00:00')), 'Ende ist ausgeschlossen.');
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-07-01 07:59:59')));
    }

    // ==================== overlaps / touches ====================

    public function test_adjacent_intervals_touch_but_do_not_overlap(): void {
        $morning = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00'),
            new DateTimeImmutable('2026-07-01 12:00:00')
        );
        $afternoon = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 12:00:00'),
            new DateTimeImmutable('2026-07-01 16:00:00')
        );

        $this->assertFalse($morning->overlaps($afternoon), 'Halboffen: aufeinanderfolgende Buchungen überlappen nicht.');
        $this->assertTrue($morning->touches($afternoon));
        $this->assertTrue($afternoon->touches($morning));
    }

    public function test_overlapping_and_separate_intervals(): void {
        $a = DateTimeRange::between(new DateTimeImmutable('2026-07-01 08:00:00'), new DateTimeImmutable('2026-07-01 10:00:00'));
        $b = DateTimeRange::between(new DateTimeImmutable('2026-07-01 09:00:00'), new DateTimeImmutable('2026-07-01 11:00:00'));
        $c = DateTimeRange::between(new DateTimeImmutable('2026-07-01 14:00:00'), new DateTimeImmutable('2026-07-01 15:00:00'));

        $this->assertTrue($a->overlaps($b));
        $this->assertFalse($a->overlaps($c));
        $this->assertFalse($a->touches($c));
        $this->assertFalse($a->touches($b), 'Überlappung ist keine Berührung.');
    }

    // ==================== intersection / span ====================

    public function test_intersection_and_span(): void {
        $a = DateTimeRange::between(new DateTimeImmutable('2026-07-01 08:00:00'), new DateTimeImmutable('2026-07-01 10:00:00'));
        $b = DateTimeRange::between(new DateTimeImmutable('2026-07-01 09:00:00'), new DateTimeImmutable('2026-07-01 11:00:00'));

        $intersection = $a->intersection($b);
        $this->assertNotNull($intersection);
        $this->assertSame('09:00:00', $intersection->getStart()->format('H:i:s'));
        $this->assertSame('10:00:00', $intersection->getEnd()->format('H:i:s'));

        $span = $a->span($b);
        $this->assertSame('08:00:00', $span->getStart()->format('H:i:s'));
        $this->assertSame('11:00:00', $span->getEnd()->format('H:i:s'));
    }

    public function test_intersection_of_touching_intervals_is_null(): void {
        // Halboffen: [8,12) ∩ [12,16) ist leer — kein Nullintervall erzeugen.
        $a = DateTimeRange::between(new DateTimeImmutable('2026-07-01 08:00:00'), new DateTimeImmutable('2026-07-01 12:00:00'));
        $b = DateTimeRange::between(new DateTimeImmutable('2026-07-01 12:00:00'), new DateTimeImmutable('2026-07-01 16:00:00'));

        $this->assertNull($a->intersection($b));
    }

    // ==================== Dauer ====================

    public function test_duration_in_seconds(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00'),
            new DateTimeImmutable('2026-07-01 09:30:00')
        );
        $this->assertSame(5400, $range->durationInSeconds());
    }

    public function test_duration_over_day_change(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 23:00:00'),
            new DateTimeImmutable('2026-07-02 01:00:00')
        );
        $this->assertSame(7200, $range->durationInSeconds());
    }

    public function test_duration_over_dst_change_is_real_elapsed_time(): void {
        // DST-Beginn Europe/Berlin am 29.03.2026: 02:00 → 03:00.
        // Der Kalendertag hat real nur 23 Stunden.
        $berlin = new DateTimeZone('Europe/Berlin');
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-03-29 00:00:00', $berlin),
            new DateTimeImmutable('2026-03-30 00:00:00', $berlin)
        );
        $this->assertSame(23 * 3600, $range->durationInSeconds());
    }

    // ==================== Zeitpunkt- statt Uhrzeitvergleich ====================

    public function test_same_instants_with_different_offsets_are_equal(): void {
        // 12:00 +02:00 und 11:00 +01:00 sind derselbe Zeitpunkt.
        $a = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 12:00:00+02:00'),
            new DateTimeImmutable('2026-07-01 14:00:00+02:00')
        );
        $b = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 11:00:00+01:00'),
            new DateTimeImmutable('2026-07-01 13:00:00+01:00')
        );

        $this->assertTrue($a->equals($b));
        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($a->contains(new DateTimeImmutable('2026-07-01 10:30:00+00:00')));
    }

    // ==================== Serialisierung ====================

    public function test_iso_output_contains_offset(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00+02:00'),
            new DateTimeImmutable('2026-07-01 09:00:00+02:00')
        );

        $this->assertSame('2026-07-01T08:00:00+02:00/2026-07-01T09:00:00+02:00', (string) $range);
        $this->assertSame(
            '{"start":"2026-07-01T08:00:00+02:00","end":"2026-07-01T09:00:00+02:00"}',
            json_encode($range)
        );
    }

    public function test_json_and_array_roundtrip(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00+02:00'),
            new DateTimeImmutable('2026-07-01 09:00:00+02:00')
        );

        $restored = DateTimeRange::fromArray($range->jsonSerialize());
        $this->assertTrue($restored->equals($range));
        $this->assertSame($range->durationInSeconds(), $restored->durationInSeconds());
    }

    public function test_from_array_rejects_missing_fields(): void {
        $this->expectException(InvalidArgumentException::class);
        DateTimeRange::fromArray(['start' => '2026-07-01T08:00:00+02:00']);
    }

    public function test_from_array_rejects_garbage(): void {
        $this->expectException(InvalidArgumentException::class);
        DateTimeRange::fromArray(['start' => 'kein Zeitpunkt', 'end' => '2026-07-01T09:00:00+02:00']);
    }

    public function test_immutability(): void {
        $range = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:00:00'),
            new DateTimeImmutable('2026-07-01 09:00:00')
        );
        $other = DateTimeRange::between(
            new DateTimeImmutable('2026-07-01 08:30:00'),
            new DateTimeImmutable('2026-07-01 10:00:00')
        );

        $range->intersection($other);
        $range->span($other);

        $this->assertSame('08:00:00', $range->getStart()->format('H:i:s'));
        $this->assertSame('09:00:00', $range->getEnd()->format('H:i:s'));
    }
}
