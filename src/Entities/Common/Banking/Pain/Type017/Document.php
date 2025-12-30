<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type017;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.017 Document - Mandate Copy Request.
 * 
 * Anfrage zur Erstellung einer Kopie eines bestehenden Mandats.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type017
 */
final class Document {
    /** @var MandateCopyRequest[] */
    private array $copyRequests = [];

    public function __construct(
        private readonly string $messageId,
        private readonly DateTimeImmutable $creationDateTime,
        private readonly PartyIdentification $initiatingParty,
        array $copyRequests = []
    ) {
        $this->copyRequests = $copyRequests;
    }

    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        array $copyRequests = []
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            initiatingParty: $initiatingParty,
            copyRequests: $copyRequests
        );
    }

    public function getType(): PainType {
        return PainType::PAIN_017;
    }

    public function getMessageId(): string {
        return $this->messageId;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getInitiatingParty(): PartyIdentification {
        return $this->initiatingParty;
    }

    /**
     * @return MandateCopyRequest[]
     */
    public function getCopyRequests(): array {
        return $this->copyRequests;
    }

    public function addCopyRequest(MandateCopyRequest $request): self {
        $clone = clone $this;
        $clone->copyRequests[] = $request;
        return $clone;
    }

    public function countRequests(): int {
        return count($this->copyRequests);
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->messageId) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->copyRequests)) {
            $errors[] = 'Mindestens eine Mandatskopie-Anfrage erforderlich';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
