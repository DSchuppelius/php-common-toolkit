<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053ToBankTransactionConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\DATEV;

use CommonToolkit\Builders\Camt053DocumentBuilder;
use CommonToolkit\Converters\DATEV\Camt053ToBankTransactionConverter;
use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

class Camt053ToBankTransactionConverterTest extends BaseTestCase {

    public function testConvertSingleTransaction(): void {
        $reference = new Reference(endToEndId: 'E2E-001');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            100.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference,
            null,
            null,
            'BOOK',
            false,
            'Rechnung 12345',
            null,
            'NTRF',
            'Max Mustermann',
            'DE123456',
            'DEUTDEDB'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1100.00, 'CLBD');

        $camt = (new Camt053DocumentBuilder())
            ->setId('CAMT053-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0001')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt);

        $this->assertInstanceOf(BankTransaction::class, $bankTransaction);
        $this->assertCount(1, $bankTransaction->getRows());

        $row = $bankTransaction->getRows()[0];
        $this->assertEquals('COBADEDB', $row->getField(0)->getValue());
        $this->assertEquals('DE89370400440532013000', $row->getField(1)->getValue());
        $this->assertEquals('0001', $row->getField(2)->getValue());
        $this->assertEquals('+100,00', $row->getField(6)->getValue());
        $this->assertEquals('EUR', $row->getField(16)->getValue());
    }

    public function testConvertDebitTransaction(): void {
        $reference = new Reference();
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-28'),
            new DateTimeImmutable('2025-12-27'),
            250.50,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            $reference,
            null,
            null,
            'BOOK',
            false,
            'Lastschrift Strom',
            null,
            '020',
            'Stadtwerke GmbH',
            'DE555444333222111',
            'GENODES1'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 749.50, 'CLBD');

        $camt = (new Camt053DocumentBuilder())
            ->setId('CAMT053-002')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0002')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt);

        $row = $bankTransaction->getRows()[0];
        $this->assertEquals('-250,50', $row->getField(6)->getValue());
        $this->assertStringContainsString('Stadtwerke GmbH', $row->getField(7)->getValue());
    }

    public function testConvertMultipleTransactions(): void {
        $ref1 = new Reference();
        $txn1 = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $ref1,
            null,
            null,
            'BOOK',
            false,
            'Einzahlung'
        );

        $ref2 = new Reference();
        $txn2 = new Transaction(
            new DateTimeImmutable('2025-12-28'),
            new DateTimeImmutable('2025-12-28'),
            150.00,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            $ref2,
            null,
            null,
            'BOOK',
            false,
            'Miete Dezember'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 1350.00, 'CLBD');

        $camt = (new Camt053DocumentBuilder())
            ->setId('CAMT053-003')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0001')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($txn1)
            ->addEntry($txn2)
            ->build();

        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt);

        $this->assertCount(2, $bankTransaction->getRows());

        $row1 = $bankTransaction->getRows()[0];
        $this->assertEquals('+500,00', $row1->getField(6)->getValue());

        $row2 = $bankTransaction->getRows()[1];
        $this->assertEquals('-150,00', $row2->getField(6)->getValue());
    }

    public function testConvertWithSepaReferences(): void {
        $reference = new Reference(
            endToEndId: 'E2E-REF-12345',
            mandateId: 'MAND-REF-67890',
            creditorId: 'DE98ZZZ09999999999'
        );
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            99.99,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference,
            null,
            null,
            'BOOK',
            false,
            'SEPA Überweisung Rechnung'
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1099.99, 'CLBD');

        $camt = (new Camt053DocumentBuilder())
            ->setId('CAMT053-004')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0001')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt);

        $row = $bankTransaction->getRows()[0];

        // Sammle Verwendungszweck-Felder
        $verwendungszweck = '';
        for ($i = 11; $i <= 14; $i++) {
            $verwendungszweck .= $row->getField($i)->getValue() . ' ';
        }
        for ($i = 18; $i <= 23; $i++) {
            $verwendungszweck .= $row->getField($i)->getValue() . ' ';
        }

        $this->assertStringContainsString('EREF', $verwendungszweck);
        $this->assertStringContainsString('MREF', $verwendungszweck);
        $this->assertStringContainsString('CRED', $verwendungszweck);
    }

    public function testConvertBlzFromIban(): void {
        $reference = new Reference();
        $transaction = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            50.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference
        );

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1050.00, 'CLBD');

        $camt = (new Camt053DocumentBuilder())
            ->setId('CAMT053-005')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setSequenceNumber('0001')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt);

        $row = $bankTransaction->getRows()[0];
        // Wenn kein BIC angegeben, wird via BankHelper der BIC aus der Bundesbank-Datenbank ermittelt
        // oder als Fallback die BLZ aus der deutschen IBAN extrahiert
        $blzOrBic = $row->getField(0)->getValue();
        // Entweder BIC (COBADEFFXXX für BLZ 37040044) oder BLZ direkt
        $this->assertTrue(
            $blzOrBic === 'COBADEFFXXX' || $blzOrBic === '37040044',
            "Erwarte BIC 'COBADEFFXXX' oder BLZ '37040044', erhalten: '$blzOrBic'"
        );
    }

    public function testConvertMultipleDocuments(): void {
        $ref1 = new Reference();
        $txn1 = new Transaction(
            new DateTimeImmutable('2025-12-27'),
            new DateTimeImmutable('2025-12-27'),
            100.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $ref1
        );

        $ref2 = new Reference();
        $txn2 = new Transaction(
            new DateTimeImmutable('2025-12-28'),
            new DateTimeImmutable('2025-12-28'),
            200.00,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            $ref2
        );

        $ob1 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $cb1 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-27'), CurrencyCode::Euro, 1100.00, 'CLBD');

        $ob2 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 1100.00, 'OPBD');
        $cb2 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-12-28'), CurrencyCode::Euro, 900.00, 'CLBD');

        $doc1 = (new Camt053DocumentBuilder())
            ->setId('CAMT053-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0001')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-27'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($ob1)
            ->setClosingBalance($cb1)
            ->addEntry($txn1)
            ->build();

        $doc2 = (new Camt053DocumentBuilder())
            ->setId('CAMT053-002')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setServicerBic('COBADEDB')
            ->setSequenceNumber('0002')
            ->setCreationDateTime(new DateTimeImmutable('2025-12-28'))
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($ob2)
            ->setClosingBalance($cb2)
            ->addEntry($txn2)
            ->build();

        $results = Camt053ToBankTransactionConverter::convertMultiple([$doc1, $doc2]);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(BankTransaction::class, $results[0]);
        $this->assertInstanceOf(BankTransaction::class, $results[1]);
    }
}
