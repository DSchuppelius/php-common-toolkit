<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt103DocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\Common\Banking\Mt1;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Entities\Common\Banking\Mt1\Type103\Document as Mt103Document;
use CommonToolkit\Enums\Common\Banking\BankOperationCode;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class Mt103DocumentTest extends BaseTestCase {
    public function testCreateMt103Document(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::Euro,
            amount: 1000.50
        );

        $orderingCustomer = new Party(
            account: 'DE89370400440532013000',
            name: 'Max Mustermann',
            addressLine1: 'Musterstraße 1',
            addressLine2: '12345 Berlin'
        );

        $beneficiary = new Party(
            account: 'GB82WEST12345698765432',
            name: 'John Smith',
            addressLine1: '123 Main Street',
            addressLine2: 'London EC1A 1AA'
        );

        $document = new Mt103Document(
            sendersReference: 'REF2025051201',
            transferDetails: $transferDetails,
            orderingCustomer: $orderingCustomer,
            beneficiary: $beneficiary,
            bankOperationCode: BankOperationCode::CRED,
            chargesCode: ChargesCode::SHA,
            remittanceInfo: 'Invoice 12345'
        );

        $this->assertEquals(MtType::MT103, $document->getMtType());
        $this->assertEquals('REF2025051201', $document->getSendersReference());
        $this->assertEquals(1000.50, $document->getAmount());
        $this->assertEquals(CurrencyCode::Euro, $document->getCurrency());
        $this->assertEquals(BankOperationCode::CRED, $document->getBankOperationCode());
        $this->assertEquals(ChargesCode::SHA, $document->getChargesCode());
        $this->assertEquals('Invoice 12345', $document->getRemittanceInfo());
    }

    public function testMt103ToString(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::Euro,
            amount: 39.42
        );

        $orderingCustomer = new Party(
            account: '000000041000045',
            name: 'ABC LTD',
            addressLine1: 'LONDON'
        );

        $beneficiary = new Party(
            account: '112345679',
            name: 'GSIL'
        );

        $document = new Mt103Document(
            sendersReference: 'GS0DUGH121TSDG0',
            transferDetails: $transferDetails,
            orderingCustomer: $orderingCustomer,
            beneficiary: $beneficiary,
            bankOperationCode: BankOperationCode::CRED,
            chargesCode: ChargesCode::OUR,
            remittanceInfo: 'TR-PGTD0N'
        );

        $output = (string) $document;

        $this->assertStringContainsString(':20:GS0DUGH121TSDG0', $output);
        $this->assertStringContainsString(':23B:CRED', $output);
        $this->assertStringContainsString(':32A:250512EUR39,42', $output);
        $this->assertStringContainsString(':50K:', $output);
        $this->assertStringContainsString('ABC LTD', $output);
        $this->assertStringContainsString(':59:', $output);
        $this->assertStringContainsString(':70:TR-PGTD0N', $output);
        $this->assertStringContainsString(':71A:OUR', $output);
    }

    public function testMt103WithBankParties(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::USDollar,
            amount: 330.21
        );

        $orderingCustomer = new Party(
            account: '105334213',
            name: 'Goldman Sachs Bank USA'
        );

        $beneficiary = new Party(
            account: '145254512',
            name: 'GP-GSBI',
            addressLine1: 'US New York 10282'
        );

        $orderingInstitution = new Party(bic: 'GSCRUS33XXX');
        $intermediaryBank = new Party(bic: 'IRVTUS3NXXX');
        $accountWithBank = new Party(bic: 'BOFAUS3NXXX');

        $document = new Mt103Document(
            sendersReference: 'GS0DX1QH8IU8IR2',
            transferDetails: $transferDetails,
            orderingCustomer: $orderingCustomer,
            beneficiary: $beneficiary,
            orderingInstitution: $orderingInstitution,
            intermediaryInstitution: $intermediaryBank,
            accountWithInstitution: $accountWithBank,
            chargesCode: ChargesCode::OUR
        );

        $output = (string) $document;

        $this->assertStringContainsString(':52A:GSCRUS33XXX', $output);
        $this->assertStringContainsString(':56A:IRVTUS3NXXX', $output);
        $this->assertStringContainsString(':57A:BOFAUS3NXXX', $output);
    }

    public function testMt103StpCapable(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::Euro,
            amount: 100.00
        );

        // Mit Konten → STP-fähig
        $withAccounts = new Mt103Document(
            sendersReference: 'REF001',
            transferDetails: $transferDetails,
            orderingCustomer: new Party(account: 'DE89370400440532013000'),
            beneficiary: new Party(account: 'GB82WEST12345698765432')
        );

        $this->assertTrue($withAccounts->isStpCapable());

        // Ohne Konten → nicht STP-fähig
        $withoutAccounts = new Mt103Document(
            sendersReference: 'REF002',
            transferDetails: $transferDetails,
            orderingCustomer: new Party(name: 'Max Mustermann'),
            beneficiary: new Party(name: 'John Smith')
        );

        $this->assertFalse($withoutAccounts->isStpCapable());
    }

    public function testMt103WithCurrencyConversion(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::Euro,
            amount: 85.00,
            originalCurrency: CurrencyCode::USDollar,
            originalAmount: 100.00,
            exchangeRate: 0.85
        );

        $document = new Mt103Document(
            sendersReference: 'FX-REF-001',
            transferDetails: $transferDetails,
            orderingCustomer: new Party(account: 'US123456'),
            beneficiary: new Party(account: 'DE123456')
        );

        $this->assertTrue($document->getTransferDetails()->hasCurrencyConversion());

        $output = (string) $document;
        $this->assertStringContainsString(':33B:USD100,00', $output);
        $this->assertStringContainsString(':36:', $output);
    }

    public function testMt103WithChargesCode(): void {
        $transferDetails = new TransferDetails(
            valueDate: new DateTimeImmutable('2025-05-12'),
            currency: CurrencyCode::Euro,
            amount: 500.00
        );

        $document = new Mt103Document(
            sendersReference: 'CHG-REF-001',
            transferDetails: $transferDetails,
            orderingCustomer: new Party(account: 'DE123'),
            beneficiary: new Party(account: 'GB456'),
            chargesCode: ChargesCode::BEN
        );

        $updated = $document->withChargesCode(ChargesCode::OUR);

        $this->assertEquals(ChargesCode::BEN, $document->getChargesCode());
        $this->assertEquals(ChargesCode::OUR, $updated->getChargesCode());
    }
}
