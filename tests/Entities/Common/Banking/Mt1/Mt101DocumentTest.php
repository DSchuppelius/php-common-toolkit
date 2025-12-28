<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt101DocumentTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\Common\Banking\Mt1;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Document as Mt101Document;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Transaction as Mt101Transaction;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

final class Mt101DocumentTest extends BaseTestCase {
    public function testCreateMt101Document(): void {
        $orderingCustomer = new Party(
            account: '105334213',
            name: 'Goldman Sachs Bank USA'
        );

        $document = new Mt101Document(
            sendersReference: 'GS0DX1QH8IU8IR2',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-05-12')
        );

        $this->assertEquals(MtType::MT101, $document->getMtType());
        $this->assertEquals('GS0DX1QH8IU8IR2', $document->getSendersReference());
        $this->assertEquals('1/1', $document->getMessageIndex());
        $this->assertCount(0, $document->getTransactions());
    }

    public function testMt101WithTransactions(): void {
        $orderingCustomer = new Party(
            account: '105334213',
            name: 'Goldman Sachs Bank USA'
        );

        $transaction1 = new Mt101Transaction(
            transactionReference: 'TXN-001',
            transferDetails: new TransferDetails(
                valueDate: new DateTimeImmutable('2025-05-12'),
                currency: CurrencyCode::USDollar,
                amount: 330.21
            ),
            beneficiary: new Party(
                account: '145254512',
                name: 'GP-GSBI',
                addressLine1: 'US New York 10282'
            ),
            chargesCode: ChargesCode::OUR
        );

        $transaction2 = new Mt101Transaction(
            transactionReference: 'TXN-002',
            transferDetails: new TransferDetails(
                valueDate: new DateTimeImmutable('2025-05-12'),
                currency: CurrencyCode::Euro,
                amount: 500.00
            ),
            beneficiary: new Party(
                account: 'DE89370400440532013000',
                name: 'Max Mustermann'
            ),
            remittanceInfo: 'Invoice 12345',
            chargesCode: ChargesCode::SHA
        );

        $document = new Mt101Document(
            sendersReference: 'GS0DX1QH8IU8IR2',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-05-12'),
            transactions: [$transaction1, $transaction2]
        );

        $this->assertCount(2, $document->getTransactions());
        $this->assertEquals(830.21, $document->getTotalAmount());
        $this->assertCount(2, $document->getCurrencies());
    }

    public function testMt101ToString(): void {
        $orderingCustomer = new Party(
            account: '105334213',
            name: 'Goldman Sachs Bank USA'
        );

        $orderingInstitution = new Party(bic: 'GSCRUS33XXX');

        $transaction = new Mt101Transaction(
            transactionReference: 'GS0DX1QH8IU8IR2',
            transferDetails: new TransferDetails(
                valueDate: new DateTimeImmutable('2025-05-12'),
                currency: CurrencyCode::USDollar,
                amount: 330.21
            ),
            beneficiary: new Party(
                account: '145254512',
                name: 'GP-GSBI',
                addressLine1: 'US New York 10282'
            ),
            accountWithInstitution: new Party(bic: 'BOFAUS3NXXX'),
            chargesCode: ChargesCode::OUR
        );

        $document = new Mt101Document(
            sendersReference: 'GS0DX1QH8IU8IR2',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-05-12'),
            transactions: [$transaction],
            orderingInstitution: $orderingInstitution
        );

        $output = (string) $document;

        // Sequence A
        $this->assertStringContainsString(':20:GS0DX1QH8IU8IR2', $output);
        $this->assertStringContainsString(':28D:1/1', $output);
        $this->assertStringContainsString(':50H:', $output);
        $this->assertStringContainsString(':52A:GSCRUS33XXX', $output);
        $this->assertStringContainsString(':30:250512', $output);

        // Sequence B
        $this->assertStringContainsString(':21:GS0DX1QH8IU8IR2', $output);
        $this->assertStringContainsString(':32B:USD330,21', $output);
        $this->assertStringContainsString(':57A:BOFAUS3NXXX', $output);
        $this->assertStringContainsString(':59:', $output);
        $this->assertStringContainsString(':71A:OUR', $output);
    }

    public function testMt101AddTransaction(): void {
        $orderingCustomer = new Party(account: '123456');

        $document = new Mt101Document(
            sendersReference: 'REF001',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-05-12')
        );

        $this->assertEquals(0, $document->countTransactions());

        $transaction = new Mt101Transaction(
            transactionReference: 'TXN-001',
            transferDetails: new TransferDetails(
                valueDate: new DateTimeImmutable('2025-05-12'),
                currency: CurrencyCode::Euro,
                amount: 100.00
            ),
            beneficiary: new Party(account: '789012')
        );

        $document->addTransaction($transaction);

        $this->assertEquals(1, $document->countTransactions());
        $this->assertEquals(100.00, $document->getTotalAmount());
    }

    public function testMt101MessageIndexParsing(): void {
        $orderingCustomer = new Party(account: '123456');

        $document = new Mt101Document(
            sendersReference: 'REF001',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-05-12'),
            transactions: [],
            orderingInstitution: null,
            customerReference: 'CUST-REF-001',
            messageIndex: '2/3'
        );

        $this->assertEquals('2/3', $document->getMessageIndex());
        $this->assertEquals('CUST-REF-001', $document->getCustomerReference());
    }
}
