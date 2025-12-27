<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type54;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtDocumentAbstract;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Helper\Data\BankHelper;
use DOMDocument;

/**
 * CAMT.054 Document (Bank to Customer Debit Credit Notification).
 * 
 * Repräsentiert einen Soll/Haben-Avis (Einzelumsatzbenachrichtigung) gemäß
 * ISO 20022 camt.054.001.02/08 Standard.
 * 
 * Verwendet <BkToCstmrDbtCdtNtfctn> als Root und <Ntfctn> für Notifications.
 * 
 * Anders als CAMT.052/053 enthält CAMT.054 normalerweise keine Salden,
 * sondern nur einzelne Buchungsbenachrichtigungen.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt054
 */
class Document extends CamtDocumentAbstract {
    /** @var Transaction[] */
    protected array $entries = [];

    public function getCamtType(): CamtType {
        return CamtType::CAMT054;
    }

    public function addEntry(Transaction $entry): void {
        $this->entries[] = $entry;
    }

    /**
     * @return Transaction[]
     */
    public function getEntries(): array {
        return $this->entries;
    }

    public function toXml(CamtVersion $version = CamtVersion::V02): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $namespace = $version->getNamespace($this->getCamtType());
        $root = $dom->createElementNS($namespace, 'Document');
        $dom->appendChild($root);

        $bkToCstmrDbtCdtNtfctn = $dom->createElement('BkToCstmrDbtCdtNtfctn');
        $root->appendChild($bkToCstmrDbtCdtNtfctn);

        // GrpHdr
        $grpHdr = $dom->createElement('GrpHdr');
        $bkToCstmrDbtCdtNtfctn->appendChild($grpHdr);

        $msgId = $this->messageId ?? 'CAMT054' . $this->creationDateTime->format('YmdHis');
        $grpHdr->appendChild($dom->createElement('MsgId', htmlspecialchars($msgId)));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s.vP')));

        // Ntfctn (Notification)
        $ntfctn = $dom->createElement('Ntfctn');
        $bkToCstmrDbtCdtNtfctn->appendChild($ntfctn);

        $ntfctn->appendChild($dom->createElement('Id', htmlspecialchars($this->id)));

        // Acct
        $acct = $dom->createElement('Acct');
        $ntfctn->appendChild($acct);

        $acctId = $dom->createElement('Id');
        $acct->appendChild($acctId);

        // IBAN oder andere ID - mit Logging bei ungültiger IBAN
        if (BankHelper::shouldFormatAsIBAN($this->accountIdentifier)) {
            $acctId->appendChild($dom->createElement('IBAN', htmlspecialchars($this->accountIdentifier)));
        } else {
            $othr = $dom->createElement('Othr');
            $acctId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', htmlspecialchars($this->accountIdentifier)));
        }

        if ($this->accountOwner !== null) {
            $ownr = $dom->createElement('Ownr');
            $acct->appendChild($ownr);
            $orgId = $dom->createElement('Id');
            $ownr->appendChild($orgId);
            $orgIdInner = $dom->createElement('OrgId');
            $orgId->appendChild($orgIdInner);
            $orgIdInner->appendChild($dom->createElement('AnyBIC', htmlspecialchars($this->accountOwner)));
        }

        // Entries
        foreach ($this->entries as $entry) {
            $ntfctn->appendChild($this->createEntryElement($dom, $entry));
        }

