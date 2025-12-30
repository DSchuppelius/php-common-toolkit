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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type008;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.008 Document - Customer Direct Debit Initiation.
 * 
 * Lastschrift-Einreichung gemäß ISO 20022.
 * 
 * Struktur:
 * - CstmrDrctDbtInitn (Customer Direct Debit Initiation)
 *   - GrpHdr: Nachrichten-Header
 *   - PmtInf[]: Payment Instructions mit Lastschriften
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type008
 */
final class Document {
    /** @var PaymentInstruction[] */
    private array $paymentInstructions = [];

    public function __construct(
        private GroupHeader $groupHeader,
        array $paymentInstructions = []
    ) {
        $this->paymentInstructions = $paymentInstructions;
    }

    /**
     * Factory-Methode.
     */
    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        array $paymentInstructions = []
    ): self {
        $txCount = 0;
        $controlSum = 0.0;

        foreach ($paymentInstructions as $instruction) {
            $txCount += $instruction->countTransactions();
            $controlSum += $instruction->calculateControlSum();
        }

        return new self(
            groupHeader: new GroupHeader(
                messageId: $messageId,
                creationDateTime: new DateTimeImmutable(),
                numberOfTransactions: $txCount,
                controlSum: $controlSum,
                initiatingParty: $initiatingParty
            ),
            paymentInstructions: $paymentInstructions
        );
    }

    /**
     * Gibt den Nachrichtentyp zurück.
     */
    public function getType(): PainType {
        return PainType::PAIN_008;
    }

    public function getGroupHeader(): GroupHeader {
        return $this->groupHeader;
    }

    /**
     * @return PaymentInstruction[]
     */
    public function getPaymentInstructions(): array {
        return $this->paymentInstructions;
    }

    /**
     * Fügt eine PaymentInstruction hinzu.
     */
    public function addPaymentInstruction(PaymentInstruction $instruction): self {
        $clone = clone $this;
        $clone->paymentInstructions[] = $instruction;
        return $clone;
    }

    /**
     * Zählt alle Transaktionen.
     */
    public function countTransactions(): int {
        return array_sum(array_map(
            fn($i) => $i->countTransactions(),
            $this->paymentInstructions
        ));
    }

    /**
     * Berechnet die Kontrollsumme.
     */
    public function calculateControlSum(): float {
        return array_sum(array_map(
            fn($i) => $i->calculateControlSum(),
            $this->paymentInstructions
        ));
    }

    /**
     * Gibt alle Transaktionen zurück.
     */
    public function getAllTransactions(): array {
        $transactions = [];

        foreach ($this->paymentInstructions as $instruction) {
            $transactions = array_merge($transactions, $instruction->getTransactions());
        }

        return $transactions;
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

        if (!$this->groupHeader->getInitiatingParty()->isValid()) {
            $errors[] = 'InitgPty muss mindestens Name oder ID enthalten';
        }

        if (empty($this->paymentInstructions)) {
            $errors[] = 'Mindestens eine Payment Instruction erforderlich';
        }

        foreach ($this->paymentInstructions as $index => $instruction) {
            if (strlen($instruction->getPaymentInstructionId()) > 35) {
                $errors[] = "PmtInf[$index]/PmtInfId darf maximal 35 Zeichen lang sein";
            }

            if (empty($instruction->getCreditorSchemeId())) {
                $errors[] = "PmtInf[$index] benötigt Creditor Scheme ID (Gläubiger-ID)";
            }

            if (empty($instruction->getTransactions())) {
                $errors[] = "PmtInf[$index] muss mindestens eine Transaktion enthalten";
            }

            foreach ($instruction->getTransactions() as $txnIndex => $txn) {
                if ($txn->getAmount() <= 0) {
                    $errors[] = "PmtInf[$index]/DrctDbtTxInf[$txnIndex]: Betrag muss größer als 0 sein";
                }

                if (!$txn->getDebtor()->isValid()) {
                    $errors[] = "PmtInf[$index]/DrctDbtTxInf[$txnIndex]: Dbtr muss Name oder ID enthalten";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
