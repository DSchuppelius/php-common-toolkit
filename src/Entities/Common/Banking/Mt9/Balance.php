<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Balance.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt9;

use CommonToolkit\Contracts\Interfaces\Common\Banking\BalanceInterface;
use CommonToolkit\Enums\{CreditDebit, CurrencyCode};
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use RuntimeException;

/**
 * Gemeinsame Balance-Klasse für alle MT9xx-Nachrichtentypen.
 * 
 * Repräsentiert Salden in SWIFT MT-Nachrichten:
 * - :60F: Opening Balance (Final)
 * - :60M: Opening Balance (Interim - MT942)
 * - :62F: Closing Balance (Final - MT940)
 * - :62M: Closing Balance (Interim - MT942)
 * - :64:  Closing Available Balance
 * - :65:  Forward Available Balance
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt9
 */
class Balance implements BalanceInterface {
    private CreditDebit $creditDebit;
    private DateTimeImmutable $date;
    private CurrencyCode $currency;
    private float $amount;
    private string $type;

    /**
     * @param CreditDebit $creditDebit Soll/Haben-Kennzeichen
     * @param DateTimeImmutable|string $date Datum (Format: ymd für String)
     * @param CurrencyCode $currency Währung
     * @param float $amount Betrag
     * @param string $type Balance-Typ (F=Final, M=Interim, A=Available)
     */
    public function __construct(
        CreditDebit $creditDebit,
        DateTimeImmutable|string $date,
        CurrencyCode $currency,
        float $amount,
        string $type = 'F'
    ) {
        $this->creditDebit = $creditDebit;
        $this->date = $date instanceof DateTimeImmutable
            ? $date
            : (DateTimeImmutable::createFromFormat('ymd', $date) ?: throw new RuntimeException("Ungültiges Datum: $date"));
        $this->currency = $currency;
        $this->amount = round($amount, 2);
        $this->type = $type;
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

    /**
     * Gibt den Balance-Typ zurück.
     * F = Final (MT940), M = Interim (MT942), A = Available
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Prüft ob es sich um einen Final-Balance handelt (MT940).
     */
    public function isFinal(): bool {
        return $this->type === 'F';
    }

    /**
     * Prüft ob es sich um einen Interim-Balance handelt (MT942).
     */
    public function isInterim(): bool {
        return $this->type === 'M';
    }

    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    public function isDebit(): bool {
        return $this->creditDebit === CreditDebit::DEBIT;
    }

    /**
     * Gibt den formatierten Betrag zurück.
     */
    public function getFormattedAmount(?string $locale = null): string {
        return CurrencyHelper::format($this->amount, $this->currency, $locale);
    }

    /**
     * Serialisiert im SWIFT MT-Format.
     * Format: [C/D][Datum YYMMDD][Währung][Betrag mit Komma]
     */
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
