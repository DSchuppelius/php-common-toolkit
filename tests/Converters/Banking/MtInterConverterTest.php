<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtInterConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\MtInterConverter;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Type941\Document as Mt941Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Document as Mt942Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Transaction as Mt942Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für MtInterConverter - Konvertierung zwischen MT940/MT941/MT942.
 */
final class MtInterConverterTest extends BaseTestCase {
    /**
     * Test: MT940 → MT941 (nur Salden, keine Transaktionen)
     */
    public function testMt940ToMt941(): void {
        $mt940 = $this->createMt940WithTransactions();

        $mt941 = MtInterConverter::mt940ToMt941($mt940);

        // Kontodaten müssen identisch sein
        $this->assertEquals($mt940->getAccountId(), $mt941->getAccountId());
        $this->assertEquals($mt940->getReferenceId(), $mt941->getReferenceId());
        $this->assertEquals($mt940->getStatementNumber(), $mt941->getStatementNumber());

        // Salden müssen identisch sein
        $this->assertEquals($mt940->getOpeningBalance()->getAmount(), $mt941->getOpeningBalance()->getAmount());
        $this->assertEquals($mt940->getClosingBalance()->getAmount(), $mt941->getClosingBalance()->getAmount());

        // MT941 hat keine Transaktionen (keine getTransactions-Methode)
        $this->assertInstanceOf(Mt941Document::class, $mt941);
    }

    /**
     * Test: MT940 → MT942 (Interim-Format)
     */
    public function testMt940ToMt942(): void {
        $mt940 = $this->createMt940WithTransactions();

        $mt942 = MtInterConverter::mt940ToMt942($mt940);

        // Kontodaten müssen identisch sein
        $this->assertEquals($mt940->getAccountId(), $mt942->getAccountId());
        $this->assertEquals($mt940->getReferenceId(), $mt942->getReferenceId());

        // Salden müssen identisch sein
        $this->assertEquals($mt940->getOpeningBalance()->getAmount(), $mt942->getOpeningBalance()->getAmount());
        $this->assertEquals($mt940->getClosingBalance()->getAmount(), $mt942->getClosingBalance()->getAmount());

        // Transaktionsanzahl muss identisch sein
        $this->assertCount(count($mt940->getTransactions()), $mt942->getTransactions());

        // Transaktionsbeträge prüfen
        $originalAmounts = array_map(fn($t) => $t->getAmount(), $mt940->getTransactions());
        $convertedAmounts = array_map(fn($t) => $t->getAmount(), $mt942->getTransactions());
        $this->assertEquals($originalAmounts, $convertedAmounts);
    }

