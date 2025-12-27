<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtDocumentAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\Common\Banking\Camt;

use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Abstrakte Basisklasse für alle CAMT-Dokumente (052, 053, 054).
 * 
 * Enthält gemeinsame Eigenschaften und Methoden für:
 * - CAMT.052: BankToCustomerAccountReport (Intraday)
 * - CAMT.053: BankToCustomerStatement (End of Day)
 * - CAMT.054: BankToCustomerDebitCreditNotification
 * 
 * @package CommonToolkit\Entities\Common\Banking
 */
abstract class CamtDocumentAbstract {
    protected string $id;
    protected DateTimeImmutable $creationDateTime;
    protected string $accountIdentifier;
    protected CurrencyCode $currency;
    protected ?string $accountOwner;
    protected ?string $servicerBic;
    protected ?string $messageId;
    protected ?string $sequenceNumber;

    /** @var CamtTransactionAbstract[] */
    protected array $entries = [];

    /**
     * @param string $id Statement/Report/Notification ID
     * @param DateTimeImmutable|string $creationDateTime Erstellungszeitpunkt
     * @param string $accountIdentifier IBAN oder andere Kontoidentifikation
     * @param CurrencyCode|string $currency Kontowährung
     * @param string|null $accountOwner Kontoinhaber
     * @param string|null $servicerBic BIC der kontoführenden Bank
     * @param string|null $messageId Nachrichten-ID (aus GrpHdr)
     * @param string|null $sequenceNumber Sequenznummer
     */
    public function __construct(
        string $id,
        DateTimeImmutable|string $creationDateTime,
        string $accountIdentifier,
        CurrencyCode|string $currency,
        ?string $accountOwner = null,
        ?string $servicerBic = null,
        ?string $messageId = null,
        ?string $sequenceNumber = null
    ) {
        $this->id = $id;

        $this->creationDateTime = $creationDateTime instanceof DateTimeImmutable
            ? $creationDateTime
            : new DateTimeImmutable($creationDateTime);

        $this->accountIdentifier = $accountIdentifier;

        $this->currency = $currency instanceof CurrencyCode
            ? $currency
            : CurrencyCode::tryFrom(strtoupper($currency))
            ?? throw new InvalidArgumentException("Ungültige Währung: $currency");

        $this->accountOwner = $accountOwner;
        $this->servicerBic = $servicerBic;
        $this->messageId = $messageId;
        $this->sequenceNumber = $sequenceNumber;
    }

    /**
     * Gibt den CAMT-Typ dieses Dokuments zurück.
     */
    abstract public function getCamtType(): CamtType;

    public function getId(): string {
        return $this->id;
    }

    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    public function getAccountIdentifier(): string {
        return $this->accountIdentifier;
    }

    /**
     * Gibt die IBAN zurück (Alias für getAccountIdentifier bei IBAN-Konten).
     */
    public function getAccountIban(): string {
        return $this->accountIdentifier;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getAccountOwner(): ?string {
        return $this->accountOwner;
    }

    public function getServicerBic(): ?string {
        return $this->servicerBic;
    }

    public function getMessageId(): ?string {
        return $this->messageId;
    }

    public function getSequenceNumber(): ?string {
        return $this->sequenceNumber;
    }

    /**
     * @return CamtTransactionAbstract[]
     */
    public function getEntries(): array {
        return $this->entries;
    }

    public function countEntries(): int {
        return count($this->entries);
    }

    /**
     * Berechnet die Summe aller Haben-Buchungen.
     */
    public function getTotalCredits(): float {
        $sum = 0.0;
        foreach ($this->entries as $entry) {
            if ($entry->isCredit()) {
                $sum += $entry->getAmount();
            }
        }
        return round($sum, 2);
    }

    /**
     * Berechnet die Summe aller Soll-Buchungen.
     */
    public function getTotalDebits(): float {
        $sum = 0.0;
        foreach ($this->entries as $entry) {
            if ($entry->isDebit()) {
                $sum += $entry->getAmount();
            }
        }
        return round($sum, 2);
    }

    /**
     * Berechnet den Netto-Umsatz (Haben - Soll).
     */
    public function getNetAmount(): float {
        return round($this->getTotalCredits() - $this->getTotalDebits(), 2);
    }

    /**
     * Generiert XML-Ausgabe für dieses Dokument.
     */
    abstract public function toXml(CamtVersion $version = CamtVersion::V02): string;
}
