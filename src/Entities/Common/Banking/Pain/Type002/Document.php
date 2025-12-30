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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.002 Document - Customer Payment Status Report.
 * 
 * Statusrückmeldung der Bank zu eingereichten Zahlungsaufträgen.
 * Antwort auf pain.001 (Überweisungen) oder pain.008 (Lastschriften).
 * 
 * Struktur:
 * - CstmrPmtStsRpt (Customer Payment Status Report)
 *   - GrpHdr: Nachrichten-Header
 *   - OrgnlGrpInfAndSts: Status der Original-Nachricht
 *   - OrgnlPmtInfAndSts[]: Status einzelner Payment Instructions
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
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

    /**
     * Factory-Methode.
     */
    public static function create(
        string $messageId,
        OriginalGroupInformation $originalGroupInfo,
        array $originalPaymentInformations = [],
        ?PartyIdentification $initiatingParty = null
    ): self {
        return new self(
            groupHeader: GroupHeader::create($messageId, $initiatingParty),
            originalGroupInformation: $originalGroupInfo,
            originalPaymentInformations: $originalPaymentInformations
        );
    }

    /**
     * Erstellt einen vollständig akzeptierten Status.
     */
    public static function allAccepted(
        string $messageId,
        string $originalMessageId,
        string $originalMessageName = 'pain.001.001.12'
    ): self {
        return new self(
            groupHeader: GroupHeader::create($messageId),
            originalGroupInformation: new OriginalGroupInformation(
                originalMessageId: $originalMessageId,
                originalMessageNameId: $originalMessageName,
                groupStatus: TransactionStatus::ACCEPTED_SETTLEMENT_COMPLETED
            )
        );
    }

    /**
     * Gibt den Nachrichtentyp zurück.
     */
    public function getType(): PainType {
        return PainType::PAIN_002;
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

    /**
     * Fügt eine Payment Information hinzu.
     */
    public function addOriginalPaymentInformation(OriginalPaymentInformation $info): self {
        $clone = clone $this;
        $clone->originalPaymentInformations[] = $info;
        return $clone;
    }

    /**
     * Prüft, ob alle Transaktionen akzeptiert wurden.
     */
    public function isFullyAccepted(): bool {
        // Gruppen-Status prüfen
        if ($this->originalGroupInformation->isGroupAccepted()) {
            return true;
        }

        if ($this->originalGroupInformation->isGroupRejected()) {
            return false;
        }

        // Einzelne Payment Informations prüfen
        foreach ($this->originalPaymentInformations as $pmtInfo) {
            if (!$pmtInfo->isFullyAccepted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prüft, ob es Ablehnungen gibt.
     */
    public function hasRejections(): bool {
        if ($this->originalGroupInformation->isGroupRejected()) {
            return true;
        }

        foreach ($this->originalPaymentInformations as $pmtInfo) {
            if ($pmtInfo->hasRejections()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt alle abgelehnten Transaktionen zurück.
     */
    public function getRejectedTransactions(): array {
        $rejected = [];

        foreach ($this->originalPaymentInformations as $pmtInfo) {
            $rejected = array_merge($rejected, $pmtInfo->getRejectedTransactions());
        }

        return $rejected;
    }

    /**
     * Zählt alle Transaktionsstatus.
     */
    public function countTransactionStatuses(): int {
        $count = 0;

        foreach ($this->originalPaymentInformations as $pmtInfo) {
            $count += $pmtInfo->countTransactionStatuses();
        }

        return $count;
    }

    /**
     * Validiert das Dokument.
     * 
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

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
