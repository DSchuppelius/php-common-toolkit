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
    public function test_is_with_exact_string(): void {
        $this->assertTrue(CurrencyCode::Euro->is('EUR'));
        $this->assertTrue(CurrencyCode::USDollar->is('USD'));
    }

    public function test_is_with_lowercase_string(): void {
        $this->assertTrue(CurrencyCode::Euro->is('eur'));
        $this->assertTrue(CurrencyCode::USDollar->is('usd'));
    }

    public function test_is_with_mixed_case_string(): void {
        $this->assertTrue(CurrencyCode::Euro->is('Eur'));
        $this->assertTrue(CurrencyCode::BritishPound->is('gBp'));
    }

    public function test_is_with_whitespace(): void {
        $this->assertTrue(CurrencyCode::Euro->is(' EUR '));
        $this->assertTrue(CurrencyCode::Euro->is("\tEUR\n"));
    }

    public function test_is_with_enum_instance(): void {
        $this->assertTrue(CurrencyCode::Euro->is(CurrencyCode::Euro));
        $this->assertFalse(CurrencyCode::Euro->is(CurrencyCode::USDollar));
    }

    public function test_is_with_non_matching_string(): void {
        $this->assertFalse(CurrencyCode::Euro->is('USD'));
        $this->assertFalse(CurrencyCode::Euro->is(''));
        $this->assertFalse(CurrencyCode::Euro->is('EURO'));
    }

    public function test_is_not_with_exact_string(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot('USD'));
        $this->assertFalse(CurrencyCode::Euro->isNot('EUR'));
    }

    public function test_is_not_with_lowercase_string(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot('usd'));
        $this->assertFalse(CurrencyCode::Euro->isNot('eur'));
    }

    public function test_is_not_with_enum_instance(): void {
        $this->assertTrue(CurrencyCode::Euro->isNot(CurrencyCode::USDollar));
        $this->assertFalse(CurrencyCode::Euro->isNot(CurrencyCode::Euro));
    }

    public function test_is_not_with_whitespace(): void {
        $this->assertFalse(CurrencyCode::Euro->isNot(' EUR '));
        $this->assertTrue(CurrencyCode::Euro->isNot(' USD '));
    }
}
