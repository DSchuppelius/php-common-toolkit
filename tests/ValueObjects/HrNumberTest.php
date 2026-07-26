<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HrNumberTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\ValueObjects\HrNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

class HrNumberTest extends BaseTestCase {
    // ==================== Konstruktion / Normalisierung ====================

    #[DataProvider('registerTypeProvider')]
    public function test_of_accepts_all_register_types(string $input, string $expectedType, string $expectedNumber): void {
        $hrNumber = HrNumber::of($input);
        $this->assertSame($expectedType, $hrNumber->getRegisterType());
        $this->assertSame($expectedNumber, $hrNumber->getNumber());
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function registerTypeProvider(): array {
        return [
            'Handelsregister A' => ['HRA 4711', 'HRA', '4711'],
            'Handelsregister B' => ['HRB 12345', 'HRB', '12345'],
            'Genossenschaftsregister' => ['GNR 100', 'GNR', '100'],
            'Partnerschaftsregister' => ['PR 55', 'PR', '55'],
            'Vereinsregister' => ['VR 2001', 'VR', '2001'],
        ];
    }

    public function test_of_normalizes_case_dots_and_whitespace(): void {
        $this->assertSame('HRB 12345 B', HrNumber::of('hrb12345b')->getValue());
        $this->assertSame('HRB 12345', HrNumber::of('HRB 12.345')->getValue());
        $this->assertSame('HRB 12345', HrNumber::of('  HRB   12345  ')->getValue());
    }

    public function test_of_with_suffix(): void {
        $hrNumber = HrNumber::of('HRB 12345 B');
        $this->assertSame('B', $hrNumber->getSuffix());
        $this->assertNull(HrNumber::of('HRB 12345')->getSuffix());
    }

    public function test_of_rejects_unknown_prefix(): void {
        $this->expectException(InvalidArgumentException::class);
        HrNumber::of('XYZ 123');
    }

    public function test_of_rejects_too_long_number(): void {
        $this->expectException(InvalidArgumentException::class);
        HrNumber::of('HRB 1234567'); // mehr als 6 Ziffern
    }

    public function test_of_rejects_empty(): void {
        $this->expectException(InvalidArgumentException::class);
        HrNumber::of('  ');
    }

    public function test_try_from(): void {
        $this->assertNull(HrNumber::tryFrom(null));
        $this->assertNull(HrNumber::tryFrom(''));
        $this->assertNull(HrNumber::tryFrom('XYZ 123'));

        $hrNumber = HrNumber::tryFrom('hrb 12345');
        $this->assertNotNull($hrNumber);
        $this->assertSame('HRB 12345', $hrNumber->getValue());
    }

    // ==================== Formatierung ====================

    public function test_formatted_matches_canonical_value(): void {
        $this->assertSame('HRB 12345 B', HrNumber::of('hrb12345b')->formatted());
        $this->assertSame('VR 2001', HrNumber::of('vr2001')->formatted());
    }

    // ==================== Gleichheit / String / JSON ====================

    public function test_equals_ignores_input_formatting(): void {
        $this->assertTrue(HrNumber::of('hrb12345')->equals(HrNumber::of('HRB 12.345')));
        $this->assertFalse(HrNumber::of('HRB 12345')->equals(HrNumber::of('HRA 12345')));
        $this->assertFalse(HrNumber::of('HRB 12345')->equals(HrNumber::of('HRB 12345 B')));
    }

    public function test_to_string_and_json(): void {
        $hrNumber = HrNumber::of('hrb12345');
        $this->assertSame('HRB 12345', (string) $hrNumber);
        $this->assertSame('"HRB 12345"', json_encode($hrNumber));
    }
}
