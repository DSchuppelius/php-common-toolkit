<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain007Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type007\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\OriginalPaymentInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\ReversalReason;
use CommonToolkit\Entities\Common\Banking\Pain\Type007\TransactionInformation;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.007 (Customer Payment Reversal) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain007Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.007.001.12';

    /**
     * Generiert pain.007 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // CstmrPmtRvsl
        $reversal = $dom->createElement('CstmrPmtRvsl');
        $root->appendChild($reversal);

        // GrpHdr
        $this->appendGroupHeader($dom, $reversal, $document);

        // OrgnlGrpInf
        if ($document->getOriginalGroupInformation()) {
            $this->appendOriginalGroupInformation($dom, $reversal, $document);
        }

        // OrgnlPmtInfAndRvsl
        foreach ($document->getOriginalPaymentInformations() as $info) {
            $this->appendOriginalPaymentInformation($dom, $reversal, $info);
        }

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $header = $document->getGroupHeader();

        $grpHdr->appendChild($dom->createElement('MsgId', $header->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $header->getCreationDateTime()->format('Y-m-d\TH:i:s')));

        if ($header->getNumberOfTransactions() > 0) {
            $grpHdr->appendChild($dom->createElement('NbOfTxs', (string) $header->getNumberOfTransactions()));
        }

        if ($header->getControlSum() !== null) {
            $grpHdr->appendChild($dom->createElement('CtrlSum', number_format($header->getControlSum(), 2, '.', '')));
        }

        if ($header->isGroupReversal()) {
            $grpHdr->appendChild($dom->createElement('GrpRvsl', 'true'));
        }

        // InitgPty
        if ($header->getInitiatingParty()) {
            $initgPty = $dom->createElement('InitgPty');
            $grpHdr->appendChild($initgPty);

            if ($header->getInitiatingParty()->getName()) {
                $initgPty->appendChild($dom->createElement('Nm', $header->getInitiatingParty()->getName()));
            }
        }
    }

    private function appendOriginalGroupInformation(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $orgInfo = $document->getOriginalGroupInformation();
        if (!$orgInfo) {
            return;
        }

        $orgnlGrpInf = $dom->createElement('OrgnlGrpInf');
        $parent->appendChild($orgnlGrpInf);

        $orgnlGrpInf->appendChild($dom->createElement('OrgnlMsgId', $orgInfo->getOriginalMessageId()));
        $orgnlGrpInf->appendChild($dom->createElement('OrgnlMsgNmId', $orgInfo->getOriginalMessageNameId()));

        if ($orgInfo->getOriginalCreationDateTime()) {
            $orgnlGrpInf->appendChild($dom->createElement('OrgnlCreDtTm', $orgInfo->getOriginalCreationDateTime()->format('Y-m-d\TH:i:s')));
        }

        if ($orgInfo->getOriginalNumberOfTransactions() !== null) {
            $orgnlGrpInf->appendChild($dom->createElement('OrgnlNbOfTxs', (string) $orgInfo->getOriginalNumberOfTransactions()));
        }

        if ($orgInfo->getOriginalControlSum() !== null) {
            $orgnlGrpInf->appendChild($dom->createElement('OrgnlCtrlSum', number_format($orgInfo->getOriginalControlSum(), 2, '.', '')));
        }

        if ($orgInfo->getReversalReason()) {
            $this->appendReversalReason($dom, $orgnlGrpInf, $orgInfo->getReversalReason());
        }
    }

    private function appendOriginalPaymentInformation(DOMDocument $dom, DOMElement $parent, OriginalPaymentInformation $info): void {
        $orgnlPmtInfAndRvsl = $dom->createElement('OrgnlPmtInfAndRvsl');
        $parent->appendChild($orgnlPmtInfAndRvsl);

        $orgnlPmtInfAndRvsl->appendChild($dom->createElement('OrgnlPmtInfId', $info->getOriginalPaymentInformationId()));

        if ($info->getOriginalNumberOfTransactions() !== null) {
            $orgnlPmtInfAndRvsl->appendChild($dom->createElement('OrgnlNbOfTxs', (string) $info->getOriginalNumberOfTransactions()));
        }

        if ($info->getOriginalControlSum() !== null) {
            $orgnlPmtInfAndRvsl->appendChild($dom->createElement('OrgnlCtrlSum', number_format($info->getOriginalControlSum(), 2, '.', '')));
        }

        if ($info->isPaymentInformationReversal()) {
            $orgnlPmtInfAndRvsl->appendChild($dom->createElement('PmtInfRvsl', 'true'));
        }

        if ($info->getReversalReason()) {
            $this->appendReversalReason($dom, $orgnlPmtInfAndRvsl, $info->getReversalReason());
        }

        foreach ($info->getTransactionInformations() as $tx) {
            $this->appendTransactionInformation($dom, $orgnlPmtInfAndRvsl, $tx);
        }
    }

    private function appendTransactionInformation(DOMDocument $dom, DOMElement $parent, TransactionInformation $tx): void {
        $txInf = $dom->createElement('TxInf');
        $parent->appendChild($txInf);

        if ($tx->getReversalId()) {
            $txInf->appendChild($dom->createElement('RvslId', $tx->getReversalId()));
        }

        if ($tx->getOriginalInstructionId()) {
            $txInf->appendChild($dom->createElement('OrgnlInstrId', $tx->getOriginalInstructionId()));
        }

        $txInf->appendChild($dom->createElement('OrgnlEndToEndId', $tx->getOriginalEndToEndId()));

        if ($tx->getReversedAmount() !== null) {
            $rvsdInstdAmt = $dom->createElement('RvsdInstdAmt', number_format($tx->getReversedAmount(), 2, '.', ''));
            $rvsdInstdAmt->setAttribute('Ccy', $tx->getCurrency()?->value ?? 'EUR');
            $txInf->appendChild($rvsdInstdAmt);
        }

        if ($tx->getReversalReason()) {
            $this->appendReversalReason($dom, $txInf, $tx->getReversalReason());
        }
    }

    private function appendReversalReason(DOMDocument $dom, DOMElement $parent, ReversalReason $reason): void {
        $rvslRsnInf = $dom->createElement('RvslRsnInf');
        $parent->appendChild($rvslRsnInf);

        $rsn = $dom->createElement('Rsn');
        $rvslRsnInf->appendChild($rsn);

        if ($reason->getCode()) {
            $rsn->appendChild($dom->createElement('Cd', $reason->getCode()));
        } elseif ($reason->getProprietary()) {
            $rsn->appendChild($dom->createElement('Prtry', $reason->getProprietary()));
        }

        foreach ($reason->getAdditionalInfo() as $addtlInf) {
            $rvslRsnInf->appendChild($dom->createElement('AddtlInf', $addtlInf));
        }
    }
}
