<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940ToBankTransactionConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\DATEV;

use CommonToolkit\Builders\Mt940DocumentBuilder;
use CommonToolkit\Converters\DATEV\Mt940ToBankTransactionConverter;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

class Mt940ToBankTransactionConverterTest extends BaseTestCase {

    public function testConvertSingleTransaction(): void {
        $reference = new Reference('TRF', 'NTRF');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            100.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            'Max Mustermann Rechnung 12345'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1100.00);

        $mt940 = (new Mt940DocumentBuilder())
            ->setAccountId('DEUTDEDB/DE89370400440532013000')
            ->setStatementNumber('0001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $this->assertInstanceOf(BankTransaction::class, $bankTransaction);
        $this->assertCount(1, $bankTransaction->getRows());

        $row = $bankTransaction->getRows()[0];
        $this->assertEquals('DEUTDEDB', $row->getField(0)->getValue());
        $this->assertEquals('DE89370400440532013000', $row->getField(1)->getValue());
        $this->assertEquals('0001', $row->getField(2)->getValue());
        $this->assertEquals('27.12.2025', $row->getField(3)->getValue());
        $this->assertEquals('+100,00', $row->getField(6)->getValue());
        $this->assertEquals('EUR', $row->getField(16)->getValue());
    }

    public function testConvertDebitTransaction(): void {
        $reference = new Reference('CHK', '020');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            250.50,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            $reference,
            'Lastschrift Strom'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 749.50);

        $mt940 = (new Mt940DocumentBuilder())
            ->setAccountId('12345678/0123456789')
            ->setStatementNumber('0002')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $row = $bankTransaction->getRows()[0];
        $this->assertEquals('12345678', $row->getField(0)->getValue());
        $this->assertEquals('0123456789', $row->getField(1)->getValue());
        $this->assertEquals('-250,50', $row->getField(6)->getValue());
    }

    public function testConvertMultipleTransactions(): void {
        $ref1 = new Reference('TRF', '051');
        $txn1 = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $ref1,
            'Einzahlung Bar'
        );

        $ref2 = new Reference('CHK', '020');
        $txn2 = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            150.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            $ref2,
            'Miete Dezember'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1350.00);

        $mt940 = (new Mt940DocumentBuilder())
            ->setAccountId('DEUTDEDB/DE89370400440532013000')
            ->setStatementNumber('0001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($txn1)
            ->addTransaction($txn2)
            ->build();

        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $this->assertCount(2, $bankTransaction->getRows());

        $row1 = $bankTransaction->getRows()[0];
        $this->assertEquals('+500,00', $row1->getField(6)->getValue());
        $this->assertStringContainsString('Einzahlung Bar', $row1->getField(11)->getValue());

        $row2 = $bankTransaction->getRows()[1];
        $this->assertEquals('-150,00', $row2->getField(6)->getValue());
        $this->assertStringContainsString('Miete', $row2->getField(11)->getValue());
    }

    public function testConvertIbanOnly(): void {
        $reference = new Reference('TRF', 'NTRF');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            null,
            50.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            'Test'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1050.00);

        $mt940 = (new Mt940DocumentBuilder())
            ->setAccountId('DE89370400440532013000')
            ->setStatementNumber('0001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $row = $bankTransaction->getRows()[0];
        // BLZ aus deutscher IBAN extrahiert (Stellen 5-12)
        $this->assertEquals('37040044', $row->getField(0)->getValue());
        $this->assertEquals('DE89370400440532013000', $row->getField(1)->getValue());
    }

    public function testConvertLongPurposeSplit(): void {
        $longPurpose = 'Dies ist ein sehr langer Verwendungszweck der über mehrere Zeilen aufgeteilt werden muss weil DATEV nur 27 Zeichen pro Feld erlaubt';

        $reference = new Reference('TRF', '020');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            100.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            $longPurpose
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1100.00);

        $mt940 = (new Mt940DocumentBuilder())
            ->setAccountId('DEUTDEDB/DE89370400440532013000')
            ->setStatementNumber('0001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $row = $bankTransaction->getRows()[0];

        // Prüfe, dass Verwendungszweck-Felder gefüllt sind
        $this->assertNotEmpty($row->getField(11)->getValue());
        $this->assertNotEmpty($row->getField(12)->getValue());

        // Prüfe maximale Länge
        $this->assertLessThanOrEqual(27, strlen($row->getField(11)->getValue()));
        $this->assertLessThanOrEqual(27, strlen($row->getField(12)->getValue()));
    }

    public function testConvertMultipleDocuments(): void {
        $ref1 = new Reference('TRF', 'NTRF');
        $txn1 = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            100.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $ref1,
            'Test1'
        );

        $ref2 = new Reference('TRF', 'NTRF');
        $txn2 = new Transaction(
            new DateTimeImmutable('2025-12-28'),
            new DateTimeImmutable('2025-12-28'),
            200.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            $ref2,
            'Test2'
        );

        $ob1 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00);
        $cb1 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1100.00);

        $ob2 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 1100.00);
        $cb2 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 900.00);

        $doc1 = (new Mt940DocumentBuilder())
            ->setAccountId('DEUTDEDB/DE89370400440532013000')
            ->setStatementNumber('0001')
            ->setOpeningBalance($ob1)
            ->setClosingBalance($cb1)
            ->addTransaction($txn1)
            ->build();

        $doc2 = (new Mt940DocumentBuilder())
            ->setAccountId('DEUTDEDB/DE89370400440532013000')
            ->setStatementNumber('0002')
            ->setOpeningBalance($ob2)
            ->setClosingBalance($cb2)
            ->addTransaction($txn2)
            ->build();

        $results = Mt940ToBankTransactionConverter::convertMultiple([$doc1, $doc2]);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(BankTransaction::class, $results[0]);
        $this->assertInstanceOf(BankTransaction::class, $results[1]);
    }
}
