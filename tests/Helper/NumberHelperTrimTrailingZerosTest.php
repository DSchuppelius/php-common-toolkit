<?php
/*
 * Created on   : Fri Sep 18 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelperTrimTrailingZerosTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Data\NumberHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für NumberHelper::trimTrailingZeros() und den Schalter
 * $trimTrailingZeros an toGermanFormat()/toUSFormat().
 */
class NumberHelperTrimTrailingZerosTest extends BaseTestCase {
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function trimProvider(): array {
        return [
            'ganzzahl bleibt' => ['10', '.', '10'],
            'hundert bleibt' => ['100', '.', '100'],
            'nachkomma gekürzt' => ['2.5000', '.', '2.5'],
            'nur nullen' => ['100.000', '.', '100'],
            'tausenderpunkt bei komma-trenner' => ['1.000', ',', '1.000'],
            'de mit tausender' => ['1.234,50', ',', '1.234,5'],
            'negativ de' => ['-0,50', ',', '-0,5'],
            'null' => ['0.000', '.', '0'],
            'negativ null' => ['-0,00', ',', '-0'],
            'keine nullen' => ['1.25', '.', '1.25'],
            'null im nachkomma innen' => ['1.050', '.', '1.05'],
            'nullen im vorkomma' => ['1000.10', '.', '1000.1'],
            'us tausender' => ['1,000.00', '.', '1,000'],
            'leerer nachkommateil' => ['10.', '.', '10'],
            'ohne vorkomma' => ['.500', '.', '.5'],
            'ohne vorkomma nur nullen' => ['.000', '.', '0'],
            'vorzeichen ohne vorkomma' => ['-.00', '.', '-0'],
            'leer' => ['', '.', ''],
            'exponent unverändert' => ['1.0e10', '.', '1.0e10'],
            'suffix unverändert' => ['2.50 EUR', '.', '2.50 EUR'],
            'leerer trenner unverändert' => ['2.50', '', '2.50'],
            'mehrzeichen-trenner' => ['2 dec 500', ' dec ', '2 dec 5'],
        ];
    }

    #[DataProvider('trimProvider')]
    public function test_trim_trailing_zeros(string $input, string $separator, string $expected): void {
        $this->assertSame($expected, NumberHelper::trimTrailingZeros($input, $separator));
    }

    public function test_default_separator_is_dot(): void {
        $this->assertSame('2.5', NumberHelper::trimTrailingZeros('2.50'));
        $this->assertSame('2,50', NumberHelper::trimTrailingZeros('2,50'));
    }

    public function test_to_german_format_default_unchanged(): void {
        $this->assertSame('10,00', NumberHelper::toGermanFormat(10));
        $this->assertSame('1.000,00', NumberHelper::toGermanFormat('1000', 2, true));
        $this->assertSame('0,00', NumberHelper::toGermanFormat(''));
    }

    public function test_to_german_format_trims(): void {
        $this->assertSame('10', NumberHelper::toGermanFormat(10, 2, false, null, true));
        $this->assertSame('2,5', NumberHelper::toGermanFormat('2.50', 2, false, null, true));
        $this->assertSame('1.234,5', NumberHelper::toGermanFormat(1234.5, 2, true, null, true));
        $this->assertSame('1.000', NumberHelper::toGermanFormat(1000, 2, true, null, true));
        $this->assertSame('1.000', NumberHelper::toGermanFormat(1000, 0, true, null, true));
        $this->assertSame('-0,5', NumberHelper::toGermanFormat(-0.5, 2, false, null, true));
        $this->assertSame('0', NumberHelper::toGermanFormat('', 2, false, null, true));
        $this->assertSame('1,234', NumberHelper::toGermanFormat(1.2340, 4, false, null, true));
    }

    public function test_to_us_format_trims(): void {
        $this->assertSame('10.00', NumberHelper::toUSFormat(10));
        $this->assertSame('10', NumberHelper::toUSFormat(10, 2, false, null, true));
        $this->assertSame('1,000', NumberHelper::toUSFormat(1000, 2, true, null, true));
        $this->assertSame('1,234.5', NumberHelper::toUSFormat('1.234,50', 2, true, null, true));
        $this->assertSame('-2.25', NumberHelper::toUSFormat(-2.25, 3, false, null, true));
        $this->assertSame('0', NumberHelper::toUSFormat('', 2, false, null, true));
    }
}
