<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ConverterRoundtripTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\CamtToMt940Converter;
use CommonToolkit\Converters\Banking\Mt940ToCamtConverter;
use CommonToolkit\Entities\Common\Banking\Camt\Balance as CamtBalance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction as Camt052Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference as Camt053Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction as Camt054Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance as Mt9Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference as Mt9Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

/**
 * Roundtrip-Tests für die Banking-Converter.
 * 
 * Testet die bidirektionale Konvertierung zwischen MT940 und CAMT-Formaten.
 * Bei Roundtrips gehen bestimmte Details verloren (z.B. CAMT hat mehr Felder als MT940),
 * aber die Kernwerte (Beträge, Daten, Salden) müssen erhalten bleiben.
 */
final class ConverterRoundtripTest extends BaseTestCase {
    /**
     * MT940 → CAMT.053 → MT940
     * 
     * Kernwerte müssen nach dem Roundtrip erhalten bleiben.
     */
    public function testMt940ToCamt053ToMt940Roundtrip(): void {
        // Original MT940
        $originalMt940 = $this->createMt940Document();

        // MT940 → CAMT.053
        $camt053 = Mt940ToCamtConverter::toCamt053($originalMt940);

        // CAMT.053 → MT940
        $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

        // Kernwerte vergleichen
        $this->assertEquals(
            $originalMt940->getAccountId(),
            $roundtripMt940->getAccountId(),
            'Account-ID muss erhalten bleiben'
        );

        $this->assertEquals(
            $originalMt940->getStatementNumber(),
            $roundtripMt940->getStatementNumber(),
            'Statement-Nummer muss erhalten bleiben'
        );

        // Salden
        $this->assertEquals(
            $originalMt940->getOpeningBalance()->getAmount(),
            $roundtripMt940->getOpeningBalance()->getAmount(),
            'Opening Balance Betrag muss erhalten bleiben'
        );

        $this->assertEquals(
            $originalMt940->getOpeningBalance()->getCreditDebit(),
            $roundtripMt940->getOpeningBalance()->getCreditDebit(),
            'Opening Balance Soll/Haben muss erhalten bleiben'
        );

        $this->assertEquals(
            $originalMt940->getClosingBalance()->getAmount(),
            $roundtripMt940->getClosingBalance()->getAmount(),
            'Closing Balance Betrag muss erhalten bleiben'
        );

        // Transaktionen
        $this->assertCount(
            count($originalMt940->getTransactions()),
            $roundtripMt940->getTransactions(),
            'Anzahl der Transaktionen muss erhalten bleiben'
        );

        foreach ($originalMt940->getTransactions() as $index => $originalTxn) {
            $roundtripTxn = $roundtripMt940->getTransactions()[$index];

            $this->assertEquals(
                $originalTxn->getAmount(),
                $roundtripTxn->getAmount(),
                "Transaktion $index: Betrag muss erhalten bleiben"
            );

            $this->assertEquals(
                $originalTxn->getCreditDebit(),
                $roundtripTxn->getCreditDebit(),
                "Transaktion $index: Soll/Haben muss erhalten bleiben"
            );

            $this->assertEquals(
                $originalTxn->getDate()->format('Y-m-d'),
                $roundtripTxn->getDate()->format('Y-m-d'),
                "Transaktion $index: Buchungsdatum muss erhalten bleiben"
            );
        }
    }

