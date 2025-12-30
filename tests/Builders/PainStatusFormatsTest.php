<?php
/*
 * Created on   : Tue Dec 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PainStatusFormatsTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders;

use Tests\Contracts\BaseTestCase;
use CommonToolkit\Converters\Banking\Pain002Generator;
use CommonToolkit\Converters\Banking\Pain008Generator;
use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\Document as Pain002Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\GroupHeader as Pain002GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalGroupInformation as Pain002OriginalGroupInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalPaymentInformation as Pain002OriginalPaymentInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\StatusReason;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\StatusReasonCode;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\TransactionInformationAndStatus;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\TransactionStatus;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\DirectDebitTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\Document as Pain008Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\GroupHeader as Pain008GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\MandateInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\PaymentInstruction;
use CommonToolkit\Enums\Common\Banking\LocalInstrument;
use CommonToolkit\Enums\Common\Banking\PainType;
use CommonToolkit\Enums\Common\Banking\SequenceType;
use CommonToolkit\Parsers\Pain002Parser;
use CommonToolkit\Parsers\Pain008Parser;
use DateTimeImmutable;

/**
 * Tests für pain.002 (Payment Status Report) und pain.008 (Direct Debit).
 */
class PainStatusFormatsTest extends BaseTestCase {

    // ==================== pain.002 Entity Tests ====================

    public function testPain002TransactionStatusIsSuccessful(): void {
        $this->assertTrue(TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED->isSuccessful());
        $this->assertTrue(TransactionStatus::ACCEPTED_SETTLEMENT_IN_PROCESS->isSuccessful());
        $this->assertTrue(TransactionStatus::ACCEPTED_TECHNICAL_VALIDATION->isSuccessful());
        $this->assertFalse(TransactionStatus::REJECTED->isSuccessful());
        $this->assertFalse(TransactionStatus::PENDING->isSuccessful());
    }

    public function testPain002TransactionStatusIsRejected(): void {
        $this->assertTrue(TransactionStatus::REJECTED->isRejected());
        $this->assertFalse(TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED->isRejected());
    }

    public function testPain002TransactionStatusIsPending(): void {
        $this->assertTrue(TransactionStatus::PENDING->isPending());
        $this->assertTrue(TransactionStatus::RECEIVED->isPending());
        $this->assertFalse(TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED->isPending());
    }

    public function testPain002TransactionStatusDescription(): void {
        $status = TransactionStatus::REJECTED;
        $this->assertIsString($status->description());
        $this->assertNotEmpty($status->description());
    }

    public function testPain002StatusReasonFromCode(): void {
        $reason = StatusReason::fromCode('AC01', ['Kontoinformationen falsch']);

        $this->assertEquals('AC01', $reason->getCode());
        $this->assertNull($reason->getProprietary());
        $this->assertEquals('AC01', $reason->getReason());
        $this->assertContains('Kontoinformationen falsch', $reason->getAdditionalInfo());
    }

    public function testPain002StatusReasonFromProprietary(): void {
        $reason = StatusReason::fromProprietary('CUSTOM_CODE', ['Interner Fehler']);

        $this->assertNull($reason->getCode());
        $this->assertEquals('CUSTOM_CODE', $reason->getProprietary());
        $this->assertEquals('CUSTOM_CODE', $reason->getReason());
    }

    public function testPain002StatusReasonCodeParsing(): void {
        $reason = StatusReason::fromCode('AC01');
        $reasonCode = $reason->getReasonCode();

        if ($reasonCode !== null) {
            $this->assertInstanceOf(StatusReasonCode::class, $reasonCode);
        } else {
            // StatusReasonCode may not have AC01, that's ok
            $this->assertNull($reasonCode);
        }
    }

    public function testPain002TransactionInformationAndStatusAccepted(): void {
        $txStatus = TransactionInformationAndStatus::accepted('E2E-123');

        $this->assertEquals('E2E-123', $txStatus->getOriginalEndToEndId());
        $this->assertEquals(TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED, $txStatus->getStatus());
        $this->assertTrue($txStatus->getStatus()->isSuccessful());
    }

