<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ReturnReason.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalReturnReason1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * ReturnReason - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalReturnReason1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum ReturnReason: string {
    /**
     * AC01 - IncorrectAccountNumber
     * Format of the account number specified is not correct
     */
    case AC01 = 'AC01';

    /**
     * AC03 - InvalidCreditorAccountNumber
     * Wrong IBAN in SCT
     */
    case AC03 = 'AC03';

    /**
     * AC04 - ClosedAccountNumber
     * Account number specified has been closed on the bank of account's books
     */
    case AC04 = 'AC04';

    /**
     * AC06 - BlockedAccount
     * Account specified is blocked, prohibiting posting of transactions against it.
     */
    case AC06 = 'AC06';

    /**
     * AC13 - InvalidDebtorAccountType
     * Debtor account type is missing or invalid
     */
    case AC13 = 'AC13';

    /**
     * AG01 - TransactionForbidden
     * Transaction forbidden on this type of account (formerly NoAgreement)
     */
    case AG01 = 'AG01';

    /**
     * AG02 - InvalidBankOperationCode
     * Bank Operation code specified in the message is not valid for receiver
     */
    case AG02 = 'AG02';

    /**
     * AM01 - ZeroAmount
     * Specified message amount is equal to zero
     */
    case AM01 = 'AM01';

    /**
     * AM02 - NotAllowedAmount
     * Specific transaction/message amount is greater than allowed maximum
     */
    case AM02 = 'AM02';

    /**
     * AM03 - NotAllowedCurrency
     * Specified message amount is an non processable currency outside of existing agreement
     */
    case AM03 = 'AM03';

    /**
     * AM04 - InsufficientFunds
     * Amount of funds available to cover specified message amount is insufficient.
     */
    case AM04 = 'AM04';

    /**
     * AM05 - Duplication
     * Duplication
     */
    case AM05 = 'AM05';

    /**
     * AM06 - TooLowAmount
     * Specified transaction amount is less than agreed minimum.
     */
    case AM06 = 'AM06';

    /**
     * AM07 - BlockedAmount
     * Amount of funds available to cover specified message amount is insufficient.
     */
    case AM07 = 'AM07';

    /**
     * AM09 - WrongAmount
     * Amount received is not the amount agreed or expected
     */
    case AM09 = 'AM09';

    /**
     * AM10 - InvalidControlSum
     * Sum of instructed amounts does not equal the control sum.
     */
    case AM10 = 'AM10';

    /**
     * ARDT - AlreadyReturnedTransaction
     * Already returned original SCT
     */
    case ARDT = 'ARDT';

    /**
     * BE01 - InconsistenWithEndCustomer
     * Identification of end customer is not consistent with associated account number (formerly Credito...
     */
    case BE01 = 'BE01';

    /**
     * BE04 - MissingCreditorAddress
     * Specification of creditor's address, which is required for payment, is missing/not correct (forme...
     */
    case BE04 = 'BE04';

    /**
     * BE05 - UnrecognisedInitiatingParty
     * Party who initiated the message is not recognised by the end customer
     */
    case BE05 = 'BE05';

    /**
     * BE06 - UnknownEndCustomer
     * End customer specified is not known at associated Sort/National Bank Code or does no longer exist...
     */
    case BE06 = 'BE06';

    /**
     * BE07 - MissingDebtorAddress
     * Specification of debtor's address, which is required for payment, is missing/not correct.
     */
    case BE07 = 'BE07';

    /**
     * BE08 - BankError
     * Returned as a result of a bank error.
     */
    case BE08 = 'BE08';

    /**
     * CNOR - Creditor bank is not registered
     * Creditor bank is not registered under this BIC in the CSM
     */
    case CNOR = 'CNOR';

    /**
     * CURR - IncorrectCurrency
     * Currency of the payment is incorrect
     */
    case CURR = 'CURR';

    /**
     * CUST - RequestedByCustomer
     * Cancellation requested by the Debtor
     */
    case CUST = 'CUST';

    /**
     * DNOR - Debtor bank is not registered
     * Debtor bank is not registered under this BIC in the CSM
     */
    case DNOR = 'DNOR';

    /**
     * DT01 - InvalidDate
     * Invalid date (eg, wrong settlement date)
     */
    case DT01 = 'DT01';

    /**
     * ED01 - CorrespondentBankNotPossible
     * Correspondent bank not possible.
     */
    case ED01 = 'ED01';

    /**
     * ED03 - BalanceInfoRequest
     * Balance of payments complementary info is requested
     */
    case ED03 = 'ED03';

    /**
     * ED05 - SettlementFailed
     * Settlement of the transaction has failed.
     */
    case ED05 = 'ED05';

    /**
     * EMVL - EMV Liability Shift
     * The card payment is fraudulent and was not processed with EMV technology for an EMV card.
     */
    case EMVL = 'EMVL';

    /**
     * FF05 - InvalidLocalInstrumentCode
     * Local Instrument code is missing or invalid
     */
    case FF05 = 'FF05';

    /**
     * FOCR - FollowingCancellationRequest
     * Return following a cancellation request
     */
    case FOCR = 'FOCR';

    /**
     * FR01 - Fraud
     * Returned as a result of fraud.
     */
    case FR01 = 'FR01';

    /**
     * MD01 - NoMandate
     * No Mandate
     */
    case MD01 = 'MD01';

    /**
     * MD02 - MissingMandatoryInformationIn Mandate
     * Mandate related information data required by the scheme is missing.
     */
    case MD02 = 'MD02';

    /**
     * MD06 - RefundRequestByEndCustomer
     * Return of funds requested by end customer
     */
    case MD06 = 'MD06';

    /**
     * MD07 - EndCustomerDeceased
     * End customer is deceased.
     */
    case MD07 = 'MD07';

    /**
     * MS02 - NotSpecifiedReasonCustomer Generated
     * Reason has not been specified by end customer
     */
    case MS02 = 'MS02';

    /**
     * MS03 - NotSpecifiedReasonAgent Generated
     * Reason has not been specified by agent.
     */
    case MS03 = 'MS03';

    /**
     * NARR - Narrative
     * Reason is provided as narrative information in the additional reason information.
     */
    case NARR = 'NARR';

    /**
     * NOAS - NoAnswerFromCustomer
     * No response from Beneficiary
     */
    case NOAS = 'NOAS';

    /**
     * NOOR - NoOriginalTransactionReceived
     * Original SCT never received
     */
    case NOOR = 'NOOR';

    /**
     * PINL - PIN Liability Shift
     * The card payment is fraudulent (lost and stolen fraud) and was processed as EMV transaction witho...
     */
    case PINL = 'PINL';

    /**
     * RC01 - BankIdentifierIncorrect
     * Bank Identifier code specified in the message has an incorrect format (formerly IncorrectFormatFo...
     */
    case RC01 = 'RC01';

    /**
     * RC07 - InvalidCreditorBICIdentifier
     * Incorrrect BIC of the beneficiary Bank in the SCTR
     */
    case RC07 = 'RC07';

    /**
     * RF01 - NotUniqueTransactionReference
     * Transaction reference is not unique within the message.
     */
    case RF01 = 'RF01';

    /**
     * RR01 - Missing Debtor Account or Identification
     * Specification of the debtor’s account or unique identification needed for reasons of regulatory r...
     */
    case RR01 = 'RR01';

    /**
     * RR02 - Missing Debtor Name or Address
     * Specification of the debtor’s name and/or address needed for regulatory requirements is insuffici...
     */
    case RR02 = 'RR02';

    /**
     * RR03 - Missing Creditor Name or Address
     * Specification of the creditor’s name and/or address needed for regulatory requirements is insuffi...
     */
    case RR03 = 'RR03';

    /**
     * RR04 - Regulatory Reason
     * Regulatory Reason
     */
    case RR04 = 'RR04';

    /**
     * SL01 - Specific Service offered by Debtor Agent
     * Due to specific service offered by the Debtor Agent
     */
    case SL01 = 'SL01';

    /**
     * SL02 - Specific Service offered by Creditor Agent
     * Due to specific service offered by the Creditor Agent
     */
    case SL02 = 'SL02';

    /**
     * SL11 - Creditor not on Whitelist of Debtor
     * Whitelisting service offered by the Debtor Agent; Debtor has not included the Creditor on its “Wh...
     */
    case SL11 = 'SL11';

    /**
     * SL12 - Creditor on Blacklist of Debtor
     * Blacklisting service offered by the Debtor Agent; Debtor included the Creditor on his “Blacklist”...
     */
    case SL12 = 'SL12';

    /**
     * SL13 - Maximum number of Direct Debit Transactions exceeded
     * Due to Maximum allowed Direct Debit Transactions per period service offered by the Debtor Agent.
     */
    case SL13 = 'SL13';

    /**
     * SL14 - Maximum Direct Debit Transaction Amount exceeded
     * Due to Maximum allowed Direct Debit Transaction amount service offered by the Debtor Agent.
     */
    case SL14 = 'SL14';

    /**
     * SVNR - ServiceNotRendered
     * The card payment is returned since a cash amount rendered was not correct or goods or a service w...
     */
    case SVNR = 'SVNR';

    /**
     * TM01 - CutOffTime
     * Associated message was received after agreed processing cut-off time.
     */
    case TM01 = 'TM01';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::AC01 => 'IncorrectAccountNumber',
            self::AC03 => 'InvalidCreditorAccountNumber',
            self::AC04 => 'ClosedAccountNumber',
            self::AC06 => 'BlockedAccount',
            self::AC13 => 'InvalidDebtorAccountType',
            self::AG01 => 'TransactionForbidden',
            self::AG02 => 'InvalidBankOperationCode',
            self::AM01 => 'ZeroAmount',
            self::AM02 => 'NotAllowedAmount',
            self::AM03 => 'NotAllowedCurrency',
            self::AM04 => 'InsufficientFunds',
            self::AM05 => 'Duplication',
            self::AM06 => 'TooLowAmount',
            self::AM07 => 'BlockedAmount',
            self::AM09 => 'WrongAmount',
            self::AM10 => 'InvalidControlSum',
            self::ARDT => 'AlreadyReturnedTransaction',
            self::BE01 => 'InconsistenWithEndCustomer',
            self::BE04 => 'MissingCreditorAddress',
            self::BE05 => 'UnrecognisedInitiatingParty',
            self::BE06 => 'UnknownEndCustomer',
            self::BE07 => 'MissingDebtorAddress',
            self::BE08 => 'BankError',
            self::CNOR => 'Creditor bank is not registered',
            self::CURR => 'IncorrectCurrency',
            self::CUST => 'RequestedByCustomer',
            self::DNOR => 'Debtor bank is not registered',
            self::DT01 => 'InvalidDate',
            self::ED01 => 'CorrespondentBankNotPossible',
            self::ED03 => 'BalanceInfoRequest',
            self::ED05 => 'SettlementFailed',
            self::EMVL => 'EMV Liability Shift',
            self::FF05 => 'InvalidLocalInstrumentCode',
            self::FOCR => 'FollowingCancellationRequest',
            self::FR01 => 'Fraud',
            self::MD01 => 'NoMandate',
            self::MD02 => 'MissingMandatoryInformationIn Mandate',
            self::MD06 => 'RefundRequestByEndCustomer',
            self::MD07 => 'EndCustomerDeceased',
            self::MS02 => 'NotSpecifiedReasonCustomer Generated',
            self::MS03 => 'NotSpecifiedReasonAgent Generated',
            self::NARR => 'Narrative',
            self::NOAS => 'NoAnswerFromCustomer',
            self::NOOR => 'NoOriginalTransactionReceived',
            self::PINL => 'PIN Liability Shift',
            self::RC01 => 'BankIdentifierIncorrect',
            self::RC07 => 'InvalidCreditorBICIdentifier',
            self::RF01 => 'NotUniqueTransactionReference',
            self::RR01 => 'Missing Debtor Account or Identification',
            self::RR02 => 'Missing Debtor Name or Address',
            self::RR03 => 'Missing Creditor Name or Address',
            self::RR04 => 'Regulatory Reason',
            self::SL01 => 'Specific Service offered by Debtor Agent',
            self::SL02 => 'Specific Service offered by Creditor Agent',
            self::SL11 => 'Creditor not on Whitelist of Debtor',
            self::SL12 => 'Creditor on Blacklist of Debtor',
            self::SL13 => 'Maximum number of Direct Debit Transactions exceeded',
            self::SL14 => 'Maximum Direct Debit Transaction Amount exceeded',
            self::SVNR => 'ServiceNotRendered',
            self::TM01 => 'CutOffTime',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::AC01 => 'Format of the account number specified is not correct',
            self::AC03 => 'Wrong IBAN in SCT',
            self::AC04 => 'Account number specified has been closed on the bank of account\'s books',
            self::AC06 => 'Account specified is blocked, prohibiting posting of transactions against it.',
            self::AC13 => 'Debtor account type is missing or invalid',
            self::AG01 => 'Transaction forbidden on this type of account (formerly NoAgreement)',
            self::AG02 => 'Bank Operation code specified in the message is not valid for receiver',
            self::AM01 => 'Specified message amount is equal to zero',
            self::AM02 => 'Specific transaction/message amount is greater than allowed maximum',
            self::AM03 => 'Specified message amount is an non processable currency outside of existing agreement',
            self::AM04 => 'Amount of funds available to cover specified message amount is insufficient.',
            self::AM05 => 'Duplication',
            self::AM06 => 'Specified transaction amount is less than agreed minimum.',
            self::AM07 => 'Amount of funds available to cover specified message amount is insufficient.',
            self::AM09 => 'Amount received is not the amount agreed or expected',
            self::AM10 => 'Sum of instructed amounts does not equal the control sum.',
            self::ARDT => 'Already returned original SCT',
            self::BE01 => 'Identification of end customer is not consistent with associated account number (formerly CreditorConsistency).',
            self::BE04 => 'Specification of creditor\'s address, which is required for payment, is missing/not correct (formerly IncorrectCreditorAddress).',
            self::BE05 => 'Party who initiated the message is not recognised by the end customer',
            self::BE06 => 'End customer specified is not known at associated Sort/National Bank Code or does no longer exist in the books',
            self::BE07 => 'Specification of debtor\'s address, which is required for payment, is missing/not correct.',
            self::BE08 => 'Returned as a result of a bank error.',
            self::CNOR => 'Creditor bank is not registered under this BIC in the CSM',
            self::CURR => 'Currency of the payment is incorrect',
            self::CUST => 'Cancellation requested by the Debtor',
            self::DNOR => 'Debtor bank is not registered under this BIC in the CSM',
            self::DT01 => 'Invalid date (eg, wrong settlement date)',
            self::ED01 => 'Correspondent bank not possible.',
            self::ED03 => 'Balance of payments complementary info is requested',
            self::ED05 => 'Settlement of the transaction has failed.',
            self::EMVL => 'The card payment is fraudulent and was not processed with EMV technology for an EMV card.',
            self::FF05 => 'Local Instrument code is missing or invalid',
            self::FOCR => 'Return following a cancellation request',
            self::FR01 => 'Returned as a result of fraud.',
            self::MD01 => 'No Mandate',
            self::MD02 => 'Mandate related information data required by the scheme is missing.',
            self::MD06 => 'Return of funds requested by end customer',
            self::MD07 => 'End customer is deceased.',
            self::MS02 => 'Reason has not been specified by end customer',
            self::MS03 => 'Reason has not been specified by agent.',
            self::NARR => 'Reason is provided as narrative information in the additional reason information.',
            self::NOAS => 'No response from Beneficiary',
            self::NOOR => 'Original SCT never received',
            self::PINL => 'The card payment is fraudulent (lost and stolen fraud) and was processed as EMV transaction without PIN verification.',
            self::RC01 => 'Bank Identifier code specified in the message has an incorrect format (formerly IncorrectFormatForRoutingCode).',
            self::RC07 => 'Incorrrect BIC of the beneficiary Bank in the SCTR',
            self::RF01 => 'Transaction reference is not unique within the message.',
            self::RR01 => 'Specification of the debtor’s account or unique identification needed for reasons of regulatory requirements is insufficient or missing',
            self::RR02 => 'Specification of the debtor’s name and/or address needed for regulatory requirements is insufficient or missing.',
            self::RR03 => 'Specification of the creditor’s name and/or address needed for regulatory requirements is insufficient or missing.',
            self::RR04 => 'Regulatory Reason',
            self::SL01 => 'Due to specific service offered by the Debtor Agent',
            self::SL02 => 'Due to specific service offered by the Creditor Agent',
            self::SL11 => 'Whitelisting service offered by the Debtor Agent; Debtor has not included the Creditor on its “Whitelist” (yet). In the Whitelist the Debtor may list all allowed Creditors to debit Debtor bank acco...',
            self::SL12 => 'Blacklisting service offered by the Debtor Agent; Debtor included the Creditor on his “Blacklist”. In the Blacklist the Debtor may list all Creditors not allowed to debit Debtor bank account.',
            self::SL13 => 'Due to Maximum allowed Direct Debit Transactions per period service offered by the Debtor Agent.',
            self::SL14 => 'Due to Maximum allowed Direct Debit Transaction amount service offered by the Debtor Agent.',
            self::SVNR => 'The card payment is returned since a cash amount rendered was not correct or goods or a service was not rendered to the customer, e.g. in an e-commerce situation.',
            self::TM01 => 'Associated message was received after agreed processing cut-off time.',
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
