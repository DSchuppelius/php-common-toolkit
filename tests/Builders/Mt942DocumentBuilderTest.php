<?php
/*
 * Created on   : Sun Jul 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt942DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Mt942DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Mt942DocumentBuilderTest extends BaseTestCase {
    private Mt942DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Mt942DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentWithClosingBalance(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->build();

        $this->assertEquals('DE89370400440532013000', $document->getAccountId());
        $this->assertEquals('COMMON', $document->getReferenceId());
        $this->assertEquals('00000', $document->getStatementNumber());
        $this->assertSame($closingBalance, $document->getClosingBalance());
        $this->assertNotNull($document->getOpeningBalance()); // Opening wird berechnet
    }

    #[Test]
    public function testDocumentWithOpeningBalanceCalculatesClosing(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
        $this->assertEquals(CreditDebit::CREDIT, $document->getClosingBalance()->getCreditDebit());
    }

    #[Test]
    public function testDocumentWithBothBalancesValidatesConsistency(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        $this->assertEquals(1000.00, $document->getOpeningBalance()->getAmount());
        $this->assertEquals(1500.00, $document->getClosingBalance()->getAmount());
    }

    #[Test]
    public function testDocumentWithInconsistentBalancesThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Opening- und Closing-Salden stimmen nicht überein');

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 2000.00); // Falsch!
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();
    }

    #[Test]
    public function testDocumentWithTransactions(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $transaction1 = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123'),
            'Zahlung 1'
        );
        $transaction2 = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            200.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF456'),
            'Zahlung 2'
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction1)
            ->addTransaction($transaction2)
            ->build();

        $this->assertCount(2, $document->getTransactions());
    }

    #[Test]
    public function testAddTransactionsArray(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $transactions = [
            new Transaction(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-01-01'),
                500.00,
                CreditDebit::CREDIT,
                CurrencyCode::Euro,
                new Reference('NTRF', 'REF123')
            ),
            new Transaction(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-01-01'),
                200.00,
                CreditDebit::DEBIT,
                CurrencyCode::Euro,
                new Reference('NTRF', 'REF456')
            ),
        ];

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->addTransactions($transactions)
            ->build();

        $this->assertCount(2, $document->getTransactions());
    }

    #[Test]
    public function testAddTransactionsWithInvalidTypeThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alle Elemente müssen vom Typ Transaction sein');

        $this->builder->addTransactions(['not a transaction']);
    }

    #[Test]
    public function testDocumentWithFloorLimitIndicator(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->setFloorLimitIndicator(100.00)
            ->build();

        $this->assertEquals(100.00, $document->getFloorLimitIndicator());
    }

    #[Test]
    public function testDocumentWithDateTimeIndicator(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $dateTime = new DateTimeImmutable('2025-01-01 14:30:00');

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->setDateTimeIndicator($dateTime)
            ->build();

        $this->assertEquals($dateTime, $document->getDateTimeIndicator());
    }

    #[Test]
    public function testDocumentWithCreationDateTime(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $creationDateTime = new DateTimeImmutable('2025-01-01 15:45:00');

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->setCreationDateTime($creationDateTime)
            ->build();

        $this->assertEquals($creationDateTime, $document->getCreationDateTime());
    }

    #[Test]
    public function testImmutableBuilder(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $builder1 = $this->builder->setAccountId('ACCOUNT1');
        $builder2 = $builder1->setAccountId('ACCOUNT2');

        $this->assertNotSame($builder1, $builder2);

        $doc1 = $builder1->setClosingBalance($closingBalance)->build();
        $doc2 = $builder2->setClosingBalance($closingBalance)->build();

        $this->assertEquals('ACCOUNT1', $doc1->getAccountId());
        $this->assertEquals('ACCOUNT2', $doc2->getAccountId());
    }

    #[Test]
    public function testMissingAccountIdThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AccountId muss angegeben werden');

        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $this->builder
            ->setClosingBalance($closingBalance)
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
    public function testDebitTransactionsReduceBalance(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            300.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        // 1000 - 300 = 700
        $this->assertEquals(700.00, $document->getClosingBalance()->getAmount());
        $this->assertEquals(CreditDebit::CREDIT, $document->getClosingBalance()->getCreditDebit());
    }

    #[Test]
    public function testBalanceTurnsDebit(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 100.00);
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::DEBIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->addTransaction($transaction)
            ->build();

        // 100 - 500 = -400 (Soll)
        $this->assertEquals(400.00, $document->getClosingBalance()->getAmount());
        $this->assertEquals(CreditDebit::DEBIT, $document->getClosingBalance()->getCreditDebit());
    }

    #[Test]
    public function testReverseCalculation(): void {
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $transaction = new Transaction(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01'),
            500.00,
            CreditDebit::CREDIT,
            CurrencyCode::Euro,
            new Reference('NTRF', 'REF123')
        );

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->addTransaction($transaction)
            ->build();

        // 1500 - 500 = 1000 Opening
        $this->assertEquals(1000.00, $document->getOpeningBalance()->getAmount());
        $this->assertEquals(CreditDebit::CREDIT, $document->getOpeningBalance()->getCreditDebit());
    }
}
