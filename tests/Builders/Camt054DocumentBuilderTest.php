<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt054DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Camt054DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\CamtParser;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Camt054DocumentBuilderTest extends BaseTestCase {
    private Camt054DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Camt054DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentCreation(): void {
        $document = $this->builder
            ->setId('CAMT054-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('CAMT054-001', $document->getId());
        $this->assertEquals('DE89370400440532013000', $document->getAccountIdentifier());
        $this->assertEquals(CurrencyCode::Euro, $document->getCurrency());
        $this->assertEquals(CamtType::CAMT054, $document->getCamtType());
    }

    #[Test]
    public function testDocumentWithAllFields(): void {
        $creationDateTime = new DateTimeImmutable('2025-01-15 12:45:00');

        $document = $this->builder
            ->setId('CAMT054-002')
            ->setCreationDateTime($creationDateTime)
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setAccountOwner('Notification GmbH')
            ->setServicerBic('HYVEDEMM')
            ->setMessageId('MSG-NTFCTN-001')
            ->setSequenceNumber('00001')
            ->build();

        $this->assertEquals('CAMT054-002', $document->getId());
        $this->assertEquals($creationDateTime, $document->getCreationDateTime());
        $this->assertEquals('Notification GmbH', $document->getAccountOwner());
        $this->assertEquals('HYVEDEMM', $document->getServicerBic());
        $this->assertEquals('MSG-NTFCTN-001', $document->getMessageId());
        $this->assertEquals('00001', $document->getSequenceNumber());
    }

    #[Test]
    public function testDocumentWithTransaction(): void {
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            258808.98,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            'REF001',
            'ACCT-REF-001',
            'BOOK',
            false,
            'INSTR-001',
            'E2E-001',
            'Liquiditätstransfer',
            null, // purposeCode
            'LIQT'
        );

        $document = $this->builder
            ->setId('CAMT054-003')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        $this->assertCount(1, $document->getEntries());

        /** @var Transaction $entry */
        $entry = $document->getEntries()[0];
        $this->assertEquals(258808.98, $entry->getAmount());
        $this->assertTrue($entry->isDebit());
        $this->assertEquals('LIQT', $entry->getBankTransactionCode());
        $this->assertEquals('INSTR-001', $entry->getInstructionId());
        $this->assertEquals('E2E-001', $entry->getEndToEndId());
    }

    #[Test]
    public function testAddMultipleEntries(): void {
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
        ];

        $document = $this->builder
            ->setId('CAMT054-004')
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
    public function testTransactionWithAgentBics(): void {
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            100000.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001',
            'ACCT-REF-001',
            'BOOK',
            false,
            'INSTR-001',
            'E2E-001',
            'Zahlung',
            null, // purposeCode
            'PMNT',
            null, // domainCode
            null, // familyCode
            null, // subFamilyCode
            null, // returnReason
            null, // localInstrumentCode
            'ZYBUDEFFSEK', // instructingAgentBic
            'MARKDEFFSCL', // instructedAgentBic
            'COBADEFFXXX', // debtorAgentBic
            'DEUTDEFFXXX'  // creditorAgentBic
        );

        $document = $this->builder
            ->setId('CAMT054-005')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        /** @var Transaction $entry */
        $entry = $document->getEntries()[0];
        $this->assertEquals('ZYBUDEFFSEK', $entry->getInstructingAgentBic());
        $this->assertEquals('COBADEFFXXX', $entry->getDebtorAgentBic());
        $this->assertEquals('DEUTDEFFXXX', $entry->getCreditorAgentBic());
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
            ->setId('CAMT054-001')
            ->setCurrency(CurrencyCode::Euro)
            ->build();
    }

    #[Test]
    public function testMissingCurrencyThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Currency muss angegeben werden');

        $this->builder
            ->setId('CAMT054-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->build();
    }

    #[Test]
    public function testEmptyDocumentWithoutEntries(): void {
        // CAMT.054 kann auch ohne Entries erstellt werden
        $document = $this->builder
            ->setId('CAMT054-006')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();

        $this->assertCount(0, $document->getEntries());
    }

    #[Test]
    public function testReversalTransaction(): void {
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001',
            'ACCT-REF-001',
            'BOOK',
            true // isReversal
        );

        $document = $this->builder
            ->setId('CAMT054-007')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        /** @var Transaction $entry */
        $entry = $document->getEntries()[0];
        $this->assertTrue($entry->isReversal());
    }

    // ============================================================
    // Roundtrip Tests
    // ============================================================

    #[Test]
    public function testBuilderToXmlRoundtrip(): void {
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            50000.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            'REF001',
            'ACCT-REF-001',
            'BOOK',
            false,
            'INSTR-001',
            'E2E-ROUNDTRIP-001',
            'Roundtrip Zahlungsinformation',
            'PMNT'
        );

        $document1 = $this->builder
            ->setId('CAMT054-ROUNDTRIP')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        // Konvertiere zu XML und parse zurück
        $xml = $document1->toXml();
        $document2 = CamtParser::parseCamt054($xml);

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
    public function testBuilderToXmlRoundtripWithMultipleNotifications(): void {
        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                10000.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                'REF001'
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                5000.00,
                CurrencyCode::Euro,
                CreditDebit::DEBIT,
                'REF002'
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                2500.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                'REF003'
            ),
        ];

        $document1 = $this->builder
            ->setId('CAMT054-MULTI')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntries($transactions)
            ->build();

        $xml = $document1->toXml();
        $document2 = CamtParser::parseCamt054($xml);

        $this->assertEquals(3, $document2->countEntries());

        // Summen vergleichen
        $totalAmount1 = array_sum(array_map(fn($e) => $e->getAmount(), $document1->getEntries()));
        $totalAmount2 = array_sum(array_map(fn($e) => $e->getAmount(), $document2->getEntries()));
        $this->assertEquals($totalAmount1, $totalAmount2);
    }

    // ========================================
    // Parse → toXml → Parse Roundtrip Tests
    // ========================================

    #[Test]
    public function testParseToXmlRoundtripFromSampleFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/1. camt.054- Beispieldatei liquidity transfer order.xml';
        $this->assertFileExists($sampleFile, 'Sample-Datei CAMT.054 existiert nicht');

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt054($originalContent);

        // Document → XML → Parse
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt054($regeneratedXml);

        // Kerndaten vergleichen
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->getCamtType(), $document2->getCamtType());

        // Transaktionsanzahl vergleichen
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesTransactionAmounts(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/1. camt.054- Beispieldatei liquidity transfer order.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt054($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt054($regeneratedXml);

        $this->assertEquals($document1->countEntries(), $document2->countEntries());

        if ($document1->countEntries() > 0) {
            // Transaktionsbeträge vergleichen
            $amounts1 = array_map(fn($e) => $e->getAmount(), $document1->getEntries());
            $amounts2 = array_map(fn($e) => $e->getAmount(), $document2->getEntries());
            sort($amounts1);
            sort($amounts2);

            $this->assertEquals($amounts1, $amounts2);

            // Credit/Debit vergleichen
            $debits1 = array_filter($document1->getEntries(), fn($e) => $e->isDebit());
            $debits2 = array_filter($document2->getEntries(), fn($e) => $e->isDebit());
            $this->assertCount(count($debits1), $debits2);
        }
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesDates(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/1. camt.054- Beispieldatei liquidity transfer order.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parseCamt054($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parseCamt054($regeneratedXml);

        $entries1 = $document1->getEntries();
        $entries2 = $document2->getEntries();

        for ($i = 0; $i < count($entries1); $i++) {
            // Buchungsdatum
            $this->assertEquals(
                $entries1[$i]->getBookingDate()->format('Y-m-d'),
                $entries2[$i]->getBookingDate()->format('Y-m-d'),
                "Transaktion $i: Buchungsdatum stimmt nicht überein"
            );

            // Valutadatum
            $this->assertEquals(
                $entries1[$i]->getValutaDate()->format('Y-m-d'),
                $entries2[$i]->getValutaDate()->format('Y-m-d'),
                "Transaktion $i: Valutadatum stimmt nicht überein"
            );
        }
    }
}
