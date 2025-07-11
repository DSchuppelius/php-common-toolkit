<?php

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Camt053;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

final class Camt053Transaction {
    private DateTimeImmutable $bookingDate;
    private ?DateTimeImmutable $valutaDate;
    private float $amount;
    private CurrencyCode $currency;
    private CreditDebit $creditDebit;
    private Camt053Reference $reference;
    private ?string $purpose;
    private ?string $additionalInfo;
    private ?string $transactionCode;
    private ?string $entryReference;

    public function __construct(DateTimeImmutable $bookingDate, ?DateTimeImmutable $valutaDate, float $amount, CurrencyCode $currency, CreditDebit $creditDebit, Camt053Reference $reference, ?string $purpose = null, ?string $additionalInfo = null) {
        $this->bookingDate = $bookingDate;
        $this->valutaDate = $valutaDate;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->creditDebit = $creditDebit;
        $this->reference = $reference;
        $this->purpose = $purpose;
        $this->additionalInfo = $additionalInfo;
    }

    public function getBookingDate(): DateTimeImmutable {
        return $this->bookingDate;
    }

    public function getValutaDate(): ?DateTimeImmutable {
        return $this->valutaDate;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getCreditDebit(): CreditDebit {
        return $this->creditDebit;
    }

    public function getReference(): Camt053Reference {
        return $this->reference;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getAdditionalInfo(): ?string {
        return $this->additionalInfo;
    }

    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    public function isDebit(): bool {
        return $this->creditDebit === CreditDebit::DEBIT;
    }

    public function getSign(): string {
        return $this->creditDebit->getSymbol();
    }
}