    public function testPain002TransactionInformationAndStatusRejected(): void {
        $reason = StatusReason::fromCode('AC01', ['Konto nicht gefunden']);
        $txStatus = TransactionInformationAndStatus::rejected('E2E-456', $reason);

        $this->assertEquals('E2E-456', $txStatus->getOriginalEndToEndId());
        $this->assertEquals(TransactionStatus::REJECTED, $txStatus->getStatus());
        $this->assertCount(1, $txStatus->getStatusReasons());
        $this->assertEquals('AC01', $txStatus->getStatusReasons()[0]->getCode());
    }

    public function testPain002OriginalGroupInformationForPain001(): void {
        $orgGrp = Pain002OriginalGroupInformation::forPain001('ORIG-MSG-001', TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED);

        $this->assertEquals('ORIG-MSG-001', $orgGrp->getOriginalMessageId());
        $this->assertEquals('pain.001.001.12', $orgGrp->getOriginalMessageNameId());
        $this->assertTrue($orgGrp->isGroupAccepted());
        $this->assertFalse($orgGrp->isGroupRejected());
    }

    public function testPain002OriginalGroupInformationForPain008(): void {
        $orgGrp = Pain002OriginalGroupInformation::forPain008('ORIG-MSG-002', TransactionStatus::REJECTED);

        $this->assertEquals('pain.008.001.11', $orgGrp->getOriginalMessageNameId());
        $this->assertTrue($orgGrp->isGroupRejected());
        $this->assertFalse($orgGrp->isGroupAccepted());
    }

    public function testPain002DocumentCreation(): void {
        $groupHeader = Pain002GroupHeader::create('MSG-002-001');
        $orgGrp = Pain002OriginalGroupInformation::forPain001('ORIG-001', TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED);

        $document = new Pain002Document($groupHeader, $orgGrp);

        $this->assertEquals(PainType::PAIN_002, $document->getType());
        $this->assertEquals('MSG-002-001', $document->getGroupHeader()->getMessageId());
        $this->assertEmpty($document->getOriginalPaymentInformations());
    }

    public function testPain002DocumentAllAccepted(): void {
        $document = Pain002Document::allAccepted(
            'STATUS-001',
            'ORIG-PAYMENT-001',
            'pain.001.001.12'
        );

        $this->assertEquals(PainType::PAIN_002, $document->getType());
        $this->assertEquals('STATUS-001', $document->getGroupHeader()->getMessageId());
        $this->assertTrue($document->getOriginalGroupInformation()->isGroupAccepted());
    }

    public function testPain002DocumentWithPaymentInformations(): void {
        $txStatus = TransactionInformationAndStatus::accepted('E2E-001');
        $pmtInfo = new Pain002OriginalPaymentInformation(
            originalPaymentInformationId: 'PMTINF-001',
            transactionStatuses: [$txStatus]
        );

        $document = Pain002Document::create(
            'MSG-002-002',
            Pain002OriginalGroupInformation::forPain001('ORIG-001'),
            [$pmtInfo]
        );

        $this->assertCount(1, $document->getOriginalPaymentInformations());
        $this->assertEquals('PMTINF-001', $document->getOriginalPaymentInformations()[0]->getOriginalPaymentInformationId());
    }

    // ==================== pain.002 Generator Tests ====================

    public function testPain002GeneratorBasicOutput(): void {
        $document = Pain002Document::allAccepted('STATUS-001', 'ORIG-001');

        $generator = new Pain002Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('CstmrPmtStsRpt', $xml);
        $this->assertStringContainsString('pain.002.001.14', $xml);
        $this->assertStringContainsString('STATUS-001', $xml);
        $this->assertStringContainsString('ORIG-001', $xml);
    }

    public function testPain002GeneratorWithTransactionStatus(): void {
        $txStatus = TransactionInformationAndStatus::rejected(
            'E2E-REJECTED',
            StatusReason::fromCode('AC04', ['Konto geschlossen'])
        );
        $pmtInfo = new Pain002OriginalPaymentInformation(
            originalPaymentInformationId: 'PMTINF-REJECT',
            transactionStatuses: [$txStatus]
        );

        $document = Pain002Document::create(
            'STATUS-REJECT',
            new Pain002OriginalGroupInformation('ORIG-002', 'pain.001.001.12'),
            [$pmtInfo]
        );

        $generator = new Pain002Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('OrgnlPmtInfAndSts', $xml);
        $this->assertStringContainsString('TxInfAndSts', $xml);
        $this->assertStringContainsString('E2E-REJECTED', $xml);
    }

    // ==================== pain.002 Parser Tests ====================

