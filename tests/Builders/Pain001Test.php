<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain001Test.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Tests\Builders;

use CommonToolkit\Builders\Pain001DocumentBuilder;
use CommonToolkit\Converters\Banking\Pain001Generator;
use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PostalAddress;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\CreditTransferTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\PaymentInstruction;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use CommonToolkit\Enums\Common\Banking\PainType;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Parsers\Pain001Parser;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * Tests für pain.001 Document, Builder, Parser und Generator.
 */
class Pain001Test extends TestCase {
    // ===== Entity Tests =====

    public function testPartyIdentificationCreation(): void {
        $address = new PostalAddress(
            streetName: 'Musterstraße',
            buildingNumber: '123',
            postCode: '12345',
            townName: 'Musterstadt',
            country: CountryCode::Germany
        );

        $party = new PartyIdentification(
            name: 'Max Mustermann',
            postalAddress: $address,
            bic: 'DEUTDEFF',
            lei: '5299001234567891234K',
            countryOfResidence: CountryCode::Germany
        );

        $this->assertSame('Max Mustermann', $party->getName());
        $this->assertSame('DEUTDEFF', $party->getBic());
        $this->assertSame('5299001234567891234K', $party->getLei());
        $this->assertSame('Musterstraße', $party->getPostalAddress()->getStreetName());
        $this->assertSame(CountryCode::Germany, $party->getCountryOfResidence());
        $this->assertTrue($party->isValid());
    }

    public function testAccountIdentificationWithIban(): void {
        $account = new AccountIdentification(
            iban: 'DE89370400440532013000',
            currency: CurrencyCode::Euro
        );

        $this->assertSame('DE89370400440532013000', $account->getIban());
        $this->assertNull($account->getOther());
        $this->assertSame(CurrencyCode::Euro, $account->getCurrency());
    }

