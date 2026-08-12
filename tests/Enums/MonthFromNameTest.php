<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MonthFromNameTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Month;
use Tests\Contracts\BaseTestCase;

final class MonthFromNameTest extends BaseTestCase {
    public function test_german_full_names_resolve(): void {
        $this->assertSame(Month::JULY, Month::fromName('Juli'));
        $this->assertSame(Month::MARCH, Month::fromName('März'));
        $this->assertSame(Month::DECEMBER, Month::fromName('Dezember'));
    }

    public function test_english_abbreviations_with_period_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('Jan.'));
        $this->assertSame(Month::SEPTEMBER, Month::fromName('Sept'));
        $this->assertSame(Month::OCTOBER, Month::fromName('OCT'));
    }

    public function test_dutch_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('januari'));
        $this->assertSame(Month::MARCH, Month::fromName('mrt'));
        $this->assertSame(Month::MARCH, Month::fromName('maart'));
        $this->assertSame(Month::MAY, Month::fromName('mei'));
        $this->assertSame(Month::AUGUST, Month::fromName('augustus'));
        $this->assertSame(Month::FEBRUARY, Month::fromName('februari'));
    }

    public function test_unknown_name_returns_null(): void {
        $this->assertNull(Month::fromName('Frimaire'));
        $this->assertNull(Month::fromName(''));
    }
}
