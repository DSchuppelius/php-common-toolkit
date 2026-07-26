<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : LeiTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\Lei;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class LeiTest extends BaseTestCase {
    /** Bekannter gültiger LEI (GLEIF-Beispiel). */
    private const VALID = '5493001KJTIIGC8Y1R12';

    // ==================== Konstruktion / Normalisierung ====================

    public function test_of_accepts_valid_lei(): void {
        $this->assertSame(self::VALID, Lei::of(self::VALID)->getValue());
    }

    public function test_of_normalizes_case_and_whitespace(): void {
        $this->assertSame('HWUPKR0MPOU8FGXBT394', Lei::of(' hwupkr0mpou8fgxbt394 ')->getValue());
    }

    public function test_of_rejects_invalid_checksum(): void {
        $this->expectException(InvalidArgumentException::class);
        Lei::of('5493001KJTIIGC8Y1R13');
    }

    public function test_of_rejects_wrong_length(): void {
        $this->expectException(InvalidArgumentException::class);
        Lei::of('5493001KJTIIGC8Y1R1');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        Lei::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(Lei::tryFrom(null));
        $this->assertNull(Lei::tryFrom(''));
        $this->assertNull(Lei::tryFrom('5493001KJTIIGC8Y1R13'));

        $lei = Lei::tryFrom('hwupkr0mpou8fgxbt394');
        $this->assertNotNull($lei);
        $this->assertSame('HWUPKR0MPOU8FGXBT394', $lei->getValue());
    }

    // ==================== Strukturzugriff ====================

    public function test_structure_accessors(): void {
        $lei = Lei::of(self::VALID);
        $this->assertSame('5493', $lei->getLouCode());
        $this->assertSame('001KJTIIGC8Y1R', $lei->getEntityPart());
        $this->assertSame('12', $lei->getCheckDigits());
    }

    public function test_formatted(): void {
        $this->assertSame('HWUP KR0M POU8 FGXB T394', Lei::of('HWUPKR0MPOU8FGXBT394')->formatted());
    }

    // ==================== Gleichheit / String / JSON ====================

    public function test_equals_ignores_case(): void {
        $this->assertTrue(Lei::of('hwupkr0mpou8fgxbt394')->equals(Lei::of('HWUPKR0MPOU8FGXBT394')));
        $this->assertFalse(Lei::of(self::VALID)->equals(Lei::of('HWUPKR0MPOU8FGXBT394')));
    }

    public function test_to_string_and_json(): void {
        $lei = Lei::of(self::VALID);
        $this->assertSame(self::VALID, (string) $lei);
        $this->assertSame('"' . self::VALID . '"', json_encode($lei));
    }
}
