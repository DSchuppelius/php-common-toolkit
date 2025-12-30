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

namespace CommonToolkit\Entities\Common\Banking\Pain\Type001;

use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\Common\Banking\PainType;
use DateTimeImmutable;

/**
 * pain.001 Document - Customer Credit Transfer Initiation.
 * 
 * Überweisungsauftrag gemäß ISO 20022.
 * Äquivalent zu SWIFT MT101/MT103.
 * 
 * Struktur:
 * - CstmrCdtTrfInitn (Customer Credit Transfer Initiation)
 *   - GrpHdr: Nachrichten-Header
 *   - PmtInf[]: Payment Instructions mit Transaktionen
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type001
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
     * Gibt den Nachrichten-Typ zurück.
     */
    public function getType(): PainType {
        return PainType::PAIN_001;
    }

    /**
     * Gibt den Group Header zurück.
     */
    public function getGroupHeader(): GroupHeader {
        return $this->groupHeader;
    }

    /**
     * Gibt alle Payment Instructions zurück.
     * @return PaymentInstruction[]
     */
    public function getPaymentInstructions(): array {
        return $this->paymentInstructions;
    }

    /**
     * Fügt eine Payment Instruction hinzu.
     */
    public function addPaymentInstruction(PaymentInstruction $instruction): void {
        $this->paymentInstructions[] = $instruction;
        $this->updateGroupHeader();
    }

    /**
     * Gibt die Gesamtanzahl der Transaktionen zurück.
     */
    public function countTransactions(): int {
        return array_reduce(
            $this->paymentInstructions,
            fn(int $sum, PaymentInstruction $pi) => $sum + $pi->countTransactions(),
            0
        );
    }

    /**
     * Berechnet die Kontrollsumme aller Transaktionen.
     */
    public function calculateControlSum(): float {
        return array_reduce(
            $this->paymentInstructions,
            fn(float $sum, PaymentInstruction $pi) => $sum + $pi->calculateControlSum(),
            0.0
        );
    }

    /**
     * Gibt alle Transaktionen als flache Liste zurück.
     * @return CreditTransferTransaction[]
     */
    public function getAllTransactions(): array {
        $transactions = [];
        foreach ($this->paymentInstructions as $instruction) {
            $transactions = array_merge($transactions, $instruction->getTransactions());
        }
        return $transactions;
    }

    /**
     * Aktualisiert den GroupHeader mit berechneten Werten.
     */
    private function updateGroupHeader(): void {
        $this->groupHeader = $this->groupHeader
            ->withTransactionCount($this->countTransactions())
            ->withControlSum($this->calculateControlSum());
    }

    /**
     * Erstellt ein neues pain.001 Dokument.
     */
    public static function create(
        string $messageId,
        PartyIdentification $initiatingParty,
        array $paymentInstructions = []
    ): self {
        $totalTransactions = array_reduce(
            $paymentInstructions,
            fn(int $sum, PaymentInstruction $pi) => $sum + $pi->countTransactions(),
            0
        );

        $controlSum = array_reduce(
            $paymentInstructions,
            fn(float $sum, PaymentInstruction $pi) => $sum + $pi->calculateControlSum(),
            0.0
        );

        $groupHeader = new GroupHeader(
            messageId: $messageId,
            creationDateTime: new DateTimeImmutable(),
            numberOfTransactions: $totalTransactions,
            initiatingParty: $initiatingParty,
            controlSum: $controlSum
        );

        return new self($groupHeader, $paymentInstructions);
    }

    /**
     * Validiert das Dokument.
     * 
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(): array {
        $errors = [];

        // GroupHeader Validierung
        if (strlen($this->groupHeader->getMessageId()) > 35) {
            $errors[] = 'MsgId darf maximal 35 Zeichen lang sein';
        }

        if (!$this->groupHeader->getInitiatingParty()->isValid()) {
            $errors[] = 'InitgPty muss mindestens Name oder ID enthalten';
        }

        // Payment Instructions Validierung
        if (empty($this->paymentInstructions)) {
            $errors[] = 'Mindestens eine Payment Instruction erforderlich';
        }

        foreach ($this->paymentInstructions as $index => $instruction) {
            if (strlen($instruction->getPaymentInstructionId()) > 35) {
                $errors[] = "PmtInf[$index]/PmtInfId darf maximal 35 Zeichen lang sein";
            }

            if (empty($instruction->getTransactions())) {
                $errors[] = "PmtInf[$index] muss mindestens eine Transaktion enthalten";
            }

            foreach ($instruction->getTransactions() as $txnIndex => $txn) {
                if ($txn->getAmount() <= 0) {
                    $errors[] = "PmtInf[$index]/CdtTrfTxInf[$txnIndex]: Betrag muss größer als 0 sein";
                }

                if (!$txn->getCreditor()->isValid()) {
                    $errors[] = "PmtInf[$index]/CdtTrfTxInf[$txnIndex]: Cdtr muss Name oder ID enthalten";
                }
            }
        }

        // Kontrollsumme prüfen
        $calculatedSum = $this->calculateControlSum();
        if (
            $this->groupHeader->getControlSum() !== null
            && abs($this->groupHeader->getControlSum() - $calculatedSum) > 0.01
        ) {
            $errors[] = sprintf(
                'CtrlSum (%s) stimmt nicht mit berechneter Summe (%s) überein',
                number_format($this->groupHeader->getControlSum(), 2),
                number_format($calculatedSum, 2)
            );
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}