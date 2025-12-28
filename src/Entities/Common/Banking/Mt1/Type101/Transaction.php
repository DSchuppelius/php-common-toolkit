<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Transaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Mt1\Type101;

use CommonToolkit\Entities\Common\Banking\Mt1\Party;
use CommonToolkit\Entities\Common\Banking\Mt1\TransferDetails;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use DateTimeImmutable;

/**
 * MT101 Einzeltransaktion innerhalb eines Request for Transfer.
 * 
 * MT101 kann mehrere Transaktionen (Sequence B) in einer Nachricht enthalten.
 * Jede Transaktion hat eigene Details aber teilt sich gemeinsame Header-Daten.
 * 
 * Felder pro Transaktion:
 * - :21:  Transaction Reference
 * - :32B: Currency/Amount
 * - :57a: Account With Institution
 * - :59a: Beneficiary
 * - :70:  Remittance Information
 * - :71A: Details of Charges
 * 
 * @package CommonToolkit\Entities\Common\Banking\Mt1\Type101
 */
final readonly class Transaction {
    public function __construct(
        private string $transactionReference,
        private TransferDetails $transferDetails,
        private Party $beneficiary,
        private ?Party $accountWithInstitution = null,
        private ?string $remittanceInfo = null,
        private ?ChargesCode $chargesCode = null
    ) {
    }

    /**
     * Gibt die Transaction Reference zurück (Feld :21:).
     */
    public function getTransactionReference(): string {
        return $this->transactionReference;
    }

    /**
     * Gibt die Überweisungsdetails zurück.
     */
    public function getTransferDetails(): TransferDetails {
        return $this->transferDetails;
    }

    /**
     * Gibt den Begünstigten zurück (Feld :59:).
     */
    public function getBeneficiary(): Party {
        return $this->beneficiary;
    }

    /**
     * Gibt die Account With Institution zurück (Feld :57a:).
     */
    public function getAccountWithInstitution(): ?Party {
        return $this->accountWithInstitution;
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
     * Gibt den Betrag zurück.
     */
    public function getAmount(): float {
        return $this->transferDetails->getAmount();
    }

    /**
     * Serialisiert als SWIFT-Felder.
     */
    public function __toString(): string {
        $lines = [];

        $lines[] = ':21:' . $this->transactionReference;
        $lines[] = ':32B:' . $this->transferDetails->toField32B();

        if ($this->accountWithInstitution !== null) {
            if ($this->accountWithInstitution->isBicOnly()) {
                $lines[] = ':57A:' . $this->accountWithInstitution->toOptionA();
            } else {
                $lines[] = ':57D:' . $this->accountWithInstitution->toOptionK();
            }
        }

        $lines[] = ':59:' . $this->beneficiary->toOptionK();

        if ($this->remittanceInfo !== null) {
            $lines[] = ':70:' . $this->remittanceInfo;
        }

        if ($this->chargesCode !== null) {
            $lines[] = ':71A:' . $this->chargesCode->value;
        }

        return implode("\r\n", $lines);
    }
}
