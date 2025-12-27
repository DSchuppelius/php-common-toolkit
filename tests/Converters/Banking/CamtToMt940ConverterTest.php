<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtToMt940ConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\CamtToMt940Converter;
use CommonToolkit\Entities\Common\Banking\Camt\Balance as CamtBalance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction as Camt052Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference as Camt053Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction as Camt054Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class CamtToMt940ConverterTest extends BaseTestCase {
    private Camt053Document $camt053Document;
    private Camt052Document $camt052Document;
    private Camt054Document $camt054Document;

    protected function setUp(): void {
        parent::setUp();

        // CAMT.053 Setup
        $openingBalance = new CamtBalance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            1000.00,
            'PRCD'
        );

        $closingBalance = new CamtBalance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            1150.00,
            'CLBD'
        );

        $this->camt053Document = new Camt053Document(
            id: 'CAMT053-2025-001',
            creationDateTime: new DateTimeImmutable('2025-01-31'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            accountOwner: 'Max Mustermann',
            servicerBic: 'COBADEFFXXX',
            messageId: 'MSG-2025-001',
            sequenceNumber: '00042',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        // Transaktionen hinzufügen
        $reference1 = new Camt053Reference(
            endToEndId: 'END2END-001',
            mandateId: 'MANDATE-001',
            creditorId: 'DE98ZZZ09999999999'
        );

        $transaction1 = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-01-15'),
            valutaDate: new DateTimeImmutable('2025-01-15'),
            amount: 200.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT,
            reference: $reference1,
            entryReference: 'ENTRY-001',
            accountServicerReference: 'BANK-REF-001',
            status: 'BOOK',
            isReversal: false,
            purpose: 'Gehalt Januar 2025',
            additionalInfo: null,
            transactionCode: 'NTRF',
            counterpartyName: 'Arbeitgeber GmbH',
            counterpartyIban: 'DE12345678901234567890',
            counterpartyBic: 'DEUTDEDBXXX'
        );

        $reference2 = new Camt053Reference(
            endToEndId: 'END2END-002'
        );

        $transaction2 = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-01-20'),
            valutaDate: new DateTimeImmutable('2025-01-20'),
            amount: 50.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::DEBIT,
            reference: $reference2,
            entryReference: 'ENTRY-002',
            accountServicerReference: null,
            status: 'BOOK',
            isReversal: false,
            purpose: 'Stromkosten',
            transactionCode: 'NCHG'
        );

        $this->camt053Document->addEntry($transaction1);
        $this->camt053Document->addEntry($transaction2);

        // CAMT.052 Setup (Intraday)
        $this->camt052Document = new Camt052Document(
            id: 'CAMT052-2025-001',
            creationDateTime: new DateTimeImmutable('2025-01-15 14:30:00'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            accountOwner: 'Max Mustermann',
            servicerBic: 'COBADEFFXXX',
            messageId: 'MSG-INTRA-001',
            sequenceNumber: '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $intradayTransaction = new Camt052Transaction(
            bookingDate: new DateTimeImmutable('2025-01-15'),
            valutaDate: new DateTimeImmutable('2025-01-15'),
            amount: 75.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT,
            entryReference: 'INTRA-001'
        );
        $this->camt052Document->addEntry($intradayTransaction);

        // CAMT.054 Setup (Notifications)
        $this->camt054Document = new Camt054Document(
            id: 'CAMT054-2025-001',
            creationDateTime: new DateTimeImmutable('2025-01-15'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            accountOwner: 'Max Mustermann',
            servicerBic: 'COBADEFFXXX',
            messageId: 'MSG-NOTIFY-001',
            sequenceNumber: '00001'
        );

        $notifyTransaction = new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-01-15'),
            valutaDate: new DateTimeImmutable('2025-01-15'),
            amount: 99.99,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::DEBIT,
            entryReference: 'NOTIFY-001'
        );
        $this->camt054Document->addEntry($notifyTransaction);
    }

    public function testConvertFromCamt053(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);

        $this->assertInstanceOf(Mt940Document::class, $mt940);
        $this->assertEquals('DE89370400440532013000', $mt940->getAccountId());
        $this->assertEquals('00042', $mt940->getStatementNumber());
        $this->assertCount(2, $mt940->getTransactions());

        // Opening Balance
        $openingBalance = $mt940->getOpeningBalance();
        $this->assertEquals(1000.00, $openingBalance->getAmount());
        $this->assertTrue($openingBalance->isCredit());
        $this->assertEquals('F', $openingBalance->getType());

        // Closing Balance
        $closingBalance = $mt940->getClosingBalance();
        $this->assertEquals(1150.00, $closingBalance->getAmount());
        $this->assertTrue($closingBalance->isCredit());
        $this->assertEquals('F', $closingBalance->getType());
    }

    public function testConvertFromCamt052(): void {
        $mt940 = CamtToMt940Converter::fromCamt052($this->camt052Document);

        $this->assertInstanceOf(Mt940Document::class, $mt940);
        $this->assertEquals('DE89370400440532013000', $mt940->getAccountId());
        $this->assertCount(1, $mt940->getTransactions());

        $transaction = $mt940->getTransactions()[0];
        $this->assertEquals(75.00, $transaction->getAmount());
        $this->assertTrue($transaction->isCredit());
    }

    public function testConvertFromCamt054(): void {
        $mt940 = CamtToMt940Converter::fromCamt054($this->camt054Document);

        $this->assertInstanceOf(Mt940Document::class, $mt940);
        $this->assertCount(1, $mt940->getTransactions());

        // CAMT.054 hat keine Salden, daher Zero-Balances
        $this->assertEquals(0.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals(0.00, $mt940->getClosingBalance()->getAmount());

        $transaction = $mt940->getTransactions()[0];
        $this->assertEquals(99.99, $transaction->getAmount());
        $this->assertTrue($transaction->isDebit());
    }

    public function testGenericConvert(): void {
        $mt940From053 = CamtToMt940Converter::convert($this->camt053Document);
        $mt940From052 = CamtToMt940Converter::convert($this->camt052Document);
        $mt940From054 = CamtToMt940Converter::convert($this->camt054Document);

        $this->assertInstanceOf(Mt940Document::class, $mt940From053);
        $this->assertInstanceOf(Mt940Document::class, $mt940From052);
        $this->assertInstanceOf(Mt940Document::class, $mt940From054);
    }

    public function testTransactionConversion(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);
        $transactions = $mt940->getTransactions();

        // Erste Transaktion (Credit)
        $txn1 = $transactions[0];
        $this->assertEquals(200.00, $txn1->getAmount());
        $this->assertTrue($txn1->isCredit());
        $this->assertEquals('TRF', $txn1->getReference()->getTransactionCode());

        // Verwendungszweck enthält SEPA-Tags
        $purpose = $txn1->getPurpose();
        $this->assertStringContainsString('EREF+END2END-001', $purpose);
        $this->assertStringContainsString('MREF+MANDATE-001', $purpose);
        $this->assertStringContainsString('CRED+DE98ZZZ09999999999', $purpose);
        $this->assertStringContainsString('NAME+Arbeitgeber GmbH', $purpose);
        $this->assertStringContainsString('IBAN+DE12345678901234567890', $purpose);
        $this->assertStringContainsString('BIC+DEUTDEDBXXX', $purpose);
        $this->assertStringContainsString('SVWZ+Gehalt Januar 2025', $purpose);

        // Zweite Transaktion (Debit)
        $txn2 = $transactions[1];
        $this->assertEquals(50.00, $txn2->getAmount());
        $this->assertTrue($txn2->isDebit());
        $this->assertEquals('CHG', $txn2->getReference()->getTransactionCode()); // NCHG → CHG
    }

    public function testCustomReferenceId(): void {
        $customRefId = 'CUSTOM-REF-123';
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document, $customRefId);

        $this->assertEquals($customRefId, $mt940->getReferenceId());
    }

    public function testConvertMultiple(): void {
        $documents = [$this->camt053Document, $this->camt053Document];
        $results = CamtToMt940Converter::convertMultipleFromCamt053($documents);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Mt940Document::class, $results[0]);
        $this->assertInstanceOf(Mt940Document::class, $results[1]);
    }

    public function testMt940StringOutput(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);
        $output = (string) $mt940;

        // MT940 Felder prüfen
        $this->assertStringContainsString(':20:', $output);
        $this->assertStringContainsString(':25:', $output);
        $this->assertStringContainsString(':28C:', $output);
        $this->assertStringContainsString(':60F:', $output);
        $this->assertStringContainsString(':61:', $output);
        $this->assertStringContainsString(':62F:', $output);
        $this->assertStringContainsString('DE89370400440532013000', $output);
    }

    public function testTransactionCodeMapping(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);
        $transactions = $mt940->getTransactions();

        // NTRF → TRF
        $this->assertEquals('TRF', $transactions[0]->getReference()->getTransactionCode());
        // NCHG → CHG
        $this->assertEquals('CHG', $transactions[1]->getReference()->getTransactionCode());
    }

    public function testReferenceIdGeneration(): void {
        // ID wird auf max. 16 Zeichen gekürzt
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);
        $refId = $mt940->getReferenceId();

        $this->assertLessThanOrEqual(16, strlen($refId));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-]+$/', $refId);
    }

    public function testCamt054WithProvidedBalances(): void {
        $customOpening = new \CommonToolkit\Entities\Common\Banking\Mt9\Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            500.00,
            'F'
        );

        $customClosing = new \CommonToolkit\Entities\Common\Banking\Mt9\Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-15'),
            CurrencyCode::Euro,
            400.01,
            'F'
        );

        $mt940 = CamtToMt940Converter::fromCamt054(
            $this->camt054Document,
            null,
            $customOpening,
            $customClosing
        );

        $this->assertEquals(500.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals(400.01, $mt940->getClosingBalance()->getAmount());
    }

    public function testDatePreservation(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);
        $transaction = $mt940->getTransactions()[0];

        $this->assertEquals('2025-01-15', $transaction->getDate()->format('Y-m-d'));
        $this->assertEquals('2025-01-15', $transaction->getValutaDate()->format('Y-m-d'));
    }

    public function testCurrencyPreservation(): void {
        $mt940 = CamtToMt940Converter::fromCamt053($this->camt053Document);

        $this->assertEquals(CurrencyCode::Euro, $mt940->getCurrency());
        $this->assertEquals(CurrencyCode::Euro, $mt940->getTransactions()[0]->getCurrency());
    }
}
