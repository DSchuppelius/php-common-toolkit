<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Pain001Parser.php
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
use CommonToolkit\Entities\Common\Banking\Pain\Type001\CreditTransferTransaction;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\Document;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\GroupHeader;
use CommonToolkit\Entities\Common\Banking\Pain\Type001\PaymentInstruction;
use CommonToolkit\Enums\Common\Banking\ChargesCode;
use CommonToolkit\Enums\Common\Banking\PaymentMethod;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Parser für pain.001 XML-Dokumente (Customer Credit Transfer Initiation).
 * 
 * Unterstützt die Versionen:
 * - pain.001.001.03 (ältere Version)
 * - pain.001.001.09 (SEPA 2019)
 * - pain.001.001.12 (aktuelle Version, ISO 20022 2024)
 * 
 * @package CommonToolkit\Parsers
 */
class Pain001Parser {
    private const NAMESPACES = [
        'pain001v03' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
        'pain001v09' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09',
        'pain001v12' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.12',
    ];

    /**
     * Parst ein pain.001 XML-Dokument.
     * 
     * @param string $xmlContent Der XML-Inhalt
     * @return Document Das geparste Dokument
     * @throws RuntimeException Bei ungültigem XML oder fehlendem Content
     */
    public static function fromXml(string $xmlContent): Document {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new RuntimeException("Ungültiges XML-Dokument: " . ($errors[0]->message ?? 'Unbekannter Fehler'));
        }

        $xpath = new DOMXPath($dom);

        // Namespace automatisch erkennen
        $namespace = self::detectNamespace($dom);
        $useNamespace = !empty($namespace);

        if ($useNamespace) {
            $xpath->registerNamespace('ns', $namespace);
        }

        // Customer Credit Transfer Initiation Block finden
        $cstmrCdtTrfInitnNode = null;
        if ($useNamespace) {
            $cstmrCdtTrfInitnNode = $xpath->query('//ns:CstmrCdtTrfInitn')->item(0);
        }
        if (!$cstmrCdtTrfInitnNode) {
            // Fallback ohne Namespace
            $cstmrCdtTrfInitnNode = $xpath->query('//CstmrCdtTrfInitn')->item(0);
            $useNamespace = false;
        }
        if (!$cstmrCdtTrfInitnNode) {
            throw new RuntimeException("Kein <CstmrCdtTrfInitn>-Block gefunden.");
        }

        $prefix = $useNamespace ? 'ns:' : '';

        // GroupHeader parsen
        $grpHdrNode = $xpath->query("{$prefix}GrpHdr", $cstmrCdtTrfInitnNode)->item(0);
        if (!$grpHdrNode) {
            throw new RuntimeException("Kein <GrpHdr>-Block gefunden.");
        }

        $groupHeader = self::parseGroupHeader($xpath, $grpHdrNode, $prefix);

        // PaymentInstructions parsen
        $pmtInfNodes = $xpath->query("{$prefix}PmtInf", $cstmrCdtTrfInitnNode);
        $paymentInstructions = [];

        foreach ($pmtInfNodes as $pmtInfNode) {
            $paymentInstructions[] = self::parsePaymentInstruction($xpath, $pmtInfNode, $prefix);
        }