    /**
     * Test: MT942 → MT940 (Final-Format)
     */
    public function testMt942ToMt940(): void {
        $mt942 = $this->createMt942WithTransactions();

        $mt940 = MtInterConverter::mt942ToMt940($mt942);

        // Kontodaten müssen identisch sein
        $this->assertEquals($mt942->getAccountId(), $mt940->getAccountId());
        $this->assertEquals($mt942->getReferenceId(), $mt940->getReferenceId());

        // Salden müssen identisch sein
        $this->assertEquals($mt942->getOpeningBalance()->getAmount(), $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals($mt942->getClosingBalance()->getAmount(), $mt940->getClosingBalance()->getAmount());

        // Transaktionsanzahl muss identisch sein
        $this->assertCount(count($mt942->getTransactions()), $mt940->getTransactions());
    }

    /**
     * Test: MT942 ohne Opening Balance → MT940 (Berechnung aus Closing - Transaktionen)
     */
    public function testMt942ToMt940WithoutOpeningBalance(): void {
        // MT942 ohne Opening Balance erstellen
        // Closing: 1500 EUR (Credit)
        // Transactions: +500 (Credit), -200 (Debit)
        // Expected Opening: 1500 - 500 + 200 = 1200 EUR

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            1500.00
        );

        $transactions = [
            new Mt942Transaction(
                bookingDate: new DateTimeImmutable('2025-01-15'),
                valutaDate: new DateTimeImmutable('2025-01-15'),
                amount: 500.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'CREDIT-001')
            ),
            new Mt942Transaction(
                bookingDate: new DateTimeImmutable('2025-01-20'),
                valutaDate: new DateTimeImmutable('2025-01-20'),
                amount: 200.00,
                creditDebit: CreditDebit::DEBIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'DEBIT-001')
            ),
        ];

        $mt942 = new Mt942Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'MT942-NO-OPEN',
            statementNumber: '00001',
            closingBalance: $closingBalance,
            transactions: $transactions,
            openingBalance: null // Kein Opening Balance!
        );

        $mt940 = MtInterConverter::mt942ToMt940($mt942);

        // Opening Balance wurde berechnet
        $this->assertNotNull($mt940->getOpeningBalance());
        $this->assertEquals(1200.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals(CreditDebit::CREDIT, $mt940->getOpeningBalance()->getCreditDebit());

        // Rechnerische Konsistenz prüfen
        $totalCredits = 0.0;
        $totalDebits = 0.0;
        foreach ($mt940->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredits += $txn->getAmount();
            } else {
                $totalDebits += $txn->getAmount();
            }
        }

        $calculatedClosing = $mt940->getOpeningBalance()->getAmount() + $totalCredits - $totalDebits;
        $this->assertEquals($mt940->getClosingBalance()->getAmount(), $calculatedClosing);
    }

    /**
     * Test: MT941 → MT940 (leeres Dokument ohne Transaktionen)
     */
    public function testMt941ToMt940(): void {
        $mt941 = $this->createMt941();

        $mt940 = MtInterConverter::mt941ToMt940($mt941);

        // Kontodaten müssen identisch sein
        $this->assertEquals($mt941->getAccountId(), $mt940->getAccountId());
        $this->assertEquals($mt941->getReferenceId(), $mt940->getReferenceId());

        // Salden müssen identisch sein
        $this->assertEquals($mt941->getOpeningBalance()->getAmount(), $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals($mt941->getClosingBalance()->getAmount(), $mt940->getClosingBalance()->getAmount());

        // MT940 aus MT941 hat keine Transaktionen
        $this->assertCount(0, $mt940->getTransactions());
    }

    /**
     * Test: MT940 → MT942 → MT940 Roundtrip
     */
    public function testMt940ToMt942ToMt940Roundtrip(): void {
        $originalMt940 = $this->createMt940WithTransactions();

        $mt942 = MtInterConverter::mt940ToMt942($originalMt940);
        $roundtripMt940 = MtInterConverter::mt942ToMt940($mt942);

        // Kontodaten müssen erhalten bleiben
        $this->assertEquals($originalMt940->getAccountId(), $roundtripMt940->getAccountId());
        $this->assertEquals($originalMt940->getReferenceId(), $roundtripMt940->getReferenceId());
        $this->assertEquals($originalMt940->getStatementNumber(), $roundtripMt940->getStatementNumber());

        // Salden müssen erhalten bleiben
        $this->assertEquals(
            $originalMt940->getOpeningBalance()->getAmount(),
            $roundtripMt940->getOpeningBalance()->getAmount()
        );
        $this->assertEquals(
            $originalMt940->getClosingBalance()->getAmount(),
            $roundtripMt940->getClosingBalance()->getAmount()
        );

        // Transaktionsanzahl muss erhalten bleiben
        $this->assertCount(
            count($originalMt940->getTransactions()),
            $roundtripMt940->getTransactions()
        );

        // Transaktionsbeträge müssen erhalten bleiben
        for ($i = 0; $i < count($originalMt940->getTransactions()); $i++) {
            $this->assertEquals(
                $originalMt940->getTransactions()[$i]->getAmount(),
                $roundtripMt940->getTransactions()[$i]->getAmount()
            );
            $this->assertEquals(
                $originalMt940->getTransactions()[$i]->getCreditDebit(),
                $roundtripMt940->getTransactions()[$i]->getCreditDebit()
            );
        }
    }

    /**
     * Test: Summenprüfung bei MT-Format-Konvertierung
     */
    public function testBalanceConsistencyAcrossMtFormats(): void {
        // MT940 mit rechnerisch konsistenten Werten
        // Opening: 1000 EUR
        // + 500 (Credit), - 200 (Debit), + 100 (Credit)
        // = 1400 EUR (Closing)

        $openingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-31'),
            CurrencyCode::Euro,
            1400.00
        );

        $transactions = [
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-03-05'),
                valutaDate: new DateTimeImmutable('2025-03-05'),
                amount: 500.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'TXN-001')
            ),
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-03-10'),
                valutaDate: new DateTimeImmutable('2025-03-10'),
                amount: 200.00,
                creditDebit: CreditDebit::DEBIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'TXN-002')
            ),
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-03-15'),
                valutaDate: new DateTimeImmutable('2025-03-15'),
                amount: 100.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'TXN-003')
            ),
        ];

        $mt940 = new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'SUM-CHECK',
            statementNumber: '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions
        );

        // Konvertiere zu MT942 und zurück
        $mt942 = MtInterConverter::mt940ToMt942($mt940);
        $roundtripMt940 = MtInterConverter::mt942ToMt940($mt942);

        // Summenprüfung im MT942
        $totalCredits942 = 0.0;
        $totalDebits942 = 0.0;
        foreach ($mt942->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredits942 += $txn->getAmount();
            } else {
                $totalDebits942 += $txn->getAmount();
            }
        }

        $calculated942 = $mt942->getOpeningBalance()->getAmount() + $totalCredits942 - $totalDebits942;
        $this->assertEquals($mt942->getClosingBalance()->getAmount(), $calculated942, 'MT942 Summenprüfung');

        // Summenprüfung im Roundtrip MT940
        $totalCreditsRt = 0.0;
        $totalDebitsRt = 0.0;
        foreach ($roundtripMt940->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCreditsRt += $txn->getAmount();
            } else {
                $totalDebitsRt += $txn->getAmount();
            }
        }

        $calculatedRt = $roundtripMt940->getOpeningBalance()->getAmount() + $totalCreditsRt - $totalDebitsRt;
        $this->assertEquals($roundtripMt940->getClosingBalance()->getAmount(), $calculatedRt, 'Roundtrip MT940 Summenprüfung');
    }

    /**
     * Test: Negative Opening Balance Berechnung (Soll-Saldo)
     */
    public function testMt942ToMt940WithDebitClosingBalance(): void {
        // MT942 ohne Opening Balance, Closing ist Soll (Debit)
        // Closing: -500 EUR (Debit)
        // Transactions: +200 (Credit)
        // Expected Opening: -500 - 200 = -700 EUR (Debit)

        $closingBalance = new Balance(
            CreditDebit::DEBIT,
            new DateTimeImmutable('2025-04-30'),
            CurrencyCode::Euro,
            500.00
        );

        $transactions = [
            new Mt942Transaction(
                bookingDate: new DateTimeImmutable('2025-04-15'),
                valutaDate: new DateTimeImmutable('2025-04-15'),
                amount: 200.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'CREDIT-001')
            ),
        ];

        $mt942 = new Mt942Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'MT942-DEBIT',
            statementNumber: '00001',
            closingBalance: $closingBalance,
            transactions: $transactions,
            openingBalance: null
        );

        $mt940 = MtInterConverter::mt942ToMt940($mt942);

        // Opening Balance wurde berechnet: -500 - 200 = -700 (Soll)
        $this->assertEquals(700.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals(CreditDebit::DEBIT, $mt940->getOpeningBalance()->getCreditDebit());
    }

    // --- Helper Methods ---

    private function createMt940WithTransactions(): Mt940Document {
        $openingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            1150.00
        );

        $transactions = [
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-01-15'),
                valutaDate: new DateTimeImmutable('2025-01-15'),
                amount: 200.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'TXN-001'),
                purpose: 'EREF+E2E-001+MREF+MAND-001+SVWZ+Einzahlung'
            ),
            new Mt940Transaction(
                bookingDate: new DateTimeImmutable('2025-01-25'),
                valutaDate: new DateTimeImmutable('2025-01-25'),
                amount: 50.00,
                creditDebit: CreditDebit::DEBIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'TXN-002'),
                purpose: 'SVWZ+Abbuchung'
            ),
        ];

        return new Mt940Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'MT940-TEST',
            statementNumber: '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions
        );
    }

    private function createMt942WithTransactions(): Mt942Document {
        $openingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-02-01'),
            CurrencyCode::Euro,
            2000.00
        );

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-02-15'),
            CurrencyCode::Euro,
            2300.00
        );

        $transactions = [
            new Mt942Transaction(
                bookingDate: new DateTimeImmutable('2025-02-05'),
                valutaDate: new DateTimeImmutable('2025-02-05'),
                amount: 400.00,
                creditDebit: CreditDebit::CREDIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'MT942-TXN-001')
            ),
            new Mt942Transaction(
                bookingDate: new DateTimeImmutable('2025-02-10'),
                valutaDate: new DateTimeImmutable('2025-02-10'),
                amount: 100.00,
                creditDebit: CreditDebit::DEBIT,
                currency: CurrencyCode::Euro,
                reference: new Reference('TRF', 'MT942-TXN-002')
            ),
        ];

        return new Mt942Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'MT942-TEST',
            statementNumber: '00001',
            closingBalance: $closingBalance,
            transactions: $transactions,
            openingBalance: $openingBalance
        );
    }

    private function createMt941(): Mt941Document {
        $openingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-01'),
            CurrencyCode::Euro,
            5000.00
        );

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-31'),
            CurrencyCode::Euro,
            5500.00
        );

        $closingAvailable = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-31'),
            CurrencyCode::Euro,
            5400.00
        );

        return new Mt941Document(
            accountId: 'DE89370400440532013000',
            referenceId: 'MT941-TEST',
            statementNumber: '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            closingAvailableBalance: $closingAvailable
        );
    }
}
