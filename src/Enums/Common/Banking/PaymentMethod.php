<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentMethod.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * Zahlungsmethode für pain-Nachrichten (PmtMtd).
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum PaymentMethod: string {
    /** Überweisung (Transfer) - Standard für SEPA und Auslandsüberweisungen */
    case TRANSFER = 'TRF';

    /** Scheck (Cheque) */
    case CHEQUE = 'CHK';

    /** Direktlastschrift (Direct Debit) - für pain.008 */
    case DIRECT_DEBIT = 'DD';

    /**
     * Gibt die deutsche Beschreibung zurück.
     */
    public function description(): string {
        return match ($this) {
            self::TRANSFER => 'Überweisung',
            self::CHEQUE => 'Scheck',
            self::DIRECT_DEBIT => 'Lastschrift',
        };
    }

    /**
     * Erstellt PaymentMethod aus String.
     */
    public static function fromString(string $code): self {
        return match (strtoupper(trim($code))) {
            'TRF', 'TRANSFER' => self::TRANSFER,
            'CHK', 'CHEQUE' => self::CHEQUE,
            'DD', 'DIRECT_DEBIT', 'DIRECTDEBIT' => self::DIRECT_DEBIT,
            default => self::TRANSFER,
        };
    }

    /**
     * Prüft, ob die Methode für pain.001 geeignet ist.
     */
    public function isPain001(): bool {
        return in_array($this, [self::TRANSFER, self::CHEQUE]);
    }

    /**
     * Prüft, ob die Methode für pain.008 geeignet ist.
     */
    public function isPain008(): bool {
        return $this === self::DIRECT_DEBIT;
    }

    /**
     * Gibt den Standard für SEPA-Überweisungen zurück.
     */
    public static function defaultSepa(): self {
        return self::TRANSFER;
    }
}
