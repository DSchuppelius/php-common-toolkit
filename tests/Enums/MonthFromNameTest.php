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

    public function test_french_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('janvier'));
        $this->assertSame(Month::FEBRUARY, Month::fromName('février'));
        $this->assertSame(Month::FEBRUARY, Month::fromName('fevrier'));
        $this->assertSame(Month::JULY, Month::fromName('juillet'));
        $this->assertSame(Month::AUGUST, Month::fromName('août'));
        $this->assertSame(Month::AUGUST, Month::fromName('aout'));
        $this->assertSame(Month::DECEMBER, Month::fromName('déc.'));
    }

    public function test_italian_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('gennaio'));
        $this->assertSame(Month::MAY, Month::fromName('maggio'));
        $this->assertSame(Month::JUNE, Month::fromName('giu'));
        $this->assertSame(Month::JULY, Month::fromName('luglio'));
        $this->assertSame(Month::OCTOBER, Month::fromName('ottobre'));
        $this->assertSame(Month::DECEMBER, Month::fromName('dicembre'));
    }

    public function test_spanish_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('Enero'));
        $this->assertSame(Month::AUGUST, Month::fromName('agosto'));
        $this->assertSame(Month::SEPTEMBER, Month::fromName('septiembre'));
        $this->assertSame(Month::OCTOBER, Month::fromName('octubre'));
        $this->assertSame(Month::DECEMBER, Month::fromName('diciembre'));
    }

    public function test_portuguese_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('janeiro'));
        $this->assertSame(Month::FEBRUARY, Month::fromName('fevereiro'));
        $this->assertSame(Month::MARCH, Month::fromName('março'));
        $this->assertSame(Month::MARCH, Month::fromName('marco'));
        $this->assertSame(Month::OCTOBER, Month::fromName('outubro'));
        $this->assertSame(Month::OCTOBER, Month::fromName('out'));
        $this->assertSame(Month::DECEMBER, Month::fromName('dezembro'));
    }

    public function test_polish_names_resolve(): void {
        $this->assertSame(Month::JANUARY, Month::fromName('styczeń'));
        $this->assertSame(Month::JANUARY, Month::fromName('styczen'));
        $this->assertSame(Month::FEBRUARY, Month::fromName('luty'));
        $this->assertSame(Month::APRIL, Month::fromName('kwiecień'));
        $this->assertSame(Month::AUGUST, Month::fromName('sierpień'));
        $this->assertSame(Month::OCTOBER, Month::fromName('październik'));
        $this->assertSame(Month::OCTOBER, Month::fromName('pazdziernik'));
        $this->assertSame(Month::DECEMBER, Month::fromName('grudzień'));
    }

    public function test_unknown_name_returns_null(): void {
        $this->assertNull(Month::fromName('Frimaire'));
        $this->assertNull(Month::fromName(''));
    }
}
