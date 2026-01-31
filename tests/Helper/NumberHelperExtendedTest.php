<?php
/*
 * Created on   : Wed Jan 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelperExtendedTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\NumberHelper;
use Tests\Contracts\BaseTestCase;

class NumberHelperExtendedTest extends BaseTestCase {
    public function testFormatCurrency(): void {
        $this->assertEquals('1.234,56 €', NumberHelper::formatCurrency(1234.56));
        $this->assertEquals('-100,00 €', NumberHelper::formatCurrency(-100));
        $this->assertEquals('$ 1,234.56', NumberHelper::formatCurrency(1234.56, CurrencyCode::USDollar, 2, '.', ',', true));
    }

    public function testOrdinalize(): void {
        // Deutsch
        $this->assertEquals('1.', NumberHelper::ordinalize(1, 'de'));
        $this->assertEquals('2.', NumberHelper::ordinalize(2, 'de'));
        $this->assertEquals('100.', NumberHelper::ordinalize(100, 'de'));

        // Englisch
        $this->assertEquals('1st', NumberHelper::ordinalize(1, 'en'));
        $this->assertEquals('2nd', NumberHelper::ordinalize(2, 'en'));
        $this->assertEquals('3rd', NumberHelper::ordinalize(3, 'en'));
        $this->assertEquals('4th', NumberHelper::ordinalize(4, 'en'));
        $this->assertEquals('11th', NumberHelper::ordinalize(11, 'en'));
        $this->assertEquals('12th', NumberHelper::ordinalize(12, 'en'));
        $this->assertEquals('13th', NumberHelper::ordinalize(13, 'en'));
        $this->assertEquals('21st', NumberHelper::ordinalize(21, 'en'));
        $this->assertEquals('22nd', NumberHelper::ordinalize(22, 'en'));
    }

    public function testToWords(): void {
        $this->assertEquals('null', NumberHelper::toWords(0));
        $this->assertEquals('eins', NumberHelper::toWords(1));
        $this->assertEquals('zwölf', NumberHelper::toWords(12));
        $this->assertEquals('einundzwanzig', NumberHelper::toWords(21));
        $this->assertEquals('einhundertdreiundzwanzig', NumberHelper::toWords(123));
        $this->assertEquals('eintausend', NumberHelper::toWords(1000));
        $this->assertEquals('eine Million', NumberHelper::toWords(1000000));
        $this->assertEquals('minus fünf', NumberHelper::toWords(-5));
        $this->assertEquals('Zehn', NumberHelper::toWords(10, true));
    }

    public function testIsEvenAndOdd(): void {
        $this->assertTrue(NumberHelper::isEven(2));
        $this->assertTrue(NumberHelper::isEven(0));
        $this->assertFalse(NumberHelper::isEven(1));

        $this->assertTrue(NumberHelper::isOdd(1));
        $this->assertTrue(NumberHelper::isOdd(3));
        $this->assertFalse(NumberHelper::isOdd(2));
    }

    public function testIsPositiveNegativeZero(): void {
        $this->assertTrue(NumberHelper::isPositive(5));
        $this->assertFalse(NumberHelper::isPositive(-5));
        $this->assertFalse(NumberHelper::isPositive(0));

        $this->assertTrue(NumberHelper::isNegative(-5));
        $this->assertFalse(NumberHelper::isNegative(5));
        $this->assertFalse(NumberHelper::isNegative(0));

        $this->assertTrue(NumberHelper::isZero(0));
        $this->assertTrue(NumberHelper::isZero(0.0));
        $this->assertFalse(NumberHelper::isZero(1));
    }

    public function testAverage(): void {
        $this->assertEquals(5.0, NumberHelper::average([1, 5, 9]));
        $this->assertEquals(0.0, NumberHelper::average([]));
        $this->assertEquals(10.0, NumberHelper::average([10]));
    }

    public function testMedian(): void {
        $this->assertEquals(5.0, NumberHelper::median([1, 5, 9]));
        $this->assertEquals(3.0, NumberHelper::median([1, 2, 4, 5]));
        $this->assertEquals(0.0, NumberHelper::median([]));
    }

    public function testSign(): void {
        $this->assertEquals(1, NumberHelper::sign(10));
        $this->assertEquals(-1, NumberHelper::sign(-10));
        $this->assertEquals(0, NumberHelper::sign(0));
    }

    public function testFormatWithSiPrefix(): void {
        $this->assertEquals('1k', NumberHelper::formatWithSiPrefix(1000, 0));
        $this->assertEquals('1.5M', NumberHelper::formatWithSiPrefix(1500000, 1));
        $this->assertEquals('1Ki', NumberHelper::formatWithSiPrefix(1024, 0, true));
    }

    public function testFactorial(): void {
        $this->assertEquals(1.0, NumberHelper::factorial(0));
        $this->assertEquals(1.0, NumberHelper::factorial(1));
        $this->assertEquals(120.0, NumberHelper::factorial(5));
        $this->assertEquals(3628800.0, NumberHelper::factorial(10));
    }

    public function testFactorialThrowsExceptionForNegative(): void {
        $this->expectException(\InvalidArgumentException::class);
        NumberHelper::factorial(-1);
    }

    public function testIsPrime(): void {
        $this->assertFalse(NumberHelper::isPrime(0));
        $this->assertFalse(NumberHelper::isPrime(1));
        $this->assertTrue(NumberHelper::isPrime(2));
        $this->assertTrue(NumberHelper::isPrime(3));
        $this->assertFalse(NumberHelper::isPrime(4));
        $this->assertTrue(NumberHelper::isPrime(5));
        $this->assertTrue(NumberHelper::isPrime(97));
        $this->assertFalse(NumberHelper::isPrime(100));
    }

    public function testGcd(): void {
        $this->assertEquals(6, NumberHelper::gcd(12, 18));
        $this->assertEquals(1, NumberHelper::gcd(17, 23));
        $this->assertEquals(5, NumberHelper::gcd(15, 25));
    }

    public function testLcm(): void {
        $this->assertEquals(36, NumberHelper::lcm(12, 18));
        $this->assertEquals(391, NumberHelper::lcm(17, 23));
        $this->assertEquals(75, NumberHelper::lcm(15, 25));
        $this->assertEquals(0, NumberHelper::lcm(0, 5));
    }
}
