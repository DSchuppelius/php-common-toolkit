<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GlnTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\Gln;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class GlnTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_accepts_valid_gln(): void {
        $this->assertSame('4012345000009', Gln::of('4012345000009')->getValue());
    }

    public function test_of_normalizes_separators(): void {
        $this->assertSame('4012345000009', Gln::of('4-012345-00000-9')->getValue());
        $this->assertSame('4012345000009', Gln::of(' 4012345 000009 ')->getValue());
    }

    public function test_of_rejects_invalid_check_digit(): void {
        $this->expectException(InvalidArgumentException::class);
        Gln::of('4012345000008');
    }

    public function test_of_rejects_wrong_length(): void {
        $this->expectException(InvalidArgumentException::class);
        Gln::of('40123450000'); // 11 Ziffern
    }

    public function test_of_rejects_letters(): void {
        $this->expectException(InvalidArgumentException::class);
        Gln::of('40123450000AB');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        Gln::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(Gln::tryFrom(null));
        $this->assertNull(Gln::tryFrom(''));
        $this->assertNull(Gln::tryFrom('4012345000008'));

        $gln = Gln::tryFrom('4-012345-00000-9');
        $this->assertNotNull($gln);
        $this->assertSame('4012345000009', $gln->getValue());
    }

    // ==================== Strukturzugriff / Formatierung ====================

    public function test_check_digit(): void {
        $this->assertSame('9', Gln::of('4012345000009')->getCheckDigit());
    }

    public function test_formatted(): void {
        $this->assertSame('4-012345-00000-9', Gln::of('4012345000009')->formatted());
    }

    // ==================== Gleichheit / String / JSON ====================

    public function test_equals_ignores_input_formatting(): void {
        $this->assertTrue(Gln::of('4-012345-00000-9')->equals(Gln::of('4012345000009')));
        $this->assertFalse(Gln::of('4012345000009')->equals(Gln::of('4006381333931')));
    }

    public function test_to_string_and_json(): void {
        $gln = Gln::of('4-012345-00000-9');
        $this->assertSame('4012345000009', (string) $gln);
        $this->assertSame('"4012345000009"', json_encode($gln));
    }
}
