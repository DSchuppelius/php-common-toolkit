<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransferDetails.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt1;

use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use RuntimeException;

/**
 * Überweisungsdetails für MT10x-Nachrichten.
 * 
 * Enthält die Kernfelder einer Überweisung:
 * - :32A: Valutadatum, Währung, Betrag
 * - :33B: Ursprungswährung und -betrag (bei Währungsumrechnung)
 * - :36:  Wechselkurs
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt1
 */
final readonly class TransferDetails {
    public function __construct(
        private DateTimeImmutable $valueDate,
        private CurrencyCode $currency,
        private float $amount,
        private ?CurrencyCode $originalCurrency = null,
        private ?float $originalAmount = null,
        private ?float $exchangeRate = null
    ) {
    }

    /**
     * Gibt das Valutadatum zurück.
     */
    public function getValueDate(): DateTimeImmutable {
        return $this->valueDate;
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    /**
     * Gibt den Betrag zurück.
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * Gibt die ursprüngliche Währung zurück (bei Währungsumrechnung).
     */
    public function getOriginalCurrency(): ?CurrencyCode {
        return $this->originalCurrency;
    }

    /**
     * Gibt den ursprünglichen Betrag zurück (bei Währungsumrechnung).
     */
    public function getOriginalAmount(): ?float {
        return $this->originalAmount;
    }

    /**
     * Gibt den Wechselkurs zurück.
     */
    public function getExchangeRate(): ?float {
        return $this->exchangeRate;
    }

    /**
     * Prüft ob eine Währungsumrechnung stattfand.
     */
    public function hasCurrencyConversion(): bool {
        return $this->originalCurrency !== null
            && $this->originalCurrency !== $this->currency;
    }

    /**
     * Gibt den formatierten Betrag zurück.
     */
    public function getFormattedAmount(): string {
        return number_format($this->amount, 2, ',', '.') . ' ' . $this->currency->value;
    }

    /**
     * Parst Feld :32A: (Value Date/Currency/Amount).
     * Format: YYMMDDCCCAMOUNT (z.B. 250512EUR39,42)
     */
    public static function fromField32A(string $content): self {
        $content = trim($content);

        // Format: YYMMDD + CCC + Amount
        if (!preg_match('/^(\d{6})([A-Z]{3})(.+)$/', $content, $matches)) {
            throw new RuntimeException("Ungültiges :32A: Format: $content");
        }

        $dateStr = $matches[1];
        $currencyStr = $matches[2];
        $amountStr = str_replace(',', '.', $matches[3]);

        $date = DateTimeImmutable::createFromFormat('ymd', $dateStr);
        if (!$date) {
            throw new RuntimeException("Ungültiges Datum in :32A:: $dateStr");
        }

        $currency = CurrencyCode::tryFrom($currencyStr);
        if (!$currency) {
            throw new RuntimeException("Unbekannte Währung in :32A:: $currencyStr");
        }

        return new self(
            valueDate: $date,
            currency: $currency,
            amount: (float) $amountStr
        );
    }

    /**
     * Parst Feld :32B: (Currency/Amount ohne Datum).
     * Format: CCCAMOUNT (z.B. EUR39,42)
     */
    public static function fromField32B(string $content, DateTimeImmutable $valueDate): self {
        $content = trim($content);

        if (!preg_match('/^([A-Z]{3})(.+)$/', $content, $matches)) {
            throw new RuntimeException("Ungültiges :32B: Format: $content");
        }

        $currencyStr = $matches[1];
        $amountStr = str_replace(',', '.', $matches[2]);

        $currency = CurrencyCode::tryFrom($currencyStr);
        if (!$currency) {
            throw new RuntimeException("Unbekannte Währung in :32B:: $currencyStr");
        }

        return new self(
            valueDate: $valueDate,
            currency: $currency,
            amount: (float) $amountStr
        );
    }

    /**
     * Erweitert mit Originalwährungsdaten aus :33B:.
     */
    public function withOriginal(CurrencyCode $originalCurrency, float $originalAmount, ?float $exchangeRate = null): self {
        return new self(
            valueDate: $this->valueDate,
            currency: $this->currency,
            amount: $this->amount,
            originalCurrency: $originalCurrency,
            originalAmount: $originalAmount,
            exchangeRate: $exchangeRate
        );
    }

    /**
     * Erweitert mit Wechselkurs aus :36:.
     */
    public function withExchangeRate(float $exchangeRate): self {
        return new self(
            valueDate: $this->valueDate,
            currency: $this->currency,
            amount: $this->amount,
            originalCurrency: $this->originalCurrency,
            originalAmount: $this->originalAmount,
            exchangeRate: $exchangeRate
        );
    }

    /**
     * Serialisiert als :32A: Feld.
     */
    public function toField32A(): string {
        $amountStr = str_replace('.', ',', number_format($this->amount, 2, '.', ''));
        return $this->valueDate->format('ymd') . $this->currency->value . $amountStr;
    }

    /**
     * Serialisiert als :32B: Feld.
     */
    public function toField32B(): string {
        $amountStr = str_replace('.', ',', number_format($this->amount, 2, '.', ''));
        return $this->currency->value . $amountStr;
    }

    public function __toString(): string {
        return $this->getFormattedAmount();
    }
}
