<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Transaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type54;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtTransactionAbstract;
use CommonToolkit\Enums\Common\Banking\Camt\ReturnReason;
use CommonToolkit\Enums\Common\Banking\Camt\TransactionDomain;
use CommonToolkit\Enums\Common\Banking\Camt\TransactionFamily;
use CommonToolkit\Enums\Common\Banking\Camt\TransactionPurpose;
use CommonToolkit\Enums\Common\Banking\Camt\TransactionSubFamily;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * CAMT.054 Transaction Entry.
 * 
 * Repräsentiert einen einzelnen Buchungseintrag (Ntry) in der
 * Soll/Haben-Benachrichtigung.
 * 
 * CAMT.054 enthält typischerweise mehr Details zu Agents und
 * Referenzen als CAMT.052/053.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt054
 */
final class Transaction extends CamtTransactionAbstract {
    private ?string $instructionId;
    private ?string $endToEndId;
    private ?string $remittanceInfo;
    private ?TransactionPurpose $purposeCode;
    private ?string $bankTransactionCode;
    private ?TransactionDomain $domainCode;
    private ?TransactionFamily $familyCode;
    private ?TransactionSubFamily $subFamilyCode;
    private ?ReturnReason $returnReason;
    private ?string $localInstrumentCode;
    private ?string $instructingAgentBic;
    private ?string $instructedAgentBic;
    private ?string $debtorAgentBic;
    private ?string $creditorAgentBic;

    public function __construct(
        DateTimeImmutable $bookingDate,
        ?DateTimeImmutable $valutaDate,
        float $amount,
        CurrencyCode $currency,
        CreditDebit $creditDebit,
        ?string $entryReference = null,
        ?string $accountServicerReference = null,
        ?string $status = 'BOOK',
        bool $isReversal = false,
        ?string $instructionId = null,
        ?string $endToEndId = null,
        ?string $remittanceInfo = null,
        TransactionPurpose|string|null $purposeCode = null,
        ?string $bankTransactionCode = null,
        TransactionDomain|string|null $domainCode = null,
        TransactionFamily|string|null $familyCode = null,
        TransactionSubFamily|string|null $subFamilyCode = null,
        ReturnReason|string|null $returnReason = null,
        ?string $localInstrumentCode = null,
        ?string $instructingAgentBic = null,
        ?string $instructedAgentBic = null,
        ?string $debtorAgentBic = null,
        ?string $creditorAgentBic = null
    ) {
        parent::__construct(
            $bookingDate,
            $valutaDate,
            $amount,
            $currency,
            $creditDebit,
            $entryReference,
            $accountServicerReference,
            $status,
            $isReversal
        );

        $this->instructionId = $instructionId;
        $this->endToEndId = $endToEndId;
        $this->remittanceInfo = $remittanceInfo;
        $this->purposeCode = $purposeCode instanceof TransactionPurpose ? $purposeCode : TransactionPurpose::tryFrom($purposeCode ?? '');
        $this->bankTransactionCode = $bankTransactionCode;
        $this->domainCode = $domainCode instanceof TransactionDomain ? $domainCode : TransactionDomain::tryFrom($domainCode ?? '');
        $this->familyCode = $familyCode instanceof TransactionFamily ? $familyCode : TransactionFamily::tryFrom($familyCode ?? '');
        $this->subFamilyCode = $subFamilyCode instanceof TransactionSubFamily ? $subFamilyCode : TransactionSubFamily::tryFrom($subFamilyCode ?? '');
        $this->returnReason = $returnReason instanceof ReturnReason ? $returnReason : ReturnReason::tryFrom($returnReason ?? '');
        $this->localInstrumentCode = $localInstrumentCode;
        $this->instructingAgentBic = $instructingAgentBic;
        $this->instructedAgentBic = $instructedAgentBic;
        $this->debtorAgentBic = $debtorAgentBic;
        $this->creditorAgentBic = $creditorAgentBic;
    }

    public function getInstructionId(): ?string {
        return $this->instructionId;
    }

    public function getEndToEndId(): ?string {
        return $this->endToEndId;
    }

    public function getRemittanceInfo(): ?string {
        return $this->remittanceInfo;
    }

    public function getPurposeCode(): ?TransactionPurpose {
        return $this->purposeCode;
    }

    public function getReturnReason(): ?ReturnReason {
        return $this->returnReason;
    }

    public function getBankTransactionCode(): ?string {
        return $this->bankTransactionCode;
    }

    public function getDomainCode(): ?TransactionDomain {
        return $this->domainCode;
    }

    public function getFamilyCode(): ?TransactionFamily {
        return $this->familyCode;
    }

    public function getSubFamilyCode(): ?TransactionSubFamily {
        return $this->subFamilyCode;
    }

    /**
     * Gibt den vollständigen Transaktionscode zurück (Domain/Family/SubFamily).
     */
    public function getFullTransactionCode(): ?string {
        if ($this->domainCode === null) {
            return $this->bankTransactionCode;
        }

        $code = $this->domainCode->value;
        if ($this->familyCode !== null) {
            $code .= '/' . $this->familyCode->value;
            if ($this->subFamilyCode !== null) {
                $code .= '/' . $this->subFamilyCode->value;
            }
        }

        return $code;
    }

    public function getLocalInstrumentCode(): ?string {
        return $this->localInstrumentCode;
    }

    public function getInstructingAgentBic(): ?string {
        return $this->instructingAgentBic;
    }

    public function getInstructedAgentBic(): ?string {
        return $this->instructedAgentBic;
    }

    public function getDebtorAgentBic(): ?string {
        return $this->debtorAgentBic;
    }

    public function getCreditorAgentBic(): ?string {
        return $this->creditorAgentBic;
    }

    /**
     * Erstellt eine zusammenfassende Beschreibung der Transaktion.
     */
    public function getSummary(): string {
        $parts = [];

        $parts[] = $this->bookingDate->format('d.m.Y H:i:s');
        $parts[] = ($this->isCredit() ? '+' : '-') . number_format($this->amount, 2, ',', '.') . ' ' . $this->currency->value;

        if ($this->endToEndId !== null) {
            $parts[] = 'E2E: ' . $this->endToEndId;
        }

        if ($this->remittanceInfo !== null) {
            $parts[] = $this->remittanceInfo;
        }

        return implode(' | ', $parts);
    }

    /**
     * Gibt eine String-Repräsentation der Transaktion zurück.
     */
    public function __toString(): string {
        return $this->getSummary();
    }
}
