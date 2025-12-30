<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain012Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type012\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type012\MandateAcceptance;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.012 (Mandate Acceptance Report) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain012Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.012.001.08';

    /**
     * Generiert pain.012 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtAccptncRpt
        $report = $dom->createElement('MndtAccptncRpt');
        $root->appendChild($report);

        // GrpHdr
        $this->appendGroupHeader($dom, $report, $document);

        // OrgnlMsgInf
        $this->appendOriginalMessageInfo($dom, $report, $document);

        // UndrlygAccptncDtls
        foreach ($document->getMandateAcceptances() as $acceptance) {
            $this->appendMandateAcceptance($dom, $report, $acceptance);
        }

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

    private function appendOriginalMessageInfo(DOMDocument $dom, DOMElement $parent, Document $document): void {
        $orgnlMsgInf = $dom->createElement('OrgnlMsgInf');
        $parent->appendChild($orgnlMsgInf);

        if ($document->getOriginalMessageId()) {
            $orgnlMsgInf->appendChild($dom->createElement('OrgnlMsgId', $document->getOriginalMessageId()));
        }

        if ($document->getOriginalMessageNameId()) {
            $orgnlMsgInf->appendChild($dom->createElement('OrgnlMsgNmId', $document->getOriginalMessageNameId()));
        }
    }

    private function appendMandateAcceptance(DOMDocument $dom, DOMElement $parent, MandateAcceptance $acceptance): void {
        $undrlygAccptncDtls = $dom->createElement('UndrlygAccptncDtls');
        $parent->appendChild($undrlygAccptncDtls);

        // OrgnlMndtId
        $undrlygAccptncDtls->appendChild($dom->createElement('OrgnlMndtId', $acceptance->getMandateId()));

        // AccptncSts
        $accptncSts = $dom->createElement('AccptncSts');
        $undrlygAccptncDtls->appendChild($accptncSts);

        $accptncSts->appendChild($dom->createElement('Accptd', $acceptance->isAccepted() ? 'true' : 'false'));

        // AccptncDtTm
        if ($acceptance->getAcceptanceDateTime()) {
            $undrlygAccptncDtls->appendChild($dom->createElement('AccptncDtTm', $acceptance->getAcceptanceDateTime()->format('Y-m-d\TH:i:s')));
        }

        // RjctRsn
        if ($acceptance->isRejected() && $acceptance->getRejectReason()) {
            $rjctRsn = $dom->createElement('RjctRsn');
            $undrlygAccptncDtls->appendChild($rjctRsn);

            $rjctRsn->appendChild($dom->createElement('Prtry', $acceptance->getRejectReason()));
        }

        // Mndt (wenn vorhanden)
        if ($mandate = $acceptance->getMandate()) {
            $mndt = $dom->createElement('Mndt');
            $undrlygAccptncDtls->appendChild($mndt);

            $mndt->appendChild($dom->createElement('MndtId', $mandate->getMandateId()));
            $mndt->appendChild($dom->createElement('DtOfSgntr', $mandate->getDateOfSignature()->format('Y-m-d')));
        }
    }
}
