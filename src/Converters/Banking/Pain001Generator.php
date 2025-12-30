<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain001Generator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PostalAddress;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\CreditTransferTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\PaymentInstruction;
use DOMDocument;
use DOMElement;

/**
 * Generiert pain.001 XML-Dokumente aus Entity-Objekten.
 * 
 * Unterstützt pain.001.001.12 (ISO 20022 2024).
 * 
 * @package CommonToolkit\Converters\Banking
 */
class Pain001Generator {
    private const NAMESPACE = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.12';
    private const XSI_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    private DOMDocument $dom;
    private string $namespace;

    public function __construct(string $namespace = self::NAMESPACE) {
        $this->namespace = $namespace;
    }

    /**
     * Generiert XML aus einem pain.001 Dokument.
     * 
     * @param Document $document Das zu konvertierende Dokument
     * @return string Das generierte XML
     */
    public function generate(Document $document): string {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        // Root-Element: Document
        $documentElement = $this->dom->createElementNS($this->namespace, 'Document');
        $documentElement->setAttributeNS(
            self::XSI_NAMESPACE,
            'xsi:schemaLocation',
            $this->namespace . ' pain.001.001.12.xsd'
        );
        $this->dom->appendChild($documentElement);

        // CstmrCdtTrfInitn
        $cstmrCdtTrfInitn = $this->createElement('CstmrCdtTrfInitn');
        $documentElement->appendChild($cstmrCdtTrfInitn);

        // GroupHeader
        $this->appendGroupHeader($cstmrCdtTrfInitn, $document->getGroupHeader());

        // PaymentInstructions
        foreach ($document->getPaymentInstructions() as $paymentInstruction) {
            $this->appendPaymentInstruction($cstmrCdtTrfInitn, $paymentInstruction);
        }

        return $this->dom->saveXML();
    }

    /**
     * Fügt den GroupHeader hinzu.
     */
    private function appendGroupHeader(DOMElement $parent, GroupHeader $groupHeader): void {
        $grpHdr = $this->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        // MsgId
        $grpHdr->appendChild($this->createElement('MsgId', $groupHeader->getMessageId()));

        // CreDtTm
        $grpHdr->appendChild($this->createElement(
            'CreDtTm',
            $groupHeader->getCreationDateTime()->format('Y-m-d\TH:i:s')
        ));

        // NbOfTxs
        $grpHdr->appendChild($this->createElement(
            'NbOfTxs',
            (string) $groupHeader->getNumberOfTransactions()
        ));

        // CtrlSum (optional)
        if ($groupHeader->getControlSum() !== null) {
            $grpHdr->appendChild($this->createElement(
                'CtrlSum',
                number_format($groupHeader->getControlSum(), 2, '.', '')
            ));
        }

        // InitgPty
        $this->appendPartyIdentification($grpHdr, 'InitgPty', $groupHeader->getInitiatingParty());

        // FwdgAgt (optional)
        if ($groupHeader->getForwardingAgent() !== null) {
            $this->appendFinancialInstitution($grpHdr, 'FwdgAgt', $groupHeader->getForwardingAgent());
        }
    }

    /**
     * Fügt eine PaymentInstruction hinzu.
     */
    private function appendPaymentInstruction(DOMElement $parent, PaymentInstruction $instruction): void {
        $pmtInf = $this->createElement('PmtInf');
        $parent->appendChild($pmtInf);

        // PmtInfId
        $pmtInf->appendChild($this->createElement('PmtInfId', $instruction->getPaymentInstructionId()));

        // PmtMtd
        $pmtInf->appendChild($this->createElement('PmtMtd', $instruction->getPaymentMethod()->value));

        // NbOfTxs (Payment Instruction level)
        $pmtInf->appendChild($this->createElement('NbOfTxs', (string) $instruction->countTransactions()));

        // CtrlSum
        $pmtInf->appendChild($this->createElement(
            'CtrlSum',
            number_format($instruction->calculateControlSum(), 2, '.', '')
        ));

        // ReqdExctnDt
        $reqdExctnDt = $this->createElement('ReqdExctnDt');
        $pmtInf->appendChild($reqdExctnDt);
        $reqdExctnDt->appendChild($this->createElement('Dt', $instruction->getRequestedExecutionDate()->format('Y-m-d')));

        // Dbtr
        $this->appendPartyIdentification($pmtInf, 'Dbtr', $instruction->getDebtor());

        // DbtrAcct
        $this->appendAccountIdentification($pmtInf, 'DbtrAcct', $instruction->getDebtorAccount());

        // DbtrAgt
        $debtorAgent = $instruction->getDebtorAgent();
        if ($debtorAgent !== null) {
            $this->appendFinancialInstitution($pmtInf, 'DbtrAgt', $debtorAgent);
        }

        // ChrgBr
        $chargeBearer = $instruction->getChargeBearer();
        if ($chargeBearer !== null) {
            $pmtInf->appendChild($this->createElement('ChrgBr', $chargeBearer->value));
        }

        // CdtTrfTxInf
        foreach ($instruction->getTransactions() as $transaction) {
            $this->appendCreditTransferTransaction($pmtInf, $transaction);
        }
    }

