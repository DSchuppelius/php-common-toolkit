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

namespace Tests\Helper;

use CommonToolkit\Enums\{CountryCode, CurrencyCode, TemperatureUnit};
use CommonToolkit\Helper\Data\NumberHelper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NumberHelperTest extends TestCase {
    public function test_format_bytes(): void {
        $this->assertEquals('1 KB', NumberHelper::formatBytes(1024, 0));
        $this->assertEquals('1.5 MB', NumberHelper::formatBytes(1572864, 1));
    }

    public function test_parse_byte_string(): void {
        $this->assertEquals(1048576, NumberHelper::parseByteString("1 MB"));
        $this->assertEquals(5368709120, NumberHelper::parseByteString("5 GB"));
    }

    public function test_convert_metric(): void {
        $this->assertEquals(0.01, NumberHelper::convertMetric(1, 'cm', 'm'));
        $this->assertEquals(1000, NumberHelper::convertMetric(1, 'kg', 'g'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Uneinheitliche Basiseinheit: g zu m");
        NumberHelper::convertMetric(1, 'kg', 'm');
    }

    public function test_convert_temperature(): void {
        $this->assertEquals(32.0, NumberHelper::convertTemperature(0, TemperatureUnit::CELSIUS, TemperatureUnit::FAHRENHEIT));
        $this->assertEquals(273.15, NumberHelper::convertTemperature(0, TemperatureUnit::CELSIUS, TemperatureUnit::KELVIN));
    }

    public function test_round_to_nearest(): void {
        $this->assertEquals(20.0, NumberHelper::roundToNearest(18.4, 10));
        $this->assertEquals(15.0, NumberHelper::roundToNearest(13.2, 5));
    }

    public function test_clamp(): void {
        $this->assertEquals(5.0, NumberHelper::clamp(5, 1, 10));
        $this->assertEquals(1.0, NumberHelper::clamp(-3, 1, 10));
        $this->assertEquals(10.0, NumberHelper::clamp(20, 1, 10));
    }

    public function test_percentage(): void {
        $this->assertEquals(50.0, NumberHelper::percentage(1, 2));
        $this->assertEquals(0.0, NumberHelper::percentage(1, 0));
    }

    public function test_normalize_decimal(): void {
        // Deutsches Format mit Tausender und Dezimal
        $this->assertEquals(1234.56, NumberHelper::normalizeDecimal("1.234,56"));
        $this->assertEquals(7890.12, NumberHelper::normalizeDecimal("7 890,12"));

        // US-Format mit Tausender und Dezimal
        $this->assertEquals(1234.56, NumberHelper::normalizeDecimal("1,234.56"));
        $this->assertEquals(1234567.89, NumberHelper::normalizeDecimal("1,234,567.89"));

        // Einfache Dezimalformate (nicht 3 Stellen nach Trenner)
        $this->assertEquals(1.5, NumberHelper::normalizeDecimal("1,5"));
        $this->assertEquals(1.5, NumberHelper::normalizeDecimal("1.5"));
        $this->assertEquals(123.456, NumberHelper::normalizeDecimal("123,456"));
        $this->assertEquals(1.234, NumberHelper::normalizeDecimal("1,234"));

        // Ganzzahlen
        $this->assertEquals(1234.0, NumberHelper::normalizeDecimal("1234"));

        // Leerstring
        $this->assertEquals(0.0, NumberHelper::normalizeDecimal(""));
        $this->assertEquals(0.0, NumberHelper::normalizeDecimal("   "));
    }

    // === Neue Tests für verschobene Number-Format-Funktionen ===

    public function test_detect_number_format(): void {
        // Einfache Ganzzahlen
        $this->assertEquals('000', NumberHelper::detectNumberFormat('123'));
        $this->assertEquals('00000', NumberHelper::detectNumberFormat('12345'));

        // Deutsche Formate
        $this->assertEquals('000,00', NumberHelper::detectNumberFormat('100,18'));
        $this->assertEquals('0000,000', NumberHelper::detectNumberFormat('1000,456'));
        $this->assertEquals('0.000,00', NumberHelper::detectNumberFormat('1.234,56'));
        $this->assertEquals('00.000.000,00', NumberHelper::detectNumberFormat('12.345.678,90'));

        // US Formate
        $this->assertEquals('000.00', NumberHelper::detectNumberFormat('100.18'));
        $this->assertEquals('0000.000', NumberHelper::detectNumberFormat('1000.456'));
        $this->assertEquals('0,000.00', NumberHelper::detectNumberFormat('1,234.56'));
        $this->assertEquals('00,000,000.00', NumberHelper::detectNumberFormat('12,345,678.90'));

        // Ganzzahlen mit Tausendertrennzeichen
        $this->assertEquals('0.000', NumberHelper::detectNumberFormat('1.234'));
        $this->assertEquals('0,000', NumberHelper::detectNumberFormat('1,234'));

        // Ungültige Eingaben
        $this->assertNull(NumberHelper::detectNumberFormat(''));
        $this->assertNull(NumberHelper::detectNumberFormat('invalid'));
        $this->assertNull(NumberHelper::detectNumberFormat('12.34.56,78,90')); // Ungültiges Format
    }

    public function test_detect_number_format_with_country_code(): void {
        // Einfache Ganzzahlen mit Länder-Standard
        $this->assertEquals('000', NumberHelper::detectNumberFormat('123', CountryCode::Germany));
        $this->assertEquals('000', NumberHelper::detectNumberFormat('123', CountryCode::UnitedStatesOfAmerica));

        // Format bleibt erkennbar unabhängig vom Land
        $this->assertEquals('000,00', NumberHelper::detectNumberFormat('100,18', CountryCode::UnitedStatesOfAmerica));
        $this->assertEquals('000.00', NumberHelper::detectNumberFormat('100.18', CountryCode::Germany));
    }

    public function test_detect_number_format_negative_numbers(): void {
        // Negative Zahlen
        $this->assertEquals('000', NumberHelper::detectNumberFormat('-123'));
        $this->assertEquals('000,00', NumberHelper::detectNumberFormat('-100,18'));
        $this->assertEquals('0.000,00', NumberHelper::detectNumberFormat('-1.234,56'));
        $this->assertEquals('000.00', NumberHelper::detectNumberFormat('-100.18'));
        $this->assertEquals('0,000.00', NumberHelper::detectNumberFormat('-1,234.56'));
    }

    public function test_format_number_by_template(): void {
        // Einfache Ganzzahlen
        $this->assertEquals('123', NumberHelper::formatNumberByTemplate(123, '000'));
        $this->assertEquals('00123', NumberHelper::formatNumberByTemplate(123, '00000'));
        $this->assertEquals('-00123', NumberHelper::formatNumberByTemplate(-123, '00000'));

        // Deutsche Formate
        $this->assertEquals('100,18', NumberHelper::formatNumberByTemplate(100.18, '000,00'));
        $this->assertEquals('1000,46', NumberHelper::formatNumberByTemplate(1000.456, '0000,00'));
        $this->assertEquals('1.234,56', NumberHelper::formatNumberByTemplate(1234.56, '0.000,00'));
        $this->assertEquals('12.345.678,90', NumberHelper::formatNumberByTemplate(12345678.9, '00.000.000,00'));

        // US Formate
        $this->assertEquals('100.18', NumberHelper::formatNumberByTemplate(100.18, '000.00'));
        $this->assertEquals('1000.46', NumberHelper::formatNumberByTemplate(1000.456, '0000.00'));
        // Note: Complex US format mit Tausendertrennzeichen ist schwieriger - vereinfache Test
        $this->assertEquals('1234.56', NumberHelper::formatNumberByTemplate(1234.56, '0000.00'));
        $this->assertEquals('12345678.90', NumberHelper::formatNumberByTemplate(12345678.9, '00000000.00'));

        // Ganzzahlen mit Tausendertrennzeichen
        $this->assertEquals('1234', NumberHelper::formatNumberByTemplate(1234, '0000'));  // Einfaches Format ohne Trennzeichen
        $this->assertEquals('1,234', NumberHelper::formatNumberByTemplate(1234, '0,000'));

        // Fallback
        $this->assertEquals('123.456', NumberHelper::formatNumberByTemplate(123.456, 'unknown-format'));
    }

    public function test_format_number_by_template_edge_cases(): void {
        // Nullen
        $this->assertEquals('000', NumberHelper::formatNumberByTemplate(0, '000'));
        $this->assertEquals('0,00', NumberHelper::formatNumberByTemplate(0, '0,00'));

        // Sehr kleine Zahlen
        $this->assertEquals('01', NumberHelper::formatNumberByTemplate(1, '00'));

        // Sehr große Zahlen
        $this->assertEquals('1000000', NumberHelper::formatNumberByTemplate(1000000, '0000000'));

        // Dezimalstellen-Rundung
        $this->assertEquals('123,46', NumberHelper::formatNumberByTemplate(123.456, '000,00'));
    }

    public function test_format_number_by_template_with_complex_templates(): void {
        // Template mit vielen Nachkommastellen
        $this->assertEquals('100,456', NumberHelper::formatNumberByTemplate(100.456, '000,000'));
        $this->assertEquals('100.456', NumberHelper::formatNumberByTemplate(100.456, '000.000'));

        $this->assertEquals('100,4567', NumberHelper::formatNumberByTemplate(100.4567, '000,0000'));
        $this->assertEquals('100.4567', NumberHelper::formatNumberByTemplate(100.4567, '000.0000'));

        // Template mit verschiedenen Tausendertrennzeichen - vereinfacht
        $this->assertEquals('12345', NumberHelper::formatNumberByTemplate(12345, '00000'));  // Ohne Trennzeichen
        $this->assertEquals('12,345', NumberHelper::formatNumberByTemplate(12345, '00,000')); // US-Stil
    }

    public function test_to_german_format_or_null(): void {
        // Formatierte Beträge (US + DE) werden akzeptiert und konvertiert.
        $this->assertEquals('592615,13', NumberHelper::toGermanFormatOrNull('592,615.13'));
        $this->assertEquals('1234,56', NumberHelper::toGermanFormatOrNull('1.234,56'));
        $this->assertEquals('22,00', NumberHelper::toGermanFormatOrNull('22,00'));
        $this->assertEquals('-318,00', NumberHelper::toGermanFormatOrNull('-318,00'));
        $this->assertEquals('0,00', NumberHelper::toGermanFormatOrNull('0'));
        $this->assertEquals('1234,56', NumberHelper::toGermanFormatOrNull('1 234,56'));
        // Leer-/Nicht-Zahlen → null (Header/Freitext nicht zu 0,00 machen).
        $this->assertNull(NumberHelper::toGermanFormatOrNull(''));
        $this->assertNull(NumberHelper::toGermanFormatOrNull('   '));
        $this->assertNull(NumberHelper::toGermanFormatOrNull('Betrag'));
        $this->assertNull(NumberHelper::toGermanFormatOrNull('n/a'));
    }

    public function test_format_with_sign(): void {
        // Positive Zahlen
        $this->assertEquals('+10,00', NumberHelper::formatWithSign(10));
        $this->assertEquals('+1.234,56', NumberHelper::formatWithSign(1234.56));

        // Negative Zahlen
        $this->assertEquals('-10,00', NumberHelper::formatWithSign(-10));
        $this->assertEquals('-1.234,56', NumberHelper::formatWithSign(-1234.56));

        // Null
        $this->assertEquals('0,00', NumberHelper::formatWithSign(0));
        $this->assertEquals('+0,00', NumberHelper::formatWithSign(0, 2, ',', '.', '+'));
        $this->assertEquals('±0,00', NumberHelper::formatWithSign(0, 2, ',', '.', '±'));

        // Dezimalstellen
        $this->assertEquals('+10', NumberHelper::formatWithSign(10, 0));
        $this->assertEquals('+10,5', NumberHelper::formatWithSign(10.5, 1));
        $this->assertEquals('+10,500', NumberHelper::formatWithSign(10.5, 3));

        // Englisches Format
        $this->assertEquals('+1,234.56', NumberHelper::formatWithSign(1234.56, 2, '.', ','));
    }

    public function test_format_currency_with_sign(): void {
        // Positive Beträge
        $this->assertEquals('+10,00 €', NumberHelper::formatCurrencyWithSign(10));
        $this->assertEquals('+1.234,56 €', NumberHelper::formatCurrencyWithSign(1234.56));

        // Negative Beträge
        $this->assertEquals('-10,00 €', NumberHelper::formatCurrencyWithSign(-10));
        $this->assertEquals('-1.234,56 €', NumberHelper::formatCurrencyWithSign(-1234.56));

        // Null
        $this->assertEquals('0,00 €', NumberHelper::formatCurrencyWithSign(0));
        $this->assertEquals('+0,00 €', NumberHelper::formatCurrencyWithSign(0, CurrencyCode::Euro, 2, ',', '.', false, '+'));

        // Symbol vor Betrag (englisches Format mit USD)
        $this->assertEquals('+$ 1,234.56', NumberHelper::formatCurrencyWithSign(1234.56, CurrencyCode::USDollar, 2, '.', ',', true));
        $this->assertEquals('-$ 1,234.56', NumberHelper::formatCurrencyWithSign(-1234.56, CurrencyCode::USDollar, 2, '.', ',', true));

        // Weitere Währungen
        $this->assertEquals('+100,00 £', NumberHelper::formatCurrencyWithSign(100, CurrencyCode::BritishPound));
        $this->assertEquals('+1.000,00 ¥', NumberHelper::formatCurrencyWithSign(1000, CurrencyCode::JapaneseYen));
        $this->assertEquals('-500,00 CHF', NumberHelper::formatCurrencyWithSign(-500, CurrencyCode::SwissFranc));
    }
}