        return $dom->saveXML();
    }

    private function createEntryElement(DOMDocument $dom, Transaction $entry): \DOMElement {
        $ntry = $dom->createElement('Ntry');

        if ($entry->getEntryReference() !== null) {
            $ntry->appendChild($dom->createElement('NtryRef', htmlspecialchars($entry->getEntryReference())));
        }

        $amt = $dom->createElement('Amt', number_format($entry->getAmount(), 2, '.', ''));
        $amt->setAttribute('Ccy', $entry->getCurrency()->value);
        $ntry->appendChild($amt);

        $ntry->appendChild($dom->createElement('CdtDbtInd', $entry->isCredit() ? 'CRDT' : 'DBIT'));

        if ($entry->isReversal()) {
            $ntry->appendChild($dom->createElement('RvslInd', 'true'));
        }

        $sts = $dom->createElement('Sts');
        $ntry->appendChild($sts);
        $sts->appendChild($dom->createElement('Cd', $entry->getStatus() ?? 'BOOK'));

        $bookgDt = $dom->createElement('BookgDt');
        $ntry->appendChild($bookgDt);
        // CAMT.054 verwendet typischerweise DtTm statt Dt
        $bookgDt->appendChild($dom->createElement('DtTm', $entry->getBookingDate()->format('Y-m-d\TH:i:s.vP')));

        if ($entry->getValutaDate() !== null) {
            $valDt = $dom->createElement('ValDt');
            $ntry->appendChild($valDt);
            $valDt->appendChild($dom->createElement('Dt', $entry->getValutaDate()->format('Y-m-d')));
        }

        // BkTxCd
        if ($entry->getBankTransactionCode() !== null) {
            $bkTxCd = $dom->createElement('BkTxCd');
            $ntry->appendChild($bkTxCd);
            $prtry = $dom->createElement('Prtry');
            $bkTxCd->appendChild($prtry);
            $prtry->appendChild($dom->createElement('Cd', htmlspecialchars($entry->getBankTransactionCode())));
        }

        // Entry Details
        $ntryDtls = $dom->createElement('NtryDtls');
        $ntry->appendChild($ntryDtls);

        $txDtls = $dom->createElement('TxDtls');
        $ntryDtls->appendChild($txDtls);

        // Refs
        if ($entry->getInstructionId() !== null || $entry->getEndToEndId() !== null) {
            $refs = $dom->createElement('Refs');
            $txDtls->appendChild($refs);

            if ($entry->getInstructionId() !== null) {
                $refs->appendChild($dom->createElement('InstrId', htmlspecialchars($entry->getInstructionId())));
            }
            if ($entry->getEndToEndId() !== null) {
                $refs->appendChild($dom->createElement('EndToEndId', htmlspecialchars($entry->getEndToEndId())));
            }
        }

        // Amount in TxDtls
        $txAmt = $dom->createElement('Amt', number_format($entry->getAmount(), 2, '.', ''));
        $txAmt->setAttribute('Ccy', $entry->getCurrency()->value);
        $txDtls->appendChild($txAmt);

        // Related Agents
        if ($entry->getInstructingAgentBic() !== null || $entry->getDebtorAgentBic() !== null) {
            $rltdAgts = $dom->createElement('RltdAgts');
            $txDtls->appendChild($rltdAgts);

            if ($entry->getInstructingAgentBic() !== null) {
                $instgAgt = $dom->createElement('InstgAgt');
                $rltdAgts->appendChild($instgAgt);
                $finInstnId = $dom->createElement('FinInstnId');
                $instgAgt->appendChild($finInstnId);
                $finInstnId->appendChild($dom->createElement('BICFI', htmlspecialchars($entry->getInstructingAgentBic())));
            }

            if ($entry->getDebtorAgentBic() !== null) {
                $dbtrAgt = $dom->createElement('DbtrAgt');
                $rltdAgts->appendChild($dbtrAgt);
                $finInstnId = $dom->createElement('FinInstnId');
                $dbtrAgt->appendChild($finInstnId);
                $finInstnId->appendChild($dom->createElement('BICFI', htmlspecialchars($entry->getDebtorAgentBic())));
            }
        }

        // Remittance Info
        if ($entry->getRemittanceInfo() !== null) {
            $rmtInf = $dom->createElement('RmtInf');
            $txDtls->appendChild($rmtInf);
            $rmtInf->appendChild($dom->createElement('Ustrd', htmlspecialchars($entry->getRemittanceInfo())));
        }

        return $ntry;
    }

    /**
     * Gibt das Dokument als XML-String zurück.
     */
    public function __toString(): string {
        return $this->toXml();
    }
}