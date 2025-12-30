<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateInformation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type008;

use DateTimeImmutable;

/**
 * Mandate Related Information für pain.008 (DrctDbtTx/MndtRltdInf).
 * 
 * Enthält Informationen zum SEPA-Lastschrift-Mandat.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type008
 */
final readonly class MandateInformation {
    public function __construct(
        private string $mandateId,
        private DateTimeImmutable $dateOfSignature,
        private ?bool $amendmentIndicator = null,
        private ?string $originalMandateId = null,
        private ?string $originalCreditorSchemeId = null,
        private ?string $originalCreditorName = null,
        private ?string $originalDebtorAccount = null,
        private ?string $originalDebtorAgent = null,
        private ?string $electronicSignature = null
    ) {
    }

    /**
     * Factory für einfaches Mandat.
     */
    public static function create(
        string $mandateId,
        DateTimeImmutable $dateOfSignature
    ): self {
        return new self($mandateId, $dateOfSignature);
    }

    /**
     * Factory für geändertes Mandat.
     */
    public static function amended(
        string $mandateId,
        DateTimeImmutable $dateOfSignature,
        ?string $originalMandateId = null,
        ?string $originalCreditorSchemeId = null
    ): self {
        return new self(
            mandateId: $mandateId,
            dateOfSignature: $dateOfSignature,
            amendmentIndicator: true,
            originalMandateId: $originalMandateId,
            originalCreditorSchemeId: $originalCreditorSchemeId
        );
    }

    public function getMandateId(): string {
        return $this->mandateId;
    }

    public function getDateOfSignature(): DateTimeImmutable {
        return $this->dateOfSignature;
    }

    public function getAmendmentIndicator(): ?bool {
        return $this->amendmentIndicator;
    }

    public function getOriginalMandateId(): ?string {
        return $this->originalMandateId;
    }

    public function getOriginalCreditorSchemeId(): ?string {
        return $this->originalCreditorSchemeId;
    }

    public function getOriginalCreditorName(): ?string {
        return $this->originalCreditorName;
    }

    public function getOriginalDebtorAccount(): ?string {
        return $this->originalDebtorAccount;
    }

    public function getOriginalDebtorAgent(): ?string {
        return $this->originalDebtorAgent;
    }

    public function getElectronicSignature(): ?string {
        return $this->electronicSignature;
    }

    /**
     * Prüft, ob das Mandat geändert wurde.
     */
    public function isAmended(): bool {
        return $this->amendmentIndicator === true;
    }
}
