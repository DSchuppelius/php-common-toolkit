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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type009;

use CommonToolkit\Entities\Common\Banking\Pain\Mandate\Mandate;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.009 Document - Mandate Initiation Request.
 * 
 * Anfrage zur Erstellung eines SEPA-Lastschrift-Mandats.
 * 
 * Struktur:
 * - MndtInitnReq (Mandate Initiation Request)
 *   - GrpHdr: Nachrichten-Header
 *   - Mndt[]: Mandate zur Erstellung
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type009
 */
final class Document {
    /** @var Mandate[] */
    private array $mandates = [];

    public function __construct(
        private readonly string $messageId,
        private readonly DateTimeImmutable $creationDateTime,
        private readonly PartyIdentification $initiatingParty,
        array $mandates = []
    ) {
        $this->mandates = $mandates;
    }

    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        array $mandates = []
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            initiatingParty: $initiatingParty,
            mandates: $mandates
        );
    }

    public function getType(): PainType {
        return PainType::PAIN_009;
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
     * @return Mandate[]
     */
    public function getMandates(): array {
        return $this->mandates;
    }

    public function addMandate(Mandate $mandate): self {
        $clone = clone $this;
        $clone->mandates[] = $mandate;
        return $clone;
    }

    public function countMandates(): int {
        return count($this->mandates);
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->messageId) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->mandates)) {
            $errors[] = 'Mindestens ein Mandat erforderlich';
        }

        foreach ($this->mandates as $index => $mandate) {
            if (strlen($mandate->getMandateId()) > 35) {
                $errors[] = "Mndt[$index]/MndtId darf maximal 35 Zeichen lang sein";
            }

            if (!$mandate->getCreditorSchemeId()) {
                $errors[] = "Mndt[$index] benötigt Gläubiger-ID (CdtrSchmeId)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
