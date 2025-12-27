<?php
/*
 * Created on   : Thu May 09 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940DocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\CreditDebit;
use InvalidArgumentException;
use RuntimeException;

final class Mt940DocumentBuilder {
    private string $accountId;
    private string $referenceId = 'COMMON';
    private string $statementNumber = '00000';

    /** @var Transaction[] */
    private array $transactions = [];

    private ?Balance $openingBalance = null;
    private ?Balance $closingBalance = null;
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

    public function addTransactions(array $transactions): self {
        foreach ($transactions as $transaction) {
            if (!$transaction instanceof Transaction) {
                throw new InvalidArgumentException('Alle Elemente müssen vom Typ Transaction sein.');
            }
            $this->transactions[] = $transaction;
        }
        return $this;
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

    public function build(): Document {
        if (!$this->openingBalance && !$this->closingBalance) {
            throw new RuntimeException("Mindestens ein Saldo (Opening oder Closing) muss angegeben werden.");
        }

        if (!$this->openingBalance) {
            $opening = $this->reverseCalculateBalance($this->closingBalance);
            $closing = $this->closingBalance;
        } elseif (!$this->closingBalance) {
            $opening = $this->openingBalance;
            $closing = $this->calculateClosingBalance($opening);
        } else {
            $opening = $this->openingBalance;
            $closing = $this->closingBalance;
            $expected = $this->calculateClosingBalance($opening);
            if ((string) $expected !== (string) $closing) {
                throw new RuntimeException("Opening- und Closing-Salden stimmen nicht überein. Erwartet: " . $expected);
            }
        }

        return new Document($this->accountId, $this->referenceId, $this->statementNumber, $opening, $closing, $this->transactions);
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
