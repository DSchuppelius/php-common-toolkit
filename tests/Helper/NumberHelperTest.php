<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NumberHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Enums\TemperatureUnit;
use PHPUnit\Framework\TestCase;
use CommonToolkit\Helper\Data\NumberHelper;

final class NumberHelperTest extends TestCase {
    public function testFormatBytes(): void {
        $this->assertEquals('1 KB', NumberHelper::formatBytes(1024, 0));
        $this->assertEquals('1.5 MB', NumberHelper::formatBytes(1572864, 1));
    }

    public function testParseByteString(): void {
        $this->assertEquals(1048576, NumberHelper::parseByteString("1 MB"));
        $this->assertEquals(5368709120, NumberHelper::parseByteString("5 GB"));
    }

    public function testConvertMetric(): void {
        $this->assertEquals(0.01, NumberHelper::convertMetric(1, 'cm', 'm'));
        $this->assertEquals(1000, NumberHelper::convertMetric(1, 'kg', 'g'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Uneinheitliche Basiseinheit: g zu m");
        NumberHelper::convertMetric(1, 'kg', 'm');
    }

    public function testConvertTemperature(): void {
        $this->assertEquals(32.0, NumberHelper::convertTemperature(0, TemperatureUnit::CELSIUS, TemperatureUnit::FAHRENHEIT));
        $this->assertEquals(273.15, NumberHelper::convertTemperature(0, TemperatureUnit::CELSIUS, TemperatureUnit::KELVIN));
    }

    public function testRoundToNearest(): void {
        $this->assertEquals(20.0, NumberHelper::roundToNearest(18.4, 10));
        $this->assertEquals(15.0, NumberHelper::roundToNearest(13.2, 5));
    }

    public function testClamp(): void {
        $this->assertEquals(5.0, NumberHelper::clamp(5, 1, 10));
        $this->assertEquals(1.0, NumberHelper::clamp(-3, 1, 10));
        $this->assertEquals(10.0, NumberHelper::clamp(20, 1, 10));
    }

    public function testPercentage(): void {
        $this->assertEquals(50.0, NumberHelper::percentage(1, 2));
        $this->assertEquals(0.0, NumberHelper::percentage(1, 0));
    }

    public function testNormalizeDecimal(): void {
        $this->assertEquals(1234.56, NumberHelper::normalizeDecimal("1.234,56"));
        $this->assertEquals(7890.12, NumberHelper::normalizeDecimal("7 890,12"));
    }
}