    /**
     * CAMT.053 → MT940 → CAMT.053
     * 
     * Kernwerte müssen nach dem Roundtrip erhalten bleiben.
     * Hinweis: Einige CAMT-spezifische Felder gehen verloren,
     * da MT940 weniger strukturierte Daten hat.
     */
    public function testCamt053ToMt940ToCamt053Roundtrip(): void {
        // Original CAMT.053
        $originalCamt053 = $this->createCamt053Document();

        // CAMT.053 → MT940
        $mt940 = CamtToMt940Converter::fromCamt053($originalCamt053);

        // MT940 → CAMT.053
        $roundtripCamt053 = Mt940ToCamtConverter::toCamt053($mt940);

        // Kernwerte vergleichen
        $this->assertEquals(
            $originalCamt053->getAccountIdentifier(),
            $roundtripCamt053->getAccountIdentifier(),
            'Account-Identifier muss erhalten bleiben'
        );

        $this->assertEquals(
            $originalCamt053->getSequenceNumber(),
            $roundtripCamt053->getSequenceNumber(),
            'Sequence-Number muss erhalten bleiben'
        );

        // Salden
        $this->assertEquals(
            $originalCamt053->getOpeningBalance()->getAmount(),
            $roundtripCamt053->getOpeningBalance()->getAmount(),
            'Opening Balance Betrag muss erhalten bleiben'
        );

        $this->assertEquals(
            $originalCamt053->getClosingBalance()->getAmount(),
            $roundtripCamt053->getClosingBalance()->getAmount(),
            'Closing Balance Betrag muss erhalten bleiben'
        );

        // Transaktionen
        $this->assertCount(
            count($originalCamt053->getEntries()),
            $roundtripCamt053->getEntries(),
            'Anzahl der Einträge muss erhalten bleiben'
        );

        foreach ($originalCamt053->getEntries() as $index => $originalEntry) {
            $roundtripEntry = $roundtripCamt053->getEntries()[$index];

            $this->assertEquals(
                $originalEntry->getAmount(),
                $roundtripEntry->getAmount(),
                "Eintrag $index: Betrag muss erhalten bleiben"
            );

            $this->assertEquals(
                $originalEntry->getCreditDebit(),
                $roundtripEntry->getCreditDebit(),
                "Eintrag $index: Soll/Haben muss erhalten bleiben"
            );

            $this->assertEquals(
                $originalEntry->getBookingDate()->format('Y-m-d'),
                $roundtripEntry->getBookingDate()->format('Y-m-d'),
                "Eintrag $index: Buchungsdatum muss erhalten bleiben"
            );
        }
    }

    /**
     * MT940 → CAMT.052 → MT940
     */
    public function testMt940ToCamt052ToMt940Roundtrip(): void {
        $originalMt940 = $this->createMt940Document();

        // MT940 → CAMT.052
        $camt052 = Mt940ToCamtConverter::toCamt052($originalMt940);

        // CAMT.052 → MT940
        $roundtripMt940 = CamtToMt940Converter::fromCamt052($camt052);

        // Kernwerte vergleichen
        $this->assertEquals(
            $originalMt940->getAccountId(),
            $roundtripMt940->getAccountId()
        );

        $this->assertEquals(
            $originalMt940->getOpeningBalance()->getAmount(),
            $roundtripMt940->getOpeningBalance()->getAmount()
        );

        $this->assertEquals(
            $originalMt940->getClosingBalance()->getAmount(),
            $roundtripMt940->getClosingBalance()->getAmount()
        );

        $this->assertCount(
            count($originalMt940->getTransactions()),
            $roundtripMt940->getTransactions()
        );
    }

    /**
     * MT940 → CAMT.054 → MT940
     * 
     * Hinweis: CAMT.054 hat keine Salden, daher werden bei der
     * Rückkonvertierung Zero-Balances erzeugt.
     */
    public function testMt940ToCamt054ToMt940Roundtrip(): void {
        $originalMt940 = $this->createMt940Document();

        // MT940 → CAMT.054
        $camt054 = Mt940ToCamtConverter::toCamt054($originalMt940);

        // CAMT.054 → MT940 (mit Original-Salden)
        $roundtripMt940 = CamtToMt940Converter::fromCamt054(
            $camt054,
            null,
            $originalMt940->getOpeningBalance(),
            $originalMt940->getClosingBalance()
        );

        // Mit übergebenen Salden sollten diese erhalten bleiben
        $this->assertEquals(
            $originalMt940->getOpeningBalance()->getAmount(),
            $roundtripMt940->getOpeningBalance()->getAmount()
        );

        $this->assertEquals(
            $originalMt940->getClosingBalance()->getAmount(),
            $roundtripMt940->getClosingBalance()->getAmount()
        );

        // Transaktionen
        $this->assertCount(
            count($originalMt940->getTransactions()),
            $roundtripMt940->getTransactions()
        );
    }