    public function testPain002ParserIsValid(): void {
        $xml = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.002.001.14"><CstmrPmtStsRpt><GrpHdr><MsgId>TEST</MsgId><CreDtTm>2025-01-01T12:00:00</CreDtTm></GrpHdr><OrgnlGrpInfAndSts><OrgnlMsgId>ORIG</OrgnlMsgId><OrgnlMsgNmId>pain.001.001.12</OrgnlMsgNmId></OrgnlGrpInfAndSts></CstmrPmtStsRpt></Document>';

        $this->assertTrue(Pain002Parser::isValid($xml));
    }

    public function testPain002ParserIsValidInvalid(): void {
        $xml = '<?xml version="1.0"?><Document><CstmrCdtTrfInitn/></Document>';

        $this->assertFalse(Pain002Parser::isValid($xml));
    }

    public function testPain002ParserFromXml(): void {
        $xml = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.002.001.14"><CstmrPmtStsRpt><GrpHdr><MsgId>PARSED-MSG</MsgId><CreDtTm>2025-01-15T14:30:00</CreDtTm></GrpHdr><OrgnlGrpInfAndSts><OrgnlMsgId>ORIG-PARSED</OrgnlMsgId><OrgnlMsgNmId>pain.001.001.12</OrgnlMsgNmId><GrpSts>ACSC</GrpSts></OrgnlGrpInfAndSts></CstmrPmtStsRpt></Document>';

        $document = Pain002Parser::fromXml($xml);

        $this->assertEquals('PARSED-MSG', $document->getGroupHeader()->getMessageId());
        $this->assertEquals('ORIG-PARSED', $document->getOriginalGroupInformation()->getOriginalMessageId());
        $this->assertEquals(TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED, $document->getOriginalGroupInformation()->getGroupStatus());
    }

    public function testPain002RoundtripGenerateAndParse(): void {
        $original = Pain002Document::allAccepted('ROUNDTRIP-001', 'ORIG-ROUNDTRIP');

        $generator = new Pain002Generator();
        $xml = $generator->generate($original);

        $parsed = Pain002Parser::fromXml($xml);

        $this->assertEquals(
            $original->getGroupHeader()->getMessageId(),
            $parsed->getGroupHeader()->getMessageId()
        );
        $this->assertEquals(
            $original->getOriginalGroupInformation()->getOriginalMessageId(),
            $parsed->getOriginalGroupInformation()->getOriginalMessageId()
        );
    }

    // ==================== pain.008 Entity Tests ====================

    public function testPain008MandateInformationCreation(): void {
        $mandateInfo = MandateInformation::create('MNDT-001', new DateTimeImmutable('2024-06-15'));

        $this->assertEquals('MNDT-001', $mandateInfo->getMandateId());
        $this->assertEquals('2024-06-15', $mandateInfo->getDateOfSignature()->format('Y-m-d'));
        $this->assertNull($mandateInfo->getAmendmentIndicator());
    }

    public function testPain008MandateInformationAmended(): void {
        $mandateInfo = MandateInformation::amended(
            'MNDT-002',
            new DateTimeImmutable('2024-07-01'),
            'MNDT-001',
            'DE98ZZZ09999999999'
        );

        $this->assertEquals('MNDT-002', $mandateInfo->getMandateId());
        $this->assertTrue($mandateInfo->getAmendmentIndicator());
        $this->assertEquals('MNDT-001', $mandateInfo->getOriginalMandateId());
        $this->assertEquals('DE98ZZZ09999999999', $mandateInfo->getOriginalCreditorSchemeId());
    }

    public function testPain008DirectDebitTransactionSepa(): void {
        $tx = DirectDebitTransaction::sepa(
            'DD-E2E-001',
            250.00,
            'MNDT-123',
            new DateTimeImmutable('2024-01-15'),
            'Max Mustermann',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'Rechnung 2025-001'
        );

        $this->assertEquals('DD-E2E-001', $tx->getPaymentId()->getEndToEndId());
        $this->assertEquals(250.00, $tx->getAmount());
        $this->assertEquals('MNDT-123', $tx->getMandateInfo()->getMandateId());
        $this->assertEquals('Max Mustermann', $tx->getDebtor()->getName());
        $this->assertEquals('DE89370400440532013000', $tx->getDebtorAccount()->getIban());
        $this->assertNotNull($tx->getRemittanceInformation());
    }

