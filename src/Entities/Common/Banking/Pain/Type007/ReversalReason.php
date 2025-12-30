<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ReversalReason.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type007;

/**
 * Reversal Reason für pain.007 (RvslRsnInf).
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type007
 */
final readonly class ReversalReason {
    public function __construct(
        private ?string $code = null,
        private ?string $proprietary = null,
        private array $additionalInfo = []
    ) {
    }

    public static function fromCode(string $code, array $additionalInfo = []): self {
        return new self($code, null, $additionalInfo);
    }

    public static function customerRequest(?string $additionalInfo = null): self {
        return new self('CUST', null, $additionalInfo ? [$additionalInfo] : []);
    }

    public static function duplicate(): self {
        return new self('DUPL', null, ['Doppelte Transaktion']);
    }

    public static function technicalError(): self {
        return new self('TECH', null, ['Technischer Fehler']);
    }

    public static function fraudulent(): self {
        return new self('FRAD', null, ['Betrugsverdacht']);
    }

    public function getCode(): ?string {
        return $this->code;
    }

    public function getProprietary(): ?string {
        return $this->proprietary;
    }

    public function getAdditionalInfo(): array {
        return $this->additionalInfo;
    }

    public function getReason(): ?string {
        return $this->code ?? $this->proprietary;
    }
}
