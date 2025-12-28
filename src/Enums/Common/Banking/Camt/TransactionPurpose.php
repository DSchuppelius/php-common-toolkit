<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TransactionPurpose.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: ISO_ExternalPurpose1Code
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * TransactionPurpose - ISO 20022 External Code List
 * 
 * Generiert aus: ISO_ExternalPurpose1Code
 * @see https://www.iso20022.org/external_code_list.page
 */
enum TransactionPurpose: string {
    /**
     * ACCT - AccountManagement
     * Transaction moves funds between 2 accounts of same account holder at the same bank.
     */
    case ACCT = 'ACCT';

    /**
     * ADCS - AdvisoryDonationCopyrightServices
     * Payments for donation, sponsorship, advisory, intellectual and other copyright services.
     */
    case ADCS = 'ADCS';

    /**
     * ADMG - AdministrativeManagement
     * Transaction is related to a payment associated with administrative management.
     */
    case ADMG = 'ADMG';

    /**
     * ADVA - AdvancePayment
     * Transaction is an advance payment.
     */
    case ADVA = 'ADVA';

    /**
     * AEMP - ActiveEmploymentPolicy
     * Payment concerning active employment policy.
     */
    case AEMP = 'AEMP';

    /**
     * AGRT - AgriculturalTransfer
     * Transaction is related to the agricultural domain.
     */
    case AGRT = 'AGRT';

    /**
     * AIRB - Air
     * Transaction is a payment for air transport related business.
     */
    case AIRB = 'AIRB';

    /**
     * ALLW - Allowance
     * Transaction is the payment of allowances.
     */
    case ALLW = 'ALLW';

    /**
     * ALMY - AlimonyPayment
     * Transaction is the payment of alimony.
     */
    case ALMY = 'ALMY';

    /**
     * ANNI - Annuity
     * Transaction settles annuity related to credit, insurance, investments, other.n
     */
    case ANNI = 'ANNI';

    /**
     * ANTS - AnesthesiaServices
     * Transaction is a payment for anesthesia services.
     */
    case ANTS = 'ANTS';

    /**
     * AREN - Accounts Receivables Entry
     * Transaction is related to a payment associated with an Account Receivable Entry
     */
    case AREN = 'AREN';

    /**
     * B112 - Trailer Fee Payment
     * US mutual fund trailer fee (12b-1) payment
     */
    case B112 = 'B112';

    /**
     * BBSC - Baby Bonus Scheme
     * Transaction is related to a payment made as incentive to encourage parents to have more children
     */
    case BBSC = 'BBSC';

    /**
     * BCDM - BearerChequeDomestic
     * Transaction is the payment of a domestic bearer cheque.
     */
    case BCDM = 'BCDM';

    /**
     * BCFG - BearerChequeForeign
     * Transaction is the payment of a foreign bearer cheque.
     */
    case BCFG = 'BCFG';

    /**
     * BECH - ChildBenefit
     * Transaction is related to a payment made to assist parent/guardian to maintain child.
     */
    case BECH = 'BECH';

    /**
     * BENE - UnemploymentDisabilityBenefit
     * Transaction is related to a payment to a person who is unemployed/disabled.
     */
    case BENE = 'BENE';

    /**
     * BEXP - BusinessExpenses
     * Transaction is related to a payment of business expenses.
     */
    case BEXP = 'BEXP';

    /**
     * BFWD - Bond Forward
     * Cash collateral related to any securities traded out beyond 3 days which include treasury notes, ...
     */
    case BFWD = 'BFWD';

    /**
     * BKDF - Bank Loan Delayed Draw Funding
     * Delayed draw funding. Certain issuers may utilize delayed draw loans whereby the lender is commit...
     */
    case BKDF = 'BKDF';

    /**
     * BKFE - Bank Loan Fees
     * Bank loan fees. Cash activity related to specific bank loan fees, including (a) agent / assignmen...
     */
    case BKFE = 'BKFE';

    /**
     * BKFM - Bank Loan Funding Memo
     * Bank loan funding memo. Net cash movement for the loan contract final notification when sent sepa...
     */
    case BKFM = 'BKFM';

    /**
     * BKIP - Bank Loan Accrued Interest Payment
     * Accrued interest payments. Specific to bank loans.
     */
    case BKIP = 'BKIP';

    /**
     * BKPP - Bank Loan Principal Paydown
     * Principal paydowns. Specific to bank loans
     */
    case BKPP = 'BKPP';

    /**
     * BLDM - BuildingMaintenance
     * Transaction is related to a payment associated with building maintenance.
     */
    case BLDM = 'BLDM';

    /**
     * BNET - Bond Forward Netting
     * Bond Forward pair-off cash net movement
     */
    case BNET = 'BNET';

    /**
     * BOCE - Back Office Conversion Entry
     * Transaction is related to a payment associated with a Back Office Conversion Entry
     */
    case BOCE = 'BOCE';

    /**
     * BONU - BonusPayment.
     * Transaction is related to payment of a bonus.
     */
    case BONU = 'BONU';

    /**
     * BR12 - Trailer Fee Rebate
     * US mutual fund trailer fee (12b-1) rebate payment
     */
    case BR12 = 'BR12';

    /**
     * BUSB - Bus
     * Transaction is a payment for bus transport related business.
     */
    case BUSB = 'BUSB';

    /**
     * CAFI - Custodian Management fee In-house
     * Transaction is the payment of custodian account management fee where custodian bank and current a...
     */
    case CAFI = 'CAFI';

    /**
     * CASH - CashManagementTransfer
     * Transaction is a general cash management instruction.
     */
    case CASH = 'CASH';

    /**
     * CBFF - CapitalBuilding
     * Transaction is related to capital building fringe fortune, ie capital building in general
     */
    case CBFF = 'CBFF';

    /**
     * CBFR - CapitalBuildingRetirement
     * Transaction is related to capital building fringe fortune for retirement
     */
    case CBFR = 'CBFR';

    /**
     * CBLK - Card Bulk Clearing
     * A Service that is settling money for a bulk of card transactions, while referring to a specific t...
     */
    case CBLK = 'CBLK';

    /**
     * CBTV - CableTVBill
     * Transaction is related to a payment of cable TV bill.
     */
    case CBTV = 'CBTV';

    /**
     * CCHD - Cash compensation, Helplessness, Disability
     * Payments made by Government institute related to cash compensation, helplessness, disability. The...
     */
    case CCHD = 'CCHD';

    /**
     * CCIR - Cross Currency IRS
     * Cash Collateral related to a Cross Currency Interest Rate Swap, indicating the exchange of fixed ...
     */
    case CCIR = 'CCIR';

    /**
     * CCPC - CCP Cleared Initial Margin
     * Cash Collateral associated with an ISDA or Central Clearing Agreement that is covering the initia...
     */
    case CCPC = 'CCPC';

    /**
     * CCPM - CCP Cleared Variation Margin
     * Cash Collateral associated with an ISDA or Central Clearing Agreement that is covering the variat...
     */
    case CCPM = 'CCPM';

    /**
     * CCRD - CreditCardPayment
     * Transaction is related to a payment of credit card account.
     */
    case CCRD = 'CCRD';

    /**
     * CCSM - CCP Cleared Initial Margin Segregated Cash
     * CCP Segregated initial margin: Initial margin on OTC Derivatives cleared through a CCP that requi...
     */
    case CCSM = 'CCSM';

    /**
     * CDBL - CreditCardBill
     * Transaction is related to a payment of credit card bill.
     */
    case CDBL = 'CDBL';

    /**
     * CDCB - CardPayment with CashBack
     * Purchase of Goods and Services with additional Cash disbursement at the POI (Cashback)
     */
    case CDCB = 'CDCB';

    /**
     * CDCD - CashDisbursement
     * ATM Cash Withdrawal in an unattended or Cash Advance in an attended environment (POI or bank coun...
     */
    case CDCD = 'CDCD';

    /**
     * CDCS - Cash Disbursement with Surcharging
     * ATM Cash Withdrawal in an unattended or Cash Advance in an attended environment (POI or bank coun...
     */
    case CDCS = 'CDCS';

    /**
     * CDDP - Card Deferred Payment
     * A combined service which enables the card acceptor to perform an authorisation for a temporary am...
     */
    case CDDP = 'CDDP';

    /**
     * CDEP - Credit default event payment
     * Payment related to a credit default event
     */
    case CDEP = 'CDEP';

    /**
     * CDOC - OriginalCredit
     * A service which allows the card acceptor to effect a credit to a cardholder' account. Unlike a Me...
     */
    case CDOC = 'CDOC';

    /**
     * CDQC - QuasiCash
     * Purchase of Goods which are equivalent to cash like coupons in casinos.
     */
    case CDQC = 'CDQC';

    /**
     * CFDI - Capital falling due In-house
     * Transaction is the payment of capital falling due where custodian bank and current account servic...
     */
    case CFDI = 'CFDI';

    /**
     * CFEE - CancellationFee
     * Transaction is related to a payment of cancellation fee.
     */
    case CFEE = 'CFEE';

    /**
     * CGDD - CardGeneratedDirectDebit
     * Transaction is related to a direct debit where the mandate was generated by using data from a pay...
     */
    case CGDD = 'CGDD';

    /**
     * CHAR - CharityPayment
     * Transaction is a payment for charity reasons.
     */
    case CHAR = 'CHAR';

    /**
     * CLPR - CarLoanPrincipalRepayment
     * Transaction is a payment of car loan principal payment.
     */
    case CLPR = 'CLPR';

    /**
     * CMDT - CommodityTransfer
     * Transaction is payment of commodities.
     */
    case CMDT = 'CMDT';

    /**
     * COLL - CollectionPayment
     * Transaction is a collection of funds initiated via a credit transfer or direct debit.
     */
    case COLL = 'COLL';

    /**
     * COMC - CommercialPayment
     * Transaction is related to a payment of commercial credit or debit. (formerly CommercialCredit)
     */
    case COMC = 'COMC';

    /**
     * COMM - Commission
     * Transaction is payment of commission.
     */
    case COMM = 'COMM';

    /**
     * COMT - ConsumerThirdPartyConsolidatedPayment
     * Transaction is a payment used by a third party who can collect funds to pay on behalf of consumer...
     */
    case COMT = 'COMT';

