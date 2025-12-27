<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AsciiToCamt053ConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\DATEV;

use CommonToolkit\Builders\DATEV\BankTransactionBuilder;
use CommonToolkit\Converters\DATEV\AsciiToCamt053Converter;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use Tests\Contracts\BaseTestCase;
use RuntimeException;

/**
 * Tests für AsciiToCamt053Converter.
 * 
 * @package Tests\Converters\DATEV
 */
final class AsciiToCamt053ConverterTest extends BaseTestCase {

    public function testConvertSingleTransaction(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+1000,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Max Mustermann',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_2->value => 'GmbH',
            BankTransactionHeaderField::BLZ_BIC_AUFTRAGGEBER->value => 'COBADEFF',
            BankTransactionHeaderField::KONTONUMMER_IBAN_AUFTRAGGEBER->value => 'DE12500105170648489890',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Rechnung 12345',
            BankTransactionHeaderField::VERWENDUNGSZWECK_2->value => 'Projekt ABC',
            BankTransactionHeaderField::VERWENDUNGSZWECK_3->value => '',
            BankTransactionHeaderField::VERWENDUNGSZWECK_4->value => '',
            BankTransactionHeaderField::GESCHAEFTSVORGANGSCODE->value => 'NTRF',
            BankTransactionHeaderField::WAEHRUNG->value => 'EUR',
        ])->build();

        $camt053 = AsciiToCamt053Converter::convert($document);

        // Grunddaten prüfen
        $this->assertInstanceOf(Camt053Document::class, $camt053);
        $this->assertNotEmpty($camt053->getId());
        $this->assertEquals('DE89370400440532013000', $camt053->getAccountIban());
        $this->assertEquals(CurrencyCode::Euro, $camt053->getCurrency());
        $this->assertEquals('00001', $camt053->getSequenceNumber());

        // Transaktionen prüfen
        $this->assertCount(1, $camt053->getEntries());
        $txn = $camt053->getEntries()[0];

        $this->assertEquals(1000.0, $txn->getAmount());
        $this->assertEquals(CreditDebit::CREDIT, $txn->getCreditDebit());
        $this->assertTrue($txn->isCredit());
        $this->assertEquals('Max Mustermann GmbH', $txn->getCounterpartyName());
        $this->assertEquals('DE12500105170648489890', $txn->getCounterpartyIban());
        $this->assertStringContainsString('Rechnung 12345', $txn->getPurpose());
        $this->assertStringContainsString('Projekt ABC', $txn->getPurpose());
    }

    public function testConvertDebitTransaction(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '-500,50',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Lieferant XY',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Warenlieferung',
            BankTransactionHeaderField::GESCHAEFTSVORGANGSCODE->value => 'NTRF',
            BankTransactionHeaderField::WAEHRUNG->value => 'EUR',
        ])->build();

        $camt053 = AsciiToCamt053Converter::convert($document);
        $txn = $camt053->getEntries()[0];

        $this->assertEquals(500.50, $txn->getAmount());
        $this->assertEquals(CreditDebit::DEBIT, $txn->getCreditDebit());
        $this->assertTrue($txn->isDebit());
        $this->assertEquals(-500.50, $txn->getSignedAmount());
    }

    public function testBalanceCalculation(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder
            ->addTransaction([
                BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
                BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
                BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
                BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::VALUTA->value => '27.12.2025',
                BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::UMSATZ->value => '+1000,00',
                BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Einzahlung',
                BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Einzahlung',
            ])
            ->addTransaction([
                BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
                BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
                BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
                BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::VALUTA->value => '27.12.2025',
                BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
                BankTransactionHeaderField::UMSATZ->value => '-300,00',
                BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Ausgabe',
                BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Ausgabe',
            ])
            ->build();

        // Mit Anfangssaldo von 500
        $camt053 = AsciiToCamt053Converter::convert($document, 500.0, CreditDebit::CREDIT);

        // Opening Balance
        $opening = $camt053->getOpeningBalance();
        $this->assertNotNull($opening);
        $this->assertEquals(500.0, $opening->getAmount());
        $this->assertTrue($opening->isCredit());

        // Closing Balance: 500 + 1000 - 300 = 1200
        $closing = $camt053->getClosingBalance();
        $this->assertNotNull($closing);
        $this->assertEquals(1200.0, $closing->getAmount());
        $this->assertTrue($closing->isCredit());

        // Netto-Berechnung
        $this->assertEquals(1000.0, $camt053->getTotalCredits());
        $this->assertEquals(300.0, $camt053->getTotalDebits());
        $this->assertEquals(700.0, $camt053->getNetAmount());
    }

    public function testNegativeClosingBalance(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '-1500,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Große Ausgabe',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Großer Abgang',
        ])->build();

        // Anfangssaldo 500, Abgang 1500 = -1000
        $camt053 = AsciiToCamt053Converter::convert($document, 500.0, CreditDebit::CREDIT);

        $closing = $camt053->getClosingBalance();
        $this->assertNotNull($closing);
        $this->assertEquals(1000.0, $closing->getAmount());
        $this->assertTrue($closing->isDebit());
    }

    public function testConvertMultipleDocuments(): void {
        $builder1 = new BankTransactionBuilder();
        $doc1 = $builder1->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '25.12.2025',
            BankTransactionHeaderField::VALUTA->value => '25.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '25.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+500,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Eingang 1',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Test 1',
        ])->build();

        $builder2 = new BankTransactionBuilder();
        $doc2 = $builder2->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00002',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '26.12.2025',
            BankTransactionHeaderField::VALUTA->value => '26.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '26.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '-200,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Ausgang 1',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Test 2',
        ])->build();

        $builder3 = new BankTransactionBuilder();
        $doc3 = $builder3->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00003',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+300,00',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Eingang 2',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Test 3',
        ])->build();

        $results = AsciiToCamt053Converter::convertMultiple([$doc1, $doc2, $doc3], 1000.0);

        $this->assertCount(3, $results);

        // Erstes Dokument: Opening 1000, Closing 1500
        $this->assertEquals(1000.0, $results[0]->getOpeningBalance()->getAmount());
        $this->assertEquals(1500.0, $results[0]->getClosingBalance()->getAmount());

        // Zweites Dokument: Opening 1500, Closing 1300
        $this->assertEquals(1500.0, $results[1]->getOpeningBalance()->getAmount());
        $this->assertEquals(1300.0, $results[1]->getClosingBalance()->getAmount());

        // Drittes Dokument: Opening 1300, Closing 1600
        $this->assertEquals(1300.0, $results[2]->getOpeningBalance()->getAmount());
        $this->assertEquals(1600.0, $results[2]->getClosingBalance()->getAmount());
    }

    public function testEmptyDocumentThrowsException(): void {
        $builder = new BankTransactionBuilder();
        $document = $builder->build();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('keine Transaktionen');

        AsciiToCamt053Converter::convert($document);
    }

    public function testXmlGeneration(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => '12345678',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+1234,56',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Test',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'Test Zweck',
        ])->build();

        $camt053 = AsciiToCamt053Converter::convert($document, 100.0, CreditDebit::CREDIT);
        $xml = $camt053->toXml();

        // XML-Struktur prüfen
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('urn:iso:std:iso:20022:tech:xsd:camt.053.001.02', $xml);
        $this->assertStringContainsString('<BkToCstmrStmt>', $xml);
        $this->assertStringContainsString('<GrpHdr>', $xml);
        $this->assertStringContainsString('<Stmt>', $xml);
        $this->assertStringContainsString('<Bal>', $xml);
        $this->assertStringContainsString('<Ntry>', $xml);
        $this->assertStringContainsString('1234.56', $xml);
        $this->assertStringContainsString('CRDT', $xml);
    }

    public function testXmlReaderRoundtrip(): void {
        $builder = new BankTransactionBuilder();

        $document = $builder->addTransaction([
            BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->value => 'DEUTDEDB',
            BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->value => 'DE89370400440532013000',
            BankTransactionHeaderField::AUSZUGSNUMMER->value => '00001',
            BankTransactionHeaderField::AUSZUGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::VALUTA->value => '27.12.2025',
            BankTransactionHeaderField::BUCHUNGSDATUM->value => '27.12.2025',
            BankTransactionHeaderField::UMSATZ->value => '+999,99',
            BankTransactionHeaderField::AUFTRAGGEBERNAME_1->value => 'Test Firma',
            BankTransactionHeaderField::KONTONUMMER_IBAN_AUFTRAGGEBER->value => 'DE12345678901234567890',
            BankTransactionHeaderField::VERWENDUNGSZWECK_1->value => 'EREF+E2E123456',
            BankTransactionHeaderField::VERWENDUNGSZWECK_2->value => 'Test Verwendungszweck',
            BankTransactionHeaderField::VERWENDUNGSZWECK_4->value => 'Test Buchungstext',
            BankTransactionHeaderField::GESCHAEFTSVORGANGSCODE->value => 'NTRF',
            BankTransactionHeaderField::WAEHRUNG->value => 'EUR',
        ])->build();

        $camt053 = AsciiToCamt053Converter::convert($document, 500.0, CreditDebit::CREDIT);
        $xml = $camt053->toXml();

        // XML muss valide sein
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'XML muss valide sein');

        // Wichtige Elemente müssen vorhanden sein
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ns', 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02');

        $this->assertGreaterThan(0, $xpath->query('//ns:BkToCstmrStmt')->length);
        $this->assertGreaterThan(0, $xpath->query('//ns:GrpHdr/ns:MsgId')->length);
        $this->assertGreaterThan(0, $xpath->query('//ns:Stmt/ns:Id')->length);
        $this->assertGreaterThan(0, $xpath->query('//ns:Acct/ns:Id/ns:IBAN')->length);
        $this->assertGreaterThan(0, $xpath->query('//ns:Bal')->length);
        $this->assertGreaterThan(0, $xpath->query('//ns:Ntry')->length);
    }
}
