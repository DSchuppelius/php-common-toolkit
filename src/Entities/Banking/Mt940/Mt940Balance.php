<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940Balance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Mt940;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use RuntimeException;

class Mt940Balance {
    private CreditDebit $creditDebit;
    private DateTimeImmutable $date;      // z. B. '240501'
    private CurrencyCode $currency;  // z. B. 'EUR'
    private float $amount;     // 1234.56

    public function __construct(CreditDebit $creditDebit, DateTimeImmutable|string $date, CurrencyCode $currency, float $amount) {
        $this->creditDebit = $creditDebit;
        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromFormat('ymd', $date);
        $this->currency = $currency;
        $this->amount = round($amount, 2);
    }

    public static function parse(string $raw): self {
        if (!preg_match('/^([CD])(\d{6})([A-Z]{3})([0-9,]+)$/', $raw, $matches)) {
            throw new RuntimeException("Balance-String ungültig: $raw");
        }

        return new self(
            creditDebit: CreditDebit::fromMt940Code($matches[1]),
            date: DateTimeImmutable::createFromFormat('ymd', $matches[2]),
            currency: CurrencyCode::tryFrom($matches[3]) ?? throw new RuntimeException("Ungültige Währung: {$matches[3]}"),
            amount: (float) str_replace(',', '.', $matches[4])
        );
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

    public function toMt940(): string {
        return sprintf(
            '%s%s%s%s',
            $this->creditDebit->toMt940Code(),
            $this->date,
            $this->currency->value,
            number_format($this->amount, 2, ',', '')
        );
    }

    public function __toString(): string {
        return $this->toMt940();
    }
}