    /**
     * CORT - Trade Settlement Payment
     * Transaction is related to settlement of a trade, e.g. a foreign exchange deal or a securities tra...
     */
    case CORT = 'CORT';

    /**
     * COST - Costs
     * Transaction is related to payment of costs.
     */
    case COST = 'COST';

    /**
     * CPKC - Carpark Charges
     * Transaction is related to carpark charges.
     */
    case CPKC = 'CPKC';

    /**
     * CPYR - Copyright
     * Transaction is payment of copyright.
     */
    case CPYR = 'CPYR';

    /**
     * CRDS - Credit DefaultSwap
     * Cash collateral related to trading of credit default swap.
     */
    case CRDS = 'CRDS';

    /**
     * CRPR - Cross Product
     * Cash collateral related to a combination of various types of trades.
     */
    case CRPR = 'CRPR';

    /**
     * CRSP - Credit Support
     * Cash collateral related to cash lending/borrowing; letter of Credit; signing of master agreement.
     */
    case CRSP = 'CRSP';

    /**
     * CRTL - Credit Line
     * Cash collateral related to opening of a credit line before trading.
     */
    case CRTL = 'CRTL';

    /**
     * CSDB - CashDisbursement
     * Transaction is related to cash disbursement.
     */
    case CSDB = 'CSDB';

    /**
     * CSLP - CompanySocialLoanPaymentToBank
     * Transaction is a payment by a company to a bank for financing social loans to employees.
     */
    case CSLP = 'CSLP';

    /**
     * CVCF - ConvalescentCareFacility
     * Transaction is a payment for convalescence care facility services.
     */
    case CVCF = 'CVCF';

    /**
     * DBTC - DebitCollectionPayment
     * Collection of funds initiated via a debit transfer.
     */
    case DBTC = 'DBTC';

    /**
     * DCRD - Debit Card Payment
     * Transaction is related to a debit card payment.
     */
    case DCRD = 'DCRD';

    /**
     * DEPT - Deposit
     * Transaction is releted to a payment of deposit.
     */
    case DEPT = 'DEPT';

    /**
     * DERI - Derivatives
     * Transaction is related to a derivatives transaction
     */
    case DERI = 'DERI';

    /**
     * DIVD - Dividend
     * Transaction is payment of dividends.
     */
    case DIVD = 'DIVD';

    /**
     * DMEQ - DurableMedicaleEquipment
     * Transaction is a payment is for use of durable medical equipment.
     */
    case DMEQ = 'DMEQ';

    /**
     * DNTS - DentalServices
     * Transaction is a payment for dental services.
     */
    case DNTS = 'DNTS';

    /**
     * DSMT - Printed Order Disbursement
     * Transaction is the payment of a disbursement due to a specific type of printed order for a paymen...
     */
    case DSMT = 'DSMT';

    /**
     * DVPM - Deliver Against Payment
     * Code used to pre-advise the account servicer of a forthcoming deliver against payment instruction.
     */
    case DVPM = 'DVPM';

    /**
     * ECPG - GuaranteedEPayment
     * E-Commerce payment with payment guarantee of the issuing bank.
     */
    case ECPG = 'ECPG';

    /**
     * ECPR - EPaymentReturn
     * E-Commerce payment return.
     */
    case ECPR = 'ECPR';

    /**
     * ECPU - NonGuaranteedEPayment
     * E-Commerce payment without payment guarantee of the issuing bank.
     */
    case ECPU = 'ECPU';

    /**
     * EDUC - Education
     * Transaction is related to a payment of study/tuition fees.
     */
    case EDUC = 'EDUC';

    /**
     * ELEC - ElectricityBill
     * Transaction is related to a payment of electricity bill.
     */
    case ELEC = 'ELEC';

    /**
     * ENRG - Energies
     * Transaction is related to a utility operation.
     */
    case ENRG = 'ENRG';

    /**
     * EPAY - Epayment
     * Transaction is related to ePayment.
     */
    case EPAY = 'EPAY';

    /**
     * EQPT - Equity Option
     * Cash collateral related to trading of equity option (Also known as stock options).
     */
    case EQPT = 'EQPT';

    /**
     * EQUS - Equity Swap
     * Cash collateral related to equity swap trades where the return of an equity is exchanged for eith...
     */
    case EQUS = 'EQUS';

    /**
     * ESTX - EstateTax
     * Transaction is related to a payment of estate tax.
     */
    case ESTX = 'ESTX';

    /**
     * ETUP - E-Purse Top Up
     * Transaction is related to a Service that is first reserving money from a card account and then is...
     */
    case ETUP = 'ETUP';

    /**
     * EXPT - Exotic Option
     * Cash collateral related to trading of an exotic option for example a non-standard option.
     */
    case EXPT = 'EXPT';

    /**
     * EXTD - Exchange Traded Derivatives
     * Cash collateral related to trading of exchanged traded derivatives in general (Opposite to Over t...
     */
    case EXTD = 'EXTD';

    /**
     * FACT - Factor Update related payment
     * Payment related to a factor update
     */
    case FACT = 'FACT';

    /**
     * FAND - FinancialAidInCaseOfNaturalDisaster
     * Financial aid by State authorities for abolition of consequences of natural disasters.
     */
    case FAND = 'FAND';

    /**
     * FCOL - Fee Collection
     * A Service that is settling card transaction related fees between two parties.
     */
    case FCOL = 'FCOL';

    /**
     * FCPM - Late Payment of Fees & Charges
     * Transaction is the payment for late fees & charges. E.g Credit card charges
     */
    case FCPM = 'FCPM';

    /**
     * FEES - Fees
     * Fees related to the opening of a trade
     */
    case FEES = 'FEES';

    /**
     * FERB - Ferry
     * Transaction is a payment for ferry related business.
     */
    case FERB = 'FERB';

    /**
     * FIXI - Fixed Income
     * Cash collateral related to a fixed income instrument
     */
    case FIXI = 'FIXI';

    /**
     * FNET - Futures Netting Payment
     * Cash associated with a netting of futures payments. Refer to CCPM codeword for netting of initial...
     */
    case FNET = 'FNET';

    /**
     * FORW - Forward Foreign Exchange
     * FX trades with a value date in the future.
     */
    case FORW = 'FORW';

    /**
     * FREX - ForeignExchange
     * Transaction is related to a foreign exchange operation.
     */
    case FREX = 'FREX';

    /**
     * FUTR - Futures
     * Cash related to futures trading activity.
     */
    case FUTR = 'FUTR';

    /**
     * FWBC - Forward Broker Owned Cash Collateral
     * Cash collateral payment against a Master Forward Agreement (MFA) where the cash is held in a segr...
     */
    case FWBC = 'FWBC';

    /**
     * FWCC - Forward Client Owned Cash Collateral
     * Cash collateral payment against a Master Forward Agreement (MFA) where the cash is owned and may ...
     */
    case FWCC = 'FWCC';

    /**
     * FWLV - Foreign Worker Levy
     * Transaction is related to a payment of Foreign Worker Levy
     */
    case FWLV = 'FWLV';

    /**
     * FWSB - Forward Broker Owned Cash Collateral Segregated
     * Any cash payment related to the collateral for a Master Agreement forward, which is segregated, a...
     */
    case FWSB = 'FWSB';

    /**
     * FWSC - Forward Client Owned Segregated Cash Collateral
     * Any cash payment related to the collateral for a Master agreement forward, which is owned by the ...
     */
    case FWSC = 'FWSC';

    /**
     * FXNT - Foreign Exchange Related Netting
     * FX netting if cash is moved by separate wire instead of within the closing FX instruction
     */
    case FXNT = 'FXNT';

    /**
     * GASB - GasBill
     * Transaction is related to a payment of gas bill.
     */
    case GASB = 'GASB';

    /**
     * GDDS - PurchaseSaleOfGoods
     * Transaction is related to purchase and sale of goods.
     */
    case GDDS = 'GDDS';

    /**
     * GDSV - PurchaseSaleOfGoodsAndServices
     * Transaction is related to purchase and sale of goods and services.
     */
    case GDSV = 'GDSV';

    /**
     * GFRP - GuaranteeFundRightsPayment
     * Compensation to unemployed persons during insolvency procedures.
     */
    case GFRP = 'GFRP';

    /**
     * GOVI - GovernmentInsurance
     * Transaction is related to a payment of government insurance.
     */
    case GOVI = 'GOVI';

    /**
     * GOVT - GovernmentPayment
     * Transaction is a payment to or from a government department.
     */
    case GOVT = 'GOVT';

    /**
     * GSCB - PurchaseSaleOfGoodsAndServicesWithCashBack
     * Transaction is related to purchase and sale of goods and services with cash back.
     */
    case GSCB = 'GSCB';

    /**
     * GSTX - Goods & Services Tax
     * Transaction is the payment of Goods & Services Tax
     */
    case GSTX = 'GSTX';

    /**
     * GVEA - Austrian Government Employees Category A
     * Transaction is payment to category A Austrian government employees.
     */
    case GVEA = 'GVEA';

    /**
     * GVEB - Austrian Government Employees Category B
     * Transaction is payment to category B Austrian government employees.
     */
    case GVEB = 'GVEB';

    /**
     * GVEC - Austrian Government Employees Category C
     * Transaction is payment to category C Austrian government employees.
     */
    case GVEC = 'GVEC';

    /**
     * GVED - Austrian Government Employees Category D
     * Transaction is payment to category D Austrian government employees.
     */
    case GVED = 'GVED';

    /**
     * GWLT - GovermentWarLegislationTransfer
     * Payment to victims of war violence and to disabled soldiers.
     */
    case GWLT = 'GWLT';

    /**
     * HEDG - Hedging
     * Transaction is related to a hedging operation.
     */
    case HEDG = 'HEDG';

    /**
     * HLRP - HousingLoanRepayment
     * Transaction is related to a payment of housing loan.
     */
    case HLRP = 'HLRP';

    /**
     * HLTC - HomeHealthCare
     * Transaction is a payment for home health care services.
     */
    case HLTC = 'HLTC';

    /**
     * HLTI - HealthInsurance
     * Transaction is a payment of health insurance.
     */
    case HLTI = 'HLTI';

    /**
     * HREC - Housing Related Contribution
     * Transaction is a contribution by an employer to the housing expenditures (purchase, construction,...
     */
    case HREC = 'HREC';

