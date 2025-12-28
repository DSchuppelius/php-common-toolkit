<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionSubFamily.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalBankTransactionSubFamily1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * TransactionSubFamily - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalBankTransactionSubFamily1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum TransactionSubFamily: string {
    /**
     * ACCC - Account Closing
     * Transaction is related to the closing of the account
     */
    case ACCC = 'ACCC';

    /**
     * ACCO - Account Opening
     * Transaction is related to the opening of the account
     */
    case ACCO = 'ACCO';

    /**
     * ACCT - Account Transfer
     * Transaction is related to the transfer of the account within the same institution (resulting in a...
     */
    case ACCT = 'ACCT';

    /**
     * ACDT - ACH Credit
     * Transaction is an electronic credit payment that is processed through an ACH.
     */
    case ACDT = 'ACDT';

    /**
     * ACON - ACH Concentration
     * Transfer is an ACH Concentration transaction, i.e. movement of funds from own smaller depository ...
     */
    case ACON = 'ACON';

    /**
     * ACOR - ACH Corporate Trade
     * Transfer is an ACH Corporate Trade transaction.
     */
    case ACOR = 'ACOR';

    /**
     * ADBT - ACH Debit
     * Transaction is an electronic debit payment that is processed through an ACH.
     */
    case ADBT = 'ADBT';

    /**
     * ADJT - Adjustments
     * Generic credit or debit adjustments related to the transaction without further details available ...
     */
    case ADJT = 'ADJT';

    /**
     * APAC - ACH Pre-Authorised
     * Transfer is an ACH Pre-Authorised transaction
     */
    case APAC = 'APAC';

    /**
     * ARET - ACH Return
     * Transfer is an ACH Return transaction, processed through an ACH.
     */
    case ARET = 'ARET';

    /**
     * AREV - ACH Reversal
     * Transaction is related to a reversal of an initial credit transfer, following pre-established rul...
     */
    case AREV = 'AREV';

    /**
     * ARPD - ARP Debit
     * Transaction is an account reconciliation package transaction that allows the account consolidatio...
     */
    case ARPD = 'ARPD';

    /**
     * ASET - ACH Settlement
     * Transfer is an ACH Settlement transaction. Likely used as a single transaction that is the total ...
     */
    case ASET = 'ASET';

    /**
     * ATXN - ACH Transaction
     * Transaction is an electronic payment that is processed through an ACH (generic ACH transfer).
     */
    case ATXN = 'ATXN';

    /**
     * AUTT - Automatic Transfer
     * Transaction is an individual automatic transfer transaction executed under agreed conditions.
     */
    case AUTT = 'AUTT';

    /**
     * BACT - Branch Account Transfer
     * Transaction is a cash concentration transfer between two financial institution branches belonging...
     */
    case BACT = 'BACT';

    /**
     * BBDD - SEPA B2B Direct Debit
     * Transaction is SEPA direct debit payment, as defined in the B2B Direct Debit Rulebook.
     */
    case BBDD = 'BBDD';

    /**
     * BCDP - Branch Deposit
     * Transaction is a counter or safe cash deposit operation, related to coin and currency deposit, op...
     */
    case BCDP = 'BCDP';

    /**
     * BCHQ - Bank Cheque 
     * Transaction is related to a cheque drawn on the account of the debtor’s financial institution, wh...
     */
    case BCHQ = 'BCHQ';

    /**
     * BCKV - Back Value
     * Transaction related to adjustments required on the Back Value of the transaction.
     */
    case BCKV = 'BCKV';

    /**
     * BCWD - Branch Withdrawal
     * Transaction is a counter cash withdrawal operation, related to coin and currency withdrawal, oper...
     */
    case BCWD = 'BCWD';

    /**
     * BIDS - Repurchase offer/Issuer Bid/Reverse Rights.
     * Offer to existing shareholders by the issuing company to repurchase equity or other securities co...
     */
    case BIDS = 'BIDS';

    /**
     * BKFE - Bank Fees
     * Charges that a bank applies to an account for custody services provided.
     */
    case BKFE = 'BKFE';

    /**
     * BONU - Bonus Issue/Capitalisation Issue
     * Bonus, scrip or capitalisation issue. Security holders receive additional assets free of payment ...
     */
    case BONU = 'BONU';

    /**
     * BOOK - Internal Book Transfer
     * Transaction is a transfer between –two different accounts within the same bank.
     */
    case BOOK = 'BOOK';

    /**
     * BPUT - Put Redemption
     * Early redemption of a security at the election of the holder subject to the terms and condition o...
     */
    case BPUT = 'BPUT';

    /**
     * BROK - Brokerage fee
     * Fee paid to a broker for services provided.
     */
    case BROK = 'BROK';

    /**
     * BSBC - Sell Buy Back
     * Cash movement related to the opening or closing of a sell-buy back transaction ie a transaction w...
     */
    case BSBC = 'BSBC';

    /**
     * BSBO - Buy Sell Back
     * Cash movement related to the opening or closing of a buy-sell back transaction ie a transaction w...
     */
    case BSBO = 'BSBO';

    /**
     * CAJT - DebitAdjustments
     * Token from Sub-Familydefinition Adjustment: Generic creditor debit adjustment srelated to the tra...
     */
    case CAJT = 'CAJT';

    /**
     * CAPG - Capital Gains Distribution
     * Distribution of profits resulting from the sale of company assets eg Shareholders of Mutual Funds...
     */
    case CAPG = 'CAPG';

    /**
     * CASH - Cash Letter
     * Transaction is related to a cash letter.
     */
    case CASH = 'CASH';

    /**
     * CCCH - Certified Customer Cheque
     * Transaction is related to a cheque drawn on the account of the debtor, and debited on the debtor’...
     */
    case CCCH = 'CCCH';

    /**
     * CCHQ - Cheque
     * Transaction is related to a cheque drawn on the account of the debtor, and debited on the debtor’...
     */
    case CCHQ = 'CCHQ';

    /**
     * CDIS - Controlled Disbursement
     * Transaction is related to a service that provides for movement of funds associated with cheque pr...
     */
    case CDIS = 'CDIS';

    /**
     * CDPT - Cash Deposit
     * Transaction is an ATM deposit operation or transaction is a counter or safe cash deposit operatio...
     */
    case CDPT = 'CDPT';

    /**
     * CHAR - Charge/fees
     * Overall charge paid for an account. May or may not be split up into detailed charges.
     */
    case CHAR = 'CHAR';

    /**
     * CHKD - Cheque Deposit
     * Transaction is a counter or safe cash deposit operation, related to cheque deposit
     */
    case CHKD = 'CHKD';

    /**
     * CHRG - Charges
     * Generic charges related to the transaction without further details available
     */
    case CHRG = 'CHRG';

    /**
     * CLAI - Compensation/Claims
     * Cash movement related to the payment of a claim or compensation.
     */
    case CLAI = 'CLAI';

    /**
     * CLCQ - Circular Cheque
     * Transaction is related to an instruction from a bank to its correspondent bank to pay the credito...
     */
    case CLCQ = 'CLCQ';

    /**
     * CMBO - Corporate mark broker owned
     * Cash movement related to corporate mark broker owned collateral
     */
    case CMBO = 'CMBO';

    /**
     * CMCO - Corporate mark client owned
     * Cash movement related to corporate mark client owned collateral
     */
    case CMCO = 'CMCO';

    /**
     * COAT - Corporate Own Account Transfer
     * Transaction is a cash concentration transfer between own accounts, i.e., a transfer between 2 dif...
     */
    case COAT = 'COAT';

    /**
     * COME - Commission excluding taxes
     * Generic commissions without taxes related to the transaction without further details available
     */
    case COME = 'COME';

    /**
     * COMI - Commission including taxes
     * Generic commissions including taxes related to the transaction without further details available
     */
    case COMI = 'COMI';

    /**
     * COMM - Commission
     * Generic commissions without further details related to the transaction
     */
    case COMM = 'COMM';

    /**
     * COMT - Non Taxable commissions
     * Generic non-taxable commissions related to the transaction without further details available
     */
    case COMT = 'COMT';

    /**
     * CONV - Conversion
     * Conversion of securities (generally convertible bonds or preferred shares) into another form of s...
     */
    case CONV = 'CONV';

    /**
     * CPRB - Corporate Rebate
     * Cash movement related to a corporate rebate
     */
    case CPRB = 'CPRB';

    /**
     * CQRV - Cheque Reversal
     * Transaction is related to a reversal of a cheque payment.
     */
    case CQRV = 'CQRV';

    /**
     * CRCQ - Crossed Cheque
     * Transaction is related to a cheque that must be paid into an account and not cashed over the coun...
     */
    case CRCQ = 'CRCQ';

    /**
     * CROS - Cross Trade
     * Cash movement related to an investment funds cross in or out transaction
     */
    case CROS = 'CROS';

    /**
     * CSHA - Cash Letter Adjustment
     * Transaction is related to an adjustment of a cash letter payment.
     */
    case CSHA = 'CSHA';

    /**
     * CSLI - Cash in lieu
     * Cash paid in lieu of something else.
     */
    case CSLI = 'CSLI';

    /**
     * CWDL - Cash Withdrawal
     * Transaction is an ATM withdrawal operation or transaction is a counter cash withdrawal operation,...
     */
    case CWDL = 'CWDL';

    /**
     * DAJT - CreditAdjustments
     * Token from Sub-Familydefinition Adjustment: Generic creditor debit adjustment srelated to the tra...
     */
    case DAJT = 'DAJT';

    /**
     * DDFT - Discounted Draft
     * Transaction is related to a discounted draft, i.e. the beneficiary has received an early payment ...
     */
    case DDFT = 'DDFT';

    /**
     * DDWN - Drawdown
     * Transaction is related to drawdown of fixed term / notice / mortgage / consumer loans or syndicat...
     */
    case DDWN = 'DDWN';

    /**
     * DECR - Decrease in Value
     * Reduction of face value of a single share. The number of circulating shares remains unchanged. Th...
     */
    case DECR = 'DECR';

    /**
     * DMCG - Draft Maturity Change
     * Transaction is related to the change of the maturity date of a draft.
     */
    case DMCG = 'DMCG';

    /**
     * DMCT - Domestic Credit Transfer
     * Transaction is a  in-country domestic currency credit transfer
     */
    case DMCT = 'DMCT';

    /**
     * DPST - Deposit
     * Transaction is related to opening of the fixed term / notice deposits contract.
     */
    case DPST = 'DPST';

    /**
     * DRAW - Drawing
     * Redemption in part before the scheduled final maturity date of a security. Drawing is distinct fr...
     */
    case DRAW = 'DRAW';

    /**
     * DRIP - Dividend Reinvestment
     * Dividend payment where holders can keep cash or have the cash reinvested in the market by the iss...
     */
    case DRIP = 'DRIP';

    /**
     * DSBR - Controlled Disbursement
     * Transaction is related to a service that provides for movement of funds associated with cheque pr...
     */
    case DSBR = 'DSBR';

    /**
     * DTCH - Dutch Auction
     * Action by a party wishing to acquire a security. Holders of the security are invited to make an o...
     */
    case DTCH = 'DTCH';

    /**
     * DVCA - Cash Dividend
     * Distribution of cash to shareholders, in proportion to their equity holding. Ordinary dividends a...
     */
    case DVCA = 'DVCA';

    /**
     * DVOP - Dividend Option
     * Distribution of a dividend to shareholders with a choice of benefit to receive. Shareholders may ...
     */
    case DVOP = 'DVOP';

    /**
     * EQBO - Equity mark broker owned
     * Cash movement related to equity mark broker owned collateral
     */
    case EQBO = 'EQBO';

    /**
     * EQCO - Equity mark client owned
     * Cash movement related to equity mark client owned collateral
     */
    case EQCO = 'EQCO';

    /**
     * ERTA - Exchange Rate Adjustment
     * Transaction relates to corrections on the account that result in a debit / credit on the account ...
     */
    case ERTA = 'ERTA';

    /**
     * ERWA - Lending income
     * Income received from lending activity
     */
    case ERWA = 'ERWA';

    /**
     * ERWI - Borrowing fee
     * Fee paid for borrowing activity.
     */
    case ERWI = 'ERWI';

    /**
     * ESCT - SEPA Credit Transfer
     * Transaction is a SEPA credit transfer
     */
    case ESCT = 'ESCT';

    /**
     * ESDD - SEPA Core Direct Debit
     * Transaction is SEPA core direct debit payment.
     */
    case ESDD = 'ESDD';

    /**
     * EXOF - Exchange
     * Exchange of holdings for other securities and/or cash.  The exchange can be either mandatory or v...
     */
    case EXOF = 'EXOF';

    /**
     * EXRI - Call on intermediate securities
     * Call or exercise on nil-paid securities or intermediate securities resulting from an intermediate...
     */
    case EXRI = 'EXRI';

    /**
     * EXWA - Warrant Exercise/Warrant Conversion
     * Option to buy (call warrant) or to sell (put warrant) a specific amount of equities, cash, commod...
     */
    case EXWA = 'EXWA';

    /**
     * FCDP - Foreign Currency Deposit
     * Transaction is a movement resulting from foreign currency sell operations (bank notes and coins) ...
     */
    case FCDP = 'FCDP';

    /**
     * FCTA - Factor Update
     * Cash movement related to a factor update transaction on a purchase or sale of factored securities.
     */
    case FCTA = 'FCTA';

    /**
     * FCWD - Foreign Currency Withdrawal
     * Transaction is a movement resulting from foreign currency buy operations (bank notes and coins) a...
     */
    case FCWD = 'FCWD';

    /**
     * FEES - Fees
     * Generic fees related to the transaction without further details available
     */
    case FEES = 'FEES';

    /**
     * FICT - Financial Institution Credit Transfer
     * Transaction is a financial institution credit transfer, i.e. the debtor and creditor are financia...
     */
    case FICT = 'FICT';

    /**
     * FIDD - Financial Institution Direct Debit Payment
     * Transaction is a financial institution direct debit payment.
     */
    case FIDD = 'FIDD';

    /**
     * FIOA - Financial Institution Own Account Transfer
     * Transaction is a cash concentration transfer between financial institution’s own accounts, i.e., ...
     */
    case FIOA = 'FIOA';

    /**
     * FLTA - Float adjustment
     * Transaction relates to corrections on the account that result in a debit / credit on the account ...
     */
    case FLTA = 'FLTA';

    /**
     * FRZF - Freeze of funds
     * Transaction is related to the freeze of funds under Import Stand-by letter of credit or documenta...
     */
    case FRZF = 'FRZF';

    /**
     * FUCO - Futures Commission
     * A fee charged for executing futures transactions.
     */
    case FUCO = 'FUCO';

    /**
     * FUTU - Future Variation Margin
     * Transaction is for the payment of futures variation margin/s.
     */
    case FUTU = 'FUTU';

    /**
     * FWBC - Forwards broker owned collateral
     * Cash movement related to forwards broker owned collateral
     */
    case FWBC = 'FWBC';

    /**
     * FWCC - Forwards client owned collateral
     * Cash movement related to forwards client owned collateral
     */
    case FWCC = 'FWCC';

    /**
     * GEN1 - Withdrawal/distribution
     * Cash movement related to the withdrawal/distribution of cash out of an account.
     */
    case GEN1 = 'GEN1';

    /**
     * GEN2 - Deposit/Contribution
     * Cash movement related to the deposit/contribution of cash into an account.
     */
    case GEN2 = 'GEN2';

    /**
     * IADD - Invoice Accepted with Differed Due Date
     * Service allowing the debtor bank to inform directly and in advance the provider of its customer a...
     */
    case IADD = 'IADD';

    /**
     * ICCT - Intra Company Transfer
     * Transaction is an intra-company cash concentration transfer, i.e., a payment between two differen...
     */
    case ICCT = 'ICCT';

    /**
     * INFD - Fixed Deposit Interest Amount
     * Interest payment distributed to holders of a deposit with a fixed term.
     */
    case INFD = 'INFD';

    /**
     * INSP - Inspeci/Share Exchange
     * Cash movement related to a move of stock into or out of a pooled account.
     */
    case INSP = 'INSP';

    /**
     * INTR - Interest
     * Generic interest related to the transaction without further details available or regular interest...
     */
    case INTR = 'INTR';

    /**
     * ISSU - Depositary Receipt Issue
     * Cash movement related to a depositary receipt issue operation.
     */
    case ISSU = 'ISSU';

    /**
     * LBCA - Credit Adjustment
     * Transaction is related to a lockbox credit adjustment.
     */
    case LBCA = 'LBCA';

    /**
     * LBDB - Debit
     * Transaction is related to a lockbox debit.
     */
    case LBDB = 'LBDB';

    /**
     * LBDP - Deposit
     * Transaction is related to a lockbox deposit
     */
    case LBDP = 'LBDP';

    /**
     * LIQU - Liquidation Dividend / Liquidation Payment
     * Distribution of cash, assets, or both.  Debt may be paid in order of priority based on preferred ...
     */
    case LIQU = 'LIQU';

    /**
     * MARG - Margin Payments
     * Cash collateral movement to meet the minimum amount of financial instruments that must be maintai...
     */
    case MARG = 'MARG';

    /**
     * MCAL - Full Call / Early Redemption
     * Redemption of an entire issue outstanding of bonds/preferred equity by the issuer before final ma...
     */
    case MCAL = 'MCAL';

    /**
     * MGCC - Margin client owned cash collateral
     * Cash collateral movement related to margin client owned cash collateral
     */
    case MGCC = 'MGCC';

    /**
     * MIXD - Mixed Deposit
     * Transaction is a counter cash  deposit operation, related to a combination of cheque, coin and cu...
     */
    case MIXD = 'MIXD';

    /**
     * MNFE - Management Fees
     * Charges that an investment manager applies to an account for services provided.
     */
    case MNFE = 'MNFE';

    /**
     * MRGR - Merger
     * Exchange of outstanding securities, initiated by the issuer which may include options, as the res...
     */
    case MRGR = 'MRGR';

    /**
     * MSCD - Miscellaneous Deposit
     * Transaction is a counter deposit related to undefined underlying instruments
     */
    case MSCD = 'MSCD';

    /**
     * NETT - Netting
     * Cash movement related to the netting of securities trades.
     */
    case NETT = 'NETT';

    /**
     * NPCC - Non-Presented Circular Cheque
     * Transaction is related to a non-presented circular cheque.
     */
    case NPCC = 'NPCC';

    /**
     * NSYN - Non Syndicated
     * Cash movement related to the issue of a medium and short term paper (CP, CD, MTN, notes etc) unde...
     */
    case NSYN = 'NSYN';

    /**
     * NTAV - Not available
     * The “Not Available” Sub-Family is used to cater for the Bank Transaction Code mandatory field, wh...
     */
    case NTAV = 'NTAV';

    /**
     * ODFT - Overdraft
     * Transaction relates to a cash management instruction that requesting the processing of overdraft ...
     */
    case ODFT = 'ODFT';

    /**
     * ODLT - Odd Lot Sale/Purchase
     * Sale or purchase of odd-lots to/from the issuing company, initiated either by the holder of the s...
     */
    case ODLT = 'ODLT';

    /**
     * OODD - One-Off Direct Debit
     * Transaction is a one-off direct debit payment.
     */
    case OODD = 'OODD';

    /**
     * OPBC - Option broker owned collateral
     * Cash collateral movement related to option broker owned collateral
     */
    case OPBC = 'OPBC';

    /**
     * OPCC - Option client owned collateral
     * Cash movement related to option client owned collateral
     */
    case OPCC = 'OPCC';

    /**
     * OPCQ - Open Cheque
     * Transaction is related to a cheque that may only be cashed at the bank of origin.
     */
    case OPCQ = 'OPCQ';

    /**
     * ORCQ - Order Cheque
     * Transaction is related to a cheque made payable to a named recipient ‘or order’. The payee can ei...
     */
    case ORCQ = 'ORCQ';

    /**
     * OTCC - OTC CCP
     * Represents the cash legs of transactions ‘over the counter’ (OTC), going through CCP functions
     */
    case OTCC = 'OTCC';

    /**
     * OTCG - OTC
     * Represents the cash legs of transactions ‘over the counter’ (OTC) exchanges – this code is only t...
     */
    case OTCG = 'OTCG';

    /**
     * OTCN - OTC Non-CCP
     * Represents the cash legs of transactions ‘over the counter’ (OTC), not going through CCP functions
     */
    case OTCN = 'OTCN';

    /**
     * OTHR - Other
     * The “Other” Sub-Family is used to cater for the Bank Transaction Code mandatory field, when the r...
     */
    case OTHR = 'OTHR';

    /**
     * OVCH - Overdraft Charge
     * Fees charged to an account when the cash is overdrawn.
     */
    case OVCH = 'OVCH';

    /**
     * OWNE - External Account Transfer
     * Cash movement related to an external securities account transfer ie a transfer involving more tha...
     */
    case OWNE = 'OWNE';

    /**
     * OWNI - Internal Account Transfer
     * Cash movement related to an internal securities account transfer ie a transfer involving one inst...
     */
    case OWNI = 'OWNI';

    /**
     * PADD - Pre-Authorised Direct Debit
     * Transaction is a Pre-Authorised Direct Debit payment, e.g. the ACH pre-authorised Direct Debit.
     */
    case PADD = 'PADD';

    /**
     * PAIR - Pair-Off
     * Clean cash movement related to a pair-off transaction, ie a buyback to offset and effectively liq...
     */
    case PAIR = 'PAIR';

    /**
     * PCAL - Partial Redemption with reduction of nominal value
     * Securities are redeemed in part before their scheduled final maturity date with reduction of the ...
     */
    case PCAL = 'PCAL';

    /**
     * PLAC - Placement
     * Cash movement related to a placement/new issue.
     */
    case PLAC = 'PLAC';

    /**
     * PMDD - Direct Debit
     * Transaction is a legacy direct debit payment, which is related to a recurring payment. The settle...
     */
    case PMDD = 'PMDD';

    /**
     * PORT - Portfolio Move
     * Cash movement related to a portfolio move from one investment manager to another and/or from an a...
     */
    case PORT = 'PORT';

    /**
     * POSC - Credit Card Payment
     * Transaction is a payment done through a credit card that permits the credit cardholders to electr...
     */
    case POSC = 'POSC';

    /**
     * POSD - Point-of-Sale (POS) Payment  - Debit Card
     * Transaction is a payment done through an electronic network of banks, debit cardholders, and merc...
     */
    case POSD = 'POSD';

    /**
     * POSP - Point-of-Sale (POS) Payment
     * Transaction is a payment done through an electronic network of banks, debit cardholders, and merc...
     */
    case POSP = 'POSP';

    /**
     * PPAY - Principal Payment
     * Transaction is related to the payment of the principal of fixed term / notice / mortgage / consum...
     */
    case PPAY = 'PPAY';

    /**
     * PRCT - Priority Credit Transfer
     * Transaction is a credit transfer defined with higher priority, eg a PRIEURO credit transfer
     */
    case PRCT = 'PRCT';

    /**
     * PRDD - Reversal due to Payment Reversal
     * Transaction is related to the reversal / reimbursement of a direct debit transaction (which may b...
     */
    case PRDD = 'PRDD';

    /**
     * PRED - Partial Redemption Without Reduction of Nominal Value
     * Securities are redeemed in part before their scheduled final maturity date without reduction of t...
     */
    case PRED = 'PRED';

    /**
     * PRII - Interest Payment with Principle
     * Payment of a portion of the principal of an interest bearing asset, in addition to the interest p...
     */
    case PRII = 'PRII';

    /**
     * PRIN - Interest Payment with Principle
     * Payment of a portion of the principal of an interest bearing asset, in addition to the interest p...
     */
    case PRIN = 'PRIN';

    /**
     * PRIO - Priority Issue
     * Form of open or public offer where, due to a limited amount of securities available, priority is ...
     */
    case PRIO = 'PRIO';

    /**
     * PRUD - Principal Pay-down/pay-up
     * Partial payment or receipt of principal on factored securities.
     */
    case PRUD = 'PRUD';

    /**
     * PSTE - Posting Error
     * Translation relates to the correction of a posting error.
     */
    case PSTE = 'PSTE';

    /**
     * RCDD - Reversal due to a Payment Cancellation Request
     * Transaction is related to the cancellation of an initial direct debit upon request from the credi...
     */
    case RCDD = 'RCDD';

    /**
     * REAA - Redemption Asset Allocation
     * Cash movement related to a redemption in an asset allocation plan which enables investors to with...
     */
    case REAA = 'REAA';

    /**
     * REDM - Redemption / Final Maturity
     * Cash movement related to the redemption of an investment fund or Redemption of an entire issue ou...
     */
    case REDM = 'REDM';

    /**
     * REPU - Repo
     * Cash collateral marks related to repo or cash movement related to the initiation or closing of a ...
     */
    case REPU = 'REPU';

    /**
     * RESI - Futures Residual Amount
     * Transaction related to a futures residual amount.
     */
    case RESI = 'RESI';

    /**
     * RHTS - Rights Issue/Subscription Rights/Rights Offer
     * Distribution of a security or privilege that gives the holder an entitlement or right to take par...
     */
    case RHTS = 'RHTS';

    /**
     * RIMB - Reimbursements
     * Generic reimbursement of costs related to the transaction without further details available
     */
    case RIMB = 'RIMB';

    /**
     * RNEW - Renewal
     * Transaction is related to renewal of fixed term / notice / mortgage / consumer loans or syndicati...
     */
    case RNEW = 'RNEW';

    /**
     * RPCR - Reversal due to Payment Cancellation Request
     * Transaction is related to the cancellation of an initial credit transfer upon request from the de...
     */
    case RPCR = 'RPCR';

    /**
     * RPMT - Repayment
     * Transaction is related to repayment of the fixed term / notice deposits.
     */
    case RPMT = 'RPMT';

    /**
     * RRTN - Reversal due to Payment Return/reimbursement of a Credit Transfer
     * Transaction is related to the return/reimbursement of a credit transfer transaction (which may be...
     */
    case RRTN = 'RRTN';

    /**
     * RVPO - Reverse Repo
     * Cash movement related to the initiation or closing of a reverse repo transaction in which a buyer...
     */
    case RVPO = 'RVPO';

    /**
     * RWPL - Redemption Withdrawing Plan
     * Cash movement related to a withdrawal by individuals in the framework of a structured plan for in...
     */
    case RWPL = 'RWPL';

    /**
     * SABG - Settlement against bank guarantee
     * Transaction is related to the settlement of the Letter of Credit (Stand-By or Documentary) agains...
     */
    case SABG = 'SABG';

    /**
     * SALA - Payroll/Salary Payment
     * Transaction is related to the payment of a payroll salary
     */
    case SALA = 'SALA';

    /**
     * SDVA - Same Day Value Credit Transfer
     * Transfer is a credit transfer whereby the payment was executed with same day value to the benefic...
     */
    case SDVA = 'SDVA';

    /**
     * SECB - Securities Borrowing
     * Cash movement related to the initiation or closing of a securities borrowing transaction or cash ...
     */
    case SECB = 'SECB';

    /**
     * SECL - Securities Lending
     * Cash movement related to the initiation or closing of a securities lending transaction or cash co...
     */
    case SECL = 'SECL';

    /**
     * SHPR - Equity Premium Reserve
     * Shareholders receive an amount in cash issued from the equity premium reserve. This event is simi...
     */
    case SHPR = 'SHPR';

    /**
     * SLBC - Lending Broker Owned Cash Collateral
     * Cash movement related to lending broker owned collateral
     */
    case SLBC = 'SLBC';

    /**
     * SLCC - Lending Client Owned Cash Collateral
     * Cash movement related to lending client owned collateral
     */
    case SLCC = 'SLCC';

    /**
     * SMCD - Smart-Card Payment
     * Transaction is a card-based payment. For the merchant, the transaction related to transfer of the...
     */
    case SMCD = 'SMCD';

    /**
     * SMRT - Smart-Card Payment
     * Transaction is a card-based payment. The smart-card is a system that stores values for transactio...
     */
    case SMRT = 'SMRT';

    /**
     * SOSE - Settlement of Sight Export document
     * Transaction is related to the settlement upon presentation of the Export Letter of credit (Stand-...
     */
    case SOSE = 'SOSE';

    /**
     * SOSI - Settlement of Sight Import document
     * Transaction is related to the settlement upon presentation of the Import Letter of credit (Stand-...
     */
    case SOSI = 'SOSI';

    /**
     * SSPL - Subscription Savings Plan
     * Cash movement related to a subscription for a savings plan, i.e. money set aside by individuals i...
     */
    case SSPL = 'SSPL';

    /**
     * STAC - Settlement after collection
     * Transaction is related to a settlement after collection
     */
    case STAC = 'STAC';

    /**
     * STAM - Settlement at Maturity / Stamp duty
     * Transaction is related to a draft or bill to order which has been paid on maturity date or stamp ...
     */
    case STAM = 'STAM';

    /**
     * STDO - Standing Order
     * Transaction is a standing order. A standing order is an instruction given by a party having expli...
     */
    case STDO = 'STDO';

    /**
     * STLM - Settlement
     * Transaction relates to the settlement of a guarantee.
     */
    case STLM = 'STLM';

    /**
     * STLR - Settlement under reserve
     * Transaction is related to a settlement under reserve of the draft or transaction is related to a ...
     */
    case STLR = 'STLR';

    /**
     * SUAA - Subscription Asset Allocation
     * Cash movement related to an asset allocation plan that enables investors to allocate, by percenta...
     */
    case SUAA = 'SUAA';

    /**
     * SUBS - Subscription
     * Cash movement related to the subscription to an investment fund.
     */
    case SUBS = 'SUBS';

    /**
     * SWAP - Swap Payment
     * Transaction is a swap related payment.
     */
    case SWAP = 'SWAP';

    /**
     * SWBC - Swap broker owned collateral
     * Cash movement related to swap broker owned collateral
     */
    case SWBC = 'SWBC';

    /**
     * SWCC - Client Owned Collateral
     * Transaction is a swap client owned collateral
     */
    case SWCC = 'SWCC';

    /**
     * SWEP - Sweeping
     * Cash movement related to a sweep eg an end of day short term investment vehicle or transaction re...
     */
    case SWEP = 'SWEP';

    /**
     * SWFP - Final Payment
     * Transaction is a swap related final payment
     */
    case SWFP = 'SWFP';

    /**
     * SWIC - Switch
     * Cash movement related to a change between investment funds (usually of the same family) with cash...
     */
    case SWIC = 'SWIC';

    /**
     * SWPP - Partial Payment
     * Transaction is a swap related partial payment
     */
    case SWPP = 'SWPP';

    /**
     * SWRS - Reset Payment
     * Transaction is a swap related reset payment
     */
    case SWRS = 'SWRS';

    /**
     * SWUF - Upfront Payment
     * Transaction is a swap related upfront payment
     */
    case SWUF = 'SWUF';

    /**
     * SYND - Syndicated
     * Cash movement related to the issue of securities (bonds, warrants, equities etc) through a syndic...
     */
    case SYND = 'SYND';

    /**
     * TAXE - Taxes
     * Generic taxes related to the transaction without further details available
     */
    case TAXE = 'TAXE';

    /**
     * TBAC - TBA closing
     * Cash movement related to a TBA (To Be Announced) closing transaction.
     */
    case TBAC = 'TBAC';

    /**
     * TCDP - Travellers Cheques Deposit
     * Transaction is a movement resulting from a travellers’ cheques deposit by the account owner at th...
     */
    case TCDP = 'TCDP';

    /**
     * TCWD - Travellers Cheques Withdrawal
     * Transaction is a movement resulting from a travellers’ cheques withdrawal by the account owner at...
     */
    case TCWD = 'TCWD';

    /**
     * TEND - Tender
     * Cash movement related to an offer made to shareholders, normally by a third party, requesting the...
     */
    case TEND = 'TEND';

    /**
     * TOPG - Topping
     * Transaction is a cash management instruction, requesting to top the account above a certain floor...
     */
    case TOPG = 'TOPG';

    /**
     * TOUT - Transfer Out
     * Cash movement related to a debit to an account on the shareholders register, and is not linked to...
     */
    case TOUT = 'TOUT';

    /**
     * TRAD - Trade
     * Cash movement related to a securities purchase or sale.
     */
    case TRAD = 'TRAD';

    /**
     * TREC - Tax Reclaim
     * Event related to tax reclaim activities.
     */
    case TREC = 'TREC';

    /**
     * TRFE - Transaction Fees
     * Fees associated with security settlement activity.
     */
    case TRFE = 'TRFE';

    /**
     * TRIN - Transfer In
     * Cash movement related to an incoming credit to an account on the shareholders register, and is no...
     */
    case TRIN = 'TRIN';

    /**
     * TRPO - Triparty Repo
     * Cash movement related to the initiation or closing of a triparty repo transaction or cash collate...
     */
    case TRPO = 'TRPO';

    /**
     * TRVO - Triparty Reverse Repo
     * Cash movement related to the initiation or closing of a triparty reverse repo transaction
     */
    case TRVO = 'TRVO';

    /**
     * TTLS - Treasury Tax And Loan Service
     * Transaction is related to a Treasury Tax and Loan Service, i.e. a service offered by the Federal ...
     */
    case TTLS = 'TTLS';

    /**
     * TURN - Turnaround
     * Cash movement related to a turnaround transaction, the simultaneous purchase and sell of the same...
     */
    case TURN = 'TURN';

    /**
     * UDFT - Dishonoured/Unpaid Draft
     * Transaction is related to a Dishonoured / Unpaid Draft or Bill To Order. The beneficiary has rece...
     */
    case UDFT = 'UDFT';

    /**
     * UNCO - Underwriting Commission
     * Fee investment bankers charge for underwriting a security issue.
     */
    case UNCO = 'UNCO';

    /**
     * UPCQ - Unpaid Cheque
     * Transaction is related to a cheque for which the settlement could not be completed.
     */
    case UPCQ = 'UPCQ';

    /**
     * UPCT - Unpaid Card Transaction
     * Transaction is related to the return of a debit/credit payment that has not been settled or has b...
     */
    case UPCT = 'UPCT';

    /**
     * UPDD - Reversal due to Return/Unpaid Direct Debit
     * Transaction is related to a Returned Direct Debit. Several reasons may exist: debtor’s account cl...
     */
    case UPDD = 'UPDD';

    /**
     * URCQ - Cheque Under Reserve
     * Transaction is related to a cheque booked before settlement of the funds has taken place.
     */
    case URCQ = 'URCQ';

    /**
     * URDD - Direct Debit under reserve
     * Transaction is a legacy direct debit payment under reserve of settlement. Although the amount has...
     */
    case URDD = 'URDD';

    /**
     * VALD - Value Date
     * Transaction relates to adjustments required on the value date of the transaction and/or the balan...
     */
    case VALD = 'VALD';

    /**
     * VCOM - Credit Transfer with agreed Commercial Information
     * Transaction is a credit transfer including commercial information, i.e. additional information ag...
     */
    case VCOM = 'VCOM';

    /**
     * WITH - Withholding Tax
     * Tax levied by a country of source on income paid, usually on dividends remitted to the home count...
     */
    case WITH = 'WITH';

    /**
     * XBCP - Cross-Border Credit Card Payment
     * Transaction is a payment done through a credit card in a foreign country.
     */
    case XBCP = 'XBCP';

    /**
     * XBCQ - Foreign Cheque
     * Transaction is related to a cheque drawn on the account of the debtor, and cashed in a different ...
     */
    case XBCQ = 'XBCQ';

    /**
     * XBCT - Cross-Border Credit Transfer
     * Transaction is a cross-border credit transfer
     */
    case XBCT = 'XBCT';

    /**
     * XBCW - Cross-Border Cash Withdrawal
     * Transaction is an ATM cash withdrawal operation in a foreign country.
     */
    case XBCW = 'XBCW';

    /**
     * XBDD - Cross-Border Direct Debit
     * Transaction is a cross-border direct debit payment.
     */
    case XBDD = 'XBDD';

    /**
     * XBRD - Cross-Border
     * Transaction is related to a cash management activity that is cross-border cash pooling or account...
     */
    case XBRD = 'XBRD';

    /**
     * XBSA - Cross-Border Payroll/Salary Payment
     * Transaction is related to the payment of a cross-border payroll salary
     */
    case XBSA = 'XBSA';

    /**
     * XBST - Cross-Border Standing Order
     * Transaction is a cross-border standing order
     */
    case XBST = 'XBST';

    /**
     * XCHC - Exchange Traded CCP
     * Representing cash legs of transactions in exchanges, going through CCP functions
     */
    case XCHC = 'XCHC';

    /**
     * XCHG - Exchange Traded
     * Representing cash legs of transactions traded in exchanges – this code is only to be used where t...
     */
    case XCHG = 'XCHG';

    /**
     * XCHN - Exchange Traded Non-CCP
     * Represents cash legs of transactions traded in exchanges, not going through CCP functions
     */
    case XCHN = 'XCHN';

    /**
     * XICT - Cross-Border Intra Company Transfer
     * Transaction is a cross-border intra-company cash concentration transfer.
     */
    case XICT = 'XICT';

    /**
     * XPCQ - Unpaid Foreign Cheque
     * Transaction is related to a foreign cheque for which the settlement could not be completed.
     */
    case XPCQ = 'XPCQ';

    /**
     * XRCQ - Foreign Cheque Under Reserve
     * Transaction is related to a foreign cheque, booked before settlement of the funds has taken place.
     */
    case XRCQ = 'XRCQ';

    /**
     * YTDA - YTD Adjustment
     * Transaction relates to corrections on the account that result in a debit / credit on the account ...
     */
    case YTDA = 'YTDA';

    /**
     * ZABA - Zero Balancing
     * Transaction is a cash management instruction, requesting to zero balance the account. Zero Balanc...
     */
    case ZABA = 'ZABA';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ACCC => 'Account Closing',
            self::ACCO => 'Account Opening',
            self::ACCT => 'Account Transfer',
            self::ACDT => 'ACH Credit',
            self::ACON => 'ACH Concentration',
            self::ACOR => 'ACH Corporate Trade',
            self::ADBT => 'ACH Debit',
            self::ADJT => 'Adjustments',
            self::APAC => 'ACH Pre-Authorised',
            self::ARET => 'ACH Return',
            self::AREV => 'ACH Reversal',
            self::ARPD => 'ARP Debit',
            self::ASET => 'ACH Settlement',
            self::ATXN => 'ACH Transaction',
            self::AUTT => 'Automatic Transfer',
            self::BACT => 'Branch Account Transfer',
            self::BBDD => 'SEPA B2B Direct Debit',
            self::BCDP => 'Branch Deposit',
            self::BCHQ => 'Bank Cheque ',
            self::BCKV => 'Back Value',
            self::BCWD => 'Branch Withdrawal',
            self::BIDS => 'Repurchase offer/Issuer Bid/Reverse Rights.',
            self::BKFE => 'Bank Fees',
            self::BONU => 'Bonus Issue/Capitalisation Issue',
            self::BOOK => 'Internal Book Transfer',
            self::BPUT => 'Put Redemption',
            self::BROK => 'Brokerage fee',
            self::BSBC => 'Sell Buy Back',
            self::BSBO => 'Buy Sell Back',
            self::CAJT => 'DebitAdjustments',
            self::CAPG => 'Capital Gains Distribution',
            self::CASH => 'Cash Letter',
            self::CCCH => 'Certified Customer Cheque',
            self::CCHQ => 'Cheque',
            self::CDIS => 'Controlled Disbursement',
            self::CDPT => 'Cash Deposit',
            self::CHAR => 'Charge/fees',
            self::CHKD => 'Cheque Deposit',
            self::CHRG => 'Charges',
            self::CLAI => 'Compensation/Claims',
            self::CLCQ => 'Circular Cheque',
            self::CMBO => 'Corporate mark broker owned',
            self::CMCO => 'Corporate mark client owned',
            self::COAT => 'Corporate Own Account Transfer',
            self::COME => 'Commission excluding taxes',
            self::COMI => 'Commission including taxes',
            self::COMM => 'Commission',
            self::COMT => 'Non Taxable commissions',
            self::CONV => 'Conversion',
            self::CPRB => 'Corporate Rebate',
            self::CQRV => 'Cheque Reversal',
            self::CRCQ => 'Crossed Cheque',
            self::CROS => 'Cross Trade',
            self::CSHA => 'Cash Letter Adjustment',
            self::CSLI => 'Cash in lieu',
            self::CWDL => 'Cash Withdrawal',
            self::DAJT => 'CreditAdjustments',
            self::DDFT => 'Discounted Draft',
            self::DDWN => 'Drawdown',
            self::DECR => 'Decrease in Value',
            self::DMCG => 'Draft Maturity Change',
            self::DMCT => 'Domestic Credit Transfer',
            self::DPST => 'Deposit',
            self::DRAW => 'Drawing',
            self::DRIP => 'Dividend Reinvestment',
            self::DSBR => 'Controlled Disbursement',
            self::DTCH => 'Dutch Auction',
            self::DVCA => 'Cash Dividend',
            self::DVOP => 'Dividend Option',
            self::EQBO => 'Equity mark broker owned',
            self::EQCO => 'Equity mark client owned',
            self::ERTA => 'Exchange Rate Adjustment',
            self::ERWA => 'Lending income',
            self::ERWI => 'Borrowing fee',
            self::ESCT => 'SEPA Credit Transfer',
            self::ESDD => 'SEPA Core Direct Debit',
            self::EXOF => 'Exchange',
            self::EXRI => 'Call on intermediate securities',
            self::EXWA => 'Warrant Exercise/Warrant Conversion',
            self::FCDP => 'Foreign Currency Deposit',
            self::FCTA => 'Factor Update',
            self::FCWD => 'Foreign Currency Withdrawal',
            self::FEES => 'Fees',
            self::FICT => 'Financial Institution Credit Transfer',
            self::FIDD => 'Financial Institution Direct Debit Payment',
            self::FIOA => 'Financial Institution Own Account Transfer',
            self::FLTA => 'Float adjustment',
            self::FRZF => 'Freeze of funds',
            self::FUCO => 'Futures Commission',
            self::FUTU => 'Future Variation Margin',
            self::FWBC => 'Forwards broker owned collateral',
            self::FWCC => 'Forwards client owned collateral',
            self::GEN1 => 'Withdrawal/distribution',
            self::GEN2 => 'Deposit/Contribution',
            self::IADD => 'Invoice Accepted with Differed Due Date',
            self::ICCT => 'Intra Company Transfer',
            self::INFD => 'Fixed Deposit Interest Amount',
            self::INSP => 'Inspeci/Share Exchange',
            self::INTR => 'Interest',
            self::ISSU => 'Depositary Receipt Issue',
            self::LBCA => 'Credit Adjustment',
            self::LBDB => 'Debit',
            self::LBDP => 'Deposit',
            self::LIQU => 'Liquidation Dividend / Liquidation Payment',
            self::MARG => 'Margin Payments',
            self::MCAL => 'Full Call / Early Redemption',
            self::MGCC => 'Margin client owned cash collateral',
            self::MIXD => 'Mixed Deposit',
            self::MNFE => 'Management Fees',
            self::MRGR => 'Merger',
            self::MSCD => 'Miscellaneous Deposit',
            self::NETT => 'Netting',
            self::NPCC => 'Non-Presented Circular Cheque',
            self::NSYN => 'Non Syndicated',
            self::NTAV => 'Not available',
            self::ODFT => 'Overdraft',
            self::ODLT => 'Odd Lot Sale/Purchase',
            self::OODD => 'One-Off Direct Debit',
            self::OPBC => 'Option broker owned collateral',
            self::OPCC => 'Option client owned collateral',
            self::OPCQ => 'Open Cheque',
            self::ORCQ => 'Order Cheque',
            self::OTCC => 'OTC CCP',
            self::OTCG => 'OTC',
            self::OTCN => 'OTC Non-CCP',
            self::OTHR => 'Other',
            self::OVCH => 'Overdraft Charge',
            self::OWNE => 'External Account Transfer',
            self::OWNI => 'Internal Account Transfer',
            self::PADD => 'Pre-Authorised Direct Debit',
            self::PAIR => 'Pair-Off',
            self::PCAL => 'Partial Redemption with reduction of nominal value',
            self::PLAC => 'Placement',
            self::PMDD => 'Direct Debit',
            self::PORT => 'Portfolio Move',
            self::POSC => 'Credit Card Payment',
            self::POSD => 'Point-of-Sale (POS) Payment  - Debit Card',
            self::POSP => 'Point-of-Sale (POS) Payment',
            self::PPAY => 'Principal Payment',
            self::PRCT => 'Priority Credit Transfer',
            self::PRDD => 'Reversal due to Payment Reversal',
            self::PRED => 'Partial Redemption Without Reduction of Nominal Value',
            self::PRII => 'Interest Payment with Principle',
            self::PRIN => 'Interest Payment with Principle',
            self::PRIO => 'Priority Issue',
            self::PRUD => 'Principal Pay-down/pay-up',
            self::PSTE => 'Posting Error',
            self::RCDD => 'Reversal due to a Payment Cancellation Request',
            self::REAA => 'Redemption Asset Allocation',
            self::REDM => 'Redemption / Final Maturity',
            self::REPU => 'Repo',
            self::RESI => 'Futures Residual Amount',
            self::RHTS => 'Rights Issue/Subscription Rights/Rights Offer',
            self::RIMB => 'Reimbursements',
            self::RNEW => 'Renewal',
            self::RPCR => 'Reversal due to Payment Cancellation Request',
            self::RPMT => 'Repayment',
            self::RRTN => 'Reversal due to Payment Return/reimbursement of a Credit Transfer',
            self::RVPO => 'Reverse Repo',
            self::RWPL => 'Redemption Withdrawing Plan',
            self::SABG => 'Settlement against bank guarantee',
            self::SALA => 'Payroll/Salary Payment',
            self::SDVA => 'Same Day Value Credit Transfer',
            self::SECB => 'Securities Borrowing',
            self::SECL => 'Securities Lending',
            self::SHPR => 'Equity Premium Reserve',
            self::SLBC => 'Lending Broker Owned Cash Collateral',
            self::SLCC => 'Lending Client Owned Cash Collateral',
            self::SMCD => 'Smart-Card Payment',
            self::SMRT => 'Smart-Card Payment',
            self::SOSE => 'Settlement of Sight Export document',
            self::SOSI => 'Settlement of Sight Import document',
            self::SSPL => 'Subscription Savings Plan',
            self::STAC => 'Settlement after collection',
            self::STAM => 'Settlement at Maturity / Stamp duty',
            self::STDO => 'Standing Order',
            self::STLM => 'Settlement',
            self::STLR => 'Settlement under reserve',
            self::SUAA => 'Subscription Asset Allocation',
            self::SUBS => 'Subscription',
            self::SWAP => 'Swap Payment',
            self::SWBC => 'Swap broker owned collateral',
            self::SWCC => 'Client Owned Collateral',
            self::SWEP => 'Sweeping',
            self::SWFP => 'Final Payment',
            self::SWIC => 'Switch',
            self::SWPP => 'Partial Payment',
            self::SWRS => 'Reset Payment',
            self::SWUF => 'Upfront Payment',
            self::SYND => 'Syndicated',
            self::TAXE => 'Taxes',
            self::TBAC => 'TBA closing',
            self::TCDP => 'Travellers Cheques Deposit',
            self::TCWD => 'Travellers Cheques Withdrawal',
            self::TEND => 'Tender',
            self::TOPG => 'Topping',
            self::TOUT => 'Transfer Out',
            self::TRAD => 'Trade',
            self::TREC => 'Tax Reclaim',
            self::TRFE => 'Transaction Fees',
            self::TRIN => 'Transfer In',
            self::TRPO => 'Triparty Repo',
            self::TRVO => 'Triparty Reverse Repo',
            self::TTLS => 'Treasury Tax And Loan Service',
            self::TURN => 'Turnaround',
            self::UDFT => 'Dishonoured/Unpaid Draft',
            self::UNCO => 'Underwriting Commission',
            self::UPCQ => 'Unpaid Cheque',
            self::UPCT => 'Unpaid Card Transaction',
            self::UPDD => 'Reversal due to Return/Unpaid Direct Debit',
            self::URCQ => 'Cheque Under Reserve',
            self::URDD => 'Direct Debit under reserve',
            self::VALD => 'Value Date',
            self::VCOM => 'Credit Transfer with agreed Commercial Information',
            self::WITH => 'Withholding Tax',
            self::XBCP => 'Cross-Border Credit Card Payment',
            self::XBCQ => 'Foreign Cheque',
            self::XBCT => 'Cross-Border Credit Transfer',
            self::XBCW => 'Cross-Border Cash Withdrawal',
            self::XBDD => 'Cross-Border Direct Debit',
            self::XBRD => 'Cross-Border',
            self::XBSA => 'Cross-Border Payroll/Salary Payment',
            self::XBST => 'Cross-Border Standing Order',
            self::XCHC => 'Exchange Traded CCP',
            self::XCHG => 'Exchange Traded',
            self::XCHN => 'Exchange Traded Non-CCP',
            self::XICT => 'Cross-Border Intra Company Transfer',
            self::XPCQ => 'Unpaid Foreign Cheque',
            self::XRCQ => 'Foreign Cheque Under Reserve',
            self::YTDA => 'YTD Adjustment',
            self::ZABA => 'Zero Balancing',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ACCC => 'Transaction is related to the closing of the account',
            self::ACCO => 'Transaction is related to the opening of the account',
            self::ACCT => 'Transaction is related to the transfer of the account within the same institution (resulting in a change of the account number)',
            self::ACDT => 'Transaction is an electronic credit payment that is processed through an ACH.',
            self::ACON => 'Transfer is an ACH Concentration transaction, i.e. movement of funds from own smaller depository accounts at other financial institutions to a concentration account',
            self::ACOR => 'Transfer is an ACH Corporate Trade transaction.',
            self::ADBT => 'Transaction is an electronic debit payment that is processed through an ACH.',
            self::ADJT => 'Generic credit or debit adjustments related to the transaction without further details available or transaction relates to corrections on the account that result in a debit / credit on the account,...',
            self::APAC => 'Transfer is an ACH Pre-Authorised transaction',
            self::ARET => 'Transfer is an ACH Return transaction, processed through an ACH.',
            self::AREV => 'Transaction is related to a reversal of an initial credit transfer, following pre-established rules (eg in ACH environment).',
            self::ARPD => 'Transaction is an account reconciliation package transaction that allows the account consolidation and enhances the management of paper cheques.',
            self::ASET => 'Transfer is an ACH Settlement transaction. Likely used as a single transaction that is the total of a batch of ACH Debits.',
            self::ATXN => 'Transaction is an electronic payment that is processed through an ACH (generic ACH transfer).',
            self::AUTT => 'Transaction is an individual automatic transfer transaction executed under agreed conditions.',
            self::BACT => 'Transaction is a cash concentration transfer between two financial institution branches belonging to the same group.',
            self::BBDD => 'Transaction is SEPA direct debit payment, as defined in the B2B Direct Debit Rulebook.',
            self::BCDP => 'Transaction is a counter or safe cash deposit operation, related to coin and currency deposit, operated at branch location.',
            self::BCHQ => 'Transaction is related to a cheque drawn on the account of the debtor’s financial institution, which is debited on the debtor’s account when the cheque is issued. These cheques are printed by the d...',
            self::BCKV => 'Transaction related to adjustments required on the Back Value of the transaction.',
            self::BCWD => 'Transaction is a counter cash withdrawal operation, related to coin and currency withdrawal, operated at branch location.',
            self::BIDS => 'Offer to existing shareholders by the issuing company to repurchase equity or other securities convertible into equity. The objective of the offer is to reduce the number of outstanding equities.',
            self::BKFE => 'Charges that a bank applies to an account for custody services provided.',
            self::BONU => 'Bonus, scrip or capitalisation issue. Security holders receive additional assets free of payment from the issuer, in proportion to their holding.',
            self::BOOK => 'Transaction is a transfer between –two different accounts within the same bank.',
            self::BPUT => 'Early redemption of a security at the election of the holder subject to the terms and condition of the issue.',
            self::BROK => 'Fee paid to a broker for services provided.',
            self::BSBC => 'Cash movement related to the opening or closing of a sell-buy back transaction ie a transaction which consist of a simultaneous matching sale and purchase of the same quantity of the same securitie...',
            self::BSBO => 'Cash movement related to the opening or closing of a buy-sell back transaction ie a transaction which consist of a simultaneous matching purchase and sale of the same quantity of the same securitie...',
            self::CAJT => 'Token from Sub-Familydefinition Adjustment: Generic creditor debit adjustment srelated to the transaction without further details available',
            self::CAPG => 'Distribution of profits resulting from the sale of company assets eg Shareholders of Mutual Funds, Unit Trusts, or Sicavs are recipients of capital gains distributions which are often reinvested in...',
            self::CASH => 'Transaction is related to a cash letter.',
            self::CCCH => 'Transaction is related to a cheque drawn on the account of the debtor, and debited on the debtor’s account when the cheque is cashed. The financial institution prints and certifies the cheque, guar...',
            self::CCHQ => 'Transaction is related to a cheque drawn on the account of the debtor, and debited on the debtor’s account when the cheque is cashed. Settlement of the cheque has been completed.',
            self::CDIS => 'Transaction is related to a service that provides for movement of funds associated with cheque presentation. This is the presentation leg of the transaction.',
            self::CDPT => 'Transaction is an ATM deposit operation or transaction is a counter or safe cash deposit operation, related to coin and currency deposit.',
            self::CHAR => 'Overall charge paid for an account. May or may not be split up into detailed charges.',
            self::CHKD => 'Transaction is a counter or safe cash deposit operation, related to cheque deposit',
            self::CHRG => 'Generic charges related to the transaction without further details available',
            self::CLAI => 'Cash movement related to the payment of a claim or compensation.',
            self::CLCQ => 'Transaction is related to an instruction from a bank to its correspondent bank to pay the creditor a stated sum upon the presentation of a means of identification.',
            self::CMBO => 'Cash movement related to corporate mark broker owned collateral',
            self::CMCO => 'Cash movement related to corporate mark client owned collateral',
            self::COAT => 'Transaction is a cash concentration transfer between own accounts, i.e., a transfer between 2 different accounts of the same company within the same bank. Difference between sub accounts and main a...',
            self::COME => 'Generic commissions without taxes related to the transaction without further details available',
            self::COMI => 'Generic commissions including taxes related to the transaction without further details available',
            self::COMM => 'Generic commissions without further details related to the transaction',
            self::COMT => 'Generic non-taxable commissions related to the transaction without further details available',
            self::CONV => 'Conversion of securities (generally convertible bonds or preferred shares) into another form of securities (usually common shares) at a pre-stated price/ratio.',
            self::CPRB => 'Cash movement related to a corporate rebate',
            self::CQRV => 'Transaction is related to a reversal of a cheque payment.',
            self::CRCQ => 'Transaction is related to a cheque that must be paid into an account and not cashed over the counter. There are two parallel lines across the face of a crossed cheque.',
            self::CROS => 'Cash movement related to an investment funds cross in or out transaction',
            self::CSHA => 'Transaction is related to an adjustment of a cash letter payment.',
            self::CSLI => 'Cash paid in lieu of something else.',
            self::CWDL => 'Transaction is an ATM withdrawal operation or transaction is a counter cash withdrawal operation, related to coin and currency withdrawal',
            self::DAJT => 'Token from Sub-Familydefinition Adjustment: Generic creditor debit adjustment srelated to the transaction without further details available',
            self::DDFT => 'Transaction is related to a discounted draft, i.e. the beneficiary has received an early payment from any bank under subtraction of a discount.',
            self::DDWN => 'Transaction is related to drawdown of fixed term / notice / mortgage / consumer loans or syndications contracts.',
            self::DECR => 'Reduction of face value of a single share. The number of circulating shares remains unchanged. This event may include a cash payout to holders.',
            self::DMCG => 'Transaction is related to the change of the maturity date of a draft.',
            self::DMCT => 'Transaction is a  in-country domestic currency credit transfer',
            self::DPST => 'Transaction is related to opening of the fixed term / notice deposits contract.',
            self::DRAW => 'Redemption in part before the scheduled final maturity date of a security. Drawing is distinct from partial call since drawn bonds are chosen by lottery and results are confirmed to bondholder.',
            self::DRIP => 'Dividend payment where holders can keep cash or have the cash reinvested in the market by the issuer into additional shares in the issuing company. To be distinguished from DVOP as the company inve...',
            self::DSBR => 'Transaction is related to a service that provides for movement of funds associated with cheque presentation. This is the presentation leg of the transaction.',
            self::DTCH => 'Action by a party wishing to acquire a security. Holders of the security are invited to make an offer to sell, within a specific price range. The acquiring party will buy from the holder with lowes...',
            self::DVCA => 'Distribution of cash to shareholders, in proportion to their equity holding. Ordinary dividends are recurring and regular.  Shareholder must take cash and may be offered a choice of currency.',
            self::DVOP => 'Distribution of a dividend to shareholders with a choice of benefit to receive. Shareholders may choose to receive shares or cash. To be distinguished from DRIP as the company creates new share cap...',
            self::EQBO => 'Cash movement related to equity mark broker owned collateral',
            self::EQCO => 'Cash movement related to equity mark client owned collateral',
            self::ERTA => 'Transaction relates to corrections on the account that result in a debit / credit on the account through exchange rates adjustments.',
            self::ERWA => 'Income received from lending activity',
            self::ERWI => 'Fee paid for borrowing activity.',
            self::ESCT => 'Transaction is a SEPA credit transfer',
            self::ESDD => 'Transaction is SEPA core direct debit payment.',
            self::EXOF => 'Exchange of holdings for other securities and/or cash.  The exchange can be either mandatory or voluntary involving the exchange of outstanding securities for different securities and/or cash.  For...',
            self::EXRI => 'Call or exercise on nil-paid securities or intermediate securities resulting from an intermediate securities distribution. This code is used for the second event, when an intermediate securities’ i...',
            self::EXWA => 'Option to buy (call warrant) or to sell (put warrant) a specific amount of equities, cash, commodity, etc, at a predetermined price over a specific period of time.',
            self::FCDP => 'Transaction is a movement resulting from foreign currency sell operations (bank notes and coins) at the counter.',
            self::FCTA => 'Cash movement related to a factor update transaction on a purchase or sale of factored securities.',
            self::FCWD => 'Transaction is a movement resulting from foreign currency buy operations (bank notes and coins) at the counter.',
            self::FEES => 'Generic fees related to the transaction without further details available',
            self::FICT => 'Transaction is a financial institution credit transfer, i.e. the debtor and creditor are financial institutions.',
            self::FIDD => 'Transaction is a financial institution direct debit payment.',
            self::FIOA => 'Transaction is a cash concentration transfer between financial institution’s own accounts, i.e., a transfer between two different accounts of the financial institution customer within one financial...',
            self::FLTA => 'Transaction relates to corrections on the account that result in a debit / credit on the account through float adjustments.',
            self::FRZF => 'Transaction is related to the freeze of funds under Import Stand-by letter of credit or documentary credit.',
            self::FUCO => 'A fee charged for executing futures transactions.',
            self::FUTU => 'Transaction is for the payment of futures variation margin/s.',
            self::FWBC => 'Cash movement related to forwards broker owned collateral',
            self::FWCC => 'Cash movement related to forwards client owned collateral',
            self::GEN1 => 'Cash movement related to the withdrawal/distribution of cash out of an account.',
            self::GEN2 => 'Cash movement related to the deposit/contribution of cash into an account.',
            self::IADD => 'Service allowing the debtor bank to inform directly and in advance the provider of its customer about the elements of the invoices the customer of the bank will settle on due date. Action is relate...',
            self::ICCT => 'Transaction is an intra-company cash concentration transfer, i.e., a payment between two different legal entities belonging to the same group.',
            self::INFD => 'Interest payment distributed to holders of a deposit with a fixed term.',
            self::INSP => 'Cash movement related to a move of stock into or out of a pooled account.',
            self::INTR => 'Generic interest related to the transaction without further details available or regular interest payment distributed to holders of an interest bearing asset.',
            self::ISSU => 'Cash movement related to a depositary receipt issue operation.',
            self::LBCA => 'Transaction is related to a lockbox credit adjustment.',
            self::LBDB => 'Transaction is related to a lockbox debit.',
            self::LBDP => 'Transaction is related to a lockbox deposit',
            self::LIQU => 'Distribution of cash, assets, or both.  Debt may be paid in order of priority based on preferred claims to assets specified by the security.',
            self::MARG => 'Cash collateral movement to meet the minimum amount of financial instruments that must be maintained in a margin account after an investor has bought securities on margin.',
            self::MCAL => 'Redemption of an entire issue outstanding of bonds/preferred equity by the issuer before final maturity.',
            self::MGCC => 'Cash collateral movement related to margin client owned cash collateral',
            self::MIXD => 'Transaction is a counter cash  deposit operation, related to a combination of cheque, coin and currency deposit',
            self::MNFE => 'Charges that an investment manager applies to an account for services provided.',
            self::MRGR => 'Exchange of outstanding securities, initiated by the issuer which may include options, as the result of two or more companies combining assets ie an external, third party company. Cash payments may...',
            self::MSCD => 'Transaction is a counter deposit related to undefined underlying instruments',
            self::NETT => 'Cash movement related to the netting of securities trades.',
            self::NPCC => 'Transaction is related to a non-presented circular cheque.',
            self::NSYN => 'Cash movement related to the issue of a medium and short term paper (CP, CD, MTN, notes etc) under a program and without syndication arrangement.',
            self::NTAV => 'The “Not Available” Sub-Family is used to cater for the Bank Transaction Code mandatory field, when no further details are available for the Bank Transaction Code, e.g. a received credit transfer i...',
            self::ODFT => 'Transaction relates to a cash management instruction that requesting the processing of overdraft conditions',
            self::ODLT => 'Sale or purchase of odd-lots to/from the issuing company, initiated either by the holder of the security or through an offer made by the issuer.',
            self::OODD => 'Transaction is a one-off direct debit payment.',
            self::OPBC => 'Cash collateral movement related to option broker owned collateral',
            self::OPCC => 'Cash movement related to option client owned collateral',
            self::OPCQ => 'Transaction is related to a cheque that may only be cashed at the bank of origin.',
            self::ORCQ => 'Transaction is related to a cheque made payable to a named recipient ‘or order’. The payee can either deposit the cheque in an account or endorse it to a third party.',
            self::OTCC => 'Represents the cash legs of transactions ‘over the counter’ (OTC), going through CCP functions',
            self::OTCG => 'Represents the cash legs of transactions ‘over the counter’ (OTC) exchanges – this code is only to be used where the service-provider does not distinguish between transactions going through CCP and...',
            self::OTCN => 'Represents the cash legs of transactions ‘over the counter’ (OTC), not going through CCP functions',
            self::OTHR => 'The “Other” Sub-Family is used to cater for the Bank Transaction Code mandatory field, when the reported Family does not match any of the Families listed in the specified Domain, but further detail...',
            self::OVCH => 'Fees charged to an account when the cash is overdrawn.',
            self::OWNE => 'Cash movement related to an external securities account transfer ie a transfer involving more than one instructing party and/or account servicer.',
            self::OWNI => 'Cash movement related to an internal securities account transfer ie a transfer involving one instructing party at one account servicer.',
            self::PADD => 'Transaction is a Pre-Authorised Direct Debit payment, e.g. the ACH pre-authorised Direct Debit.',
            self::PAIR => 'Clean cash movement related to a pair-off transaction, ie a buyback to offset and effectively liquidate a prior sale of securities or a sellback to offset and effectively liquidate a prior buy of s...',
            self::PCAL => 'Securities are redeemed in part before their scheduled final maturity date with reduction of the nominal value of the shares. The outstanding amount of securities will be reduced proportionally.',
            self::PLAC => 'Cash movement related to a placement/new issue.',
            self::PMDD => 'Transaction is a legacy direct debit payment, which is related to a recurring payment. The settlement of the direct debit transaction has already been completed successfully.',
            self::PORT => 'Cash movement related to a portfolio move from one investment manager to another and/or from an account servicer to another.',
            self::POSC => 'Transaction is a payment done through a credit card that permits the credit cardholders to electronically make a payment at the place of purchase.',
            self::POSD => 'Transaction is a payment done through an electronic network of banks, debit cardholders, and merchants that permit consumers to electronically make direct payment at the place of purchase. The fund...',
            self::POSP => 'Transaction is a payment done through an electronic network of banks, debit cardholders, and merchants that permit consumers to electronically make direct payment at the place of purchase. The fund...',
            self::PPAY => 'Transaction is related to the payment of the principal of fixed term / notice / mortgage / consumer loans or syndications contracts.',
            self::PRCT => 'Transaction is a credit transfer defined with higher priority, eg a PRIEURO credit transfer',
            self::PRDD => 'Transaction is related to the reversal / reimbursement of a direct debit transaction (which may be related to a double processing, the debit of an incorrect account, or request to reimburse the deb...',
            self::PRED => 'Securities are redeemed in part before their scheduled final maturity date without reduction of the nominal value of the shares. This is commonly done by pool factor reduction.',
            self::PRII => 'Payment of a portion of the principal of an interest bearing asset, in addition to the interest payment.',
            self::PRIN => 'Payment of a portion of the principal of an interest bearing asset, in addition to the interest payment.',
            self::PRIO => 'Form of open or public offer where, due to a limited amount of securities available, priority is given to existing shareholders.',
            self::PRUD => 'Partial payment or receipt of principal on factored securities.',
            self::PSTE => 'Translation relates to the correction of a posting error.',
            self::RCDD => 'Transaction is related to the cancellation of an initial direct debit upon request from the creditor. The creditor had already been credited for the initial direct debit, but interbank settlement h...',
            self::REAA => 'Cash movement related to a redemption in an asset allocation plan which enables investors to withdraw, by percentage a certain amount of cash from several sub-funds of a same umbrella structure.',
            self::REDM => 'Cash movement related to the redemption of an investment fund or Redemption of an entire issue outstanding of bonds/preferred equities by the issuer at final maturity.',
            self::REPU => 'Cash collateral marks related to repo or cash movement related to the initiation or closing of a repo transaction in which a seller acquires cash by selling securities (used as collateral) and simu...',
            self::RESI => 'Transaction related to a futures residual amount.',
            self::RHTS => 'Distribution of a security or privilege that gives the holder an entitlement or right to take part in a future event.',
            self::RIMB => 'Generic reimbursement of costs related to the transaction without further details available',
            self::RNEW => 'Transaction is related to renewal of fixed term / notice / mortgage / consumer loans or syndications contracts.',
            self::RPCR => 'Transaction is related to the cancellation of an initial credit transfer upon request from the debtor. The debtor had already been debited for the initial credit transfer, but interbank settlement ...',
            self::RPMT => 'Transaction is related to repayment of the fixed term / notice deposits.',
            self::RRTN => 'Transaction is related to the return/reimbursement of a credit transfer transaction (which may be related to a double processing, the debit of an incorrect account, or return of the credit transfer)',
            self::RVPO => 'Cash movement related to the initiation or closing of a reverse repo transaction in which a buyer lends cash by buying securities (used as collateral) and simultaneously agrees to sell back the sam...',
            self::RWPL => 'Cash movement related to a withdrawal by individuals in the framework of a structured plan for investments made in the past.',
            self::SABG => 'Transaction is related to the settlement of the Letter of Credit (Stand-By or Documentary) against a bank guarantee.',
            self::SALA => 'Transaction is related to the payment of a payroll salary',
            self::SDVA => 'Transfer is a credit transfer whereby the payment was executed with same day value to the beneficiary.',
            self::SECB => 'Cash movement related to the initiation or closing of a securities borrowing transaction or cash collateral marks related to securities borrowing activity.',
            self::SECL => 'Cash movement related to the initiation or closing of a securities lending transaction or cash collateral marks related to securities lending activity.',
            self::SHPR => 'Shareholders receive an amount in cash issued from the equity premium reserve. This event is similar to a dividend but has different tax implications.',
            self::SLBC => 'Cash movement related to lending broker owned collateral',
            self::SLCC => 'Cash movement related to lending client owned collateral',
            self::SMCD => 'Transaction is a card-based payment. For the merchant, the transaction related to transfer of the funds related to the settlement of the recorded transaction paid through smart-cards.',
            self::SMRT => 'Transaction is a card-based payment. The smart-card is a system that stores values for transactions on a computer chip located on the card itself. As the card is used for transactions, the amounts ...',
            self::SOSE => 'Transaction is related to the settlement upon presentation of the Export Letter of credit (Stand-By or Documentary)',
            self::SOSI => 'Transaction is related to the settlement upon presentation of the Import Letter of credit (Stand-By or Documentary)',
            self::SSPL => 'Cash movement related to a subscription for a savings plan, i.e. money set aside by individuals in the framework of a structured plan for a special purpose eg retirement.',
            self::STAC => 'Transaction is related to a settlement after collection',
            self::STAM => 'Transaction is related to a draft or bill to order which has been paid on maturity date or stamp duty',
            self::STDO => 'Transaction is a standing order. A standing order is an instruction given by a party having explicit authority on the debtor’s account to debit, i.e. either debit account owner or originating party...',
            self::STLM => 'Transaction relates to the settlement of a guarantee.',
            self::STLR => 'Transaction is related to a settlement under reserve of the draft or transaction is related to a settlement under reserve of collection or transaction is related to a settlement under reserve of fu...',
            self::SUAA => 'Cash movement related to an asset allocation plan that enables investors to allocate, by percentage a certain amount of cash into several sub-funds of a same umbrella structure.',
            self::SUBS => 'Cash movement related to the subscription to an investment fund.',
            self::SWAP => 'Transaction is a swap related payment.',
            self::SWBC => 'Cash movement related to swap broker owned collateral',
            self::SWCC => 'Transaction is a swap client owned collateral',
            self::SWEP => 'Cash movement related to a sweep eg an end of day short term investment vehicle or transaction relates to a cash management instruction, requesting a sweep of the account above an agreed floor amou...',
            self::SWFP => 'Transaction is a swap related final payment',
            self::SWIC => 'Cash movement related to a change between investment funds (usually of the same family) with cash in/out, at more interesting conditions than a separate redemption or a separate subscription.',
            self::SWPP => 'Transaction is a swap related partial payment',
            self::SWRS => 'Transaction is a swap related reset payment',
            self::SWUF => 'Transaction is a swap related upfront payment',
            self::SYND => 'Cash movement related to the issue of securities (bonds, warrants, equities etc) through a syndicate of underwriters and a Lead Manager.',
            self::TAXE => 'Generic taxes related to the transaction without further details available',
            self::TBAC => 'Cash movement related to a TBA (To Be Announced) closing transaction.',
            self::TCDP => 'Transaction is a movement resulting from a travellers’ cheques deposit by the account owner at the counter.',
            self::TCWD => 'Transaction is a movement resulting from a travellers’ cheques withdrawal by the account owner at the counter.',
            self::TEND => 'Cash movement related to an offer made to shareholders, normally by a third party, requesting them to sell (tender) or exchange their equities.',
            self::TOPG => 'Transaction is a cash management instruction, requesting to top the account above a certain floor amount. The floor amount, if not pre-agreed by the parties involved, may be specified.',
            self::TOUT => 'Cash movement related to a debit to an account on the shareholders register, and is not linked to a shift in investment (redemption or switch), but to account management.',
            self::TRAD => 'Cash movement related to a securities purchase or sale.',
            self::TREC => 'Event related to tax reclaim activities.',
            self::TRFE => 'Fees associated with security settlement activity.',
            self::TRIN => 'Cash movement related to an incoming credit to an account on the shareholders register, and is not linked to a shift in investment (subscription or switch), but to account management.',
            self::TRPO => 'Cash movement related to the initiation or closing of a triparty repo transaction or cash collateral marks related to triparty repo.',
            self::TRVO => 'Cash movement related to the initiation or closing of a triparty reverse repo transaction',
            self::TTLS => 'Transaction is related to a Treasury Tax and Loan Service, i.e. a service offered by the Federal Reserve Banks of the United States that keeps tax receipts in the banking sector by depositing them ...',
            self::TURN => 'Cash movement related to a turnaround transaction, the simultaneous purchase and sell of the same quantity of financial instruments on the same day.',
            self::UDFT => 'Transaction is related to a Dishonoured / Unpaid Draft or Bill To Order. The beneficiary has received early payment, but the ordering customer’s account could not be debited on value date.',
            self::UNCO => 'Fee investment bankers charge for underwriting a security issue.',
            self::UPCQ => 'Transaction is related to a cheque for which the settlement could not be completed.',
            self::UPCT => 'Transaction is related to the return of a debit/credit payment that has not been settled or has been rejected by the card holder for a justified reason.',
            self::UPDD => 'Transaction is related to a Returned Direct Debit. Several reasons may exist: debtor’s account closed, insufficient funds available, request for refund by the Debtor, etc.',
            self::URCQ => 'Transaction is related to a cheque booked before settlement of the funds has taken place.',
            self::URDD => 'Transaction is a legacy direct debit payment under reserve of settlement. Although the amount has been already been posted, the funds have not yet been settled.',
            self::VALD => 'Transaction relates to adjustments required on the value date of the transaction and/or the balance, those adjustments will be reported as value date adjustments',
            self::VCOM => 'Transaction is a credit transfer including commercial information, i.e. additional information agreed between the sender and the receiver.',
            self::WITH => 'Tax levied by a country of source on income paid, usually on dividends remitted to the home country of the firm operating in a foreign country.',
            self::XBCP => 'Transaction is a payment done through a credit card in a foreign country.',
            self::XBCQ => 'Transaction is related to a cheque drawn on the account of the debtor, and cashed in a different country than the country of the debtor’s bank.',
            self::XBCT => 'Transaction is a cross-border credit transfer',
            self::XBCW => 'Transaction is an ATM cash withdrawal operation in a foreign country.',
            self::XBDD => 'Transaction is a cross-border direct debit payment.',
            self::XBRD => 'Transaction is related to a cash management activity that is cross-border cash pooling or account balancing operation.',
            self::XBSA => 'Transaction is related to the payment of a cross-border payroll salary',
            self::XBST => 'Transaction is a cross-border standing order',
            self::XCHC => 'Representing cash legs of transactions in exchanges, going through CCP functions',
            self::XCHG => 'Representing cash legs of transactions traded in exchanges – this code is only to be used where the service-provider does not distinguish between transactions going through CCP and other transactions',
            self::XCHN => 'Represents cash legs of transactions traded in exchanges, not going through CCP functions',
            self::XICT => 'Transaction is a cross-border intra-company cash concentration transfer.',
            self::XPCQ => 'Transaction is related to a foreign cheque for which the settlement could not be completed.',
            self::XRCQ => 'Transaction is related to a foreign cheque, booked before settlement of the funds has taken place.',
            self::YTDA => 'Transaction relates to corrections on the account that result in a debit / credit on the account through year-to-date adjustments.',
            self::ZABA => 'Transaction is a cash management instruction, requesting to zero balance the account. Zero Balance Accounts empty or fill the balances in accounts at the same bank, in the same country into or out ...',
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
