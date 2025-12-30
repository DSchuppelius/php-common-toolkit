<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionStatus.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

/**
 * Transaction Status für pain.002 (TxSts).
 * 
 * ISO 20022 Transaktionsstatus.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
 */
enum TransactionStatus: string {
    case ACCEPTED_SETTLEMENT_COMPLETED = 'ACSC';
    case ACCEPTED_SETTLEMENT_IN_PROCESS = 'ACSP';
    case ACCEPTED_TECHNICAL_VALIDATION = 'ACTC';
    case ACCEPTED_WITH_CHANGE = 'ACWC';
    case ACCEPTED_WITHOUT_POSTING = 'ACWP';
    case ACCEPTED_CUSTOMER_PROFILE = 'ACCP';
    case ACCEPTED_FUNDS_CHECKED = 'ACFC';
    case PARTIALLY_ACCEPTED = 'PART';
    case PENDING = 'PDNG';
    case RECEIVED = 'RCVD';
    case REJECTED = 'RJCT';
    case CANCELLED = 'CANC';

    /**
     * Prüft, ob die Transaktion erfolgreich war.
     */
    public function isSuccessful(): bool {
        return match ($this) {
            self::ACCEPTED_SETTLEMENT_COMPLETED,
            self::ACCEPTED_SETTLEMENT_IN_PROCESS,
            self::ACCEPTED_TECHNICAL_VALIDATION,
            self::ACCEPTED_WITH_CHANGE,
            self::ACCEPTED_WITHOUT_POSTING,
            self::ACCEPTED_CUSTOMER_PROFILE,
            self::ACCEPTED_FUNDS_CHECKED => true,
            default => false,
        };
    }

    /**
     * Prüft, ob die Transaktion abgelehnt wurde.
     */
    public function isRejected(): bool {
        return $this === self::REJECTED;
    }

    /**
     * Prüft, ob die Transaktion noch in Bearbeitung ist.
     */
    public function isPending(): bool {
        return match ($this) {
            self::PENDING,
            self::RECEIVED,
            self::PARTIALLY_ACCEPTED => true,
            default => false,
        };
    }

    /**
     * Gibt eine Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::ACCEPTED_SETTLEMENT_COMPLETED => 'Abwicklung abgeschlossen',
            self::ACCEPTED_SETTLEMENT_IN_PROCESS => 'Abwicklung in Bearbeitung',
            self::ACCEPTED_TECHNICAL_VALIDATION => 'Technisch validiert',
            self::ACCEPTED_WITH_CHANGE => 'Akzeptiert mit Änderungen',
            self::ACCEPTED_WITHOUT_POSTING => 'Akzeptiert ohne Buchung',
            self::ACCEPTED_CUSTOMER_PROFILE => 'Kundenprofil validiert',
            self::ACCEPTED_FUNDS_CHECKED => 'Deckung geprüft',
            self::PARTIALLY_ACCEPTED => 'Teilweise akzeptiert',
            self::PENDING => 'Ausstehend',
            self::RECEIVED => 'Empfangen',
            self::REJECTED => 'Abgelehnt',
            self::CANCELLED => 'Storniert',
        };
    }
}
