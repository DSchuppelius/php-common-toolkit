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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type018;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.018 Document - Mandate Suspension Request.
 * 
 * Anfrage zur temporären Aussetzung eines Mandats.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type018
 */
final class Document {
    /** @var MandateSuspensionRequest[] */
    private array $suspensionRequests = [];

    public function __construct(
        private readonly string $messageId,
        private readonly DateTimeImmutable $creationDateTime,
        private readonly PartyIdentification $initiatingParty,
        array $suspensionRequests = []
    ) {
        $this->suspensionRequests = $suspensionRequests;
    }

    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        array $suspensionRequests = []
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            initiatingParty: $initiatingParty,
            suspensionRequests: $suspensionRequests
        );
    }

    public function getType(): PainType {
        return PainType::PAIN_018;
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
     * @return MandateSuspensionRequest[]
     */
    public function getSuspensionRequests(): array {
        return $this->suspensionRequests;
    }

    public function addSuspensionRequest(MandateSuspensionRequest $request): self {
        $clone = clone $this;
        $clone->suspensionRequests[] = $request;
        return $clone;
    }

    public function countRequests(): int {
        return count($this->suspensionRequests);
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->messageId) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->suspensionRequests)) {
            $errors[] = 'Mindestens eine Mandatsaussetzungs-Anfrage erforderlich';
        }

        foreach ($this->suspensionRequests as $index => $request) {
            if ($request->getSuspensionStartDate() > $request->getSuspensionEndDate()) {
                $errors[] = "MndtSspnsnReq[$index] Startdatum liegt nach Enddatum";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
