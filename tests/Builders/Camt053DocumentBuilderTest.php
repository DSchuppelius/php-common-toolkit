<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Camt053DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\CamtParser;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Camt053DocumentBuilderTest extends BaseTestCase {
    private Camt053DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Camt053DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentCreation(): void {
        $document = $this->builder
            ->setId('CAMT053-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->build();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('CAMT053-001', $document->getId());
        $this->assertEquals('DE89370400440532013000', $document->getAccountIdentifier());
        $this->assertEquals(CurrencyCode::Euro, $document->getCurrency());
        $this->assertEquals(CamtType::CAMT053, $document->getCamtType());
    }

    #[Test]
    public function testDocumentWithAllFields(): void {
        $creationDateTime = new DateTimeImmutable('2025-01-15 18:00:00');
        // Ohne Transaktionen müssen Opening und Closing gleich sein
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00, 'CLBD');

        $document = $this->builder
            ->setId('CAMT053-002')
            ->setCreationDateTime($creationDateTime)
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setAccountOwner('Beispiel AG')
            ->setServicerBic('DEUTDEFFXXX')
            ->setMessageId('MSG-STMT-001')
            ->setSequenceNumber('00001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->build();

        $this->assertEquals('CAMT053-002', $document->getId());
        $this->assertEquals($creationDateTime, $document->getCreationDateTime());
        $this->assertEquals('Beispiel AG', $document->getAccountOwner());
        $this->assertEquals('DEUTDEFFXXX', $document->getServicerBic());
        $this->assertSame($openingBalance, $document->getOpeningBalance());
        $this->assertSame($closingBalance, $document->getClosingBalance());
    }

    #[Test]
    public function testDocumentWithTransaction(): void {
        $reference = new Reference(
            endToEndId: 'E2E-001',
            mandateId: 'MANDATE-001',
            creditorId: 'DE98ZZZ09999999999'
        );

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference,
            'REF001'
        );

        $document = $this->builder
            ->setId('CAMT053-003')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->addEntry($transaction)
            ->build();

        $this->assertCount(1, $document->getEntries());
        $this->assertEquals('E2E-001', $document->getEntries()[0]->getReference()->getEndToEndId());
    }

    #[Test]
    public function testAddMultipleEntries(): void {
        $reference1 = new Reference(endToEndId: 'E2E-001');
        $reference2 = new Reference(endToEndId: 'E2E-002');

        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                1000.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                $reference1
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                500.00,
                CurrencyCode::Euro,
                CreditDebit::DEBIT,
                $reference2
            ),
        ];

        $document = $this->builder
            ->setId('CAMT053-004')
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
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 2000.00, 'OPBD');
        $reference = new Reference(endToEndId: 'E2E-001');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            800.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference
        );

        $document = $this->builder
            ->setId('CAMT053-005')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        $this->assertNotNull($document->getClosingBalance());
        $this->assertEquals(2800.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    #[Test]
    public function testBalanceConsistencyValidation(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1500.00, 'CLBD');
        $reference = new Reference(endToEndId: 'E2E-001');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference
        );

        $document = $this->builder
            ->setId('CAMT053-006')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        // Konsistenz geprüft, sollte ohne Exception durchlaufen
        $this->assertEquals(1000.00, $document->getOpeningBalance()->getAmount());
        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
    }

    #[Test]
    public function testInconsistentBalancesThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Opening- und Closing-Salden stimmen nicht überein');

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00, 'OPBD');
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 2000.00, 'CLBD'); // Falsch!
        $reference = new Reference(endToEndId: 'E2E-001');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference
        );

        $this->builder
            ->setId('CAMT053-007')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();
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
            ->setId('CAMT053-001')
            ->setCurrency(CurrencyCode::Euro)
            ->build();
    }

    #[Test]
    public function testMissingCurrencyThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Currency muss angegeben werden');

        $this->builder
            ->setId('CAMT053-001')
            ->setAccountIdentifier('DE89370400440532013000')
            ->build();
    }

    #[Test]
    public function testReverseCalculateOpeningBalance(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 3000.00, 'CLBD');
        $reference = new Reference(endToEndId: 'E2E-001');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            1000.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference
        );

        $document = $this->builder
            ->setId('CAMT053-008')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setClosingBalance($closingBalance)
            ->addEntry($transaction)
            ->build();

        // 3000 - 1000 = 2000
        $this->assertNotNull($document->getOpeningBalance());
        $this->assertEquals(2000.00, $document->getOpeningBalance()->getAmount());
        $this->assertTrue($document->getOpeningBalance()->isCredit());
    }

    #[Test]
    public function testDebitTransactionReducesBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00, 'OPBD');
        $reference = new Reference(endToEndId: 'E2E-001');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            1500.00,
            CurrencyCode::Euro,
            CreditDebit::DEBIT,
            $reference
        );

        $document = $this->builder
            ->setId('CAMT053-009')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        // 5000 - 1500 = 3500
        $this->assertEquals(3500.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    // ============================================================
    // Roundtrip Tests
    // ============================================================

    #[Test]
    public function testBuilderToXmlRoundtrip(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 10000.00, 'OPBD');
        $reference = new Reference(
            endToEndId: 'E2E-ROUNDTRIP-001',
            mandateId: 'MANDATE-001'
        );

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            2500.00,
            CurrencyCode::Euro,
            CreditDebit::CREDIT,
            $reference,
            'REF001',
            null,
            'BOOK',
            false,
            'Roundtrip Verwendungszweck'
        );

        $document1 = $this->builder
            ->setId('CAMT053-ROUNDTRIP')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntry($transaction)
            ->build();

        // Konvertiere zu XML und parse zurück
        $xml = $document1->toXml();
        $document2 = CamtParser::parse($xml);

        // Vergleiche Kerndaten
        $this->assertEquals($document1->getId(), $document2->getId());
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->getCurrency(), $document2->getCurrency());
        $this->assertEquals($document1->countEntries(), $document2->countEntries());

        // Vergleiche Salden
        $this->assertEquals(
            $document1->getOpeningBalance()->getAmount(),
            $document2->getOpeningBalance()->getAmount()
        );
        $this->assertEquals(
            $document1->getClosingBalance()->getAmount(),
            $document2->getClosingBalance()->getAmount()
        );
    }

    #[Test]
    public function testBuilderToXmlRoundtripPreservesTransactionData(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00, 'OPBD');
        $reference1 = new Reference(endToEndId: 'E2E-001');
        $reference2 = new Reference(endToEndId: 'E2E-002');

        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                1000.00,
                CurrencyCode::Euro,
                CreditDebit::CREDIT,
                $reference1
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                500.00,
                CurrencyCode::Euro,
                CreditDebit::DEBIT,
                $reference2
            ),
        ];

        $document1 = $this->builder
            ->setId('CAMT053-MULTI')
            ->setAccountIdentifier('DE89370400440532013000')
            ->setCurrency(CurrencyCode::Euro)
            ->setOpeningBalance($openingBalance)
            ->addEntries($transactions)
            ->build();

        $xml = $document1->toXml();
        $document2 = CamtParser::parse($xml);

        $this->assertEquals(2, $document2->countEntries());

        // Beträge vergleichen
        $amounts1 = array_map(fn($e) => $e->getAmount(), $document1->getEntries());
        $amounts2 = array_map(fn($e) => $e->getAmount(), $document2->getEntries());
        sort($amounts1);
        sort($amounts2);
        $this->assertEquals($amounts1, $amounts2);
    }

    // ========================================
    // Parse → toXml → Parse Roundtrip Tests
    // ========================================

    #[Test]
    public function testParseToXmlRoundtripFromSampleFile(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        $this->assertFileExists($sampleFile, 'Sample-Datei CAMT.053 existiert nicht');

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parse($originalContent);

        // Document → XML → Parse
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parse($regeneratedXml);

        // Kerndaten vergleichen
        $this->assertEquals($document1->getAccountIdentifier(), $document2->getAccountIdentifier());
        $this->assertEquals($document1->getCurrency(), $document2->getCurrency());
        $this->assertEquals($document1->getCamtType(), $document2->getCamtType());

        // Transaktionsanzahl vergleichen
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesBalances(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parse($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parse($regeneratedXml);

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
            $this->assertEquals(
                $document1->getClosingBalance()->getCreditDebit(),
                $document2->getClosingBalance()->getCreditDebit()
            );
        }
    }

    #[Test]
    public function testParseToXmlRoundtripPreservesTransactionAmounts(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/CAMT/11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);

        /** @var Document $document1 */
        $document1 = CamtParser::parse($originalContent);
        $regeneratedXml = $document1->toXml();

        /** @var Document $document2 */
        $document2 = CamtParser::parse($regeneratedXml);

        $this->assertEquals($document1->countEntries(), $document2->countEntries());

        // Transaktionsbeträge vergleichen
        $amounts1 = array_map(fn($e) => $e->getAmount(), $document1->getEntries());
        $amounts2 = array_map(fn($e) => $e->getAmount(), $document2->getEntries());
        sort($amounts1);
        sort($amounts2);

        $this->assertEquals($amounts1, $amounts2);

        // Credit/Debit vergleichen
        $credits1 = array_filter($document1->getEntries(), fn($e) => $e->isCredit());
        $credits2 = array_filter($document2->getEntries(), fn($e) => $e->isCredit());
        $this->assertCount(count($credits1), $credits2);
    }
}
