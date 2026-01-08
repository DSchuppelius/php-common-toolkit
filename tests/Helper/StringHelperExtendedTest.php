<?php
/*
 * Created on   : Wed Jan 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperExtendedTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use Tests\Contracts\BaseTestCase;

class StringHelperExtendedTest extends BaseTestCase {
    public function testSlugify(): void {
        $this->assertEquals('hello-world', StringHelper::slugify('Hello World'));
        $this->assertEquals('mueller-strasse', StringHelper::slugify('Müller Straße'));
        $this->assertEquals('test-und-mehr', StringHelper::slugify('Test & mehr'));
        $this->assertEquals('multiple-spaces', StringHelper::slugify('Multiple    Spaces'));
        $this->assertEquals('Hello_World', StringHelper::slugify('Hello World', '_', false));
    }

    public function testCamelToSnake(): void {
        $this->assertEquals('hello_world', StringHelper::camelToSnake('helloWorld'));
        $this->assertEquals('my_test_method', StringHelper::camelToSnake('myTestMethod'));
        $this->assertEquals('http_response', StringHelper::camelToSnake('HttpResponse'));
    }

    public function testSnakeToCamel(): void {
        $this->assertEquals('helloWorld', StringHelper::snakeToCamel('hello_world'));
        $this->assertEquals('myTestMethod', StringHelper::snakeToCamel('my_test_method'));
        $this->assertEquals('HelloWorld', StringHelper::snakeToCamel('hello_world', true));
    }

    public function testKebabToCamel(): void {
        $this->assertEquals('helloWorld', StringHelper::kebabToCamel('hello-world'));
        $this->assertEquals('myTestMethod', StringHelper::kebabToCamel('my-test-method'));
        $this->assertEquals('HelloWorld', StringHelper::kebabToCamel('hello-world', true));
    }

    public function testCamelToKebab(): void {
        $this->assertEquals('hello-world', StringHelper::camelToKebab('helloWorld'));
        $this->assertEquals('my-test-method', StringHelper::camelToKebab('myTestMethod'));
    }

    public function testMask(): void {
        $this->assertEquals('****1234', StringHelper::mask('12341234', 0, 4));
        $this->assertEquals('12****34', StringHelper::mask('12341234', 2, 2));
        $this->assertEquals('1******4', StringHelper::mask('12341234', 1, 1));
        $this->assertEquals('1234', StringHelper::mask('1234', 2, 2)); // Zu kurz zum Maskieren
    }

    public function testMaskEmail(): void {
        $this->assertEquals('j***@example.com', StringHelper::maskEmail('john@example.com'));
        $this->assertEquals('ab@example.com', StringHelper::maskEmail('ab@example.com')); // Zu kurz zum Maskieren
        $this->assertEquals('a**@example.com', StringHelper::maskEmail('abc@example.com'));
    }

    public function testWordCount(): void {
        $this->assertEquals(3, StringHelper::wordCount('Eins zwei drei'));
        $this->assertEquals(5, StringHelper::wordCount('Dies ist ein längerer Text'));
        $this->assertEquals(0, StringHelper::wordCount(''));
        $this->assertEquals(1, StringHelper::wordCount('Wort'));
    }

    public function testStartsWith(): void {
        $this->assertTrue(StringHelper::startsWith('Hello World', 'Hello'));
        $this->assertFalse(StringHelper::startsWith('Hello World', 'hello'));
        $this->assertTrue(StringHelper::startsWith('Hello World', 'hello', false));
    }

    public function testEndsWith(): void {
        $this->assertTrue(StringHelper::endsWith('Hello World', 'World'));
        $this->assertFalse(StringHelper::endsWith('Hello World', 'world'));
        $this->assertTrue(StringHelper::endsWith('Hello World', 'world', false));
    }

    public function testExcerpt(): void {
        $text = 'Dies ist ein längerer Text mit vielen Wörtern darin.';
        $excerpt = StringHelper::excerpt($text, 'längerer', 10);
        $this->assertNotNull($excerpt);
        $this->assertStringContainsString('längerer', $excerpt);
        $this->assertStringStartsWith('...', $excerpt);

        $this->assertNull(StringHelper::excerpt($text, 'nichtvorhanden'));
    }

    public function testTitleCase(): void {
        $this->assertEquals('Hello World', StringHelper::titleCase('hello world'));
        $this->assertEquals('Müller Und Meier', StringHelper::titleCase('müller und meier'));
    }

    public function testReverse(): void {
        $this->assertEquals('olleH', StringHelper::reverse('Hello'));
        $this->assertEquals('relläM', StringHelper::reverse('Mäller'));
    }

    public function testIsAlpha(): void {
        $this->assertTrue(StringHelper::isAlpha('Hello'));
        $this->assertTrue(StringHelper::isAlpha('Müller'));
        $this->assertFalse(StringHelper::isAlpha('Hello123'));
        $this->assertFalse(StringHelper::isAlpha('Hello World'));
    }

    public function testIsAlphanumeric(): void {
        $this->assertTrue(StringHelper::isAlphanumeric('Hello123'));
        $this->assertTrue(StringHelper::isAlphanumeric('Müller2'));
        $this->assertFalse(StringHelper::isAlphanumeric('Hello 123'));
    }

    public function testIsNumeric(): void {
        $this->assertTrue(StringHelper::isNumeric('12345'));
        $this->assertFalse(StringHelper::isNumeric('123.45'));
        $this->assertFalse(StringHelper::isNumeric('abc'));
    }

    public function testCountOccurrences(): void {
        $this->assertEquals(3, StringHelper::countOccurrences('ababab', 'ab'));
        $this->assertEquals(2, StringHelper::countOccurrences('Hello HELLO', 'hello', false));
        $this->assertEquals(1, StringHelper::countOccurrences('Hello HELLO', 'Hello', true));
    }

    public function testPad(): void {
        $this->assertEquals('test      ', StringHelper::pad('test', 10));
        $this->assertEquals('      test', StringHelper::pad('test', 10, ' ', STR_PAD_LEFT));
        $this->assertEquals('   test   ', StringHelper::pad('test', 10, ' ', STR_PAD_BOTH));
        $this->assertEquals('test000000', StringHelper::pad('test', 10, '0'));
    }

    public function testRemoveDigits(): void {
        $this->assertEquals('abc', StringHelper::removeDigits('a1b2c3'));
        $this->assertEquals('Hello', StringHelper::removeDigits('Hello'));
    }

    public function testExtractDigits(): void {
        $this->assertEquals('123', StringHelper::extractDigits('a1b2c3'));
        $this->assertEquals('', StringHelper::extractDigits('Hello'));
    }

    public function testNormalizeLineEndings(): void {
        $text = "Line1\r\nLine2\rLine3\nLine4";
        $normalized = StringHelper::normalizeLineEndings($text, "\n");
        $this->assertEquals("Line1\nLine2\nLine3\nLine4", $normalized);
    }

    public function testCollapseWhitespace(): void {
        $this->assertEquals('a b c', StringHelper::collapseWhitespace('a    b    c'));
        $this->assertEquals('hello world', StringHelper::collapseWhitespace("hello\t\n  world"));
    }
}
