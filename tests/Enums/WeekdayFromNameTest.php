<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WeekdayFromNameTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Weekday;
use Tests\Contracts\BaseTestCase;

final class WeekdayFromNameTest extends BaseTestCase {
    public function test_german_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('Montag'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('Mi'));
        $this->assertSame(Weekday::THURSDAY, Weekday::fromName('Do.'));
        $this->assertSame(Weekday::SATURDAY, Weekday::fromName('Sonnabend'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('So'));
    }

    public function test_english_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('Mon'));
        $this->assertSame(Weekday::TUESDAY, Weekday::fromName('Tues'));
        $this->assertSame(Weekday::THURSDAY, Weekday::fromName('Thurs'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('SUNDAY'));
    }

    public function test_french_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('lundi'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('mercredi'));
        $this->assertSame(Weekday::SATURDAY, Weekday::fromName('sam.'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('dimanche'));
    }

    public function test_italian_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('lunedì'));
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('lunedi'));
        $this->assertSame(Weekday::THURSDAY, Weekday::fromName('giovedì'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('domenica'));
    }

    public function test_spanish_names_resolve(): void {
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('miércoles'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('miercoles'));
        $this->assertSame(Weekday::THURSDAY, Weekday::fromName('jueves'));
        $this->assertSame(Weekday::SATURDAY, Weekday::fromName('sábado'));
    }

    public function test_dutch_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('maandag'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('wo'));
        $this->assertSame(Weekday::SATURDAY, Weekday::fromName('zaterdag'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('zo'));
    }

    public function test_portuguese_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('segunda-feira'));
        $this->assertSame(Weekday::TUESDAY, Weekday::fromName('terça'));
        $this->assertSame(Weekday::TUESDAY, Weekday::fromName('terca'));
        $this->assertSame(Weekday::FRIDAY, Weekday::fromName('sexta-feira'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('domingo'));
    }

    public function test_polish_names_resolve(): void {
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('poniedziałek'));
        $this->assertSame(Weekday::MONDAY, Weekday::fromName('poniedzialek'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('środa'));
        $this->assertSame(Weekday::WEDNESDAY, Weekday::fromName('sroda'));
        $this->assertSame(Weekday::FRIDAY, Weekday::fromName('piątek'));
        $this->assertSame(Weekday::SUNDAY, Weekday::fromName('niedziela'));
    }

    public function test_unknown_name_returns_null(): void {
        $this->assertNull(Weekday::fromName('Feiertag'));
        $this->assertNull(Weekday::fromName(''));
    }
}
