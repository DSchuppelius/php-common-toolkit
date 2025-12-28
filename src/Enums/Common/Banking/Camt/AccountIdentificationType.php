<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AccountIdentificationType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalAccountIdentification1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * AccountIdentificationType - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalAccountIdentification1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum AccountIdentificationType: string {
    /**
     * AIIN - IssuerIdentificationNumber
     * Issuer Identification Number (IIN) - identifies a card issuing institution in an international in...
     */
    case AIIN = 'AIIN';

    /**
     * BBAN - BBANIdentifier
     * Basic Bank Account Number (BBAN) - identifier used nationally by financial institutions, ie, in i...
     */
    case BBAN = 'BBAN';

    /**
     * CUID - CHIPSUniversalIdentifier
     * (United States) Clearing House Interbank Payments System (CHIPS) Universal Identification (UID) -...
     */
    case CUID = 'CUID';

    /**
     * UPIC - UPICIdentifier
     * Universal Payment Identification Code (UPIC) - identifier used by the New York Clearing House to ...
     */
    case UPIC = 'UPIC';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::AIIN => 'IssuerIdentificationNumber',
            self::BBAN => 'BBANIdentifier',
            self::CUID => 'CHIPSUniversalIdentifier',
            self::UPIC => 'UPICIdentifier',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::AIIN => 'Issuer Identification Number (IIN) - identifies a card issuing institution in an international interchange environment. Issued by ABA (American Bankers Association).',
            self::BBAN => 'Basic Bank Account Number (BBAN) - identifier used nationally by financial institutions, ie, in individual countries, generally as part of a National Account Numbering Scheme(s), to uniquely identi...',
            self::CUID => '(United States) Clearing House Interbank Payments System (CHIPS) Universal Identification (UID) - identifies entities that own accounts at CHIPS participating financial institutions, through which ...',
            self::UPIC => 'Universal Payment Identification Code (UPIC) - identifier used by the New York Clearing House to mask confidential data, such as bank accounts and bank routing numbers. UPIC numbers remain with bus...',
        };
    }

    /**
     * Factory-Methode aus String.
     */
    public static function fromString(string $value): ?self {
        return self::tryFrom(strtoupper(trim($value)));
    }

    /**
     * Prüft ob der Wert ein gültiger Code ist.
     */
    public static function isValid(string $value): bool {
        return self::tryFrom(strtoupper(trim($value))) !== null;
    }
}
