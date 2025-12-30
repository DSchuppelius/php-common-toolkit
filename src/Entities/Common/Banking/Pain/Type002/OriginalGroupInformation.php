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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

use DateTimeImmutable;

/**
 * Original Group Information and Status für pain.002 (OrgnlGrpInfAndSts).
 * 
 * Informationen zur Original-Nachricht und deren Gesamtstatus.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
 */
final readonly class OriginalGroupInformation {
    /**
     * @param string $originalMessageId Original Message ID (OrgnlMsgId)
     * @param string $originalMessageNameId Original Message Name ID (OrgnlMsgNmId)
     * @param DateTimeImmutable|null $originalCreationDateTime Original Creation DateTime (OrgnlCreDtTm)
     * @param int|null $originalNumberOfTransactions Anzahl der Original-Transaktionen (OrgnlNbOfTxs)
     * @param float|null $originalControlSum Original Control Sum (OrgnlCtrlSum)
     * @param TransactionStatus|null $groupStatus Gruppen-Status (GrpSts)
     * @param StatusReason[] $statusReasons Status-Begründungen (StsRsnInf)
     */
    public function __construct(
        private string $originalMessageId,
        private string $originalMessageNameId,
        private ?DateTimeImmutable $originalCreationDateTime = null,
        private ?int $originalNumberOfTransactions = null,
        private ?float $originalControlSum = null,
        private ?TransactionStatus $groupStatus = null,
        private array $statusReasons = []
    ) {
    }

    /**
     * Factory für pain.001 Antwort.
     */
    public static function forPain001(
        string $originalMessageId,
        ?TransactionStatus $groupStatus = null
    ): self {
        return new self(
            originalMessageId: $originalMessageId,
            originalMessageNameId: 'pain.001.001.12',
            groupStatus: $groupStatus
        );
    }

    /**
     * Factory für pain.008 Antwort.
     */
    public static function forPain008(
        string $originalMessageId,
        ?TransactionStatus $groupStatus = null
    ): self {
        return new self(
            originalMessageId: $originalMessageId,
            originalMessageNameId: 'pain.008.001.11',
            groupStatus: $groupStatus
        );
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

    public function getGroupStatus(): ?TransactionStatus {
        return $this->groupStatus;
    }

    /**
     * @return StatusReason[]
     */
    public function getStatusReasons(): array {
        return $this->statusReasons;
    }

    /**
     * Prüft, ob die gesamte Gruppe akzeptiert wurde.
     */
    public function isGroupAccepted(): bool {
        return $this->groupStatus?->isSuccessful() ?? false;
    }

    /**
     * Prüft, ob die gesamte Gruppe abgelehnt wurde.
     */
    public function isGroupRejected(): bool {
        return $this->groupStatus?->isRejected() ?? false;
    }
}
