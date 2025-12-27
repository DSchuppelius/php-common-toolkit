<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Reference.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Camt\Type53;

/**
 * Repräsentiert alle Referenzen einer CAMT.053 Transaktion.
 * 
 * Basierend auf den SEPA-Referenzfeldern:
 * - EndToEndId: Ende-zu-Ende-Identifikation (EREF+)
 * - MandateId: Mandatsreferenz (MREF+)
 * - CreditorId: Gläubiger-ID (CRED+)
 * - EntryReference: Umsatzreferenz der Bank
 * - AccountServicerReference: Referenz des kontoführenden Instituts
 * - PaymentInformationId: Zahlungsinformations-ID
 * - InstructionId: Auftrags-ID (KREF+)
 * 
 * @package CommonToolkit\Entities\Common\Banking\Camt053
 */
final class Reference {
    private ?string $endToEndId;
    private ?string $mandateId;
    private ?string $creditorId;
    private ?string $entryReference;
    private ?string $accountServicerReference;
    private ?string $paymentInformationId;
    private ?string $instructionId;
    private ?string $additional;

    public function __construct(
        ?string $endToEndId = null,
        ?string $mandateId = null,
        ?string $creditorId = null,
        ?string $entryReference = null,
        ?string $accountServicerReference = null,
        ?string $paymentInformationId = null,
        ?string $instructionId = null,
        ?string $additional = null
    ) {
        $this->endToEndId = $endToEndId;
        $this->mandateId = $mandateId;
        $this->creditorId = $creditorId;
        $this->entryReference = $entryReference;
        $this->accountServicerReference = $accountServicerReference;
        $this->paymentInformationId = $paymentInformationId;
        $this->instructionId = $instructionId;
        $this->additional = $additional;
    }

    /**
     * Ende-zu-Ende-Identifikation (EREF+).
     * Vom Auftraggeber vergebene eindeutige Referenz.
     */
    public function getEndToEndId(): ?string {
        return $this->endToEndId;
    }

    /**
     * Mandatsreferenz (MREF+).
     * Nur bei SEPA-Lastschriften.
     */
    public function getMandateId(): ?string {
        return $this->mandateId;
    }

    /**
     * Gläubiger-Identifikation (CRED+).
     * Nur bei SEPA-Lastschriften.
     */
    public function getCreditorId(): ?string {
        return $this->creditorId;
    }

    /**
     * Umsatzreferenz des kontoführenden Instituts.
     */
    public function getEntryReference(): ?string {
        return $this->entryReference;
    }

    /**
     * Referenz des kontoführenden Instituts.
     * Entspricht Subfeld 10 im MT940 :61:-Feld.
     */
    public function getAccountServicerReference(): ?string {
        return $this->accountServicerReference;
    }

    /**
     * Zahlungsinformations-ID.
     */
    public function getPaymentInformationId(): ?string {
        return $this->paymentInformationId;
    }

    /**
     * Auftrags-ID (KREF+).
     */
    public function getInstructionId(): ?string {
        return $this->instructionId;
    }

    /**
     * Zusätzliche Referenz.
     */
    public function getAdditional(): ?string {
        return $this->additional;
    }

    /**
     * Prüft, ob mindestens eine Referenz gesetzt ist.
     */
    public function hasAnyReference(): bool {
        return $this->endToEndId !== null
            || $this->mandateId !== null
            || $this->creditorId !== null
            || $this->entryReference !== null
            || $this->accountServicerReference !== null
            || $this->paymentInformationId !== null
            || $this->instructionId !== null
            || $this->additional !== null;
    }

    /**
     * Gibt die primäre Referenz zurück (erste gefundene).
     */
    public function getPrimaryReference(): ?string {
        return $this->endToEndId
            ?? $this->accountServicerReference
            ?? $this->entryReference
            ?? $this->mandateId
            ?? $this->instructionId
            ?? $this->additional;
    }

    public function __toString(): string {
        $parts = array_filter([
            $this->endToEndId ? "EREF+{$this->endToEndId}" : null,
            $this->mandateId ? "MREF+{$this->mandateId}" : null,
            $this->creditorId ? "CRED+{$this->creditorId}" : null,
            $this->instructionId ? "KREF+{$this->instructionId}" : null,
            $this->additional,
        ]);

        return implode(' ', $parts);
    }
}