        return new Document($groupHeader, $paymentInstructions);
    }

    /**
     * Erkennt den Namespace des Dokuments.
     */
    private static function detectNamespace(DOMDocument $dom): string {
        $rootElement = $dom->documentElement;

        // Prüfe bekannte Namespaces
        foreach (self::NAMESPACES as $namespace) {
            if ($rootElement->namespaceURI === $namespace) {
                return $namespace;
            }
            if ($rootElement->lookupNamespaceUri('') === $namespace) {
                return $namespace;
            }
        }

        // Suche nach pain.001 im Namespace-URI
        $nsUri = $rootElement->namespaceURI ?? '';
        if (str_contains($nsUri, 'pain.001')) {
            return $nsUri;
        }

        return '';
    }

    /**
     * Parst den GroupHeader.
     */
    private static function parseGroupHeader(DOMXPath $xpath, DOMNode $node, string $prefix): GroupHeader {
        $messageId = $xpath->evaluate("string({$prefix}MsgId)", $node);
        $creationDateTimeStr = $xpath->evaluate("string({$prefix}CreDtTm)", $node);
        $numberOfTransactions = (int) $xpath->evaluate("string({$prefix}NbOfTxs)", $node);
        $controlSumStr = $xpath->evaluate("string({$prefix}CtrlSum)", $node);

        $creationDateTime = !empty($creationDateTimeStr)
            ? new DateTimeImmutable($creationDateTimeStr)
            : new DateTimeImmutable();

        $controlSum = !empty($controlSumStr) ? (float) $controlSumStr : null;

        // InitiatingParty parsen
        $initgPtyNode = $xpath->query("{$prefix}InitgPty", $node)->item(0);
        $initiatingParty = $initgPtyNode
            ? self::parsePartyIdentification($xpath, $initgPtyNode, $prefix)
            : new PartyIdentification(name: 'Unknown');

        // ForwardingAgent parsen (optional)
        $fwdgAgtNode = $xpath->query("{$prefix}FwdgAgt", $node)->item(0);
        $forwardingAgent = $fwdgAgtNode
            ? self::parseFinancialInstitution($xpath, $fwdgAgtNode, $prefix)
            : null;

        return new GroupHeader(
            messageId: $messageId,
            creationDateTime: $creationDateTime,
            numberOfTransactions: $numberOfTransactions,
            initiatingParty: $initiatingParty,
            controlSum: $controlSum,
            forwardingAgent: $forwardingAgent
        );
    }

    /**
     * Parst eine PaymentInstruction.
     */
    private static function parsePaymentInstruction(DOMXPath $xpath, DOMNode $node, string $prefix): PaymentInstruction {
        $pmtInfId = $xpath->evaluate("string({$prefix}PmtInfId)", $node);
        $pmtMtdStr = $xpath->evaluate("string({$prefix}PmtMtd)", $node);
        $reqdExctnDtStr = $xpath->evaluate("string({$prefix}ReqdExctnDt/{$prefix}Dt)", $node)
            ?: $xpath->evaluate("string({$prefix}ReqdExctnDt)", $node);
        $chrgBrStr = $xpath->evaluate("string({$prefix}ChrgBr)", $node);

        $paymentMethod = PaymentMethod::fromString($pmtMtdStr ?: 'TRF');
        $requestedExecutionDate = !empty($reqdExctnDtStr)
            ? new DateTimeImmutable($reqdExctnDtStr)
            : new DateTimeImmutable();
        $chargesCode = ChargesCode::fromString($chrgBrStr ?: 'SLEV');

        // Debtor parsen
        $dbtrNode = $xpath->query("{$prefix}Dbtr", $node)->item(0);
        $debtor = $dbtrNode
            ? self::parsePartyIdentification($xpath, $dbtrNode, $prefix)
            : new PartyIdentification(name: 'Unknown');

        // DebtorAccount parsen
        $dbtrAcctNode = $xpath->query("{$prefix}DbtrAcct", $node)->item(0);
        $debtorAccount = $dbtrAcctNode
            ? self::parseAccountIdentification($xpath, $dbtrAcctNode, $prefix)
            : new AccountIdentification(iban: '');

        // DebtorAgent parsen (optional)
        $dbtrAgtNode = $xpath->query("{$prefix}DbtrAgt", $node)->item(0);
        $debtorAgent = $dbtrAgtNode
            ? self::parseFinancialInstitution($xpath, $dbtrAgtNode, $prefix)
            : null;

        // CreditTransferTransactions parsen
        $cdtTrfTxInfNodes = $xpath->query("{$prefix}CdtTrfTxInf", $node);
        $transactions = [];

        foreach ($cdtTrfTxInfNodes as $txnNode) {
            $transactions[] = self::parseCreditTransferTransaction($xpath, $txnNode, $prefix);
        }

        return new PaymentInstruction(
            paymentInstructionId: $pmtInfId,
            paymentMethod: $paymentMethod,
            requestedExecutionDate: $requestedExecutionDate,
            debtor: $debtor,
            debtorAccount: $debtorAccount,
            debtorAgent: $debtorAgent ?? new FinancialInstitution(),
            transactions: $transactions,
            chargeBearer: $chargesCode
        );
    }

    /**
     * Parst eine CreditTransferTransaction.
     */
    private static function parseCreditTransferTransaction(DOMXPath $xpath, DOMNode $node, string $prefix): CreditTransferTransaction {
        // PaymentIdentification parsen
        $pmtIdNode = $xpath->query("{$prefix}PmtId", $node)->item(0);
        $paymentIdentification = $pmtIdNode
            ? self::parsePaymentIdentification($xpath, $pmtIdNode, $prefix)
            : PaymentIdentification::create('unknown');

        // Betrag und Währung parsen
        $amtNode = $xpath->query("{$prefix}Amt/{$prefix}InstdAmt", $node)->item(0);
        if (!$amtNode) {
            $amtNode = $xpath->query("{$prefix}Amt", $node)->item(0);
        }

        $amount = 0.0;
        $currencyStr = 'EUR';

        if ($amtNode instanceof \DOMElement) {
            $amount = (float) $amtNode->textContent;
            $currencyStr = $amtNode->getAttribute('Ccy') ?: 'EUR';
        }

        $currency = CurrencyCode::tryFrom($currencyStr) ?? CurrencyCode::Euro;

        // Creditor parsen
        $cdtrNode = $xpath->query("{$prefix}Cdtr", $node)->item(0);
        $creditor = $cdtrNode
            ? self::parsePartyIdentification($xpath, $cdtrNode, $prefix)
            : new PartyIdentification(name: 'Unknown');

        // CreditorAccount parsen
        $cdtrAcctNode = $xpath->query("{$prefix}CdtrAcct", $node)->item(0);
        $creditorAccount = $cdtrAcctNode
            ? self::parseAccountIdentification($xpath, $cdtrAcctNode, $prefix)
            : null;

        // CreditorAgent parsen (optional)
        $cdtrAgtNode = $xpath->query("{$prefix}CdtrAgt", $node)->item(0);
        $creditorAgent = $cdtrAgtNode
            ? self::parseFinancialInstitution($xpath, $cdtrAgtNode, $prefix)
            : null;

        // RemittanceInformation parsen (optional)
        $rmtInfNode = $xpath->query("{$prefix}RmtInf", $node)->item(0);
        $remittanceInformation = $rmtInfNode
            ? self::parseRemittanceInformation($xpath, $rmtInfNode, $prefix)
            : null;

        return new CreditTransferTransaction(
            paymentId: $paymentIdentification,
            amount: $amount,
            currency: $currency,
            creditor: $creditor,
            creditorAccount: $creditorAccount,
            creditorAgent: $creditorAgent,
            remittanceInformation: $remittanceInformation
        );
    }

    /**
     * Parst eine PartyIdentification.
     */
    private static function parsePartyIdentification(DOMXPath $xpath, DOMNode $node, string $prefix): PartyIdentification {
        $name = $xpath->evaluate("string({$prefix}Nm)", $node);
        $bic = $xpath->evaluate("string({$prefix}Id/{$prefix}OrgId/{$prefix}AnyBIC)", $node);
        $lei = $xpath->evaluate("string({$prefix}Id/{$prefix}OrgId/{$prefix}LEI)", $node);
        $countryOfResidence = $xpath->evaluate("string({$prefix}CtryOfRes)", $node);

        // PostalAddress parsen (optional)
        $pstlAdrNode = $xpath->query("{$prefix}PstlAdr", $node)->item(0);
        $postalAddress = $pstlAdrNode
            ? self::parsePostalAddress($xpath, $pstlAdrNode, $prefix)
            : null;

        $countryCode = null;
        if (!empty($countryOfResidence)) {
            $countryCode = CountryCode::tryFrom($countryOfResidence);
        }

        return new PartyIdentification(
            name: !empty($name) ? $name : null,
            postalAddress: $postalAddress,
            bic: !empty($bic) ? $bic : null,
            lei: !empty($lei) ? $lei : null,
            countryOfResidence: $countryCode
        );
    }

    /**
     * Parst eine PostalAddress.
     */
    private static function parsePostalAddress(DOMXPath $xpath, DOMNode $node, string $prefix): PostalAddress {
        $streetName = $xpath->evaluate("string({$prefix}StrtNm)", $node);
        $buildingNumber = $xpath->evaluate("string({$prefix}BldgNb)", $node);
        $postCode = $xpath->evaluate("string({$prefix}PstCd)", $node);
        $townName = $xpath->evaluate("string({$prefix}TwnNm)", $node);
        $countryStr = $xpath->evaluate("string({$prefix}Ctry)", $node);

        $country = !empty($countryStr) ? CountryCode::tryFrom($countryStr) : null;

        // Address lines
        $addressLines = [];
        $adrLineNodes = $xpath->query("{$prefix}AdrLine", $node);
        foreach ($adrLineNodes as $lineNode) {
            $addressLines[] = $lineNode->textContent;
        }

        return new PostalAddress(
            streetName: !empty($streetName) ? $streetName : null,
            buildingNumber: !empty($buildingNumber) ? $buildingNumber : null,
            postCode: !empty($postCode) ? $postCode : null,
            townName: !empty($townName) ? $townName : null,
            country: $country,
            addressLines: $addressLines
        );
    }

    /**
     * Parst eine AccountIdentification.
     */
    private static function parseAccountIdentification(DOMXPath $xpath, DOMNode $node, string $prefix): AccountIdentification {
        $iban = $xpath->evaluate("string({$prefix}Id/{$prefix}IBAN)", $node);
        $other = $xpath->evaluate("string({$prefix}Id/{$prefix}Othr/{$prefix}Id)", $node);
        $currencyStr = $xpath->evaluate("string({$prefix}Ccy)", $node);

        $currency = !empty($currencyStr) ? (CurrencyCode::tryFrom($currencyStr) ?? null) : null;

        return new AccountIdentification(
            iban: !empty($iban) ? $iban : null,
            other: !empty($other) ? $other : null,
            currency: $currency
        );
    }

    /**
     * Parst eine FinancialInstitution.
     */
    private static function parseFinancialInstitution(DOMXPath $xpath, DOMNode $node, string $prefix): FinancialInstitution {
        $finInstnIdNode = $xpath->query("{$prefix}FinInstnId", $node)->item(0);
        if (!$finInstnIdNode) {
            return new FinancialInstitution();
        }

        $bic = $xpath->evaluate("string({$prefix}BICFI)", $finInstnIdNode)
            ?: $xpath->evaluate("string({$prefix}BIC)", $finInstnIdNode);
        $name = $xpath->evaluate("string({$prefix}Nm)", $finInstnIdNode);
        $lei = $xpath->evaluate("string({$prefix}LEI)", $finInstnIdNode);
        $memberId = $xpath->evaluate("string({$prefix}ClrSysMmbId/{$prefix}MmbId)", $finInstnIdNode);

        return new FinancialInstitution(
            bic: !empty($bic) ? $bic : null,
            name: !empty($name) ? $name : null,
            lei: !empty($lei) ? $lei : null,
            memberId: !empty($memberId) ? $memberId : null
        );
    }

    /**
     * Parst eine PaymentIdentification.
     */
    private static function parsePaymentIdentification(DOMXPath $xpath, DOMNode $node, string $prefix): PaymentIdentification {
        $instrId = $xpath->evaluate("string({$prefix}InstrId)", $node);
        $endToEndId = $xpath->evaluate("string({$prefix}EndToEndId)", $node);
        $uetr = $xpath->evaluate("string({$prefix}UETR)", $node);

        return new PaymentIdentification(
            endToEndId: !empty($endToEndId) ? $endToEndId : 'NOTPROVIDED',
            instructionId: !empty($instrId) ? $instrId : null,
            uetr: !empty($uetr) ? $uetr : null
        );
    }

    /**
     * Parst RemittanceInformation.
     */
    private static function parseRemittanceInformation(DOMXPath $xpath, DOMNode $node, string $prefix): RemittanceInformation {
        // Unstructured (kann mehrere Elemente haben)
        $unstructuredLines = [];
        $ustrdNodes = $xpath->query("{$prefix}Ustrd", $node);
        foreach ($ustrdNodes as $ustrdNode) {
            $unstructuredLines[] = $ustrdNode->textContent;
        }

        // Structured Creditor Reference
        $creditorReference = $xpath->evaluate("string({$prefix}Strd/{$prefix}CdtrRefInf/{$prefix}Ref)", $node);

        return new RemittanceInformation(
            unstructured: $unstructuredLines,
            creditorReference: !empty($creditorReference) ? $creditorReference : null
        );
    }

    /**
     * Prüft, ob eine Datei ein gültiges pain.001 Dokument ist.
     */
    public static function isValid(string $xmlContent): bool {
        try {
            self::fromXml($xmlContent);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }
}