    /**
     * HSPC - HospitalCare
     * Transaction is a payment for hospital care services.
     */
    case HSPC = 'HSPC';

    /**
     * HSTX - HousingTax
     * Transaction is related to a payment of housing tax.
     */
    case HSTX = 'HSTX';

    /**
     * ICCP - IrrevocableCreditCardPayment
     * Transaction is reimbursement of credit card payment.
     */
    case ICCP = 'ICCP';

    /**
     * ICRF - IntermediateCareFacility
     * Transaction is a payment for intermediate care facility services.
     */
    case ICRF = 'ICRF';

    /**
     * IDCP - IrrevocableDebitCardPayment
     * Transaction is reimbursement of debit card payment.
     */
    case IDCP = 'IDCP';

    /**
     * IHRP - InstalmentHirePurchaseAgreement
     * Transaction is payment for an installment/hire-purchase agreement.
     */
    case IHRP = 'IHRP';

    /**
     * INPC - InsurancePremiumCar
     * Transaction is a payment of car insurance premium.
     */
    case INPC = 'INPC';

    /**
     * INSM - Installment
     * Transaction is related to a payment of an installment.
     */
    case INSM = 'INSM';

    /**
     * INSU - InsurancePremium
     * Transaction is payment of an insurance premium.
     */
    case INSU = 'INSU';

    /**
     * INTC - IntraCompanyPayment
     * Transaction is an intra-company payment, ie, a payment between two companies belonging to the sam...
     */
    case INTC = 'INTC';

    /**
     * INTE - Interest
     * Transaction is payment of interest.
     */
    case INTE = 'INTE';

    /**
     * INTX - IncomeTax
     * Transaction is related to a payment of income tax.
     */
    case INTX = 'INTX';

    /**
     * INVS - Investment & Securities
     * Transaction is for the payment of mutual funds, investment products and shares
     */
    case INVS = 'INVS';

    /**
     * IVPT - Invoice Payment
     * Transaction is the payment for invoices.
     */
    case IVPT = 'IVPT';

    /**
     * LBIN - Lending Buy-In Netting
     * Net payment related to a buy-in. When an investment manager is bought in on a sell trade that fai...
     */
    case LBIN = 'LBIN';

    /**
     * LBRI - LaborInsurance
     * Transaction is a payment of labor insurance.
     */
    case LBRI = 'LBRI';

    /**
     * LCOL - Lending Cash Collateral Free Movement
     * Free movement of cash collateral. Cash collateral paid by the borrower is done separately from th...
     */
    case LCOL = 'LCOL';

    /**
     * LFEE - Lending Fees
     * Fee payments, other than rebates, for securities lending. Includes (a) exclusive fees; (b) transa...
     */
    case LFEE = 'LFEE';

    /**
     * LICF - LicenseFee
     * Transaction is payment of a license fee.
     */
    case LICF = 'LICF';

    /**
     * LIFI - LifeInsurance
     * Transaction is a payment of life insurance.
     */
    case LIFI = 'LIFI';

    /**
     * LIMA - LiquidityManagement
     * Bank initiated account transfer to support zero target balance management, pooling or sweeping.
     */
    case LIMA = 'LIMA';

    /**
     * LMEQ - Lending Equity marked-to-market cash collateral
     * Cash collateral payments resulting from the marked-to-market of a portfolio of loaned equity secu...
     */
    case LMEQ = 'LMEQ';

    /**
     * LMFI - Lending Fixed Income marked-to-market cash collateral
     * Cash collateral payments resulting from the marked-to-market of a portfolio of loaned fixed incom...
     */
    case LMFI = 'LMFI';

    /**
     * LMRK - Lending unspecified type of marked-to-market cash collateral
     * Cash collateral payments resulting from the marked-to-market of a portfolio of loaned securities ...
     */
    case LMRK = 'LMRK';

    /**
     * LOAN - Loan
     * Transaction is related to transfer of loan to borrower.
     */
    case LOAN = 'LOAN';

    /**
     * LOAR - LoanRepayment
     * Transaction is related to repayment of loan to lender.
     */
    case LOAR = 'LOAR';

    /**
     * LREB - Lending rebate payments
     * Securities lending rebate payments
     */
    case LREB = 'LREB';

    /**
     * LREV - Lending Revenue Payments
     * Revenue payments made by the lending agent to the client
     */
    case LREV = 'LREV';

    /**
     * LSFL - Lending Claim Payment
     * Payments made by a borrower to a lending agent to satisfy claims made by the investment manager r...
     */
    case LSFL = 'LSFL';

    /**
     * LTCF - LongTermCareFacility
     * Transaction is a payment for long-term care facility services.
     */
    case LTCF = 'LTCF';

    /**
     * MARG - Daily margin on listed derivatives
     * Daily margin on listed derivatives – not segregated as collateral associated with an FCM agreemen...
     */
    case MARG = 'MARG';

    /**
     * MBSB - MBS Broker Owned Cash Collateral
     * MBS Broker Owned Segregated (40Act/Dodd Frank) Cash Collateral - Any cash payment related to the ...
     */
    case MBSB = 'MBSB';

    /**
     * MBSC - MBS Client Owned Cash Collateral
     * MBS Client Owned Cash Segregated (40Act/Dodd Frank) Cash Collateral - Any cash payment related to...
     */
    case MBSC = 'MBSC';

    /**
     * MCDM - MultiCurrenyChequeDomestic
     * Transaction is the payment of a domestic multi-currency cheque
     */
    case MCDM = 'MCDM';

    /**
     * MCFG - MultiCurrenyChequeForeign
     * Transaction is the payment of a foreign multi-currency cheque
     */
    case MCFG = 'MCFG';

    /**
     * MDCS - MedicalServices
     * Transaction is a payment for medical care services.
     */
    case MDCS = 'MDCS';

    /**
     * MGCC - Futures Initial Margin
     * Initial futures margin. Where such payment is owned by the client and is available for use by the...
     */
    case MGCC = 'MGCC';

    /**
     * MGSC - Futures Initial Margin Client Owned Segregated Cash Collateral
     * Margin Client Owned Segregated Cash Collateral - Any cash payment related to the collateral for i...
     */
    case MGSC = 'MGSC';

    /**
     * MSVC - MultipleServiceTypes
     * Transaction is related to a payment for multiple service types.
     */
    case MSVC = 'MSVC';

    /**
     * MTUP - Mobile Top Up
     * A Service that is first reserving money from a card account and then is loading a prepaid mobile ...
     */
    case MTUP = 'MTUP';

    /**
     * NETT - Netting
     * Transaction is related to a netting operation.
     */
    case NETT = 'NETT';

    /**
     * NITX - NetIncomeTax
     * Transaction is related to a payment of net income tax.
     */
    case NITX = 'NITX';

    /**
     * NOWS - NotOtherwiseSpecified
     * Transaction is related to a payment for type of services not specified elsewhere.
     */
    case NOWS = 'NOWS';

    /**
     * NWCH - NetworkCharge
     * Transaction is related to a payment of network charges.
     */
    case NWCH = 'NWCH';

    /**
     * NWCM - NetworkCommunication
     * Transaction is related to a payment of network communication.
     */
    case NWCM = 'NWCM';

    /**
     * OCCC - Client owned OCC pledged collateral
     * Client owned collateral identified as eligible for OCC pledging
     */
    case OCCC = 'OCCC';

    /**
     * OCDM - OrderChequeDomestic
     * Transaction is the payment of a domestic order cheque
     */
    case OCDM = 'OCDM';

    /**
     * OCFG - OrderChequeForeign
     * Transaction is the payment of a foreign order cheque
     */
    case OCFG = 'OCFG';

    /**
     * OFEE - OpeningFee
     * Transaction is related to a payment of opening fee.
     */
    case OFEE = 'OFEE';

    /**
     * OPBC - OTC Option Broker owned Cash collateral
     * Cash collateral payment for OTC options associated with an FCM agreement. Where such payment is s...
     */
    case OPBC = 'OPBC';

    /**
     * OPCC - OTC Option Client owned Cash collateral
     * Cash collateral payment for OTC options associated with an FCM agreement. Where such payment is n...
     */
    case OPCC = 'OPCC';

    /**
     * OPSB - OTC Option Broker Owned Segregated Cash Collateral
     * Option Broker Owned Segregated Cash Collateral - Any cash payment related to the collateral for a...
     */
    case OPSB = 'OPSB';

    /**
     * OPSC - OTC Option Client Owned Cash Segregated Cash Collateral
     * Option Client Owned Cash Segregated Cash Collateral - Any cash payment related to the collateral ...
     */
    case OPSC = 'OPSC';

    /**
     * OPTN - FX Option
     * Cash collateral related to trading of option on Foreign Exchange.
     */
    case OPTN = 'OPTN';

    /**
     * OTCD - OTC Derivatives
     * Cash collateral related to Over-the-counter (OTC) Derivatives in general for example contracts wh...
     */
    case OTCD = 'OTCD';

    /**
     * OTHR - Other
     * Other payment purpose.
     */
    case OTHR = 'OTHR';

    /**
     * OTLC - OtherTelecomRelatedBill
     * Transaction is related to a payment of other telecom related bill.
     */
    case OTLC = 'OTLC';

    /**
     * PADD - Preauthorized debit
     * Transaction is related to a pre-authorized debit origination
     */
    case PADD = 'PADD';

    /**
     * PAYR - Payroll
     * Transaction is related to the payment of payroll.
     */
    case PAYR = 'PAYR';

    /**
     * PENO - PaymentBasedOnEnforcementOrder
     * Payment based on enforcement orders except those arising from judicial alimony decrees.
     */
    case PENO = 'PENO';

    /**
     * PENS - PensionPayment
     * Transaction is the payment of pension.
     */
    case PENS = 'PENS';

    /**
     * PHON - TelephoneBill
     * Transaction is related to a payment of telephone bill.
     */
    case PHON = 'PHON';

    /**
     * POPE - Point of Purchase Entry
     * Transaction is related to a payment associated with a Point of Purchase Entry.
     */
    case POPE = 'POPE';

    /**
     * PPTI - PropertyInsurance
     * Transaction is a payment of property insurance.
     */
    case PPTI = 'PPTI';

    /**
     * PRCP - PricePayment
     * Transaction is related to a payment of a price.
     */
    case PRCP = 'PRCP';

