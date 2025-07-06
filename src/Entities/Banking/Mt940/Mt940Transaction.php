<?php
/*
 * Created on   : Wed May 07 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940Transaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Mt940;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use RuntimeException;

class Mt940Transaction {
    private DateTimeImmutable $date;
    private ?DateTimeImmutable $valutaDate;
    private float $amount;
    private CreditDebit $creditDebit;
    private CurrencyCode $currency;
    private Mt940Reference $reference;
    private ?string $purpose;

    public function __construct(DateTimeImmutable|string $date,  DateTimeImmutable|string|null $valutaDate, float $amount, CreditDebit $creditDebit, CurrencyCode $currency, Mt940Reference $reference, ?string $purpose = null) {
        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : (DateTimeImmutable::createFromFormat('ymd', $date) ?: throw new RuntimeException("Ungültiges Datum: $date"));
        $this->valutaDate = match (true) {
            $valutaDate instanceof DateTimeImmutable => $valutaDate,
            is_string($valutaDate) => (DateTimeImmutable::createFromFormat('Ymd', $this->date->format('Y') . $valutaDate)
                ?: throw new RuntimeException("Ungültiges Valutadatum: $valutaDate")),
            default => null
        };
        $this->amount = $amount;
        $this->creditDebit = $creditDebit;
        $this->currency = $currency;
        $this->reference = $reference;
        $this->purpose = $purpose;
    }

    public function getDate(): DateTimeImmutable {
        return $this->date;
    }

    public function getValutaDate(): ?DateTimeImmutable {
        return $this->valutaDate;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCreditDebit(): CreditDebit {
        return $this->creditDebit;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getReference(): Mt940Reference {
        return $this->reference;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getFormattedAmount(?string $locale = null): string {
        return CurrencyHelper::format($this->amount, $this->currency, $locale);
    }

    public function isDebit(): bool {
        return $this->creditDebit  === CreditDebit::DEBIT;
    }

    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    public function getSign(): string {
        return $this->creditDebit->getSymbol();
    }

    private function toMt940Lines(): array {
        $amountStr = CurrencyHelper::usToDe((string) $this->amount);
        $dateStr = $this->date->format('ymd');
        $valutaStr = $this->valutaDate ? $this->valutaDate->format('md') : '';
        $direction = $this->creditDebit->toMt940Code();

        $lines = [
            sprintf(':61:%s%s%s%s%s', $dateStr, $valutaStr, $direction, $amountStr, (string)$this->reference),
        ];

        $segments = str_split($this->purpose ?? '', 27);
        $first = array_shift($segments);
        $lines[] = ':86:' . ($first ?? '');

        $i = 20;
        foreach ($segments as $segment) {
            $lines[] = sprintf('?%02d%s', $i++, $segment);
        }

        return $lines;
    }

    public function __toString(): string {
        return implode("\r\n", $this->toMt940Lines()) . "\r\n";
    }
}
