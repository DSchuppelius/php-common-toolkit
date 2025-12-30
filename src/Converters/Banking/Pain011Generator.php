<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain011Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type011\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type011\MandateCancellation;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.011 (Mandate Cancellation Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain011Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.011.001.08';

    /**
     * Generiert pain.011 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtCxlReq
        $request = $dom->createElement('MndtCxlReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // UndrlygCxlDtls
        foreach ($document->getMandateCancellations() as $cancellation) {
            $this->appendMandateCancellation($dom, $request, $cancellation);
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

    private function appendMandateCancellation(DOMDocument $dom, DOMElement $parent, MandateCancellation $cancellation): void {
        $undrlygCxlDtls = $dom->createElement('UndrlygCxlDtls');
        $parent->appendChild($undrlygCxlDtls);

        // OrgnlMndt
        $orgnlMndt = $dom->createElement('OrgnlMndt');
        $undrlygCxlDtls->appendChild($orgnlMndt);

        $orgnlMndt->appendChild($dom->createElement('MndtId', $cancellation->getMandateId()));

        // Original Mandate Details wenn vorhanden
        if ($originalMandate = $cancellation->getOriginalMandate()) {
            $orgnlMndt->appendChild($dom->createElement('DtOfSgntr', $originalMandate->getDateOfSignature()->format('Y-m-d')));

            // Cdtr
            if ($originalMandate->getCreditor()->getName()) {
                $cdtr = $dom->createElement('Cdtr');
                $orgnlMndt->appendChild($cdtr);
                $cdtr->appendChild($dom->createElement('Nm', $originalMandate->getCreditor()->getName()));
            }

            // CdtrSchmeId
            if ($originalMandate->getCreditorSchemeId()) {
                $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
                $orgnlMndt->appendChild($cdtrSchmeId);

                $id = $dom->createElement('Id');
                $cdtrSchmeId->appendChild($id);

                $prvtId = $dom->createElement('PrvtId');
                $id->appendChild($prvtId);

                $othr = $dom->createElement('Othr');
                $prvtId->appendChild($othr);
                $othr->appendChild($dom->createElement('Id', $originalMandate->getCreditorSchemeId()));
            }

            // Dbtr
            if ($originalMandate->getDebtor()->getName()) {
                $dbtr = $dom->createElement('Dbtr');
                $orgnlMndt->appendChild($dbtr);
                $dbtr->appendChild($dom->createElement('Nm', $originalMandate->getDebtor()->getName()));
            }

            // DbtrAcct
            if ($originalMandate->getDebtorAccount()->getIban()) {
                $dbtrAcct = $dom->createElement('DbtrAcct');
                $orgnlMndt->appendChild($dbtrAcct);
                $id = $dom->createElement('Id');
                $dbtrAcct->appendChild($id);
                $id->appendChild($dom->createElement('IBAN', $originalMandate->getDebtorAccount()->getIban()));
            }
        }

        // CxlRsn
        $reason = $cancellation->getCancellationReason();
        $cxlRsn = $dom->createElement('CxlRsn');
        $undrlygCxlDtls->appendChild($cxlRsn);

        $rsn = $dom->createElement('Rsn');
        $cxlRsn->appendChild($rsn);

        if ($reason->getCode()) {
            $rsn->appendChild($dom->createElement('Cd', $reason->getCode()));
        } elseif ($reason->getProprietary()) {
            $rsn->appendChild($dom->createElement('Prtry', $reason->getProprietary()));
        }

        foreach ($reason->getAdditionalInfo() as $addtlInf) {
            $cxlRsn->appendChild($dom->createElement('AddtlInf', $addtlInf));
        }
    }
}
