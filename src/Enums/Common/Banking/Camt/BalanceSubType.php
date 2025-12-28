<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BalanceSubType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalBalanceSubType1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * BalanceSubType - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalBalanceSubType1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum BalanceSubType: string {
    /**
     * ADJT - Adjustment
     * Balance to be held in the settlement account in order to comply with the average reserve due, in ...
     */
    case ADJT = 'ADJT';

    /**
     * BCUR - BaseCurrency
     * Balance representing the amount in the domestic or base accounting currency.
     */
    case BCUR = 'BCUR';

    /**
     * BLCK - Blocked
     * Balance representing the regulatory reserve that a financial institution must have with the accou...
     */
    case BLCK = 'BLCK';

    /**
     * BLKD - Blocked Funds
     * Balance representing funds that cannot be touched by the account owner.
     */
    case BLKD = 'BLKD';

    /**
     * DLOD - DaylightOverdraft
     * Balance representing the intra day overdraft granted by the Central Bank to financial institution...
     */
    case DLOD = 'DLOD';

    /**
     * EAST - EligibleAssets
     * Balance representing the potential loan a Central Bank would make in cash if the collateral is pl...
     */
    case EAST = 'EAST';

    /**
     * FCOL - Firm collateralization
     * Balance representing the forecast of the cash-equivalent resulting from evaluation of existing ho...
     */
    case FCOL = 'FCOL';

    /**
     * FCOU - Amounts that have been used to serve as firm collateral
     * Balance representing the cash equivalent resulting from evaluation of existing holdings at CSD th...
     */
    case FCOU = 'FCOU';

    /**
     * FORC - SecuritiesForecast
     * Balance representing the total of all balance types representing the forecast of transactions to ...
     */
    case FORC = 'FORC';

    /**
     * FUND - NetFunding
     * Balance representing the net amount to be funded resulting from the difference between the total ...
     */
    case FUND = 'FUND';

    /**
     * INTM - Intermediate
     * Balance representing an intermediate amount such as the opening or closing balance incrementally ...
     */
    case INTM = 'INTM';

    /**
     * LCUR - LocalCurrency
     * Balance representing the amount in the local market currency for which the asset is held.
     */
    case LCUR = 'LCUR';

    /**
     * LRLD - LimitRelated
     * Balance of a specific limit value, eg, a bilateral balance is calculated in relation to a given b...
     */
    case LRLD = 'LRLD';

    /**
     * NOTE - Reserved liquidity
     * Balance representing the amount that a financial institution has set aside for a specific reason ...
     */
    case NOTE = 'NOTE';

    /**
     * PDNG - SecuritiesPending
     * Balance of securities pending delivery, such as orders to sell securities have been executed but ...
     */
    case PDNG = 'PDNG';

    /**
     * PIPO - PayInPayOut
     * Balance representing the fictive amount of automated direct debits or payment based on standing a...
     */
    case PIPO = 'PIPO';

    /**
     * PRAV - ProgressiveAverage
     * Average of the daily balances on the account used to fulfil the reserve requirements calculated f...
     */
    case PRAV = 'PRAV';

    /**
     * RESV - Reserve
     * Balance representing the regulatory reserve that a financial institution must have with the accou...
     */
    case RESV = 'RESV';

    /**
     * SCOL - Self-collateralization
     * Balance representing the forecast of the cash-equivalent resulting from evaluation of the net inc...
     */
    case SCOL = 'SCOL';

    /**
     * SCOU - Amounts that have been used to serve as self collateral
     * Balance representing the cash-equivalent resulting from evaluation of incoming securities, qualif...
     */
    case SCOU = 'SCOU';

    /**
     * THRE - Threshold
     * Balance representing the amount that will be destined for investment. Difference between availabl...
     */
    case THRE = 'THRE';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ADJT => 'Adjustment',
            self::BCUR => 'BaseCurrency',
            self::BLCK => 'Blocked',
            self::BLKD => 'Blocked Funds',
            self::DLOD => 'DaylightOverdraft',
            self::EAST => 'EligibleAssets',
            self::FCOL => 'Firm collateralization',
            self::FCOU => 'Amounts that have been used to serve as firm collateral',
            self::FORC => 'SecuritiesForecast',
            self::FUND => 'NetFunding',
            self::INTM => 'Intermediate',
            self::LCUR => 'LocalCurrency',
            self::LRLD => 'LimitRelated',
            self::NOTE => 'Reserved liquidity',
            self::PDNG => 'SecuritiesPending',
            self::PIPO => 'PayInPayOut',
            self::PRAV => 'ProgressiveAverage',
            self::RESV => 'Reserve',
            self::SCOL => 'Self-collateralization',
            self::SCOU => 'Amounts that have been used to serve as self collateral',
            self::THRE => 'Threshold',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ADJT => 'Balance to be held in the settlement account in order to comply with the average reserve due, in the event that the bank\'s balance is equal to the reserve due during the remaining days of the maint...',
            self::BCUR => 'Balance representing the amount in the domestic or base accounting currency.',
            self::BLCK => 'Balance representing the regulatory reserve that a financial institution must have with the account servicing institution, eg, the minimum credit balance a financial institution is to keep with its...',
            self::BLKD => 'Balance representing funds that cannot be touched by the account owner.',
            self::DLOD => 'Balance representing the intra day overdraft granted by the Central Bank to financial institutions participating in a RTGS system. This balance may vary over time and shall be offset at the end of ...',
            self::EAST => 'Balance representing the potential loan a Central Bank would make in cash if the collateral is pledged, eg, securities available and eligible as collateral with the Central Bank.',
            self::FCOL => 'Balance representing the forecast of the cash-equivalent resulting from evaluation of existing holdings at CSD that are qualified to serve as collateral.',
            self::FCOU => 'Balance representing the cash equivalent resulting from evaluation of existing holdings at CSD that are qualified to serve as collateral and have been used as collateral.',
            self::FORC => 'Balance representing the total of all balance types representing the forecast of transactions to settle, blocked items, custody transactions and corporate actions cash disbursements.',
            self::FUND => 'Balance representing the net amount to be funded resulting from the difference between the total of all transactions with a cash impact and the existing cash coverage.',
            self::INTM => 'Balance representing an intermediate amount such as the opening or closing balance incrementally carried forward from one page to the next in a multi-page statement or report.',
            self::LCUR => 'Balance representing the amount in the local market currency for which the asset is held.',
            self::LRLD => 'Balance of a specific limit value, eg, a bilateral balance is calculated in relation to a given bilateral limit.',
            self::NOTE => 'Balance representing the amount that a financial institution has set aside for a specific reason and which is therefore not available. In the context of CSDs, reservation of liquidity made to meet ...',
            self::PDNG => 'Balance of securities pending delivery, such as orders to sell securities have been executed but settlement of the open transactions has not been confirmed.',
            self::PIPO => 'Balance representing the fictive amount of automated direct debits or payment based on standing arrangements between the CSD and the user. Usage: Pay-Ins and Pay-Outs can be different based on indi...',
            self::PRAV => 'Average of the daily balances on the account used to fulfil the reserve requirements calculated from the beginning of the maintenance period.',
            self::RESV => 'Balance representing the regulatory reserve that a financial institution must have with the account servicing institution, eg, the minimum credit balance a financial institution is to keep with its...',
            self::SCOL => 'Balance representing the forecast of the cash-equivalent resulting from evaluation of the net incoming balance of securities qualified to serve as collateral for which settlement instructions are h...',
            self::SCOU => 'Balance representing the cash-equivalent resulting from evaluation of incoming securities, qualified to serve as collateral and actually used as collateral, which have been settled during the settl...',
            self::THRE => 'Balance representing the amount that will be destined for investment. Difference between available balance and threshold for investment limit.',
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
