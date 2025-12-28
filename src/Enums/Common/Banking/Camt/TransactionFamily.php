<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionFamily.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalBankTransactionFamily1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * TransactionFamily - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalBankTransactionFamily1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum TransactionFamily: string {
    /**
     * ACCB - Account Balancing
     * Transaction is related to a cash movement that sets the balances of an account to an amount that ...
     */
    case ACCB = 'ACCB';

    /**
     * ACOP - Additional Miscellaneous Credit Operations
     * Transaction is related to miscellaneous credit operations on the balance or on a specific transac...
     */
    case ACOP = 'ACOP';

    /**
     * ADOP - Additional Miscellaneous Debit Operations
     * Transaction is related to miscellaneous debit operations on the balance or on a specific transact...
     */
    case ADOP = 'ADOP';

    /**
     * BLOC - Blocked Transactions
     * Represents all cash legs for trades settling via CSD that have been matched but blocked in order ...
     */
    case BLOC = 'BLOC';

    /**
     * CAPL - Cash Pooling
     * Cash pooling is an arrangement between entities within the same business organisation. It present...
     */
    case CAPL = 'CAPL';

    /**
     * CASH - Miscellaneous Securities Operation
     * Cash movement related to other securities related activity
     */
    case CASH = 'CASH';

    /**
     * CCRD - Customer Card Transactions
     * Transaction is a payment card operation performed by the customer by the means of a debit or cred...
     */
    case CCRD = 'CCRD';

    /**
     * CLNC - Clean Collection
     * Transaction is related to a clean collection, i.e. collections that do not require documents rest...
     */
    case CLNC = 'CLNC';

    /**
     * CNTR - Counter Transactions
     * Transaction is related to cash movements initiated through over-the-counter operations at the fin...
     */
    case CNTR = 'CNTR';

    /**
     * COLC - Custody Collection
     * All corporate action related payment obligations of the participant bank, based on their role as ...
     */
    case COLC = 'COLC';

    /**
     * COLL - Collateral Management
     * Cash movement related to the management of collateral.
     */
    case COLL = 'COLL';

    /**
     * CORP - Corporate Action
     * Cash movement related to corporate action activity
     */
    case CORP = 'CORP';

    /**
     * CSLN - Consumer Loans
     * Transaction related to a loan that has been issued for consumable goods, such as a car.
     */
    case CSLN = 'CSLN';

    /**
     * CUST - Custody
     * Represents the total of all asset servicing transactions such as dividends, income corporate acti...
     */
    case CUST = 'CUST';

    /**
     * DCCT - Documentary Credit
     * Documentary Credits, also called “commercial credits”, require the presentation of documents that...
     */
    case DCCT = 'DCCT';

    /**
     * DLVR - Delivery
     * Transaction is the physical delivery of precious metal OR related to the physical delivery of com...
     */
    case DLVR = 'DLVR';

    /**
     * DOCC - Documentary Collection
     * Transaction is related to a documentary collection, i.e. collections which are accompanied by doc...
     */
    case DOCC = 'DOCC';

    /**
     * DRFT - Drafts / Bill Of Orders
     * Transaction is related to a guaranteed bank cheque issued by the account owner with a future valu...
     */
    case DRFT = 'DRFT';

    /**
     * FTDP - Fixed Term Deposits
     * Transaction relates to an amount of money deposited in a savings account for a fixed period of ti...
     */
    case FTDP = 'FTDP';

    /**
     * FTLN - Fixed Term Loans
     * Transaction relates to a loan with a fixed maturity during which time interest is paid, but no pa...
     */
    case FTLN = 'FTLN';

    /**
     * FTUR - Futures
     * Transaction is related to contracts on futures exchange which require the delivery of a specified...
     */
    case FTUR = 'FTUR';

    /**
     * FWRD - Forwards
     * Transaction is related to a Foreign Exchange forward, i.e. an exchange of two currencies  on a fu...
     */
    case FWRD = 'FWRD';

    /**
     * GUAR - Guarantees
     * Transaction relates to a guarantee, i.e. a promise, especially in writing, that something is of s...
     */
    case GUAR = 'GUAR';

    /**
     * ICCN - Issued Cash Concentration
     * Transaction is related to outgoing cash movements that are related to cash management activities ...
     */
    case ICCN = 'ICCN';

    /**
     * ICDT - Issued Credit Transfers
     * Payable Credit Transfers are instructions to transfer an amount of money by the account owner to ...
     */
    case ICDT = 'ICDT';

    /**
     * ICHQ - Issued Cheques
     * Transaction is related to a written paper order – the cheque – issued by the account owner to the...
     */
    case ICHQ = 'ICHQ';

    /**
     * IDDT - Issued Direct Debits
     * The Issued Direct Debit transactions are related to instructions sent by the account owner to col...
     */
    case IDDT = 'IDDT';

    /**
     * LACK - LACK
     * ‘LACK’ is an extra amount to be funded in addition to ‘FUND’, as it is a forecast of a cash amoun...
     */
    case LACK = 'LACK';

    /**
     * LBOX - Lock Box
     * Transaction is related to a lockbox, which is a batch of cheques that have been deposited in a BO...
     */
    case LBOX = 'LBOX';

    /**
     * LFUT - Listed Futures
     * Transaction is a cash movement related to a listed future, i.e. a legally binding agreement, made...
     */
    case LFUT = 'LFUT';

    /**
     * LOCT - Stand-by Letter Of Credit
     * Stand-by credits do not necessarily require the presentation of documentary evidence that events ...
     */
    case LOCT = 'LOCT';

    /**
     * LOPT - Listed Options
     * Transaction is a movement related to an option listed on  a stock or futures exchange, i.e. a con...
     */
    case LOPT = 'LOPT';

    /**
     * MCOP - Miscellaneous Credit Operations
     * Transaction is related to miscellaneous credit operations on the balance or on a specific transac...
     */
    case MCOP = 'MCOP';

    /**
     * MCRD - Merchant Card Transactions
     * Transaction is a payment card operation performed by debit or credit card operation, reported for...
     */
    case MCRD = 'MCRD';

    /**
     * MDOP - Miscellaneous Debit Operations
     * Transaction is related to miscellaneous debit operations on the balance or on a specific transact...
     */
    case MDOP = 'MDOP';

    /**
     * MGLN - Mortgage Loans
     * Transaction relates to a loan that is secured by a guarantee of real estate.
     */
    case MGLN = 'MGLN';

    /**
     * NDFX - Non Deliverable
     * Transaction is related to a non-deliverable Forex ie a cash-settled, short term forward contract ...
     */
    case NDFX = 'NDFX';

    /**
     * NSET - Non Settled
     * Transaction representing the cash equivalent of all non-settled securities transactions
     */
    case NSET = 'NSET';

    /**
     * NTAV - Not available
     * The “Not Available” family is used to cater for the Bank Transaction Code mandatory field, when n...
     */
    case NTAV = 'NTAV';

    /**
     * NTDP - Notice Deposits
     * Transaction relates to a deposit which can be recalled with a fixed notice period. The amount of ...
     */
    case NTDP = 'NTDP';

    /**
     * NTLN - Notice Loans
     * Transaction relates to a loan which can be reimbursed with a fixed notice period.
     */
    case NTLN = 'NTLN';

    /**
     * OBND - OTC – Bonds Derivatives
     * Transaction is related to a bond derivative, i.e. a derivative whose pay-offs depends on the valu...
     */
    case OBND = 'OBND';

    /**
     * OCRD - OTC – Credit Derivatives
     * Transaction is related to an OTC derivative designed to assume or shift credit risk, that is, the...
     */
    case OCRD = 'OCRD';

    /**
     * OEQT - OTC – Equity Derivatives
     * Transaction is related to an equity derivative, i.e. a derivative whose pay-offs depends on the v...
     */
    case OEQT = 'OEQT';

    /**
     * OIRT - OTC – Interest Rates Derivatives
     * Transaction relates to an interest rate derivative.
     */
    case OIRT = 'OIRT';

    /**
     * OPCL - Opening & Closing
     * Transaction is related to the administration of the account, such as closing or opening of the ac...
     */
    case OPCL = 'OPCL';

    /**
     * OPTN - Options
     * Transaction is related to precious metal option instruments OR to commodities option instruments
     */
    case OPTN = 'OPTN';

    /**
     * OSED - OTC – Structured Exotic derivatives
     * Transaction is related to derivatives operations of combined multiple types of instruments, inclu...
     */
    case OSED = 'OSED';

    /**
     * OSWP - OTC – Swap Derivatives
     * Transaction is related to any kind of swap derivative.
     */
    case OSWP = 'OSWP';

    /**
     * OTHB - CSD Blocked Transactions
     * Represents total of all cash legs for trades settling via CSD that have been matched but blocked ...
     */
    case OTHB = 'OTHB';

    /**
     * OTHR - Other
     * The “Other” family is used to cater for the Bank Transaction Code mandatory field, when the repor...
     */
    case OTHR = 'OTHR';

    /**
     * RCCN - Received Cash Concentration
     * Transaction is related to incoming cash movements that are related to cash management activities ...
     */
    case RCCN = 'RCCN';

    /**
     * RCDT - Received Credit Transfers
     * Receivable Credit Transfers are instructions to receive an amount of money from a debtor by the a...
     */
    case RCDT = 'RCDT';

    /**
     * RCHQ - Received Cheques
     * Transaction is related to a written paper order – the cheque – received by the account owner from...
     */
    case RCHQ = 'RCHQ';

    /**
     * RDDT - Received Direct Debits
     * The Received Direct Debit transactions are related to instructions received by the account owner ...
     */
    case RDDT = 'RDDT';

    /**
     * SETT - Trade, Clearing and Settlement
     * Transaction relates to cash movement generated by a Trading, Clearing or Settlement Activity
     */
    case SETT = 'SETT';

    /**
     * SPOT - Spots
     * Transaction is related to the exchange of two currencies at an agreed upon exchange rate for cash...
     */
    case SPOT = 'SPOT';

    /**
     * SWAP - Swaps
     * Transaction is related to a swap that involves the exchange of principal and interest in one curr...
     */
    case SWAP = 'SWAP';

    /**
     * SYDN - Syndications
     * Transaction relates to a syndication which is the process of involving numerous different lenders...
     */
    case SYDN = 'SYDN';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ACCB => 'Account Balancing',
            self::ACOP => 'Additional Miscellaneous Credit Operations',
            self::ADOP => 'Additional Miscellaneous Debit Operations',
            self::BLOC => 'Blocked Transactions',
            self::CAPL => 'Cash Pooling',
            self::CASH => 'Miscellaneous Securities Operation',
            self::CCRD => 'Customer Card Transactions',
            self::CLNC => 'Clean Collection',
            self::CNTR => 'Counter Transactions',
            self::COLC => 'Custody Collection',
            self::COLL => 'Collateral Management',
            self::CORP => 'Corporate Action',
            self::CSLN => 'Consumer Loans',
            self::CUST => 'Custody',
            self::DCCT => 'Documentary Credit',
            self::DLVR => 'Delivery',
            self::DOCC => 'Documentary Collection',
            self::DRFT => 'Drafts / Bill Of Orders',
            self::FTDP => 'Fixed Term Deposits',
            self::FTLN => 'Fixed Term Loans',
            self::FTUR => 'Futures',
            self::FWRD => 'Forwards',
            self::GUAR => 'Guarantees',
            self::ICCN => 'Issued Cash Concentration',
            self::ICDT => 'Issued Credit Transfers',
            self::ICHQ => 'Issued Cheques',
            self::IDDT => 'Issued Direct Debits',
            self::LACK => 'LACK',
            self::LBOX => 'Lock Box',
            self::LFUT => 'Listed Futures',
            self::LOCT => 'Stand-by Letter Of Credit',
            self::LOPT => 'Listed Options',
            self::MCOP => 'Miscellaneous Credit Operations',
            self::MCRD => 'Merchant Card Transactions',
            self::MDOP => 'Miscellaneous Debit Operations',
            self::MGLN => 'Mortgage Loans',
            self::NDFX => 'Non Deliverable',
            self::NSET => 'Non Settled',
            self::NTAV => 'Not available',
            self::NTDP => 'Notice Deposits',
            self::NTLN => 'Notice Loans',
            self::OBND => 'OTC – Bonds Derivatives',
            self::OCRD => 'OTC – Credit Derivatives',
            self::OEQT => 'OTC – Equity Derivatives',
            self::OIRT => 'OTC – Interest Rates Derivatives',
            self::OPCL => 'Opening & Closing',
            self::OPTN => 'Options',
            self::OSED => 'OTC – Structured Exotic derivatives',
            self::OSWP => 'OTC – Swap Derivatives',
            self::OTHB => 'CSD Blocked Transactions',
            self::OTHR => 'Other',
            self::RCCN => 'Received Cash Concentration',
            self::RCDT => 'Received Credit Transfers',
            self::RCHQ => 'Received Cheques',
            self::RDDT => 'Received Direct Debits',
            self::SETT => 'Trade, Clearing and Settlement',
            self::SPOT => 'Spots',
            self::SWAP => 'Swaps',
            self::SYDN => 'Syndications',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ACCB => 'Transaction is related to a cash movement that sets the balances of an account to an amount that has been pre-agreed or specified in the transaction. Those transactions are mainly automated liquidi...',
            self::ACOP => 'Transaction is related to miscellaneous credit operations on the balance or on a specific transaction on the account which are not covered by the generic Miscellaneous Credit Operations.',
            self::ADOP => 'Transaction is related to miscellaneous debit operations on the balance or on a specific transaction on the account which are not covered by the generic Miscellaneous Debit Operations.',
            self::BLOC => 'Represents all cash legs for trades settling via CSD that have been matched but blocked in order to not settle',
            self::CAPL => 'Cash pooling is an arrangement between entities within the same business organisation. It presents their short term credit and debit cash balance positions as a net number. Pooling is normally effe...',
            self::CASH => 'Cash movement related to other securities related activity',
            self::CCRD => 'Transaction is a payment card operation performed by the customer by the means of a debit or credit card. Cards are issued by a credit institution or a card company. They indicate that the holder o...',
            self::CLNC => 'Transaction is related to a clean collection, i.e. collections that do not require documents restricting possession or ownership. A collection is a set of documents including a letter or completed ...',
            self::CNTR => 'Transaction is related to cash movements initiated through over-the-counter operations at the financial institution’s counter',
            self::COLC => 'All corporate action related payment obligations of the participant bank, based on their role as main paying agent',
            self::COLL => 'Cash movement related to the management of collateral.',
            self::CORP => 'Cash movement related to corporate action activity',
            self::CSLN => 'Transaction related to a loan that has been issued for consumable goods, such as a car.',
            self::CUST => 'Represents the total of all asset servicing transactions such as dividends, income corporate action equivalents, tax returns, redemptions etc',
            self::DCCT => 'Documentary Credits, also called “commercial credits”, require the presentation of documents that prove certain events have taken place.',
            self::DLVR => 'Transaction is the physical delivery of precious metal OR related to the physical delivery of commodities.',
            self::DOCC => 'Transaction is related to a documentary collection, i.e. collections which are accompanied by documents restricting possession or ownership.',
            self::DRFT => 'Transaction is related to a guaranteed bank cheque issued by the account owner with a future value date (do not pay before), which in commercial terms is a ‘negotiable instrument’: the beneficiary ...',
            self::FTDP => 'Transaction relates to an amount of money deposited in a savings account for a fixed period of time, the terms of which impose a financial penalty if the amount of money is withdrawn before the spe...',
            self::FTLN => 'Transaction relates to a loan with a fixed maturity during which time interest is paid, but no payments to reduce principal are made. The entire principal is due and payable at the end of the loan ...',
            self::FTUR => 'Transaction is related to contracts on futures exchange which require the delivery of a specified amount of currency at a specified date, if not liquidated before the contract matures OR  for the f...',
            self::FWRD => 'Transaction is related to a Foreign Exchange forward, i.e. an exchange of two currencies  on a future date.',
            self::GUAR => 'Transaction relates to a guarantee, i.e. a promise, especially in writing, that something is of specified quality, content, benefit, or that it will provide satisfaction or will perform or produce ...',
            self::ICCN => 'Transaction is related to outgoing cash movements that are related to cash management activities initiated by the owner of the account to optimise the return on the available funds.',
            self::ICDT => 'Payable Credit Transfers are instructions to transfer an amount of money by the account owner to a creditor. The payable credit transfers are related to instructions sent by the account owner.',
            self::ICHQ => 'Transaction is related to a written paper order – the cheque – issued by the account owner to the cheque recipient, to debit the account of the cheque issuer.',
            self::IDDT => 'The Issued Direct Debit transactions are related to instructions sent by the account owner to collect an amount of money that is due to the account owner.',
            self::LACK => '‘LACK’ is an extra amount to be funded in addition to ‘FUND’, as it is a forecast of a cash amount that will not be delivered to the participant due to sales that will fail because of lack of holdi...',
            self::LBOX => 'Transaction is related to a lockbox, which is a batch of cheques that have been deposited in a BO, and are processed in one operation.',
            self::LFUT => 'Transaction is a cash movement related to a listed future, i.e. a legally binding agreement, made on a futures exchange, to buy or sell a financial instrument some time in the future.',
            self::LOCT => 'Stand-by credits do not necessarily require the presentation of documentary evidence that events have happened, but rely on attestations. Since “standbys” normally do not guarantee specific perform...',
            self::LOPT => 'Transaction is a movement related to an option listed on  a stock or futures exchange, i.e. a contract that gives the option holder the right to purchase (or call) a specified number of shares of t...',
            self::MCOP => 'Transaction is related to miscellaneous credit operations on the balance or on a specific transaction on the account.',
            self::MCRD => 'Transaction is a payment card operation performed by debit or credit card operation, reported for the merchant.',
            self::MDOP => 'Transaction is related to miscellaneous debit operations on the balance or on a specific transaction on the account.',
            self::MGLN => 'Transaction relates to a loan that is secured by a guarantee of real estate.',
            self::NDFX => 'Transaction is related to a non-deliverable Forex ie a cash-settled, short term forward contract on a thinly traded or non-convertible foreign currency, where the profit or loss at the time at the ...',
            self::NSET => 'Transaction representing the cash equivalent of all non-settled securities transactions',
            self::NTAV => 'The “Not Available” family is used to cater for the Bank Transaction Code mandatory field, when no further details are available for the Bank Transaction Code, eg a payment is reported but no famil...',
            self::NTDP => 'Transaction relates to a deposit which can be recalled with a fixed notice period. The amount of money is put on a savings account on which the customer agrees to give the thrift institution a spec...',
            self::NTLN => 'Transaction relates to a loan which can be reimbursed with a fixed notice period.',
            self::OBND => 'Transaction is related to a bond derivative, i.e. a derivative whose pay-offs depends on the value of an underlying long-term debt security issued by corporations and governments. Typical derivativ...',
            self::OCRD => 'Transaction is related to an OTC derivative designed to assume or shift credit risk, that is, the risk of a credit event such as a default or bankruptcy of a borrower. For example, a lender might u...',
            self::OEQT => 'Transaction is related to an equity derivative, i.e. a derivative whose pay-offs depends on the value of an underlying share, basket, or stock market index eg single stock derivatives, equity baske...',
            self::OIRT => 'Transaction relates to an interest rate derivative.',
            self::OPCL => 'Transaction is related to the administration of the account, such as closing or opening of the account.',
            self::OPTN => 'Transaction is related to precious metal option instruments OR to commodities option instruments',
            self::OSED => 'Transaction is related to derivatives operations of combined multiple types of instruments, including securities, bonds, commodities and/or weather derivatives.',
            self::OSWP => 'Transaction is related to any kind of swap derivative.',
            self::OTHB => 'Represents total of all cash legs for trades settling via CSD that have been matched but blocked by the CSD in order to not settle',
            self::OTHR => 'The “Other” family is used to cater for the Bank Transaction Code mandatory field, when the reported family does not match any of the families listed in the specified domain, but further details ar...',
            self::RCCN => 'Transaction is related to incoming cash movements that are related to cash management activities initiated by the owner of the sending account to optimise the return on the available funds.',
            self::RCDT => 'Receivable Credit Transfers are instructions to receive an amount of money from a debtor by the account owner. The receivable credit transfers are related to transactions received by the account ow...',
            self::RCHQ => 'Transaction is related to a written paper order – the cheque – received by the account owner from the cheque drawer, to credit the account of the owner.',
            self::RDDT => 'The Received Direct Debit transactions are related to instructions received by the account owner to debit the account.',
            self::SETT => 'Transaction relates to cash movement generated by a Trading, Clearing or Settlement Activity',
            self::SPOT => 'Transaction is related to the exchange of two currencies at an agreed upon exchange rate for cash delivery OR related to the purchase or the selling of precious metal at the spot price i.e. at the ...',
            self::SWAP => 'Transaction is related to a swap that involves the exchange of principal and interest in one currency for the same in another currency',
            self::SYDN => 'Transaction relates to a syndication which is the process of involving numerous different lenders in providing various portions of a loan.',
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