    /**
     * PRME - PreciousMetal
     * Transaction is related to a precious metal operation.
     */
    case PRME = 'PRME';

    /**
     * PTSP - PaymentTerms
     * Transaction is related to payment terms specifications
     */
    case PTSP = 'PTSP';

    /**
     * PTXP - Property Tax
     * Transaction is related to a payment of property tax.
     */
    case PTXP = 'PTXP';

    /**
     * RCKE - Re-presented Check Entry
     * Transaction is related to a payment associated with a re-presented check entry
     */
    case RCKE = 'RCKE';

    /**
     * RCPT - ReceiptPayment
     * Transaction is related to a payment of receipt.
     */
    case RCPT = 'RCPT';

    /**
     * RDTX - Road Tax
     * Transaction is related to a payment of road tax.
     */
    case RDTX = 'RDTX';

    /**
     * REBT - Rebate
     * Transaction is the payment of a rebate.
     */
    case REBT = 'REBT';

    /**
     * REFU - Refund
     * Transaction is the payment of a refund.
     */
    case REFU = 'REFU';

    /**
     * RENT - Rent
     * Transaction is the payment of rent.
     */
    case RENT = 'RENT';

    /**
     * REPO - Repurchase Agreement
     * Cash collateral related to a repurchase agreement transaction.
     */
    case REPO = 'REPO';

    /**
     * RHBS - RehabilitationSupport
     * Benefit for the duration of occupational rehabilitation.
     */
    case RHBS = 'RHBS';

    /**
     * RIMB - Reimbursement of a previous erroneous transaction
     * Transaction is related to a reimbursement of a previous erroneous transaction.
     */
    case RIMB = 'RIMB';

    /**
     * RINP - RecurringInstallmentPayment
     * Transaction is related to a payment of a recurring installment made at regular intervals.
     */
    case RINP = 'RINP';

    /**
     * RLWY - Railway
     * Transaction is a payment for railway transport related business.
     */
    case RLWY = 'RLWY';

    /**
     * ROYA - Royalties
     * Transaction is the payment of royalties.
     */
    case ROYA = 'ROYA';

    /**
     * RPBC - Bi-lateral repo broker owned collateral
     * Bi-lateral repo broker owned collateral associated with a repo master agreement – GMRA or MRA Mas...
     */
    case RPBC = 'RPBC';

    /**
     * RPCC - Repo client owned collateral
     * Repo client owned collateral associated with a repo master agreement – GMRA or MRA Master Repo Ag...
     */
    case RPCC = 'RPCC';

    /**
     * RPNT - Bi-lateral repo internet netting
     * Bi-lateral repo interest net/bulk payment at rollover/pair-off or other closing scenarios where a...
     */
    case RPNT = 'RPNT';

    /**
     * RPSB - Bi-lateral repo broker owned segregated cash collateral
     * Bi-lateral repo broker owned segregated cash collateral associated with a repo master agreement
     */
    case RPSB = 'RPSB';

    /**
     * RPSC - Bi-lateral Repo client owned segregated cash collateral
     * Repo client owned segregated collateral associated with a repo master agreement
     */
    case RPSC = 'RPSC';

    /**
     * RRBN - Round Robin
     * Cash payment resulting from a Round Robin
     */
    case RRBN = 'RRBN';

    /**
     * RVPM - Receive Against Payment
     * Code used to pre-advise the account servicer of a forthcoming receive against payment instruction.
     */
    case RVPM = 'RVPM';

    /**
     * RVPO - Reverse Repurchase Agreement
     * Cash collateral related to a reverse repurchase agreement transaction.
     */
    case RVPO = 'RVPO';

    /**
     * SALA - SalaryPayment
     * Transaction is the payment of salaries.
     */
    case SALA = 'SALA';

    /**
     * SAVG - Savings
     * Transfer to savings/retirement account.
     */
    case SAVG = 'SAVG';

    /**
     * SBSC - Securities Buy Sell Sell Buy Back
     * Cash collateral related to a Securities Buy Sell Sell Buy Back
     */
    case SBSC = 'SBSC';

    /**
     * SCIE - Single Currency IRS Exotic
     * Cash collateral related to Exotic single currency interest rate swap.
     */
    case SCIE = 'SCIE';

    /**
     * SCIR - Single Currency IRS
     * Cash collateral related to Single Currency Interest Rate Swap.
     */
    case SCIR = 'SCIR';

    /**
     * SCRP - Securities Cross Products
     * Cash collateral related to Combination of securities-related exposure types.
     */
    case SCRP = 'SCRP';

    /**
     * SCVE - PurchaseSaleOfServices
     * Transaction is related to purchase and sale of services.
     */
    case SCVE = 'SCVE';

    /**
     * SECU - Securities
     * Transaction is the payment of securities.
     */
    case SECU = 'SECU';

    /**
     * SEPI - Securities Purchase In-house
     * Transaction is the payment of a purchase of securities where custodian bank and current account s...
     */
    case SEPI = 'SEPI';

    /**
     * SHBC - Broker owned collateral Short Sale
     * Short Sale broker owned collateral associated with a prime broker agreement
     */
    case SHBC = 'SHBC';

    /**
     * SHCC - Client owned collateral Short Sale
     * Short Sale client owned collateral associated with a prime brokerage agreement
     */
    case SHCC = 'SHCC';

    /**
     * SHSL - Short Sell
     * Cash Collateral related to a Short Sell
     */
    case SHSL = 'SHSL';

    /**
     * SLEB - Securities Lending And Borrowing
     * Cash collateral related to Securities lending and borrowing.
     */
    case SLEB = 'SLEB';

    /**
     * SLOA - SecuredLoan
     * Cash collateral related to a Secured loan.
     */
    case SLOA = 'SLOA';

    /**
     * SLPI - PaymentSlipInstruction
     * Transaction is payment of a well formatted payment slip.
     */
    case SLPI = 'SLPI';

    /**
     * SPLT - Split payments
     * Split payments. To be used when cash and security movements for a security trade settlement are i...
     */
    case SPLT = 'SPLT';

    /**
     * SSBE - SocialSecurityBenefit
     * Transaction is a social security benefit, ie payment made by a government to support individuals.
     */
    case SSBE = 'SSBE';

    /**
     * STDY - Study
     * Transaction is related to a payment of study/tuition costs.
     */
    case STDY = 'STDY';

    /**
     * SUBS - Subscription
     * Transaction is related to a payment of information or entertainment services either in printed or...
     */
    case SUBS = 'SUBS';

    /**
     * SUPP - SupplierPayment
     * Transaction is related to a payment to a supplier.
     */
    case SUPP = 'SUPP';

    /**
     * SWBC - Swap Broker owned cash collateral
     * Cash collateral payment for swaps associated with an ISDA agreement. . Where such payment is segr...
     */
    case SWBC = 'SWBC';

    /**
     * SWCC - Swap Client owned cash collateral
     * Cash collateral payment for swaps associated with an ISDA agreement. Where such payment is not se...
     */
    case SWCC = 'SWCC';

    /**
     * SWFP - Swap contract final payment
     * Final payments for a swap contract
     */
    case SWFP = 'SWFP';

    /**
     * SWPP - Swap contract partial payment
     * Partial payment for a swap contract
     */
    case SWPP = 'SWPP';

    /**
     * SWPT - Swaption
     * Cash collateral related to an option on interest rate swap.
     */
    case SWPT = 'SWPT';

    /**
     * SWRS - Swap contract reset payment
     * Reset payment for a swap contract
     */
    case SWRS = 'SWRS';

    /**
     * SWSB - Swaps Broker Owned Segregated Cash Collateral
     * Swaps Broker Owned Segregated Cash Collateral - Any cash payment related to the collateral for Sw...
     */
    case SWSB = 'SWSB';

    /**
     * SWSC - Swaps Client Owned Segregated Cash Collateral
     * Swaps Client Owned Segregated Cash Collateral - Any cash payment related to the collateral for Sw...
     */
    case SWSC = 'SWSC';

    /**
     * SWUF - Swap contract upfront payment
     * Upfront payment for a swap contract
     */
    case SWUF = 'SWUF';

    /**
     * TAXR - TaxRefund
     * Transaction is the refund of a tax payment or obligation.
     */
    case TAXR = 'TAXR';

    /**
     * TAXS - TaxPayment
     * Transaction is the payment of taxes.
     */
    case TAXS = 'TAXS';

    /**
     * TBAN - TBA pair-off netting
     * TBA pair-off cash wire net movement
     */
    case TBAN = 'TBAN';

    /**
     * TBAS - To Be Announced
     * Cash collateral related to a To Be Announced (TBA)
     */
    case TBAS = 'TBAS';

    /**
     * TBBC - TBA Broker owned cash collateral
     * Cash collateral payment (segregated) for TBA securities associated with a TBA Master Agreement. W...
     */
    case TBBC = 'TBBC';

    /**
     * TBCC - TBA Client owned cash collateral
     * Cash collateral payment (for use by client)for TBA securities associated with a TBA Master Agreem...
     */
    case TBCC = 'TBCC';

    /**
     * TBIL - Telecommunications Bill
     * Transaction is related to a payment of telecommunications related bill.
     */
    case TBIL = 'TBIL';

    /**
     * TCSC - Town Council Service Charges
     * Transaction is related to a payment associated with charges levied by a town council.
     */
    case TCSC = 'TCSC';

    /**
     * TELI - Telephone-Initiated Transaction
     * Transaction is related to a payment initiated via telephone.
     */
    case TELI = 'TELI';

    /**
     * TLRF - Non-US mutual fund trailer fee payment
     * Any non-US mutual fund trailer fee (retrocession) payment (use ISIN to determine onshore versus o...
     */
    case TLRF = 'TLRF';

    /**
     * TLRR - Non-US mutual fund trailer fee rebate payment
     * Any non-US mutual fund trailer fee (retrocession) rebate payment (use ISIN to determine onshore v...
     */
    case TLRR = 'TLRR';

    /**
     * TMPG - TMPG claim payment
     * Cash payment resulting from a TMPG Claim
     */
    case TMPG = 'TMPG';

    /**
     * TPRI - Tri Party Repo Interest
     * Tri-Party Repo related interest
     */
    case TPRI = 'TPRI';