    /**
     * Doppelter Roundtrip: MT940 → CAMT.053 → MT940 → CAMT.053
     * 
     * Nach zwei Roundtrips sollten die Werte stabil sein.
     */
    public function testDoubleRoundtripStability(): void {
        $originalMt940 = $this->createMt940Document();

        // Erster Roundtrip
        $camt053_1 = Mt940ToCamtConverter::toCamt053($originalMt940);
        $mt940_1 = CamtToMt940Converter::fromCamt053($camt053_1);

        // Zweiter Roundtrip
        $camt053_2 = Mt940ToCamtConverter::toCamt053($mt940_1);
        $mt940_2 = CamtToMt940Converter::fromCamt053($camt053_2);

        // Nach dem zweiten Roundtrip sollten die Werte identisch sein
        $this->assertEquals(
            $mt940_1->getOpeningBalance()->getAmount(),
            $mt940_2->getOpeningBalance()->getAmount(),
            'Opening Balance muss nach doppeltem Roundtrip stabil sein'
        );

        $this->assertEquals(
            $mt940_1->getClosingBalance()->getAmount(),
            $mt940_2->getClosingBalance()->getAmount(),
            'Closing Balance muss nach doppeltem Roundtrip stabil sein'
        );

        $this->assertCount(
            count($mt940_1->getTransactions()),
            $mt940_2->getTransactions(),
            'Transaktionsanzahl muss nach doppeltem Roundtrip stabil sein'
        );

        // Beträge vergleichen
        foreach ($mt940_1->getTransactions() as $index => $txn1) {
            $txn2 = $mt940_2->getTransactions()[$index];
            $this->assertEquals(
                $txn1->getAmount(),
                $txn2->getAmount(),
                "Transaktion $index: Betrag muss nach doppeltem Roundtrip stabil sein"
            );
        }
    }

