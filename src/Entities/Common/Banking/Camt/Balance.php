<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtBalance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Repräsentiert einen Saldo in CAMT-Dokumenten.
 * 
 * Unterstützte Balance-Typen:
 * - OPBD: Opening Booked (Eröffnungssaldo)
 * - CLBD: Closing Booked (Schlusssaldo)
 * - PRCD: Previously Closed Booked (Vortrag)
 * - CLAV: Closing Available (verfügbarer Schlusssaldo)
 * - FWAV: Forward Available (voraussichtlich verfügbar)
 * 
 * @package CommonToolkit\Entities\Common\Banking
 */
class Balance {
    private CreditDebit $creditDebit;
    private DateTimeImmutable $date;
    private CurrencyCode $currency;
    private float $amount;
    private string $type;

    /**
     * @param CreditDebit $creditDebit Soll/Haben-Kennzeichen
     * @param DateTimeImmutable|string $date Datum des Saldos
     * @param CurrencyCode|string $currency Währung
     * @param float $amount Betrag (positiv)
     * @param string $type Balance-Typ (OPBD, CLBD, PRCD, CLAV, FWAV)
     */
    public function __construct(
        CreditDebit $creditDebit,
        DateTimeImmutable|string $date,
        CurrencyCode|string $currency,
        float $amount,
        string $type = 'CLBD'
    ) {
        $this->creditDebit = $creditDebit;

        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : new DateTimeImmutable($date);

        $this->currency = $currency instanceof CurrencyCode
            ? $currency
            : CurrencyCode::tryFrom(strtoupper($currency))
            ?? throw new InvalidArgumentException("Ungültige Währung: $currency");

        $this->amount = abs($amount);
        $this->type = strtoupper($type);
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

    public function getType(): string {
        return $this->type;
    }

    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    public function isDebit(): bool {
        return $this->creditDebit === CreditDebit::DEBIT;
    }

    /**
     * Gibt den vorzeichenbehafteten Betrag zurück.
     * Positiv für Haben, negativ für Soll.
     */
    public function getSignedAmount(): float {
        return $this->isCredit() ? $this->amount : -$this->amount;
    }

    /**
     * Prüft, ob es sich um einen Eröffnungssaldo handelt.
     */
    public function isOpeningBalance(): bool {
        return in_array($this->type, ['OPBD', 'PRCD'], true);
    }

    /**
     * Prüft, ob es sich um einen Schlusssaldo handelt.
     */
    public function isClosingBalance(): bool {
        return in_array($this->type, ['CLBD', 'CLAV'], true);
    }

    /**
     * Erstellt einen Balance aus XML-Daten.
     */
    public static function fromArray(array $data): self {
        return new self(
            creditDebit: CreditDebit::from($data['creditDebit'] ?? 'CRDT'),
            date: $data['date'] ?? 'now',
            currency: $data['currency'] ?? 'EUR',
            amount: (float) ($data['amount'] ?? 0.0),
            type: $data['type'] ?? 'CLBD'
        );
    }

    /**
     * Gibt eine String-Repräsentation des Balance zurück.
     * Format: "OPBD: C 2025-01-15 EUR 1000.00"
     */
    public function __toString(): string {
        return sprintf(
            '%s: %s %s %s %.2f',
            $this->type,
            $this->creditDebit === CreditDebit::CREDIT ? 'C' : 'D',
            $this->date->format('Y-m-d'),
            $this->currency->value,
            $this->amount
        );
    }
}