    /**
     * Fügt eine CreditTransferTransaction hinzu.
     */
    private function appendCreditTransferTransaction(DOMElement $parent, CreditTransferTransaction $transaction): void {
        $cdtTrfTxInf = $this->createElement('CdtTrfTxInf');
        $parent->appendChild($cdtTrfTxInf);

        // PmtId
        $this->appendPaymentIdentification($cdtTrfTxInf, $transaction->getPaymentId());

        // Amt
        $amt = $this->createElement('Amt');
        $cdtTrfTxInf->appendChild($amt);

        $instdAmt = $this->createElement('InstdAmt', number_format($transaction->getAmount(), 2, '.', ''));
        $instdAmt->setAttribute('Ccy', $transaction->getCurrency()->value);
        $amt->appendChild($instdAmt);

        // CdtrAgt (optional)
        if ($transaction->getCreditorAgent() !== null) {
            $this->appendFinancialInstitution($cdtTrfTxInf, 'CdtrAgt', $transaction->getCreditorAgent());
        }

        // Cdtr
        $this->appendPartyIdentification($cdtTrfTxInf, 'Cdtr', $transaction->getCreditor());

        // CdtrAcct
        if ($transaction->getCreditorAccount() !== null) {
            $this->appendAccountIdentification($cdtTrfTxInf, 'CdtrAcct', $transaction->getCreditorAccount());
        }

        // RmtInf
        if ($transaction->getRemittanceInformation() !== null) {
            $this->appendRemittanceInformation($cdtTrfTxInf, $transaction->getRemittanceInformation());
        }
    }

    /**
     * Fügt eine PartyIdentification hinzu.
     */
    private function appendPartyIdentification(DOMElement $parent, string $elementName, PartyIdentification $party): void {
        $element = $this->createElement($elementName);
        $parent->appendChild($element);

        // Nm (Name)
        if ($party->getName() !== null) {
            $element->appendChild($this->createElement('Nm', $party->getName()));
        }

        // PstlAdr (optional)
        if ($party->getPostalAddress() !== null) {
            $this->appendPostalAddress($element, $party->getPostalAddress());
        }

        // Id (BIC/LEI)
        if ($party->getBic() !== null || $party->getLei() !== null) {
            $id = $this->createElement('Id');
            $element->appendChild($id);

            $orgId = $this->createElement('OrgId');
            $id->appendChild($orgId);

            if ($party->getBic() !== null) {
                $orgId->appendChild($this->createElement('AnyBIC', $party->getBic()));
            }
            if ($party->getLei() !== null) {
                $orgId->appendChild($this->createElement('LEI', $party->getLei()));
            }
        }

        // CtryOfRes
        if ($party->getCountryOfResidence() !== null) {
            $element->appendChild($this->createElement('CtryOfRes', $party->getCountryOfResidence()->value));
        }
    }

