<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt9\Type941;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9\MtDocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * MT941 Document - Balance Report.
 * 
 * Saldeninformation ohne Umsatzdetails gemäß SWIFT-Standard.
 * Enthält nur Opening und Closing Balance sowie optionale Available Balances.
 * 
 * Verwendung:
 * - Schnelle Saldenabfrage ohne vollständige Umsatzliste
 * - Kontostand-Übersicht für Treasury/Cash Management
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9\Type941
 */
class Document extends MtDocumentAbstract {
    private Balance $openingBalance;
    private Balance $closingBalance;
    private ?Balance $closingAvailableBalance;

    /** @var Balance[] Forward Available Balances */
    private array $forwardAvailableBalances = [];

    public function __construct(
        string $accountId,
        string $referenceId,
        string $statementNumber,
        Balance $openingBalance,
        Balance $closingBalance,
        ?Balance $closingAvailableBalance = null,
        array $forwardAvailableBalances = [],
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
        $this->forwardAvailableBalances = $forwardAvailableBalances;
    }

    public function getMtType(): MtType {
        return MtType::MT941;
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
     * Gibt die Forward Available Balances zurück.
     * Feld :65: in SWIFT-Notation (kann mehrfach vorkommen).
     * @return Balance[]
     */
    public function getForwardAvailableBalances(): array {
        return $this->forwardAvailableBalances;
    }

    /**
     * Fügt einen Forward Available Balance hinzu.
     */
    public function addForwardAvailableBalance(Balance $balance): void {
        $this->forwardAvailableBalances[] = $balance;
    }

    /**
     * MT941 enthält keine Transaktionen.
     */
    public function countEntries(): int {
        return 0;
    }

    /**
     * Berechnet die Differenz zwischen Opening und Closing Balance.
     */
    public function getBalanceChange(): float {
        $openingAmount = $this->openingBalance->isCredit()
            ? $this->openingBalance->getAmount()
            : -$this->openingBalance->getAmount();

        $closingAmount = $this->closingBalance->isCredit()
            ? $this->closingBalance->getAmount()
            : -$this->closingBalance->getAmount();

        return $closingAmount - $openingAmount;
    }

    public function __toString(): string {
        $lines = [
            ':20:' . $this->referenceId,
            ':25:' . $this->accountId,
            ':28C:' . $this->statementNumber,
            ':60F:' . (string) $this->openingBalance,
            ':62F:' . (string) $this->closingBalance,
        ];

        if ($this->closingAvailableBalance !== null) {
            $lines[] = ':64:' . (string) $this->closingAvailableBalance;
        }

        foreach ($this->forwardAvailableBalances as $balance) {
            $lines[] = ':65:' . (string) $balance;
        }

        $lines[] = '-';

        return implode("\r\n", $lines) . "\r\n";
    }
}
