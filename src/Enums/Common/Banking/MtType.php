<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking;

/**
 * MT Nachrichtentypen gemäß SWIFT-Spezifikation.
 * 
 * SWIFT-Nachrichten verschiedener Kategorien:
 * - Kategorie 1: Customer Payments & Cheques
 * - Kategorie 9: Cash Management & Customer Status
 * 
 * @package CommonToolkit\Enums\Common\Banking
 */
enum MtType: string {
    // ========================================
    // Kategorie 1: Customer Payments & Cheques
    // ========================================

    /**
     * MT101 - Request for Transfer
     * Zahlungsauftrag des Kunden an die Bank
     */
    case MT101 = 'MT101';

    /**
     * MT103 - Single Customer Credit Transfer
     * Einzelne Kundenüberweisung (SEPA/International)
     */
    case MT103 = 'MT103';

    // ========================================
    // Kategorie 9: Cash Management & Customer Status
    // ========================================

    /**
     * MT900 - Confirmation of Debit
     * Belastungsbestätigung (Debit Advice)
     */
    case MT900 = 'MT900';

    /**
     * MT910 - Confirmation of Credit
     * Gutschriftsbestätigung (Credit Advice)
     */
    case MT910 = 'MT910';

    /**
     * MT920 - Customer Statement Message Request
     * Anforderung von Umsatz- und Saldeninformationen
     */
    case MT920 = 'MT920';

    /**
     * MT940 - Customer Statement Message
     * Tagesendeauszug (End of Day Statement)
     * Äquivalent zu CAMT.053
     */
    case MT940 = 'MT940';

    /**
     * MT941 - Balance Report
     * Saldeninformation ohne Umsatzdetails
     */
    case MT941 = 'MT941';

    /**
     * MT942 - Interim Transaction Report
     * Untertägige Umsatzinformation (Intraday)
     * Äquivalent zu CAMT.052
     */
    case MT942 = 'MT942';

    /**
     * Gibt den deutschen Beschreibungstext zurück.
     */
    public function getDescription(): string {
        return match ($this) {
            self::MT101 => 'Zahlungsauftrag (Request for Transfer)',
            self::MT103 => 'Einzelüberweisung (Single Credit Transfer)',
            self::MT900 => 'Belastungsbestätigung (Confirmation of Debit)',
            self::MT910 => 'Gutschriftsbestätigung (Confirmation of Credit)',
            self::MT920 => 'Anforderung Umsatz-/Saldeninformationen',
            self::MT940 => 'Tagesendeauszug (Customer Statement)',
            self::MT941 => 'Saldeninformation (Balance Report)',
            self::MT942 => 'Untertägige Umsatzinformation (Interim Report)',
        };
    }

    /**
     * Gibt den SWIFT-Nachrichtennamen zurück.
     */
    public function getMessageName(): string {
        return match ($this) {
            self::MT101 => 'Request for Transfer',
            self::MT103 => 'Single Customer Credit Transfer',
            self::MT900 => 'Confirmation of Debit',
            self::MT910 => 'Confirmation of Credit',
            self::MT920 => 'Customer Statement Message Request',
            self::MT940 => 'Customer Statement Message',
            self::MT941 => 'Balance Report',
            self::MT942 => 'Interim Transaction Report',
        };
    }

    /**
     * Gibt den numerischen Nachrichtentyp zurück.
     */
    public function getNumericType(): int {
        return match ($this) {
            self::MT101 => 101,
            self::MT103 => 103,
            self::MT900 => 900,
            self::MT910 => 910,
            self::MT920 => 920,
            self::MT940 => 940,
            self::MT941 => 941,
            self::MT942 => 942,
        };
    }

    /**
     * Gibt die SWIFT-Kategorie zurück.
     */
    public function getCategory(): int {
        return match ($this) {
            self::MT101, self::MT103 => 1,  // Customer Payments & Cheques
            self::MT900, self::MT910, self::MT920, self::MT940, self::MT941, self::MT942 => 9,  // Cash Management
        };
    }

    /**
     * Gibt die Kategoriebeschreibung zurück.
     */
    public function getCategoryDescription(): string {
        return match ($this->getCategory()) {
            1 => 'Customer Payments and Cheques',
            9 => 'Cash Management and Customer Status',
            default => 'Unknown',
        };
    }

    /**
     * Prüft ob dieser Typ ein Zahlungsauftrag ist.
     */
    public function isPaymentInitiation(): bool {
        return match ($this) {
            self::MT101, self::MT103 => true,
            default => false,
        };
    }

    /**
     * Prüft ob dieser Typ eine Bestätigung ist.
     */
    public function isConfirmation(): bool {
        return match ($this) {
            self::MT900, self::MT910 => true,
            default => false,
        };
    }

