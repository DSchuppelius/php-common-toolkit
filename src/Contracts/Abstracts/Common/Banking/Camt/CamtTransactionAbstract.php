<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtTransactionAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\Common\Banking\Camt;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Abstrakte Basisklasse für CAMT Entry/Transaction.
 * 
 * Repräsentiert einen einzelnen Buchungseintrag (Ntry) in CAMT-Dokumenten.
 * 
 * @package CommonToolkit\Entities\Common\Banking
 */
abstract class CamtTransactionAbstract {
    protected DateTimeImmutable $bookingDate;
    protected ?DateTimeImmutable $valutaDate;
    protected float $amount;
    protected CurrencyCode $currency;
    protected CreditDebit $creditDebit;
    protected ?string $entryReference;
    protected ?string $accountServicerReference;
    protected ?string $status;
    protected bool $isReversal;

    /**
     * @param DateTimeImmutable $bookingDate Buchungsdatum
     * @param DateTimeImmutable|null $valutaDate Valutadatum (Wertstellung)
     * @param float $amount Betrag (immer positiv)
     * @param CurrencyCode $currency Währung
     * @param CreditDebit $creditDebit Soll/Haben-Kennzeichen
     * @param string|null $entryReference Entry Reference (NtryRef)
     * @param string|null $accountServicerReference Account Servicer Reference
     * @param string|null $status Buchungsstatus (BOOK, PDNG, INFO)
     * @param bool $isReversal Storno-Kennzeichen
     */
    public function __construct(
        DateTimeImmutable $bookingDate,
        ?DateTimeImmutable $valutaDate,
        float $amount,
        CurrencyCode $currency,
        CreditDebit $creditDebit,
        ?string $entryReference = null,
        ?string $accountServicerReference = null,
        ?string $status = 'BOOK',
        bool $isReversal = false
    ) {
        $this->bookingDate = $bookingDate;
        $this->valutaDate = $valutaDate;
        $this->amount = abs($amount);
        $this->currency = $currency;
        $this->creditDebit = $creditDebit;
        $this->entryReference = $entryReference;
        $this->accountServicerReference = $accountServicerReference;
        $this->status = $status;
        $this->isReversal = $isReversal;
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

    public function getEntryReference(): ?string {
        return $this->entryReference;
    }

    public function getAccountServicerReference(): ?string {
        return $this->accountServicerReference;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function isReversal(): bool {
        return $this->isReversal;
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
}