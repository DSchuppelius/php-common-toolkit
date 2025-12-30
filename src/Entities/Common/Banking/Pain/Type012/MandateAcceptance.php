<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateAcceptance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type012;

use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;
use CommonToolkit\Enums\Common\Banking\MandateStatus;
use DateTimeImmutable;

/**
 * Mandate Acceptance für pain.012.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type012
 */
final readonly class MandateAcceptance {
    public function __construct(
        private string $mandateId,
        private MandateStatus $status,
        private ?Mandate $mandate = null,
        private ?string $originalMessageId = null,
        private ?DateTimeImmutable $acceptanceDateTime = null,
        private ?string $rejectReason = null
    ) {
    }

    public static function accepted(
        string $mandateId,
        ?Mandate $mandate = null
    ): self {
        return new self(
            mandateId: $mandateId,
            status: MandateStatus::ACCEPTED,
            mandate: $mandate,
            acceptanceDateTime: new DateTimeImmutable()
        );
    }

    public static function rejected(
        string $mandateId,
        string $rejectReason
    ): self {
        return new self(
            mandateId: $mandateId,
            status: MandateStatus::REJECTED,
            rejectReason: $rejectReason,
            acceptanceDateTime: new DateTimeImmutable()
        );
    }

    public function getMandateId(): string {
        return $this->mandateId;
    }

    public function getStatus(): MandateStatus {
        return $this->status;
    }

    public function getMandate(): ?Mandate {
        return $this->mandate;
    }

    public function getOriginalMessageId(): ?string {
        return $this->originalMessageId;
    }

    public function getAcceptanceDateTime(): ?DateTimeImmutable {
        return $this->acceptanceDateTime;
    }

    public function getRejectReason(): ?string {
        return $this->rejectReason;
    }

    public function isAccepted(): bool {
        return $this->status === MandateStatus::ACCEPTED;
    }

    public function isRejected(): bool {
        return $this->status === MandateStatus::REJECTED;
    }
}
