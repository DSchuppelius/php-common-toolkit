<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WeekdayGetNameTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Weekday;
use Tests\Contracts\BaseTestCase;

final class WeekdayGetNameTest extends BaseTestCase {
    public function test_german_and_english_names(): void {
        $this->assertSame('Montag', Weekday::MONDAY->getName('de'));
        $this->assertSame('Sonntag', Weekday::SUNDAY->getName('de'));
        $this->assertSame('Monday', Weekday::MONDAY->getName('en'));
        $this->assertSame('Sunday', Weekday::SUNDAY->getName());
    }

    public function test_additional_languages(): void {
        $this->assertSame('lundi', Weekday::MONDAY->getName('fr'));
        $this->assertSame('mercoledì', Weekday::WEDNESDAY->getName('it'));
        $this->assertSame('miércoles', Weekday::WEDNESDAY->getName('es'));
        $this->assertSame('woensdag', Weekday::WEDNESDAY->getName('nl'));
        $this->assertSame('segunda-feira', Weekday::MONDAY->getName('pt'));
        $this->assertSame('poniedziałek', Weekday::MONDAY->getName('pl'));
        $this->assertSame('środa', Weekday::WEDNESDAY->getName('pl'));
    }

    public function test_short_names(): void {
        $this->assertSame('Mo', Weekday::MONDAY->getShortName('de'));
        $this->assertSame('Mon', Weekday::MONDAY->getShortName('en'));
        $this->assertSame('lun', Weekday::MONDAY->getShortName('fr'));
        $this->assertSame('seg', Weekday::MONDAY->getShortName('pt'));
        $this->assertSame('śr', Weekday::WEDNESDAY->getShortName('pl'));
        $this->assertSame('za', Weekday::SATURDAY->getShortName('nl'));
    }

    public function test_full_locale_is_normalized(): void {
        $this->assertSame('Montag', Weekday::MONDAY->getName('de_DE'));
        $this->assertSame('lundi', Weekday::MONDAY->getName('fr-FR'));
        $this->assertSame('Di', Weekday::TUESDAY->getShortName('de_AT'));
    }

    public function test_unknown_locale_falls_back_to_english(): void {
        $this->assertSame('Monday', Weekday::MONDAY->getName('xx'));
        $this->assertSame('Sat', Weekday::SATURDAY->getShortName(''));
    }

    public function test_to_array_uses_locale(): void {
        $days = Weekday::toArray(false, 'fr');
        $this->assertSame('dimanche', $days[0]);
        $this->assertSame('samedi', $days[6]);
        $this->assertCount(7, $days);
    }

    public function test_format_mask_uses_locale(): void {
        $mask = Weekday::createMask(Weekday::MONDAY, Weekday::FRIDAY);
        $this->assertSame('Mo, Fr', Weekday::formatMask($mask, 'de'));
        $this->assertSame('Mon, Fri', Weekday::formatMask($mask, 'en'));
        $this->assertSame('lun, ven', Weekday::formatMask($mask, 'fr'));
    }
}
