<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateSuspensionRequest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type018;

use DateTimeImmutable;

/**
 * Mandate Suspension Request für pain.018.
 * 
 * Einzelne Anfrage zur temporären Aussetzung eines Mandats.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type018
 */
final readonly class MandateSuspensionRequest {
    public function __construct(
        private string $mandateId,
        private DateTimeImmutable $suspensionStartDate,
        private DateTimeImmutable $suspensionEndDate,
        private ?string $creditorSchemeId = null,
        private ?string $suspensionReason = null
    ) {
    }

    public static function create(
        string $mandateId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $reason = null
    ): self {
        return new self(
            mandateId: $mandateId,
            suspensionStartDate: $startDate,
            suspensionEndDate: $endDate,
            suspensionReason: $reason
        );
    }

    /**
     * Factory für unbegrenzte Aussetzung.
     */
    public static function indefinite(
        string $mandateId,
        DateTimeImmutable $startDate,
        ?string $reason = null
    ): self {
        return new self(
            mandateId: $mandateId,
            suspensionStartDate: $startDate,
            suspensionEndDate: new DateTimeImmutable('2099-12-31'),
            suspensionReason: $reason
        );
    }

    public function getMandateId(): string {
        return $this->mandateId;
    }

    public function getSuspensionStartDate(): DateTimeImmutable {
        return $this->suspensionStartDate;
    }

    public function getSuspensionEndDate(): DateTimeImmutable {
        return $this->suspensionEndDate;
    }

    public function getCreditorSchemeId(): ?string {
        return $this->creditorSchemeId;
    }

    public function getSuspensionReason(): ?string {
        return $this->suspensionReason;
    }

    public function getDurationDays(): int {
        $interval = $this->suspensionStartDate->diff($this->suspensionEndDate);
        return $interval->days;
    }

    public function isActive(): bool {
        $now = new DateTimeImmutable();
        return $now >= $this->suspensionStartDate && $now <= $this->suspensionEndDate;
    }
}
