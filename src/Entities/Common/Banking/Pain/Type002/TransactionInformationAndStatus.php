<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionInformationAndStatus.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Transaction Information and Status für pain.002 (TxInfAndSts).
 * 
 * Details zum Status einer einzelnen Transaktion.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
 */
final readonly class TransactionInformationAndStatus {
    /**
     * @param string|null $statusId Status-ID (StsId)
     * @param string|null $originalInstructionId Original Instruction ID (OrgnlInstrId)
     * @param string|null $originalEndToEndId Original End-to-End ID (OrgnlEndToEndId)
     * @param string|null $originalUetr Original UETR (OrgnlUETR)
     * @param TransactionStatus|null $status Transaktionsstatus (TxSts)
     * @param StatusReason[] $statusReasons Status-Begründungen (StsRsnInf)
     * @param float|null $originalAmount Original-Betrag (OrgnlTxRef/Amt)
     * @param CurrencyCode|null $originalCurrency Original-Währung
     * @param DateTimeImmutable|null $acceptanceDateTime Akzeptanz-Zeitpunkt (AccptncDtTm)
     */
    public function __construct(
        private ?string $statusId = null,
        private ?string $originalInstructionId = null,
        private ?string $originalEndToEndId = null,
        private ?string $originalUetr = null,
        private ?TransactionStatus $status = null,
        private array $statusReasons = [],
        private ?float $originalAmount = null,
        private ?CurrencyCode $originalCurrency = null,
        private ?DateTimeImmutable $acceptanceDateTime = null
    ) {
    }

    /**
     * Erstellt einen erfolgreichen Status.
     */
    public static function accepted(
        string $originalEndToEndId,
        TransactionStatus $status = TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED
    ): self {
        return new self(
            originalEndToEndId: $originalEndToEndId,
            status: $status
        );
    }

    /**
     * Erstellt einen Ablehnungsstatus.
     */
    public static function rejected(
        string $originalEndToEndId,
        StatusReason $reason
    ): self {
        return new self(
            originalEndToEndId: $originalEndToEndId,
            status: TransactionStatus::REJECTED,
            statusReasons: [$reason]
        );
    }

    public function getStatusId(): ?string {
        return $this->statusId;
    }

    public function getOriginalInstructionId(): ?string {
        return $this->originalInstructionId;
    }

    public function getOriginalEndToEndId(): ?string {
        return $this->originalEndToEndId;
    }

    public function getOriginalUetr(): ?string {
        return $this->originalUetr;
    }

    public function getStatus(): ?TransactionStatus {
        return $this->status;
    }

    /**
     * @return StatusReason[]
     */
    public function getStatusReasons(): array {
        return $this->statusReasons;
    }

    public function getOriginalAmount(): ?float {
        return $this->originalAmount;
    }

    public function getOriginalCurrency(): ?CurrencyCode {
        return $this->originalCurrency;
    }

    public function getAcceptanceDateTime(): ?DateTimeImmutable {
        return $this->acceptanceDateTime;
    }

    /**
     * Prüft, ob die Transaktion erfolgreich war.
     */
    public function isSuccessful(): bool {
        return $this->status?->isSuccessful() ?? false;
    }

    /**
     * Prüft, ob die Transaktion abgelehnt wurde.
     */
    public function isRejected(): bool {
        return $this->status?->isRejected() ?? false;
    }

    /**
     * Gibt den ersten Ablehnungsgrund zurück.
     */
    public function getFirstReason(): ?StatusReason {
        return $this->statusReasons[0] ?? null;
    }
}
