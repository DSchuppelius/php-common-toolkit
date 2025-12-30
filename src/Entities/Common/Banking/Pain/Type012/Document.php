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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type012;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.012 Document - Mandate Acceptance Report.
 * 
 * Bestätigung/Ablehnung von Mandatsanfragen durch die Bank.
 * Antwort auf pain.009 (Initiation), pain.010 (Amendment), pain.011 (Cancellation).
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type012
 */
final class Document {
    /** @var MandateAcceptance[] */
    private array $mandateAcceptances = [];

    public function __construct(
        private readonly string $messageId,
        private readonly DateTimeImmutable $creationDateTime,
        private readonly ?PartyIdentification $initiatingParty = null,
        private readonly ?string $originalMessageId = null,
        private readonly ?string $originalMessageNameId = null,
        array $mandateAcceptances = []
    ) {
        $this->mandateAcceptances = $mandateAcceptances;
    }

    public static function create(
        string $messageId,
        string $originalMessageId,
        string $originalMessageNameId,
        array $mandateAcceptances = [],
        ?PartyIdentification $initiatingParty = null
    ): self {
        return new self(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            initiatingParty: $initiatingParty,
            originalMessageId: $originalMessageId,
            originalMessageNameId: $originalMessageNameId,
            mandateAcceptances: $mandateAcceptances
        );
    }

    /**
     * Factory für pain.009 Antwort.
     */
    public static function forPain009(
        string $messageId,
        string $originalMessageId,
        array $mandateAcceptances = []
    ): self {
        return self::create($messageId, $originalMessageId, 'pain.009.001.08', $mandateAcceptances);
    }

    /**
     * Factory für pain.010 Antwort.
     */
    public static function forPain010(
        string $messageId,
        string $originalMessageId,
        array $mandateAcceptances = []
    ): self {
        return self::create($messageId, $originalMessageId, 'pain.010.001.08', $mandateAcceptances);
    }

    /**
     * Factory für pain.011 Antwort.
     */
    public static function forPain011(
        string $messageId,
        string $originalMessageId,
        array $mandateAcceptances = []
    ): self {
        return self::create($messageId, $originalMessageId, 'pain.011.001.08', $mandateAcceptances);
    }

    public function getType(): PainType {
        return PainType::PAIN_012;
    }

    public function getMessageId(): string {
        return $this->messageId;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getInitiatingParty(): ?PartyIdentification {
        return $this->initiatingParty;
    }

    public function getOriginalMessageId(): ?string {
        return $this->originalMessageId;
    }

    public function getOriginalMessageNameId(): ?string {
        return $this->originalMessageNameId;
    }

    /**
     * @return MandateAcceptance[]
     */
    public function getMandateAcceptances(): array {
        return $this->mandateAcceptances;
    }

    public function addMandateAcceptance(MandateAcceptance $acceptance): self {
        $clone = clone $this;
        $clone->mandateAcceptances[] = $acceptance;
        return $clone;
    }

    public function countAcceptances(): int {
        return count($this->mandateAcceptances);
    }

    public function hasRejections(): bool {
        foreach ($this->mandateAcceptances as $acceptance) {
            if ($acceptance->isRejected()) {
                return true;
            }
        }
        return false;
    }

    public function isFullyAccepted(): bool {
        foreach ($this->mandateAcceptances as $acceptance) {
            if (!$acceptance->isAccepted()) {
                return false;
            }
        }
        return count($this->mandateAcceptances) > 0;
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->messageId) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->mandateAcceptances)) {
            $errors[] = 'Mindestens eine Mandatsbestätigung erforderlich';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
