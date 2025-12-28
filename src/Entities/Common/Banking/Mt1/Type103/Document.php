<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt1\Type103;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Mt1\MtDocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Enums\Common\Banking\BankOperationCode;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\MtType;
use DateTimeImmutable;

/**
 * MT103 Document - Single Customer Credit Transfer.
 * 
 * Einzelüberweisung gemäß SWIFT-Standard. Der häufigste Nachrichtentyp
 * für Kundenzahlungen im internationalen Zahlungsverkehr.
 * 
 * Pflichtfelder:
 * - :20:  Sender's Reference
 * - :23B: Bank Operation Code (meist CRED)
 * - :32A: Value Date, Currency, Amount
 * - :50a: Ordering Customer
 * - :59a: Beneficiary
 * - :71A: Details of Charges
 * 
 * Optionale Felder:
 * - :33B: Currency/Instructed Amount (bei Währungsumrechnung)
 * - :36:  Exchange Rate
 * - :52a: Ordering Institution
 * - :53a: Sender's Correspondent
 * - :56a: Intermediary Institution
 * - :57a: Account With Institution
 * - :70:  Remittance Information
 * - :72:  Sender to Receiver Information
 * - :77B: Regulatory Reporting
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt1\Type103
 */
class Document extends MtDocumentAbstract {
    private BankOperationCode $bankOperationCode;
    private ?Party $orderingInstitution;
    private ?Party $sendersCorrespondent;
    private ?Party $intermediaryInstitution;
    private ?Party $accountWithInstitution;
    private ?string $senderToReceiverInfo;
    private ?string $regulatoryReporting;
    private ?string $transactionTypeCode;

    public function __construct(
        string $sendersReference,
        TransferDetails $transferDetails,
        Party $orderingCustomer,
        Party $beneficiary,
        ?BankOperationCode $bankOperationCode = null,
        ?ChargesCode $chargesCode = null,
        ?string $remittanceInfo = null,
        ?Party $orderingInstitution = null,
        ?Party $sendersCorrespondent = null,
        ?Party $intermediaryInstitution = null,
        ?Party $accountWithInstitution = null,
        ?string $senderToReceiverInfo = null,
        ?string $regulatoryReporting = null,
        ?string $transactionTypeCode = null,
        ?DateTimeImmutable $creationDateTime = null
    ) {
        parent::__construct(
            $sendersReference,
            $transferDetails,
            $orderingCustomer,
            $beneficiary,
            $remittanceInfo,
            $chargesCode,
            $creationDateTime
        );

        $this->bankOperationCode = $bankOperationCode ?? BankOperationCode::CRED;
        $this->orderingInstitution = $orderingInstitution;
        $this->sendersCorrespondent = $sendersCorrespondent;
        $this->intermediaryInstitution = $intermediaryInstitution;
        $this->accountWithInstitution = $accountWithInstitution;
        $this->senderToReceiverInfo = $senderToReceiverInfo;
        $this->regulatoryReporting = $regulatoryReporting;
        $this->transactionTypeCode = $transactionTypeCode;
    }

    public function getMtType(): MtType {
        return MtType::MT103;
    }

    /**
     * Gibt den Bank Operation Code zurück (Feld :23B:).
     */
    public function getBankOperationCode(): BankOperationCode {
        return $this->bankOperationCode;
    }

    /**
     * Gibt die Ordering Institution zurück (Feld :52a:).
     * Die Bank des Auftraggebers.
     */
    public function getOrderingInstitution(): ?Party {
        return $this->orderingInstitution;
    }

    /**
     * Gibt den Sender's Correspondent zurück (Feld :53a:).
     * Die Korrespondenzbank des Senders.
     */
    public function getSendersCorrespondent(): ?Party {
        return $this->sendersCorrespondent;
    }

    /**
     * Gibt die Intermediary Institution zurück (Feld :56a:).
     * Zwischenbank im Zahlungsweg.
     */
    public function getIntermediaryInstitution(): ?Party {
        return $this->intermediaryInstitution;
    }

    /**
     * Gibt die Account With Institution zurück (Feld :57a:).
     * Die Bank des Begünstigten.
     */
    public function getAccountWithInstitution(): ?Party {
        return $this->accountWithInstitution;
    }

    /**
     * Gibt die Sender to Receiver Information zurück (Feld :72:).
     */
    public function getSenderToReceiverInfo(): ?string {
        return $this->senderToReceiverInfo;
    }

    /**
     * Gibt die Regulatory Reporting Information zurück (Feld :77B:).
     */
    public function getRegulatoryReporting(): ?string {
        return $this->regulatoryReporting;
    }

