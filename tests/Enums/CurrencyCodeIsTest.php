<?php
/*
 * Created on   : Mon Apr 07 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyCodeIsTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Enums;

use CommonToolkit\Enums\CurrencyCode;
use Tests\Contracts\BaseTestCase;

final class CurrencyCodeIsTest extends BaseTestCase {
    public function testIsWithExactString(): void {
        $this->assertTrue(CurrencyCode::Euro->is('EUR'));
        $this->assertTrue(CurrencyCode::USDollar->is('USD'));
    }

    public function testIsWithLowercaseString(): void {
        $this->assertTrue(CurrencyCode::Euro->is('eur'));
        $this->assertTrue(CurrencyCode::USDollar->is('usd'));
    }

    public function testIsWithMixedCaseString(): void {
        $this->assertTrue(CurrencyCode::Euro->is('Eur'));
        $this->assertTrue(CurrencyCode::BritishPound->is('gBp'));
    }

    public function testIsWithWhitespace(): void {
        $this->assertTrue(CurrencyCode::Euro->is(' EUR '));
        $this->assertTrue(CurrencyCode::Euro->is("\tEUR\n"));
    }

    public function testIsWithEnumInstance(): void {
        $this->assertTrue(CurrencyCode::Euro->is(CurrencyCode::Euro));
        $this->assertFalse(CurrencyCode::Euro->is(CurrencyCode::USDollar));
    }

    public function testIsWithNonMatchingString(): void {
        $this->assertFalse(CurrencyCode::Euro->is('USD'));
        $this->assertFalse(CurrencyCode::Euro->is(''));
        $this->assertFalse(CurrencyCode::Euro->is('EURO'));
    }

    public function testIsNotWithExactString(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot('USD'));
        $this->assertFalse(CurrencyCode::Euro->isNot('EUR'));
    }

    public function testIsNotWithLowercaseString(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot('usd'));
        $this->assertFalse(CurrencyCode::Euro->isNot('eur'));
    }

    public function testIsNotWithEnumInstance(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot(CurrencyCode::USDollar));
        $this->assertFalse(CurrencyCode::Euro->isNot(CurrencyCode::Euro));
    }

    public function testIsNotWithWhitespace(): void {
        $this->assertFalse(CurrencyCode::Euro->isNot(' EUR '));
        $this->assertTrue(CurrencyCode::Euro->isNot(' USD '));
    }
}
