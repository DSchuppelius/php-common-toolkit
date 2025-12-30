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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type001;

use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use DateTimeImmutable;

/**
 * Group Header für pain.001-Nachrichten (GrpHdr).
 * 
 * Enthält Metadaten für die gesamte Nachricht:
 * - MsgId: Eindeutige Nachrichten-ID
 * - CreDtTm: Erstellungszeitpunkt
 * - NbOfTxs: Anzahl der Transaktionen
 * - CtrlSum: Kontrollsumme (optional)
 * - InitgPty: Initiierender (auftraggebende Partei)
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type001
 */
final readonly class GroupHeader {
    public function __construct(
        private string $messageId,
        private DateTimeImmutable $creationDateTime,
        private int $numberOfTransactions,
        private PartyIdentification $initiatingParty,
        private ?float $controlSum = null,
        private ?FinancialInstitution $forwardingAgent = null
    ) {
    }

    /**
     * Gibt die Nachrichten-ID zurück (MsgId).
     * Max. 35 Zeichen.
     */
    public function getMessageId(): string {
        return $this->messageId;
    }

    /**
     * Gibt den Erstellungszeitpunkt zurück (CreDtTm).
     */
    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    /**
     * Gibt die Anzahl der Transaktionen zurück (NbOfTxs).
     */
    public function getNumberOfTransactions(): int {
        return $this->numberOfTransactions;
    }

    /**
     * Gibt die Kontrollsumme zurück (CtrlSum).
     * Summe aller Beträge in der Nachricht.
     */
    public function getControlSum(): ?float {
        return $this->controlSum;
    }

    /**
     * Gibt die initiierende Partei zurück (InitgPty).
     * Der Auftraggeber der gesamten Nachricht.
     */
    public function getInitiatingParty(): PartyIdentification {
        return $this->initiatingParty;
    }

    /**
     * Gibt den Forwarding Agent zurück (FwdgAgt).
     * Bank, die die Nachricht weiterleitet.
     */
    public function getForwardingAgent(): ?FinancialInstitution {
        return $this->forwardingAgent;
    }

    /**
     * Erstellt einen neuen GroupHeader mit aktualisierter Transaktionsanzahl.
     */
    public function withTransactionCount(int $count): self {
        return new self(
            messageId: $this->messageId,
            creationDateTime: $this->creationDateTime,
            numberOfTransactions: $count,
            initiatingParty: $this->initiatingParty,
            controlSum: $this->controlSum,
            forwardingAgent: $this->forwardingAgent
        );
    }

    /**
     * Erstellt einen neuen GroupHeader mit aktualisierter Kontrollsumme.
     */
    public function withControlSum(float $sum): self {
        return new self(
            messageId: $this->messageId,
            creationDateTime: $this->creationDateTime,
            numberOfTransactions: $this->numberOfTransactions,
            initiatingParty: $this->initiatingParty,
            controlSum: $sum,
            forwardingAgent: $this->forwardingAgent
        );
    }

    /**
     * Erstellt einen einfachen GroupHeader.
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
            initiatingParty: $initiatingParty,
            controlSum: $controlSum
        );
    }
}
