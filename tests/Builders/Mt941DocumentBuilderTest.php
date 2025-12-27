<?php
/*
 * Created on   : Sun Jul 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt941DocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use CommonToolkit\Builders\Mt941DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class Mt941DocumentBuilderTest extends BaseTestCase {
    private Mt941DocumentBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new Mt941DocumentBuilder();
    }

    #[Test]
    public function testBasicDocumentCreation(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->build();

        $this->assertEquals('DE89370400440532013000', $document->getAccountId());
        $this->assertEquals('COMMON', $document->getReferenceId());
        $this->assertEquals('00000', $document->getStatementNumber());
        $this->assertSame($openingBalance, $document->getOpeningBalance());
        $this->assertSame($closingBalance, $document->getClosingBalance());
    }

    #[Test]
    public function testDocumentWithAllFields(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $closingAvailable = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1400.00);
        $forwardBalance1 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-02'), CurrencyCode::Euro, 1600.00);
        $forwardBalance2 = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-03'), CurrencyCode::Euro, 1700.00);
        $creationDateTime = new DateTimeImmutable('2025-01-01 15:30:00');

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setReferenceId('REFERENCE123')
            ->setStatementNumber('00001')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->setClosingAvailableBalance($closingAvailable)
            ->addForwardAvailableBalance($forwardBalance1)
            ->addForwardAvailableBalance($forwardBalance2)
            ->setCreationDateTime($creationDateTime)
            ->build();

        $this->assertEquals('DE89370400440532013000', $document->getAccountId());
        $this->assertEquals('REFERENCE123', $document->getReferenceId());
        $this->assertEquals('00001', $document->getStatementNumber());
        $this->assertSame($closingAvailable, $document->getClosingAvailableBalance());
        $this->assertCount(2, $document->getForwardAvailableBalances());
    }

    #[Test]
    public function testSetForwardAvailableBalances(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);
        $forwardBalances = [
            new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-02'), CurrencyCode::Euro, 1600.00),
            new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-03'), CurrencyCode::Euro, 1700.00),
        ];

        $document = $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->setForwardAvailableBalances($forwardBalances)
            ->build();

        $this->assertCount(2, $document->getForwardAvailableBalances());
    }

    #[Test]
    public function testImmutableBuilder(): void {
        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $builder1 = $this->builder->setAccountId('ACCOUNT1');
        $builder2 = $builder1->setAccountId('ACCOUNT2');

        $this->assertNotSame($builder1, $builder2);

        $doc1 = $builder1->setOpeningBalance($openingBalance)->setClosingBalance($closingBalance)->build();
        $doc2 = $builder2->setOpeningBalance($openingBalance)->setClosingBalance($closingBalance)->build();

        $this->assertEquals('ACCOUNT1', $doc1->getAccountId());
        $this->assertEquals('ACCOUNT2', $doc2->getAccountId());
    }

    #[Test]
    public function testMissingAccountIdThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AccountId muss angegeben werden');

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);
        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $this->builder
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->build();
    }

    #[Test]
    public function testMissingOpeningBalanceThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Opening- und Closing-Balance müssen für MT941 angegeben werden');

        $closingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1500.00);

        $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setClosingBalance($closingBalance)
            ->build();
    }

    #[Test]
    public function testMissingClosingBalanceThrowsException(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Opening- und Closing-Balance müssen für MT941 angegeben werden');

        $openingBalance = new Balance(CreditDebit::CREDIT, new DateTimeImmutable('2025-01-01'), CurrencyCode::Euro, 1000.00);

        $this->builder
            ->setAccountId('DE89370400440532013000')
            ->setOpeningBalance($openingBalance)
            ->build();
    }
}
