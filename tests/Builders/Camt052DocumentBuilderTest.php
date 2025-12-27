<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt052DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Camt052DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\CamtParser;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Camt052DocumentBuilderTest extends BaseTestCase {
    private Camt052DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Camt052DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentCreation(): void {
        $document = $this->builder
            ->setId('CAMT052-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('CAMT052-001', $document->getId());
        $this->assertEquals('DE89370400440532013000', $document->getAccountIdentifier());
        $this->assertEquals(CurrencyCode::Euro, $document->getCurrency());
        $this->assertEquals(CamtType::CAMT052, $document->getCamtType());
    }

    #[Test]
    public function testDocumentWithAllFields(): void {
        $creationDateTime = new DateTimeImmutable('2025-01-15 10:30:00');
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1500.00, 'CLBD');

        $document = $this->builder
            ->setId('CAMT052-002')
            ->setCreationDateTime($creationDateTime)
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setAccountOwner('Test GmbH')
            ->setServicerBic('COBADEFFXXX')
            ->setMessageId('MSG-001')
            ->setSequenceNumber('00001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->build();

        $this->assertEquals('CAMT052-002', $document->getId());
        $this->assertEquals($creationDateTime, $document->getCreationDateTime());
        $this->assertEquals('Test GmbH', $document->getAccountOwner());
        $this->assertEquals('COBADEFFXXX', $document->getServicerBic());
        $this->assertEquals('MSG-001', $document->getMessageId());
        $this->assertEquals('00001', $document->getSequenceNumber());
        $this->assertSame($openingBalance, $document->getOpeningBalance());
        $this->assertSame($closingBalance, $document->getClosingBalance());
    }

    #[Test]
    public function testDocumentWithTransaction(): void {
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT052-003')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        $this->assertCount(1, $document->getEntries());
        $this->assertEquals(500.00, $document->getEntries()[0]->getAmount());
    }

    #[Test]
    public function testAddMultipleEntries(): void {
        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                500.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                'REF001'
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                200.00,
                CurrencyCode::Euro,
                CreditDebit::DEBIT,
                'REF002'
            ),
        ];

        $document = $this->builder
            ->setId('CAMT052-004')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntries($transactions)
            ->build();

        $this->assertCount(2, $document->getEntries());
    }

    #[Test]
    public function testAddEntriesWithInvalidTypeThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alle Elemente müssen vom Typ Transaction sein');

        $this->builder->addEntries(['not a transaction']);
    }

    #[Test]
    public function testCalculateClosingBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT052-005')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        $this->assertNotNull($document->getClosingBalance());
        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    #[Test]
    public function testReverseCalculateOpeningBalance(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1500.00, 'CLBD');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT052-006')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        $this->assertNotNull($document->getOpeningBalance());
        $this->assertEquals(1000.00, $document->getOpeningBalance()->getAmount());
        $this->assertTrue($document->getOpeningBalance()->isCredit());
    }

    #[Test]
    public function testImmutableBuilder(): void {
        $builder1 = $this->builder->setId('ID1');
        $builder2 = $builder1->setId('ID2');

        $this->assertNotSame($builder1, $builder2);

        $doc1 = $builder1
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();
        $doc2 = $builder2
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();

        $this->assertEquals('ID1', $doc1->getId());
        $this->assertEquals('ID2', $doc2->getId());
    }

    #[Test]
    public function testMissingIdThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Id muss angegeben werden');

        $this->builder
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();
    }

    #[Test]
    public function testMissingAccountIdentifierThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AccountIdentifier muss angegeben werden');

        $this->builder
            ->setId('CAMT052-001')
            ->setCurrency(CurrencyCode::Euro)
            ->build();
    }

    #[Test]
    public function testMissingCurrencyThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Currency muss angegeben werden');

        $this->builder
            ->setId('CAMT052-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->build();
    }

    #[Test]
    public function testDebitTransactionReducesBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            300.00,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT052-007')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        // 1000 - 300 = 700
        $this->assertEquals(700.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    #[Test]
    public function testBalanceTurnsDebit(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 100.00, 'OPBD');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT052-008')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        // 100 - 500 = -400
        $this->assertEquals(400.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isDebit());
    }

    // ============================================================
    // Roundtrip Tests
    // ============================================================

    #[Test]
    public function testBuilderToXmlRoundtrip(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001',
            null,
            'BOOK',
            false,
            'Roundtrip Test',
            null,
            null,
            'PMNT',
            'RCDT',
            'ESCT'
        );

        $document1 = $this->builder
            ->setId('CAMT052-ROUNDTRIP')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        // Konvertiere zu XML und parse zurück
        $xml = $document1->toXml();
        $document2 = CamtParser::parseCamt052($xml);

        // Vergleiche Kerndaten
        $this->assertEquals($document1->getId(), $document2->getId());
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->getCurrency(), $document2->getCurrency());
        $this->assertEquals($document1->countEntries(), $document2->countEntries());

        // Vergleiche Transaktionsdaten
        $entry1 = $document1->getEntries()[0];
        $entry2 = $document2->getEntries()[0];
        $this->assertEquals($entry1->getAmount(), $entry2->getAmount());
        $this->assertEquals($entry1->getCreditDebit(), $entry2->getCreditDebit());
    }

    #[Test]
    public function testBuilderToXmlRoundtripWithMultipleTransactions(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00, 'OPBD');

        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                1000.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                'REF001'
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                500.00,
                CurrencyCode::Euro,
                CreditDebit::DEBIT,
                'REF002'
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                250.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                'REF003'
            ),
        ];

        $document1 = $this->builder
            ->setId('CAMT052-MULTI')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntries($transactions)
            ->build();

        $xml = $document1->toXml();
        $document2 = CamtParser::parseCamt052($xml);

        $this->assertEquals(3, $document2->countEntries());

        // Summen vergleichen
        $totalCredit1 = array_sum(array_map(
            fn($e) => $e->isCredit() ? $e->getAmount() : 0,
            $document1->getEntries()
        ));
        $totalCredit2 = array_sum(array_map(
            fn($e) => $e->isCredit() ? $e->getAmount() : 0,
            $document2->getEntries()
        ));

        $this->assertEquals($totalCredit1, $totalCredit2);
    }

    // ========================================
    // Parse → toXml → Parse Roundtrip Tests
    // ========================================

    #[Test]
    public function testParseToXmlRoundtripFromSampleFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        $this->assertFileExists($sampleFile, 'Sample-Datei CAMT.052 existiert nicht');

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt052($originalContent);

        // Document → XML → Parse
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt052($regeneratedXml);

        // Kerndaten vergleichen
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->getCurrency(), $document2->getCurrency());
        $this->assertEquals($document1->getCamtType(), $document2->getCamtType());

        // Transaktionsanzahl vergleichen
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesBalances(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt052($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt052($regeneratedXml);

        // Opening Balance
        if ($document1->getOpeningBalance() !== null && $document2->getOpeningBalance() !== null) {
            $this->assertEquals(
                $document1->getOpeningBalance()->getAmount(),
                $document2->getOpeningBalance()->getAmount()
            );
            $this->assertEquals(
                $document1->getOpeningBalance()->getCreditDebit(),
                $document2->getOpeningBalance()->getCreditDebit()
            );
        }

        // Closing Balance
        if ($document1->getClosingBalance() !== null && $document2->getClosingBalance() !== null) {
            $this->assertEquals(
                $document1->getClosingBalance()->getAmount(),
                $document2->getClosingBalance()->getAmount()
            );
        }
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesTransactionAmounts(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt052($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt052($regeneratedXml);

        $this->assertEquals($document1->countEntries(), $document2->countEntries());

        if ($document1->countEntries() > 0) {
            // Transaktionsbeträge vergleichen
            $amounts1 = array_map(fn($e) => $e->getAmount(), $document1->getEntries());
            $amounts2 = array_map(fn($e) => $e->getAmount(), $document2->getEntries());
            sort($amounts1);
            sort($amounts2);

            $this->assertEquals($amounts1, $amounts2);
        }
    }
}