    public function testPaymentIdentificationWithUetr(): void {
        $paymentId = PaymentIdentification::create('Invoice-12345');

        $this->assertSame('Invoice-12345', $paymentId->getEndToEndId());
        $this->assertNull($paymentId->getUetr());

        $paymentIdWithUetr = PaymentIdentification::withUetr('Invoice-67890');
        $this->assertSame('Invoice-67890', $paymentIdWithUetr->getEndToEndId());
        $this->assertNotNull($paymentIdWithUetr->getUetr());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $paymentIdWithUetr->getUetr()
        );
    }

    public function testCreditTransferTransactionCreation(): void {
        $paymentId = PaymentIdentification::create('TXN-001');
        $creditor = new PartyIdentification(name: 'Empfänger GmbH');
        $creditorAccount = new AccountIdentification(iban: 'DE02120300000000202051');
        $remittanceInfo = RemittanceInformation::fromText('Rechnung 2025-001');

        $transaction = new CreditTransferTransaction(
            paymentId: $paymentId,
            amount: 1234.56,
            currency: CurrencyCode::Euro,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            remittanceInformation: $remittanceInfo
        );

        $this->assertSame('TXN-001', $transaction->getPaymentId()->getEndToEndId());
        $this->assertSame(1234.56, $transaction->getAmount());
        $this->assertSame(CurrencyCode::Euro, $transaction->getCurrency());
        $this->assertSame('Empfänger GmbH', $transaction->getCreditor()->getName());
        $this->assertSame('Rechnung 2025-001', $transaction->getRemittanceInformation()->getUnstructuredString());
    }

    public function testPaymentInstructionWithSepaHelper(): void {
        $debtor = new PartyIdentification(name: 'Max Mustermann');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');

        $transaction = CreditTransferTransaction::sepa(
            endToEndId: 'E2E-001',
            amount: 500.00,
            creditorName: 'Empfänger GmbH',
            creditorIban: 'DE02120300000000202051',
            creditorBic: 'DEUTDEFF',
            remittanceInfo: 'Miete Januar 2025'
        );

        $instruction = PaymentInstruction::sepaFromEntities(
            paymentInstructionId: 'PMTINF-001',
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            debtorAgent: $debtorAgent,
            transactions: [$transaction]
        );

        $this->assertSame('PMTINF-001', $instruction->getPaymentInstructionId());
        $this->assertSame(PaymentMethod::TRANSFER, $instruction->getPaymentMethod());
        $this->assertSame(ChargesCode::SLEV, $instruction->getChargeBearer());
        $this->assertCount(1, $instruction->getTransactions());
        $this->assertSame(500.0, $instruction->calculateControlSum());
    }

    public function testGroupHeaderCreation(): void {
        $initiatingParty = new PartyIdentification(name: 'Auftraggeber AG');

        $groupHeader = GroupHeader::create(
            messageId: 'MSG-2025-001',
            initiatingParty: $initiatingParty,
            numberOfTransactions: 5,
            controlSum: 12345.67
        );

        $this->assertSame('MSG-2025-001', $groupHeader->getMessageId());
        $this->assertSame(5, $groupHeader->getNumberOfTransactions());
        $this->assertSame(12345.67, $groupHeader->getControlSum());
        $this->assertSame('Auftraggeber AG', $groupHeader->getInitiatingParty()->getName());
    }

    public function testDocumentCreation(): void {
        $initiatingParty = new PartyIdentification(name: 'Test GmbH');
        $debtor = new PartyIdentification(name: 'Test GmbH');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');

        $txn1 = CreditTransferTransaction::sepa('E2E-001', 100.00, 'Empfänger 1', 'DE02120300000000202051', 'DEUTDEFF', 'Ref 1');
        $txn2 = CreditTransferTransaction::sepa('E2E-002', 200.00, 'Empfänger 2', 'DE02120300000000202052', 'DEUTDEFF', 'Ref 2');

        $instruction = new PaymentInstruction(
            paymentInstructionId: 'PMTINF-001',
            paymentMethod: PaymentMethod::TRANSFER,
            requestedExecutionDate: new DateTimeImmutable('2025-01-15'),
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            transactions: [$txn1, $txn2],
            debtorAgent: $debtorAgent
        );

        $document = Document::create('MSG-001', $initiatingParty, [$instruction]);

        $this->assertSame(PainType::PAIN_001, $document->getType());
        $this->assertSame('MSG-001', $document->getGroupHeader()->getMessageId());
        $this->assertSame(2, $document->countTransactions());
        $this->assertSame(300.0, $document->calculateControlSum());
        $this->assertCount(1, $document->getPaymentInstructions());
    }

    // ===== Builder Tests =====

    public function testPain001DocumentBuilderSimple(): void {
        $builder = new Pain001DocumentBuilder();

        $initiatingParty = new PartyIdentification(name: 'Builder Test GmbH');
        $debtor = new PartyIdentification(name: 'Builder Test GmbH');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');
        $transaction = CreditTransferTransaction::sepa('E2E-BUILDER', 750.00, 'Empfänger', 'DE02120300000000202051', 'DEUTDEFF', 'Test');

        $instruction = PaymentInstruction::sepaFromEntities('PMTINF-BUILDER', $debtor, $debtorAccount, $debtorAgent, [$transaction]);

        $document = $builder
            ->setMessageId('MSG-BUILDER-001')
            ->setInitiatingParty($initiatingParty)
            ->addPaymentInstruction($instruction)
            ->build();

        $this->assertSame('MSG-BUILDER-001', $document->getGroupHeader()->getMessageId());
        $this->assertSame(1, $document->countTransactions());
        $this->assertSame(750.0, $document->calculateControlSum());
    }

    public function testPain001DocumentBuilderFluent(): void {
        $builder = new Pain001DocumentBuilder();

        $initiatingParty = new PartyIdentification(name: 'Fluent Test GmbH');
        $debtor = new PartyIdentification(name: 'Fluent Test GmbH');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');

        $transaction1 = CreditTransferTransaction::sepa('E2E-001', 100.00, 'Empfänger 1', 'DE02120300000000202051', 'DEUTDEFF', 'Ref 1');
        $transaction2 = CreditTransferTransaction::sepa('E2E-002', 250.00, 'Empfänger 2', 'DE02120300000000202052', 'DEUTDEFF', 'Ref 2');

        $document = $builder
            ->setMessageId('MSG-FLUENT-001')
            ->setInitiatingParty($initiatingParty)
            ->beginPaymentInstruction('PMTINF-001', $debtor, $debtorAccount, $debtorAgent)
            ->addTransaction($transaction1)
            ->addTransaction($transaction2)
            ->setChargesCode(ChargesCode::SHA)
            ->endPaymentInstruction()
            ->build();

        $this->assertSame(2, $document->countTransactions());
        $this->assertSame(350.0, $document->calculateControlSum());
        $this->assertSame(ChargesCode::SHA, $document->getPaymentInstructions()[0]->getChargeBearer());
    }

    public function testCreateSepaTransferStatic(): void {
        $document = Pain001DocumentBuilder::createSepaTransfer(
            messageId: 'SEPA-SIMPLE-001',
            initiatorName: 'Max Mustermann',
            debtorIban: 'DE89370400440532013000',
            debtorBic: 'COBADEFFXXX',
            creditorName: 'Empfänger GmbH',
            creditorIban: 'DE02120300000000202051',
            amount: 99.99,
            reference: 'Rechnung 2025-001'
        );

        $this->assertSame('SEPA-SIMPLE-001', $document->getGroupHeader()->getMessageId());
        $this->assertSame(1, $document->countTransactions());
        $this->assertSame(99.99, $document->calculateControlSum());

        $transaction = $document->getAllTransactions()[0];
        $this->assertSame('Empfänger GmbH', $transaction->getCreditor()->getName());
        $this->assertSame('Rechnung 2025-001', $transaction->getRemittanceInformation()->getUnstructuredString());
    }

    // ===== Generator und Parser Roundtrip Tests =====

    public function testGeneratorOutputValidXml(): void {
        $document = Pain001DocumentBuilder::createSepaTransfer(
            messageId: 'XML-GEN-001',
            initiatorName: 'Generator Test',
            debtorIban: 'DE89370400440532013000',
            debtorBic: 'COBADEFFXXX',
            creditorName: 'Empfänger',
            creditorIban: 'DE02120300000000202051',
            amount: 500.00,
            reference: 'Test Verwendungszweck'
        );

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<Document', $xml);
        $this->assertStringContainsString('<CstmrCdtTrfInitn>', $xml);
        $this->assertStringContainsString('<GrpHdr>', $xml);
        $this->assertStringContainsString('<MsgId>XML-GEN-001</MsgId>', $xml);
        $this->assertStringContainsString('<PmtInf>', $xml);
        $this->assertStringContainsString('<CdtTrfTxInf>', $xml);
        $this->assertStringContainsString('<Ustrd>Test Verwendungszweck</Ustrd>', $xml);
    }

    public function testParserRoundtrip(): void {
        // Dokument erstellen
        $original = Pain001DocumentBuilder::createSepaTransfer(
            messageId: 'ROUNDTRIP-001',
            initiatorName: 'Roundtrip Test GmbH',
            debtorIban: 'DE89370400440532013000',
            debtorBic: 'COBADEFFXXX',
            creditorName: 'Empfänger AG',
            creditorIban: 'DE02120300000000202051',
            amount: 1234.56,
            reference: 'Roundtrip Test Referenz'
        );

        // In XML konvertieren
        $generator = new Pain001Generator();
        $xml = $generator->generate($original);

        // Zurück parsen
        $parsed = Pain001Parser::fromXml($xml);

        // Vergleichen
        $this->assertSame(
            $original->getGroupHeader()->getMessageId(),
            $parsed->getGroupHeader()->getMessageId()
        );
        $this->assertSame(
            $original->countTransactions(),
            $parsed->countTransactions()
        );
        $this->assertSame(
            $original->calculateControlSum(),
            $parsed->calculateControlSum()
        );

        $originalTxn = $original->getAllTransactions()[0];
        $parsedTxn = $parsed->getAllTransactions()[0];

        $this->assertSame($originalTxn->getAmount(), $parsedTxn->getAmount());
        $this->assertSame(
            $originalTxn->getCreditor()->getName(),
            $parsedTxn->getCreditor()->getName()
        );
        $this->assertSame(
            $originalTxn->getRemittanceInformation()->getUnstructured(),
            $parsedTxn->getRemittanceInformation()->getUnstructured()
        );
    }

    public function testMultiplePaymentInstructionsRoundtrip(): void {
        $initiatingParty = new PartyIdentification(name: 'Multi-Test GmbH');
        $debtor = new PartyIdentification(name: 'Multi-Test GmbH');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');

        // Erste PaymentInstruction mit 2 Transaktionen
        $txn1 = CreditTransferTransaction::sepa('E2E-001', 100.00, 'Empfänger 1', 'DE02120300000000202051', 'DEUTDEFF', 'Ref 1');
        $txn2 = CreditTransferTransaction::sepa('E2E-002', 200.00, 'Empfänger 2', 'DE02120300000000202052', 'DEUTDEFF', 'Ref 2');
        $instruction1 = PaymentInstruction::sepaFromEntities('PMTINF-001', $debtor, $debtorAccount, $debtorAgent, [$txn1, $txn2]);

        // Zweite PaymentInstruction mit 1 Transaktion
        $txn3 = CreditTransferTransaction::sepa('E2E-003', 300.00, 'Empfänger 3', 'DE02120300000000202053', 'DEUTDEFF', 'Ref 3');
        $instruction2 = PaymentInstruction::sepaFromEntities('PMTINF-002', $debtor, $debtorAccount, $debtorAgent, [$txn3]);

        $document = Document::create('MULTI-MSG-001', $initiatingParty, [$instruction1, $instruction2]);

        // Roundtrip
        $generator = new Pain001Generator();
        $xml = $generator->generate($document);
        $parsed = Pain001Parser::fromXml($xml);

        $this->assertSame(2, count($parsed->getPaymentInstructions()));
        $this->assertSame(3, $parsed->countTransactions());
        $this->assertSame(600.0, $parsed->calculateControlSum());

        // Einzelne PaymentInstructions prüfen
        $this->assertSame('PMTINF-001', $parsed->getPaymentInstructions()[0]->getPaymentInstructionId());
        $this->assertSame('PMTINF-002', $parsed->getPaymentInstructions()[1]->getPaymentInstructionId());
        $this->assertCount(2, $parsed->getPaymentInstructions()[0]->getTransactions());
        $this->assertCount(1, $parsed->getPaymentInstructions()[1]->getTransactions());
    }

    // ===== Validation Tests =====

    public function testDocumentValidation(): void {
        $document = Pain001DocumentBuilder::createSepaTransfer(
            messageId: 'VALID-001',
            initiatorName: 'Valid Test',
            debtorIban: 'DE89370400440532013000',
            debtorBic: 'COBADEFFXXX',
            creditorName: 'Empfänger',
            creditorIban: 'DE02120300000000202051',
            amount: 100.00,
            reference: 'Test'
        );

        $result = $document->validate();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testParserIsValid(): void {
        $document = Pain001DocumentBuilder::createSepaTransfer(
            messageId: 'ISVALID-001',
            initiatorName: 'Is Valid Test',
            debtorIban: 'DE89370400440532013000',
            debtorBic: 'COBADEFFXXX',
            creditorName: 'Empfänger',
            creditorIban: 'DE02120300000000202051',
            amount: 50.00,
            reference: 'Test'
        );

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);

        $this->assertTrue(Pain001Parser::isValid($xml));
        $this->assertFalse(Pain001Parser::isValid('<invalid>xml</invalid>'));
    }

    // ===== PainType Enum Tests =====

    public function testPainTypeEnum(): void {
        $pain001 = PainType::PAIN_001;

        $this->assertSame('pain.001', $pain001->value);
        $this->assertSame('CstmrCdtTrfInitn', $pain001->rootElement());
        $this->assertSame('Überweisungsauftrag', $pain001->description());
        $this->assertTrue($pain001->isCreditTransfer());
        $this->assertFalse($pain001->isDirectDebit());
        $this->assertFalse($pain001->isMandate());

        $this->assertSame(PainType::PAIN_001, PainType::fromRootElement('CstmrCdtTrfInitn'));
        $this->assertSame(PainType::PAIN_008, PainType::fromRootElement('CstmrDrctDbtInitn'));
    }

    // ===== ChargesCode and PaymentMethod Tests =====

    public function testChargesCodeSepaCompliance(): void {
        $this->assertTrue(ChargesCode::SHA->isSepaCompliant());
        $this->assertTrue(ChargesCode::SLEV->isSepaCompliant());
        $this->assertFalse(ChargesCode::OUR->isSepaCompliant());
        $this->assertFalse(ChargesCode::BEN->isSepaCompliant());

        $this->assertSame(ChargesCode::SLEV, ChargesCode::defaultSepa());
    }

    public function testPaymentMethodForPainTypes(): void {
        $this->assertTrue(PaymentMethod::TRANSFER->isPain001());
        $this->assertTrue(PaymentMethod::CHEQUE->isPain001());
        $this->assertFalse(PaymentMethod::DIRECT_DEBIT->isPain001());

        $this->assertFalse(PaymentMethod::TRANSFER->isPain008());
        $this->assertTrue(PaymentMethod::DIRECT_DEBIT->isPain008());

        $this->assertSame(PaymentMethod::TRANSFER, PaymentMethod::defaultSepa());
    }

    // ===== Additional Edge Case Tests =====

    public function testEmptyPaymentInstructionValidation(): void {
        $initiatingParty = new PartyIdentification(name: 'Test GmbH');

        // Dokument ohne PaymentInstructions
        $document = Document::create('MSG-EMPTY', $initiatingParty, []);

        $result = $document->validate();
        $this->assertFalse($result['valid']);
        $this->assertContains('Mindestens eine Payment Instruction erforderlich', $result['errors']);
    }

    public function testPostalAddressRoundtrip(): void {
        $address = new PostalAddress(
            streetName: 'Hauptstraße',
            buildingNumber: '42a',
            postCode: '10115',
            townName: 'Berlin',
            country: CountryCode::Germany
        );

        $creditor = new PartyIdentification(
            name: 'Adress-Test GmbH',
            postalAddress: $address
        );
        $creditorAccount = new AccountIdentification(iban: 'DE02120300000000202051');

        $paymentId = PaymentIdentification::create('ADDR-E2E-001');
        $remittance = RemittanceInformation::fromText('Adresstest');

        $transaction = new CreditTransferTransaction(
            paymentId: $paymentId,
            amount: 100.00,
            currency: CurrencyCode::Euro,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            remittanceInformation: $remittance
        );

        $debtor = new PartyIdentification(name: 'Auftraggeber');
        $debtorAccount = new AccountIdentification(iban: 'DE89370400440532013000');
        $debtorAgent = new FinancialInstitution(bic: 'COBADEFFXXX');

        $instruction = PaymentInstruction::sepaFromEntities(
            'PMTINF-ADDR',
            $debtor,
            $debtorAccount,
            $debtorAgent,
            [$transaction]
        );

        $initiatingParty = new PartyIdentification(name: 'Auftraggeber');
        $document = Document::create('MSG-ADDR', $initiatingParty, [$instruction]);

        // Roundtrip
        $generator = new Pain001Generator();
        $xml = $generator->generate($document);
        $parsed = Pain001Parser::fromXml($xml);

        $parsedAddress = $parsed->getAllTransactions()[0]->getCreditor()->getPostalAddress();
        $this->assertNotNull($parsedAddress);
        $this->assertSame('Hauptstraße', $parsedAddress->getStreetName());
        $this->assertSame('42a', $parsedAddress->getBuildingNumber());
        $this->assertSame('10115', $parsedAddress->getPostCode());
        $this->assertSame('Berlin', $parsedAddress->getTownName());
        $this->assertSame(CountryCode::Germany, $parsedAddress->getCountry());
    }

    public function testCreditorAgentRoundtrip(): void {
        $creditorAgent = new FinancialInstitution(bic: 'DEUTDEFF');

        $transaction = new CreditTransferTransaction(
            paymentId: PaymentIdentification::create('AGENT-E2E'),
            amount: 250.00,
            currency: CurrencyCode::Euro,
            creditor: new PartyIdentification(name: 'Empfänger'),
            creditorAccount: new AccountIdentification(iban: 'DE02120300000000202051'),
            creditorAgent: $creditorAgent
        );

        $instruction = PaymentInstruction::sepaFromEntities(
            'PMTINF-AGENT',
            new PartyIdentification(name: 'Auftraggeber'),
            new AccountIdentification(iban: 'DE89370400440532013000'),
            new FinancialInstitution(bic: 'COBADEFFXXX'),
            [$transaction]
        );

        $document = Document::create('MSG-AGENT', new PartyIdentification(name: 'Auftraggeber'), [$instruction]);

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);
        $parsed = Pain001Parser::fromXml($xml);

        $parsedAgent = $parsed->getAllTransactions()[0]->getCreditorAgent();
        $this->assertNotNull($parsedAgent);
        $this->assertSame('DEUTDEFF', $parsedAgent->getBic());
    }

    public function testRemittanceInformationMultipleLines(): void {
        $remittance = new RemittanceInformation(['Zeile 1', 'Zeile 2', 'Zeile 3']);

        $transaction = new CreditTransferTransaction(
            paymentId: PaymentIdentification::create('MULTI-LINE'),
            amount: 50.00,
            currency: CurrencyCode::Euro,
            creditor: new PartyIdentification(name: 'Empfänger'),
            creditorAccount: new AccountIdentification(iban: 'DE02120300000000202051'),
            remittanceInformation: $remittance
        );

        $instruction = PaymentInstruction::sepaFromEntities(
            'PMTINF-MULTI',
            new PartyIdentification(name: 'Auftraggeber'),
            new AccountIdentification(iban: 'DE89370400440532013000'),
            new FinancialInstitution(bic: 'COBADEFFXXX'),
            [$transaction]
        );

        $document = Document::create('MSG-MULTI', new PartyIdentification(name: 'Auftraggeber'), [$instruction]);

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);

        // Überprüfen, dass alle Zeilen im XML vorhanden sind
        $this->assertStringContainsString('Zeile 1', $xml);
        $this->assertStringContainsString('Zeile 2', $xml);
        $this->assertStringContainsString('Zeile 3', $xml);

        // Roundtrip
        $parsed = Pain001Parser::fromXml($xml);
        $parsedRemittance = $parsed->getAllTransactions()[0]->getRemittanceInformation();

        $this->assertCount(3, $parsedRemittance->getUnstructured());
        $this->assertSame(['Zeile 1', 'Zeile 2', 'Zeile 3'], $parsedRemittance->getUnstructured());
    }

    public function testAccountWithOtherId(): void {
        $account = new AccountIdentification(
            other: 'PROP123456789'
        );

        $this->assertNull($account->getIban());
        $this->assertSame('PROP123456789', $account->getOther());
    }

    public function testFinancialInstitutionWithClearingCode(): void {
        $institution = new FinancialInstitution(
            bic: 'DEUTDEFF',
            memberId: 'DE12345'
        );

        $this->assertSame('DEUTDEFF', $institution->getBic());
        $this->assertSame('DE12345', $institution->getMemberId());
    }

    public function testPainTypeFromNamespace(): void {
        $namespace001_03 = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
        $namespace001_12 = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.12';
        $namespace008_08 = 'urn:iso:std:iso:20022:tech:xsd:pain.008.008.02';

        $this->assertSame(PainType::PAIN_001, PainType::fromNamespace($namespace001_03));
        $this->assertSame(PainType::PAIN_001, PainType::fromNamespace($namespace001_12));
        $this->assertSame(PainType::PAIN_008, PainType::fromNamespace($namespace008_08));
    }

    public function testLargeBatchTransactions(): void {
        $transactions = [];
        for ($i = 1; $i <= 50; $i++) {
            $transactions[] = CreditTransferTransaction::sepa(
                sprintf('E2E-BATCH-%03d', $i),
                10.00 + ($i * 0.01),
                sprintf('Empfänger %d', $i),
                sprintf('DE%020d', $i),
                'DEUTDEFF',
                sprintf('Referenz %d', $i)
            );
        }

        $instruction = PaymentInstruction::sepaFromEntities(
            'PMTINF-BATCH',
            new PartyIdentification(name: 'Batch Test'),
            new AccountIdentification(iban: 'DE89370400440532013000'),
            new FinancialInstitution(bic: 'COBADEFFXXX'),
            $transactions
        );

        $document = Document::create('MSG-BATCH', new PartyIdentification(name: 'Batch Test'), [$instruction]);

        $this->assertSame(50, $document->countTransactions());
        $expectedSum = array_sum(array_map(fn($t) => $t->getAmount(), $transactions));
        $this->assertEqualsWithDelta($expectedSum, $document->calculateControlSum(), 0.01);

        // Generator und Parser sollten auch große Batches handhaben
        $generator = new Pain001Generator();
        $xml = $generator->generate($document);
        $parsed = Pain001Parser::fromXml($xml);

        $this->assertSame(50, $parsed->countTransactions());
    }

    public function testDifferentCurrencies(): void {
        $transactionEur = new CreditTransferTransaction(
            paymentId: PaymentIdentification::create('CURR-EUR'),
            amount: 100.00,
            currency: CurrencyCode::Euro,
            creditor: new PartyIdentification(name: 'Empfänger EUR'),
            creditorAccount: new AccountIdentification(iban: 'DE02120300000000202051')
        );

        $transactionUsd = new CreditTransferTransaction(
            paymentId: PaymentIdentification::create('CURR-USD'),
            amount: 200.00,
            currency: CurrencyCode::USDollar,
            creditor: new PartyIdentification(name: 'Empfänger USD'),
            creditorAccount: new AccountIdentification(iban: 'DE02120300000000202052')
        );

        $transactionChf = new CreditTransferTransaction(
            paymentId: PaymentIdentification::create('CURR-CHF'),
            amount: 300.00,
            currency: CurrencyCode::SwissFranc,
            creditor: new PartyIdentification(name: 'Empfänger CHF'),
            creditorAccount: new AccountIdentification(iban: 'CH9300762011623852957')
        );

        $instruction = PaymentInstruction::sepaFromEntities(
            'PMTINF-MULTI-CURR',
            new PartyIdentification(name: 'Auftraggeber'),
            new AccountIdentification(iban: 'DE89370400440532013000'),
            new FinancialInstitution(bic: 'COBADEFFXXX'),
            [$transactionEur, $transactionUsd, $transactionChf]
        );

        $document = Document::create('MSG-CURR', new PartyIdentification(name: 'Auftraggeber'), [$instruction]);

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('Ccy="EUR"', $xml);
        $this->assertStringContainsString('Ccy="USD"', $xml);
        $this->assertStringContainsString('Ccy="CHF"', $xml);

        $parsed = Pain001Parser::fromXml($xml);
        $transactions = $parsed->getAllTransactions();

        $this->assertSame(CurrencyCode::Euro, $transactions[0]->getCurrency());
        $this->assertSame(CurrencyCode::USDollar, $transactions[1]->getCurrency());
        $this->assertSame(CurrencyCode::SwissFranc, $transactions[2]->getCurrency());
    }

    public function testPaymentInstructionWithFutureExecutionDate(): void {
        $futureDate = new DateTimeImmutable('+7 days');

        $instruction = new PaymentInstruction(
            paymentInstructionId: 'PMTINF-FUTURE',
            paymentMethod: PaymentMethod::TRANSFER,
            requestedExecutionDate: $futureDate,
            debtor: new PartyIdentification(name: 'Auftraggeber'),
            debtorAccount: new AccountIdentification(iban: 'DE89370400440532013000'),
            debtorAgent: new FinancialInstitution(bic: 'COBADEFFXXX'),
            transactions: [CreditTransferTransaction::sepa('E2E-FUTURE', 100.00, 'Empfänger', 'DE02120300000000202051', 'DEUTDEFF', 'Ref')]
        );

        $document = Document::create('MSG-FUTURE', new PartyIdentification(name: 'Auftraggeber'), [$instruction]);

        $generator = new Pain001Generator();
        $xml = $generator->generate($document);
        $parsed = Pain001Parser::fromXml($xml);

        $parsedDate = $parsed->getPaymentInstructions()[0]->getRequestedExecutionDate();
        $this->assertSame($futureDate->format('Y-m-d'), $parsedDate->format('Y-m-d'));
    }
}
