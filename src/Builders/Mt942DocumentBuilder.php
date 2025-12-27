<?php
/*
 * Created on   : Sun Jul 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt942DocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\CreditDebit;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Builder für MT942 Documents (Interim Transaction Report).
 * 
 * MT942 enthält untertägige Transaktionen seit dem letzten Report.
 * Opening Balance ist optional, Closing Balance ist Pflicht.
 * Äquivalent zu CAMT.052 (Bank to Customer Account Report).
 * 
 * @package CommonToolkit\Builders
 */
final class Mt942DocumentBuilder {
    private string $accountId;
    private string $referenceId = 'COMMON';
    private string $statementNumber = '00000';

    /** @var Transaction[] */
    private array $transactions = [];

    private ?Balance $openingBalance = null;
    private ?Balance $closingBalance = null;
    private ?float $floorLimitIndicator = null;
    private ?DateTimeImmutable $dateTimeIndicator = null;
    private ?DateTimeImmutable $creationDateTime = null;

    public function setAccountId(string $accountId): self {
        $clone = clone $this;
        $clone->accountId = $accountId;
        return $clone;
    }

    public function setReferenceId(string $referenceId): self {
        $clone = clone $this;
        $clone->referenceId = $referenceId;
        return $clone;
    }

    public function setStatementNumber(string $statementNumber): self {
        $clone = clone $this;
        $clone->statementNumber = $statementNumber;
        return $clone;
    }

    public function addTransaction(Transaction $transaction): self {
        $clone = clone $this;
        $clone->transactions[] = $transaction;
        return $clone;
    }

    /**
     * Fügt mehrere Transaktionen hinzu.
     * 
     * @param Transaction[] $transactions
     * @throws InvalidArgumentException Wenn ein Element kein Transaction-Objekt ist
     */
    public function addTransactions(array $transactions): self {
        $clone = clone $this;
        foreach ($transactions as $transaction) {
            if (!$transaction instanceof Transaction) {
                throw new InvalidArgumentException('Alle Elemente müssen vom Typ Transaction sein.');
            }
            $clone->transactions[] = $transaction;
        }
        return $clone;
    }

    public function setOpeningBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->openingBalance = $balance;
        return $clone;
    }

    public function setClosingBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->closingBalance = $balance;
        return $clone;
    }

    /**
     * Setzt den Floor Limit Indicator (Feld :34F:).
     * Transaktionen unter diesem Betrag werden nicht einzeln gemeldet.
     */
    public function setFloorLimitIndicator(float $limit): self {
        $clone = clone $this;
        $clone->floorLimitIndicator = $limit;
        return $clone;
    }

    /**
     * Setzt den DateTime Indicator (Feld :13D:).
     * Zeitpunkt der Report-Erstellung.
     */
    public function setDateTimeIndicator(DateTimeImmutable $dateTime): self {
        $clone = clone $this;
        $clone->dateTimeIndicator = $dateTime;
        return $clone;
    }

    public function setCreationDateTime(DateTimeImmutable $dateTime): self {
        $clone = clone $this;
        $clone->creationDateTime = $dateTime;
        return $clone;
    }

    /**
     * Erstellt das MT942 Document.
     * 
     * Closing Balance ist bei MT942 Pflicht. Opening Balance ist optional.
     * Wenn nur Opening Balance angegeben ist, wird Closing Balance berechnet.
     * 
     * @throws RuntimeException Wenn accountId oder Closing Balance fehlt
     */
    public function build(): Document {
        if (!isset($this->accountId) || empty($this->accountId)) {
            throw new RuntimeException("AccountId muss angegeben werden.");
        }

        if (!$this->closingBalance && !$this->openingBalance) {
            throw new RuntimeException("Mindestens ein Saldo (Opening oder Closing) muss angegeben werden.");
        }

        $closing = $this->closingBalance;
        $opening = $this->openingBalance;

        // Bei MT942: Wenn nur Opening gegeben, berechne Closing
        if (!$closing && $opening) {
            $closing = $this->calculateClosingBalance($opening);
        }
        // Wenn nur Closing gegeben, berechne Opening rückwärts
        elseif (!$opening && $closing) {
            $opening = $this->reverseCalculateBalance($closing);
        }
        // Wenn beide gegeben, validiere Konsistenz
        elseif ($opening && $closing) {
            $expected = $this->calculateClosingBalance($opening);
            if ((string) $expected !== (string) $closing) {
                throw new RuntimeException("Opening- und Closing-Salden stimmen nicht überein. Erwartet: " . $expected);
            }
        }

        return new Document(
            $this->accountId,
            $this->referenceId,
            $this->statementNumber,
            $closing,
            $this->transactions,
            $opening,
            $this->floorLimitIndicator,
            $this->dateTimeIndicator,
            $this->creationDateTime
        );
    }

    private function calculateClosingBalance(Balance $opening): Balance {
        $total = $opening->isDebit() ? -$opening->getAmount() : $opening->getAmount();

        foreach ($this->transactions as $txn) {
            $sign = $txn->getCreditDebit() === CreditDebit::CREDIT ? 1 : -1;
            $total += $sign * $txn->getAmount();
        }

        $direction = $total >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;
        return new Balance($direction, $opening->getDate(), $opening->getCurrency(), abs($total));
    }

    private function reverseCalculateBalance(Balance $closing): Balance {
        $total = $closing->isDebit() ? -$closing->getAmount() : $closing->getAmount();

        foreach ($this->transactions as $txn) {
            $sign = $txn->getCreditDebit() === CreditDebit::CREDIT ? 1 : -1;
            $total -= $sign * $txn->getAmount();
        }

        $direction = $total >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;
        return new Balance($direction, $closing->getDate(), $closing->getCurrency(), abs($total));
    }
}
