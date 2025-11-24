<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CurrencyCodeFromSymbolTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use CommonToolkit\Enums\CurrencyCode;
use InvalidArgumentException;

final class CurrencyCodeFromSymbolTest extends TestCase {
    public function testEuroSymbolResolvesToEuro(): void {
        $this->assertSame(
            CurrencyCode::Euro,
            CurrencyCode::fromSymbol('€')
        );
    }

    public function testDollarSymbolResolvesToUsdollar(): void {
        // Leitwährung für "$" ist USDollar
        $this->assertSame(
            CurrencyCode::USDollar,
            CurrencyCode::fromSymbol('$')
        );
    }

    public function testPoundSymbolResolvesToBritishPound(): void {
        $this->assertSame(
            CurrencyCode::BritishPound,
            CurrencyCode::fromSymbol('£')
        );
    }

    public function testYenSymbolResolvesToJapaneseYen(): void {
        // Leitwährung für ¥ ist Japanischer Yen
        $this->assertSame(
            CurrencyCode::JapaneseYen,
            CurrencyCode::fromSymbol('¥')
        );
    }

    public function testRubleSymbolResolvesToRussianRuble(): void {
        $this->assertSame(
            CurrencyCode::RussianRuble,
            CurrencyCode::fromSymbol('₽')
        );
    }

    public function testRupeeSymbolResolvesToIndianRupee(): void {
        $this->assertSame(
            CurrencyCode::IndianRupee,
            CurrencyCode::fromSymbol('₹')
        );
    }

    public function testNairaSymbolResolvesToNigerianNaira(): void {
        $this->assertSame(
            CurrencyCode::NigerianNaira,
            CurrencyCode::fromSymbol('₦')
        );
    }

    public function testShekelSymbolResolvesToIsraeliShekel(): void {
        $this->assertSame(
            CurrencyCode::IsraeliShekel,
            CurrencyCode::fromSymbol('₪')
        );
    }

    public function testDongSymbolResolvesToVietnameseDong(): void {
        $this->assertSame(
            CurrencyCode::VietnameseDong,
            CurrencyCode::fromSymbol('₫')
        );
    }

    public function testWonSymbolResolvesToSouthKoreanWon(): void {
        $this->assertSame(
            CurrencyCode::SouthKoreanWon,
            CurrencyCode::fromSymbol('₩')
        );
    }

    public function testTurkishLiraSymbolResolvesToTurkishLira(): void {
        $this->assertSame(
            CurrencyCode::TurkishLira,
            CurrencyCode::fromSymbol('₺')
        );
    }

    public function testRialSymbolResolvesToIranianRial(): void {
        // mehrere Währungen benutzen ﷼ → Leitwährung: IRR
        $this->assertSame(
            CurrencyCode::IranianRial,
            CurrencyCode::fromSymbol('﷼')
        );
    }

    public function testDirhamSymbolResolvesToArabEmirateDirham(): void {
        $this->assertSame(
            CurrencyCode::ArabEmirateDirham,
            CurrencyCode::fromSymbol('د.إ')
        );
    }

    public function testUnknownSymbolThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        CurrencyCode::fromSymbol('@');
    }
}