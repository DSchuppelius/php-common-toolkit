<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt10xConverterTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\Banking;

use CommonToolkit\Converters\Banking\Mt10xConverter;
use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Document as Mt101Document;
use CommonToolkit\Entities\Common\Banking\Mt1\Type101\Transaction as Mt101Transaction;
use CommonToolkit\Entities\Common\Banking\Mt1\Type103\Document as Mt103Document;
use CommonToolkit\Enums\Common\Banking\BankOperationCode;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für Mt10xConverter - Konvertierung zwischen MT101 und MT103.
 */
final class Mt10xConverterTest extends BaseTestCase {
    /**
     * Test: MT101 → MT103 Array (Aufteilung in Einzelüberweisungen)
     */
    public function testMt101ToMt103Array(): void {
        $mt101 = $this->createMt101WithTransactions();

        $mt103Array = Mt10xConverter::mt101ToMt103Array($mt101);

        // 3 Transaktionen → 3 MT103-Dokumente
        $this->assertCount(3, $mt103Array);

        // Erstes MT103 prüfen
        $first = $mt103Array[0];
        $this->assertInstanceOf(Mt103Document::class, $first);
        $this->assertStringStartsWith($mt101->getSendersReference(), $first->getSendersReference());
        $this->assertEquals(500.00, $first->getTransferDetails()->getAmount());
        $this->assertEquals('Max Mustermann', $first->getOrderingCustomer()->getName());
        $this->assertEquals('Empfänger 1', $first->getBeneficiary()->getName());

        // Zweites MT103 prüfen
        $second = $mt103Array[1];
        $this->assertEquals(250.00, $second->getTransferDetails()->getAmount());
        $this->assertEquals('Empfänger 2', $second->getBeneficiary()->getName());

        // Drittes MT103 prüfen
        $third = $mt103Array[2];
        $this->assertEquals(100.00, $third->getTransferDetails()->getAmount());
        $this->assertEquals(CurrencyCode::USDollar, $third->getTransferDetails()->getCurrency());
    }

    /**
     * Test: MT103 Array → MT101 (Zusammenfassung zu Sammelüberweisung)
     */
    public function testMt103ArrayToMt101(): void {
        $mt103Documents = $this->createMt103Documents();

        $mt101 = Mt10xConverter::mt103ArrayToMt101(
            $mt103Documents,
            'BATCH-2025-001',
            new DateTimeImmutable('2025-03-15')
        );

        $this->assertInstanceOf(Mt101Document::class, $mt101);
        $this->assertEquals('BATCH-2025-001', $mt101->getSendersReference());
        $this->assertCount(2, $mt101->getTransactions());

        // Transaktionsdetails prüfen
        $transactions = $mt101->getTransactions();
        $this->assertEquals(1000.00, $transactions[0]->getTransferDetails()->getAmount());
        $this->assertEquals(750.00, $transactions[1]->getTransferDetails()->getAmount());
    }

    /**
     * Test: Leere MT103-Liste → Fehler
     */
    public function testMt103ArrayToMt101EmptyThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mindestens ein MT103-Dokument erforderlich');

