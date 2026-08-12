<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MonthArithmeticTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Month;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

final class MonthArithmeticTest extends BaseTestCase {
    public function test_quarter_of_month(): void {
        $this->assertSame(1, Month::JANUARY->getQuarter());
        $this->assertSame(1, Month::MARCH->getQuarter());
        $this->assertSame(2, Month::APRIL->getQuarter());
        $this->assertSame(3, Month::SEPTEMBER->getQuarter());
        $this->assertSame(4, Month::OCTOBER->getQuarter());
        $this->assertSame(4, Month::DECEMBER->getQuarter());
    }

    public function test_quarter_start_and_end(): void {
        $this->assertTrue(Month::JANUARY->isQuarterStart());
        $this->assertTrue(Month::JULY->isQuarterStart());
        $this->assertFalse(Month::FEBRUARY->isQuarterStart());
        $this->assertTrue(Month::MARCH->isQuarterEnd());
        $this->assertTrue(Month::DECEMBER->isQuarterEnd());
        $this->assertFalse(Month::NOVEMBER->isQuarterEnd());
    }

    public function test_from_quarter_returns_three_months(): void {
        $this->assertSame([Month::JANUARY, Month::FEBRUARY, Month::MARCH], Month::fromQuarter(1));
        $this->assertSame([Month::OCTOBER, Month::NOVEMBER, Month::DECEMBER], Month::fromQuarter(4));
    }

    public function test_from_quarter_rejects_invalid_quarter(): void {
        $this->expectException(InvalidArgumentException::class);
        Month::fromQuarter(5);
    }

    public function test_next_and_previous_wrap_around(): void {
        $this->assertSame(Month::FEBRUARY, Month::JANUARY->next());
        $this->assertSame(Month::JANUARY, Month::DECEMBER->next());
        $this->assertSame(Month::DECEMBER, Month::JANUARY->previous());
        $this->assertSame(Month::NOVEMBER, Month::DECEMBER->previous());
    }

    public function test_add_handles_overflow_and_negatives(): void {
        $this->assertSame(Month::MARCH, Month::JANUARY->add(2));
        $this->assertSame(Month::FEBRUARY, Month::DECEMBER->add(2));
        $this->assertSame(Month::DECEMBER, Month::FEBRUARY->add(-2));
        $this->assertSame(Month::JANUARY, Month::JANUARY->add(24));
        $this->assertSame(Month::OCTOBER, Month::JANUARY->add(-27));
    }

    public function test_distance_to_counts_forward(): void {
        $this->assertSame(0, Month::MAY->distanceTo(Month::MAY));
        $this->assertSame(1, Month::JANUARY->distanceTo(Month::FEBRUARY));
        $this->assertSame(11, Month::FEBRUARY->distanceTo(Month::JANUARY));
        $this->assertSame(2, Month::DECEMBER->distanceTo(Month::FEBRUARY));
    }

    public function test_to_date_builds_midnight_date(): void {
        $date = Month::MARCH->toDate(2026, 27);
        $this->assertSame('2026-03-27 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-01', Month::FEBRUARY->toDate(2026)->format('Y-m-d'));
    }

    public function test_to_date_rejects_invalid_day(): void {
        $this->expectException(InvalidArgumentException::class);
        Month::FEBRUARY->toDate(2026, 30);
    }

    public function test_current_matches_today(): void {
        $this->assertSame(Month::fromDate(new DateTimeImmutable), Month::current());
    }
}
