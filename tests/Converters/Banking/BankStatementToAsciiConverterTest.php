<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankStatementToAsciiConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\BankStatementToAsciiConverter;
use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference as Camt053Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance as Mt9Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference as Mt9Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class BankStatementToAsciiConverterTest extends BaseTestCase {
    public function testConvertMt940ToAscii(): void {
        $openingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-05-01'),
            amount: 10000.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $closingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-05-01'),
            amount: 11234.56,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $transaction1 = new Mt940Transaction(
            bookingDate: new DateTimeImmutable('2025-05-01'),
            valutaDate: new DateTimeImmutable('2025-05-01'),
            amount: 1500.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro,
            reference: new Mt9Reference('TRF', 'REF123'),
            purpose: '?20Lohnzahlung Mai 2025?21Mitarbeiter ID 12345'
        );

        $transaction2 = new Mt940Transaction(
            bookingDate: new DateTimeImmutable('2025-05-01'),
            valutaDate: new DateTimeImmutable('2025-05-01'),
            amount: 265.44,
            creditDebit: CreditDebit::DEBIT,
            currency: CurrencyCode::Euro,
            reference: new Mt9Reference('DDT', 'REF456'),
            purpose: '?20Lastschrift Stromrechnung?21EVN Energie GmbH'
        );

        $document = new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'STMT20250501',
            statementNumber: '001/001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: [$transaction1, $transaction2]
        );

        $converter = new BankStatementToAsciiConverter();
        $ascii = $converter->fromMt940($document);

        // Prüfe Header
        $this->assertStringContainsString('KONTOAUSZUG (MT940)', $ascii);

        // Prüfe Kontoinformationen
        $this->assertStringContainsString('DE89370400440532013000', $ascii);
        $this->assertStringContainsString('STMT20250501', $ascii);
        $this->assertStringContainsString('001/001', $ascii);

        // Prüfe Salden
        $this->assertStringContainsString('10.000,00', $ascii);
        $this->assertStringContainsString('11.234,56', $ascii);

        // Prüfe Transaktionen
        $this->assertStringContainsString('1.500,00', $ascii);
        $this->assertStringContainsString('265,44', $ascii);

        // Prüfe Verwendungszweck
        $this->assertStringContainsString('Lohnzahlung Mai 2025', $ascii);
    }

    public function testConvertCamt053ToAscii(): void {
        $openingBalance = new Balance(
            type: 'PRCD',
            date: new DateTimeImmutable('2025-05-01'),
            amount: 5000.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        );

        $closingBalance = new Balance(
            type: 'CLBD',
            date: new DateTimeImmutable('2025-05-01'),
            amount: 5500.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        );

        $document = new Camt053Document(
            id: 'CAMT053-20250501',
            creationDateTime: new DateTimeImmutable('2025-05-01 12:00:00'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            accountOwner: 'Max Mustermann',
            servicerBic: 'COBADEFFXXX',
            messageId: 'MSG-001',
            sequenceNumber: '001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $reference = new Camt053Reference(
            endToEndId: 'E2E-12345',
            mandateId: 'MNDT-001',
            creditorId: 'DE98ZZZ09999999999',
            accountServicerReference: 'ASR001'
        );

        $transaction = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-05-01'),
            valutaDate: new DateTimeImmutable('2025-05-01'),
            amount: 500.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT,
            reference: $reference,
            purpose: 'Überweisung Rechnung 2025-001',
            counterpartyName: 'Firma ABC GmbH',
            counterpartyIban: 'DE12345678901234567890'
        );

        $document->addEntry($transaction);

        $converter = new BankStatementToAsciiConverter();
        $ascii = $converter->fromCamt053($document);

        // Prüfe Header
        $this->assertStringContainsString('KONTOAUSZUG (CAMT.053)', $ascii);

        // Prüfe Kontoinformationen
        $this->assertStringContainsString('DE89370400440532013000', $ascii);
        $this->assertStringContainsString('Max Mustermann', $ascii);

        // Prüfe Salden
        $this->assertStringContainsString('5.000,00', $ascii);
        $this->assertStringContainsString('5.500,00', $ascii);

        // Prüfe SEPA-Details
        $this->assertStringContainsString('E2E-12345', $ascii);
        $this->assertStringContainsString('MNDT-001', $ascii);
        $this->assertStringContainsString('DE98ZZZ09999999999', $ascii);
    }

    public function testCustomLineWidth(): void {
        $openingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 100.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $closingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 100.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $document = new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'REF001',
            statementNumber: '001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $converter = new BankStatementToAsciiConverter(lineWidth: 120);
        $ascii = $converter->fromMt940($document);

        // Prüfe dass Trennlinien länger sind
        $this->assertStringContainsString(str_repeat('=', 120), $ascii);
    }

    public function testWindowsLineBreaks(): void {
        $openingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 100.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $closingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 100.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $document = new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'REF001',
            statementNumber: '001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $converter = new BankStatementToAsciiConverter(lineBreak: "\r\n");
        $ascii = $converter->fromMt940($document);

        $this->assertStringContainsString("\r\n", $ascii);
    }

    public function testDisableSepaDetails(): void {
        $openingBalance = new Balance(
            type: 'PRCD',
            date: new DateTimeImmutable('2025-05-01'),
            amount: 1000.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        );

        $closingBalance = new Balance(
            type: 'CLBD',
            date: new DateTimeImmutable('2025-05-01'),
            amount: 1000.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        );

        $document = new Camt053Document(
            id: 'CAMT053-001',
            creationDateTime: new DateTimeImmutable(),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $reference = new Camt053Reference(
            endToEndId: 'E2E-HIDDEN',
            mandateId: 'MNDT-HIDDEN',
            creditorId: 'CRED-HIDDEN'
        );

        $transaction = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-05-01'),
            valutaDate: new DateTimeImmutable('2025-05-01'),
            amount: 100.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT,
            reference: $reference
        );

        $document->addEntry($transaction);

        $converter = new BankStatementToAsciiConverter(includeSepaDetails: false);
        $ascii = $converter->fromCamt053($document);

        // SEPA-Detail-Zeilen sollten nicht angezeigt werden
        $this->assertStringNotContainsString('End-to-End-ID:', $ascii);
        $this->assertStringNotContainsString('Mandats-ID:', $ascii);
        $this->assertStringNotContainsString('Gläubiger-ID:', $ascii);
    }

    public function testSummaryCalculation(): void {
        $openingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 1000.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $closingBalance = new Mt9Balance(
            date: new DateTimeImmutable('2025-01-01'),
            amount: 1350.00,
            creditDebit: CreditDebit::CREDIT,
            currency: CurrencyCode::Euro
        );

        $transactions = [
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-01-01'),
                valutaDate: new DateTimeImmutable('2025-01-01'),
                amount: 500.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Mt9Reference('TRF', 'REF1')
            ),
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-01-01'),
                valutaDate: new DateTimeImmutable('2025-01-01'),
                amount: 150.00,
                creditDebit: CreditDebit::DEBIT,
                currency: CurrencyCode::Euro,
                reference: new Mt9Reference('DDT', 'REF2')
            ),
        ];

        $document = new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'REF001',
            statementNumber: '001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions
        );

        $converter = new BankStatementToAsciiConverter();
        $ascii = $converter->fromMt940($document);

        // Prüfe Summen
        $this->assertStringContainsString('Summe Gutschriften', $ascii);
        $this->assertStringContainsString('500,00', $ascii);
        $this->assertStringContainsString('Summe Belastungen', $ascii);
        $this->assertStringContainsString('150,00', $ascii);
    }
}