<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt10xParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Enums\Common\Banking\BankOperationCode;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\Mt10xParser;
use Tests\Contracts\BaseTestCase;

final class Mt10xParserTest extends BaseTestCase {
    private string $samplesPath;

    protected function setUp(): void {
        parent::setUp();
        $this->samplesPath = dirname(__DIR__, 2) . '/.samples/Banking/SWIFT/';
    }

    public function testParseMt103FromString(): void {
        $textBlock = <<<MT103
:20:FTR0123456789012
:23B:CRED
:32A:250512EUR12345,67
:50K:/DE89370400440532013000
Max Mustermann
Musterstraße 1
12345 Musterstadt
:59:/DE89370400440532013001
Firma ABC GmbH
Hauptstraße 123
98765 Beispielstadt
:70:Rechnung 2025-001
:71A:SHA
MT103;

        $mt103 = Mt10xParser::parseMt103($textBlock);

        $this->assertEquals('FTR0123456789012', $mt103->getSendersReference());
        $this->assertEquals(BankOperationCode::CRED, $mt103->getBankOperationCode());
        $this->assertEquals(CurrencyCode::Euro, $mt103->getTransferDetails()->getCurrency());
        $this->assertEquals(12345.67, $mt103->getTransferDetails()->getAmount());
        $this->assertEquals('250512', $mt103->getTransferDetails()->getValueDate()->format('ymd'));
        $this->assertEquals('Max Mustermann', $mt103->getOrderingCustomer()->getName());
        $this->assertEquals('Firma ABC GmbH', $mt103->getBeneficiary()->getName());
        $this->assertEquals('Rechnung 2025-001', $mt103->getRemittanceInfo());
        $this->assertEquals(ChargesCode::SHA, $mt103->getChargesCode());
    }

    public function testParseMt103WithBIC(): void {
        $textBlock = <<<MT103
:20:TESTREF123
:23B:CRED
:32A:250101USD500,00
:50K:/123456789
Ordering Customer
:52A:DEUTDEFF
:57A:BOFAUS3N
:59:/US123456789
Beneficiary Name
:70:Payment
:71A:OUR
MT103;

        $mt103 = Mt10xParser::parseMt103($textBlock);

        $this->assertEquals('DEUTDEFF', $mt103->getOrderingInstitution()?->getBic());
        $this->assertEquals('BOFAUS3N', $mt103->getAccountWithInstitution()?->getBic());
        $this->assertEquals(ChargesCode::OUR, $mt103->getChargesCode());
    }

    public function testParseMt103WithCurrencyConversion(): void {
        $textBlock = <<<MT103
:20:CONVREF001
:23B:CRED
:32A:250615EUR10000,00
:33B:USD11500,00
:36:1,15
:50K:/DE12345678901234567890
Sender Name
:59:/US123456789
Receiver Name
:71A:SHA
MT103;

        $mt103 = Mt10xParser::parseMt103($textBlock);

        $this->assertTrue($mt103->getTransferDetails()->hasCurrencyConversion() || $mt103->getTransferDetails()->getExchangeRate() !== null);
        $this->assertEquals(1.15, $mt103->getTransferDetails()->getExchangeRate());
        $this->assertEquals(CurrencyCode::Euro, $mt103->getTransferDetails()->getCurrency());
        $this->assertEquals(10000.00, $mt103->getTransferDetails()->getAmount());
    }

    public function testParseMt101FromString(): void {
        $textBlock = <<<MT101
:20:BATCHREF001
:28D:1/1
:50H:/123456789
Ordering Company
Business District
:52A:DEUTDEFF
:30:250512
:21:TXN001
:32B:EUR1000,00
:57A:COBADEFF
:59:/DE89370400440532013000
Beneficiary One
:71A:SHA
:21:TXN002
:32B:EUR2500,50
:57A:INGDDEFF
:59:/DE89370400440532013001
Beneficiary Two
:70:Invoice 2025
:71A:OUR
MT101;

        $mt101 = Mt10xParser::parseMt101($textBlock);

        $this->assertEquals('BATCHREF001', $mt101->getSendersReference());
        $this->assertEquals('1/1', $mt101->getMessageIndex());
        $this->assertEquals('Ordering Company', $mt101->getOrderingCustomer()->getName());
        $this->assertEquals('DEUTDEFF', $mt101->getOrderingInstitution()?->getBic());

        $transactions = $mt101->getTransactions();
        $this->assertCount(2, $transactions);

        $this->assertEquals('TXN001', $transactions[0]->getTransactionReference());
        $this->assertEquals(1000.00, $transactions[0]->getTransferDetails()->getAmount());
        $this->assertEquals('Beneficiary One', $transactions[0]->getBeneficiary()->getName());

        $this->assertEquals('TXN002', $transactions[1]->getTransactionReference());
        $this->assertEquals(2500.50, $transactions[1]->getTransferDetails()->getAmount());
        $this->assertEquals('Invoice 2025', $transactions[1]->getRemittanceInfo());

        $this->assertEquals(3500.50, $mt101->getTotalAmount());
    }

