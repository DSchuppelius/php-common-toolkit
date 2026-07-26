<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GtinTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\Gtin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class GtinTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    #[DataProvider('validGtinProvider')]
    public function test_of_accepts_all_gtin_lengths(string $input, int $expectedLength): void {
        $gtin = Gtin::of($input);
        $this->assertSame($expectedLength, $gtin->getLength());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function validGtinProvider(): array {
        return [
            'GTIN-8 (EAN-8)' => ['96385074', 8],
            'GTIN-12 (UPC-A)' => ['036000291452', 12],
            'GTIN-13 (EAN-13)' => ['4006381333931', 13],
            'GTIN-14' => ['04006381333931', 14],
        ];
    }

    public function test_of_normalizes_separators(): void {
        $this->assertSame('4006381333931', Gtin::of('4006381-333931')->getValue());
        $this->assertSame('4006381333931', Gtin::of(' 4006381 333931 ')->getValue());
        $this->assertSame('4006381333931', Gtin::of('4.006.381.333.931')->getValue());
    }

    public function test_of_rejects_invalid_check_digit(): void {
        $this->expectException(InvalidArgumentException::class);
        Gtin::of('4006381333930');
    }

    public function test_of_rejects_invalid_length(): void {
        $this->expectException(InvalidArgumentException::class);
        Gtin::of('1234567890'); // 10 Ziffern sind keine GTIN-Länge
    }

    public function test_of_rejects_letters(): void {
        $this->expectException(InvalidArgumentException::class);
        Gtin::of('ABC4006381333931');
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        Gtin::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(Gtin::tryFrom(null));
        $this->assertNull(Gtin::tryFrom(''));
        $this->assertNull(Gtin::tryFrom('4006381333930'));

        $gtin = Gtin::tryFrom('4006381-333931');
        $this->assertNotNull($gtin);
        $this->assertSame('4006381333931', $gtin->getValue());
    }

    // ==================== Strukturzugriff ====================

    public function test_check_digit(): void {
        $this->assertSame('1', Gtin::of('4006381333931')->getCheckDigit());
        $this->assertSame('4', Gtin::of('96385074')->getCheckDigit());
    }

    // ==================== toGtin14 ====================

    public function test_to_gtin14_pads_with_leading_zeros(): void {
        $gtin14 = Gtin::of('4006381333931')->toGtin14();
        $this->assertSame('04006381333931', $gtin14->getValue());
        $this->assertSame(14, $gtin14->getLength());
    }

    public function test_to_gtin14_is_idempotent(): void {
        $gtin14 = Gtin::of('04006381333931');
        $this->assertSame($gtin14, $gtin14->toGtin14(), 'Bereits 14-stellig: dieselbe Instanz.');
    }

    public function test_to_gtin14_check_digit_stays_valid(): void {
        // Links-Padding verändert die Mod-10-Prüfziffer nicht.
        $this->assertSame('00000096385074', Gtin::of('96385074')->toGtin14()->getValue());
    }

    // ==================== Gleichheit ====================

    public function test_equals_is_exact_not_length_agnostic(): void {
        $this->assertTrue(Gtin::of('4006381333931')->equals(Gtin::of('4006381-333931')));
        $this->assertFalse(
            Gtin::of('4006381333931')->equals(Gtin::of('04006381333931')),
            'GTIN-13 und gepaddete GTIN-14 sind exakt NICHT gleich.'
        );
        // GS1-übergreifender Vergleich läuft bewusst über toGtin14().
        $this->assertTrue(Gtin::of('4006381333931')->toGtin14()->equals(Gtin::of('04006381333931')));
    }

    // ==================== String / JSON ====================

    public function test_to_string_and_json(): void {
        $gtin = Gtin::of('4006381-333931');
        $this->assertSame('4006381333931', (string) $gtin);
        $this->assertSame('"4006381333931"', json_encode($gtin));
    }
}
