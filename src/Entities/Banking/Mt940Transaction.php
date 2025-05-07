<?php

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking;

use CommonToolkit\Helper\Data\CurrencyHelper;

class Mt940Transaction {
    private string $date;
    private float $amount;
    private string $debitCredit;
    private string $currency;
    private string $transactionCode;
    private string $reference;
    private ?string $purpose;

    public function __construct(
        string $date,
        float $amount,
        string $debitCredit,
        string $currency,
        string $transactionCode,
        string $reference,
        ?string $purpose = null
    ) {
        $this->date = $date;
        $this->amount = $amount;
        $this->debitCredit = $debitCredit;
        $this->currency = $currency;
        $this->transactionCode = $transactionCode;
        $this->reference = $reference;
        $this->purpose = $purpose;
    }

    public function getDate(): string {
        return $this->date;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getDebitCredit(): string {
        return $this->debitCredit;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getTransactionCode(): string {
        return $this->transactionCode;
    }

    public function getReference(): string {
        return $this->reference;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getFormattedAmount(?string $locale = null): string {
        return CurrencyHelper::format($this->amount, $this->currency, $locale);
    }

    public function isDebit(): bool {
        return strtoupper($this->debitCredit) === 'D';
    }

    public function isCredit(): bool {
        return strtoupper($this->debitCredit) === 'C';
    }

    public function getSign(): string {
        return $this->isDebit() ? '-' : '+';
    }

    public function toMt940Lines(): array {
        $lines = [];

        // Format Betrag zurück ins DE-Format (für MT940 erforderlich)
        $amountStr = CurrencyHelper::usToDe((string) $this->amount);

        // :61:-Zeile aufbauen (einfaches Format, ggf. erweiterbar)
        $lines[] = sprintf(
            ':61:%s%s%s%s%s',
            $this->date,
            strtoupper($this->debitCredit),
            $amountStr,
            $this->transactionCode,
            $this->reference
        );

        // Verwendungszweck (86) vorbereiten
        $purpose = $this->purpose ?? '';
        $segments = str_split($purpose, 27); // SWIFT-segmentierte Zeilen

        // Erste Zeile mit :86:, Rest mit ?20, ?21 usw.
        $first = array_shift($segments);
        $lines[] = ':86:' . ($first ?? '');

        $i = 20;
        foreach ($segments as $segment) {
            $lines[] = sprintf('?%02d%s', $i++, $segment);
        }

        return $lines;
    }
}
