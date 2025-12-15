<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Balance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt940;

use CommonToolkit\Contracts\Interfaces\Common\Banking\BalanceInterface;
use CommonToolkit\Enums\{CreditDebit, CurrencyCode};
use DateTimeImmutable;
use RuntimeException;

class Balance implements BalanceInterface {
    private CreditDebit $creditDebit;
    private DateTimeImmutable $date;
    private CurrencyCode $currency;
    private float $amount;

    public function __construct(CreditDebit $creditDebit, DateTimeImmutable|string $date, CurrencyCode $currency, float $amount) {
        $this->creditDebit = $creditDebit;
        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : (DateTimeImmutable::createFromFormat('ymd', $date) ?: throw new RuntimeException("Ungültiges Datum: $date"));
        $this->currency = $currency;
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
            '%s%s%s%s',
            $this->creditDebit->toMt940Code(),
            $this->date->format('ymd'),
            $this->currency->value,
            number_format($this->amount, 2, ',', '')
        );
    }
}