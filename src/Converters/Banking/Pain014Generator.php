<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain014Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type014\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type014\PaymentActivationStatus;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.014 (Creditor Payment Activation Request Status Report) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain014Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.014.001.11';

    /**
     * Generiert pain.014 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // CdtrPmtActvtnReqStsRpt
        $report = $dom->createElement('CdtrPmtActvtnReqStsRpt');
        $root->appendChild($report);

        // GrpHdr
        $this->appendGroupHeader($dom, $report, $document);

        // OrgnlGrpInfAndSts
        $this->appendOriginalGroupInfoAndStatus($dom, $report, $document);

        // OrgnlPmtInfAndSts
        $this->appendPaymentStatuses($dom, $report, $document);

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $grpHdr->appendChild($dom->createElement('MsgId', $document->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $document->getCreationDateTime()->format('Y-m-d\TH:i:s')));

        // InitgPty
        if ($document->getInitiatingParty()) {
            $initgPty = $dom->createElement('InitgPty');
            $grpHdr->appendChild($initgPty);

            if ($document->getInitiatingParty()->getName()) {
                $initgPty->appendChild($dom->createElement('Nm', $document->getInitiatingParty()->getName()));
            }
        }
    }

    private function appendOriginalGroupInfoAndStatus(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $orgnlGrpInfAndSts = $dom->createElement('OrgnlGrpInfAndSts');
        $parent->appendChild($orgnlGrpInfAndSts);

        $orgnlGrpInfAndSts->appendChild($dom->createElement('OrgnlMsgId', $document->getOriginalMessageId()));
        $orgnlGrpInfAndSts->appendChild($dom->createElement('OrgnlMsgNmId', $document->getOriginalMessageNameId()));

        if ($document->getOriginalNumberOfTransactions() > 0) {
            $orgnlGrpInfAndSts->appendChild($dom->createElement('OrgnlNbOfTxs', (string) $document->getOriginalNumberOfTransactions()));
        }

        if ($document->getOriginalControlSum() > 0) {
            $orgnlGrpInfAndSts->appendChild($dom->createElement('OrgnlCtrlSum', number_format($document->getOriginalControlSum(), 2, '.', '')));
        }
    }

    private function appendPaymentStatuses(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $orgnlPmtInfAndSts = $dom->createElement('OrgnlPmtInfAndSts');
        $parent->appendChild($orgnlPmtInfAndSts);

        foreach ($document->getPaymentStatuses() as $status) {
            $this->appendPaymentStatus($dom, $orgnlPmtInfAndSts, $status);
        }
    }

    private function appendPaymentStatus(DOMDocument $dom, DOMElement $parent, PaymentActivationStatus $status): void {
        $txInfAndSts = $dom->createElement('TxInfAndSts');
        $parent->appendChild($txInfAndSts);

        $txInfAndSts->appendChild($dom->createElement('OrgnlInstrId', $status->getOriginalInstructionId()));
        $txInfAndSts->appendChild($dom->createElement('OrgnlEndToEndId', $status->getOriginalEndToEndId()));
        $txInfAndSts->appendChild($dom->createElement('TxSts', $status->getStatus()->value));

        if ($status->getOriginalAmount() !== null) {
            $orgnlInstdAmt = $dom->createElement('OrgnlInstdAmt', number_format($status->getOriginalAmount(), 2, '.', ''));
            $orgnlInstdAmt->setAttribute('Ccy', 'EUR');
            $txInfAndSts->appendChild($orgnlInstdAmt);
        }

        if ($status->getStatusReason()) {
            $stsRsnInf = $dom->createElement('StsRsnInf');
            $txInfAndSts->appendChild($stsRsnInf);

            $rsn = $dom->createElement('Rsn');
            $stsRsnInf->appendChild($rsn);

            if ($status->getStatusReason()->getCode()) {
                $rsn->appendChild($dom->createElement('Cd', $status->getStatusReason()->getCode()));
            } elseif ($status->getStatusReason()->getProprietary()) {
                $rsn->appendChild($dom->createElement('Prtry', $status->getStatusReason()->getProprietary()));
            }
        }
    }
}
