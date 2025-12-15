<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt053;

use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use InvalidArgumentException;

class Document {
    private string $statementId;
    private DateTimeImmutable $creationDateTime;
    private string $accountIban;
    private CurrencyCode $currency;

    /** @var Camt053Transaction[] */
    private array $transactions = [];

    public function __construct(string $statementId, DateTimeImmutable|string $creationDateTime, string $accountIban, CurrencyCode|string $currency, array $transactions = []) {
        $this->statementId = $statementId;

        $this->creationDateTime = $creationDateTime instanceof DateTimeImmutable
            ? $creationDateTime
            : new DateTimeImmutable($creationDateTime);

        $this->accountIban = $accountIban;

        $this->currency = $currency instanceof CurrencyCode
            ? $currency
            : CurrencyCode::tryFrom(strtoupper($currency))
            ?? throw new InvalidArgumentException("Ungültige Währung: $currency");

        foreach ($transactions as $txn) {
            $this->addTransaction($txn);
        }
    }

    public function addTransaction(Transaction $transaction): void {
        $this->transactions[] = $transaction;
    }

    public function getStatementId(): string {
        return $this->statementId;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getAccountIban(): string {
        return $this->accountIban;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    public function countTransactions(): int {
        return count($this->transactions);
    }

    public function withTransaction(Transaction $transaction): self {
        $clone = clone $this;
        $clone->transactions[] = $transaction;
        return $clone;
    }

    public function withTransactions(array $transactions): self {
        $clone = clone $this;
        $clone->transactions = [...$clone->transactions, ...$transactions];
        return $clone;
    }
}