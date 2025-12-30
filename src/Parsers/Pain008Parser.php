<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain008Parser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Pain\AccountIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\FinancialInstitution;
use CommonToolkit\Entities\Common\Banking\Pain\PartyIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PaymentIdentification;
use CommonToolkit\Entities\Common\Banking\Pain\PostalAddress;
use CommonToolkit\Entities\Common\Banking\Pain\RemittanceInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\DirectDebitTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\MandateInformation;
use CommonToolkit\Entities\Common\Banking\Pain\Type008\PaymentInstruction;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\LocalInstrument;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use CommonToolkit\Enums\Common\Banking\SequenceType;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parser für pain.008 (Customer Direct Debit Initiation).
 * 
 * @package CommonToolkit\Parsers
 */
class Pain008Parser {
    /**
     * Parst ein pain.008 XML-Dokument.
     */
    public static function fromXml(string $xml): Document {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);

        // Namespace ermitteln
        $namespace = self::detectNamespace($dom);
        if ($namespace) {
            $xpath->registerNamespace('p', $namespace);
            $prefix = 'p:';
        } else {
            $prefix = '';
        }

        // GroupHeader parsen
        $grpHdrNode = $xpath->query("//{$prefix}GrpHdr")->item(0);
        $groupHeader = self::parseGroupHeader($xpath, $grpHdrNode, $prefix);

        // PaymentInstructions parsen
        $pmtInfNodes = $xpath->query("//{$prefix}PmtInf");
        $paymentInstructions = [];

        foreach ($pmtInfNodes as $pmtInfNode) {
            $paymentInstructions[] = self::parsePaymentInstruction($xpath, $pmtInfNode, $prefix);
        }