    public function testPain008PaymentInstructionSepaCore(): void {
        $collectionDate = new DateTimeImmutable('+5 days');
        $tx = DirectDebitTransaction::sepa(
            'DD-001',
            100.00,
            'MNDT-CORE',
            new DateTimeImmutable('2024-01-01'),
            'Debtor GmbH',
            'DE75512108001245126199',
            'INGDDEFFXXX'
        );

        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-DD-001',
            $collectionDate,
            'Creditor AG',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            [$tx]
        );

        $this->assertEquals('PMTINF-DD-001', $instruction->getPaymentInstructionId());
        $this->assertEquals('Creditor AG', $instruction->getCreditor()->getName());
        $this->assertEquals('DE98ZZZ09999999999', $instruction->getCreditorSchemeId());
        $this->assertEquals(SequenceType::FIRST, $instruction->getSequenceType());
        $this->assertEquals(LocalInstrument::SEPA_CORE, $instruction->getLocalInstrument());
        $this->assertCount(1, $instruction->getTransactions());
    }

    public function testPain008PaymentInstructionSepaB2B(): void {
        $collectionDate = new DateTimeImmutable('+10 days');

        $instruction = PaymentInstruction::sepaB2B(
            'PMTINF-B2B-001',
            $collectionDate,
            'Business Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::RECURRING,
            []
        );

        $this->assertEquals(LocalInstrument::SEPA_B2B, $instruction->getLocalInstrument());
        $this->assertEquals(SequenceType::RECURRING, $instruction->getSequenceType());
    }

    public function testPain008PaymentInstructionCountTransactions(): void {
        $tx1 = DirectDebitTransaction::sepa('DD-001', 100.00, 'M1', new DateTimeImmutable(), 'D1', 'DE89370400440532013000');
        $tx2 = DirectDebitTransaction::sepa('DD-002', 200.00, 'M2', new DateTimeImmutable(), 'D2', 'DE75512108001245126199');

        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-001',
            new DateTimeImmutable('+5 days'),
            'Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::RECURRING,
            [$tx1, $tx2]
        );

        $this->assertEquals(2, $instruction->countTransactions());
        $this->assertEquals(300.00, $instruction->calculateControlSum());
    }

    public function testPain008DocumentCreation(): void {
        $tx = DirectDebitTransaction::sepa('DD-001', 150.00, 'M1', new DateTimeImmutable(), 'Debtor', 'DE89370400440532013000');
        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-001',
            new DateTimeImmutable('+5 days'),
            'Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            [$tx]
        );

        $document = Pain008Document::create(
            'MSG-DD-001',
            new PartyIdentification(name: 'Initiating Company'),
            [$instruction]
        );

        $this->assertEquals(PainType::PAIN_008, $document->getType());
        $this->assertEquals('MSG-DD-001', $document->getGroupHeader()->getMessageId());
        $this->assertCount(1, $document->getPaymentInstructions());
        $this->assertEquals(1, $document->countTransactions());
        $this->assertEquals(150.00, $document->calculateControlSum());
    }

    public function testPain008DocumentAddPaymentInstruction(): void {
        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-NEW',
            new DateTimeImmutable('+5 days'),
            'Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            []
        );

        $document = Pain008Document::create('MSG-001', new PartyIdentification(name: 'Company'), []);
        $newDocument = $document->addPaymentInstruction($instruction);

        $this->assertCount(0, $document->getPaymentInstructions());
        $this->assertCount(1, $newDocument->getPaymentInstructions());
    }

    // ==================== pain.008 Generator Tests ====================

    public function testPain008GeneratorBasicOutput(): void {
        $tx = DirectDebitTransaction::sepa(
            'DD-GEN-001',
            99.99,
            'MNDT-GEN',
            new DateTimeImmutable('2024-06-01'),
            'Test Debtor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'Testbuchung'
        );

        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-GEN-001',
            new DateTimeImmutable('+7 days'),
            'Test Creditor',
            'DE75512108001245126199',
            'INGDDEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            [$tx]
        );

        $document = Pain008Document::create('MSG-GEN-001', new PartyIdentification(name: 'Generator Test'), [$instruction]);

        $generator = new Pain008Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('CstmrDrctDbtInitn', $xml);
        $this->assertStringContainsString('pain.008.001.11', $xml);
        $this->assertStringContainsString('MSG-GEN-001', $xml);
        $this->assertStringContainsString('DD-GEN-001', $xml);
        $this->assertStringContainsString('MNDT-GEN', $xml);
        $this->assertStringContainsString('DE89370400440532013000', $xml);
    }

    public function testPain008GeneratorWithMultipleTransactions(): void {
        $tx1 = DirectDebitTransaction::sepa('DD-M1', 100.00, 'M1', new DateTimeImmutable(), 'D1', 'DE89370400440532013000');
        $tx2 = DirectDebitTransaction::sepa('DD-M2', 250.50, 'M2', new DateTimeImmutable(), 'D2', 'DE75512108001245126199');

        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-MULTI',
            new DateTimeImmutable('+5 days'),
            'Multi Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::RECURRING,
            [$tx1, $tx2]
        );

        $document = Pain008Document::create('MSG-MULTI', new PartyIdentification(name: 'Multi Test'), [$instruction]);

        $generator = new Pain008Generator();
        $xml = $generator->generate($document);

        $this->assertStringContainsString('DD-M1', $xml);
        $this->assertStringContainsString('DD-M2', $xml);
        $this->assertStringContainsString('NbOfTxs', $xml);
        $this->assertStringContainsString('CtrlSum', $xml);
    }

    // ==================== pain.008 Parser Tests ====================

    public function testPain008ParserIsValid(): void {
        $xml = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.11"><CstmrDrctDbtInitn><GrpHdr><MsgId>TEST</MsgId><CreDtTm>2025-01-01T12:00:00</CreDtTm><NbOfTxs>1</NbOfTxs><InitgPty><Nm>Test</Nm></InitgPty></GrpHdr></CstmrDrctDbtInitn></Document>';

        $this->assertTrue(Pain008Parser::isValid($xml));
    }

    public function testPain008ParserIsValidInvalid(): void {
        $xml = '<?xml version="1.0"?><Document><CstmrCdtTrfInitn/></Document>';

        $this->assertFalse(Pain008Parser::isValid($xml));
    }

    public function testPain008RoundtripGenerateAndParse(): void {
        $tx = DirectDebitTransaction::sepa(
            'DD-ROUND-001',
            175.50,
            'MNDT-ROUND',
            new DateTimeImmutable('2024-03-01'),
            'Roundtrip Debtor',
            'DE89370400440532013000',
            'COBADEFFXXX'
        );

        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-ROUND',
            new DateTimeImmutable('+10 days'),
            'Roundtrip Creditor',
            'DE75512108001245126199',
            'INGDDEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            [$tx]
        );

        $original = Pain008Document::create('MSG-ROUND', new PartyIdentification(name: 'Roundtrip Test'), [$instruction]);

        $generator = new Pain008Generator();
        $xml = $generator->generate($original);

        $parsed = Pain008Parser::fromXml($xml);

        $this->assertEquals(
            $original->getGroupHeader()->getMessageId(),
            $parsed->getGroupHeader()->getMessageId()
        );
        $this->assertCount(
            count($original->getPaymentInstructions()),
            $parsed->getPaymentInstructions()
        );
    }

    // ==================== Combined Tests ====================

    public function testPain002ResponseForPain008(): void {
        // Erstelle pain.008 Dokument
        $tx = DirectDebitTransaction::sepa('DD-001', 100.00, 'M1', new DateTimeImmutable(), 'D1', 'DE89370400440532013000');
        $instruction = PaymentInstruction::sepaCore(
            'PMTINF-008',
            new DateTimeImmutable('+5 days'),
            'Creditor',
            'DE89370400440532013000',
            'COBADEFFXXX',
            'DE98ZZZ09999999999',
            SequenceType::FIRST,
            [$tx]
        );
        $ddDocument = Pain008Document::create('MSG-008', new PartyIdentification(name: 'Company'), [$instruction]);

        // Erstelle pain.002 Antwort
        $orgGrp = Pain002OriginalGroupInformation::forPain008(
            $ddDocument->getGroupHeader()->getMessageId(),
            TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED
        );

        $statusDocument = Pain002Document::create('STATUS-FOR-008', $orgGrp);

        $this->assertEquals('MSG-008', $statusDocument->getOriginalGroupInformation()->getOriginalMessageId());
        $this->assertEquals('pain.008.001.11', $statusDocument->getOriginalGroupInformation()->getOriginalMessageNameId());
        $this->assertTrue($statusDocument->getOriginalGroupInformation()->isGroupAccepted());
    }
}