        Mt10xConverter::mt103ArrayToMt101([], 'EMPTY-TEST');
    }

    /**
     * Test: MT101 Summenberechnung
     */
    public function testCalculateMt101Totals(): void {
        $mt101 = $this->createMt101WithTransactions();

        $totals = Mt10xConverter::calculateMt101Totals($mt101);

        $this->assertEquals(3, $totals['count']);
        $this->assertTrue($totals['mixed_currencies']);
        $this->assertEquals(0.0, $totals['total']); // Mixed currencies → keine Summe
    }

    /**
     * Test: MT101 Summenberechnung mit einheitlicher Währung
     */
    public function testCalculateMt101TotalsSingleCurrency(): void {
        $mt101 = $this->createMt101SingleCurrency();

        $totals = Mt10xConverter::calculateMt101Totals($mt101);

        $this->assertEquals(2, $totals['count']);
        $this->assertFalse($totals['mixed_currencies']);
        $this->assertEquals(750.00, $totals['total']); // 500 + 250
        $this->assertEquals('EUR', $totals['currency']);
    }

    /**
     * Test: MT103 Validierung - gültig
     */
    public function testValidateMt103Valid(): void {
        $mt103 = $this->createValidMt103();

        $result = Mt10xConverter::validateMt103($mt103);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test: MT103 Validierung - ungültig
     */
    public function testValidateMt103Invalid(): void {
        // MT103 mit leerem Begünstigten
        $mt103 = new Mt103Document(
            sendersReference: 'REF-001',
            transferDetails: new TransferDetails(
                new DateTimeImmutable('2025-01-15'),
                CurrencyCode::Euro,
                0.00 // Ungültiger Betrag
            ),
            orderingCustomer: new Party(), // Leere Party
            beneficiary: new Party() // Leere Party
        );

        $result = Mt10xConverter::validateMt103($mt103);

        $this->assertFalse($result['valid']);
        $this->assertContains('Betrag muss größer als 0 sein (:32A:/:32B:)', $result['errors']);
        $this->assertContains('Ordering Customer (:50a:) muss Name oder Konto enthalten', $result['errors']);
        $this->assertContains('Beneficiary (:59a:) muss Name oder Konto enthalten', $result['errors']);
    }

    /**
     * Test: MT101 Validierung - gültig
     */
    public function testValidateMt101Valid(): void {
        $mt101 = $this->createMt101SingleCurrency();

        $result = Mt10xConverter::validateMt101($mt101);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['transaction_errors']);
    }

    /**
     * Test: MT101 Zusammenfassung
     */
    public function testSummarizeMt101(): void {
        $mt101 = $this->createMt101WithTransactions();

        $summary = Mt10xConverter::summarizeMt101($mt101);

        $this->assertEquals('MT101-TEST-001', $summary['reference']);
        $this->assertEquals('2025-02-01', $summary['execution_date']);
        $this->assertEquals('Max Mustermann', $summary['ordering_customer']);
        $this->assertEquals(3, $summary['transaction_count']);

        // Summen nach Währung
        $this->assertArrayHasKey('EUR', $summary['totals_by_currency']);
        $this->assertArrayHasKey('USD', $summary['totals_by_currency']);
        $this->assertEquals(750.00, $summary['totals_by_currency']['EUR']); // 500 + 250
        $this->assertEquals(100.00, $summary['totals_by_currency']['USD']);

        // Charges Summary
        $this->assertArrayHasKey('SHA', $summary['charges_summary']);
        $this->assertEquals(2, $summary['charges_summary']['SHA']);
    }

    /**
     * Test: MT103 Zusammenfassung
     */
    public function testSummarizeMt103(): void {
        $mt103 = $this->createValidMt103();

        $summary = Mt10xConverter::summarizeMt103($mt103);

        $this->assertEquals('MT103-SINGLE-001', $summary['reference']);
        $this->assertEquals(1500.00, $summary['amount']);
        $this->assertEquals('EUR', $summary['currency']);
        $this->assertEquals('Firma ABC GmbH', $summary['ordering_customer']);
        $this->assertEquals('Lieferant XYZ', $summary['beneficiary']);
        $this->assertEquals('CRED', $summary['bank_operation']);
    }

    /**
     * Test: Roundtrip MT101 → MT103[] → MT101
     */
    public function testMt101ToMt103ToMt101Roundtrip(): void {
        $original = $this->createMt101SingleCurrency();

        // MT101 → MT103[]
        $mt103Array = Mt10xConverter::mt101ToMt103Array($original);

        // MT103[] → MT101
        $roundtrip = Mt10xConverter::mt103ArrayToMt101(
            $mt103Array,
            'ROUNDTRIP-001',
            $original->getRequestedExecutionDate()
        );

        // Transaktionsanzahl muss identisch sein
        $this->assertCount(
            $original->countTransactions(),
            $roundtrip->getTransactions()
        );

        // Summen müssen identisch sein
        $originalTotals = Mt10xConverter::calculateMt101Totals($original);
        $roundtripTotals = Mt10xConverter::calculateMt101Totals($roundtrip);

        $this->assertEquals($originalTotals['total'], $roundtripTotals['total']);
        $this->assertEquals($originalTotals['currency'], $roundtripTotals['currency']);
    }

    // --- Helper Methods ---

    private function createMt101WithTransactions(): Mt101Document {
        $orderingCustomer = new Party(
            name: 'Max Mustermann',
            account: 'DE89370400440532013000',
            bic: 'COBADEFFXXX'
        );

        $transactions = [
            new Mt101Transaction(
                transactionReference: 'TXN-001',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-02-01'),
                    CurrencyCode::Euro,
                    500.00
                ),
                beneficiary: new Party(name: 'Empfänger 1', account: 'DE12345678901234567890'),
                chargesCode: ChargesCode::SHA
            ),
            new Mt101Transaction(
                transactionReference: 'TXN-002',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-02-01'),
                    CurrencyCode::Euro,
                    250.00
                ),
                beneficiary: new Party(name: 'Empfänger 2', account: 'DE98765432109876543210'),
                chargesCode: ChargesCode::SHA
            ),
            new Mt101Transaction(
                transactionReference: 'TXN-003',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-02-01'),
                    CurrencyCode::USDollar,
                    100.00
                ),
                beneficiary: new Party(name: 'US Empfänger', account: 'US12345678901234567890'),
                chargesCode: ChargesCode::OUR
            ),
        ];

        return new Mt101Document(
            sendersReference: 'MT101-TEST-001',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-02-01'),
            transactions: $transactions
        );
    }

    private function createMt101SingleCurrency(): Mt101Document {
        $orderingCustomer = new Party(
            name: 'Firma GmbH',
            account: 'DE89370400440532013000'
        );

        $transactions = [
            new Mt101Transaction(
                transactionReference: 'TXN-001',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-03-01'),
                    CurrencyCode::Euro,
                    500.00
                ),
                beneficiary: new Party(name: 'Lieferant A', account: 'DE11111111111111111111'),
                chargesCode: ChargesCode::SHA
            ),
            new Mt101Transaction(
                transactionReference: 'TXN-002',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-03-01'),
                    CurrencyCode::Euro,
                    250.00
                ),
                beneficiary: new Party(name: 'Lieferant B', account: 'DE22222222222222222222'),
                chargesCode: ChargesCode::SHA
            ),
        ];

        return new Mt101Document(
            sendersReference: 'MT101-SINGLE-001',
            orderingCustomer: $orderingCustomer,
            requestedExecutionDate: new DateTimeImmutable('2025-03-01'),
            transactions: $transactions
        );
    }

    /**
     * @return Mt103Document[]
     */
    private function createMt103Documents(): array {
        $orderingCustomer = new Party(
            name: 'Auftraggeber GmbH',
            account: 'DE89370400440532013000'
        );

        return [
            new Mt103Document(
                sendersReference: 'MT103-001',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-03-15'),
                    CurrencyCode::Euro,
                    1000.00
                ),
                orderingCustomer: $orderingCustomer,
                beneficiary: new Party(name: 'Empfänger A', account: 'DE11111111111111111111'),
                chargesCode: ChargesCode::SHA
            ),
            new Mt103Document(
                sendersReference: 'MT103-002',
                transferDetails: new TransferDetails(
                    new DateTimeImmutable('2025-03-15'),
                    CurrencyCode::Euro,
                    750.00
                ),
                orderingCustomer: $orderingCustomer,
                beneficiary: new Party(name: 'Empfänger B', account: 'DE22222222222222222222'),
                chargesCode: ChargesCode::OUR
            ),
        ];
    }

    private function createValidMt103(): Mt103Document {
        return new Mt103Document(
            sendersReference: 'MT103-SINGLE-001',
            transferDetails: new TransferDetails(
                new DateTimeImmutable('2025-04-01'),
                CurrencyCode::Euro,
                1500.00
            ),
            orderingCustomer: new Party(
                name: 'Firma ABC GmbH',
                account: 'DE89370400440532013000',
                bic: 'COBADEFFXXX'
            ),
            beneficiary: new Party(
                name: 'Lieferant XYZ',
                account: 'DE98765432109876543210',
                bic: 'DEUTDEFFXXX'
            ),
            bankOperationCode: BankOperationCode::CRED,
            chargesCode: ChargesCode::SHA,
            remittanceInfo: 'Rechnung 2025-001'
        );
    }
}
