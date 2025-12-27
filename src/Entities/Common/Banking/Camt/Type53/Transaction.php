<?php
/*
 * Created on   : Mon Nov 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Transaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type53;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtTransactionAbstract;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * CAMT.053 Transaction Entry.
 * 
 * Repräsentiert einen einzelnen Buchungseintrag (Ntry) im
 * Tagesauszug (Bank to Customer Statement).
 * 
 * CAMT.053 enthält vollständige Referenzinformationen über das
 * Reference-Objekt für SEPA-Buchungen.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt053
 */
final class Transaction extends CamtTransactionAbstract {
    private Reference $reference;
    private ?string $purpose;
    private ?string $additionalInfo;
    private ?string $transactionCode;
    private ?string $counterpartyName;
    private ?string $counterpartyIban;
    private ?string $counterpartyBic;

    /**
     * @param DateTimeImmutable $bookingDate Buchungsdatum
     * @param DateTimeImmutable|null $valutaDate Valutadatum (Wertstellung)
     * @param float $amount Betrag (immer positiv)
     * @param CurrencyCode $currency Währung
     * @param CreditDebit $creditDebit Soll/Haben-Kennzeichen
     * @param Reference $reference Alle Referenzen der Transaktion
     * @param string|null $entryReference Entry Reference (NtryRef)
     * @param string|null $accountServicerReference Account Servicer Reference
     * @param string|null $status Buchungsstatus (BOOK, PDNG, INFO)
     * @param bool $isReversal Storno-Kennzeichen
     * @param string|null $purpose Verwendungszweck (unstrukturiert)
     * @param string|null $additionalInfo Zusätzliche Buchungsinformationen
     * @param string|null $transactionCode Transaktionscode (GVC)
     * @param string|null $counterpartyName Name der Gegenseite
     * @param string|null $counterpartyIban IBAN der Gegenseite
     * @param string|null $counterpartyBic BIC der Gegenseite
     */
    public function __construct(
        DateTimeImmutable $bookingDate,
        ?DateTimeImmutable $valutaDate,
        float $amount,
        CurrencyCode $currency,
        CreditDebit $creditDebit,
        Reference $reference,
        ?string $entryReference = null,
        ?string $accountServicerReference = null,
        ?string $status = 'BOOK',
        bool $isReversal = false,
        ?string $purpose = null,
        ?string $additionalInfo = null,
        ?string $transactionCode = null,
        ?string $counterpartyName = null,
        ?string $counterpartyIban = null,
        ?string $counterpartyBic = null
    ) {
        parent::__construct(
            $bookingDate,
            $valutaDate,
            $amount,
            $currency,
            $creditDebit,
            $entryReference,
            $accountServicerReference,
            $status,
            $isReversal
        );

        $this->reference = $reference;
        $this->purpose = $purpose;
        $this->additionalInfo = $additionalInfo;
        $this->transactionCode = $transactionCode;
        $this->counterpartyName = $counterpartyName;
        $this->counterpartyIban = $counterpartyIban;
        $this->counterpartyBic = $counterpartyBic;
    }

    public function getReference(): Reference {
        return $this->reference;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }

    public function getAdditionalInfo(): ?string {
        return $this->additionalInfo;
    }

    public function getTransactionCode(): ?string {
        return $this->transactionCode;
    }

    public function getCounterpartyName(): ?string {
        return $this->counterpartyName;
    }

    public function getCounterpartyIban(): ?string {
        return $this->counterpartyIban;
    }

    public function getCounterpartyBic(): ?string {
        return $this->counterpartyBic;
    }

    public function getSign(): string {
        return $this->creditDebit->getSymbol();
    }

    /**
     * Gibt eine kompakte Beschreibung der Transaktion zurück.
     */
    public function getSummary(): string {
        $parts = [];

        if ($this->counterpartyName !== null) {
            $parts[] = $this->counterpartyName;
        }

        if ($this->purpose !== null) {
            $parts[] = $this->purpose;
        }

        if (empty($parts) && $this->additionalInfo !== null) {
            $parts[] = $this->additionalInfo;
        }

        return implode(' - ', $parts);
    }

    /**
     * Erstellt eine Kopie mit geändertem Verwendungszweck.
     */
    public function withPurpose(string $purpose): self {
        $clone = clone $this;
        $clone->purpose = $purpose;
        return $clone;
    }

    /**
     * Erstellt eine Kopie mit geänderten Gegenseiten-Daten.
     */
    public function withCounterparty(?string $name, ?string $iban = null, ?string $bic = null): self {
        $clone = clone $this;
        $clone->counterpartyName = $name;
        $clone->counterpartyIban = $iban;
        $clone->counterpartyBic = $bic;
        return $clone;
    }

    /**
     * Gibt eine String-Repräsentation der Transaktion zurück.
     */
    public function __toString(): string {
        $sign = $this->isCredit() ? '+' : '-';
        $amount = number_format($this->amount, 2, ',', '.') . ' ' . $this->currency->value;
        $date = $this->bookingDate->format('d.m.Y');
        $summary = $this->getSummary();

        return sprintf('%s | %s%s%s', $date, $sign, $amount, $summary ? ' | ' . $summary : '');
    }
}
