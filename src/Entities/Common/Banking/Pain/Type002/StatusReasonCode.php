<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : StatusReasonCode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Pain\Type002;

/**
 * Status Reason Code für pain.002 Payment Status Report.
 * 
 * ISO 20022 External Status Reason Codes.
 * 
 * @package CommonToolkit\Entities\Common\Banking\Pain\Type002
 */
enum StatusReasonCode: string {
    // Akzeptiert
    case ACCEPTED_CUSTOMER_PROFILE = 'ACCP';
    case ACCEPTED_SETTLEMENT_IN_PROCESS = 'ACSP';
    case ACCEPTED_WITH_CHANGE = 'ACWC';
    case ACCEPTED_TECHNICAL_VALIDATION = 'ACTC';

        // Abgelehnt (Reject)
    case REJECTED = 'RJCT';

        // Pending
    case PENDING = 'PDNG';

        // Häufige Ablehnungsgründe
    case ACCOUNT_BLOCKED = 'AC04';
    case ACCOUNT_CLOSED = 'AC01';
    case ACCOUNT_INVALID = 'AC02';
    case ACCOUNT_NOT_FOUND = 'AC03';
    case AGENT_SUSPENDED = 'AG01';
    case AMOUNT_NOT_ALLOWED = 'AM01';
    case AMOUNT_EXCEEDS_LIMIT = 'AM02';
    case CURRENCY_NOT_ALLOWED = 'AM03';
    case INSUFFICIENT_FUNDS = 'AM04';
    case DUPLICATE_MESSAGE = 'AM05';
    case TOO_LOW_AMOUNT = 'AM06';
    case BLOCKED_AMOUNT = 'AM07';
    case INVALID_BANK_OP_CODE = 'BE01';
    case INVALID_COUNTRY = 'BE04';
    case INVALID_CREDITOR = 'BE05';
    case INVALID_DEBTOR = 'BE06';
    case INVALID_AGENT = 'BE07';
    case CUSTOMER_DECEASED = 'MD07';
    case NO_MANDATE = 'MS02';
    case MANDATE_CANCELLED = 'MS03';
    case REGULATORY_REASON = 'RR01';
    case SPECIFIC_SERVICE_OFFERED = 'SL01';
    case TECHNICAL_REJECTION = 'TM01';
    case CUT_OFF_TIME = 'TS01';

    /**
     * Prüft, ob der Status akzeptiert wurde.
     */
    public function isAccepted(): bool {
        return match ($this) {
            self::ACCEPTED_CUSTOMER_PROFILE,
            self::ACCEPTED_SETTLEMENT_IN_PROCESS,
            self::ACCEPTED_WITH_CHANGE,
            self::ACCEPTED_TECHNICAL_VALIDATION => true,
            default => false,
        };
    }

    /**
     * Prüft, ob der Status abgelehnt wurde.
     */
    public function isRejected(): bool {
        return $this === self::REJECTED;
    }

    /**
     * Prüft, ob der Status ausstehend ist.
     */
    public function isPending(): bool {
        return $this === self::PENDING;
    }

    /**
     * Gibt eine Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::ACCEPTED_CUSTOMER_PROFILE => 'Kundenprofil akzeptiert',
            self::ACCEPTED_SETTLEMENT_IN_PROCESS => 'Akzeptiert, Abwicklung läuft',
            self::ACCEPTED_WITH_CHANGE => 'Akzeptiert mit Änderung',
            self::ACCEPTED_TECHNICAL_VALIDATION => 'Technisch validiert',
            self::REJECTED => 'Abgelehnt',
            self::PENDING => 'Ausstehend',
            self::ACCOUNT_BLOCKED => 'Konto gesperrt',
            self::ACCOUNT_CLOSED => 'Konto geschlossen',
            self::ACCOUNT_INVALID => 'Konto ungültig',
            self::ACCOUNT_NOT_FOUND => 'Konto nicht gefunden',
            self::AGENT_SUSPENDED => 'Agent suspendiert',
            self::AMOUNT_NOT_ALLOWED => 'Betrag nicht erlaubt',
            self::AMOUNT_EXCEEDS_LIMIT => 'Betrag übersteigt Limit',
            self::CURRENCY_NOT_ALLOWED => 'Währung nicht erlaubt',
            self::INSUFFICIENT_FUNDS => 'Ungenügende Deckung',
            self::DUPLICATE_MESSAGE => 'Duplikat',
            self::TOO_LOW_AMOUNT => 'Betrag zu niedrig',
            self::BLOCKED_AMOUNT => 'Betrag blockiert',
            self::INVALID_BANK_OP_CODE => 'Ungültiger Bankcode',
            self::INVALID_COUNTRY => 'Ungültiges Land',
            self::INVALID_CREDITOR => 'Ungültiger Gläubiger',
            self::INVALID_DEBTOR => 'Ungültiger Schuldner',
            self::INVALID_AGENT => 'Ungültiger Agent',
            self::CUSTOMER_DECEASED => 'Kunde verstorben',
            self::NO_MANDATE => 'Kein Mandat vorhanden',
            self::MANDATE_CANCELLED => 'Mandat widerrufen',
            self::REGULATORY_REASON => 'Regulatorische Gründe',
            self::SPECIFIC_SERVICE_OFFERED => 'Spezifischer Service',
            self::TECHNICAL_REJECTION => 'Technische Ablehnung',
            self::CUT_OFF_TIME => 'Annahmeschluss überschritten',
        };
    }
}
