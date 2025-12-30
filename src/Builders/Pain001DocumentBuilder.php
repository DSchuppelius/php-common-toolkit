<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain001DocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\CreditTransferTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\PaymentInstruction;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use RuntimeException;

/**
 * Builder für pain.001 Documents (Customer Credit Transfer Initiation).
 * 
 * Erstellt Überweisungsaufträge gemäß ISO 20022.
 * Unterstützt sowohl SEPA-konforme als auch nicht-SEPA-Überweisungen.
 * 
 * Struktur:
 * - GroupHeader: Nachrichten-Metadaten
 * - PaymentInstruction[]: Zahlungsanweisungen mit Transaktionen
 * 
 * @package CommonToolkit\Builders
 */
final class Pain001DocumentBuilder {
    private string $messageId;
    private DateTimeImmutable $creationDateTime;
    private PartyIdentification $initiatingParty;
    private ?FinancialInstitution $forwardingAgent = null;

    /** @var PaymentInstruction[] */
    private array $paymentInstructions = [];

    /** 
     * Aktive PaymentInstruction die gerade gebaut wird
     * @var PaymentInstructionBuilder|null 
     */
    private ?PaymentInstructionBuilder $currentInstructionBuilder = null;

    public function __construct() {
        $this->creationDateTime = new DateTimeImmutable();
    }

    /**
     * Setzt die Nachrichten-ID (MsgId).
     * Max. 35 Zeichen, muss eindeutig sein.
     */
    public function setMessageId(string $messageId): self {
        $clone = clone $this;
        $clone->messageId = $messageId;
        return $clone;
    }

    /**
     * Setzt den Erstellungszeitpunkt (CreDtTm).
     */
    public function setCreationDateTime(DateTimeImmutable $dateTime): self {
        $clone = clone $this;
        $clone->creationDateTime = $dateTime;
        return $clone;
    }

    /**
     * Setzt die initiierende Partei (InitgPty).
     * Der Auftraggeber der gesamten Nachricht.
     */
    public function setInitiatingParty(PartyIdentification $party): self {
        $clone = clone $this;
        $clone->initiatingParty = $party;
        return $clone;
    }

    /**
     * Setzt den Forwarding Agent (FwdgAgt).
     * Bank, die die Nachricht weiterleitet.
     */
    public function setForwardingAgent(FinancialInstitution $agent): self {
        $clone = clone $this;
        $clone->forwardingAgent = $agent;
        return $clone;
    }

    /**
     * Fügt eine fertige PaymentInstruction hinzu.
     */
    public function addPaymentInstruction(PaymentInstruction $instruction): self {
        $clone = clone $this;
        $clone->paymentInstructions[] = $instruction;
        return $clone;
    }

    /**
     * Startet eine neue PaymentInstruction.
     * Nutze addTransaction() und endPaymentInstruction() zum Fertigstellen.
     */
    public function beginPaymentInstruction(
        string $paymentInstructionId,
        PartyIdentification $debtor,
        AccountIdentification $debtorAccount,
        ?FinancialInstitution $debtorAgent = null
    ): self {
        $clone = clone $this;
        $clone->currentInstructionBuilder = new PaymentInstructionBuilder(
            $paymentInstructionId,
            $debtor,
            $debtorAccount,
            $debtorAgent
        );
        return $clone;
    }

    /**
     * Fügt eine Transaktion zur aktuellen PaymentInstruction hinzu.
     * 
     * @throws RuntimeException Wenn keine PaymentInstruction begonnen wurde
     */
    public function addTransaction(CreditTransferTransaction $transaction): self {
        if ($this->currentInstructionBuilder === null) {
            throw new RuntimeException(
                "Keine PaymentInstruction aktiv. Rufe beginPaymentInstruction() zuerst auf."
            );
        }

        $clone = clone $this;
        $clone->currentInstructionBuilder = $this->currentInstructionBuilder->addTransaction($transaction);
        return $clone;
    }

    /**
     * Setzt die Zahlungsmethode für die aktuelle PaymentInstruction.
     */
    public function setPaymentMethod(PaymentMethod $method): self {
        if ($this->currentInstructionBuilder === null) {
            throw new RuntimeException("Keine PaymentInstruction aktiv.");
        }

        $clone = clone $this;
        $clone->currentInstructionBuilder = $this->currentInstructionBuilder->setPaymentMethod($method);
        return $clone;
    }

    /**
     * Setzt das angeforderte Ausführungsdatum für die aktuelle PaymentInstruction.
     */
    public function setRequestedExecutionDate(DateTimeImmutable $date): self {
        if ($this->currentInstructionBuilder === null) {
            throw new RuntimeException("Keine PaymentInstruction aktiv.");
        }

        $clone = clone $this;
        $clone->currentInstructionBuilder = $this->currentInstructionBuilder->setRequestedExecutionDate($date);
        return $clone;
    }

    /**
     * Setzt den ChargesCode für die aktuelle PaymentInstruction.
     */
    public function setChargesCode(ChargesCode $code): self {
        if ($this->currentInstructionBuilder === null) {
            throw new RuntimeException("Keine PaymentInstruction aktiv.");
        }

        $clone = clone $this;
        $clone->currentInstructionBuilder = $this->currentInstructionBuilder->setChargesCode($code);
        return $clone;
    }

    /**
     * Beendet die aktuelle PaymentInstruction und fügt sie zum Dokument hinzu.
     * 
     * @throws RuntimeException Wenn keine PaymentInstruction aktiv ist
     */
    public function endPaymentInstruction(): self {
        if ($this->currentInstructionBuilder === null) {
            throw new RuntimeException("Keine PaymentInstruction aktiv.");
        }

        $clone = clone $this;
        $clone->paymentInstructions[] = $this->currentInstructionBuilder->build();
        $clone->currentInstructionBuilder = null;
        return $clone;
    }

