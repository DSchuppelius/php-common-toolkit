<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain018Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type018\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type018\MandateSuspensionRequest;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.018 (Mandate Suspension Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain018Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.018.001.04';

    /**
     * Generiert pain.018 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtSspnsnReq
        $request = $dom->createElement('MndtSspnsnReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // UndrlygSspnsnDtls
        foreach ($document->getSuspensionRequests() as $suspensionRequest) {
            $this->appendSuspensionRequest($dom, $request, $suspensionRequest);
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

    private function appendSuspensionRequest(DOMDocument $dom, DOMElement $parent, MandateSuspensionRequest $request): void {
        $undrlygSspnsnDtls = $dom->createElement('UndrlygSspnsnDtls');
        $parent->appendChild($undrlygSspnsnDtls);

        // OrgnlMndt
        $orgnlMndt = $dom->createElement('OrgnlMndt');
        $undrlygSspnsnDtls->appendChild($orgnlMndt);

        $orgnlMndt->appendChild($dom->createElement('MndtId', $request->getMandateId()));

        if ($request->getCreditorSchemeId()) {
            $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
            $orgnlMndt->appendChild($cdtrSchmeId);

            $id = $dom->createElement('Id');
            $cdtrSchmeId->appendChild($id);

            $prvtId = $dom->createElement('PrvtId');
            $id->appendChild($prvtId);

            $othr = $dom->createElement('Othr');
            $prvtId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', $request->getCreditorSchemeId()));
        }

        // SspnsnPrd
        $sspnsnPrd = $dom->createElement('SspnsnPrd');
        $undrlygSspnsnDtls->appendChild($sspnsnPrd);

        $sspnsnPrd->appendChild($dom->createElement('FrDt', $request->getSuspensionStartDate()->format('Y-m-d')));
        $sspnsnPrd->appendChild($dom->createElement('ToDt', $request->getSuspensionEndDate()->format('Y-m-d')));

        // SspnsnRsn
        if ($request->getSuspensionReason()) {
            $sspnsnRsn = $dom->createElement('SspnsnRsn');
            $undrlygSspnsnDtls->appendChild($sspnsnRsn);

            $rsn = $dom->createElement('Rsn');
            $sspnsnRsn->appendChild($rsn);
            $rsn->appendChild($dom->createElement('Prtry', $request->getSuspensionReason()));
        }
    }
}
