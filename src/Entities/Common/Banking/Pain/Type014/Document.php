<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type014;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.014 Document - Creditor Payment Activation Request Status Report.
 * 
 * Statusbericht für Zahlungsaktivierungsanfragen.
 * Antwort auf pain.013.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type014
 */
final class Document {
    /** @var PaymentActivationStatus[] */
    private array $paymentStatuses = [];

    public function __construct(
        private readonly string $messageId,
        private readonly DateTimeImmutable $creationDateTime,
        private readonly string $originalMessageId,
        private readonly string $originalMessageNameId,
        private readonly ?PartyIdentification $initiatingParty = null,
        private readonly int $originalNumberOfTransactions = 0,
        private readonly float $originalControlSum = 0.0,
        array $paymentStatuses = []
    ) {
        $this->paymentStatuses = $paymentStatuses;
    }

    public static function create(
        string $messageId,
        string $originalMessageId,
        array $paymentStatuses = [],
        ?PartyIdentification $initiatingParty = null
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            originalMessageId: $originalMessageId,
            originalMessageNameId: 'pain.013.001.11',
            initiatingParty: $initiatingParty,
            originalNumberOfTransactions: count($paymentStatuses),
            paymentStatuses: $paymentStatuses
        );
    }

    public function getType(): PainType {
        return PainType::PAIN_014;
    }

    public function getMessageId(): string {
        return $this->messageId;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getOriginalMessageId(): string {
        return $this->originalMessageId;
    }

    public function getOriginalMessageNameId(): string {
        return $this->originalMessageNameId;
    }

    public function getInitiatingParty(): ?PartyIdentification {
        return $this->initiatingParty;
    }

    public function getOriginalNumberOfTransactions(): int {
        return $this->originalNumberOfTransactions;
    }

    public function getOriginalControlSum(): float {
        return $this->originalControlSum;
    }

    /**
     * @return PaymentActivationStatus[]
     */
    public function getPaymentStatuses(): array {
        return $this->paymentStatuses;
    }

    public function addPaymentStatus(PaymentActivationStatus $status): self {
        $clone = clone $this;
        $clone->paymentStatuses[] = $status;
        return $clone;
    }

    public function countStatuses(): int {
        return count($this->paymentStatuses);
    }

    public function hasRejections(): bool {
        foreach ($this->paymentStatuses as $status) {
            if ($status->isRejected()) {
                return true;
            }
        }
        return false;
    }

    public function isFullyAccepted(): bool {
        foreach ($this->paymentStatuses as $status) {
            if (!$status->isAccepted()) {
                return false;
            }
        }
        return count($this->paymentStatuses) > 0;
    }

    /**
     * @return PaymentActivationStatus[]
     */
    public function getRejectedPayments(): array {
        return array_values(array_filter(
            $this->paymentStatuses,
            fn(PaymentActivationStatus $s) => $s->isRejected()
        ));
    }

    /**
     * @return PaymentActivationStatus[]
     */
    public function getPendingPayments(): array {
        return array_values(array_filter(
            $this->paymentStatuses,
            fn(PaymentActivationStatus $s) => $s->isPending()
        ));
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->messageId) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->paymentStatuses)) {
            $errors[] = 'Mindestens ein Zahlungsstatus erforderlich';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
