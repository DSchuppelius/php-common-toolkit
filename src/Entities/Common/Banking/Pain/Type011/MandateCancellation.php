<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MandateCancellation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type011;

use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;

/**
 * Mandate Cancellation für pain.011.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type011
 */
final readonly class MandateCancellation {
    public function __construct(
        private string $mandateId,
        private CancellationReason $cancellationReason,
        private ?Mandate $originalMandate = null
    ) {
    }

    public static function create(
        string $mandateId,
        CancellationReason $reason
    ): self {
        return new self($mandateId, $reason);
    }

    public static function withOriginal(
        Mandate $originalMandate,
        CancellationReason $reason
    ): self {
        return new self(
            $originalMandate->getMandateId(),
            $reason,
            $originalMandate
        );
    }

    public function getMandateId(): string {
        return $this->mandateId;
    }

    public function getCancellationReason(): CancellationReason {
        return $this->cancellationReason;
    }

    public function getOriginalMandate(): ?Mandate {
        return $this->originalMandate;
    }
}
