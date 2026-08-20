<?php
/*
 * Created on   : Thu Aug 20 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EpcQrBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\Payment;

use CommonToolkit\Builders\Payment\EpcQrBuilder;
use CommonToolkit\Enums\CurrencyCode;
use InvalidArgumentException;
use Tests\Contracts\BaseTestCase;

/**
 * EPC-QR-Nutzlast (Girocode, EPC069-12).
 */
class EpcQrBuilderTest extends BaseTestCase {
    private const IBAN = 'DE02120300000000202051';

    public function test_minimal_payload_stops_after_the_iban(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', self::IBAN)->build();

        $this->assertSame(
            "BCD\n002\n1\nSCT\n\nMuster GmbH\n" . self::IBAN,
            $payload,
        );
    }

    public function test_amount_uses_dot_and_two_decimals(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', self::IBAN)->amount(1234.5)->build();

        $this->assertStringContainsString("\nEUR1234.50", $payload);
    }

    public function test_iban_is_normalized(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', 'de02 1203 0000 0000 2020 51')->build();

        $this->assertStringContainsString(self::IBAN, $payload);
    }

    public function test_full_payload_keeps_field_order(): void {
        $payload = EpcQrBuilder::to('Muster GmbH', self::IBAN)
            ->bic('BYLADEM1001')
            ->amount(99.99)
            ->purpose('GDDS')
            ->remittance('Rechnung 2026-0815')
            ->note('Bitte bis 31.08. zahlen')
            ->build();

        $this->assertSame([
            'BCD',
            '002',
            '1',
            'SCT',
            'BYLADEM1001',
            'Muster GmbH',
            self::IBAN,
            'EUR99.99',
            'GDDS',
            '',
            'Rechnung 2026-0815',
            'Bitte bis 31.08. zahlen',
        ], explode("\n", $payload));
    }

    public function test_reference_and_remittance_are_mutually_exclusive(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('Muster GmbH', self::IBAN)
            ->reference('RF18539007547034')
            ->remittance('Rechnung 2026-0815');
    }

    public function test_only_euro_is_allowed(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('Muster GmbH', self::IBAN)->amount(10.0, CurrencyCode::USDollar);
    }

    public function test_amount_bounds_are_enforced(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('Muster GmbH', self::IBAN)->amount(0.001);
    }

    public function test_invalid_iban_is_refused(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('Muster GmbH', 'DE00000000000000000000');
    }

    public function test_empty_name_is_refused(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to('   ', self::IBAN);
    }

    public function test_payload_length_limit_is_enforced(): void {
        $this->expectException(InvalidArgumentException::class);

        EpcQrBuilder::to(str_repeat('A', 70), self::IBAN)
            ->bic('BYLADEM1001')
            ->amount(99.99)
            ->purpose('GDDS')
            ->remittance(str_repeat('B', 140))
            ->note(str_repeat('C', 70))
            ->build();
    }

    public function test_iso_charset_transcodes_the_note(): void {
        $payload = EpcQrBuilder::to('Grüße GmbH', self::IBAN)->charset(false)->build();

        $this->assertSame('2', explode("\n", $payload)[2]);
        $this->assertStringNotContainsString('Grüße', $payload); // nicht mehr UTF-8
    }
}
