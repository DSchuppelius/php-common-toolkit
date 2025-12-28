<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ClearingSystemIdentification.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalClearingSystemIdentification1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * ClearingSystemIdentification - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalClearingSystemIdentification1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum ClearingSystemIdentification: string {
    /**
     * ATBLZ - Austrian Bankleitzahl
     * Bank Branch code used in Austria
     */
    case ATBLZ = 'ATBLZ';

    /**
     * AUBSB - Australian Bank State Branch Code (BSB)
     * Bank Branch code used in Australia
     */
    case AUBSB = 'AUBSB';

    /**
     * CACPA - Canadian Payments Association Payment Routing Number
     * Bank Branch code used in Canada
     */
    case CACPA = 'CACPA';

    /**
     * CHBCC - Swiss Clearing Code (BC Code)
     * Bank Clearing number used in Switzerland
     */
    case CHBCC = 'CHBCC';

    /**
     * CHSIC - Swiss Clearing Code (SIC Code)
     * Bank Branch code used in clearing with Swiss Francs
     */
    case CHSIC = 'CHSIC';

    /**
     * CNAPS - CNAPS Identifier
     * Bank Branch code used in China
     */
    case CNAPS = 'CNAPS';

    /**
     * DEBLZ - German Bankleitzahl
     * Bank Branch code used in Germany
     */
    case DEBLZ = 'DEBLZ';

    /**
     * ESNCC - Spanish Domestic Interbanking Code
     * Bank Branch code used in Spain
     */
    case ESNCC = 'ESNCC';

    /**
     * GBDSC - UK Domestic Sort Code
     * Bank Branch code used in the UK
     */
    case GBDSC = 'GBDSC';

    /**
     * GRBIC - Helenic Bank Identification Code
     * Bank Branch code used in Greece
     */
    case GRBIC = 'GRBIC';

    /**
     * HKNCC - Hong Kong Bank Code
     * Bank Branch code used in Hong Kong
     */
    case HKNCC = 'HKNCC';

    /**
     * IENCC - Irish National Clearing Code
     * Bank Branch code used in Ireland
     */
    case IENCC = 'IENCC';

    /**
     * INFSC - Indian Financial System Code
     * Bank Branch code used in India
     */
    case INFSC = 'INFSC';

    /**
     * ITNCC - Italian Domestic Identification Code
     * Bank Branch code used in Italy
     */
    case ITNCC = 'ITNCC';

    /**
     * JPZGN - Japan Zengin Clearing Code
     * Bank Branch code used in Japan
     */
    case JPZGN = 'JPZGN';

    /**
     * NZNCC - New Zealand National Clearing Code
     * Bank Branch code used in New Zealand
     */
    case NZNCC = 'NZNCC';

    /**
     * PLKNR - Polish National Clearing Code
     * Bank Branch code used in Poland
     */
    case PLKNR = 'PLKNR';

    /**
     * PTNCC - Portuguese National Clearing Code
     * Bank Branch code used in Portugal
     */
    case PTNCC = 'PTNCC';

    /**
     * RUCBC - Russian Central Bank Identification Code
     * Bank Branch code used in Russia
     */
    case RUCBC = 'RUCBC';

    /**
     * SESBA - Sweden Bankgiro Clearing Code  
     * Bank Branch code used in Sweden
     */
    case SESBA = 'SESBA';

    /**
     * SGIBG - IBG Sort Code
     * Bank Branch code used in Singapore
     */
    case SGIBG = 'SGIBG';

    /**
     * THCBC - Thai Central Bank Identification Code
     * Bank Identification code used in Thailand
     */
    case THCBC = 'THCBC';

    /**
     * TWNCC - Financial Institution Code
     * Bank Branch code used in Taiwan
     */
    case TWNCC = 'TWNCC';

    /**
     * USABA - United States Routing Number (Fedwire, NACHA)
     * Routing Transit number assigned by the ABA for US financial institutons
     */
    case USABA = 'USABA';

    /**
     * USPID - CHIPS Participant Identifier
     * Bank identifier used by CHIPs in the US
     */
    case USPID = 'USPID';

    /**
     * ZANCC - South African National Clearing Code
     * Bank Branch code used in South Africa
     */
    case ZANCC = 'ZANCC';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ATBLZ => 'Austrian Bankleitzahl',
            self::AUBSB => 'Australian Bank State Branch Code (BSB)',
            self::CACPA => 'Canadian Payments Association Payment Routing Number',
            self::CHBCC => 'Swiss Clearing Code (BC Code)',
            self::CHSIC => 'Swiss Clearing Code (SIC Code)',
            self::CNAPS => 'CNAPS Identifier',
            self::DEBLZ => 'German Bankleitzahl',
            self::ESNCC => 'Spanish Domestic Interbanking Code',
            self::GBDSC => 'UK Domestic Sort Code',
            self::GRBIC => 'Helenic Bank Identification Code',
            self::HKNCC => 'Hong Kong Bank Code',
            self::IENCC => 'Irish National Clearing Code',
            self::INFSC => 'Indian Financial System Code',
            self::ITNCC => 'Italian Domestic Identification Code',
            self::JPZGN => 'Japan Zengin Clearing Code',
            self::NZNCC => 'New Zealand National Clearing Code',
            self::PLKNR => 'Polish National Clearing Code',
            self::PTNCC => 'Portuguese National Clearing Code',
            self::RUCBC => 'Russian Central Bank Identification Code',
            self::SESBA => 'Sweden Bankgiro Clearing Code  ',
            self::SGIBG => 'IBG Sort Code',
            self::THCBC => 'Thai Central Bank Identification Code',
            self::TWNCC => 'Financial Institution Code',
            self::USABA => 'United States Routing Number (Fedwire, NACHA)',
            self::USPID => 'CHIPS Participant Identifier',
            self::ZANCC => 'South African National Clearing Code',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ATBLZ => 'Bank Branch code used in Austria',
            self::AUBSB => 'Bank Branch code used in Australia',
            self::CACPA => 'Bank Branch code used in Canada',
            self::CHBCC => 'Bank Clearing number used in Switzerland',
            self::CHSIC => 'Bank Branch code used in clearing with Swiss Francs',
            self::CNAPS => 'Bank Branch code used in China',
            self::DEBLZ => 'Bank Branch code used in Germany',
            self::ESNCC => 'Bank Branch code used in Spain',
            self::GBDSC => 'Bank Branch code used in the UK',
            self::GRBIC => 'Bank Branch code used in Greece',
            self::HKNCC => 'Bank Branch code used in Hong Kong',
            self::IENCC => 'Bank Branch code used in Ireland',
            self::INFSC => 'Bank Branch code used in India',
            self::ITNCC => 'Bank Branch code used in Italy',
            self::JPZGN => 'Bank Branch code used in Japan',
            self::NZNCC => 'Bank Branch code used in New Zealand',
            self::PLKNR => 'Bank Branch code used in Poland',
            self::PTNCC => 'Bank Branch code used in Portugal',
            self::RUCBC => 'Bank Branch code used in Russia',
            self::SESBA => 'Bank Branch code used in Sweden',
            self::SGIBG => 'Bank Branch code used in Singapore',
            self::THCBC => 'Bank Identification code used in Thailand',
            self::TWNCC => 'Bank Branch code used in Taiwan',
            self::USABA => 'Routing Transit number assigned by the ABA for US financial institutons',
            self::USPID => 'Bank identifier used by CHIPs in the US',
            self::ZANCC => 'Bank Branch code used in South Africa',
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
