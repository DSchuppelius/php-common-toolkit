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
    private ?string $additionalInfo;
    private ?string $bankTransactionCode;
    private ?string $domainCode;
    private ?string $familyCode;
    private ?string $subFamilyCode;

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
        ?string $additionalInfo = null,
        ?string $bankTransactionCode = null,
        ?string $domainCode = null,
        ?string $familyCode = null,
        ?string $subFamilyCode = null
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
        $this->additionalInfo = $additionalInfo;
        $this->bankTransactionCode = $bankTransactionCode;
        $this->domainCode = $domainCode;
        $this->familyCode = $familyCode;
        $this->subFamilyCode = $subFamilyCode;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getAdditionalInfo(): ?string {
        return $this->additionalInfo;
    }

    public function getBankTransactionCode(): ?string {
        return $this->bankTransactionCode;
    }

    public function getDomainCode(): ?string {
        return $this->domainCode;
    }

    public function getFamilyCode(): ?string {
        return $this->familyCode;
    }

    public function getSubFamilyCode(): ?string {
        return $this->subFamilyCode;
    }

    /**
     * Gibt den vollständigen Transaktionscode zurück (Domain/Family/SubFamily).
     */
    public function getFullTransactionCode(): ?string {
        if ($this->domainCode === null) {
            return $this->bankTransactionCode;
        }

        $code = $this->domainCode;
        if ($this->familyCode !== null) {
            $code .= '/' . $this->familyCode;
            if ($this->subFamilyCode !== null) {
                $code .= '/' . $this->subFamilyCode;
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
