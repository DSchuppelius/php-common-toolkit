<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain010Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type010\AmendmentDetails;
use CommonToolkit\Entities\Common\Banking\Pain\Type010\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type010\MandateAmendment;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.010 (Mandate Amendment Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain010Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.010.001.08';

    /**
     * Generiert pain.010 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtAmdmntReq
        $request = $dom->createElement('MndtAmdmntReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // UndrlygAmdmntDtls
        foreach ($document->getMandateAmendments() as $amendment) {
            $this->appendMandateAmendment($dom, $request, $amendment);
        }

        return $dom->saveXML();
    }

    private function appendGroupHeader(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $grpHdr = $dom->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        $grpHdr->appendChild($dom->createElement('MsgId', $document->getMessageId()));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $document->getCreationDateTime()->format('Y-m-d\TH:i:s')));

        // InitgPty
        $initgPty = $dom->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);

        if ($document->getInitiatingParty()->getName()) {
            $initgPty->appendChild($dom->createElement('Nm', $document->getInitiatingParty()->getName()));
        }
    }

    private function appendMandateAmendment(DOMDocument $dom, DOMElement $parent, MandateAmendment $amendment): void {
        $undrlygAmdmntDtls = $dom->createElement('UndrlygAmdmntDtls');
        $parent->appendChild($undrlygAmdmntDtls);

        $mandate = $amendment->getMandate();
        $details = $amendment->getAmendmentDetails();

        // Mndt
        $mndt = $dom->createElement('Mndt');
        $undrlygAmdmntDtls->appendChild($mndt);

        $mndt->appendChild($dom->createElement('MndtId', $mandate->getMandateId()));
        $mndt->appendChild($dom->createElement('DtOfSgntr', $mandate->getDateOfSignature()->format('Y-m-d')));

        // Cdtr
        $cdtr = $dom->createElement('Cdtr');
        $mndt->appendChild($cdtr);
        if ($mandate->getCreditor()->getName()) {
            $cdtr->appendChild($dom->createElement('Nm', $mandate->getCreditor()->getName()));
        }

        // CdtrAcct
        if ($mandate->getCreditorAccount()->getIban()) {
            $cdtrAcct = $dom->createElement('CdtrAcct');
            $mndt->appendChild($cdtrAcct);
            $id = $dom->createElement('Id');
            $cdtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $mandate->getCreditorAccount()->getIban()));
        }

        // Dbtr
        $dbtr = $dom->createElement('Dbtr');
        $mndt->appendChild($dbtr);
        if ($mandate->getDebtor()->getName()) {
            $dbtr->appendChild($dom->createElement('Nm', $mandate->getDebtor()->getName()));
        }

        // DbtrAcct
        if ($mandate->getDebtorAccount()->getIban()) {
            $dbtrAcct = $dom->createElement('DbtrAcct');
            $mndt->appendChild($dbtrAcct);
            $id = $dom->createElement('Id');
            $dbtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $mandate->getDebtorAccount()->getIban()));
        }

        // OrgnlMndt (Amendment Details)
        $this->appendAmendmentDetails($dom, $undrlygAmdmntDtls, $details);
    }

    private function appendAmendmentDetails(DOMDocument $dom, DOMElement $parent, AmendmentDetails $details): void {
        $orgnlMndt = $dom->createElement('OrgnlMndt');
        $parent->appendChild($orgnlMndt);

        if ($details->getOriginalMandateId()) {
            $orgnlMndt->appendChild($dom->createElement('OrgnlMndtId', $details->getOriginalMandateId()));
        }

        if ($details->getOriginalCreditorSchemeId()) {
            $orgnlCdtrSchmeId = $dom->createElement('OrgnlCdtrSchmeId');
            $orgnlMndt->appendChild($orgnlCdtrSchmeId);

            $id = $dom->createElement('Id');
            $orgnlCdtrSchmeId->appendChild($id);

            $prvtId = $dom->createElement('PrvtId');
            $id->appendChild($prvtId);

            $othr = $dom->createElement('Othr');
            $prvtId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', $details->getOriginalCreditorSchemeId()));
        }

        if ($details->getOriginalDebtorAccount()) {
            $orgnlDbtrAcct = $dom->createElement('OrgnlDbtrAcct');
            $orgnlMndt->appendChild($orgnlDbtrAcct);

            $id = $dom->createElement('Id');
            $orgnlDbtrAcct->appendChild($id);

            if ($details->getOriginalDebtorAccount()->getIban()) {
                $id->appendChild($dom->createElement('IBAN', $details->getOriginalDebtorAccount()->getIban()));
            }
        }
    }
}
