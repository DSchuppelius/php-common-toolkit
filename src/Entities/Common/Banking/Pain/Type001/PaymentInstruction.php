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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type001;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use DateTimeImmutable;

/**
 * Payment Instruction für pain.001-Nachrichten (PmtInf).
 * 
 * Gruppiert Überweisungen mit gemeinsamen Auftraggeber-Daten:
 * - PmtInfId: Payment Instruction ID
 * - PmtMtd: Zahlungsmethode (TRF=Überweisung, CHK=Scheck)
 * - Dbtr: Auftraggeber (Debtor)
 * - DbtrAcct: Konto des Auftraggebers
 * - DbtrAgt: Bank des Auftraggebers
 * - CdtTrfTxInf: Einzelne Überweisungen
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type001
 */
final class PaymentInstruction {
    /** @var CreditTransferTransaction[] */
    private array $transactions = [];

    public function __construct(
        private readonly string $paymentInstructionId,
        private readonly PaymentMethod $paymentMethod,
        private readonly DateTimeImmutable $requestedExecutionDate,
        private readonly PartyIdentification $debtor,
        private readonly AccountIdentification $debtorAccount,
        private readonly FinancialInstitution $debtorAgent,
        array $transactions = [],
        private readonly ?bool $batchBooking = null,
        private readonly ?ChargesCode $chargeBearer = null,
        private readonly ?PartyIdentification $ultimateDebtor = null,
        private readonly ?string $serviceLevel = null,
        private readonly ?string $localInstrument = null,
        private readonly ?string $categoryPurpose = null
    ) {
        $this->transactions = $transactions;
    }

    /**
     * Gibt die Payment Instruction ID zurück (PmtInfId).
     */
    public function getPaymentInstructionId(): string {
        return $this->paymentInstructionId;
    }

    /**
     * Gibt die Zahlungsmethode zurück (PmtMtd).
     */
    public function getPaymentMethod(): PaymentMethod {
        return $this->paymentMethod;
    }

    /**
     * Gibt das gewünschte Ausführungsdatum zurück (ReqdExctnDt).
     */
    public function getRequestedExecutionDate(): DateTimeImmutable {
        return $this->requestedExecutionDate;
    }

    /**
     * Gibt den Auftraggeber zurück (Dbtr).
     */
    public function getDebtor(): PartyIdentification {
        return $this->debtor;
    }

    /**
     * Gibt das Konto des Auftraggebers zurück (DbtrAcct).
     */
    public function getDebtorAccount(): AccountIdentification {
        return $this->debtorAccount;
    }

    /**
     * Gibt die Bank des Auftraggebers zurück (DbtrAgt).
     */
    public function getDebtorAgent(): FinancialInstitution {
        return $this->debtorAgent;
    }

    /**
     * Gibt die Transaktionen zurück (CdtTrfTxInf).
     * @return CreditTransferTransaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    /**
     * Gibt die Batch-Booking-Option zurück (BtchBookg).
     * true = Sammelbuchung, false = Einzelbuchung pro Transaktion
     */
    public function getBatchBooking(): ?bool {
        return $this->batchBooking;
    }

    /**
     * Gibt den Gebührenträger zurück (ChrgBr).
     */
    public function getChargeBearer(): ?ChargesCode {
        return $this->chargeBearer;
    }

    /**
     * Gibt den letztendlichen Auftraggeber zurück (UltmtDbtr).
     */
    public function getUltimateDebtor(): ?PartyIdentification {
        return $this->ultimateDebtor;
    }

    /**
     * Gibt das Service Level zurück (SvcLvl/Cd).
     * Z.B. "SEPA" für SEPA-Überweisungen.
     */
    public function getServiceLevel(): ?string {
        return $this->serviceLevel;
    }

    /**
     * Gibt das Local Instrument zurück (LclInstrm/Cd).
     */
    public function getLocalInstrument(): ?string {
        return $this->localInstrument;
    }

    /**
     * Gibt den Category Purpose zurück (CtgyPurp/Cd).
     * Z.B. "SALA" für Gehaltszahlung.
     */
    public function getCategoryPurpose(): ?string {
        return $this->categoryPurpose;
    }

    /**
     * Fügt eine Transaktion hinzu.
     */
    public function addTransaction(CreditTransferTransaction $transaction): void {
        $this->transactions[] = $transaction;
    }

    /**
     * Gibt die Anzahl der Transaktionen zurück.
     */
    public function countTransactions(): int {
        return count($this->transactions);
    }

    /**
     * Berechnet die Kontrollsumme aller Transaktionen.
     */
    public function calculateControlSum(): float {
        return array_reduce(
            $this->transactions,
            fn(float $sum, CreditTransferTransaction $txn) => $sum + $txn->getAmount(),
            0.0
        );
    }

    /**
     * Erstellt eine SEPA-Payment-Instruction mit String-Parametern.
     */
    public static function sepa(
        string $paymentInstructionId,
        DateTimeImmutable $executionDate,
        string $debtorName,
        string $debtorIban,
        string $debtorBic,
        array $transactions = []
    ): self {
        return new self(
            paymentInstructionId: $paymentInstructionId,
            paymentMethod: PaymentMethod::TRANSFER,
            requestedExecutionDate: $executionDate,
            debtor: PartyIdentification::fromName($debtorName),
            debtorAccount: AccountIdentification::fromIban($debtorIban),
            debtorAgent: FinancialInstitution::fromBic($debtorBic),
            transactions: $transactions,
            batchBooking: true,
            chargeBearer: ChargesCode::SLEV,
            serviceLevel: 'SEPA'
        );
    }

    /**
     * Erstellt eine SEPA-Payment-Instruction mit Entity-Objekten.
     */
    public static function sepaFromEntities(
        string $paymentInstructionId,
        PartyIdentification $debtor,
        AccountIdentification $debtorAccount,
        FinancialInstitution $debtorAgent,
        array $transactions = [],
        ?DateTimeImmutable $executionDate = null
    ): self {
        return new self(
            paymentInstructionId: $paymentInstructionId,
            paymentMethod: PaymentMethod::TRANSFER,
            requestedExecutionDate: $executionDate ?? new DateTimeImmutable(),
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            debtorAgent: $debtorAgent,
            transactions: $transactions,
            batchBooking: true,
            chargeBearer: ChargesCode::SLEV,
            serviceLevel: 'SEPA'
        );
    }
}