    /**
     * Erstellt das pain.001 Document.
     * 
     * @throws RuntimeException Wenn Pflichtfelder fehlen
     */
    public function build(): Document {
        if (!isset($this->messageId) || empty($this->messageId)) {
            throw new RuntimeException("MessageId muss angegeben werden.");
        }

        if (!isset($this->initiatingParty)) {
            throw new RuntimeException("InitiatingParty muss angegeben werden.");
        }

        // Falls noch eine PaymentInstruction offen ist, schließen
        $instructions = $this->paymentInstructions;
        if ($this->currentInstructionBuilder !== null) {
            $instructions[] = $this->currentInstructionBuilder->build();
        }

        if (empty($instructions)) {
            throw new RuntimeException("Mindestens eine PaymentInstruction ist erforderlich.");
        }

        // Anzahl und Summe berechnen
        $totalTransactions = array_reduce(
            $instructions,
            fn(int $sum, PaymentInstruction $pi) => $sum + $pi->countTransactions(),
            0
        );

        $controlSum = array_reduce(
            $instructions,
            fn(float $sum, PaymentInstruction $pi) => $sum + $pi->calculateControlSum(),
            0.0
        );

        $groupHeader = new GroupHeader(
            messageId: $this->messageId,
            creationDateTime: $this->creationDateTime,
            numberOfTransactions: $totalTransactions,
            initiatingParty: $this->initiatingParty,
            controlSum: $controlSum,
            forwardingAgent: $this->forwardingAgent
        );

        return new Document($groupHeader, $instructions);
    }

    /**
     * Erstellt eine einfache SEPA-Überweisung.
     * 
     * Convenience-Methode für einfache Überweisungen mit einer Transaktion.
     * 
     * @param string $messageId Eindeutige Nachrichten-ID
     * @param string $initiatorName Name des Auftraggebers
     * @param string $debtorIban IBAN des Auftraggebers
     * @param string $debtorBic BIC der Bank des Auftraggebers
     * @param string $creditorName Name des Empfängers
     * @param string $creditorIban IBAN des Empfängers
     * @param float $amount Betrag in EUR
     * @param string $reference Verwendungszweck
     */
    public static function createSepaTransfer(
        string $messageId,
        string $initiatorName,
        string $debtorIban,
        string $debtorBic,
        string $creditorName,
        string $creditorIban,
        float $amount,
        string $reference
    ): Document {
        $initiatingParty = new PartyIdentification(name: $initiatorName);

        $creditor = new PartyIdentification(name: $creditorName);
        $creditorAccount = new AccountIdentification(iban: $creditorIban);

        $paymentId = PaymentIdentification::create($messageId . '-PMTINF1-TXN1');
        $remittanceInfo = RemittanceInformation::fromText($reference);

        $transaction = new CreditTransferTransaction(
            paymentId: $paymentId,
            amount: $amount,
            currency: CurrencyCode::Euro,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            remittanceInformation: $remittanceInfo
        );

        $paymentInstruction = PaymentInstruction::sepa(
            paymentInstructionId: $messageId . '-PMTINF1',
            executionDate: new DateTimeImmutable(),
            debtorName: $initiatorName,
            debtorIban: $debtorIban,
            debtorBic: $debtorBic,
            transactions: [$transaction]
        );

        return Document::create($messageId, $initiatingParty, [$paymentInstruction]);
    }
}

/**
 * Hilfsbuilder für PaymentInstruction.
 * Intern verwendet von Pain001DocumentBuilder.
 */
final class PaymentInstructionBuilder {
    private PaymentMethod $paymentMethod = PaymentMethod::TRANSFER;
    private DateTimeImmutable $requestedExecutionDate;
    private ChargesCode $chargesCode = ChargesCode::SLEV;

    /** @var CreditTransferTransaction[] */
    private array $transactions = [];

    public function __construct(
        private string $paymentInstructionId,
        private PartyIdentification $debtor,
        private AccountIdentification $debtorAccount,
        private ?FinancialInstitution $debtorAgent = null
    ) {
        $this->requestedExecutionDate = new DateTimeImmutable();
    }

    public function setPaymentMethod(PaymentMethod $method): self {
        $clone = clone $this;
        $clone->paymentMethod = $method;
        return $clone;
    }

    public function setRequestedExecutionDate(DateTimeImmutable $date): self {
        $clone = clone $this;
        $clone->requestedExecutionDate = $date;
        return $clone;
    }

    public function setChargesCode(ChargesCode $code): self {
        $clone = clone $this;
        $clone->chargesCode = $code;
        return $clone;
    }

    public function addTransaction(CreditTransferTransaction $transaction): self {
        $clone = clone $this;
        $clone->transactions[] = $transaction;
        return $clone;
    }

    public function build(): PaymentInstruction {
        if (empty($this->transactions)) {
            throw new RuntimeException("PaymentInstruction muss mindestens eine Transaktion enthalten.");
        }

        return new PaymentInstruction(
            paymentInstructionId: $this->paymentInstructionId,
            paymentMethod: $this->paymentMethod,
            requestedExecutionDate: $this->requestedExecutionDate,
            debtor: $this->debtor,
            debtorAccount: $this->debtorAccount,
            debtorAgent: $this->debtorAgent ?? new FinancialInstitution(),
            transactions: $this->transactions,
            chargeBearer: $this->chargesCode
        );
    }
}