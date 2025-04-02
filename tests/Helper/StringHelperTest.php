<?php
/*
 * Created on   : Wed Apr 02 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase {

    public function testUtf8ToIso8859_1(): void {
        $utf8 = "Grüße";
        $iso = StringHelper::utf8ToIso8859_1($utf8);
        $this->assertNotSame($utf8, $iso);
        $this->assertIsString($iso);
    }

    public function testConvertEncoding(): void {
        $original = '€ Zeichen';
        $converted = StringHelper::convertEncoding($original, 'UTF-8', 'ISO-8859-1');
        $this->assertNotSame($original, $converted);
    }

    public function testSanitizePrintable(): void {
        $string = "abc" . chr(7) . "def";
        $cleaned = StringHelper::sanitizePrintable($string);
        $this->assertStringNotContainsString(chr(7), $cleaned);
    }

    public function testRemoveNonAscii(): void {
        $input = "ÄÖÜabc";
        $ascii = StringHelper::removeNonAscii($input);
        $this->assertSame('abc', $ascii);
    }

    public function testTruncate(): void {
        $text = "Das ist ein sehr langer Text";
        $short = StringHelper::truncate($text, 10);
        $this->assertSame('Das ist...', $short);
    }

    public function testIsAscii(): void {
        $ascii = "abc";
        $utf8 = "ü";
        $this->assertTrue(StringHelper::isAscii($ascii));
        $this->assertFalse(StringHelper::isAscii($utf8));
    }

    public function testHtmlEntitiesToText(): void {
        $html = "K&auml;se &amp; Brot";
        $text = StringHelper::htmlEntitiesToText($html);
        $this->assertSame("Käse & Brot", $text);
    }

    public function testNormalizeWhitespace(): void {
        $input = "Text   mit \n  Tabs\tund\nZeilen";
        $normalized = StringHelper::normalizeWhitespace($input);
        $this->assertSame("Text mit Tabs und Zeilen", $normalized);
    }

    public function testToLowerAndUpper(): void {
        $upper = StringHelper::toUpper("Straße");
        $lower = StringHelper::toLower("Straße");
        $this->assertSame("STRASSE", $upper);
        $this->assertSame("straße", $lower);
    }

    public function testRemoveUtf8Bom(): void {
        $bom = "\xEF\xBB\xBFHallo";
        $clean = StringHelper::removeUtf8Bom($bom);
        $this->assertSame("Hallo", $clean);
    }

    public function testDetectEncoding(): void {
        $text = "Grüße aus München"; // UTF-8
        $encoding = StringHelper::detectEncoding($text);

        $this->assertIsString($encoding, "Erkannte Kodierung sollte ein String sein");
        $this->assertMatchesRegularExpression('/utf-?8|iso-8859/i', $encoding, "Kodierung sollte plausibel sein (UTF-8 oder ISO)");
    }
}
