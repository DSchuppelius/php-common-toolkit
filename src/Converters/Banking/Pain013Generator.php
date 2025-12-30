<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain013Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type013\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type013\PaymentActivationRequest;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.013 (Creditor Payment Activation Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain013Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.013.001.11';

    /**
     * Generiert pain.013 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // CdtrPmtActvtnReq
        $request = $dom->createElement('CdtrPmtActvtnReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // PmtInf
        $this->appendPaymentInformation($dom, $request, $document);

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $grpHdr->appendChild($dom->createElement('MsgId', $document->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $document->getCreationDateTime()->format('Y-m-d\TH:i:s')));
        $grpHdr->appendChild($dom->createElement('NbOfTxs', (string) $document->countRequests()));
        $grpHdr->appendChild($dom->createElement('CtrlSum', number_format($document->getControlSum(), 2, '.', '')));

        // InitgPty
        $initgPty = $dom->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);

        if ($document->getInitiatingParty()->getName()) {
            $initgPty->appendChild($dom->createElement('Nm', $document->getInitiatingParty()->getName()));
        }
    }

    private function appendPaymentInformation(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $pmtInf = $dom->createElement('PmtInf');
        $parent->appendChild($pmtInf);

        $pmtInf->appendChild($dom->createElement('PmtInfId', $document->getMessageId() . '-PMTINF'));
        $pmtInf->appendChild($dom->createElement('PmtMtd', 'TRF'));
        $pmtInf->appendChild($dom->createElement('NbOfTxs', (string) $document->countRequests()));
        $pmtInf->appendChild($dom->createElement('CtrlSum', number_format($document->getControlSum(), 2, '.', '')));

        // CdtTrfTxInf
        foreach ($document->getPaymentRequests() as $request) {
            $this->appendPaymentRequest($dom, $pmtInf, $request);
        }
    }

    private function appendPaymentRequest(DOMDocument $dom, DOMElement $parent, PaymentActivationRequest $request): void {
        $cdtTrfTxInf = $dom->createElement('CdtTrfTxInf');
        $parent->appendChild($cdtTrfTxInf);

        // PmtId
        $pmtId = $dom->createElement('PmtId');
        $cdtTrfTxInf->appendChild($pmtId);
        $pmtId->appendChild($dom->createElement('InstrId', $request->getInstructionId()));
        $pmtId->appendChild($dom->createElement('EndToEndId', $request->getEndToEndId()));

        // Amt
        $amt = $dom->createElement('Amt');
        $cdtTrfTxInf->appendChild($amt);

        $instdAmt = $dom->createElement('InstdAmt', number_format($request->getAmount(), 2, '.', ''));
        $instdAmt->setAttribute('Ccy', $request->getCurrency()->value);
        $amt->appendChild($instdAmt);

        // Dbtr
        $dbtr = $dom->createElement('Dbtr');
        $cdtTrfTxInf->appendChild($dbtr);
        if ($request->getDebtor()->getName()) {
            $dbtr->appendChild($dom->createElement('Nm', $request->getDebtor()->getName()));
        }

        // DbtrAcct
        if ($request->getDebtorAccount()->getIban()) {
            $dbtrAcct = $dom->createElement('DbtrAcct');
            $cdtTrfTxInf->appendChild($dbtrAcct);
            $id = $dom->createElement('Id');
            $dbtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $request->getDebtorAccount()->getIban()));
        }

        // DbtrAgt
        if ($request->getDebtorAgent()->getBic()) {
            $dbtrAgt = $dom->createElement('DbtrAgt');
            $cdtTrfTxInf->appendChild($dbtrAgt);
            $finInstnId = $dom->createElement('FinInstnId');
            $dbtrAgt->appendChild($finInstnId);
            $finInstnId->appendChild($dom->createElement('BICFI', $request->getDebtorAgent()->getBic()));
        }

        // Cdtr
        $cdtr = $dom->createElement('Cdtr');
        $cdtTrfTxInf->appendChild($cdtr);
        if ($request->getCreditor()->getName()) {
            $cdtr->appendChild($dom->createElement('Nm', $request->getCreditor()->getName()));
        }

        // CdtrAcct
        if ($request->getCreditorAccount()->getIban()) {
            $cdtrAcct = $dom->createElement('CdtrAcct');
            $cdtTrfTxInf->appendChild($cdtrAcct);
            $id = $dom->createElement('Id');
            $cdtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $request->getCreditorAccount()->getIban()));
        }

        // CdtrAgt
        if ($request->getCreditorAgent()->getBic()) {
            $cdtrAgt = $dom->createElement('CdtrAgt');
            $cdtTrfTxInf->appendChild($cdtrAgt);
            $finInstnId = $dom->createElement('FinInstnId');
            $cdtrAgt->appendChild($finInstnId);
            $finInstnId->appendChild($dom->createElement('BICFI', $request->getCreditorAgent()->getBic()));
        }

        // RmtInf
        if ($request->getRemittanceInformation()) {
            $rmtInf = $dom->createElement('RmtInf');
            $cdtTrfTxInf->appendChild($rmtInf);
            $rmtInf->appendChild($dom->createElement('Ustrd', $request->getRemittanceInformation()));
        }
    }
}
