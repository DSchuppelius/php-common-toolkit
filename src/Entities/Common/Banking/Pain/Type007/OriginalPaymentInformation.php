<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OriginalPaymentInformation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type007;

/**
 * Original Payment Information für pain.007 (OrgnlPmtInfAndRvsl).
 * 
 * Referenz auf die ursprüngliche Payment Instruction.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type007
 */
final class OriginalPaymentInformation {
    /** @var TransactionInformation[] */
    private array $transactionInformations = [];

    public function __construct(
        private readonly ?string $reversalPaymentInformationId = null,
        private readonly ?string $originalPaymentInformationId = null,
        private readonly ?int $originalNumberOfTransactions = null,
        private readonly ?float $originalControlSum = null,
        private readonly ?bool $paymentInformationReversal = null,
        private readonly ?ReversalReason $reversalReason = null,
        array $transactionInformations = []
    ) {
        $this->transactionInformations = $transactionInformations;
    }

    public static function create(
        string $originalPaymentInformationId,
        array $transactionInformations = [],
        ?ReversalReason $reversalReason = null
    ): self {
        return new self(
            originalPaymentInformationId: $originalPaymentInformationId,
            reversalReason: $reversalReason,
            transactionInformations: $transactionInformations
        );
    }

    /**
     * Storniert die gesamte Payment Instruction.
     */
    public static function reverseAll(
        string $originalPaymentInformationId,
        ReversalReason $reason
    ): self {
        return new self(
            originalPaymentInformationId: $originalPaymentInformationId,
            paymentInformationReversal: true,
            reversalReason: $reason
        );
    }

    public function getReversalPaymentInformationId(): ?string {
        return $this->reversalPaymentInformationId;
    }

    public function getOriginalPaymentInformationId(): ?string {
        return $this->originalPaymentInformationId;
    }

    public function getOriginalNumberOfTransactions(): ?int {
        return $this->originalNumberOfTransactions;
    }

    public function getOriginalControlSum(): ?float {
        return $this->originalControlSum;
    }

    public function isPaymentInformationReversal(): ?bool {
        return $this->paymentInformationReversal;
    }

    public function getReversalReason(): ?ReversalReason {
        return $this->reversalReason;
    }

    /**
     * @return TransactionInformation[]
     */
    public function getTransactionInformations(): array {
        return $this->transactionInformations;
    }

    public function addTransactionInformation(TransactionInformation $info): self {
        $clone = clone $this;
        $clone->transactionInformations[] = $info;
        return $clone;
    }

    public function countTransactions(): int {
        return count($this->transactionInformations);
    }

    public function calculateReversalSum(): float {
        return array_sum(array_map(
            fn($t) => $t->getReversedAmount() ?? 0,
            $this->transactionInformations
        ));
    }
}
