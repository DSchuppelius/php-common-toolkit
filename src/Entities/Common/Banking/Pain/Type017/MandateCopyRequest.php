<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateCopyRequest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type017;

/**
 * Mandate Copy Request für pain.017.
 * 
 * Einzelne Anfrage zur Erstellung einer Mandatskopie.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type017
 */
final readonly class MandateCopyRequest {
    public function __construct(
        private string $mandateId,
        private ?string $creditorSchemeId = null,
        private ?string $creditorId = null,
        private ?string $debtorId = null,
        private ?bool $includeElectronicSignature = null
    ) {
    }

    public static function create(
        string $mandateId,
        ?string $creditorSchemeId = null
    ): self {
        return new self(
            mandateId: $mandateId,
            creditorSchemeId: $creditorSchemeId
        );
    }

    public function getMandateId(): string {
        return $this->mandateId;
    }

    public function getCreditorSchemeId(): ?string {
        return $this->creditorSchemeId;
    }

    public function getCreditorId(): ?string {
        return $this->creditorId;
    }

    public function getDebtorId(): ?string {
        return $this->debtorId;
    }

    public function includeElectronicSignature(): ?bool {
        return $this->includeElectronicSignature;
    }
}
