<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain017Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\Type017\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type017\MandateCopyRequest;
use DOMDocument;
use DOMElement;

/**
 * Generator für pain.017 (Mandate Copy Request) XML.
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain017Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.017.001.04';

    /**
     * Generiert pain.017 XML aus einem Document.
     */
    public function generate(Document $document): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document Root
        $root = $dom->createElementNS(self::NAMESPACE, 'Document');
        $dom->appendChild($root);

        // MndtCpyReq
        $request = $dom->createElement('MndtCpyReq');
        $root->appendChild($request);

        // GrpHdr
        $this->appendGroupHeader($dom, $request, $document);

        // UndrlygCpyReqDtls
        foreach ($document->getCopyRequests() as $copyRequest) {
            $this->appendCopyRequest($dom, $request, $copyRequest);
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

    private function appendCopyRequest(DOMDocument $dom, DOMElement $parent, MandateCopyRequest $copyRequest): void {
        $undrlygCpyReqDtls = $dom->createElement('UndrlygCpyReqDtls');
        $parent->appendChild($undrlygCpyReqDtls);

        // OrgnlMndt
        $orgnlMndt = $dom->createElement('OrgnlMndt');
        $undrlygCpyReqDtls->appendChild($orgnlMndt);

        $orgnlMndt->appendChild($dom->createElement('MndtId', $copyRequest->getMandateId()));

        if ($copyRequest->getCreditorSchemeId()) {
            $cdtrSchmeId = $dom->createElement('CdtrSchmeId');
            $orgnlMndt->appendChild($cdtrSchmeId);

            $id = $dom->createElement('Id');
            $cdtrSchmeId->appendChild($id);

            $prvtId = $dom->createElement('PrvtId');
            $id->appendChild($prvtId);

            $othr = $dom->createElement('Othr');
            $prvtId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', $copyRequest->getCreditorSchemeId()));
        }

        // InclElctrncSgntr
        if ($copyRequest->includeElectronicSignature() !== null) {
            $undrlygCpyReqDtls->appendChild(
                $dom->createElement('InclElctrncSgntr', $copyRequest->includeElectronicSignature() ? 'true' : 'false')
            );
        }
    }
}
