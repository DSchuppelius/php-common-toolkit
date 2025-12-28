<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt9\Type940;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9\MtDocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * MT940 Document - Customer Statement Message.
 * 
 * Tagesendeauszug gemäß SWIFT-Standard.
 * Enthält vollständige Umsätze eines Tages mit Opening und Closing Balance.
 * 
 * Äquivalent zu CAMT.053 (Bank to Customer Statement).
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9\Type940
 */
class Document extends MtDocumentAbstract {
    private Balance $openingBalance;
    private Balance $closingBalance;
    private ?Balance $closingAvailableBalance;
    private ?Balance $forwardAvailableBalance;

    /** @var Transaction[] */
    private array $transactions = [];

    public function __construct(
        string $accountId,
        string $referenceId,
        string $statementNumber,
        Balance $openingBalance,
        Balance $closingBalance,
        array $transactions = [],
        ?Balance $closingAvailableBalance = null,
        ?Balance $forwardAvailableBalance = null,
        ?DateTimeImmutable $creationDateTime = null
    ) {
        parent::__construct(
            $accountId,
            $referenceId,
            $statementNumber,
            $openingBalance->getCurrency(),
            $creationDateTime
        );

        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->closingAvailableBalance = $closingAvailableBalance;
        $this->forwardAvailableBalance = $forwardAvailableBalance;
        $this->transactions = $transactions;
    }

    public function getMtType(): MtType {
        return MtType::MT940;
    }

    /**
     * Gibt den Opening Balance zurück.
     * Feld :60F: in SWIFT-Notation.
     */
    public function getOpeningBalance(): Balance {
        return $this->openingBalance;
    }

    /**
     * Gibt den Closing Balance zurück.
     * Feld :62F: in SWIFT-Notation.
     */
    public function getClosingBalance(): Balance {
        return $this->closingBalance;
    }

    /**
     * Gibt den Closing Available Balance zurück.
     * Feld :64: in SWIFT-Notation (optional).
     */
    public function getClosingAvailableBalance(): ?Balance {
        return $this->closingAvailableBalance;
    }

    /**
     * Gibt den Forward Available Balance zurück.
     * Feld :65: in SWIFT-Notation (optional).
     */
    public function getForwardAvailableBalance(): ?Balance {
        return $this->forwardAvailableBalance;
    }

    /**
     * Gibt alle Transaktionen zurück.
     * @return Transaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    /**
     * Fügt eine Transaktion hinzu.
     */
    public function addTransaction(Transaction $transaction): void {
        $this->transactions[] = $transaction;
    }

    public function countEntries(): int {
        return count($this->transactions);
    }

    /**
     * Berechnet die Summe aller Soll-Buchungen.
     */
    public function getTotalDebit(): float {
        return array_reduce(
            $this->transactions,
            fn(float $sum, Transaction $txn) => $sum + ($txn->isDebit() ? $txn->getAmount() : 0),
            0.0
        );
    }

    /**
     * Berechnet die Summe aller Haben-Buchungen.
     */
    public function getTotalCredit(): float {
        return array_reduce(
            $this->transactions,
            fn(float $sum, Transaction $txn) => $sum + ($txn->isCredit() ? $txn->getAmount() : 0),
            0.0
        );
    }

    public function __toString(): string {
        $lines = [
            ':20:' . $this->referenceId,
            ':25:' . $this->accountId,
            ':28C:' . $this->statementNumber,
            ':60F:' . (string) $this->openingBalance,
        ];

        foreach ($this->transactions as $txn) {
            $lines[] = (string) $txn;
        }

        $lines[] = ':62F:' . (string) $this->closingBalance;

        if ($this->closingAvailableBalance !== null) {
            $lines[] = ':64:' . (string) $this->closingAvailableBalance;
        }

        if ($this->forwardAvailableBalance !== null) {
            $lines[] = ':65:' . (string) $this->forwardAvailableBalance;
        }

        $lines[] = '-';

        return implode("\r\n", $lines) . "\r\n";
    }
}