        return new Document($groupHeader, $paymentInstructions);
    }

    /**
     * Prüft, ob ein XML ein gültiges pain.008 Dokument ist.
     */
    public static function isValid(string $xml): bool {
        try {
            $dom = new DOMDocument();
            if (!@$dom->loadXML($xml)) {
                return false;
            }

            $xpath = new DOMXPath($dom);
            $namespace = self::detectNamespace($dom);

            if ($namespace) {
                $xpath->registerNamespace('p', $namespace);
                return $xpath->query('//p:CstmrDrctDbtInitn')->length > 0;
            }

            return $xpath->query('//CstmrDrctDbtInitn')->length > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function detectNamespace(DOMDocument $dom): ?string {
        $root = $dom->documentElement;

        if ($root && $root->hasAttribute('xmlns')) {
            return $root->getAttribute('xmlns');
        }

        $ns = $root?->namespaceURI;
        if ($ns && str_contains($ns, 'pain.008')) {
            return $ns;
        }

        return null;
    }

    private static function parseGroupHeader(DOMXPath $xpath, ?\DOMNode $node, string $prefix): GroupHeader {
        if (!$node) {
            return GroupHeader::create('UNKNOWN', new PartyIdentification(name: 'Unknown'));
        }

        $msgId = $xpath->query("{$prefix}MsgId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $creDtTmStr = $xpath->query("{$prefix}CreDtTm", $node)->item(0)?->textContent;
        $creDtTm = $creDtTmStr ? new DateTimeImmutable($creDtTmStr) : new DateTimeImmutable();

        $nbOfTxs = (int) ($xpath->query("{$prefix}NbOfTxs", $node)->item(0)?->textContent ?? '0');
        $ctrlSumStr = $xpath->query("{$prefix}CtrlSum", $node)->item(0)?->textContent;
        $ctrlSum = $ctrlSumStr !== null ? (float) $ctrlSumStr : null;

        // InitgPty parsen
        $initgPtyNode = $xpath->query("{$prefix}InitgPty", $node)->item(0);
        $initiatingParty = self::parsePartyIdentification($xpath, $initgPtyNode, $prefix);

        return new GroupHeader($msgId, $creDtTm, $nbOfTxs, $ctrlSum, $initiatingParty);
    }

    private static function parsePaymentInstruction(DOMXPath $xpath, \DOMNode $node, string $prefix): PaymentInstruction {
        $pmtInfId = $xpath->query("{$prefix}PmtInfId", $node)->item(0)?->textContent ?? 'UNKNOWN';

        $pmtMtdStr = $xpath->query("{$prefix}PmtMtd", $node)->item(0)?->textContent ?? 'DD';
        $pmtMtd = PaymentMethod::tryFrom($pmtMtdStr) ?? PaymentMethod::DIRECT_DEBIT;

        $reqdColltnDtStr = $xpath->query("{$prefix}ReqdColltnDt", $node)->item(0)?->textContent;
        $reqdColltnDt = $reqdColltnDtStr ? new DateTimeImmutable($reqdColltnDtStr) : new DateTimeImmutable();

        // Creditor parsen
        $cdtrNode = $xpath->query("{$prefix}Cdtr", $node)->item(0);
        $creditor = self::parsePartyIdentification($xpath, $cdtrNode, $prefix);

        // CreditorAccount parsen
        $cdtrAcctNode = $xpath->query("{$prefix}CdtrAcct", $node)->item(0);
        $creditorAccount = self::parseAccountIdentification($xpath, $cdtrAcctNode, $prefix);

        // CreditorAgent parsen
        $cdtrAgtNode = $xpath->query("{$prefix}CdtrAgt", $node)->item(0);
        $creditorAgent = self::parseFinancialInstitution($xpath, $cdtrAgtNode, $prefix);

        // CreditorSchemeId parsen
        $cdtrSchmeId = $xpath->query("{$prefix}CdtrSchmeId/{$prefix}Id/{$prefix}PrvtId/{$prefix}Othr/{$prefix}Id", $node)->item(0)?->textContent;

        // ChargeBearer
        $chrgBrStr = $xpath->query("{$prefix}ChrgBr", $node)->item(0)?->textContent;
        $chrgBr = $chrgBrStr ? ChargesCode::tryFrom($chrgBrStr) : null;

        // SequenceType
        $seqTpStr = $xpath->query("{$prefix}PmtTpInf/{$prefix}SeqTp", $node)->item(0)?->textContent;
        $seqTp = $seqTpStr ? SequenceType::tryFrom($seqTpStr) : null;

        // LocalInstrument
        $lclInstrmStr = $xpath->query("{$prefix}PmtTpInf/{$prefix}LclInstrm/{$prefix}Cd", $node)->item(0)?->textContent;
        $lclInstrm = $lclInstrmStr ? LocalInstrument::tryFrom($lclInstrmStr) : null;

        // ServiceLevel
        $svcLvl = $xpath->query("{$prefix}PmtTpInf/{$prefix}SvcLvl/{$prefix}Cd", $node)->item(0)?->textContent;

        // DrctDbtTxInf parsen
        $txNodes = $xpath->query("{$prefix}DrctDbtTxInf", $node);
        $transactions = [];

        foreach ($txNodes as $txNode) {
            $transactions[] = self::parseDirectDebitTransaction($xpath, $txNode, $prefix);
        }

        return new PaymentInstruction(
            paymentInstructionId: $pmtInfId,
            paymentMethod: $pmtMtd,
            requestedCollectionDate: $reqdColltnDt,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            creditorAgent: $creditorAgent,
            transactions: $transactions,
            creditorSchemeId: $cdtrSchmeId,
            chargeBearer: $chrgBr,
            sequenceType: $seqTp,
            localInstrument: $lclInstrm,
            serviceLevel: $svcLvl
        );
    }

    private static function parseDirectDebitTransaction(DOMXPath $xpath, \DOMNode $node, string $prefix): DirectDebitTransaction {
        // PaymentIdentification
        $endToEndId = $xpath->query("{$prefix}PmtId/{$prefix}EndToEndId", $node)->item(0)?->textContent ?? 'NOTPROVIDED';
        $instrId = $xpath->query("{$prefix}PmtId/{$prefix}InstrId", $node)->item(0)?->textContent;
        $uetr = $xpath->query("{$prefix}PmtId/{$prefix}UETR", $node)->item(0)?->textContent;

        $paymentId = new PaymentIdentification($endToEndId, $instrId, $uetr);

        // Betrag und Währung
        $amtNode = $xpath->query("{$prefix}InstdAmt", $node)->item(0);
        $amount = 0.0;
        $currency = CurrencyCode::Euro;

        if ($amtNode instanceof DOMElement) {
            $amount = (float) $amtNode->textContent;
            $currencyStr = $amtNode->getAttribute('Ccy') ?: 'EUR';
            $currency = CurrencyCode::tryFrom($currencyStr) ?? CurrencyCode::Euro;
        }

        // MandateInformation
        $mndtNode = $xpath->query("{$prefix}DrctDbtTx/{$prefix}MndtRltdInf", $node)->item(0);
        $mandateInfo = self::parseMandateInformation($xpath, $mndtNode, $prefix);

        // Debtor
        $dbtrNode = $xpath->query("{$prefix}Dbtr", $node)->item(0);
        $debtor = self::parsePartyIdentification($xpath, $dbtrNode, $prefix);

        // DebtorAccount
        $dbtrAcctNode = $xpath->query("{$prefix}DbtrAcct", $node)->item(0);
        $debtorAccount = self::parseAccountIdentification($xpath, $dbtrAcctNode, $prefix);

        // DebtorAgent
        $dbtrAgtNode = $xpath->query("{$prefix}DbtrAgt", $node)->item(0);
        $debtorAgent = $dbtrAgtNode ? self::parseFinancialInstitution($xpath, $dbtrAgtNode, $prefix) : null;

        // RemittanceInformation
        $rmtInfNode = $xpath->query("{$prefix}RmtInf", $node)->item(0);
        $remittanceInfo = null;
        if ($rmtInfNode) {
            $ustrdNodes = $xpath->query("{$prefix}Ustrd", $rmtInfNode);
            $ustrd = [];
            foreach ($ustrdNodes as $ustrdNode) {
                $ustrd[] = $ustrdNode->textContent;
            }
            if (!empty($ustrd)) {
                $remittanceInfo = new RemittanceInformation($ustrd);
            }
        }

        return new DirectDebitTransaction(
            paymentId: $paymentId,
            amount: $amount,
            currency: $currency,
            mandateInfo: $mandateInfo,
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            debtorAgent: $debtorAgent,
            remittanceInformation: $remittanceInfo
        );
    }

    private static function parseMandateInformation(DOMXPath $xpath, ?\DOMNode $node, string $prefix): MandateInformation {
        if (!$node) {
            return MandateInformation::create('UNKNOWN', new DateTimeImmutable());
        }

        $mndtId = $xpath->query("{$prefix}MndtId", $node)->item(0)?->textContent ?? 'UNKNOWN';
        $dtOfSgntrStr = $xpath->query("{$prefix}DtOfSgntr", $node)->item(0)?->textContent;
        $dtOfSgntr = $dtOfSgntrStr ? new DateTimeImmutable($dtOfSgntrStr) : new DateTimeImmutable();

        $amdmntIndStr = $xpath->query("{$prefix}AmdmntInd", $node)->item(0)?->textContent;
        $amdmntInd = $amdmntIndStr !== null ? ($amdmntIndStr === 'true') : null;

        $orgnlMndtId = $xpath->query("{$prefix}AmdmntInfDtls/{$prefix}OrgnlMndtId", $node)->item(0)?->textContent;
        $orgnlCdtrSchmeId = $xpath->query("{$prefix}AmdmntInfDtls/{$prefix}OrgnlCdtrSchmeId/{$prefix}Id/{$prefix}PrvtId/{$prefix}Othr/{$prefix}Id", $node)->item(0)?->textContent;

        return new MandateInformation(
            mandateId: $mndtId,
            dateOfSignature: $dtOfSgntr,
            amendmentIndicator: $amdmntInd,
            originalMandateId: $orgnlMndtId,
            originalCreditorSchemeId: $orgnlCdtrSchmeId
        );
    }

    private static function parsePartyIdentification(DOMXPath $xpath, ?\DOMNode $node, string $prefix): PartyIdentification {
        if (!$node) {
            return new PartyIdentification(name: 'Unknown');
        }

        $name = $xpath->query("{$prefix}Nm", $node)->item(0)?->textContent;

        // PostalAddress
        $pstlAdrNode = $xpath->query("{$prefix}PstlAdr", $node)->item(0);
        $postalAddress = null;

        if ($pstlAdrNode) {
            $strtNm = $xpath->query("{$prefix}StrtNm", $pstlAdrNode)->item(0)?->textContent;
            $bldgNb = $xpath->query("{$prefix}BldgNb", $pstlAdrNode)->item(0)?->textContent;
            $pstCd = $xpath->query("{$prefix}PstCd", $pstlAdrNode)->item(0)?->textContent;
            $twnNm = $xpath->query("{$prefix}TwnNm", $pstlAdrNode)->item(0)?->textContent;
            $ctryStr = $xpath->query("{$prefix}Ctry", $pstlAdrNode)->item(0)?->textContent;
            $ctry = $ctryStr ? CountryCode::tryFrom($ctryStr) : null;

            $postalAddress = new PostalAddress(
                streetName: $strtNm,
                buildingNumber: $bldgNb,
                postCode: $pstCd,
                townName: $twnNm,
                country: $ctry
            );
        }

        return new PartyIdentification(name: $name, postalAddress: $postalAddress);
    }

    private static function parseAccountIdentification(DOMXPath $xpath, ?\DOMNode $node, string $prefix): AccountIdentification {
        if (!$node) {
            return new AccountIdentification();
        }

        $iban = $xpath->query("{$prefix}Id/{$prefix}IBAN", $node)->item(0)?->textContent;
        $other = $xpath->query("{$prefix}Id/{$prefix}Othr/{$prefix}Id", $node)->item(0)?->textContent;
        $ccyStr = $xpath->query("{$prefix}Ccy", $node)->item(0)?->textContent;
        $ccy = $ccyStr ? CurrencyCode::tryFrom($ccyStr) : null;

        return new AccountIdentification(iban: $iban, other: $other, currency: $ccy);
    }

    private static function parseFinancialInstitution(DOMXPath $xpath, ?\DOMNode $node, string $prefix): FinancialInstitution {
        if (!$node) {
            return new FinancialInstitution();
        }

        $bic = $xpath->query("{$prefix}FinInstnId/{$prefix}BICFI", $node)->item(0)?->textContent;
        if (!$bic) {
            $bic = $xpath->query("{$prefix}FinInstnId/{$prefix}BIC", $node)->item(0)?->textContent;
        }

        $name = $xpath->query("{$prefix}FinInstnId/{$prefix}Nm", $node)->item(0)?->textContent;

        return new FinancialInstitution(bic: $bic, name: $name);
    }
}
