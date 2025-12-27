<?php
/*
 * Created on   : Sun Jul 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt941DocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\Common\Banking\Mt9\Type941\Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\CreditDebit;
use DateTimeImmutable;
use RuntimeException;

/**
 * Builder für MT941 Documents (Balance Report).
 * 
 * MT941 enthält nur Saldeninformationen ohne Transaktionsdetails.
 * Verwendet für schnelle Saldenabfragen im Treasury/Cash Management.
 * 
 * @package CommonToolkit\Builders
 */
final class Mt941DocumentBuilder {
    private string $accountId;
    private string $referenceId = 'COMMON';
    private string $statementNumber = '00000';

    private ?Balance $openingBalance = null;
    private ?Balance $closingBalance = null;
    private ?Balance $closingAvailableBalance = null;

    /** @var Balance[] */
    private array $forwardAvailableBalances = [];

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

    public function setClosingAvailableBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->closingAvailableBalance = $balance;
        return $clone;
    }

    public function addForwardAvailableBalance(Balance $balance): self {
        $clone = clone $this;
        $clone->forwardAvailableBalances[] = $balance;
        return $clone;
    }

    /**
     * Setzt mehrere Forward Available Balances.
     * 
     * @param Balance[] $balances
     */
    public function setForwardAvailableBalances(array $balances): self {
        $clone = clone $this;
        $clone->forwardAvailableBalances = $balances;
        return $clone;
    }

    public function setCreationDateTime(DateTimeImmutable $dateTime): self {
        $clone = clone $this;
        $clone->creationDateTime = $dateTime;
        return $clone;
    }

    /**
     * Erstellt das MT941 Document.
     * 
     * @throws RuntimeException Wenn accountId fehlt oder keine Salden angegeben sind
     */
    public function build(): Document {
        if (!isset($this->accountId) || empty($this->accountId)) {
            throw new RuntimeException("AccountId muss angegeben werden.");
        }

        if (!$this->openingBalance || !$this->closingBalance) {
            throw new RuntimeException("Opening- und Closing-Balance müssen für MT941 angegeben werden.");
        }

        return new Document(
            $this->accountId,
            $this->referenceId,
            $this->statementNumber,
            $this->openingBalance,
            $this->closingBalance,
            $this->closingAvailableBalance,
            $this->forwardAvailableBalances,
            $this->creationDateTime
        );
    }
}
