<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain002Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type002\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalGroupInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\OriginalPaymentInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\StatusReason;
use CommonToolkit\Entities\Common\Banking\Pain\Type002\TransactionInformationAndStatus;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.002 (Customer Payment Status Report) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain002Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.002.001.14';

    /**
     * Generiert pain.002 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // CstmrPmtStsRpt
        $report = $dom->createElement('CstmrPmtStsRpt');
        $root->appendChild($report);

        // GrpHdr
        $this->appendGroupHeader($dom, $report, $document);

        // OrgnlGrpInfAndSts
        $this->appendOriginalGroupInformation($dom, $report, $document->getOriginalGroupInformation());

        // OrgnlPmtInfAndSts
        foreach ($document->getOriginalPaymentInformations() as $pmtInfo) {
            $this->appendOriginalPaymentInformation($dom, $report, $pmtInfo);
        }

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $header = $document->getGroupHeader();

        $grpHdr->appendChild($dom->createElement('MsgId', $header->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $header->getCreationDateTime()->format('Y-m-d\TH:i:s')));

        if ($header->getInitiatingParty()) {
            $initgPty = $dom->createElement('InitgPty');
            $grpHdr->appendChild($initgPty);

            if ($header->getInitiatingParty()->getName()) {
                $initgPty->appendChild($dom->createElement('Nm', $header->getInitiatingParty()->getName()));
            }
        }
    }

    private function appendOriginalGroupInformation(DOMDocument $dom, DOMElement $parent, OriginalGroupInformation $info): void {
        $orgnlGrp = $dom->createElement('OrgnlGrpInfAndSts');
        $parent->appendChild($orgnlGrp);

        $orgnlGrp->appendChild($dom->createElement('OrgnlMsgId', $info->getOriginalMessageId()));
        $orgnlGrp->appendChild($dom->createElement('OrgnlMsgNmId', $info->getOriginalMessageNameId()));

        if ($info->getOriginalCreationDateTime()) {
            $orgnlGrp->appendChild($dom->createElement('OrgnlCreDtTm', $info->getOriginalCreationDateTime()->format('Y-m-d\TH:i:s')));
        }

        if ($info->getOriginalNumberOfTransactions() !== null) {
            $orgnlGrp->appendChild($dom->createElement('OrgnlNbOfTxs', (string) $info->getOriginalNumberOfTransactions()));
        }

        if ($info->getOriginalControlSum() !== null) {
            $orgnlGrp->appendChild($dom->createElement('OrgnlCtrlSum', number_format($info->getOriginalControlSum(), 2, '.', '')));
        }

        if ($info->getGroupStatus()) {
            $orgnlGrp->appendChild($dom->createElement('GrpSts', $info->getGroupStatus()->value));
        }

        foreach ($info->getStatusReasons() as $reason) {
            $this->appendStatusReason($dom, $orgnlGrp, $reason);
        }
    }

    private function appendOriginalPaymentInformation(DOMDocument $dom, DOMElement $parent, OriginalPaymentInformation $info): void {
        $pmtInf = $dom->createElement('OrgnlPmtInfAndSts');
        $parent->appendChild($pmtInf);

        $pmtInf->appendChild($dom->createElement('OrgnlPmtInfId', $info->getOriginalPaymentInformationId()));

        if ($info->getStatus()) {
            $pmtInf->appendChild($dom->createElement('PmtInfSts', $info->getStatus()->value));
        }

        foreach ($info->getStatusReasons() as $reason) {
            $this->appendStatusReason($dom, $pmtInf, $reason);
        }

        foreach ($info->getTransactionStatuses() as $txStatus) {
            $this->appendTransactionStatus($dom, $pmtInf, $txStatus);
        }
    }

    private function appendTransactionStatus(DOMDocument $dom, DOMElement $parent, TransactionInformationAndStatus $status): void {
        $txInf = $dom->createElement('TxInfAndSts');
        $parent->appendChild($txInf);

        if ($status->getStatusId()) {
            $txInf->appendChild($dom->createElement('StsId', $status->getStatusId()));
        }

        if ($status->getOriginalInstructionId()) {
            $txInf->appendChild($dom->createElement('OrgnlInstrId', $status->getOriginalInstructionId()));
        }

        if ($status->getOriginalEndToEndId()) {
            $txInf->appendChild($dom->createElement('OrgnlEndToEndId', $status->getOriginalEndToEndId()));
        }

        if ($status->getOriginalUetr()) {
            $txInf->appendChild($dom->createElement('OrgnlUETR', $status->getOriginalUetr()));
        }

        if ($status->getStatus()) {
            $txInf->appendChild($dom->createElement('TxSts', $status->getStatus()->value));
        }

        foreach ($status->getStatusReasons() as $reason) {
            $this->appendStatusReason($dom, $txInf, $reason);
        }

        if ($status->getAcceptanceDateTime()) {
            $txInf->appendChild($dom->createElement('AccptncDtTm', $status->getAcceptanceDateTime()->format('Y-m-d\TH:i:s')));
        }
    }

    private function appendStatusReason(DOMDocument $dom, DOMElement $parent, StatusReason $reason): void {
        $stsRsn = $dom->createElement('StsRsnInf');
        $parent->appendChild($stsRsn);

        if ($reason->getCode() || $reason->getProprietary()) {
            $rsn = $dom->createElement('Rsn');
            $stsRsn->appendChild($rsn);

            if ($reason->getCode()) {
                $rsn->appendChild($dom->createElement('Cd', $reason->getCode()));
            } elseif ($reason->getProprietary()) {
                $rsn->appendChild($dom->createElement('Prtry', $reason->getProprietary()));
            }
        }

        foreach ($reason->getAdditionalInfo() as $info) {
            $stsRsn->appendChild($dom->createElement('AddtlInf', $info));
        }
    }
}