    /**
     * Test mit komplexen SEPA-Daten im Roundtrip.
     */
    public function testSepaDataRoundtrip(): void {
        // MT940 mit SEPA-Daten im Verwendungszweck
        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            5000.00
        );

        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            4850.00
        );

        $sepaTransaction = new Mt940Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            150.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            new Mt9Reference('TRF', 'REF-SEPA-001'),
            'EREF+E2E-REF-123456 MREF+MANDATE-ABC CRED+DE98ZZZ09999999999 SVWZ+SEPA Lastschrift Strom'
        );

        $mt940 = new Mt940Document(
            'DE89370400440532013000',
            'SEPA-TEST-001',
            '00001',
            $openingBalance,
            $closingBalance,
            [$sepaTransaction]
        );

        // Roundtrip
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);
        $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

        // SEPA-Referenzen sollten im Verwendungszweck erhalten bleiben
        $purpose = $roundtripMt940->getTransactions()[0]->getPurpose();

        $this->assertStringContainsString('EREF+', $purpose, 'End-to-End-Referenz muss erhalten bleiben');
        $this->assertStringContainsString('MREF+', $purpose, 'Mandatsreferenz muss erhalten bleiben');
        $this->assertStringContainsString('CRED+', $purpose, 'Creditor-ID muss erhalten bleiben');
        $this->assertStringContainsString('SVWZ+', $purpose, 'Verwendungszweck muss erhalten bleiben');
    }

    /**
     * Test mit mehreren Währungen.
     */
    public function testCurrencyPreservationRoundtrip(): void {
        $currencies = [CurrencyCode::Euro, CurrencyCode::USDollar, CurrencyCode::SwissFranc];

        foreach ($currencies as $currency) {
            $openingBalance = new Mt9Balance(
                CreditDebit::CREDIT,
                new DateTimeImmutable('2025-01-01'),
                $currency,
                1000.00
            );

            $closingBalance = new Mt9Balance(
                CreditDebit::CREDIT,
                new DateTimeImmutable('2025-01-31'),
                $currency,
                1100.00
            );

            $transaction = new Mt940Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                100.00,
                CreditDebit::CREDIT,
                $currency,
                new Mt9Reference('TRF', 'REF-001')
            );

            $mt940 = new Mt940Document(
                'DE89370400440532013000',
                'CURRENCY-TEST',
                '00001',
                $openingBalance,
                $closingBalance,
                [$transaction]
            );

            // Roundtrip
            $camt053 = Mt940ToCamtConverter::toCamt053($mt940);
            $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

            $this->assertEquals(
                $currency,
                $roundtripMt940->getCurrency(),
                "Währung {$currency->value} muss im Roundtrip erhalten bleiben"
            );

            $this->assertEquals(
                $currency,
                $roundtripMt940->getTransactions()[0]->getCurrency(),
                "Transaktionswährung {$currency->value} muss erhalten bleiben"
            );
        }
    }

    /**
     * Test mit Soll- und Haben-Buchungen gemischt.
     */
    public function testMixedDebitCreditRoundtrip(): void {
        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            1050.00
        );

        $transactions = [
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-10'),
                new DateTimeImmutable('2025-01-10'),
                200.00,
                CreditDebit::CREDIT,  // +200
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'CREDIT-001')
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                50.00,
                CreditDebit::DEBIT,   // -50
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'DEBIT-001')
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-20'),
                new DateTimeImmutable('2025-01-20'),
                100.00,
                CreditDebit::DEBIT,   // -100
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'DEBIT-002')
            ),
        ];

        $mt940 = new Mt940Document(
            'DE89370400440532013000',
            'MIXED-TEST',
            '00001',
            $openingBalance,
            $closingBalance,
            $transactions
        );

        // Roundtrip
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);
        $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

        $roundtripTxns = $roundtripMt940->getTransactions();

        // Erste Transaktion: Credit
        $this->assertTrue($roundtripTxns[0]->isCredit());
        $this->assertEquals(200.00, $roundtripTxns[0]->getAmount());

        // Zweite Transaktion: Debit
        $this->assertTrue($roundtripTxns[1]->isDebit());
        $this->assertEquals(50.00, $roundtripTxns[1]->getAmount());

        // Dritte Transaktion: Debit
        $this->assertTrue($roundtripTxns[2]->isDebit());
        $this->assertEquals(100.00, $roundtripTxns[2]->getAmount());
    }

    /**
     * Test: Leeres Dokument (nur Salden, keine Transaktionen).
     */
    public function testEmptyDocumentRoundtrip(): void {
        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            500.00
        );

        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            500.00
        );

        $mt940 = new Mt940Document(
            'DE89370400440532013000',
            'EMPTY-TEST',
            '00001',
            $openingBalance,
            $closingBalance,
            [] // Keine Transaktionen
        );

        // Roundtrip
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);
        $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

        $this->assertCount(0, $roundtripMt940->getTransactions());
        $this->assertEquals(500.00, $roundtripMt940->getOpeningBalance()->getAmount());
        $this->assertEquals(500.00, $roundtripMt940->getClosingBalance()->getAmount());
    }

    /**
     * Test: Summenprüfung - Opening Balance + Transaktionen = Closing Balance
     * 
     * Überprüft, dass die rechnerische Konsistenz nach der Konvertierung
     * erhalten bleibt: Anfangssaldo + Gutschriften - Belastungen = Endsaldo
     */
    public function testBalanceConsistencyAfterConversion(): void {
        // Dokument mit rechnerisch konsistenten Werten
        // Opening: 1000 EUR
        // + 500 (Credit)
        // - 200 (Debit)
        // - 50 (Debit)
        // = 1250 EUR (Closing)

        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-02-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-02-28'),
            CurrencyCode::Euro,
            1250.00
        );

        $transactions = [
            new Mt940Transaction(
                new DateTimeImmutable('2025-02-05'),
                new DateTimeImmutable('2025-02-05'),
                500.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'CREDIT-001')
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-02-10'),
                new DateTimeImmutable('2025-02-10'),
                200.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'DEBIT-001')
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-02-15'),
                new DateTimeImmutable('2025-02-15'),
                50.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'DEBIT-002')
            ),
        ];

        $mt940 = new Mt940Document(
            'DE89370400440532013000',
            'BALANCE-CHECK',
            '00001',
            $openingBalance,
            $closingBalance,
            $transactions
        );

        // Konvertiere zu CAMT.053
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);

        // Prüfe die Summen im CAMT.053
        $this->assertEquals(1000.00, $camt053->getOpeningBalance()->getAmount());
        $this->assertEquals(1250.00, $camt053->getClosingBalance()->getAmount());

        // Berechne die Summe der Transaktionen
        $totalCredits = 0.0;
        $totalDebits = 0.0;
        foreach ($camt053->getEntries() as $entry) {
            if ($entry->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredits += $entry->getAmount();
            } else {
                $totalDebits += $entry->getAmount();
            }
        }

        $this->assertEquals(500.00, $totalCredits, 'Summe Gutschriften');
        $this->assertEquals(250.00, $totalDebits, 'Summe Belastungen');

        // Rechnerische Konsistenz prüfen
        $calculatedClosing = $camt053->getOpeningBalance()->getAmount() + $totalCredits - $totalDebits;
        $this->assertEquals(
            $camt053->getClosingBalance()->getAmount(),
            $calculatedClosing,
            'Opening + Credits - Debits muss dem Closing Balance entsprechen'
        );

        // Zurück zu MT940 und nochmal prüfen
        $roundtripMt940 = CamtToMt940Converter::fromCamt053($camt053);

        $totalCreditsRt = 0.0;
        $totalDebitsRt = 0.0;
        foreach ($roundtripMt940->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCreditsRt += $txn->getAmount();
            } else {
                $totalDebitsRt += $txn->getAmount();
            }
        }

        $calculatedClosingRt = $roundtripMt940->getOpeningBalance()->getAmount() + $totalCreditsRt - $totalDebitsRt;
        $this->assertEquals(
            $roundtripMt940->getClosingBalance()->getAmount(),
            $calculatedClosingRt,
            'Balance-Konsistenz muss nach Roundtrip erhalten bleiben'
        );
    }

    /**
     * Test: CAMT.054 Summenbildung (Einzelumsätze ohne Salden)
     * 
     * CAMT.054 enthält Einzelumsätze ohne Salden. Bei der Konvertierung
     * nach MT940 werden Zero-Balances verwendet, außer sie werden explizit übergeben.
     */
    public function testCamt054TransactionSumsWithoutBalances(): void {
        $camt054 = new Camt054Document(
            id: 'CAMT054-SUM-TEST',
            creationDateTime: new DateTimeImmutable('2025-03-01'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro
        );

        // Mehrere Einzelumsätze
        $camt054->addEntry(new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-03-01'),
            valutaDate: new DateTimeImmutable('2025-03-01'),
            amount: 100.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        ));

        $camt054->addEntry(new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-03-01'),
            valutaDate: new DateTimeImmutable('2025-03-01'),
            amount: 75.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::DEBIT
        ));

        $camt054->addEntry(new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-03-01'),
            valutaDate: new DateTimeImmutable('2025-03-01'),
            amount: 25.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        ));

        // Konvertiere zu MT940 ohne Salden
        $mt940 = CamtToMt940Converter::fromCamt054($camt054);

        // Zero-Balances erwartet
        $this->assertEquals(0.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertEquals(0.00, $mt940->getClosingBalance()->getAmount());

        // Transaktionen müssen vollständig sein
        $this->assertCount(3, $mt940->getTransactions());

        // Summen prüfen
        $totalCredits = 0.0;
        $totalDebits = 0.0;
        foreach ($mt940->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredits += $txn->getAmount();
            } else {
                $totalDebits += $txn->getAmount();
            }
        }

        $this->assertEquals(125.00, $totalCredits, 'CAMT.054 Credits summiert');
        $this->assertEquals(75.00, $totalDebits, 'CAMT.054 Debits summiert');

        // Mit berechneten Salden konvertieren
        $calculatedOpening = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $calculatedClosing = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-03-01'),
            CurrencyCode::Euro,
            1050.00 // 1000 + 125 - 75
        );

        $mt940WithBalances = CamtToMt940Converter::fromCamt054(
            $camt054,
            null,
            $calculatedOpening,
            $calculatedClosing
        );

        $this->assertEquals(1000.00, $mt940WithBalances->getOpeningBalance()->getAmount());
        $this->assertEquals(1050.00, $mt940WithBalances->getClosingBalance()->getAmount());
    }

    /**
     * Test: CAMT.052 vs CAMT.053 - Unterschied bei Intraday vs Tagesauszug
     * 
     * CAMT.052 ist untertägig, CAMT.053 ist der Tagesabschluss.
     * Die Balance-Typen unterscheiden sich.
     */
    public function testCamt052VsCamt053BalanceTypes(): void {
        $mt940 = $this->createMt940Document();

        $camt052 = Mt940ToCamtConverter::toCamt052($mt940);
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);

        // CAMT.052 Closing Balance ist CLAV (Closing Available)
        $this->assertEquals('CLAV', $camt052->getClosingBalance()->getType());

        // CAMT.053 Closing Balance ist CLBD (Closing Booked)
        $this->assertEquals('CLBD', $camt053->getClosingBalance()->getType());

        // Opening Balance ist bei beiden PRCD (Previous Closing Date)
        $this->assertEquals('PRCD', $camt052->getOpeningBalance()->getType());
        $this->assertEquals('PRCD', $camt053->getOpeningBalance()->getType());

        // Aber die Beträge sollten identisch sein
        $this->assertEquals(
            $camt052->getOpeningBalance()->getAmount(),
            $camt053->getOpeningBalance()->getAmount()
        );

        $this->assertEquals(
            $camt052->getClosingBalance()->getAmount(),
            $camt053->getClosingBalance()->getAmount()
        );
    }

    // =========================================================================
    // Helper-Methoden
    // =========================================================================

    private function createMt940Document(): Mt940Document {
        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            1000.00
        );

        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            1250.00
        );

        $transactions = [
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-10'),
                new DateTimeImmutable('2025-01-10'),
                300.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'REF-001'),
                'SVWZ+Gehalt Januar'
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-20'),
                new DateTimeImmutable('2025-01-20'),
                50.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                new Mt9Reference('TRF', 'REF-002'),
                'SVWZ+Lastschrift'
            ),
        ];

        return new Mt940Document(
            'DE89370400440532013000',
            'STMT-2025-001',
            '00001',
            $openingBalance,
            $closingBalance,
            $transactions
        );
    }

    private function createCamt053Document(): Camt053Document {
        $openingBalance = new CamtBalance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            2000.00,
            'PRCD'
        );

        $closingBalance = new CamtBalance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-31'),
            CurrencyCode::Euro,
            2150.00,
            'CLBD'
        );

        $document = new Camt053Document(
            id: 'CAMT-2025-001',
            creationDateTime: new DateTimeImmutable('2025-01-31'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro,
            accountOwner: 'Test User',
            servicerBic: 'COBADEFFXXX',
            messageId: 'MSG-001',
            sequenceNumber: '00005',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        $reference1 = new Camt053Reference(
            endToEndId: 'E2E-001',
            mandateId: 'MAND-001'
        );

        $transaction1 = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-01-15'),
            valutaDate: new DateTimeImmutable('2025-01-15'),
            amount: 200.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT,
            reference: $reference1,
            entryReference: 'ENTRY-001',
            purpose: 'Einzahlung'
        );

        $reference2 = new Camt053Reference(
            endToEndId: 'E2E-002'
        );

        $transaction2 = new Camt053Transaction(
            bookingDate: new DateTimeImmutable('2025-01-25'),
            valutaDate: new DateTimeImmutable('2025-01-25'),
            amount: 50.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::DEBIT,
            reference: $reference2,
            entryReference: 'ENTRY-002',
            purpose: 'Abbuchung'
        );

        $document->addEntry($transaction1);
        $document->addEntry($transaction2);

        return $document;
    }

    /**
     * Test: CAMT.053 → MT940 → CAMT.052 (Inter-CAMT-Konvertierung)
     * 
     * Die Konvertierung zwischen CAMT-Formaten erfolgt über MT940 als Zwischenformat.
     * Kernwerte müssen erhalten bleiben, Balance-Typen ändern sich entsprechend.
     */
    public function testCamt053ToMt940ToCamt052Conversion(): void {
        $camt053 = $this->createCamt053Document();

        // CAMT.053 → MT940 → CAMT.052
        $mt940 = CamtToMt940Converter::fromCamt053($camt053);
        $camt052 = Mt940ToCamtConverter::toCamt052($mt940);

        // Kontodaten müssen erhalten bleiben
        $this->assertEquals($camt053->getAccountIdentifier(), $camt052->getAccountIdentifier());

        // Transaktionsanzahl muss übereinstimmen
        $this->assertCount(count($camt053->getEntries()), $camt052->getEntries());

        // Salden-Beträge müssen identisch sein
        $this->assertEquals(
            $camt053->getOpeningBalance()->getAmount(),
            $camt052->getOpeningBalance()->getAmount()
        );
        $this->assertEquals(
            $camt053->getClosingBalance()->getAmount(),
            $camt052->getClosingBalance()->getAmount()
        );

        // CAMT.053 hat CLBD, CAMT.052 hat CLAV als Closing-Balance-Type
        $this->assertEquals('CLBD', $camt053->getClosingBalance()->getType());
        $this->assertEquals('CLAV', $camt052->getClosingBalance()->getType());

        // Transaktionsbeträge prüfen
        $originalAmounts = array_map(fn($e) => $e->getAmount(), $camt053->getEntries());
        $convertedAmounts = array_map(fn($e) => $e->getAmount(), $camt052->getEntries());
        $this->assertEquals($originalAmounts, $convertedAmounts);
    }

    /**
     * Test: CAMT.054 → MT940 → CAMT.053 (mit Salden)
     * 
     * CAMT.054 hat keine Salden. Bei Konvertierung zu CAMT.053 werden die 
     * übergebenen Salden verwendet.
     */
    public function testCamt054ToMt940ToCamt053WithBalances(): void {
        // CAMT.054 erstellen (ohne Salden)
        $camt054 = new Camt054Document(
            id: 'CAMT054-CONVERT-TEST',
            creationDateTime: new DateTimeImmutable('2025-04-01'),
            accountIdentifier: 'DE89370400440532013000',
            currency: CurrencyCode::Euro
        );

        $camt054->addEntry(new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-04-01'),
            valutaDate: new DateTimeImmutable('2025-04-01'),
            amount: 300.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::CREDIT
        ));

        $camt054->addEntry(new Camt054Transaction(
            bookingDate: new DateTimeImmutable('2025-04-01'),
            valutaDate: new DateTimeImmutable('2025-04-01'),
            amount: 100.00,
            currency: CurrencyCode::Euro,
            creditDebit: CreditDebit::DEBIT
        ));

        // Salden für die Konvertierung
        $openingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-04-01'),
            CurrencyCode::Euro,
            5000.00
        );

        // Erwarteter Endsaldo: 5000 + 300 - 100 = 5200
        $closingBalance = new Mt9Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-04-01'),
            CurrencyCode::Euro,
            5200.00
        );

        // CAMT.054 → MT940 (mit Salden)
        $mt940 = CamtToMt940Converter::fromCamt054($camt054, null, $openingBalance, $closingBalance);

        // MT940 → CAMT.053
        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);

        // Salden im CAMT.053 prüfen
        $this->assertEquals(5000.00, $camt053->getOpeningBalance()->getAmount());
        $this->assertEquals(5200.00, $camt053->getClosingBalance()->getAmount());
        $this->assertEquals('PRCD', $camt053->getOpeningBalance()->getType());
        $this->assertEquals('CLBD', $camt053->getClosingBalance()->getType());

        // Transaktionen müssen vollständig sein
        $this->assertCount(2, $camt053->getEntries());

        // Rechnerische Konsistenz prüfen
        $totalCredits = 0.0;
        $totalDebits = 0.0;
        foreach ($camt053->getEntries() as $entry) {
            if ($entry->getCreditDebit() === CreditDebit::CREDIT) {
                $totalCredits += $entry->getAmount();
            } else {
                $totalDebits += $entry->getAmount();
            }
        }

        $calculatedClosing = $camt053->getOpeningBalance()->getAmount() + $totalCredits - $totalDebits;
        $this->assertEquals($camt053->getClosingBalance()->getAmount(), $calculatedClosing);
    }
}
