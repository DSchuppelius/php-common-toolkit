<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PersonIdentification.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalPersonIdentification1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * PersonIdentification - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalPersonIdentification1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum PersonIdentification: string {
    /**
     * ARNU - AlienRegistrationNumber
     * Number assigned by a social security agency to identify a non-resident person.
     */
    case ARNU = 'ARNU';

    /**
     * CCPT - PassportNumber
     * Number assigned by an authority to identify the passport number of a person.
     */
    case CCPT = 'CCPT';

    /**
     * CUST - CustomerIdentificationNumber
     * Number assigned by an issuer to identify a customer.
     */
    case CUST = 'CUST';

    /**
     * DRLC - DriversLicenseNumber
     * Number assigned by an authority to identify a driver's license.
     */
    case DRLC = 'DRLC';

    /**
     * EMPL - EmployeeIdentificationNumber
     * Number assigned by a registration authority to an employee.
     */
    case EMPL = 'EMPL';

    /**
     * NIDN - NationalIdentityNumber
     * Number assigned by an authority to identify the national identity number of a person.
     */
    case NIDN = 'NIDN';

    /**
     * SOSE - SocialSecurityNumber
     * Number assigned by an authority to identify the social security number of a person.
     */
    case SOSE = 'SOSE';

    /**
     * TXID - TaxIdentificationNumber
     * Number assigned by a tax authority to identify a person.
     */
    case TXID = 'TXID';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ARNU => 'AlienRegistrationNumber',
            self::CCPT => 'PassportNumber',
            self::CUST => 'CustomerIdentificationNumber',
            self::DRLC => 'DriversLicenseNumber',
            self::EMPL => 'EmployeeIdentificationNumber',
            self::NIDN => 'NationalIdentityNumber',
            self::SOSE => 'SocialSecurityNumber',
            self::TXID => 'TaxIdentificationNumber',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ARNU => 'Number assigned by a social security agency to identify a non-resident person.',
            self::CCPT => 'Number assigned by an authority to identify the passport number of a person.',
            self::CUST => 'Number assigned by an issuer to identify a customer.',
            self::DRLC => 'Number assigned by an authority to identify a driver\'s license.',
            self::EMPL => 'Number assigned by a registration authority to an employee.',
            self::NIDN => 'Number assigned by an authority to identify the national identity number of a person.',
            self::SOSE => 'Number assigned by an authority to identify the social security number of a person.',
            self::TXID => 'Number assigned by a tax authority to identify a person.',
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