    /**
     * Gibt den Transaction Type Code zurück (Feld :26T:).
     */
    public function getTransactionTypeCode(): ?string {
        return $this->transactionTypeCode;
    }

    /**
     * Prüft ob es sich um eine STP-fähige Nachricht handelt.
     * (Straight Through Processing)
     */
    public function isStpCapable(): bool {
        // STP erfordert minimale Felder und bestimmte Formate
        return $this->orderingCustomer->hasAccount()
            && $this->beneficiary->hasAccount();
    }

    /**
     * Erstellt eine Kopie mit geänderten Gebühren.
     */
    public function withChargesCode(ChargesCode $chargesCode): self {
        return new self(
            $this->sendersReference,
            $this->transferDetails,
            $this->orderingCustomer,
            $this->beneficiary,
            $this->bankOperationCode,
            $chargesCode,
            $this->remittanceInfo,
            $this->orderingInstitution,
            $this->sendersCorrespondent,
            $this->intermediaryInstitution,
            $this->accountWithInstitution,
            $this->senderToReceiverInfo,
            $this->regulatoryReporting,
            $this->transactionTypeCode,
            $this->creationDateTime
        );
    }

    /**
     * Generiert die SWIFT MT103 Nachricht.
     */
    public function __toString(): string {
        $lines = [];

        // Pflichtfelder
        $lines[] = ':20:' . $this->sendersReference;
        $lines[] = ':23B:' . $this->bankOperationCode->value;

        // Transaction Type Code (optional)
        if ($this->transactionTypeCode !== null) {
            $lines[] = ':26T:' . $this->transactionTypeCode;
        }

        // Value Date, Currency, Amount
        $lines[] = ':32A:' . $this->transferDetails->toField32A();

        // Instructed Amount (bei Währungsumrechnung)
        if ($this->transferDetails->hasCurrencyConversion()) {
            $originalCurrency = $this->transferDetails->getOriginalCurrency();
            $originalAmount = $this->transferDetails->getOriginalAmount();
            if ($originalCurrency && $originalAmount) {
                $lines[] = ':33B:' . $originalCurrency->value . str_replace('.', ',', number_format($originalAmount, 2, '.', ''));
            }

            // Exchange Rate
            $exchangeRate = $this->transferDetails->getExchangeRate();
            if ($exchangeRate !== null) {
                $lines[] = ':36:' . number_format($exchangeRate, 6, ',', '');
            }
        }

        // Ordering Customer
        if ($this->orderingCustomer->hasAccount()) {
            $lines[] = ':50K:' . $this->orderingCustomer->toOptionK();
        } else {
            $lines[] = ':50K:' . $this->orderingCustomer->toOptionK();
        }

        // Ordering Institution
        if ($this->orderingInstitution !== null) {
            if ($this->orderingInstitution->isBicOnly()) {
                $lines[] = ':52A:' . $this->orderingInstitution->toOptionA();
            } else {
                $lines[] = ':52D:' . $this->orderingInstitution->toOptionK();
            }
        }

        // Sender's Correspondent
        if ($this->sendersCorrespondent !== null) {
            if ($this->sendersCorrespondent->isBicOnly()) {
                $lines[] = ':53A:' . $this->sendersCorrespondent->toOptionA();
            } else {
                $lines[] = ':53B:' . $this->sendersCorrespondent->toOptionK();
            }
        }

        // Intermediary Institution
        if ($this->intermediaryInstitution !== null) {
            if ($this->intermediaryInstitution->isBicOnly()) {
                $lines[] = ':56A:' . $this->intermediaryInstitution->toOptionA();
            } else {
                $lines[] = ':56D:' . $this->intermediaryInstitution->toOptionK();
            }
        }

        // Account With Institution
        if ($this->accountWithInstitution !== null) {
            if ($this->accountWithInstitution->isBicOnly()) {
                $lines[] = ':57A:' . $this->accountWithInstitution->toOptionA();
            } else {
                $lines[] = ':57D:' . $this->accountWithInstitution->toOptionK();
            }
        }

        // Beneficiary
        $lines[] = ':59:' . $this->beneficiary->toOptionK();

        // Remittance Information
        if ($this->remittanceInfo !== null) {
            $lines[] = ':70:' . $this->remittanceInfo;
        }

        // Details of Charges
        if ($this->chargesCode !== null) {
            $lines[] = ':71A:' . $this->chargesCode->value;
        }

        // Sender to Receiver Information
        if ($this->senderToReceiverInfo !== null) {
            $lines[] = ':72:' . $this->senderToReceiverInfo;
        }

        // Regulatory Reporting
        if ($this->regulatoryReporting !== null) {
            $lines[] = ':77B:' . $this->regulatoryReporting;
        }

        return implode("\r\n", $lines);
    }
}
