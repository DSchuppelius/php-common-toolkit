<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionToMt940ConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\DATEV;

use CommonToolkit\Builders\DATEV\BankTransactionBuilder;
use CommonToolkit\Converters\DATEV\BankTransactionToMt940Converter;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use Tests\Contracts\BaseTestCase;

class BankTransactionToMt940ConverterTest extends BaseTestCase {

    public function testConvertSingleTransaction(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '0001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+100,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Max Mustermann',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_2->value => 'GmbH',
            BankTransactionHeaderField::BLZ_BIC_AUFTRAGGEBER->value => 'COBADEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_AUFTRAGGEBER->value => 'DE123456',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Rechnung 12345',
            BankTransactionHeaderField::VERWENDUNGSZWECK_2->value => 'Projekt ABC',
            BankTransactionHeaderField::VERWENDUNGSZWECK_3->value => '',
            BankTransactionHeaderField::VERWENDUNGSZWECK_4->value => '',
            BankTransactionHeaderField::GESCHAEFTSVORGANGSCODE->value => 'NTRF',
            BankTransactionHeaderField::WAEHRUNG->value => 'EUR',
        ])->build();

        $mt940 = BankTransactionToMt940Converter::convert($document, 1000.00, CreditDebit::CREDIT);

        $this->assertInstanceOf(Mt940Document::class, $mt940);
        $this->assertCount(1, $mt940->getTransactions());

        $txn = $mt940->getTransactions()[0];
        $this->assertEquals(100.00, $txn->getAmount());
        $this->assertTrue($txn->isCredit());
        $this->assertStringContainsString('Max Mustermann', $txn->getPurpose());
        $this->assertStringContainsString('Rechnung 12345', $txn->getPurpose());
    }

    public function testConvertMultipleTransactions(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder
            ->addTransaction([
                BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
                BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
                BankTransactionHeaderField::AUSZUGSNUMMER->value => '0001',
                BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::VALUTA->value => '27.12.2025',
                BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::UMSATZ->value => '+500,00',
                BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Einzahlung',
            ])
            ->addTransaction([
                BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
                BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
                BankTransactionHeaderField::AUSZUGSNUMMER->value => '0001',
                BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::VALUTA->value => '28.12.2025',
                BankTransactionHeaderField::BUCHUNGSDATUM->value => '28.12.2025',
                BankTransactionHeaderField::UMSATZ->value => '-150,00',
                BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Auszahlung',
            ])
            ->build();

        $mt940 = BankTransactionToMt940Converter::convert($document, 1000.00, CreditDebit::CREDIT);

        $this->assertInstanceOf(Mt940Document::class, $mt940);
        $this->assertCount(2, $mt940->getTransactions());

        // Prüfe Opening/Closing Balance
        $this->assertEquals(1000.00, $mt940->getOpeningBalance()->getAmount());
        $this->assertTrue($mt940->getOpeningBalance()->isCredit());

        // Closing Balance = 1000 + 500 - 150 = 1350
        $this->assertEquals(1350.00, $mt940->getClosingBalance()->getAmount());
        $this->assertTrue($mt940->getClosingBalance()->isCredit());
    }

    public function testConvertDebitTransaction(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '0001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '-250,50',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Lieferant XYZ',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Lastschrift',
        ])->build();

        $mt940 = BankTransactionToMt940Converter::convert($document);

        $this->assertCount(1, $mt940->getTransactions());

        $txn = $mt940->getTransactions()[0];
        $this->assertEquals(250.50, $txn->getAmount());
        $this->assertTrue($txn->isDebit());
        $this->assertEquals('-', $txn->getSign());
    }

    public function testMt940OutputFormat(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '0001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+100,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Test',
        ])->build();

        $mt940 = BankTransactionToMt940Converter::convert($document, 500.00);
        $output = (string) $mt940;

        // Prüfe MT940-Struktur
        $this->assertStringContainsString(':20:', $output); // Referenz
        $this->assertStringContainsString(':25:', $output); // Konto
        $this->assertStringContainsString(':28C:', $output); // Auszugsnummer
        $this->assertStringContainsString(':60F:', $output); // Opening Balance
        $this->assertStringContainsString(':61:', $output); // Transaktion
        $this->assertStringContainsString(':62F:', $output); // Closing Balance
    }

    public function testEmptyDocumentThrowsException(): void {
        $builder = new BankTransactionBuilder();
        $document = $builder->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('keine Transaktionen');

        BankTransactionToMt940Converter::convert($document);
    }

    public function testConvertMultipleDocuments(): void {
        $builder1 = new BankTransactionBuilder();
        $doc1 = $builder1->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+100,00',
        ])->build();

        $builder2 = new BankTransactionBuilder();
        $doc2 = $builder2->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'COBADEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE11111111111111111111',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '28.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+200,00',
        ])->build();

        $results = BankTransactionToMt940Converter::convertMultiple([$doc1, $doc2]);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Mt940Document::class, $results[0]);
        $this->assertInstanceOf(Mt940Document::class, $results[1]);
    }
}