    public function testParseMt103DirectlyFromString(): void {
        $textBlock = <<<MT103
:20:MSGREF001
:23B:CRED
:32A:250701GBP999,99
:50K:/GB12ABCD12345612345678
UK Company Ltd
:59:/DE12345678901234567890
German Company
:71A:BEN
MT103;

        $mt103 = Mt10xParser::parseMt103($textBlock);

        $this->assertEquals(MtType::MT103, $mt103->getMtType());
        $this->assertEquals('MSGREF001', $mt103->getSendersReference());
        $this->assertEquals(ChargesCode::BEN, $mt103->getChargesCode());
    }

    public function testParseMt101DirectlyFromString(): void {
        $textBlock = <<<MT101
:20:BATCH101
:28D:1/2
:50K:/12345
Test Customer
:30:250801
:21:SINGLE
:32B:EUR100,00
:59:/99999
Single Beneficiary
:71A:SHA
MT101;

        $mt101 = Mt10xParser::parseMt101($textBlock);

        $this->assertEquals(MtType::MT101, $mt101->getMtType());
        $this->assertCount(1, $mt101->getTransactions());
        $this->assertEquals('1/2', $mt101->getMessageIndex());
    }

    public function testParseMt103WithAllBankFields(): void {
        $textBlock = <<<MT103
:20:FULLREF001
:23B:CRED
:32A:250901EUR5000,00
:50K:/DE12345678901234567890
Ordering Customer
:52A:DEUTDEFF
:53A:COBADEFF
:56A:BOFAUS3N
:57A:INGDDEFF
:59:/US123456789
Beneficiary Customer
:70:Full Bank Chain Test
:71A:SHA
:72:/REC/Additional Info
MT103;

        $mt103 = Mt10xParser::parseMt103($textBlock);

        $this->assertNotNull($mt103->getOrderingInstitution());
        $this->assertEquals('DEUTDEFF', $mt103->getOrderingInstitution()->getBic());

        $this->assertNotNull($mt103->getSendersCorrespondent());
        $this->assertEquals('COBADEFF', $mt103->getSendersCorrespondent()->getBic());

        $this->assertNotNull($mt103->getIntermediaryInstitution());
        $this->assertEquals('BOFAUS3N', $mt103->getIntermediaryInstitution()->getBic());

        $this->assertNotNull($mt103->getAccountWithInstitution());
        $this->assertEquals('INGDDEFF', $mt103->getAccountWithInstitution()->getBic());

        $this->assertNotNull($mt103->getSenderToReceiverInfo());
        $this->assertStringContainsString('/REC/Additional Info', $mt103->getSenderToReceiverInfo());
    }

    public function testParseMt101SingleTransaction(): void {
        $textBlock = <<<MT101
:20:SINGLEREF
:28D:1/1
:50K:/DE89370400440532013000
Test Company
:30:250601
:21:TXN-SINGLE
:32B:CHF5000,00
:59:/CH1234567890123456789
Swiss Beneficiary
:71A:OUR
MT101;

        $mt101 = Mt10xParser::parseMt101($textBlock);

        $this->assertCount(1, $mt101->getTransactions());
        $this->assertEquals('TXN-SINGLE', $mt101->getTransactions()[0]->getTransactionReference());
        $this->assertEquals(CurrencyCode::SwissFranc, $mt101->getTransactions()[0]->getTransferDetails()->getCurrency());
        $this->assertEquals(5000.00, $mt101->getTransactions()[0]->getTransferDetails()->getAmount());
    }
}
