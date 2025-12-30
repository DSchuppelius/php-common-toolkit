<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionInformation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type007;

use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Transaction Information für pain.007 (TxInf).
 * 
 * Details zur ursprünglichen Transaktion, die storniert werden soll.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type007
 */
final readonly class TransactionInformation {
    public function __construct(
        private ?string $reversalId = null,
        private ?string $originalInstructionId = null,
        private ?string $originalEndToEndId = null,
        private ?string $originalTransactionId = null,
        private ?float $reversedAmount = null,
        private ?CurrencyCode $currency = null,
        private ?ReversalReason $reversalReason = null,
        private ?DateTimeImmutable $originalInterbankSettlementDate = null
    ) {
    }

    public static function create(
        string $originalEndToEndId,
        float $reversedAmount,
        ReversalReason $reason,
        CurrencyCode $currency = CurrencyCode::Euro
    ): self {
        return new self(
            originalEndToEndId: $originalEndToEndId,
            reversedAmount: $reversedAmount,
            currency: $currency,
            reversalReason: $reason
        );
    }

    public function getReversalId(): ?string {
        return $this->reversalId;
    }

    public function getOriginalInstructionId(): ?string {
        return $this->originalInstructionId;
    }

    public function getOriginalEndToEndId(): ?string {
        return $this->originalEndToEndId;
    }

    public function getOriginalTransactionId(): ?string {
        return $this->originalTransactionId;
    }

    public function getReversedAmount(): ?float {
        return $this->reversedAmount;
    }

    public function getCurrency(): ?CurrencyCode {
        return $this->currency;
    }

    public function getReversalReason(): ?ReversalReason {
        return $this->reversalReason;
    }

    public function getOriginalInterbankSettlementDate(): ?DateTimeImmutable {
        return $this->originalInterbankSettlementDate;
    }
}
