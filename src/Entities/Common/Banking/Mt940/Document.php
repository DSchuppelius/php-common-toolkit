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

namespace CommonToolkit\Entities\Common\Banking\Mt940;

use CommonToolkit\Enums\CurrencyCode;

final class Document {
    /** @var Transaction[] */
    private array $transactions;

    private string $accountId;
    private string $referenceId;
    private string $statementNumber;
    private Balance $openingBalance;
    private Balance $closingBalance;
    private CurrencyCode $currency;

    public function __construct(string $accountId, string $referenceId, string $statementNumber, Balance $openingBalance, Balance $closingBalance, array $transactions) {
        $this->accountId = $accountId;
        $this->referenceId = $referenceId;
        $this->statementNumber = $statementNumber;
        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->transactions = $transactions;
        $this->currency = $openingBalance->getCurrency();
    }

    public function getAccountId(): string {
        return $this->accountId;
    }

    public function getReferenceId(): string {
        return $this->referenceId;
    }

    public function getStatementNumber(): string {
        return $this->statementNumber;
    }

    public function getOpeningBalance(): Balance {
        return $this->openingBalance;
    }

    public function getClosingBalance(): Balance {
        return $this->closingBalance;
    }

    public function getTransactions(): array {
        return $this->transactions;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
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
        $lines[] = '-';

        return implode("\r\n", $lines) . "\r\n";
    }
}