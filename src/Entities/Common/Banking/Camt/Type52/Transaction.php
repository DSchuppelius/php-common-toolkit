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

namespace CommonToolkit\Entities\Common\Banking\Camt\Type52;

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
 * CAMT.052 Transaction Entry.
 * 
 * Repräsentiert einen einzelnen Buchungseintrag (Ntry) im
 * untertägigen Kontobericht.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt052
 */
final class Transaction extends CamtTransactionAbstract {
    private ?string $purpose;
    private ?TransactionPurpose $purposeCode;
    private ?string $additionalInfo;
    private ?string $bankTransactionCode;
    private ?TransactionDomain $domainCode;
    private ?TransactionFamily $familyCode;
    private ?TransactionSubFamily $subFamilyCode;
    private ?ReturnReason $returnReason;

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
        ?string $purpose = null,
        TransactionPurpose|string|null $purposeCode = null,
        ?string $additionalInfo = null,
        ?string $bankTransactionCode = null,
        TransactionDomain|string|null $domainCode = null,
        TransactionFamily|string|null $familyCode = null,
        TransactionSubFamily|string|null $subFamilyCode = null,
        ReturnReason|string|null $returnReason = null
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

        $this->purpose = $purpose;
        $this->purposeCode = $purposeCode instanceof TransactionPurpose ? $purposeCode : TransactionPurpose::tryFrom($purposeCode ?? '');
        $this->additionalInfo = $additionalInfo;
        $this->bankTransactionCode = $bankTransactionCode;
        $this->domainCode = $domainCode instanceof TransactionDomain ? $domainCode : TransactionDomain::tryFrom($domainCode ?? '');
        $this->familyCode = $familyCode instanceof TransactionFamily ? $familyCode : TransactionFamily::tryFrom($familyCode ?? '');
        $this->subFamilyCode = $subFamilyCode instanceof TransactionSubFamily ? $subFamilyCode : TransactionSubFamily::tryFrom($subFamilyCode ?? '');
        $this->returnReason = $returnReason instanceof ReturnReason ? $returnReason : ReturnReason::tryFrom($returnReason ?? '');
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getPurposeCode(): ?TransactionPurpose {
        return $this->purposeCode;
    }

    public function getAdditionalInfo(): ?string {
        return $this->additionalInfo;
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

    /**
     * Erstellt eine zusammenfassende Beschreibung der Transaktion.
     */
    public function getSummary(): string {
        $parts = [];

        $parts[] = $this->bookingDate->format('d.m.Y');
        $parts[] = ($this->isCredit() ? '+' : '-') . number_format($this->amount, 2, ',', '.') . ' ' . $this->currency->value;

        if ($this->purpose !== null) {
            $parts[] = $this->purpose;
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