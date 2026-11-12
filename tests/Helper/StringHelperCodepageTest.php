<?php
/*
 * Created on   : Thu Nov 12 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StringHelperCodepageTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\StringHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * StringHelper::encodingForCodepage(): Windows-Codepage-Nummer → Kodierungsname,
 * den convertToUtf8() versteht.
 */
class StringHelperCodepageTest extends BaseTestCase {
    /**
     * @return array<string, array{int, string, string, string}>
     */
    public static function codepageProvider(): array {
        return [
            'UTF-8' => [65001, 'UTF-8', "Gr\xC3\xBC\xC3\x9Fe", 'Grüße'],
            'UTF-16LE' => [1200, 'UTF-16LE', "G\0r\0\xFC\0", 'Grü'],
            'UTF-16BE' => [1201, 'UTF-16BE', "\0G\0r\0\xFC", 'Grü'],
            'Windows-1250' => [1250, 'Windows-1250', "\xA3\xF3d\x9F", 'Łódź'],
            'Windows-1251' => [1251, 'Windows-1251', "\xCF\xF0\xE8", 'При'],
            'Windows-1252' => [1252, 'Windows-1252', "\x84Gr\xFC\xDFe\x93 \x80", '„Grüße“ €'],
            'Windows-1253' => [1253, 'Windows-1253', "\xC1\xE8\xDE\xED\xE1", 'Αθήνα'],
            'ISO-8859-2' => [28592, 'ISO-8859-2', "\xA3\xF3d\xBC", 'Łódź'],
            'ISO-8859-15' => [28605, 'ISO-8859-15', "\xA4 \xBC", '€ Œ'],
            'DOS 850' => [850, 'CP850', "\x81\x84", 'üä'],
            'KOI8-R' => [20866, 'KOI8-R', "\xF0\xD2\xC9", 'При'],
            'Shift-JIS' => [932, 'CP932', "\x93\xFA\x96\x7B", '日本'],
            'Mac Roman' => [10000, 'MacRoman', "\x8A\x9A", 'äö'],
        ];
    }

    #[DataProvider('codepageProvider')]
    public function test_maps_codepages_to_convertible_encodings(int $codepage, string $encoding, string $bytes, string $utf8): void {
        $this->assertSame($encoding, StringHelper::encodingForCodepage($codepage));
        $this->assertSame($utf8, StringHelper::convertToUtf8($bytes, $encoding));
    }

    public function test_iso_8859_range_and_unknown_codepages(): void {
        $this->assertSame('ISO-8859-1', StringHelper::encodingForCodepage(28591));
        $this->assertSame('ISO-8859-9', StringHelper::encodingForCodepage(28599));
        $this->assertSame('ISO-8859-13', StringHelper::encodingForCodepage(28603));
        $this->assertSame('ASCII', StringHelper::encodingForCodepage(20127));
        $this->assertSame('GB18030', StringHelper::encodingForCodepage(54936));
        $this->assertNull(StringHelper::encodingForCodepage(0));
        $this->assertNull(StringHelper::encodingForCodepage(-1));
        $this->assertNull(StringHelper::encodingForCodepage(28600));
        $this->assertNull(StringHelper::encodingForCodepage(12345));
    }
}
