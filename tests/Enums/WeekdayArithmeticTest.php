<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WeekdayArithmeticTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Weekday;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class WeekdayArithmeticTest extends BaseTestCase {
    public function test_next_and_previous_wrap_around(): void {
        $this->assertSame(Weekday::TUESDAY, Weekday::MONDAY->next());
        $this->assertSame(Weekday::SUNDAY, Weekday::SATURDAY->next());
        $this->assertSame(Weekday::MONDAY, Weekday::SUNDAY->next());
        $this->assertSame(Weekday::SATURDAY, Weekday::SUNDAY->previous());
        $this->assertSame(Weekday::SUNDAY, Weekday::MONDAY->previous());
    }

    public function test_add_handles_overflow_and_negatives(): void {
        $this->assertSame(Weekday::FRIDAY, Weekday::MONDAY->add(4));
        $this->assertSame(Weekday::MONDAY, Weekday::MONDAY->add(7));
        $this->assertSame(Weekday::MONDAY, Weekday::MONDAY->add(-14));
        $this->assertSame(Weekday::SATURDAY, Weekday::MONDAY->add(-2));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::SUNDAY->add(10));
    }

    public function test_days_until_counts_forward(): void {
        $this->assertSame(0, Weekday::MONDAY->daysUntil(Weekday::MONDAY));
        $this->assertSame(4, Weekday::MONDAY->daysUntil(Weekday::FRIDAY));
        $this->assertSame(3, Weekday::FRIDAY->daysUntil(Weekday::MONDAY));
        $this->assertSame(1, Weekday::SATURDAY->daysUntil(Weekday::SUNDAY));
    }

    public function test_next_workday_skips_weekend(): void {
        $this->assertSame(Weekday::TUESDAY, Weekday::MONDAY->nextWorkday());
        $this->assertSame(Weekday::MONDAY, Weekday::FRIDAY->nextWorkday());
        $this->assertSame(Weekday::MONDAY, Weekday::SATURDAY->nextWorkday());
        $this->assertSame(Weekday::MONDAY, Weekday::SUNDAY->nextWorkday());
    }

    public function test_previous_workday_skips_weekend(): void {
        $this->assertSame(Weekday::THURSDAY, Weekday::FRIDAY->previousWorkday());
        $this->assertSame(Weekday::FRIDAY, Weekday::MONDAY->previousWorkday());
        $this->assertSame(Weekday::FRIDAY, Weekday::SATURDAY->previousWorkday());
        $this->assertSame(Weekday::FRIDAY, Weekday::SUNDAY->previousWorkday());
    }

    public function test_today_matches_current_date(): void {
        $this->assertSame(Weekday::fromDate(new DateTimeImmutable), Weekday::today());
    }
}
