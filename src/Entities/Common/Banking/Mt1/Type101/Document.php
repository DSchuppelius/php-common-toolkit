<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt1\Type101;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * MT101 Document - Request for Transfer.
 * 
 * Sammelüberweisung gemäß SWIFT-Standard. Ermöglicht das Senden
 * mehrerer Zahlungsaufträge in einer Nachricht.
 * 
 * Struktur:
 * - Sequence A: Allgemeine Informationen (einmal pro Nachricht)
 *   - :20:   Sender's Reference
 *   - :21R:  Customer Specified Reference (optional)
 *   - :28D:  Message Index/Total
 *   - :50:   Ordering Customer
 *   - :52a:  Ordering Institution
 *   - :30:   Requested Execution Date
 * 
 * - Sequence B: Transaktionsdetails (wiederholbar)
 *   - :21:   Transaction Reference
 *   - :32B:  Currency/Amount
 *   - :57a:  Account With Institution
 *   - :59a:  Beneficiary
 *   - :70:   Remittance Information
 *   - :71A:  Details of Charges
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt1\Type101
 */
class Document {
    private string $sendersReference;
    private ?string $customerReference;
    private string $messageIndex;
    private Party $orderingCustomer;
    private ?Party $orderingInstitution;
    private DateTimeImmutable $requestedExecutionDate;
    private ?DateTimeImmutable $creationDateTime;

    /** @var Transaction[] */
    private array $transactions = [];

    public function __construct(
        string $sendersReference,
        Party $orderingCustomer,
        DateTimeImmutable $requestedExecutionDate,
        array $transactions = [],
        ?Party $orderingInstitution = null,
        ?string $customerReference = null,
        string $messageIndex = '1/1',
        ?DateTimeImmutable $creationDateTime = null
    ) {
        $this->sendersReference = $sendersReference;
        $this->orderingCustomer = $orderingCustomer;
        $this->requestedExecutionDate = $requestedExecutionDate;
        $this->transactions = $transactions;
        $this->orderingInstitution = $orderingInstitution;
        $this->customerReference = $customerReference;
        $this->messageIndex = $messageIndex;
        $this->creationDateTime = $creationDateTime ?? new DateTimeImmutable();
    }

    public function getMtType(): MtType {
        return MtType::MT101;
    }

    /**
     * Gibt die Sender's Reference zurück (Feld :20:).
     */
    public function getSendersReference(): string {
        return $this->sendersReference;
    }

    /**
     * Gibt die Customer Specified Reference zurück (Feld :21R:).
     */
    public function getCustomerReference(): ?string {
        return $this->customerReference;
    }

    /**
     * Gibt den Message Index zurück (Feld :28D:).
     * Format: n/m (z.B. "1/1" oder "2/3")
     */
    public function getMessageIndex(): string {
        return $this->messageIndex;
    }

    /**
     * Gibt den Auftraggeber zurück (Feld :50:).
     */
    public function getOrderingCustomer(): Party {
        return $this->orderingCustomer;
    }

    /**
     * Gibt die Ordering Institution zurück (Feld :52a:).
     */
    public function getOrderingInstitution(): ?Party {
        return $this->orderingInstitution;
    }

    /**
     * Gibt das gewünschte Ausführungsdatum zurück (Feld :30:).
     */
    public function getRequestedExecutionDate(): DateTimeImmutable {
        return $this->requestedExecutionDate;
    }

    /**
     * Gibt das Erstellungsdatum zurück.
     */
    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    /**
     * Gibt alle Transaktionen zurück.
     * @return Transaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    /**
     * Fügt eine Transaktion hinzu.
     */
    public function addTransaction(Transaction $transaction): void {
        $this->transactions[] = $transaction;
    }

    /**
     * Gibt die Anzahl der Transaktionen zurück.
     */
    public function countTransactions(): int {
        return count($this->transactions);
    }

    /**
     * Berechnet die Gesamtsumme aller Transaktionen.
     */
    public function getTotalAmount(): float {
        return array_reduce(
            $this->transactions,
            fn(float $sum, Transaction $txn) => $sum + $txn->getAmount(),
            0.0
        );
    }

    /**
     * Gibt alle verwendeten Währungen zurück.
     * @return CurrencyCode[]
     */
    public function getCurrencies(): array {
        $currencies = [];
        foreach ($this->transactions as $txn) {
            $currency = $txn->getTransferDetails()->getCurrency();
            if (!in_array($currency, $currencies, true)) {
                $currencies[] = $currency;
            }
        }
        return $currencies;
    }

    /**
     * Generiert die SWIFT MT101 Nachricht.
     */
    public function __toString(): string {
        $lines = [];

        // Sequence A - General Information
        $lines[] = ':20:' . $this->sendersReference;

        if ($this->customerReference !== null) {
            $lines[] = ':21R:' . $this->customerReference;
        }

        $lines[] = ':28D:' . $this->messageIndex;

        // Ordering Customer
        if ($this->orderingCustomer->hasAccount()) {
            $lines[] = ':50H:' . $this->orderingCustomer->toOptionK();
        } else {
            $lines[] = ':50K:' . $this->orderingCustomer->toOptionK();
        }

        // Ordering Institution
        if ($this->orderingInstitution !== null) {
            if ($this->orderingInstitution->isBicOnly()) {
                $lines[] = ':52A:' . $this->orderingInstitution->toOptionA();
            } else {
                $lines[] = ':52C:' . $this->orderingInstitution->toOptionK();
            }
        }

        // Requested Execution Date
        $lines[] = ':30:' . $this->requestedExecutionDate->format('ymd');

        // Sequence B - Transaction Details
        foreach ($this->transactions as $txn) {
            $lines[] = (string) $txn;
        }

        return implode("\r\n", $lines);
    }
}
