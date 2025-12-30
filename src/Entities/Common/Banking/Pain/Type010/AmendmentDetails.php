<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AmendmentDetails.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type010;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use DateTimeImmutable;

/**
 * Amendment Details für pain.010.
 * 
 * Enthält die geänderten Werte des Mandats.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type010
 */
final readonly class AmendmentDetails {
    public function __construct(
        private ?string $originalMandateId = null,
        private ?string $originalCreditorSchemeId = null,
        private ?PartyIdentification $originalCreditor = null,
        private ?PartyIdentification $originalDebtor = null,
        private ?AccountIdentification $originalDebtorAccount = null,
        private ?FinancialInstitution $originalDebtorAgent = null,
        private ?DateTimeImmutable $originalFinalCollectionDate = null
    ) {
    }

    public static function mandateIdChange(string $originalMandateId): self {
        return new self(originalMandateId: $originalMandateId);
    }

    public static function creditorSchemeIdChange(string $originalCreditorSchemeId): self {
        return new self(originalCreditorSchemeId: $originalCreditorSchemeId);
    }

    public static function creditorChange(string $originalCreditorSchemeId, ?PartyIdentification $originalCreditor = null): self {
        return new self(originalCreditorSchemeId: $originalCreditorSchemeId, originalCreditor: $originalCreditor);
    }

    public static function debtorAccountChange(AccountIdentification $originalDebtorAccount, ?FinancialInstitution $originalDebtorAgent = null): self {
        return new self(originalDebtorAccount: $originalDebtorAccount, originalDebtorAgent: $originalDebtorAgent);
    }

    public function getOriginalMandateId(): ?string {
        return $this->originalMandateId;
    }

    public function getOriginalCreditorSchemeId(): ?string {
        return $this->originalCreditorSchemeId;
    }

    public function getOriginalCreditor(): ?PartyIdentification {
        return $this->originalCreditor;
    }

    public function getOriginalDebtor(): ?PartyIdentification {
        return $this->originalDebtor;
    }

    public function getOriginalDebtorAccount(): ?AccountIdentification {
        return $this->originalDebtorAccount;
    }

    public function getOriginalDebtorAgent(): ?FinancialInstitution {
        return $this->originalDebtorAgent;
    }

    public function getOriginalFinalCollectionDate(): ?DateTimeImmutable {
        return $this->originalFinalCollectionDate;
    }
}
