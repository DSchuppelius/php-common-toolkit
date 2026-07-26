<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExchangeRateTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\ValueObjects;

use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\ValueObjects\{Decimal, ExchangeRate, Money};
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

class ExchangeRateTest extends BaseTestCase {
    // ==================== Konstruktion / Invarianten ====================

    public function test_of_with_clear_direction(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $this->assertSame(CurrencyCode::Euro, $rate->getSourceCurrency());
        $this->assertSame(CurrencyCode::SwissFranc, $rate->getTargetCurrency());
        $this->assertSame('0.9385', $rate->getRate()->getValue());
    }

    public function test_of_accepts_decimal_and_int_rate(): void {
        $this->assertSame('2', ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::USDollar, 2)->getRate()->getValue());
        $this->assertSame('1.10', ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::USDollar, Decimal::of('1.10'))->getRate()->getValue());
    }

    public function test_of_rejects_zero_rate(): void {
        $this->expectException(InvalidArgumentException::class);
        ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0');
    }

    public function test_of_rejects_negative_rate(): void {
        $this->expectException(InvalidArgumentException::class);
        ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '-0.9');
    }

    public function test_identical_currencies_require_rate_one(): void {
        $identity = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::Euro, '1.00');
        $this->assertTrue($identity->getRate()->equals(Decimal::one()));

        $this->expectException(InvalidArgumentException::class);
        ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::Euro, '0.9');
    }

    // ==================== convert ====================

    public function test_convert_eur_to_chf(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $converted = $rate->convert(Money::of('100.00', CurrencyCode::Euro));

        $this->assertSame('93.85', $converted->getAmount());
        $this->assertSame(CurrencyCode::SwissFranc, $converted->getCurrency());
    }

    public function test_convert_rejects_wrong_source_currency(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');

        $this->expectException(InvalidArgumentException::class);
        $rate->convert(Money::of('100.00', CurrencyCode::USDollar));
    }

    public function test_convert_uses_iso_scale_of_target_currency(): void {
        $eur = ExchangeRate::of(CurrencyCode::JapaneseYen, CurrencyCode::Euro, '0.0062')
            ->convert(Money::of('1000', CurrencyCode::JapaneseYen));
        $this->assertSame('6.20', $eur->getAmount());

        // JPY: 0 Nachkommastellen
        $yen = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::JapaneseYen, '161.24')
            ->convert(Money::of('10.00', CurrencyCode::Euro));
        $this->assertSame('1612', $yen->getAmount());
        $this->assertSame(0, $yen->getScale());

        // KWD: 3 Nachkommastellen
        $dinar = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::KuwaitiDinar, '0.3059')
            ->convert(Money::of('10.00', CurrencyCode::Euro));
        $this->assertSame('3.059', $dinar->getAmount());
        $this->assertSame(3, $dinar->getScale());
    }

    public function test_convert_with_explicit_scale(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $converted = $rate->convert(Money::of('1.00', CurrencyCode::Euro), 4);
        $this->assertSame('0.9385', $converted->getAmount());
    }

    // ==================== inverse / supports / equals ====================

    public function test_inverse_swaps_currencies_and_inverts_rate(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $inverse = $rate->inverse();

        $this->assertSame(CurrencyCode::SwissFranc, $inverse->getSourceCurrency());
        $this->assertSame(CurrencyCode::Euro, $inverse->getTargetCurrency());
        $this->assertSame('1.0655301012', $inverse->getRate()->getValue());

        // Ursprung bleibt unverändert (Immutabilität).
        $this->assertSame('0.9385', $rate->getRate()->getValue());
    }

    public function test_inverse_with_explicit_scale(): void {
        $inverse = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::USDollar, '2')->inverse(2);
        $this->assertSame('0.50', $inverse->getRate()->getValue());
    }

    public function test_supports(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $this->assertTrue($rate->supports(CurrencyCode::Euro, CurrencyCode::SwissFranc));
        $this->assertFalse($rate->supports(CurrencyCode::SwissFranc, CurrencyCode::Euro));
        $this->assertFalse($rate->supports(CurrencyCode::Euro, CurrencyCode::USDollar));
    }

    public function test_equals(): void {
        $a = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $b = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.93850');
        $c = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::USDollar, '0.9385');

        $this->assertTrue($a->equals($b), 'Fachlich gleicher Kurs trotz anderer Skala.');
        $this->assertFalse($a->equals($c));
    }

    // ==================== Serialisierung ====================

    public function test_json_and_array_roundtrip(): void {
        $rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
        $this->assertSame('{"source":"EUR","target":"CHF","rate":"0.9385","scale":4}', json_encode($rate));

        $restored = ExchangeRate::fromArray($rate->jsonSerialize());
        $this->assertTrue($restored->equals($rate));
        $this->assertSame('0.9385', $restored->getRate()->getValue());
    }

    public function test_from_array_rejects_missing_fields(): void {
        $this->expectException(InvalidArgumentException::class);
        ExchangeRate::fromArray(['source' => 'EUR', 'rate' => '0.9']);
    }

    public function test_to_string(): void {
        $this->assertSame('1 EUR = 0.9385 CHF', (string) ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385'));
    }
}
