<?php
/*
 * Created on   : Sun Aug 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : WasteCodeTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\WasteCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class WasteCodeTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    #[DataProvider('validCodeProvider')]
    public function test_of_normalizes_valid_codes(string $input, string $expectedValue, bool $expectedHazardous): void {
        $code = WasteCode::of($input);
        $this->assertSame($expectedValue, $code->getValue());
        $this->assertSame($expectedHazardous, $code->isHazardous());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function validCodeProvider(): array {
        return [
            'kanonisch mit Stern' => ['20 01 35*', '20 01 35*', true],
            'kanonisch ohne Stern' => ['20 01 36', '20 01 36', false],
            'ohne Leerzeichen' => ['200135*', '20 01 35*', true],
            'mit Bindestrichen' => ['16-02-13*', '16 02 13*', true],
            'mit Punkten' => ['16.02.14', '16 02 14', false],
            'Stern mit Leerzeichen davor' => ['20 01 35 *', '20 01 35*', true],
            'umschlossen von Whitespace' => ['  08 03 17*  ', '08 03 17*', true],
            'Batterien' => ['16 06 01*', '16 06 01*', true],
            'Kapitel 01' => ['01 01 01', '01 01 01', false],
        ];
    }

    public function test_accessors(): void {
        $code = WasteCode::of('16 02 13*');
        $this->assertSame('160213', $code->getDigits());
        $this->assertSame('16', $code->getChapter());
        $this->assertSame('1602', $code->getGroup());
        $this->assertTrue($code->isHazardous());

        $harmless = WasteCode::of('20 01 36');
        $this->assertFalse($harmless->isHazardous());
    }

    #[DataProvider('invalidCodeProvider')]
    public function test_of_rejects_invalid_codes(string $input): void {
        $this->expectException(InvalidArgumentException::class);
        WasteCode::of($input);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCodeProvider(): array {
        return [
            'leer' => ['  '],
            'zu kurz' => ['20 01 3'],
            'zu lang' => ['20 01 351'],
            'Buchstaben' => ['20 AB 35'],
            'Kapitel 00' => ['00 01 35'],
            'Kapitel 21' => ['21 01 35'],
            'Stern mittig' => ['20*0135'],
            'doppelter Stern' => ['200135**'],
        ];
    }

    public function test_try_from(): void {
        $this->assertNull(WasteCode::tryFrom(null));
        $this->assertNull(WasteCode::tryFrom(''));
        $this->assertNull(WasteCode::tryFrom('99 99 99'));

        $code = WasteCode::tryFrom('200136');
        $this->assertNotNull($code);
        $this->assertSame('20 01 36', $code->getValue());
    }

    // ==================== Gleichheit / Serialisierung ====================

    public function test_equals_distinguishes_hazard_flag(): void {
        $this->assertTrue(WasteCode::of('20 01 35*')->equals(WasteCode::of('200135*')));
        $this->assertFalse(WasteCode::of('20 01 35*')->equals(WasteCode::of('20 01 35')));
        $this->assertFalse(WasteCode::of('20 01 35')->equals(WasteCode::of('20 01 36')));
    }

    public function test_string_and_json_serialization(): void {
        $code = WasteCode::of('200135*');
        $this->assertSame('20 01 35*', (string) $code);
        $this->assertSame('"20 01 35*"', json_encode($code));
    }
}
