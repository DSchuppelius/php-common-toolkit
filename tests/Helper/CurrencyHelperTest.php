<?php
/*
 * Created on   : Wed Apr 02 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\Data\CurrencyHelper;
use Tests\Contracts\BaseTestCase;

class CurrencyHelperTest extends BaseTestCase {
    public function test_format(): void {
        $formatted = CurrencyHelper::format(1234.56, 'EUR', 'de_DE');
        $this->assertStringContainsString('€', $formatted);
    }

    public function test_parse(): void {
        $amount = CurrencyHelper::parse('1.234,56 €', 'EUR', 'de_DE');
        $this->assertEquals(1234.56, $amount);
    }

    public function test_round(): void {
        $this->assertEquals(1234.57, CurrencyHelper::round(1234.567));
    }

    public function test_equals(): void {
        $this->assertTrue(CurrencyHelper::equals(100.00, 100.001));
        $this->assertTrue(CurrencyHelper::equals(100.00, 100.01));
        $this->assertTrue(CurrencyHelper::equals(100.00, 99.99));
        $this->assertFalse(CurrencyHelper::equals(100.00, 100.05));
        $this->assertFalse(CurrencyHelper::equals(100.00, 99.95));
    }

    public function test_is_currency(): void {
        $format = '';
        $this->assertTrue(CurrencyHelper::isCurrency("1.234,56", $format));
        $this->assertEquals("DE", $format);

        $this->assertTrue(CurrencyHelper::isCurrency("1,234.56", $format));
        $this->assertEquals("US", $format);

        $this->assertFalse(CurrencyHelper::isCurrency("abc123", $format));
    }

    public function test_us_to_de(): void {
        $this->assertEquals("1234,56", CurrencyHelper::usToDe("1,234.56"));
    }

    public function test_de_to_us(): void {
        $this->assertEquals("1234.56", CurrencyHelper::deToUs("1.234,56"));
    }

    public function test_negate_amount(): void {
        $this->assertSame('-96,53', CurrencyHelper::negateAmount('96,53'));
        $this->assertSame('96,53', CurrencyHelper::negateAmount('-96,53'));
        $this->assertSame('-1.234,56', CurrencyHelper::negateAmount(' 1.234,56 '));
        $this->assertSame('1.234,56', CurrencyHelper::negateAmount(' -1.234,56 '));
    }
}
