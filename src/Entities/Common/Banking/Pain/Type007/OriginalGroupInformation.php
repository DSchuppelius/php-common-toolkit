<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : OriginalGroupInformation.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type007;

use DateTimeImmutable;

/**
 * Original Group Information für pain.007 (OrgnlGrpInf).
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type007
 */
final readonly class OriginalGroupInformation {
    public function __construct(
        private string $originalMessageId,
        private string $originalMessageNameId,
        private ?DateTimeImmutable $originalCreationDateTime = null,
        private ?int $originalNumberOfTransactions = null,
        private ?float $originalControlSum = null,
        private ?ReversalReason $reversalReason = null
    ) {
    }

    public static function forPain008(string $originalMessageId): self {
        return new self($originalMessageId, 'pain.008.001.11');
    }

    public function getOriginalMessageId(): string {
        return $this->originalMessageId;
    }

    public function getOriginalMessageNameId(): string {
        return $this->originalMessageNameId;
    }

    public function getOriginalCreationDateTime(): ?DateTimeImmutable {
        return $this->originalCreationDateTime;
    }

    public function getOriginalNumberOfTransactions(): ?int {
        return $this->originalNumberOfTransactions;
    }

    public function getOriginalControlSum(): ?float {
        return $this->originalControlSum;
    }

    public function getReversalReason(): ?ReversalReason {
        return $this->reversalReason;
    }
}
