<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StatusReason.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

/**
 * Status Reason Information für pain.002 (StsRsnInf).
 * 
 * Enthält Details zum Status einer Transaktion.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
 */
final readonly class StatusReason {
    /**
     * @param string|null $code Reason Code (Rsn/Cd)
     * @param string|null $proprietary Proprietary Reason (Rsn/Prtry)
     * @param string[] $additionalInfo Zusätzliche Informationen (AddtlInf)
     */
    public function __construct(
        private ?string $code = null,
        private ?string $proprietary = null,
        private array $additionalInfo = []
    ) {
    }

    /**
     * Erstellt aus einem ISO-Reason-Code.
     */
    public static function fromCode(string $code, array $additionalInfo = []): self {
        return new self($code, null, $additionalInfo);
    }

    /**
     * Erstellt aus proprietärem Code.
     */
    public static function fromProprietary(string $proprietary, array $additionalInfo = []): self {
        return new self(null, $proprietary, $additionalInfo);
    }

    public function getCode(): ?string {
        return $this->code;
    }

    public function getProprietary(): ?string {
        return $this->proprietary;
    }

    /**
     * @return string[]
     */
    public function getAdditionalInfo(): array {
        return $this->additionalInfo;
    }

    /**
     * Gibt den Reason (Code oder Proprietary) zurück.
     */
    public function getReason(): ?string {
        return $this->code ?? $this->proprietary;
    }

    /**
     * Versucht den Code als StatusReasonCode zu interpretieren.
     */
    public function getReasonCode(): ?StatusReasonCode {
        if ($this->code === null) {
            return null;
        }
        return StatusReasonCode::tryFrom($this->code);
    }
}
