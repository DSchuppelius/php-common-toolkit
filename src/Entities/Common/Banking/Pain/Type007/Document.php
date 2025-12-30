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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type007;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.007 Document - Customer Payment Reversal.
 * 
 * Rückruf von Lastschriften (Lastschriftstorno) gemäß ISO 20022.
 * Antwort/Korrektur zu pain.008 (Lastschriften).
 * 
 * Struktur:
 * - CstmrPmtRvsl (Customer Payment Reversal)
 *   - GrpHdr: Nachrichten-Header
 *   - OrgnlGrpInf: Referenz zur Original-Nachricht
 *   - OrgnlPmtInfAndRvsl[]: Stornierungsinformationen
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type007
 */
final class Document {
    /** @var OriginalPaymentInformation[] */
    private array $originalPaymentInformations = [];

    public function __construct(
        private GroupHeader $groupHeader,
        private OriginalGroupInformation $originalGroupInformation,
        array $originalPaymentInformations = []
    ) {
        $this->originalPaymentInformations = $originalPaymentInformations;
    }

    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        OriginalGroupInformation $originalGroupInfo,
        array $originalPaymentInformations = []
    ): self {
        $txCount = 0;
        $controlSum = 0.0;

        foreach ($originalPaymentInformations as $info) {
            $txCount += $info->countTransactions();
            $controlSum += $info->calculateReversalSum();
        }

        return new self(
            groupHeader: new GroupHeader(
                messageId: $messageId,
                creationDateTime: new DateTimeImmutable(),
                numberOfTransactions: $txCount,
                controlSum: $controlSum,
                initiatingParty: $initiatingParty
            ),
            originalGroupInformation: $originalGroupInfo,
            originalPaymentInformations: $originalPaymentInformations
        );
    }

    public function getType(): PainType {
        return PainType::PAIN_007;
    }

    public function getGroupHeader(): GroupHeader {
        return $this->groupHeader;
    }

    public function getOriginalGroupInformation(): OriginalGroupInformation {
        return $this->originalGroupInformation;
    }

    /**
     * @return OriginalPaymentInformation[]
     */
    public function getOriginalPaymentInformations(): array {
        return $this->originalPaymentInformations;
    }

    public function addOriginalPaymentInformation(OriginalPaymentInformation $info): self {
        $clone = clone $this;
        $clone->originalPaymentInformations[] = $info;
        return $clone;
    }

    public function countTransactions(): int {
        return array_sum(array_map(
            fn($i) => $i->countTransactions(),
            $this->originalPaymentInformations
        ));
    }

    public function calculateReversalSum(): float {
        return array_sum(array_map(
            fn($i) => $i->calculateReversalSum(),
            $this->originalPaymentInformations
        ));
    }

    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        if (strlen($this->groupHeader->getMessageId()) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (strlen($this->originalGroupInformation->getOriginalMessageId()) > 35) {
            $errors[] = 'OrgnlMsgId darf maximal 35 Zeichen lang sein';
        }

        if (empty($this->originalPaymentInformations)) {
            $errors[] = 'Mindestens eine OriginalPaymentInformation erforderlich';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