    /**
     * TPRP - Tri-party Repo netting
     * Tri-party Repo related net gain/loss cash movement
     */
    case TPRP = 'TPRP';

    /**
     * TRAD - TradeServices
     * Transaction is related to a trade services operation.
     */
    case TRAD = 'TRAD';

    /**
     * TRCP - Treasury Cross Product
     * Cash collateral related to a combination of treasury-related exposure types.
     */
    case TRCP = 'TRCP';

    /**
     * TREA - TreasuryPayment
     * Transaction is related to treasury operations.
     */
    case TREA = 'TREA';

    /**
     * TRFD - TrustFund
     * Transaction is related to a payment of a trust fund.
     */
    case TRFD = 'TRFD';

    /**
     * TRNC - TruncatedPaymentSlip
     * Transaction is payment of a beneficiary prefilled payment slip where beneficiary to payer informa...
     */
    case TRNC = 'TRNC';

    /**
     * TRPT - RoadPricing
     * Transaction is for the payment to top-up pre-paid card and electronic road pricing for the purpos...
     */
    case TRPT = 'TRPT';

    /**
     * TRVC - TravellerCheque
     * Transaction is the payment of a travellers cheque
     */
    case TRVC = 'TRVC';

    /**
     * UBIL - Utilities
     * Transaction is for the payment to common utility provider that provide gas, water and/or electric...
     */
    case UBIL = 'UBIL';

    /**
     * VATX - ValueAddedTaxPayment
     * Transaction is the payment of value added tax.
     */
    case VATX = 'VATX';

    /**
     * VIEW - VisionCare
     * Transaction is a payment for vision care services.
     */
    case VIEW = 'VIEW';

    /**
     * WEBI - Internet-Initiated Transaction
     * Transaction is related to a payment initiated via internet.
     */
    case WEBI = 'WEBI';

    /**
     * WHLD - WithHolding
     * Transaction is related to a payment of withholding tax.
     */
    case WHLD = 'WHLD';

