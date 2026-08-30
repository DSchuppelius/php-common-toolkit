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

namespace Tests\Enums;

use CommonToolkit\Enums\CurrencyCode;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

final class CurrencyCodeFromSymbolTest extends BaseTestCase {
    public function test_euro_symbol_resolves_to_euro(): void {
        $this->assertSame(
            CurrencyCode::Euro,
            CurrencyCode::fromSymbol('€')
        );
    }

    public function test_dollar_symbol_resolves_to_usdollar(): void {
        // Leitwährung für "$" ist USDollar
        $this->assertSame(
            CurrencyCode::USDollar,
            CurrencyCode::fromSymbol('$')
        );
    }

    public function test_pound_symbol_resolves_to_british_pound(): void {
        $this->assertSame(
            CurrencyCode::BritishPound,
            CurrencyCode::fromSymbol('£')
        );
    }

    public function test_yen_symbol_resolves_to_japanese_yen(): void {
        // Leitwährung für ¥ ist Japanischer Yen
        $this->assertSame(
            CurrencyCode::JapaneseYen,
            CurrencyCode::fromSymbol('¥')
        );
    }

    public function test_ruble_symbol_resolves_to_russian_ruble(): void {
        $this->assertSame(
            CurrencyCode::RussianRuble,
            CurrencyCode::fromSymbol('₽')
        );
    }

    public function test_rupee_symbol_resolves_to_indian_rupee(): void {
        $this->assertSame(
            CurrencyCode::IndianRupee,
            CurrencyCode::fromSymbol('₹')
        );
    }

    public function test_naira_symbol_resolves_to_nigerian_naira(): void {
        $this->assertSame(
            CurrencyCode::NigerianNaira,
            CurrencyCode::fromSymbol('₦')
        );
    }

    public function test_shekel_symbol_resolves_to_israeli_shekel(): void {
        $this->assertSame(
            CurrencyCode::IsraeliShekel,
            CurrencyCode::fromSymbol('₪')
        );
    }

    public function test_dong_symbol_resolves_to_vietnamese_dong(): void {
        $this->assertSame(
            CurrencyCode::VietnameseDong,
            CurrencyCode::fromSymbol('₫')
        );
    }

    public function test_won_symbol_resolves_to_south_korean_won(): void {
        $this->assertSame(
            CurrencyCode::SouthKoreanWon,
            CurrencyCode::fromSymbol('₩')
        );
    }

    public function test_turkish_lira_symbol_resolves_to_turkish_lira(): void {
        $this->assertSame(
            CurrencyCode::TurkishLira,
            CurrencyCode::fromSymbol('₺')
        );
    }

    public function test_rial_symbol_resolves_to_iranian_rial(): void {
        // mehrere Währungen benutzen ﷼ → Leitwährung: IRR
        $this->assertSame(
            CurrencyCode::IranianRial,
            CurrencyCode::fromSymbol('﷼')
        );
    }

    public function test_dirham_symbol_resolves_to_arab_emirate_dirham(): void {
        $this->assertSame(
            CurrencyCode::ArabEmirateDirham,
            CurrencyCode::fromSymbol('د.إ')
        );
    }

    public function test_unknown_symbol_throws_exception(): void {
        $this->expectException(InvalidArgumentException::class);
        CurrencyCode::fromSymbol('@');
    }

    public function test_eindeutige_regionale_symbole(): void {
        // "AU$" ist AUD, das nackte "$" bleibt USD
        $this->assertSame(CurrencyCode::AustralianDollar, CurrencyCode::fromSymbol('AU$'));
        $this->assertSame(CurrencyCode::AustralianDollar, CurrencyCode::fromSymbol('A$'));
        $this->assertSame(CurrencyCode::CanadianDollar, CurrencyCode::fromSymbol('C$'));
        $this->assertSame(CurrencyCode::BrazilianReal, CurrencyCode::fromSymbol('R$'));
        $this->assertSame(CurrencyCode::USDollar, CurrencyCode::fromSymbol('$'));

        // Schreibweisen: eindeutige zuerst, danach das geteilte Symbol
        $this->assertSame(['AU$', 'A$', '$'], CurrencyCode::AustralianDollar->getSymbols());
        $this->assertSame(['€'], CurrencyCode::Euro->getSymbols());
        $this->assertSame([], CurrencyCode::Euro->getUniqueSymbols());
    }

    public function test_waehrungen_am_betrag(): void {
        // Der Kreis, den der AmountTokenizer am Betrag sucht
        $amBetrag = array_values(array_map(
            static fn (CurrencyCode $c): string => $c->value,
            array_filter(CurrencyCode::cases(), static fn (CurrencyCode $c): bool => $c->isStatementCurrency())
        ));
        $this->assertSame(['AED', 'CHF', 'EUR', 'GBP', 'USD'], $this->sorted($amBetrag));
        $this->assertFalse(CurrencyCode::DanishKrone->isStatementCurrency());
    }

    /**
     * @param list<string> $codes
     * @return list<string>
     */
    private function sorted(array $codes): array {
        sort($codes);

        return $codes;
    }
}