    /**
     * Fügt eine PostalAddress hinzu.
     */
    private function appendPostalAddress(DOMElement $parent, PostalAddress $address): void {
        $pstlAdr = $this->createElement('PstlAdr');
        $parent->appendChild($pstlAdr);

        if ($address->getStreetName() !== null) {
            $pstlAdr->appendChild($this->createElement('StrtNm', $address->getStreetName()));
        }
        if ($address->getBuildingNumber() !== null) {
            $pstlAdr->appendChild($this->createElement('BldgNb', $address->getBuildingNumber()));
        }
        if ($address->getPostCode() !== null) {
            $pstlAdr->appendChild($this->createElement('PstCd', $address->getPostCode()));
        }
        if ($address->getTownName() !== null) {
            $pstlAdr->appendChild($this->createElement('TwnNm', $address->getTownName()));
        }
        if ($address->getCountry() !== null) {
            $pstlAdr->appendChild($this->createElement('Ctry', $address->getCountry()->value));
        }

        foreach ($address->getAddressLines() as $line) {
            $pstlAdr->appendChild($this->createElement('AdrLine', $line));
        }
    }

    /**
     * Fügt eine AccountIdentification hinzu.
     */
    private function appendAccountIdentification(DOMElement $parent, string $elementName, AccountIdentification $account): void {
        $element = $this->createElement($elementName);
        $parent->appendChild($element);

        $id = $this->createElement('Id');
        $element->appendChild($id);

        if ($account->getIban() !== null) {
            $id->appendChild($this->createElement('IBAN', $account->getIban()));
        } elseif ($account->getOther() !== null) {
            $othr = $this->createElement('Othr');
            $id->appendChild($othr);
            $othr->appendChild($this->createElement('Id', $account->getOther()));
        }

        if ($account->getCurrency() !== null) {
            $element->appendChild($this->createElement('Ccy', $account->getCurrency()->value));
        }
    }

    /**
     * Fügt eine FinancialInstitution hinzu.
     */
    private function appendFinancialInstitution(DOMElement $parent, string $elementName, FinancialInstitution $institution): void {
        $element = $this->createElement($elementName);
        $parent->appendChild($element);

        $finInstnId = $this->createElement('FinInstnId');
        $element->appendChild($finInstnId);

        if ($institution->getBic() !== null) {
            $finInstnId->appendChild($this->createElement('BICFI', $institution->getBic()));
        }
        if ($institution->getName() !== null) {
            $finInstnId->appendChild($this->createElement('Nm', $institution->getName()));
        }
        if ($institution->getLei() !== null) {
            $finInstnId->appendChild($this->createElement('LEI', $institution->getLei()));
        }
        if ($institution->getMemberId() !== null) {
            $clrSysMmbId = $this->createElement('ClrSysMmbId');
            $finInstnId->appendChild($clrSysMmbId);
            $clrSysMmbId->appendChild($this->createElement('MmbId', $institution->getMemberId()));
        }
    }

    /**
     * Fügt eine PaymentIdentification hinzu.
     */
    private function appendPaymentIdentification(DOMElement $parent, PaymentIdentification $identification): void {
        $pmtId = $this->createElement('PmtId');
        $parent->appendChild($pmtId);

        if ($identification->getInstructionId() !== null) {
            $pmtId->appendChild($this->createElement('InstrId', $identification->getInstructionId()));
        }

        $pmtId->appendChild($this->createElement('EndToEndId', $identification->getEndToEndId()));

        if ($identification->getUetr() !== null) {
            $pmtId->appendChild($this->createElement('UETR', $identification->getUetr()));
        }
    }

    /**
     * Fügt RemittanceInformation hinzu.
     */
    private function appendRemittanceInformation(DOMElement $parent, RemittanceInformation $info): void {
        $rmtInf = $this->createElement('RmtInf');
        $parent->appendChild($rmtInf);

        // Unstructured (mehrere Zeilen möglich)
        foreach ($info->getUnstructured() as $line) {
            $rmtInf->appendChild($this->createElement('Ustrd', $line));
        }

        if ($info->getCreditorReference() !== null) {
            $strd = $this->createElement('Strd');
            $rmtInf->appendChild($strd);

            $cdtrRefInf = $this->createElement('CdtrRefInf');
            $strd->appendChild($cdtrRefInf);

            $cdtrRefInf->appendChild($this->createElement('Ref', $info->getCreditorReference()));
        }
    }

    /**
     * Erstellt ein DOM-Element mit optionalem Text.
     */
    private function createElement(string $name, ?string $value = null): DOMElement {
        $element = $this->dom->createElementNS($this->namespace, $name);
        if ($value !== null) {
            $element->appendChild($this->dom->createTextNode($value));
        }
        return $element;
    }
}