<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain009Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;
use CommonToolkit\Entities\Common\Banking\Pain\Type009\Document;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.009 (Mandate Initiation Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain009Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.009.001.08';

    /**
     * Generiert pain.009 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtInitnReq
        $request = $dom->createElement('MndtInitnReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // Mndt
        foreach ($document->getMandates() as $mandate) {
            $this->appendMandate($dom, $request, $mandate);
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

    private function appendMandate(DOMDocument $dom, DOMElement $parent, Mandate $mandate): void {
        $mndt = $dom->createElement('Mndt');
        $parent->appendChild($mndt);

        $mndt->appendChild($dom->createElement('MndtId', $mandate->getMandateId()));
        $mndt->appendChild($dom->createElement('DtOfSgntr', $mandate->getDateOfSignature()->format('Y-m-d')));

        // MndtTpInf
        if ($mandate->getLocalInstrument() || $mandate->getSequenceType()) {
            $mndtTpInf = $dom->createElement('MndtTpInf');
            $mndt->appendChild($mndtTpInf);

            if ($mandate->getLocalInstrument()) {
                $lclInstrm = $dom->createElement('LclInstrm');
                $mndtTpInf->appendChild($lclInstrm);
                $lclInstrm->appendChild($dom->createElement('Cd', $mandate->getLocalInstrument()->value));
            }

            if ($mandate->getSequenceType()) {
                $mndtTpInf->appendChild($dom->createElement('SeqTp', $mandate->getSequenceType()->value));
            }
        }

        // Cdtr
        $this->appendParty($dom, $mndt, 'Cdtr', $mandate->getCreditor()->getName());

        // CdtrAcct
        if ($mandate->getCreditorAccount()->getIban()) {
            $cdtrAcct = $dom->createElement('CdtrAcct');
            $mndt->appendChild($cdtrAcct);
            $id = $dom->createElement('Id');
            $cdtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $mandate->getCreditorAccount()->getIban()));
        }

        // CdtrAgt
        if ($mandate->getCreditorAgent()->getBic()) {
            $cdtrAgt = $dom->createElement('CdtrAgt');
            $mndt->appendChild($cdtrAgt);
            $finInstnId = $dom->createElement('FinInstnId');
            $cdtrAgt->appendChild($finInstnId);
            $finInstnId->appendChild($dom->createElement('BICFI', $mandate->getCreditorAgent()->getBic()));
        }

        // CdtrSchmeId
        if ($mandate->getCreditorSchemeId()) {
            $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
            $mndt->appendChild($cdtrSchmeId);

            $id = $dom->createElement('Id');
            $cdtrSchmeId->appendChild($id);

            $prvtId = $dom->createElement('PrvtId');
            $id->appendChild($prvtId);

            $othr = $dom->createElement('Othr');
            $prvtId->appendChild($othr);

            $othr->appendChild($dom->createElement('Id', $mandate->getCreditorSchemeId()));

            $schmeNm = $dom->createElement('SchmeNm');
            $othr->appendChild($schmeNm);
            $schmeNm->appendChild($dom->createElement('Prtry', 'SEPA'));
        }

        // Dbtr
        $this->appendParty($dom, $mndt, 'Dbtr', $mandate->getDebtor()->getName());

        // DbtrAcct
        if ($mandate->getDebtorAccount()->getIban()) {
            $dbtrAcct = $dom->createElement('DbtrAcct');
            $mndt->appendChild($dbtrAcct);
            $id = $dom->createElement('Id');
            $dbtrAcct->appendChild($id);
            $id->appendChild($dom->createElement('IBAN', $mandate->getDebtorAccount()->getIban()));
        }

        // DbtrAgt
        if ($mandate->getDebtorAgent()->getBic()) {
            $dbtrAgt = $dom->createElement('DbtrAgt');
            $mndt->appendChild($dbtrAgt);
            $finInstnId = $dom->createElement('FinInstnId');
            $dbtrAgt->appendChild($finInstnId);
            $finInstnId->appendChild($dom->createElement('BICFI', $mandate->getDebtorAgent()->getBic()));
        }
    }

    private function appendParty(DOMDocument $dom, DOMElement $parent, string $tagName, ?string $name): void {
        $party = $dom->createElement($tagName);
        $parent->appendChild($party);

        if ($name) {
            $party->appendChild($dom->createElement('Nm', $name));
        }
    }
}
