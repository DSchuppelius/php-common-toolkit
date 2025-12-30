<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentInstruction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type008;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\LocalInstrument;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use CommonToolkit\Enums\Common\Banking\SequenceType;
use DateTimeImmutable;

/**
 * Payment Instruction für pain.008 (PmtInf).
 * 
 * Gruppiert Lastschriften mit gemeinsamen Gläubiger-Daten:
 * - PmtInfId: Payment Instruction ID
 * - PmtMtd: Zahlungsmethode (DD=Lastschrift)
 * - Cdtr: Gläubiger (Creditor)
 * - CdtrAcct: Konto des Gläubigers
 * - CdtrAgt: Bank des Gläubigers
 * - DrctDbtTxInf: Einzelne Lastschriften
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type008
 */
final class PaymentInstruction {
    /** @var DirectDebitTransaction[] */
    private array $transactions = [];

    public function __construct(
        private readonly string $paymentInstructionId,
        private readonly PaymentMethod $paymentMethod,
        private readonly DateTimeImmutable $requestedCollectionDate,
        private readonly PartyIdentification $creditor,
        private readonly AccountIdentification $creditorAccount,
        private readonly FinancialInstitution $creditorAgent,
        array $transactions = [],
        private readonly ?string $creditorSchemeId = null,
        private readonly ?bool $batchBooking = null,
        private readonly ?ChargesCode $chargeBearer = null,
        private readonly ?SequenceType $sequenceType = null,
        private readonly ?LocalInstrument $localInstrument = null,
        private readonly ?string $serviceLevel = null,
        private readonly ?string $categoryPurpose = null
    ) {
        $this->transactions = $transactions;
    }

    /**
     * Factory für SEPA Core Lastschrift.
     */
    public static function sepaCore(
        string $paymentInstructionId,
        DateTimeImmutable $collectionDate,
        string $creditorName,
        string $creditorIban,
        string $creditorBic,
        string $creditorSchemeId,
        SequenceType $sequenceType,
        array $transactions = []
    ): self {
        return new self(
            paymentInstructionId: $paymentInstructionId,
            paymentMethod: PaymentMethod::DIRECT_DEBIT,
            requestedCollectionDate: $collectionDate,
            creditor: new PartyIdentification(name: $creditorName),
            creditorAccount: new AccountIdentification(iban: $creditorIban),
            creditorAgent: new FinancialInstitution(bic: $creditorBic),
            transactions: $transactions,
            creditorSchemeId: $creditorSchemeId,
            chargeBearer: ChargesCode::SLEV,
            sequenceType: $sequenceType,
            localInstrument: LocalInstrument::SEPA_CORE,
            serviceLevel: 'SEPA'
        );
    }

    /**
     * Factory für SEPA B2B Lastschrift.
     */
    public static function sepaB2B(
        string $paymentInstructionId,
        DateTimeImmutable $collectionDate,
        string $creditorName,
        string $creditorIban,
        string $creditorBic,
        string $creditorSchemeId,
        SequenceType $sequenceType,
        array $transactions = []
    ): self {
        return new self(
            paymentInstructionId: $paymentInstructionId,
            paymentMethod: PaymentMethod::DIRECT_DEBIT,
            requestedCollectionDate: $collectionDate,
            creditor: new PartyIdentification(name: $creditorName),
            creditorAccount: new AccountIdentification(iban: $creditorIban),
            creditorAgent: new FinancialInstitution(bic: $creditorBic),
            transactions: $transactions,
            creditorSchemeId: $creditorSchemeId,
            chargeBearer: ChargesCode::SLEV,
            sequenceType: $sequenceType,
            localInstrument: LocalInstrument::SEPA_B2B,
            serviceLevel: 'SEPA'
        );
    }

    public function getPaymentInstructionId(): string {
        return $this->paymentInstructionId;
    }

    public function getPaymentMethod(): PaymentMethod {
        return $this->paymentMethod;
    }

    public function getRequestedCollectionDate(): DateTimeImmutable {
        return $this->requestedCollectionDate;
    }

    public function getCreditor(): PartyIdentification {
        return $this->creditor;
    }

    public function getCreditorAccount(): AccountIdentification {
        return $this->creditorAccount;
    }

    public function getCreditorAgent(): FinancialInstitution {
        return $this->creditorAgent;
    }

    public function getCreditorSchemeId(): ?string {
        return $this->creditorSchemeId;
    }

    public function getBatchBooking(): ?bool {
        return $this->batchBooking;
    }

    public function getChargeBearer(): ?ChargesCode {
        return $this->chargeBearer;
    }

    public function getSequenceType(): ?SequenceType {
        return $this->sequenceType;
    }

    public function getLocalInstrument(): ?LocalInstrument {
        return $this->localInstrument;
    }

    public function getServiceLevel(): ?string {
        return $this->serviceLevel;
    }

    public function getCategoryPurpose(): ?string {
        return $this->categoryPurpose;
    }

    /**
     * @return DirectDebitTransaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    /**
     * Fügt eine Transaktion hinzu.
     */
    public function addTransaction(DirectDebitTransaction $transaction): self {
        $clone = clone $this;
        $clone->transactions[] = $transaction;
        return $clone;
    }

    /**
     * Berechnet die Kontrollsumme.
     */
    public function calculateControlSum(): float {
        return array_sum(array_map(
            fn($t) => $t->getAmount(),
            $this->transactions
        ));
    }

    /**
     * Zählt die Transaktionen.
     */
    public function countTransactions(): int {
        return count($this->transactions);
    }
}
