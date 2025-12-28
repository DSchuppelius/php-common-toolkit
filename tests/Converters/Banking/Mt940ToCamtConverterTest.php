<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940ToCamtConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\Mt940ToCamtConverter;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Contracts\BaseTestCase;

final class Mt940ToCamtConverterTest extends BaseTestCase {
    private Mt940Document $mt940Document;

    protected function setUp(): void {
        parent::setUp();

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
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                100.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                new Reference('TRF', 'REF001'),
                'EREF+END2END123 SVWZ+Gehalt Januar'
            ),
            new Mt940Transaction(
                new DateTimeImmutable('2025-01-20'),
                new DateTimeImmutable('2025-01-20'),
                50.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                new Reference('TRF', 'REF002'),
                'SVWZ+Lastschrift Strom'
            ),
        ];

        $this->mt940Document = new Mt940Document(
            'DE89370400440532013000',
            'STMT2025001',
            '00001',
            $openingBalance,
            $closingBalance,
            $transactions
        );
    }

    public function testConvertToCamt053(): void {
        $camt053 = Mt940ToCamtConverter::toCamt053($this->mt940Document);

        $this->assertInstanceOf(Camt053Document::class, $camt053);
        $this->assertEquals('STMT2025001', $camt053->getId());
        $this->assertEquals('DE89370400440532013000', $camt053->getAccountIdentifier());
        $this->assertEquals('00001', $camt053->getSequenceNumber());
        $this->assertCount(2, $camt053->getEntries());

        // Opening Balance
        $openingBalance = $camt053->getOpeningBalance();
        $this->assertNotNull($openingBalance);
        $this->assertEquals(1000.00, $openingBalance->getAmount());
        $this->assertTrue($openingBalance->isCredit());
        $this->assertEquals('PRCD', $openingBalance->getType());

        // Closing Balance
        $closingBalance = $camt053->getClosingBalance();
        $this->assertNotNull($closingBalance);
        $this->assertEquals(1150.00, $closingBalance->getAmount());
        $this->assertTrue($closingBalance->isCredit());
        $this->assertEquals('CLBD', $closingBalance->getType());
    }

    public function testConvertToCamt052(): void {
        $camt052 = Mt940ToCamtConverter::toCamt052($this->mt940Document);

        $this->assertInstanceOf(Camt052Document::class, $camt052);
        $this->assertEquals('STMT2025001', $camt052->getId());
        $this->assertCount(2, $camt052->getEntries());

        // Closing Balance für Intraday ist CLAV
        $closingBalance = $camt052->getClosingBalance();
        $this->assertNotNull($closingBalance);
        $this->assertEquals('CLAV', $closingBalance->getType());
    }

    public function testConvertToCamt054(): void {
        $camt054 = Mt940ToCamtConverter::toCamt054($this->mt940Document);

        $this->assertInstanceOf(Camt054Document::class, $camt054);
        $this->assertEquals('STMT2025001', $camt054->getId());
        $this->assertCount(2, $camt054->getEntries());
    }

    public function testGenericConvert(): void {
        $camt052 = Mt940ToCamtConverter::convert($this->mt940Document, CamtType::CAMT052);
        $camt053 = Mt940ToCamtConverter::convert($this->mt940Document, CamtType::CAMT053);
        $camt054 = Mt940ToCamtConverter::convert($this->mt940Document, CamtType::CAMT054);

        $this->assertInstanceOf(Camt052Document::class, $camt052);
        $this->assertInstanceOf(Camt053Document::class, $camt053);
        $this->assertInstanceOf(Camt054Document::class, $camt054);
    }

    public function testTransactionConversion(): void {
        $camt053 = Mt940ToCamtConverter::toCamt053($this->mt940Document);
        $entries = $camt053->getEntries();

        // Erste Transaktion (Credit)
        $entry1 = $entries[0];
        $this->assertEquals(100.00, $entry1->getAmount());
        $this->assertTrue($entry1->isCredit());
        $this->assertEquals('NTRF', $entry1->getTransactionCode());
        $this->assertStringContainsString('Gehalt Januar', $entry1->getPurpose() ?? '');

        // Referenz aus Purpose extrahiert
        $reference = $entry1->getReference();
        $this->assertEquals('END2END123', $reference->getEndToEndId());

        // Zweite Transaktion (Debit)
        $entry2 = $entries[1];
        $this->assertEquals(50.00, $entry2->getAmount());
        $this->assertTrue($entry2->isDebit());
    }

    public function testCustomMessageId(): void {
        $customMessageId = 'CUSTOM-MSG-123';
        $camt053 = Mt940ToCamtConverter::toCamt053($this->mt940Document, $customMessageId);

        $this->assertEquals($customMessageId, $camt053->getMessageId());
    }

    public function testConvertMultiple(): void {
        $documents = [$this->mt940Document, $this->mt940Document];
        $results = Mt940ToCamtConverter::convertMultipleTo053($documents);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Camt053Document::class, $results[0]);
        $this->assertInstanceOf(Camt053Document::class, $results[1]);
    }

    public function testXmlGeneration(): void {
        $camt053 = Mt940ToCamtConverter::toCamt053($this->mt940Document);
        $xml = $camt053->toXml();

        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('camt.053.001.02', $xml);
        $this->assertStringContainsString('BkToCstmrStmt', $xml);
        $this->assertStringContainsString('DE89370400440532013000', $xml);
    }

    public function testSepaReferenceExtraction(): void {
        // Dokument mit vollständigen SEPA-Referenzen
        $transaction = new Mt940Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            100.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            new Reference('TRF', 'REF003'),
            'EREF+E2E123456 MREF+MANDATE789 CRED+DE98ZZZ09999999999 KREF+KREF555 SVWZ+SEPA Lastschrift'
        );

        $openingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            500.00
        );

        $closingBalance = new Balance(
            CreditDebit::CREDIT,
            new DateTimeImmutable('2025-01-01'),
            CurrencyCode::Euro,
            400.00
        );

        $mt940 = new Mt940Document(
            'DE89370400440532013000',
            'SEPATEST',
            '00001',
            $openingBalance,
            $closingBalance,
            [$transaction]
        );

        $camt053 = Mt940ToCamtConverter::toCamt053($mt940);
        $entry = $camt053->getEntries()[0];
        $reference = $entry->getReference();

        $this->assertEquals('E2E123456', $reference->getEndToEndId());
        $this->assertEquals('MANDATE789', $reference->getMandateId());
        $this->assertEquals('DE98ZZZ09999999999', $reference->getCreditorId());
        $this->assertEquals('KREF555', $reference->getInstructionId());
        $this->assertStringContainsString('SEPA Lastschrift', $entry->getPurpose() ?? '');
    }
}
