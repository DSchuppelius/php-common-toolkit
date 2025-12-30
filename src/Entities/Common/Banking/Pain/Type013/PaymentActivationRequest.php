<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentActivationRequest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type013;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;

/**
 * Payment Activation Request für pain.013.
 * 
 * Einzelne Zahlungsaktivierungsanfrage vom Kreditor.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type013
 */
final readonly class PaymentActivationRequest {
    public function __construct(
        private string $instructionId,
        private string $endToEndId,
        private float $amount,
        private CurrencyCode $currency,
        private PartyIdentification $debtor,
        private AccountIdentification $debtorAccount,
        private FinancialInstitution $debtorAgent,
        private PartyIdentification $creditor,
        private AccountIdentification $creditorAccount,
        private FinancialInstitution $creditorAgent,
        private ?DateTimeImmutable $requestedExecutionDate = null,
        private ?string $remittanceInformation = null,
        private ?string $paymentPurpose = null
    ) {
    }

    public static function create(
        string $endToEndId,
        float $amount,
        string $debtorName,
        string $debtorIban,
        string $debtorBic,
        string $creditorName,
        string $creditorIban,
        string $creditorBic,
        ?string $remittanceInformation = null
    ): self {
        return new self(
            instructionId: 'INST-' . uniqid(),
            endToEndId: $endToEndId,
            amount: $amount,
            currency: CurrencyCode::Euro,
            debtor: new PartyIdentification(name: $debtorName),
            debtorAccount: new AccountIdentification(iban: $debtorIban),
            debtorAgent: new FinancialInstitution(bic: $debtorBic),
            creditor: new PartyIdentification(name: $creditorName),
            creditorAccount: new AccountIdentification(iban: $creditorIban),
            creditorAgent: new FinancialInstitution(bic: $creditorBic),
            requestedExecutionDate: new DateTimeImmutable(),
            remittanceInformation: $remittanceInformation
        );
    }

    public function getInstructionId(): string {
        return $this->instructionId;
    }

    public function getEndToEndId(): string {
        return $this->endToEndId;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function getDebtor(): PartyIdentification {
        return $this->debtor;
    }

    public function getDebtorAccount(): AccountIdentification {
        return $this->debtorAccount;
    }

    public function getDebtorAgent(): FinancialInstitution {
        return $this->debtorAgent;
    }

    public function getCreditor(): PartyIdentification {
        return $this->creditor;
    }

    public function getCreditorAccount(): AccountIdentification {
        return $this->creditorAccount;
    }

    public function getCreditorAgent(): FinancialInstitution {
        return $this->creditorAgent;
    }

    public function getRequestedExecutionDate(): ?DateTimeImmutable {
        return $this->requestedExecutionDate;
    }

    public function getRemittanceInformation(): ?string {
        return $this->remittanceInformation;
    }

    public function getPaymentPurpose(): ?string {
        return $this->paymentPurpose;
    }
}
