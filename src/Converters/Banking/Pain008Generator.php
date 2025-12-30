<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain008Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type008\DirectDebitTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\MandateInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\PaymentInstruction;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.008 (Customer Direct Debit Initiation) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain008Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.11';

    /**
     * Generiert pain.008 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // CstmrDrctDbtInitn
        $initiation = $dom->createElement('CstmrDrctDbtInitn');
        $root->appendChild($initiation);

        // GrpHdr
        $this->appendGroupHeader($dom, $initiation, $document);

        // PmtInf
        foreach ($document->getPaymentInstructions() as $instruction) {
            $this->appendPaymentInstruction($dom, $initiation, $instruction);
        }

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $header = $document->getGroupHeader();

        $grpHdr->appendChild($dom->createElement('MsgId', $header->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $header->getCreationDateTime()->format('Y-m-d\TH:i:s')));
        $grpHdr->appendChild($dom->createElement('NbOfTxs', (string) $document->countTransactions()));
        $grpHdr->appendChild($dom->createElement('CtrlSum', number_format($document->calculateControlSum(), 2, '.', '')));

        // InitgPty
        $initgPty = $dom->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);

        if ($header->getInitiatingParty()->getName()) {
            $initgPty->appendChild($dom->createElement('Nm', $header->getInitiatingParty()->getName()));
        }
    }

    private function appendPaymentInstruction(DOMDocument $dom, DOMElement $parent, PaymentInstruction $instruction): void {
        $pmtInf = $dom->createElement('PmtInf');
        $parent->appendChild($pmtInf);

        $pmtInf->appendChild($dom->createElement('PmtInfId', $instruction->getPaymentInstructionId()));
        $pmtInf->appendChild($dom->createElement('PmtMtd', $instruction->getPaymentMethod()->value));
        $pmtInf->appendChild($dom->createElement('NbOfTxs', (string) $instruction->countTransactions()));
        $pmtInf->appendChild($dom->createElement('CtrlSum', number_format($instruction->calculateControlSum(), 2, '.', '')));

        // PmtTpInf
        $pmtTpInf = $dom->createElement('PmtTpInf');
        $pmtInf->appendChild($pmtTpInf);

        if ($instruction->getServiceLevel()) {
            $svcLvl = $dom->createElement('SvcLvl');
            $pmtTpInf->appendChild($svcLvl);
            $svcLvl->appendChild($dom->createElement('Cd', $instruction->getServiceLevel()));
        }

        if ($instruction->getLocalInstrument()) {
            $lclInstrm = $dom->createElement('LclInstrm');
            $pmtTpInf->appendChild($lclInstrm);
            $lclInstrm->appendChild($dom->createElement('Cd', $instruction->getLocalInstrument()->value));
        }

        if ($instruction->getSequenceType()) {
            $pmtTpInf->appendChild($dom->createElement('SeqTp', $instruction->getSequenceType()->value));
        }

        // ReqdColltnDt
        $pmtInf->appendChild($dom->createElement('ReqdColltnDt', $instruction->getRequestedCollectionDate()->format('Y-m-d')));

        // Cdtr
        $this->appendParty($dom, $pmtInf, 'Cdtr', $instruction->getCreditor());

        // CdtrAcct
        $this->appendAccount($dom, $pmtInf, 'CdtrAcct', $instruction->getCreditorAccount());

        // CdtrAgt
        $this->appendAgent($dom, $pmtInf, 'CdtrAgt', $instruction->getCreditorAgent());

        // CdtrSchmeId
        if ($instruction->getCreditorSchemeId()) {
            $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
            $pmtInf->appendChild($cdtrSchmeId);

            $id = $dom->createElement('Id');
            $cdtrSchmeId->appendChild($id);

            $prvtId = $dom->createElement('PrvtId');
            $id->appendChild($prvtId);

            $othr = $dom->createElement('Othr');
            $prvtId->appendChild($othr);

            $othr->appendChild($dom->createElement('Id', $instruction->getCreditorSchemeId()));

            $schmeNm = $dom->createElement('SchmeNm');
            $othr->appendChild($schmeNm);
            $schmeNm->appendChild($dom->createElement('Prtry', 'SEPA'));
        }

        // ChrgBr
        if ($instruction->getChargeBearer()) {
            $pmtInf->appendChild($dom->createElement('ChrgBr', $instruction->getChargeBearer()->value));
        }

        // DrctDbtTxInf
        foreach ($instruction->getTransactions() as $transaction) {
            $this->appendTransaction($dom, $pmtInf, $transaction);
        }
    }

    private function appendTransaction(DOMDocument $dom, DOMElement $parent, DirectDebitTransaction $transaction): void {
        $txInf = $dom->createElement('DrctDbtTxInf');
        $parent->appendChild($txInf);

        // PmtId
        $pmtId = $dom->createElement('PmtId');
        $txInf->appendChild($pmtId);

        if ($transaction->getPaymentId()->getInstructionId()) {
            $pmtId->appendChild($dom->createElement('InstrId', $transaction->getPaymentId()->getInstructionId()));
        }
        $pmtId->appendChild($dom->createElement('EndToEndId', $transaction->getPaymentId()->getEndToEndId()));

        // InstdAmt
        $instdAmt = $dom->createElement('InstdAmt', number_format($transaction->getAmount(), 2, '.', ''));
        $instdAmt->setAttribute('Ccy', $transaction->getCurrency()->value);
        $txInf->appendChild($instdAmt);

        // DrctDbtTx
        $drctDbtTx = $dom->createElement('DrctDbtTx');
        $txInf->appendChild($drctDbtTx);

        $this->appendMandateInfo($dom, $drctDbtTx, $transaction->getMandateInfo());

        // DbtrAgt
        if ($transaction->getDebtorAgent()) {
            $this->appendAgent($dom, $txInf, 'DbtrAgt', $transaction->getDebtorAgent());
        }

        // Dbtr
        $this->appendParty($dom, $txInf, 'Dbtr', $transaction->getDebtor());

        // DbtrAcct
        $this->appendAccount($dom, $txInf, 'DbtrAcct', $transaction->getDebtorAccount());

        // RmtInf
        if ($transaction->getRemittanceInformation()) {
            $rmtInf = $dom->createElement('RmtInf');
            $txInf->appendChild($rmtInf);

            foreach ($transaction->getRemittanceInformation()->getUnstructured() as $line) {
                $rmtInf->appendChild($dom->createElement('Ustrd', $line));
            }
        }
    }

    private function appendMandateInfo(DOMDocument $dom, DOMElement $parent, MandateInformation $info): void {
        $mndtRltdInf = $dom->createElement('MndtRltdInf');
        $parent->appendChild($mndtRltdInf);

        $mndtRltdInf->appendChild($dom->createElement('MndtId', $info->getMandateId()));
        $mndtRltdInf->appendChild($dom->createElement('DtOfSgntr', $info->getDateOfSignature()->format('Y-m-d')));

        if ($info->getAmendmentIndicator() !== null) {
            $mndtRltdInf->appendChild($dom->createElement('AmdmntInd', $info->isAmended() ? 'true' : 'false'));

            if ($info->isAmended()) {
                $amdmntInfDtls = $dom->createElement('AmdmntInfDtls');
                $mndtRltdInf->appendChild($amdmntInfDtls);

                if ($info->getOriginalMandateId()) {
                    $amdmntInfDtls->appendChild($dom->createElement('OrgnlMndtId', $info->getOriginalMandateId()));
                }

                if ($info->getOriginalCreditorSchemeId()) {
                    $orgnlCdtrSchmeId = $dom->createElement('OrgnlCdtrSchmeId');
                    $amdmntInfDtls->appendChild($orgnlCdtrSchmeId);

                    $id = $dom->createElement('Id');
                    $orgnlCdtrSchmeId->appendChild($id);

                    $prvtId = $dom->createElement('PrvtId');
                    $id->appendChild($prvtId);

                    $othr = $dom->createElement('Othr');
                    $prvtId->appendChild($othr);
                    $othr->appendChild($dom->createElement('Id', $info->getOriginalCreditorSchemeId()));
                }
            }
        }
    }

    private function appendParty(DOMDocument $dom, DOMElement $parent, string $elementName, $party): void {
        $element = $dom->createElement($elementName);
        $parent->appendChild($element);

        if ($party->getName()) {
            $element->appendChild($dom->createElement('Nm', $party->getName()));
        }

        if ($party->getPostalAddress()) {
            $addr = $party->getPostalAddress();
            $pstlAdr = $dom->createElement('PstlAdr');
            $element->appendChild($pstlAdr);

            if ($addr->getStreetName()) {
                $pstlAdr->appendChild($dom->createElement('StrtNm', $addr->getStreetName()));
            }
            if ($addr->getBuildingNumber()) {
                $pstlAdr->appendChild($dom->createElement('BldgNb', $addr->getBuildingNumber()));
            }
            if ($addr->getPostCode()) {
                $pstlAdr->appendChild($dom->createElement('PstCd', $addr->getPostCode()));
            }
            if ($addr->getTownName()) {
                $pstlAdr->appendChild($dom->createElement('TwnNm', $addr->getTownName()));
            }
            if ($addr->getCountry()) {
                $pstlAdr->appendChild($dom->createElement('Ctry', $addr->getCountry()->value));
            }
        }
    }

    private function appendAccount(DOMDocument $dom, DOMElement $parent, string $elementName, $account): void {
        $element = $dom->createElement($elementName);
        $parent->appendChild($element);

        $id = $dom->createElement('Id');
        $element->appendChild($id);

        if ($account->getIban()) {
            $id->appendChild($dom->createElement('IBAN', $account->getIban()));
        } elseif ($account->getOther()) {
            $othr = $dom->createElement('Othr');
            $id->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', $account->getOther()));
        }
    }

    private function appendAgent(DOMDocument $dom, DOMElement $parent, string $elementName, $agent): void {
        $element = $dom->createElement($elementName);
        $parent->appendChild($element);

        $finInstnId = $dom->createElement('FinInstnId');
        $element->appendChild($finInstnId);

        if ($agent->getBic()) {
            $finInstnId->appendChild($dom->createElement('BICFI', $agent->getBic()));
        }
    }
}
