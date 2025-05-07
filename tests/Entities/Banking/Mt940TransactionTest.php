<?php
/*
 * Created on   : Wed May 07 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940TransactionTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use CommonToolkit\Entities\Banking\Mt940Transaction;

class Mt940TransactionTest extends TestCase {

    public function testGetterMethodsReturnCorrectValues(): void {
        $transaction = new Mt940Transaction(
            date: '240501',
            amount: 1234.56,
            debitCredit: 'C',
            currency: 'EUR',
            transactionCode: 'NTRF',
            reference: 'ABC123XYZ',
            purpose: 'Testzahlung'
        );

        $this->assertEquals('240501', $transaction->getDate());
        $this->assertEquals(1234.56, $transaction->getAmount());
        $this->assertEquals('C', $transaction->getDebitCredit());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('NTRF', $transaction->getTransactionCode());
        $this->assertEquals('ABC123XYZ', $transaction->getReference());
        $this->assertEquals('Testzahlung', $transaction->getPurpose());
    }

    public function testFormattedAmountReturnsString(): void {
        $transaction = new Mt940Transaction(
            date: '240501',
            amount: 1234.56,
            debitCredit: 'D',
            currency: 'EUR',
            transactionCode: 'NTRF',
            reference: 'ABC123XYZ',
            purpose: null
        );

        $formatted = $transaction->getFormattedAmount('de_DE');
        $this->assertIsString($formatted);
        $this->assertStringContainsString('€', $formatted);
    }

    public function testIsDebitAndCredit(): void {
        $credit = new Mt940Transaction('240501', 100, 'C', 'EUR', 'NTRF', 'REF', 'raw', null);
        $debit  = new Mt940Transaction('240501', 50,  'D', 'EUR', 'NTRF', 'REF', 'raw', null);

        $this->assertTrue($credit->isCredit());
        $this->assertFalse($credit->isDebit());

        $this->assertTrue($debit->isDebit());
        $this->assertFalse($debit->isCredit());
    }

    public function testGetSign(): void {
        $credit = new Mt940Transaction('240501', 100, 'C', 'EUR', 'NTRF', 'REF', 'raw', null);
        $debit  = new Mt940Transaction('240501', 100, 'D', 'EUR', 'NTRF', 'REF', 'raw', null);

        $this->assertEquals('+', $credit->getSign());
        $this->assertEquals('-', $debit->getSign());
    }

    public function testToMt940LinesGeneratesCorrectFormat(): void {
        $transaction = new Mt940Transaction(
            date: '240501',
            amount: 1234.56,
            debitCredit: 'C',
            currency: 'EUR',
            transactionCode: 'NTRF',
            reference: 'ABC123XYZ',
            purpose: 'SEPA Überweisung Max Mustermann GmbH für Rechnung 123456 vom 01.05.2024'
        );

        $lines = $transaction->toMt940Lines();

        // Erste Zeile muss mit :61: beginnen
        $this->assertStringStartsWith(':61:', $lines[0]);

        // Zweite Zeile beginnt mit :86:
        $this->assertStringStartsWith(':86:', $lines[1]);

        // Nachfolgende Zeilen (falls vorhanden) beginnen mit ?20, ?21, ...
        for ($i = 2; $i < count($lines); $i++) {
            $this->assertMatchesRegularExpression('/^\?2\d/', $lines[$i]);
        }

        $lines = $transaction->toMt940Lines();

        // Purpose extrahieren und normalisieren
        $purposeLines = array_slice($lines, 1); // Zeile 0 ist :61:

        // Entferne :86: und ?xx
        $plainPurpose = preg_replace('/^:86:/', '', array_shift($purposeLines));
        $plainPurpose .= implode('', array_map(fn($l) => preg_replace('/^\?\d{2}/', '', $l), $purposeLines));

        $this->assertStringContainsString('Rechnung', $plainPurpose);
        $this->assertStringContainsString('123456', $plainPurpose);
    }
}
