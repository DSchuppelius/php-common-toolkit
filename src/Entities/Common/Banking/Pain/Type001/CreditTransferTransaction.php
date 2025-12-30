<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditTransferTransaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type001;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\CurrencyCode;

/**
 * Credit Transfer Transaction für pain.001-Nachrichten (CdtTrfTxInf).
 * 
 * Einzelne Überweisung innerhalb einer PaymentInstruction.
 * Enthält alle Details zu Betrag, Empfänger und Verwendungszweck.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type001
 */
final readonly class CreditTransferTransaction {
    public function __construct(
        private PaymentIdentification $paymentId,
        private float $amount,
        private CurrencyCode $currency,
        private PartyIdentification $creditor,
        private ?AccountIdentification $creditorAccount = null,
        private ?FinancialInstitution $creditorAgent = null,
        private ?PartyIdentification $ultimateCreditor = null,
        private ?RemittanceInformation $remittanceInformation = null,
        private ?ChargesCode $chargeBearer = null,
        private ?string $purposeCode = null,
        private ?float $exchangeRate = null,
        private ?CurrencyCode $instructedCurrency = null,
        private ?float $instructedAmount = null
    ) {
    }

    /**
     * Gibt die Zahlungsidentifikation zurück (PmtId).
     */
    public function getPaymentId(): PaymentIdentification {
        return $this->paymentId;
    }

    /**
     * Gibt den Betrag zurück (Amt/InstdAmt).
     */
    public function getAmount(): float {
        return $this->amount;
    }

    /**
     * Gibt die Währung zurück.
     */
    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    /**
     * Gibt den Begünstigten zurück (Cdtr).
     */
    public function getCreditor(): PartyIdentification {
        return $this->creditor;
    }

    /**
     * Gibt das Konto des Begünstigten zurück (CdtrAcct).
     */
    public function getCreditorAccount(): ?AccountIdentification {
        return $this->creditorAccount;
    }

    /**
     * Gibt die Bank des Begünstigten zurück (CdtrAgt).
     */
    public function getCreditorAgent(): ?FinancialInstitution {
        return $this->creditorAgent;
    }

    /**
     * Gibt den letztendlichen Begünstigten zurück (UltmtCdtr).
     */
    public function getUltimateCreditor(): ?PartyIdentification {
        return $this->ultimateCreditor;
    }

    /**
     * Gibt den Verwendungszweck zurück (RmtInf).
     */
    public function getRemittanceInformation(): ?RemittanceInformation {
        return $this->remittanceInformation;
    }

    /**
     * Gibt den Gebührenträger zurück (ChrgBr).
     */
    public function getChargeBearer(): ?ChargesCode {
        return $this->chargeBearer;
    }

    /**
     * Gibt den Zweckcode zurück (Purp/Cd).
     * Z.B. SALA für Gehalt, RENT für Miete.
     */
    public function getPurposeCode(): ?string {
        return $this->purposeCode;
    }

    /**
     * Gibt den Wechselkurs zurück (XchgRateInf).
     */
    public function getExchangeRate(): ?float {
        return $this->exchangeRate;
    }

    /**
     * Gibt die ursprünglich angewiesene Währung zurück.
     */
    public function getInstructedCurrency(): ?CurrencyCode {
        return $this->instructedCurrency;
    }

    /**
     * Gibt den ursprünglich angewiesenen Betrag zurück.
     */
    public function getInstructedAmount(): ?float {
        return $this->instructedAmount;
    }

    /**
     * Erstellt eine einfache Überweisung.
     */
    public static function create(
        string $endToEndId,
        float $amount,
        CurrencyCode $currency,
        PartyIdentification $creditor,
        AccountIdentification $creditorAccount,
        ?string $remittanceInfo = null
    ): self {
        return new self(
            paymentId: PaymentIdentification::fromEndToEndId($endToEndId),
            amount: $amount,
            currency: $currency,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            remittanceInformation: $remittanceInfo !== null
                ? RemittanceInformation::fromText($remittanceInfo)
                : null
        );
    }

    /**
     * Erstellt eine SEPA-Überweisung mit BIC.
     */
    public static function sepa(
        string $endToEndId,
        float $amount,
        string $creditorName,
        string $creditorIban,
        string $creditorBic,
        ?string $remittanceInfo = null
    ): self {
        return new self(
            paymentId: PaymentIdentification::fromEndToEndId($endToEndId),
            amount: $amount,
            currency: CurrencyCode::Euro,
            creditor: PartyIdentification::fromName($creditorName),
            creditorAccount: AccountIdentification::fromIban($creditorIban),
            creditorAgent: FinancialInstitution::fromBic($creditorBic),
            remittanceInformation: $remittanceInfo !== null
                ? RemittanceInformation::fromText($remittanceInfo)
                : null,
            chargeBearer: ChargesCode::SLEV // SEPA-Level
        );
    }
}