    /**
     * WTER - WaterBill
     * Transaction is related to a payment of water bill.
     */
    case WTER = 'WTER';

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match ($this) {
            self::ACCT => 'AccountManagement',
            self::ADCS => 'AdvisoryDonationCopyrightServices',
            self::ADMG => 'AdministrativeManagement',
            self::ADVA => 'AdvancePayment',
            self::AEMP => 'ActiveEmploymentPolicy',
            self::AGRT => 'AgriculturalTransfer',
            self::AIRB => 'Air',
            self::ALLW => 'Allowance',
            self::ALMY => 'AlimonyPayment',
            self::ANNI => 'Annuity',
            self::ANTS => 'AnesthesiaServices',
            self::AREN => 'Accounts Receivables Entry',
            self::B112 => 'Trailer Fee Payment',
            self::BBSC => 'Baby Bonus Scheme',
            self::BCDM => 'BearerChequeDomestic',
            self::BCFG => 'BearerChequeForeign',
            self::BECH => 'ChildBenefit',
            self::BENE => 'UnemploymentDisabilityBenefit',
            self::BEXP => 'BusinessExpenses',
            self::BFWD => 'Bond Forward',
            self::BKDF => 'Bank Loan Delayed Draw Funding',
            self::BKFE => 'Bank Loan Fees',
            self::BKFM => 'Bank Loan Funding Memo',
            self::BKIP => 'Bank Loan Accrued Interest Payment',
            self::BKPP => 'Bank Loan Principal Paydown',
            self::BLDM => 'BuildingMaintenance',
            self::BNET => 'Bond Forward Netting',
            self::BOCE => 'Back Office Conversion Entry',
            self::BONU => 'BonusPayment.',
            self::BR12 => 'Trailer Fee Rebate',
            self::BUSB => 'Bus',
            self::CAFI => 'Custodian Management fee In-house',
            self::CASH => 'CashManagementTransfer',
            self::CBFF => 'CapitalBuilding',
            self::CBFR => 'CapitalBuildingRetirement',
            self::CBLK => 'Card Bulk Clearing',
            self::CBTV => 'CableTVBill',
            self::CCHD => 'Cash compensation, Helplessness, Disability',
            self::CCIR => 'Cross Currency IRS',
            self::CCPC => 'CCP Cleared Initial Margin',
            self::CCPM => 'CCP Cleared Variation Margin',
            self::CCRD => 'CreditCardPayment',
            self::CCSM => 'CCP Cleared Initial Margin Segregated Cash',
            self::CDBL => 'CreditCardBill',
            self::CDCB => 'CardPayment with CashBack',
            self::CDCD => 'CashDisbursement',
            self::CDCS => 'Cash Disbursement with Surcharging',
            self::CDDP => 'Card Deferred Payment',
            self::CDEP => 'Credit default event payment',
            self::CDOC => 'OriginalCredit',
            self::CDQC => 'QuasiCash',
            self::CFDI => 'Capital falling due In-house',
            self::CFEE => 'CancellationFee',
            self::CGDD => 'CardGeneratedDirectDebit',
            self::CHAR => 'CharityPayment',
            self::CLPR => 'CarLoanPrincipalRepayment',
            self::CMDT => 'CommodityTransfer',
            self::COLL => 'CollectionPayment',
            self::COMC => 'CommercialPayment',
            self::COMM => 'Commission',
            self::COMT => 'ConsumerThirdPartyConsolidatedPayment',
            self::CORT => 'Trade Settlement Payment',
            self::COST => 'Costs',
            self::CPKC => 'Carpark Charges',
            self::CPYR => 'Copyright',
            self::CRDS => 'Credit DefaultSwap',
            self::CRPR => 'Cross Product',
            self::CRSP => 'Credit Support',
            self::CRTL => 'Credit Line',
            self::CSDB => 'CashDisbursement',
            self::CSLP => 'CompanySocialLoanPaymentToBank',
            self::CVCF => 'ConvalescentCareFacility',
            self::DBTC => 'DebitCollectionPayment',
            self::DCRD => 'Debit Card Payment',
            self::DEPT => 'Deposit',
            self::DERI => 'Derivatives',
            self::DIVD => 'Dividend',
            self::DMEQ => 'DurableMedicaleEquipment',
            self::DNTS => 'DentalServices',
            self::DSMT => 'Printed Order Disbursement',
            self::DVPM => 'Deliver Against Payment',
            self::ECPG => 'GuaranteedEPayment',
            self::ECPR => 'EPaymentReturn',
            self::ECPU => 'NonGuaranteedEPayment',
            self::EDUC => 'Education',
            self::ELEC => 'ElectricityBill',
            self::ENRG => 'Energies',
            self::EPAY => 'Epayment',
            self::EQPT => 'Equity Option',
            self::EQUS => 'Equity Swap',
            self::ESTX => 'EstateTax',
            self::ETUP => 'E-Purse Top Up',
            self::EXPT => 'Exotic Option',
            self::EXTD => 'Exchange Traded Derivatives',
            self::FACT => 'Factor Update related payment',
            self::FAND => 'FinancialAidInCaseOfNaturalDisaster',
            self::FCOL => 'Fee Collection',
            self::FCPM => 'Late Payment of Fees & Charges',
            self::FEES => 'Fees',
            self::FERB => 'Ferry',
            self::FIXI => 'Fixed Income',
            self::FNET => 'Futures Netting Payment',
            self::FORW => 'Forward Foreign Exchange',
            self::FREX => 'ForeignExchange',
            self::FUTR => 'Futures',
            self::FWBC => 'Forward Broker Owned Cash Collateral',
            self::FWCC => 'Forward Client Owned Cash Collateral',
            self::FWLV => 'Foreign Worker Levy',
            self::FWSB => 'Forward Broker Owned Cash Collateral Segregated',
            self::FWSC => 'Forward Client Owned Segregated Cash Collateral',
            self::FXNT => 'Foreign Exchange Related Netting',
            self::GASB => 'GasBill',
            self::GDDS => 'PurchaseSaleOfGoods',
            self::GDSV => 'PurchaseSaleOfGoodsAndServices',
            self::GFRP => 'GuaranteeFundRightsPayment',
            self::GOVI => 'GovernmentInsurance',
            self::GOVT => 'GovernmentPayment',
            self::GSCB => 'PurchaseSaleOfGoodsAndServicesWithCashBack',
            self::GSTX => 'Goods & Services Tax',
            self::GVEA => 'Austrian Government Employees Category A',
            self::GVEB => 'Austrian Government Employees Category B',
            self::GVEC => 'Austrian Government Employees Category C',
            self::GVED => 'Austrian Government Employees Category D',
            self::GWLT => 'GovermentWarLegislationTransfer',
            self::HEDG => 'Hedging',
            self::HLRP => 'HousingLoanRepayment',
            self::HLTC => 'HomeHealthCare',
            self::HLTI => 'HealthInsurance',
            self::HREC => 'Housing Related Contribution',
            self::HSPC => 'HospitalCare',
            self::HSTX => 'HousingTax',
            self::ICCP => 'IrrevocableCreditCardPayment',
            self::ICRF => 'IntermediateCareFacility',
            self::IDCP => 'IrrevocableDebitCardPayment',
            self::IHRP => 'InstalmentHirePurchaseAgreement',
            self::INPC => 'InsurancePremiumCar',
            self::INSM => 'Installment',
            self::INSU => 'InsurancePremium',
            self::INTC => 'IntraCompanyPayment',
            self::INTE => 'Interest',
            self::INTX => 'IncomeTax',
            self::INVS => 'Investment & Securities',
            self::IVPT => 'Invoice Payment',
            self::LBIN => 'Lending Buy-In Netting',
            self::LBRI => 'LaborInsurance',
            self::LCOL => 'Lending Cash Collateral Free Movement',
            self::LFEE => 'Lending Fees',
            self::LICF => 'LicenseFee',
            self::LIFI => 'LifeInsurance',
            self::LIMA => 'LiquidityManagement',
            self::LMEQ => 'Lending Equity marked-to-market cash collateral',
            self::LMFI => 'Lending Fixed Income marked-to-market cash collateral',
            self::LMRK => 'Lending unspecified type of marked-to-market cash collateral',
            self::LOAN => 'Loan',
            self::LOAR => 'LoanRepayment',
            self::LREB => 'Lending rebate payments',
            self::LREV => 'Lending Revenue Payments',
            self::LSFL => 'Lending Claim Payment',
            self::LTCF => 'LongTermCareFacility',
            self::MARG => 'Daily margin on listed derivatives',
            self::MBSB => 'MBS Broker Owned Cash Collateral',
            self::MBSC => 'MBS Client Owned Cash Collateral',
            self::MCDM => 'MultiCurrenyChequeDomestic',
            self::MCFG => 'MultiCurrenyChequeForeign',
            self::MDCS => 'MedicalServices',
            self::MGCC => 'Futures Initial Margin',
            self::MGSC => 'Futures Initial Margin Client Owned Segregated Cash Collateral',
            self::MSVC => 'MultipleServiceTypes',
            self::MTUP => 'Mobile Top Up',
            self::NETT => 'Netting',
            self::NITX => 'NetIncomeTax',
            self::NOWS => 'NotOtherwiseSpecified',
            self::NWCH => 'NetworkCharge',
            self::NWCM => 'NetworkCommunication',
            self::OCCC => 'Client owned OCC pledged collateral',
            self::OCDM => 'OrderChequeDomestic',
            self::OCFG => 'OrderChequeForeign',
            self::OFEE => 'OpeningFee',
            self::OPBC => 'OTC Option Broker owned Cash collateral',
            self::OPCC => 'OTC Option Client owned Cash collateral',
            self::OPSB => 'OTC Option Broker Owned Segregated Cash Collateral',
            self::OPSC => 'OTC Option Client Owned Cash Segregated Cash Collateral',
            self::OPTN => 'FX Option',
            self::OTCD => 'OTC Derivatives',
            self::OTHR => 'Other',
            self::OTLC => 'OtherTelecomRelatedBill',
            self::PADD => 'Preauthorized debit',
            self::PAYR => 'Payroll',
            self::PENO => 'PaymentBasedOnEnforcementOrder',
            self::PENS => 'PensionPayment',
            self::PHON => 'TelephoneBill',
            self::POPE => 'Point of Purchase Entry',
            self::PPTI => 'PropertyInsurance',
            self::PRCP => 'PricePayment',
            self::PRME => 'PreciousMetal',
            self::PTSP => 'PaymentTerms',
            self::PTXP => 'Property Tax',
            self::RCKE => 'Re-presented Check Entry',
            self::RCPT => 'ReceiptPayment',
            self::RDTX => 'Road Tax',
            self::REBT => 'Rebate',
            self::REFU => 'Refund',
            self::RENT => 'Rent',
            self::REPO => 'Repurchase Agreement',
            self::RHBS => 'RehabilitationSupport',
            self::RIMB => 'Reimbursement of a previous erroneous transaction',
            self::RINP => 'RecurringInstallmentPayment',
            self::RLWY => 'Railway',
            self::ROYA => 'Royalties',
            self::RPBC => 'Bi-lateral repo broker owned collateral',
            self::RPCC => 'Repo client owned collateral',
            self::RPNT => 'Bi-lateral repo internet netting',
            self::RPSB => 'Bi-lateral repo broker owned segregated cash collateral',
            self::RPSC => 'Bi-lateral Repo client owned segregated cash collateral',
            self::RRBN => 'Round Robin',
            self::RVPM => 'Receive Against Payment',
            self::RVPO => 'Reverse Repurchase Agreement',
            self::SALA => 'SalaryPayment',
            self::SAVG => 'Savings',
            self::SBSC => 'Securities Buy Sell Sell Buy Back',
            self::SCIE => 'Single Currency IRS Exotic',
            self::SCIR => 'Single Currency IRS',
            self::SCRP => 'Securities Cross Products',
            self::SCVE => 'PurchaseSaleOfServices',
            self::SECU => 'Securities',
            self::SEPI => 'Securities Purchase In-house',
            self::SHBC => 'Broker owned collateral Short Sale',
            self::SHCC => 'Client owned collateral Short Sale',
            self::SHSL => 'Short Sell',
            self::SLEB => 'Securities Lending And Borrowing',
            self::SLOA => 'SecuredLoan',
            self::SLPI => 'PaymentSlipInstruction',
            self::SPLT => 'Split payments',
            self::SSBE => 'SocialSecurityBenefit',
            self::STDY => 'Study',
            self::SUBS => 'Subscription',
            self::SUPP => 'SupplierPayment',
            self::SWBC => 'Swap Broker owned cash collateral',
            self::SWCC => 'Swap Client owned cash collateral',
            self::SWFP => 'Swap contract final payment',
            self::SWPP => 'Swap contract partial payment',
            self::SWPT => 'Swaption',
            self::SWRS => 'Swap contract reset payment',
            self::SWSB => 'Swaps Broker Owned Segregated Cash Collateral',
            self::SWSC => 'Swaps Client Owned Segregated Cash Collateral',
            self::SWUF => 'Swap contract upfront payment',
            self::TAXR => 'TaxRefund',
            self::TAXS => 'TaxPayment',
            self::TBAN => 'TBA pair-off netting',
            self::TBAS => 'To Be Announced',
            self::TBBC => 'TBA Broker owned cash collateral',
            self::TBCC => 'TBA Client owned cash collateral',
            self::TBIL => 'Telecommunications Bill',
            self::TCSC => 'Town Council Service Charges',
            self::TELI => 'Telephone-Initiated Transaction',
            self::TLRF => 'Non-US mutual fund trailer fee payment',
            self::TLRR => 'Non-US mutual fund trailer fee rebate payment',
            self::TMPG => 'TMPG claim payment',
            self::TPRI => 'Tri Party Repo Interest',
            self::TPRP => 'Tri-party Repo netting',
            self::TRAD => 'TradeServices',
            self::TRCP => 'Treasury Cross Product',
            self::TREA => 'TreasuryPayment',
            self::TRFD => 'TrustFund',
            self::TRNC => 'TruncatedPaymentSlip',
            self::TRPT => 'RoadPricing',
            self::TRVC => 'TravellerCheque',
            self::UBIL => 'Utilities',
            self::VATX => 'ValueAddedTaxPayment',
            self::VIEW => 'VisionCare',
            self::WEBI => 'Internet-Initiated Transaction',
            self::WHLD => 'WithHolding',
            self::WTER => 'WaterBill',
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match ($this) {
            self::ACCT => 'Transaction moves funds between 2 accounts of same account holder at the same bank.',
            self::ADCS => 'Payments for donation, sponsorship, advisory, intellectual and other copyright services.',
            self::ADMG => 'Transaction is related to a payment associated with administrative management.',
            self::ADVA => 'Transaction is an advance payment.',
            self::AEMP => 'Payment concerning active employment policy.',
            self::AGRT => 'Transaction is related to the agricultural domain.',
            self::AIRB => 'Transaction is a payment for air transport related business.',
            self::ALLW => 'Transaction is the payment of allowances.',
            self::ALMY => 'Transaction is the payment of alimony.',
            self::ANNI => 'Transaction settles annuity related to credit, insurance, investments, other.n',
            self::ANTS => 'Transaction is a payment for anesthesia services.',
            self::AREN => 'Transaction is related to a payment associated with an Account Receivable Entry',
            self::B112 => 'US mutual fund trailer fee (12b-1) payment',
            self::BBSC => 'Transaction is related to a payment made as incentive to encourage parents to have more children',
            self::BCDM => 'Transaction is the payment of a domestic bearer cheque.',
            self::BCFG => 'Transaction is the payment of a foreign bearer cheque.',
            self::BECH => 'Transaction is related to a payment made to assist parent/guardian to maintain child.',
            self::BENE => 'Transaction is related to a payment to a person who is unemployed/disabled.',
            self::BEXP => 'Transaction is related to a payment of business expenses.',
            self::BFWD => 'Cash collateral related to any securities traded out beyond 3 days which include treasury notes, JGBs and Gilts.',
            self::BKDF => 'Delayed draw funding. Certain issuers may utilize delayed draw loans whereby the lender is committed to fund cash within a specified period once a call is made by the issuer. The lender receives a ...',
            self::BKFE => 'Bank loan fees. Cash activity related to specific bank loan fees, including (a) agent / assignment fees; (b) amendment fees; (c) commitment fees; (d) consent fees; (e) cost of carry fees; (f) delay...',
            self::BKFM => 'Bank loan funding memo. Net cash movement for the loan contract final notification when sent separately from the loan contract final notification instruction.',
            self::BKIP => 'Accrued interest payments. Specific to bank loans.',
            self::BKPP => 'Principal paydowns. Specific to bank loans',
            self::BLDM => 'Transaction is related to a payment associated with building maintenance.',
            self::BNET => 'Bond Forward pair-off cash net movement',
            self::BOCE => 'Transaction is related to a payment associated with a Back Office Conversion Entry',
            self::BONU => 'Transaction is related to payment of a bonus.',
            self::BR12 => 'US mutual fund trailer fee (12b-1) rebate payment',
            self::BUSB => 'Transaction is a payment for bus transport related business.',
            self::CAFI => 'Transaction is the payment of custodian account management fee where custodian bank and current account servicing bank coincide',
            self::CASH => 'Transaction is a general cash management instruction.',
            self::CBFF => 'Transaction is related to capital building fringe fortune, ie capital building in general',
            self::CBFR => 'Transaction is related to capital building fringe fortune for retirement',
            self::CBLK => 'A Service that is settling money for a bulk of card transactions, while referring to a specific transaction file or other information like terminal ID, card acceptor ID or other transaction details.',
            self::CBTV => 'Transaction is related to a payment of cable TV bill.',
            self::CCHD => 'Payments made by Government institute related to cash compensation, helplessness, disability. These payments are made by the Government institution as a social benefit in addition to regularly paid...',
            self::CCIR => 'Cash Collateral related to a Cross Currency Interest Rate Swap, indicating the exchange of fixed interest payments in one currency for those in another.',
            self::CCPC => 'Cash Collateral associated with an ISDA or Central Clearing Agreement that is covering the initial margin requirements for OTC trades clearing through a CCP.',
            self::CCPM => 'Cash Collateral associated with an ISDA or Central Clearing Agreement that is covering the variation margin requirements for OTC trades clearing through a CCP.',
            self::CCRD => 'Transaction is related to a payment of credit card account.',
            self::CCSM => 'CCP Segregated initial margin: Initial margin on OTC Derivatives cleared through a CCP that requires segregation',
            self::CDBL => 'Transaction is related to a payment of credit card bill.',
            self::CDCB => 'Purchase of Goods and Services with additional Cash disbursement at the POI (Cashback)',
            self::CDCD => 'ATM Cash Withdrawal in an unattended or Cash Advance in an attended environment (POI or bank counter)',
            self::CDCS => 'ATM Cash Withdrawal in an unattended or Cash Advance in an attended environment (POI or bank counter) with surcharging.',
            self::CDDP => 'A combined service which enables the card acceptor to perform an authorisation for a temporary amount and a completion for the final amount within a limited time frame. Deferred Payment is only ava...',
            self::CDEP => 'Payment related to a credit default event',
            self::CDOC => 'A service which allows the card acceptor to effect a credit to a cardholder\' account. Unlike a Merchant Refund, an Original Credit is not preceded by a card payment. This service is used for exampl...',
            self::CDQC => 'Purchase of Goods which are equivalent to cash like coupons in casinos.',
            self::CFDI => 'Transaction is the payment of capital falling due where custodian bank and current account servicing bank coincide',
            self::CFEE => 'Transaction is related to a payment of cancellation fee.',
            self::CGDD => 'Transaction is related to a direct debit where the mandate was generated by using data from a payment card at the point of sale.',
            self::CHAR => 'Transaction is a payment for charity reasons.',
            self::CLPR => 'Transaction is a payment of car loan principal payment.',
            self::CMDT => 'Transaction is payment of commodities.',
            self::COLL => 'Transaction is a collection of funds initiated via a credit transfer or direct debit.',
            self::COMC => 'Transaction is related to a payment of commercial credit or debit. (formerly CommercialCredit)',
            self::COMM => 'Transaction is payment of commission.',
            self::COMT => 'Transaction is a payment used by a third party who can collect funds to pay on behalf of consumers, ie credit counseling or bill payment companies.',
            self::CORT => 'Transaction is related to settlement of a trade, e.g. a foreign exchange deal or a securities transaction.',
            self::COST => 'Transaction is related to payment of costs.',
            self::CPKC => 'Transaction is related to carpark charges.',
            self::CPYR => 'Transaction is payment of copyright.',
            self::CRDS => 'Cash collateral related to trading of credit default swap.',
            self::CRPR => 'Cash collateral related to a combination of various types of trades.',
            self::CRSP => 'Cash collateral related to cash lending/borrowing; letter of Credit; signing of master agreement.',
            self::CRTL => 'Cash collateral related to opening of a credit line before trading.',
            self::CSDB => 'Transaction is related to cash disbursement.',
            self::CSLP => 'Transaction is a payment by a company to a bank for financing social loans to employees.',
            self::CVCF => 'Transaction is a payment for convalescence care facility services.',
            self::DBTC => 'Collection of funds initiated via a debit transfer.',
            self::DCRD => 'Transaction is related to a debit card payment.',
            self::DEPT => 'Transaction is releted to a payment of deposit.',
            self::DERI => 'Transaction is related to a derivatives transaction',
            self::DIVD => 'Transaction is payment of dividends.',
            self::DMEQ => 'Transaction is a payment is for use of durable medical equipment.',
            self::DNTS => 'Transaction is a payment for dental services.',
            self::DSMT => 'Transaction is the payment of a disbursement due to a specific type of printed order for a payment of a specified sum, issued by a bank or a post office (Zahlungsanweisung zur Verrechnung)',
            self::DVPM => 'Code used to pre-advise the account servicer of a forthcoming deliver against payment instruction.',
            self::ECPG => 'E-Commerce payment with payment guarantee of the issuing bank.',
            self::ECPR => 'E-Commerce payment return.',
            self::ECPU => 'E-Commerce payment without payment guarantee of the issuing bank.',
            self::EDUC => 'Transaction is related to a payment of study/tuition fees.',
            self::ELEC => 'Transaction is related to a payment of electricity bill.',
            self::ENRG => 'Transaction is related to a utility operation.',
            self::EPAY => 'Transaction is related to ePayment.',
            self::EQPT => 'Cash collateral related to trading of equity option (Also known as stock options).',
            self::EQUS => 'Cash collateral related to equity swap trades where the return of an equity is exchanged for either a fixed or a floating rate of interest.',
            self::ESTX => 'Transaction is related to a payment of estate tax.',
            self::ETUP => 'Transaction is related to a Service that is first reserving money from a card account and then is loading an e-purse application by this amount.',
            self::EXPT => 'Cash collateral related to trading of an exotic option for example a non-standard option.',
            self::EXTD => 'Cash collateral related to trading of exchanged traded derivatives in general (Opposite to Over the Counter (OTC)).',
            self::FACT => 'Payment related to a factor update',
            self::FAND => 'Financial aid by State authorities for abolition of consequences of natural disasters.',
            self::FCOL => 'A Service that is settling card transaction related fees between two parties.',
            self::FCPM => 'Transaction is the payment for late fees & charges. E.g Credit card charges',
            self::FEES => 'Fees related to the opening of a trade',
            self::FERB => 'Transaction is a payment for ferry related business.',
            self::FIXI => 'Cash collateral related to a fixed income instrument',
            self::FNET => 'Cash associated with a netting of futures payments. Refer to CCPM codeword for netting of initial and variation margin through a CCP',
            self::FORW => 'FX trades with a value date in the future.',
            self::FREX => 'Transaction is related to a foreign exchange operation.',
            self::FUTR => 'Cash related to futures trading activity.',
            self::FWBC => 'Cash collateral payment against a Master Forward Agreement (MFA) where the cash is held in a segregated account and is not available for use by the client. Includes any instruments with a forward s...',
            self::FWCC => 'Cash collateral payment against a Master Forward Agreement (MFA) where the cash is owned and may be used by the client when returned. Includes any instruments with a forward settling date such TBAs...',
            self::FWLV => 'Transaction is related to a payment of Foreign Worker Levy',
            self::FWSB => 'Any cash payment related to the collateral for a Master Agreement forward, which is segregated, and not available for use by the client. Example master agreement forwards include TBA, repo and Bond...',
            self::FWSC => 'Any cash payment related to the collateral for a Master agreement forward, which is owned by the client and is available for use by the client when it is returned to them from the segregated accoun...',
            self::FXNT => 'FX netting if cash is moved by separate wire instead of within the closing FX instruction',
            self::GASB => 'Transaction is related to a payment of gas bill.',
            self::GDDS => 'Transaction is related to purchase and sale of goods.',
            self::GDSV => 'Transaction is related to purchase and sale of goods and services.',
            self::GFRP => 'Compensation to unemployed persons during insolvency procedures.',
            self::GOVI => 'Transaction is related to a payment of government insurance.',
            self::GOVT => 'Transaction is a payment to or from a government department.',
            self::GSCB => 'Transaction is related to purchase and sale of goods and services with cash back.',
            self::GSTX => 'Transaction is the payment of Goods & Services Tax',
            self::GVEA => 'Transaction is payment to category A Austrian government employees.',
            self::GVEB => 'Transaction is payment to category B Austrian government employees.',
            self::GVEC => 'Transaction is payment to category C Austrian government employees.',
            self::GVED => 'Transaction is payment to category D Austrian government employees.',
            self::GWLT => 'Payment to victims of war violence and to disabled soldiers.',
            self::HEDG => 'Transaction is related to a hedging operation.',
            self::HLRP => 'Transaction is related to a payment of housing loan.',
            self::HLTC => 'Transaction is a payment for home health care services.',
            self::HLTI => 'Transaction is a payment of health insurance.',
            self::HREC => 'Transaction is a contribution by an employer to the housing expenditures (purchase, construction, renovation) of the employees within a tax free fringe benefit system',
            self::HSPC => 'Transaction is a payment for hospital care services.',
            self::HSTX => 'Transaction is related to a payment of housing tax.',
            self::ICCP => 'Transaction is reimbursement of credit card payment.',
            self::ICRF => 'Transaction is a payment for intermediate care facility services.',
            self::IDCP => 'Transaction is reimbursement of debit card payment.',
            self::IHRP => 'Transaction is payment for an installment/hire-purchase agreement.',
            self::INPC => 'Transaction is a payment of car insurance premium.',
            self::INSM => 'Transaction is related to a payment of an installment.',
            self::INSU => 'Transaction is payment of an insurance premium.',
            self::INTC => 'Transaction is an intra-company payment, ie, a payment between two companies belonging to the same group.',
            self::INTE => 'Transaction is payment of interest.',
            self::INTX => 'Transaction is related to a payment of income tax.',
            self::INVS => 'Transaction is for the payment of mutual funds, investment products and shares',
            self::IVPT => 'Transaction is the payment for invoices.',
            self::LBIN => 'Net payment related to a buy-in. When an investment manager is bought in on a sell trade that fails due to a failed securities lending recall, the IM may seize the underlying collateral to pay for ...',
            self::LBRI => 'Transaction is a payment of labor insurance.',
            self::LCOL => 'Free movement of cash collateral. Cash collateral paid by the borrower is done separately from the delivery of the shares at loan opening or return of collateral done separately from return of the ...',
            self::LFEE => 'Fee payments, other than rebates, for securities lending. Includes (a) exclusive fees; (b) transaction fees; (c) custodian fees; (d) minimum balance fees',
            self::LICF => 'Transaction is payment of a license fee.',
            self::LIFI => 'Transaction is a payment of life insurance.',
            self::LIMA => 'Bank initiated account transfer to support zero target balance management, pooling or sweeping.',
            self::LMEQ => 'Cash collateral payments resulting from the marked-to-market of a portfolio of loaned equity securities',
            self::LMFI => 'Cash collateral payments resulting from the marked-to-market of a portfolio of loaned fixed income securities',
            self::LMRK => 'Cash collateral payments resulting from the marked-to-market of a portfolio of loaned securities where the instrument types are not specified',
            self::LOAN => 'Transaction is related to transfer of loan to borrower.',
            self::LOAR => 'Transaction is related to repayment of loan to lender.',
            self::LREB => 'Securities lending rebate payments',
            self::LREV => 'Revenue payments made by the lending agent to the client',
            self::LSFL => 'Payments made by a borrower to a lending agent to satisfy claims made by the investment manager related to sell fails from late loan recall deliveries',
            self::LTCF => 'Transaction is a payment for long-term care facility services.',
            self::MARG => 'Daily margin on listed derivatives – not segregated as collateral associated with an FCM agreement. Examples include listed futures and options margin payments; premiums for listed options not cove...',
            self::MBSB => 'MBS Broker Owned Segregated (40Act/Dodd Frank) Cash Collateral - Any cash payment related to the collateral for a Mortgage Back Security, which is segregated, and not available for use by the client.',
            self::MBSC => 'MBS Client Owned Cash Segregated (40Act/Dodd Frank) Cash Collateral - Any cash payment related to the collateral for a Mortgage Back Security, which is owned by the client and is available for use ...',
            self::MCDM => 'Transaction is the payment of a domestic multi-currency cheque',
            self::MCFG => 'Transaction is the payment of a foreign multi-currency cheque',
            self::MDCS => 'Transaction is a payment for medical care services.',
            self::MGCC => 'Initial futures margin. Where such payment is owned by the client and is available for use by them on return',
            self::MGSC => 'Margin Client Owned Segregated Cash Collateral - Any cash payment related to the collateral for initial futures margin, which is owned by the client and is available for use by the client when it i...',
            self::MSVC => 'Transaction is related to a payment for multiple service types.',
            self::MTUP => 'A Service that is first reserving money from a card account and then is loading a prepaid mobile phone amount by this amount.',
            self::NETT => 'Transaction is related to a netting operation.',
            self::NITX => 'Transaction is related to a payment of net income tax.',
            self::NOWS => 'Transaction is related to a payment for type of services not specified elsewhere.',
            self::NWCH => 'Transaction is related to a payment of network charges.',
            self::NWCM => 'Transaction is related to a payment of network communication.',
            self::OCCC => 'Client owned collateral identified as eligible for OCC pledging',
            self::OCDM => 'Transaction is the payment of a domestic order cheque',
            self::OCFG => 'Transaction is the payment of a foreign order cheque',
            self::OFEE => 'Transaction is related to a payment of opening fee.',
            self::OPBC => 'Cash collateral payment for OTC options associated with an FCM agreement. Where such payment is segregated and not available for use by the client',
            self::OPCC => 'Cash collateral payment for OTC options associated with an FCM agreement. Where such payment is not segregated and is available for use by the client upon return',
            self::OPSB => 'Option Broker Owned Segregated Cash Collateral - Any cash payment related to the collateral for an OTC option, which is segregated, and not available for use by the client.',
            self::OPSC => 'Option Client Owned Cash Segregated Cash Collateral - Any cash payment related to the collateral for an OTC option, which is owned by the client and is available for use by the client when it is re...',
            self::OPTN => 'Cash collateral related to trading of option on Foreign Exchange.',
            self::OTCD => 'Cash collateral related to Over-the-counter (OTC) Derivatives in general for example contracts which are traded and privately negotiated.',
            self::OTHR => 'Other payment purpose.',
            self::OTLC => 'Transaction is related to a payment of other telecom related bill.',
            self::PADD => 'Transaction is related to a pre-authorized debit origination',
            self::PAYR => 'Transaction is related to the payment of payroll.',
            self::PENO => 'Payment based on enforcement orders except those arising from judicial alimony decrees.',
            self::PENS => 'Transaction is the payment of pension.',
            self::PHON => 'Transaction is related to a payment of telephone bill.',
            self::POPE => 'Transaction is related to a payment associated with a Point of Purchase Entry.',
            self::PPTI => 'Transaction is a payment of property insurance.',
            self::PRCP => 'Transaction is related to a payment of a price.',
            self::PRME => 'Transaction is related to a precious metal operation.',
            self::PTSP => 'Transaction is related to payment terms specifications',
            self::PTXP => 'Transaction is related to a payment of property tax.',
            self::RCKE => 'Transaction is related to a payment associated with a re-presented check entry',
            self::RCPT => 'Transaction is related to a payment of receipt.',
            self::RDTX => 'Transaction is related to a payment of road tax.',
            self::REBT => 'Transaction is the payment of a rebate.',
            self::REFU => 'Transaction is the payment of a refund.',
            self::RENT => 'Transaction is the payment of rent.',
            self::REPO => 'Cash collateral related to a repurchase agreement transaction.',
            self::RHBS => 'Benefit for the duration of occupational rehabilitation.',
            self::RIMB => 'Transaction is related to a reimbursement of a previous erroneous transaction.',
            self::RINP => 'Transaction is related to a payment of a recurring installment made at regular intervals.',
            self::RLWY => 'Transaction is a payment for railway transport related business.',
            self::ROYA => 'Transaction is the payment of royalties.',
            self::RPBC => 'Bi-lateral repo broker owned collateral associated with a repo master agreement – GMRA or MRA Master Repo Agreements',
            self::RPCC => 'Repo client owned collateral associated with a repo master agreement – GMRA or MRA Master Repo Agreements',
            self::RPNT => 'Bi-lateral repo interest net/bulk payment at rollover/pair-off or other closing scenarios where applicable',
            self::RPSB => 'Bi-lateral repo broker owned segregated cash collateral associated with a repo master agreement',
            self::RPSC => 'Repo client owned segregated collateral associated with a repo master agreement',
            self::RRBN => 'Cash payment resulting from a Round Robin',
            self::RVPM => 'Code used to pre-advise the account servicer of a forthcoming receive against payment instruction.',
            self::RVPO => 'Cash collateral related to a reverse repurchase agreement transaction.',
            self::SALA => 'Transaction is the payment of salaries.',
            self::SAVG => 'Transfer to savings/retirement account.',
            self::SBSC => 'Cash collateral related to a Securities Buy Sell Sell Buy Back',
            self::SCIE => 'Cash collateral related to Exotic single currency interest rate swap.',
            self::SCIR => 'Cash collateral related to Single Currency Interest Rate Swap.',
            self::SCRP => 'Cash collateral related to Combination of securities-related exposure types.',
            self::SCVE => 'Transaction is related to purchase and sale of services.',
            self::SECU => 'Transaction is the payment of securities.',
            self::SEPI => 'Transaction is the payment of a purchase of securities where custodian bank and current account servicing bank coincide',
            self::SHBC => 'Short Sale broker owned collateral associated with a prime broker agreement',
            self::SHCC => 'Short Sale client owned collateral associated with a prime brokerage agreement',
            self::SHSL => 'Cash Collateral related to a Short Sell',
            self::SLEB => 'Cash collateral related to Securities lending and borrowing.',
            self::SLOA => 'Cash collateral related to a Secured loan.',
            self::SLPI => 'Transaction is payment of a well formatted payment slip.',
            self::SPLT => 'Split payments. To be used when cash and security movements for a security trade settlement are instructed separately.',
            self::SSBE => 'Transaction is a social security benefit, ie payment made by a government to support individuals.',
            self::STDY => 'Transaction is related to a payment of study/tuition costs.',
            self::SUBS => 'Transaction is related to a payment of information or entertainment services either in printed or electronic form.',
            self::SUPP => 'Transaction is related to a payment to a supplier.',
            self::SWBC => 'Cash collateral payment for swaps associated with an ISDA agreement. . Where such payment is segregated and not available for use by the client. Includes any cash collateral payments made under the...',
            self::SWCC => 'Cash collateral payment for swaps associated with an ISDA agreement. Where such payment is not segregated and is available for use by the client upon return. Includes any cash collateral payments m...',
            self::SWFP => 'Final payments for a swap contract',
            self::SWPP => 'Partial payment for a swap contract',
            self::SWPT => 'Cash collateral related to an option on interest rate swap.',
            self::SWRS => 'Reset payment for a swap contract',
            self::SWSB => 'Swaps Broker Owned Segregated Cash Collateral - Any cash payment related to the collateral for Swap margin , which is segregated, and not available for use by the client. This includes any collater...',
            self::SWSC => 'Swaps Client Owned Segregated Cash Collateral - Any cash payment related to the collateral for Swap margin, which is owned by the client and is available for use by the client when returned from th...',
            self::SWUF => 'Upfront payment for a swap contract',
            self::TAXR => 'Transaction is the refund of a tax payment or obligation.',
            self::TAXS => 'Transaction is the payment of taxes.',
            self::TBAN => 'TBA pair-off cash wire net movement',
            self::TBAS => 'Cash collateral related to a To Be Announced (TBA)',
            self::TBBC => 'Cash collateral payment (segregated) for TBA securities associated with a TBA Master Agreement. Where such payment is segregated and not available for use by the client.',
            self::TBCC => 'Cash collateral payment (for use by client)for TBA securities associated with a TBA Master Agreement. Where such payment is not segregated and is available for use by the client upon return.',
            self::TBIL => 'Transaction is related to a payment of telecommunications related bill.',
            self::TCSC => 'Transaction is related to a payment associated with charges levied by a town council.',
            self::TELI => 'Transaction is related to a payment initiated via telephone.',
            self::TLRF => 'Any non-US mutual fund trailer fee (retrocession) payment (use ISIN to determine onshore versus offshore designation)',
            self::TLRR => 'Any non-US mutual fund trailer fee (retrocession) rebate payment (use ISIN to determine onshore versus offshore designation)',
            self::TMPG => 'Cash payment resulting from a TMPG Claim',
            self::TPRI => 'Tri-Party Repo related interest',
            self::TPRP => 'Tri-party Repo related net gain/loss cash movement',
            self::TRAD => 'Transaction is related to a trade services operation.',
            self::TRCP => 'Cash collateral related to a combination of treasury-related exposure types.',
            self::TREA => 'Transaction is related to treasury operations.',
            self::TRFD => 'Transaction is related to a payment of a trust fund.',
            self::TRNC => 'Transaction is payment of a beneficiary prefilled payment slip where beneficiary to payer information is truncated.',
            self::TRPT => 'Transaction is for the payment to top-up pre-paid card and electronic road pricing for the purpose of transportation',
            self::TRVC => 'Transaction is the payment of a travellers cheque',
            self::UBIL => 'Transaction is for the payment to common utility provider that provide gas, water and/or electricity.',
            self::VATX => 'Transaction is the payment of value added tax.',
            self::VIEW => 'Transaction is a payment for vision care services.',
            self::WEBI => 'Transaction is related to a payment initiated via internet.',
            self::WHLD => 'Transaction is related to a payment of withholding tax.',
            self::WTER => 'Transaction is related to a payment of water bill.',
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
