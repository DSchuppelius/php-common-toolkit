<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GroupHeader.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type008;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use DateTimeImmutable;

/**
 * Group Header für pain.008 Customer Direct Debit Initiation (GrpHdr).
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type008
 */
final readonly class GroupHeader {
    public function __construct(
        private string $messageId,
        private DateTimeImmutable $creationDateTime,
        private int $numberOfTransactions,
        private ?float $controlSum,
        private PartyIdentification $initiatingParty,
        private ?string $forwardingAgent = null
    ) {
    }

    /**
     * Factory-Methode.
     */
    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        int $numberOfTransactions = 0,
        ?float $controlSum = null
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            numberOfTransactions: $numberOfTransactions,
            controlSum: $controlSum,
            initiatingParty: $initiatingParty
        );
    }

    public function getMessageId(): string {
        return $this->messageId;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getNumberOfTransactions(): int {
        return $this->numberOfTransactions;
    }

    public function getControlSum(): ?float {
        return $this->controlSum;
    }

    public function getInitiatingParty(): PartyIdentification {
        return $this->initiatingParty;
    }

    public function getForwardingAgent(): ?string {
        return $this->forwardingAgent;
    }
}
