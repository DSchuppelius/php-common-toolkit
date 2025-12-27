<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type53;

use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtDocumentAbstract;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;

/**
 * CAMT.053 Document (Bank to Customer Statement).
 * 
 * Repräsentiert einen Tagesauszug gemäß ISO 20022 camt.053.001.02/08 Standard.
 * 
 * Verwendet <BkToCstmrStmt> als Root und <Stmt> für Statements.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt053
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
        return CamtType::CAMT053;
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

        $bkToCstmrStmt = $dom->createElement('BkToCstmrStmt');
        $root->appendChild($bkToCstmrStmt);

        // GrpHdr
        $grpHdr = $dom->createElement('GrpHdr');
        $bkToCstmrStmt->appendChild($grpHdr);

        $msgId = $this->messageId ?? 'CAMT053' . $this->creationDateTime->format('YmdHis');
        $grpHdr->appendChild($dom->createElement('MsgId', htmlspecialchars($msgId)));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s.vP')));

        // Stmt (Statement)
        $stmt = $dom->createElement('Stmt');
        $bkToCstmrStmt->appendChild($stmt);

        $stmt->appendChild($dom->createElement('Id', htmlspecialchars($this->id)));

        if ($this->sequenceNumber !== null) {
            $stmt->appendChild($dom->createElement('ElctrncSeqNb', htmlspecialchars($this->sequenceNumber)));
        }

        $stmt->appendChild($dom->createElement('CreDtTm', $this->creationDateTime->format('Y-m-d\TH:i:s.vP')));

        // Acct
        $acct = $dom->createElement('Acct');
        $stmt->appendChild($acct);

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
            $stmt->appendChild($this->createBalanceElement($dom, $this->openingBalance));
        }

        if ($this->closingBalance !== null) {
            $stmt->appendChild($this->createBalanceElement($dom, $this->closingBalance));
        }

        // Entries
        foreach ($this->entries as $entry) {
            $stmt->appendChild($this->createEntryElement($dom, $entry));
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

        // Entry Reference
        if ($entry->getEntryReference() !== null) {
            $ntry->appendChild($dom->createElement('NtryRef', htmlspecialchars($entry->getEntryReference())));
        }

        // Amount
        $amt = $dom->createElement('Amt', number_format($entry->getAmount(), 2, '.', ''));
        $amt->setAttribute('Ccy', $entry->getCurrency()->value);
        $ntry->appendChild($amt);

        // Credit/Debit Indicator
        $ntry->appendChild($dom->createElement('CdtDbtInd', $entry->isCredit() ? 'CRDT' : 'DBIT'));

        // Reversal Indicator
        if ($entry->isReversal()) {
            $ntry->appendChild($dom->createElement('RvslInd', 'true'));
        }

        // Status
        $sts = $dom->createElement('Sts');
        $ntry->appendChild($sts);
        $sts->appendChild($dom->createElement('Cd', $entry->getStatus() ?? 'BOOK'));

        // Booking Date
        $bookgDt = $dom->createElement('BookgDt');
        $ntry->appendChild($bookgDt);
        $bookgDt->appendChild($dom->createElement('Dt', $entry->getBookingDate()->format('Y-m-d')));

        // Value Date
        if ($entry->getValutaDate() !== null) {
            $valDt = $dom->createElement('ValDt');
            $ntry->appendChild($valDt);
            $valDt->appendChild($dom->createElement('Dt', $entry->getValutaDate()->format('Y-m-d')));
        }

        // Account Servicer Reference
        if ($entry->getAccountServicerReference() !== null) {
            $ntry->appendChild($dom->createElement('AcctSvcrRef', htmlspecialchars($entry->getAccountServicerReference())));
        }

        // BkTxCd (Bank Transaction Code)
        if ($entry->getTransactionCode() !== null) {
            $bkTxCd = $dom->createElement('BkTxCd');
            $ntry->appendChild($bkTxCd);
            $prtry = $dom->createElement('Prtry');
            $bkTxCd->appendChild($prtry);
            $prtry->appendChild($dom->createElement('Cd', htmlspecialchars($entry->getTransactionCode())));
        }

        // Entry Details
        $ntryDtls = $dom->createElement('NtryDtls');
        $ntry->appendChild($ntryDtls);

        $txDtls = $dom->createElement('TxDtls');
        $ntryDtls->appendChild($txDtls);

        // References
        $reference = $entry->getReference();
        if ($reference->hasAnyReference()) {
            $refs = $dom->createElement('Refs');
            $txDtls->appendChild($refs);

            if ($reference->getEndToEndId() !== null) {
                $refs->appendChild($dom->createElement('EndToEndId', htmlspecialchars($reference->getEndToEndId())));
            }

            if ($reference->getMandateId() !== null) {
                $refs->appendChild($dom->createElement('MndtId', htmlspecialchars($reference->getMandateId())));
            }

            if ($reference->getCreditorId() !== null) {
                $refs->appendChild($dom->createElement('CdtrId', htmlspecialchars($reference->getCreditorId())));
            }

            if ($reference->getPaymentInformationId() !== null) {
                $refs->appendChild($dom->createElement('PmtInfId', htmlspecialchars($reference->getPaymentInformationId())));
            }

            if ($reference->getInstructionId() !== null) {
                $refs->appendChild($dom->createElement('InstrId', htmlspecialchars($reference->getInstructionId())));
            }
        }

        // Related Parties (Counterparty)
        $counterpartyName = $entry->getCounterpartyName();
        $counterpartyIban = $entry->getCounterpartyIban();
        if ($counterpartyName !== null || $counterpartyIban !== null) {
            $rltdPties = $dom->createElement('RltdPties');
            $txDtls->appendChild($rltdPties);

            $partyElement = $entry->isCredit() ? 'Dbtr' : 'Cdtr';
            $party = $dom->createElement($partyElement);
            $rltdPties->appendChild($party);

            if ($counterpartyName !== null) {
                $party->appendChild($dom->createElement('Nm', htmlspecialchars($counterpartyName)));
            }

            if ($counterpartyIban !== null) {
                $acctElement = $entry->isCredit() ? 'DbtrAcct' : 'CdtrAcct';
                $partyAcct = $dom->createElement($acctElement);
                $rltdPties->appendChild($partyAcct);
                $partyAcctId = $dom->createElement('Id');
                $partyAcct->appendChild($partyAcctId);
                $partyAcctId->appendChild($dom->createElement('IBAN', htmlspecialchars($counterpartyIban)));
            }

            if ($entry->getCounterpartyBic() !== null) {
                $agentElement = $entry->isCredit() ? 'DbtrAgt' : 'CdtrAgt';
                $agt = $dom->createElement($agentElement);
                $rltdPties->appendChild($agt);
                $finInstnId = $dom->createElement('FinInstnId');
                $agt->appendChild($finInstnId);
                $finInstnId->appendChild($dom->createElement('BICFI', htmlspecialchars($entry->getCounterpartyBic())));
            }
        }

        // Remittance Information
        if ($entry->getPurpose() !== null) {
            $rmtInf = $dom->createElement('RmtInf');
            $txDtls->appendChild($rmtInf);
            $rmtInf->appendChild($dom->createElement('Ustrd', htmlspecialchars($entry->getPurpose())));
        }

        // Additional Entry Info
        if ($entry->getAdditionalInfo() !== null) {
            $ntry->appendChild($dom->createElement('AddtlNtryInf', htmlspecialchars($entry->getAdditionalInfo())));
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
