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

namespace CommonToolkit\Entities\Common\Banking\Camt\Type52;

use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtDocumentAbstract;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;

/**
 * CAMT.052 Document (Bank to Customer Account Report).
 * 
 * Repräsentiert eine untertägige Kontobewegungsinformation gemäß
 * ISO 20022 camt.052.001.02/08 Standard.
 * 
 * Verwendet <BkToCstmrAcctRpt> als Root und <Rpt> für Reports.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt052
 */
class Document extends CamtDocumentAbstract {
    private ?Balance $openingBalance = null;
    private ?Balance $closingBalance = null;

    /** @var Transaction[] */
    protected array $entries = [];

    public function __construct(
        string $id,
        DateTimeImmutable|string $creationDateTime,
        string $accountIdentifier,
        CurrencyCode|string $currency,
        ?string $accountOwner = null,
        ?string $servicerBic = null,
        ?string $messageId = null,
        ?string $sequenceNumber = null,
        ?Balance $openingBalance = null,
        ?Balance $closingBalance = null
    ) {
        parent::__construct(
            $id,
            $creationDateTime,
            $accountIdentifier,
            $currency,
            $accountOwner,
            $servicerBic,
            $messageId,
            $sequenceNumber
        );

        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
    }

    public function getCamtType(): CamtType {
        return CamtType::CAMT052;
    }

    public function getOpeningBalance(): ?Balance {
        return $this->openingBalance;
    }

    public function getClosingBalance(): ?Balance {
        return $this->closingBalance;
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

    public function withOpeningBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->openingBalance = $balance;
        return $clone;
    }

    public function withClosingBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->closingBalance = $balance;
        return $clone;
    }

    public function toXml(CamtVersion $version = CamtVersion::V02): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $namespace = $version->getNamespace($this->getCamtType());
        $root = $dom->createElementNS($namespace, 'Document');
        $dom->appendChild($root);

        $bkToCstmrAcctRpt = $dom->createElement('BkToCstmrAcctRpt');
        $root->appendChild($bkToCstmrAcctRpt);

        // GrpHdr
        $grpHdr = $dom->createElement('GrpHdr');
        $bkToCstmrAcctRpt->appendChild($grpHdr);

        $msgId = $this->messageId ?? 'CAMT052' . $this->creationDateTime->format('YmdHis');
        $grpHdr->appendChild($dom->createElement('MsgId', htmlspecialchars($msgId)));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s.vP')));

        // Rpt (Report)
        $rpt = $dom->createElement('Rpt');
        $bkToCstmrAcctRpt->appendChild($rpt);

        $rpt->appendChild($dom->createElement('Id', htmlspecialchars($this->id)));

        if ($this->sequenceNumber !== null) {
            $rpt->appendChild($dom->createElement('ElctrncSeqNb', htmlspecialchars($this->sequenceNumber)));
        }

        $rpt->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s.vP')));

        // Acct
        $acct = $dom->createElement('Acct');
        $rpt->appendChild($acct);

        $acctId = $dom->createElement('Id');
        $acct->appendChild($acctId);

        // IBAN oder andere ID
        if (preg_match('/^[A-Z]{2}\d{2}/', $this->accountIdentifier)) {
            $acctId->appendChild($dom->createElement('IBAN', htmlspecialchars($this->accountIdentifier)));
        } else {
            $othr = $dom->createElement('Othr');
            $acctId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', htmlspecialchars($this->accountIdentifier)));
        }

        $acct->appendChild($dom->createElement('Ccy', $this->currency->value));

        if ($this->accountOwner !== null) {
            $ownr = $dom->createElement('Ownr');
            $acct->appendChild($ownr);
            $ownr->appendChild($dom->createElement('Nm', htmlspecialchars($this->accountOwner)));
        }

        if ($this->servicerBic !== null) {
            $svcr = $dom->createElement('Svcr');
            $acct->appendChild($svcr);
            $finInstnId = $dom->createElement('FinInstnId');
            $svcr->appendChild($finInstnId);
            $finInstnId->appendChild($dom->createElement('BICFI', htmlspecialchars($this->servicerBic)));
        }

        // Balances
        if ($this->openingBalance !== null) {
            $rpt->appendChild($this->createBalanceElement($dom, $this->openingBalance));
        }

        if ($this->closingBalance !== null) {
            $rpt->appendChild($this->createBalanceElement($dom, $this->closingBalance));
        }

        // Entries
        foreach ($this->entries as $entry) {
            $rpt->appendChild($this->createEntryElement($dom, $entry));
        }

        return $dom->saveXML();
    }

    private function createBalanceElement(DOMDocument $dom, Balance $balance): \DOMElement {
        $bal = $dom->createElement('Bal');

        $tp = $dom->createElement('Tp');
        $bal->appendChild($tp);
        $cdOrPrtry = $dom->createElement('CdOrPrtry');
        $tp->appendChild($cdOrPrtry);
        $cdOrPrtry->appendChild($dom->createElement('Cd', $balance->getType()));

        $amt = $dom->createElement('Amt', number_format($balance->getAmount(), 2, '.', ''));
        $amt->setAttribute('Ccy', $balance->getCurrency()->value);
        $bal->appendChild($amt);

        $bal->appendChild($dom->createElement('CdtDbtInd', $balance->isCredit() ? 'CRDT' : 'DBIT'));

        $dt = $dom->createElement('Dt');
        $bal->appendChild($dt);
        $dt->appendChild($dom->createElement('Dt', $balance->getDate()->format('Y-m-d')));

        return $bal;
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
        $bookgDt->appendChild($dom->createElement('Dt', $entry->getBookingDate()->format('Y-m-d')));

        if ($entry->getValutaDate() !== null) {
            $valDt = $dom->createElement('ValDt');
            $ntry->appendChild($valDt);
            $valDt->appendChild($dom->createElement('Dt', $entry->getValutaDate()->format('Y-m-d')));
        }

        if ($entry->getAccountServicerReference() !== null) {
            $ntry->appendChild($dom->createElement('AcctSvcrRef', htmlspecialchars($entry->getAccountServicerReference())));
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
        if ($entry->getPurpose() !== null || $entry->getAdditionalInfo() !== null) {
            $ntryDtls = $dom->createElement('NtryDtls');
            $ntry->appendChild($ntryDtls);

            $txDtls = $dom->createElement('TxDtls');
            $ntryDtls->appendChild($txDtls);

            if ($entry->getPurpose() !== null) {
                $purp = $dom->createElement('Purp');
                $txDtls->appendChild($purp);
                $purp->appendChild($dom->createElement('Prtry', htmlspecialchars($entry->getPurpose())));
            }

            if ($entry->getAdditionalInfo() !== null) {
                $txDtls->appendChild($dom->createElement('AddtlTxInf', htmlspecialchars($entry->getAdditionalInfo())));
            }
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
