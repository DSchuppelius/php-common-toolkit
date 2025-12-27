<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtDocumentAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\Common\Banking\Mt1;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Abstrakte Basisklasse für MT10x-Dokumente (Zahlungsaufträge).
 * 
 * Gemeinsame Felder für MT101, MT103, MT104:
 * - :20:  Sender's Reference
 * - :23B: Bank Operation Code
 * - :32A/B: Value Date, Currency, Amount
 * - :50:  Ordering Customer (Auftraggeber)
 * - :59:  Beneficiary (Begünstigter)
 * - :70:  Remittance Information (Verwendungszweck)
 * - :71A: Details of Charges
 * 
 * @package CommonToolkit\Contracts\Abstracts\Common\Banking\Mt1
 */
abstract class MtDocumentAbstract {
    protected string $sendersReference;
    protected TransferDetails $transferDetails;
    protected Party $orderingCustomer;
    protected Party $beneficiary;
    protected ?string $remittanceInfo;
    protected ?ChargesCode $chargesCode;
    protected ?DateTimeImmutable $creationDateTime;

    public function __construct(
        string $sendersReference,
        TransferDetails $transferDetails,
        Party $orderingCustomer,
        Party $beneficiary,
        ?string $remittanceInfo = null,
        ?ChargesCode $chargesCode = null,
        ?DateTimeImmutable $creationDateTime = null
    ) {
        $this->sendersReference = $sendersReference;
        $this->transferDetails = $transferDetails;
        $this->orderingCustomer = $orderingCustomer;
        $this->beneficiary = $beneficiary;
        $this->remittanceInfo = $remittanceInfo;
        $this->chargesCode = $chargesCode;
        $this->creationDateTime = $creationDateTime ?? new DateTimeImmutable();
    }

    /**
     * Gibt den MT-Typ zurück.
     */
    abstract public function getMtType(): MtType;

    /**
     * Gibt die Sender's Reference zurück (Feld :20:).
     */
    public function getSendersReference(): string {
        return $this->sendersReference;
    }

    /**
     * Gibt die Überweisungsdetails zurück.
     */
    public function getTransferDetails(): TransferDetails {
        return $this->transferDetails;
    }

    /**
     * Gibt den Auftraggeber zurück (Feld :50:).
     */
    public function getOrderingCustomer(): Party {
        return $this->orderingCustomer;
    }

    /**
     * Gibt den Begünstigten zurück (Feld :59:).
     */
    public function getBeneficiary(): Party {
        return $this->beneficiary;
    }

    /**
     * Gibt den Verwendungszweck zurück (Feld :70:).
     */
    public function getRemittanceInfo(): ?string {
        return $this->remittanceInfo;
    }

    /**
     * Gibt den Gebührencode zurück (Feld :71A:).
     */
    public function getChargesCode(): ?ChargesCode {
        return $this->chargesCode;
    }

    /**
     * Gibt das Erstellungsdatum zurück.
     */
    public function getCreationDateTime(): DateTimeImmutable {
        return $this->creationDateTime;
    }

    /**
     * Gibt das Valutadatum zurück.
     */
    public function getValueDate(): DateTimeImmutable {
        return $this->transferDetails->getValueDate();
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): CurrencyCode {
        return $this->transferDetails->getCurrency();
    }

    /**
     * Gibt den Betrag zurück.
     */
    public function getAmount(): float {
        return $this->transferDetails->getAmount();
    }

    /**
     * Gibt den formatierten Betrag zurück.
     */
    public function getFormattedAmount(): string {
        return $this->transferDetails->getFormattedAmount();
    }

    /**
     * Generiert die gemeinsamen SWIFT-Felder.
     * 
     * @return array<string, string>
     */
    protected function getCommonSwiftFields(): array {
        $fields = [
            ':20:' => $this->sendersReference,
        ];

        return $fields;
    }

    /**
     * Muss von konkreten Klassen implementiert werden.
     */
    abstract public function __toString(): string;
}