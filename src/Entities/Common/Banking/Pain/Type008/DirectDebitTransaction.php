<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DirectDebitTransaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type008;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Direct Debit Transaction Information für pain.008 (DrctDbtTxInf).
 * 
 * Einzelne Lastschrift-Transaktion mit Debtor-Informationen.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type008
 */
final readonly class DirectDebitTransaction {
    public function __construct(
        private PaymentIdentification $paymentId,
        private float $amount,
        private CurrencyCode $currency,
        private MandateInformation $mandateInfo,
        private PartyIdentification $debtor,
        private AccountIdentification $debtorAccount,
        private ?FinancialInstitution $debtorAgent = null,
        private ?PartyIdentification $ultimateDebtor = null,
        private ?RemittanceInformation $remittanceInformation = null,
        private ?string $purpose = null
    ) {
    }

    /**
     * Factory für SEPA-Lastschrift.
     */
    public static function sepa(
        string $endToEndId,
        float $amount,
        string $mandateId,
        DateTimeImmutable $mandateDate,
        string $debtorName,
        string $debtorIban,
        ?string $debtorBic = null,
        ?string $remittanceInfo = null
    ): self {
        return new self(
            paymentId: PaymentIdentification::create($endToEndId),
            amount: $amount,
            currency: CurrencyCode::Euro,
            mandateInfo: MandateInformation::create($mandateId, $mandateDate),
            debtor: new PartyIdentification(name: $debtorName),
            debtorAccount: new AccountIdentification(iban: $debtorIban),
            debtorAgent: $debtorBic ? new FinancialInstitution(bic: $debtorBic) : null,
            remittanceInformation: $remittanceInfo ? RemittanceInformation::fromText($remittanceInfo) : null
        );
    }

    public function getPaymentId(): PaymentIdentification {
        return $this->paymentId;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getMandateInfo(): MandateInformation {
        return $this->mandateInfo;
    }

    public function getDebtor(): PartyIdentification {
        return $this->debtor;
    }

    public function getDebtorAccount(): AccountIdentification {
        return $this->debtorAccount;
    }

    public function getDebtorAgent(): ?FinancialInstitution {
        return $this->debtorAgent;
    }

    public function getUltimateDebtor(): ?PartyIdentification {
        return $this->ultimateDebtor;
    }

    public function getRemittanceInformation(): ?RemittanceInformation {
        return $this->remittanceInformation;
    }

    public function getPurpose(): ?string {
        return $this->purpose;
    }
}
