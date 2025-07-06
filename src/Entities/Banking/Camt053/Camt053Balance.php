<?php
/*
 * Created on   : Thu May 09 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053Balance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Camt053;

use CommonToolkit\Contracts\Interfaces\Banking\BalanceInterface;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use InvalidArgumentException;

class Camt053Balance implements BalanceInterface {
    private CreditDebit $creditDebit;
    private DateTimeImmutable $date;
    private CurrencyCode $currency;
    private float $amount;

    public function __construct(
        CreditDebit $creditDebit,
        DateTimeImmutable|string $date,
        CurrencyCode|string $currency,
        float $amount
    ) {
        $this->creditDebit = $creditDebit;

        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : new DateTimeImmutable($date);

        $this->currency = $currency instanceof CurrencyCode
            ? $currency
            : CurrencyCode::tryFrom(strtoupper($currency))
            ?? throw new InvalidArgumentException("Ungültige Währung: $currency");

        $this->amount = round($amount, 2);
    }

    public function getCreditDebit(): CreditDebit {
        return $this->creditDebit;
    }

    public function getDate(): DateTimeImmutable {
        return $this->date;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    public function isDebit(): bool {
        return $this->creditDebit === CreditDebit::DEBIT;
    }

    public function __toString(): string {
        return sprintf(
            '[%s] %s %.2f %s',
            $this->creditDebit->name,
            $this->date->format('Y-m-d'),
            $this->amount,
            $this->currency->value
        );
    }
}