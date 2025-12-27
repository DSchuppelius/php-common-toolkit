<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Mt940DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\Mt940DocumentParser;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Mt940DocumentBuilderTest extends BaseTestCase {
    private Mt940DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Mt940DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentCreation(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->build();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('DE89370400440532013000', $document->getAccountId());
        $this->assertEquals('COMMON', $document->getReferenceId());
        $this->assertEquals('00000', $document->getStatementNumber());
    }

    #[Test]
    public function testDocumentWithAllFields(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00);

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setReferenceId('REF-001')
            ->setStatementNumber('00001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->build();

        $this->assertEquals('REF-001', $document->getReferenceId());
        $this->assertEquals('00001', $document->getStatementNumber());
    }

    #[Test]
    public function testDocumentWithTransaction(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            'Zahlung erhalten'
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertCount(1, $document->getTransactions());
        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
    }

    #[Test]
    public function testCalculateClosingBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    #[Test]
    public function testReverseCalculateOpeningBalance(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1500.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertEquals(1000.00, $document->getOpeningBalance()->getAmount());
        $this->assertTrue($document->getOpeningBalance()->isCredit());
    }

    #[Test]
    public function testInconsistentBalancesThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Opening- und Closing-Salden stimmen nicht überein');

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 2000.00); // Falsch!
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference
        );

        $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();
    }

    #[Test]
    public function testMissingBalancesThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mindestens ein Saldo (Opening oder Closing) muss angegeben werden');

        $this->builder
            ->setAccountId('DE89370400440532013000')
            ->build();
    }

    #[Test]
    public function testImmutableBuilder(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);

        $builder1 = $this->builder->setAccountId('ACCOUNT1');
        $builder2 = $builder1->setAccountId('ACCOUNT2');

        $this->assertNotSame($builder1, $builder2);

        $doc1 = $builder1->setOpeningBalance($openingBalance)->build();
        $doc2 = $builder2->setOpeningBalance($openingBalance)->build();

        $this->assertEquals('ACCOUNT1', $doc1->getAccountId());
        $this->assertEquals('ACCOUNT2', $doc2->getAccountId());
    }

    #[Test]
    public function testDebitTransactionReducesBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            300.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            $reference
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertEquals(700.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isCredit());
    }

    #[Test]
    public function testBalanceTurnsDebit(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 100.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            500.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            $reference
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        // 100 - 500 = -400
        $this->assertEquals(400.00, $document->getClosingBalance()->getAmount());
        $this->assertTrue($document->getClosingBalance()->isDebit());
    }

    // ============================================================
    // Roundtrip Tests
    // ============================================================

    #[Test]
    public function testBuilderToStringRoundtrip(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 10000.00);
        $reference = new Reference('NTRF', 'REF123');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-15'),
            2500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            'Roundtrip Testzahlung'
        );

        $document1 = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setReferenceId('ROUNDTRIP')
            ->setStatementNumber('00001')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        // Konvertiere zu String und parse zurück
        $mt940String = (string) $document1;
        $document2 = Mt940DocumentParser::parse($mt940String);

        // Vergleiche Kerndaten
        $this->assertEquals($document1->getAccountId(), $document2->getAccountId());
        $this->assertEquals($document1->getReferenceId(), $document2->getReferenceId());
        $this->assertEquals($document1->getStatementNumber(), $document2->getStatementNumber());
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
    public function testBuilderToStringRoundtripWithMultipleTransactions(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 5000.00);
        $reference1 = new Reference('NTRF', 'REF001');
        $reference2 = new Reference('NTRF', 'REF002');
        $reference3 = new Reference('NTRF', 'REF003');

        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                1000.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                $reference1
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                500.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                $reference2
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-15'),
                new DateTimeImmutable('2025-01-15'),
                250.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                $reference3
            ),
        ];

        $document1 = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setReferenceId('MULTI')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transactions[0])
            ->addTransaction($transactions[1])
            ->addTransaction($transactions[2])
            ->build();

        $mt940String = (string) $document1;
        $document2 = Mt940DocumentParser::parse($mt940String);

        $this->assertEquals(3, $document2->countEntries());

        // Closing Balance vergleichen: 5000 + 1000 - 500 + 250 = 5750
        $this->assertEquals(5750.00, $document2->getClosingBalance()->getAmount());

        // Summen vergleichen
        $this->assertEquals($document1->getTotalCredit(), $document2->getTotalCredit());
        $this->assertEquals($document1->getTotalDebit(), $document2->getTotalDebit());
    }

    #[Test]
    public function testRoundtripPreservesTransactionDetails(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-15'), CurrencyCode::Euro, 1000.00);
        $reference = new Reference('NTRF', 'REF-DETAILS');

        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-15'),
            new DateTimeImmutable('2025-01-16'), // Unterschiedliches Valutadatum
            750.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            $reference,
            'Detaillierter Verwendungszweck Test'
        );

        $document1 = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        $mt940String = (string) $document1;
        $document2 = Mt940DocumentParser::parse($mt940String);

        $txn1 = $document1->getTransactions()[0];
        $txn2 = $document2->getTransactions()[0];

        $this->assertEquals($txn1->getAmount(), $txn2->getAmount());
        $this->assertEquals($txn1->getCreditDebit(), $txn2->getCreditDebit());
        $this->assertEquals($txn1->getBookingDate()->format('Y-m-d'), $txn2->getBookingDate()->format('Y-m-d'));
    }

    // ========================================
    // Parse → ToString → Parse Roundtrip Tests
    // ========================================

    #[Test]
    public function testParseToStringRoundtripSimple(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/MT/example.mt940';
        $this->assertFileExists($sampleFile, 'Sample-Datei example.mt940 existiert nicht');

        $originalContent = file_get_contents($sampleFile);
        $document1 = Mt940DocumentParser::parse($originalContent);

        // Document → String → Parse
        $regeneratedString = (string) $document1;
        $document2 = Mt940DocumentParser::parse($regeneratedString);

        // Kerndaten vergleichen
        $this->assertEquals($document1->getAccountId(), $document2->getAccountId());
        $this->assertEquals($document1->getReferenceId(), $document2->getReferenceId());
        $this->assertEquals($document1->getStatementNumber(), $document2->getStatementNumber());

        // Salden vergleichen
        $this->assertEquals(
            $document1->getOpeningBalance()->getAmount(),
            $document2->getOpeningBalance()->getAmount()
        );
        $this->assertEquals(
            $document1->getClosingBalance()->getAmount(),
            $document2->getClosingBalance()->getAmount()
        );

        // Transaktionsanzahl vergleichen
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
    }

    #[Test]
    public function testParseToStringRoundtripFull(): void {
        // Nutze example.mt940 statt example_full.mt940 (das CR/DR Format wird nicht unterstützt)
        $sampleFile = __DIR__ . '/../../.samples/Banking/MT/example.mt940';
        $this->assertFileExists($sampleFile, 'Sample-Datei example.mt940 existiert nicht');

        $originalContent = file_get_contents($sampleFile);
        $document1 = Mt940DocumentParser::parse($originalContent);

        // Document → String → Parse
        $regeneratedString = (string) $document1;
        $document2 = Mt940DocumentParser::parse($regeneratedString);

        // Kerndaten vergleichen
        $this->assertEquals($document1->getAccountId(), $document2->getAccountId());
        $this->assertEquals($document1->getReferenceId(), $document2->getReferenceId());

        // Salden vergleichen
        $this->assertEquals(
            $document1->getOpeningBalance()->getAmount(),
            $document2->getOpeningBalance()->getAmount()
        );
        $this->assertEquals(
            $document1->getClosingBalance()->getAmount(),
            $document2->getClosingBalance()->getAmount()
        );
        $this->assertEquals(
            $document1->getOpeningBalance()->getCurrency(),
            $document2->getOpeningBalance()->getCurrency()
        );

        // Transaktionen detailliert vergleichen
        $this->assertEquals($document1->countEntries(), $document2->countEntries());
        $this->assertEquals($document1->getTotalCredit(), $document2->getTotalCredit());
        $this->assertEquals($document1->getTotalDebit(), $document2->getTotalDebit());

        // Einzelne Transaktionen prüfen
        $transactions1 = $document1->getTransactions();
        $transactions2 = $document2->getTransactions();

        for ($i = 0; $i < count($transactions1); $i++) {
            $this->assertEquals(
                $transactions1[$i]->getAmount(),
                $transactions2[$i]->getAmount(),
                "Transaktion $i: Betrag stimmt nicht überein"
            );
            $this->assertEquals(
                $transactions1[$i]->getCreditDebit(),
                $transactions2[$i]->getCreditDebit(),
                "Transaktion $i: Credit/Debit stimmt nicht überein"
            );
        }
    }

    #[Test]
    public function testParseToStringRoundtripPreservesDates(): void {
        $sampleFile = __DIR__ . '/../../.samples/Banking/MT/example.mt940';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);
        $document1 = Mt940DocumentParser::parse($originalContent);
        $regeneratedString = (string) $document1;
        $document2 = Mt940DocumentParser::parse($regeneratedString);

        // Opening Balance Datum
        $this->assertEquals(
            $document1->getOpeningBalance()->getDate()->format('Y-m-d'),
            $document2->getOpeningBalance()->getDate()->format('Y-m-d')
        );

        // Closing Balance Datum
        $this->assertEquals(
            $document1->getClosingBalance()->getDate()->format('Y-m-d'),
            $document2->getClosingBalance()->getDate()->format('Y-m-d')
        );

        // Transaktionsdaten
        $transactions1 = $document1->getTransactions();
        $transactions2 = $document2->getTransactions();

        for ($i = 0; $i < count($transactions1); $i++) {
            $this->assertEquals(
                $transactions1[$i]->getBookingDate()->format('Y-m-d'),
                $transactions2[$i]->getBookingDate()->format('Y-m-d'),
                "Transaktion $i: Buchungsdatum stimmt nicht überein"
            );
            $this->assertEquals(
                $transactions1[$i]->getValutaDate()->format('Y-m-d'),
                $transactions2[$i]->getValutaDate()->format('Y-m-d'),
                "Transaktion $i: Valutadatum stimmt nicht überein"
            );
        }
    }
}