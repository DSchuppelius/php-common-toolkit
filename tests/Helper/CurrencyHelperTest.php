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

use PHPUnit\Framework\TestCase;
use CommonToolkit\Helper\Data\CurrencyHelper;

class CurrencyHelperTest extends TestCase {

    public function testFormat(): void {
        $formatted = CurrencyHelper::format(1234.56, 'EUR', 'de_DE');
        $this->assertStringContainsString('€', $formatted);
    }

    public function testParse(): void {
        $amount = CurrencyHelper::parse('1.234,56 €', 'EUR', 'de_DE');
        $this->assertEquals(1234.56, $amount);
    }

    public function testRound(): void {
        $this->assertEquals(1234.57, CurrencyHelper::round(1234.567));
    }

    public function testEquals(): void {
        $this->assertTrue(CurrencyHelper::equals(100.00, 100.001));
        $this->assertTrue(CurrencyHelper::equals(100.00, 100.01));
        $this->assertTrue(CurrencyHelper::equals(100.00, 99.99));
        $this->assertFalse(CurrencyHelper::equals(100.00, 100.05));
        $this->assertFalse(CurrencyHelper::equals(100.00, 99.95));
    }

    public function testIsValid(): void {
        $format = '';
        $this->assertTrue(CurrencyHelper::isValid("1.234,56", $format));
        $this->assertEquals("DE", $format);

        $this->assertTrue(CurrencyHelper::isValid("1,234.56", $format));
        $this->assertEquals("US", $format);

        $this->assertFalse(CurrencyHelper::isValid("abc123", $format));
    }

    public function testUsToDe(): void {
        $this->assertEquals("1234,56", CurrencyHelper::usToDe("1,234.56"));
    }

    public function testDeToUs(): void {
        $this->assertEquals("1234.56", CurrencyHelper::deToUs("1.234,56"));
    }
}