    /**
     * Prüft ob dieser Typ ein Statement/Report ist.
     */
    public function isStatement(): bool {
        return match ($this) {
            self::MT940, self::MT941, self::MT942 => true,
            default => false,
        };
    }

    /**
     * Prüft ob dieser Typ Transaktionen enthält.
     */
    public function hasTransactions(): bool {
        return match ($this) {
            self::MT101 => true,   // Zahlungsaufträge
            self::MT103 => true,   // Einzelüberweisung
            self::MT900 => false,  // Nur Bestätigung
            self::MT910 => false,  // Nur Bestätigung
            self::MT920 => false,  // Nur Anforderung
            self::MT940 => true,   // Vollständige Umsätze
            self::MT941 => false,  // Nur Salden
            self::MT942 => true,   // Untertägige Umsätze
        };
    }

    /**
     * Prüft ob dieser Typ Salden enthält.
     */
    public function hasBalances(): bool {
        return match ($this) {
            self::MT101 => false,
            self::MT103 => false,
            self::MT900 => false,
            self::MT910 => false,
            self::MT920 => false,
            self::MT940 => true,
            self::MT941 => true,
            self::MT942 => true,  // Nur Closing Balance
        };
    }

    /**
     * Gibt das entsprechende CAMT-Format zurück (wenn vorhanden).
     */
    public function getCamtEquivalent(): ?CamtType {
        return match ($this) {
            self::MT101 => null,               // pain.001 wäre das Äquivalent
            self::MT103 => null,               // pacs.008 wäre das Äquivalent
            self::MT900 => CamtType::CAMT054,  // Debit Notification
            self::MT910 => CamtType::CAMT054,  // Credit Notification
            self::MT940 => CamtType::CAMT053,  // End of Day
            self::MT942 => CamtType::CAMT052,  // Intraday
            default => null,
        };
    }

    /**
     * Erstellt einen MtType aus einem numerischen Wert.
     */
    public static function fromNumeric(int $type): ?self {
        return match ($type) {
            101 => self::MT101,
            103 => self::MT103,
            900 => self::MT900,
            910 => self::MT910,
            920 => self::MT920,
            940 => self::MT940,
            941 => self::MT941,
            942 => self::MT942,
            default => null,
        };
    }

    /**
     * Ermittelt den MT-Typ aus einer SWIFT-Nachricht.
     */
    public static function fromSwiftMessage(string $content): ?self {
        // Prüfe auf Application Header Block {2:...} mit Nachrichtentyp
        if (preg_match('/\{2:[OI]\s*(\d{3})/', $content, $matches)) {
            return self::fromNumeric((int) $matches[1]);
        }

        // Fallback: Nach typischen Feldkombinationen suchen
        // MT101: Request for Transfer
        if (str_contains($content, ':28D:') && str_contains($content, ':50H:')) {
            return self::MT101;
        }
        // MT103: Single Customer Credit Transfer
        if (str_contains($content, ':23B:') && str_contains($content, ':32A:')) {
            return self::MT103;
        }
        // MT900/MT910: Confirmation messages mit :32A: aber ohne :23B:
        if (str_contains($content, ':32A:') && str_contains($content, ':21:') && !str_contains($content, ':23B:')) {
            // MT910 hat typischerweise Credit-Betrag, MT900 Debit
            // Beide haben ähnliche Struktur, unterscheiden sich nur im Kontext
            return str_contains($content, ':25:') ? self::MT910 : self::MT900;
        }
        // MT940: End of Day Statement
        if (str_contains($content, ':60F:') && str_contains($content, ':62F:')) {
            return self::MT940;
        }
        // MT942: Interim Transaction Report
        if (str_contains($content, ':62M:') || str_contains($content, ':60M:')) {
            return self::MT942;
        }
        // MT941: Balance Report (nur Salden)
        if (str_contains($content, ':62F:') && !str_contains($content, ':61:')) {
            return self::MT941;
        }
        // MT920: Statement Request
        if (str_contains($content, ':20:') && str_contains($content, ':12:')) {
            return self::MT920;
        }

        return null;
    }

    /**
     * Gibt alle Statement-Typen zurück (MT94x).
     * 
     * @return self[]
     */
    public static function getStatementTypes(): array {
        return [self::MT940, self::MT941, self::MT942];
    }

    /**
     * Gibt alle Payment-Typen zurück (MT1xx).
     * 
     * @return self[]
     */
    public static function getPaymentTypes(): array {
        return [self::MT101, self::MT103];
    }

    /**
     * Gibt alle Confirmation-Typen zurück (MT9xx ohne Statements).
     * 
     * @return self[]
     */
    public static function getConfirmationTypes(): array {
        return [self::MT900, self::MT910];
    }
}
