<?php
/*
 * Created on   : Mon Nov 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Transaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type53;

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
 * CAMT.053 Transaction Entry.
 * 
 * Repräsentiert einen einzelnen Buchungseintrag (Ntry) im
 * Tagesauszug (Bank to Customer Statement).
 * 
 * CAMT.053 enthält vollständige Referenzinformationen über das
 * Reference-Objekt für SEPA-Buchungen.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt053
 */
final class Transaction extends CamtTransactionAbstract {
    private Reference $reference;
    private ?string $purpose;
    private ?TransactionPurpose $purposeCode;
    private ?string $additionalInfo;
    private ?string $transactionCode;
    private ?TransactionDomain $domainCode;
    private ?TransactionFamily $familyCode;
    private ?TransactionSubFamily $subFamilyCode;
    private ?ReturnReason $returnReason;
    private ?string $counterpartyName;
    private ?string $counterpartyIban;
    private ?string $counterpartyBic;

    /**
     * @param DateTimeImmutable $bookingDate Buchungsdatum
     * @param DateTimeImmutable|null $valutaDate Valutadatum (Wertstellung)
     * @param float $amount Betrag (immer positiv)
     * @param CurrencyCode $currency Währung
     * @param CreditDebit $creditDebit Soll/Haben-Kennzeichen
     * @param Reference $reference Alle Referenzen der Transaktion
     * @param string|null $entryReference Entry Reference (NtryRef)
     * @param string|null $accountServicerReference Account Servicer Reference
     * @param string|null $status Buchungsstatus (BOOK, PDNG, INFO)
     * @param bool $isReversal Storno-Kennzeichen
     * @param string|null $purpose Verwendungszweck (unstrukturiert)
     * @param TransactionPurpose|string|null $purposeCode ISO 20022 Verwendungszweck-Code
     * @param string|null $additionalInfo Zusätzliche Buchungsinformationen
     * @param string|null $transactionCode Transaktionscode (GVC)
     * @param TransactionDomain|string|null $domainCode ISO 20022 Domain Code
     * @param TransactionFamily|string|null $familyCode ISO 20022 Family Code
     * @param TransactionSubFamily|string|null $subFamilyCode ISO 20022 SubFamily Code
     * @param ReturnReason|string|null $returnReason ISO 20022 Rückbuchungsgrund
     * @param string|null $counterpartyName Name der Gegenseite
     * @param string|null $counterpartyIban IBAN der Gegenseite
     * @param string|null $counterpartyBic BIC der Gegenseite
     */
    public function __construct(
        DateTimeImmutable $bookingDate,
        ?DateTimeImmutable $valutaDate,
        float $amount,
        CurrencyCode $currency,
        CreditDebit $creditDebit,
        Reference $reference,
        ?string $entryReference = null,
        ?string $accountServicerReference = null,
        ?string $status = 'BOOK',
        bool $isReversal = false,
        ?string $purpose = null,
        TransactionPurpose|string|null $purposeCode = null,
        ?string $additionalInfo = null,
        ?string $transactionCode = null,
        TransactionDomain|string|null $domainCode = null,
        TransactionFamily|string|null $familyCode = null,
        TransactionSubFamily|string|null $subFamilyCode = null,
        ReturnReason|string|null $returnReason = null,
        ?string $counterpartyName = null,
        ?string $counterpartyIban = null,
        ?string $counterpartyBic = null
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

        $this->reference = $reference;
        $this->purpose = $purpose;
        $this->purposeCode = $purposeCode instanceof TransactionPurpose ? $purposeCode : TransactionPurpose::tryFrom($purposeCode ?? '');
        $this->additionalInfo = $additionalInfo;
        $this->transactionCode = $transactionCode;
        $this->domainCode = $domainCode instanceof TransactionDomain ? $domainCode : TransactionDomain::tryFrom($domainCode ?? '');
        $this->familyCode = $familyCode instanceof TransactionFamily ? $familyCode : TransactionFamily::tryFrom($familyCode ?? '');
        $this->subFamilyCode = $subFamilyCode instanceof TransactionSubFamily ? $subFamilyCode : TransactionSubFamily::tryFrom($subFamilyCode ?? '');
        $this->returnReason = $returnReason instanceof ReturnReason ? $returnReason : ReturnReason::tryFrom($returnReason ?? '');
        $this->counterpartyName = $counterpartyName;
        $this->counterpartyIban = $counterpartyIban;
        $this->counterpartyBic = $counterpartyBic;
    }

    public function getReference(): Reference {
        return $this->reference;
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

    public function getTransactionCode(): ?string {
        return $this->transactionCode;
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
            return $this->transactionCode;
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

    public function getCounterpartyName(): ?string {
        return $this->counterpartyName;
    }

    public function getCounterpartyIban(): ?string {
        return $this->counterpartyIban;
    }

    public function getCounterpartyBic(): ?string {
        return $this->counterpartyBic;
    }

    public function getSign(): string {
        return $this->creditDebit->getSymbol();
    }

    /**
     * Gibt eine kompakte Beschreibung der Transaktion zurück.
     */
    public function getSummary(): string {
        $parts = [];

        if ($this->counterpartyName !== null) {
            $parts[] = $this->counterpartyName;
        }

        if ($this->purpose !== null) {
            $parts[] = $this->purpose;
        }

        if (empty($parts) && $this->additionalInfo !== null) {
            $parts[] = $this->additionalInfo;
        }

        return implode(' - ', $parts);
    }

    /**
     * Erstellt eine Kopie mit geändertem Verwendungszweck.
     */
    public function withPurpose(string $purpose): self {
        $clone = clone $this;
        $clone->purpose = $purpose;
        return $clone;
    }

    /**
     * Erstellt eine Kopie mit geänderten Gegenseiten-Daten.
     */
    public function withCounterparty(?string $name, ?string $iban = null, ?string $bic = null): self {
        $clone = clone $this;
        $clone->counterpartyName = $name;
        $clone->counterpartyIban = $iban;
        $clone->counterpartyBic = $bic;
        return $clone;
    }

    /**
     * Gibt eine String-Repräsentation der Transaktion zurück.
     */
    public function __toString(): string {
        $sign = $this->isCredit() ? '+' : '-';
        $amount = number_format($this->amount, 2, ',', '.') . ' ' . $this->currency->value;
        $date = $this->bookingDate->format('d.m.Y');
        $summary = $this->getSummary();

        return sprintf('%s | %s%s%s', $date, $sign, $amount, $summary ? ' | ' . $summary : '');
    }
}
