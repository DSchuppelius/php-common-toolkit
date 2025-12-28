<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionDomain.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalBankTransactionDomain1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * TransactionDomain - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalBankTransactionDomain1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum TransactionDomain: string {
    /**
     * ACMT - Account Management
     * The Account Management domain provides the bank transaction codes for operations on one account. ...
     */
    case ACMT = 'ACMT';

    /**
     * CAMT - Cash Management
     * The Cash Management domain provides the bank transaction codes for cash management activities tha...
     */
    case CAMT = 'CAMT';

    /**
     * CMDT - Commodities
     * The Commodities domain provides the bank transaction codes of all operations that are related to ...
     */
    case CMDT = 'CMDT';

    /**
     * DERV - Derivatives
     * The Derivatives domain provides the bank transaction codes for the derivatives related transactio...
     */
    case DERV = 'DERV';

    /**
     * FORX - Foreign Exchange
     * The Foreign Exchange domain provides the bank transaction codes of all operations that are relate...
     */
    case FORX = 'FORX';

    /**
     * LDAS - Loans, Deposits & Syndications
     * The Loans, Deposits and Syndications domain provides the bank transaction codes of all operations...
     */
    case LDAS = 'LDAS';

    /**
     * PMET - Precious Metal
     * The Precious Metal domain provides the bank transaction codes of all operations that are related ...
     */
    case PMET = 'PMET';

    /**
     * PMNT - Payments
     * The Payments domain provides the bank transaction codes for all payment activities that relate to...
     */
    case PMNT = 'PMNT';

    /**
     * SECU - Securities
     * The Securities domain provides the bank transaction codes for cash movements related to transacti...
     */
    case SECU = 'SECU';

    /**
     * TRAD - Trade Services
     * The Trade Services domain provides the bank transaction codes related to all of the Trade Service...
     */
    case TRAD = 'TRAD';

    /**
     * XTND - Extended Domain
     * The extended domain code is to be used whenever a specific domain has not yet been identified, or...
     */
    case XTND = 'XTND';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ACMT => 'Account Management',
            self::CAMT => 'Cash Management',
            self::CMDT => 'Commodities',
            self::DERV => 'Derivatives',
            self::FORX => 'Foreign Exchange',
            self::LDAS => 'Loans, Deposits & Syndications',
            self::PMET => 'Precious Metal',
            self::PMNT => 'Payments',
            self::SECU => 'Securities',
            self::TRAD => 'Trade Services',
            self::XTND => 'Extended Domain',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ACMT => 'The Account Management domain provides the bank transaction codes for operations on one account. Those transactions imply cash movements related to activities between the financial institution serv...',
            self::CAMT => 'The Cash Management domain provides the bank transaction codes for cash management activities that relate to own account management, i.e. cash concentration, zero-balancing or topping of accounts o...',
            self::CMDT => 'The Commodities domain provides the bank transaction codes of all operations that are related to a commodity which might be an extraction (mining), an agricultural product (soybeans, grains, coffee...',
            self::DERV => 'The Derivatives domain provides the bank transaction codes for the derivatives related transactions, i.e. a financial instrument derived from a cash market commodity, futures contract, or other fin...',
            self::FORX => 'The Foreign Exchange domain provides the bank transaction codes of all operations that are related to the foreign exchange market. Often abbreviated as FOREX.',
            self::LDAS => 'The Loans, Deposits and Syndications domain provides the bank transaction codes of all operations that are related to loans, deposits and syndications management.',
            self::PMET => 'The Precious Metal domain provides the bank transaction codes of all operations that are related to a classification of metals that are considered to be rare and/or have a high economic value.',
            self::PMNT => 'The Payments domain provides the bank transaction codes for all payment activities that relate to transfer of funds between parties.',
            self::SECU => 'The Securities domain provides the bank transaction codes for cash movements related to transactions on equities, fixed income and other securities industry related financial instruments.',
            self::TRAD => 'The Trade Services domain provides the bank transaction codes related to all of the Trade Services operations that need to be reported in the statements.',
            self::XTND => 'The extended domain code is to be used whenever a specific domain has not yet been identified, or a proprietary Bank Transaction Code has not been associated with a specific domain.',
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
