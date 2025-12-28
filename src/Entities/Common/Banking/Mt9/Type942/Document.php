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

namespace CommonToolkit\Entities\Common\Banking\Mt9\Type942;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9\MtDocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * MT942 Document - Interim Transaction Report.
 * 
 * Untertägige Umsatzinformation gemäß SWIFT-Standard.
 * Enthält Transaktionen seit dem letzten Report mit Interim-Salden.
 * 
 * Äquivalent zu CAMT.052 (Bank to Customer Account Report).
 * 
 * Unterschiede zu MT940:
 * - Verwendet :60M: (Interim) statt :60F: (Final) für Opening Balance
 * - Verwendet :62M: (Interim) statt :62F: (Final) für Closing Balance
 * - Kann mehrmals täglich erstellt werden
 * - Enthält Feld :34F: für Floor Limit Indicator
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9\Type942
 */
class Document extends MtDocumentAbstract {
    private ?Balance $openingBalance;
    private Balance $closingBalance;
    private ?float $floorLimitIndicator;
    private ?DateTimeImmutable $dateTimeIndicator;

    /** @var Transaction[] */
    private array $transactions = [];

    public function __construct(
        string $accountId,
        string $referenceId,
        string $statementNumber,
        Balance $closingBalance,
        array $transactions = [],
        ?Balance $openingBalance = null,
        ?float $floorLimitIndicator = null,
        ?DateTimeImmutable $dateTimeIndicator = null,
        ?DateTimeImmutable $creationDateTime = null
    ) {
        parent::__construct(
            $accountId,
            $referenceId,
            $statementNumber,
            $closingBalance->getCurrency(),
            $creationDateTime
        );

        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->floorLimitIndicator = $floorLimitIndicator;
        $this->dateTimeIndicator = $dateTimeIndicator;
        $this->transactions = $transactions;
    }

    public function getMtType(): MtType {
        return MtType::MT942;
    }

    /**
     * Gibt den Opening Balance zurück (optional bei MT942).
     * Feld :60M: in SWIFT-Notation.
     */
    public function getOpeningBalance(): ?Balance {
        return $this->openingBalance;
    }

    /**
     * Gibt den Closing Balance zurück.
     * Feld :62M: in SWIFT-Notation.
     */
    public function getClosingBalance(): Balance {
        return $this->closingBalance;
    }

    /**
     * Gibt den Floor Limit Indicator zurück.
     * Feld :34F: - Transaktionen unter diesem Betrag werden nicht einzeln gemeldet.
     */
    public function getFloorLimitIndicator(): ?float {
        return $this->floorLimitIndicator;
    }

    /**
     * Gibt den DateTime Indicator zurück.
     * Feld :13D: - Zeitpunkt der Erstellung des Reports.
     */
    public function getDateTimeIndicator(): ?DateTimeImmutable {
        return $this->dateTimeIndicator;
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
     * Feld :90D: Anzahl und Summe der Soll-Buchungen.
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
     * Feld :90C: Anzahl und Summe der Haben-Buchungen.
     */
    public function getTotalCredit(): float {
        return array_reduce(
            $this->transactions,
            fn(float $sum, Transaction $txn) => $sum + ($txn->isCredit() ? $txn->getAmount() : 0),
            0.0
        );
    }

    /**
     * Zählt die Soll-Buchungen.
     */
    public function countDebitEntries(): int {
        return count(array_filter($this->transactions, fn(Transaction $txn) => $txn->isDebit()));
    }

    /**
     * Zählt die Haben-Buchungen.
     */
    public function countCreditEntries(): int {
        return count(array_filter($this->transactions, fn(Transaction $txn) => $txn->isCredit()));
    }

    public function __toString(): string {
        $lines = [
            ':20:' . $this->referenceId,
            ':25:' . $this->accountId,
            ':28C:' . $this->statementNumber,
        ];

        // DateTime Indicator (optional)
        if ($this->dateTimeIndicator !== null) {
            $lines[] = ':13D:' . $this->dateTimeIndicator->format('ymdHi') . '+0000';
        }

        // Floor Limit Indicator (optional)
        if ($this->floorLimitIndicator !== null) {
            $lines[] = ':34F:' . $this->currency->value . number_format($this->floorLimitIndicator, 2, ',', '');
        }

        // Opening Balance (optional, Interim)
        if ($this->openingBalance !== null) {
            $lines[] = ':60M:' . (string) $this->openingBalance;
        }

        // Transactions
        foreach ($this->transactions as $txn) {
            $lines[] = (string) $txn;
        }

        // Summary fields
        $debitCount = $this->countDebitEntries();
        $creditCount = $this->countCreditEntries();

        if ($debitCount > 0) {
            $lines[] = sprintf(':90D:%d%s%s', $debitCount, $this->currency->value, number_format($this->getTotalDebit(), 2, ',', ''));
        }
        if ($creditCount > 0) {
            $lines[] = sprintf(':90C:%d%s%s', $creditCount, $this->currency->value, number_format($this->getTotalCredit(), 2, ',', ''));
        }

        // Closing Balance (Interim)
        $lines[] = ':62M:' . (string) $this->closingBalance;

        $lines[] = '-';

        return implode("\r\n", $lines) . "\r\n";
    }
}
