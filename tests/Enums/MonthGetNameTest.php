<?php
/*
 * Created on   : Wed Aug 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MonthGetNameTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\Month;
use Tests\Contracts\BaseTestCase;

final class MonthGetNameTest extends BaseTestCase {
    public function test_german_and_english_names(): void {
        $this->assertSame('März', Month::MARCH->getName('de'));
        $this->assertSame('Dezember', Month::DECEMBER->getName('de'));
        $this->assertSame('March', Month::MARCH->getName('en'));
        $this->assertSame('January', Month::JANUARY->getName());
    }

    public function test_additional_languages(): void {
        $this->assertSame('janvier', Month::JANUARY->getName('fr'));
        $this->assertSame('août', Month::AUGUST->getName('fr'));
        $this->assertSame('gennaio', Month::JANUARY->getName('it'));
        $this->assertSame('enero', Month::JANUARY->getName('es'));
        $this->assertSame('januari', Month::JANUARY->getName('nl'));
        $this->assertSame('janeiro', Month::JANUARY->getName('pt'));
        $this->assertSame('styczeń', Month::JANUARY->getName('pl'));
        $this->assertSame('październik', Month::OCTOBER->getName('pl'));
    }

    public function test_full_locale_is_normalized(): void {
        $this->assertSame('März', Month::MARCH->getName('de_DE'));
        $this->assertSame('mars', Month::MARCH->getName('fr-FR'));
        $this->assertSame('maart', Month::MARCH->getName('nl_NL'));
    }

    public function test_unknown_locale_falls_back_to_english(): void {
        $this->assertSame('March', Month::MARCH->getName('xx'));
        $this->assertSame('May', Month::MAY->getName(''));
    }

    public function test_to_array_uses_locale(): void {
        $months = Month::toArray(true, 'fr');
        $this->assertSame('janvier', $months['01']);
        $this->assertSame('décembre', $months['12']);
        $this->assertCount(12, $months);
    }
}
