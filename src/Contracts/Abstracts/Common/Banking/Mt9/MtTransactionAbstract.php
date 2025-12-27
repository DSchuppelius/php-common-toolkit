<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtTransactionAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;

/**
 * Abstrakte Basisklasse für MT9xx-Transaktionen (SWIFT Cash Management).
 * 
 * Gemeinsame Eigenschaften aller MT9-Transaktionstypen:
 * - Buchungsdatum / Valutadatum
 * - Betrag und Währung
 * - Soll/Haben-Kennzeichen
 * 
 * Entspricht Feld :61: in SWIFT-Notation.
 * 
 * @package CommonToolkit\Contracts\Abstracts\Common\Banking\Mt9
 */
abstract class MtTransactionAbstract {
    protected DateTimeImmutable $bookingDate;
    protected ?DateTimeImmutable $valutaDate;
    protected float $amount;
    protected CreditDebit $creditDebit;
    protected CurrencyCode $currency;

    public function __construct(
        DateTimeImmutable $bookingDate,
        ?DateTimeImmutable $valutaDate,
        float $amount,
        CreditDebit $creditDebit,
        CurrencyCode $currency
    ) {
        $this->bookingDate = $bookingDate;
        $this->valutaDate = $valutaDate;
        $this->amount = round(abs($amount), 2);
        $this->creditDebit = $creditDebit;
        $this->currency = $currency;
    }

    /**
     * Gibt das Buchungsdatum zurück.
     */
    public function getBookingDate(): DateTimeImmutable {
        return $this->bookingDate;
    }

    /**
     * Alias für getBookingDate() - Kompatibilität mit MT940-Konventionen.
     */
    public function getDate(): DateTimeImmutable {
        return $this->bookingDate;
    }

    /**
     * Gibt das Valutadatum (Wertstellung) zurück.
     */
    public function getValutaDate(): ?DateTimeImmutable {
        return $this->valutaDate;
    }

    /**
     * Gibt den Betrag zurück (immer positiv).
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * Gibt den vorzeichenbehafteten Betrag zurück.
     */
    public function getSignedAmount(): float {
        return $this->creditDebit === CreditDebit::DEBIT ? -$this->amount : $this->amount;
    }

    /**
     * Gibt das Soll/Haben-Kennzeichen zurück.
     */
    public function getCreditDebit(): CreditDebit {
        return $this->creditDebit;
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    /**
     * Prüft ob es sich um eine Soll-Buchung handelt.
     */
    public function isDebit(): bool {
        return $this->creditDebit === CreditDebit::DEBIT;
    }

    /**
     * Prüft ob es sich um eine Haben-Buchung handelt.
     */
    public function isCredit(): bool {
        return $this->creditDebit === CreditDebit::CREDIT;
    }

    /**
     * Gibt das Vorzeichen als String zurück.
     */
    public function getSign(): string {
        return $this->creditDebit->getSymbol();
    }

    /**
     * Gibt den formatierten Betrag zurück.
     */
    public function getFormattedAmount(?string $locale = null): string {
        return CurrencyHelper::format($this->amount, $this->currency, $locale);
    }

    /**
     * Serialisiert die Transaktion im SWIFT MT-Format.
     */
    abstract public function __toString(): string;
}
