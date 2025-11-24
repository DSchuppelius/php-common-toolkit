<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Reference.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Camt053;

final class Reference {
    private ?string $endToEndId;
    private ?string $mandateId;
    private ?string $additional;

    public function __construct(?string $endToEndId = null, ?string $mandateId = null, ?string $additional = null) {
        $this->endToEndId = $endToEndId;
        $this->mandateId = $mandateId;
        $this->additional = $additional;
    }

    public function getEndToEndId(): ?string {
        return $this->endToEndId;
    }

    public function getMandateId(): ?string {
        return $this->mandateId;
    }

    public function getAdditional(): ?string {
        return $this->additional;
    }

    public function __toString(): string {
        return implode(' ', array_filter([$this->endToEndId, $this->mandateId, $this->additional]));
    }
}
