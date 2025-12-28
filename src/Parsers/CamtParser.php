<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Contracts\Abstracts\Common\Banking\Camt\CamtDocumentAbstract;
use CommonToolkit\Entities\Common\Banking\Camt\Balance as CamtBalance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction as Camt052Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction as Camt054Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CamtValidator;
use CommonToolkit\Helper\FileSystem\File;
use DateTimeImmutable;
use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Generischer Parser für CAMT-Dokumente (052, 053, 054).
 * 
 * Erkennt automatisch den CAMT-Typ und gibt das entsprechende
 * Document-Objekt zurück.
 * 
 * @package CommonToolkit\Parsers
 */
class CamtParser {
    /**
     * Parst ein beliebiges CAMT-Dokument.
     * 
     * @param string $xmlContent XML-Inhalt
     * @param bool $validate Optional: XSD-Validierung durchführen
     * @return CamtDocumentAbstract|Camt053Document Geparstes Dokument
     * @throws RuntimeException Bei ungültigem XML oder unbekanntem Typ
     */
    public static function parse(string $xmlContent, bool $validate = false): CamtDocumentAbstract|Camt053Document {
        $type = CamtType::fromXml($xmlContent);

        if ($type === null) {
            throw new RuntimeException('Unbekannter CAMT-Dokumenttyp');
        }

        // Optional: XSD-Validierung
        if ($validate) {
            $validationResult = CamtValidator::validate($xmlContent, $type);
            if (!$validationResult->isValid()) {
                throw new RuntimeException(
                    "XSD-Validierung fehlgeschlagen: " . $validationResult->getFirstError()
                );
            }
        }

        return match ($type) {
            CamtType::CAMT052 => self::parseCamt052($xmlContent),
            CamtType::CAMT053 => Camt053Parser::fromXml($xmlContent),
            CamtType::CAMT054 => self::parseCamt054($xmlContent),
        };
    }

    /**
     * Parst ein CAMT.052 Dokument.
     */
    public static function parseCamt052(string $xmlContent): Camt052Document {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new RuntimeException("Ungültiges XML-Dokument: " . ($errors[0]->message ?? 'Unbekannter Fehler'));
        }

        $xpath = new DOMXPath($dom);
        $namespace = self::detectNamespace($dom);
        $xpath->registerNamespace('ns', $namespace);

        // Report-Block finden
        $rptNode = $xpath->query('//ns:Rpt')->item(0);
        if (!$rptNode) {
            throw new RuntimeException("Kein <Rpt>-Block gefunden.");
        }

        // Basisdaten
        $id = $xpath->evaluate('string(ns:Id)', $rptNode);
        $creationDateTime = $xpath->evaluate('string(ns:CreDtTm)', $rptNode);
        $accountIban = $xpath->evaluate('string(ns:Acct/ns:Id/ns:IBAN)', $rptNode);
        $accountOther = $xpath->evaluate('string(ns:Acct/ns:Id/ns:Othr/ns:Id)', $rptNode);
        $accountIdentifier = !empty($accountIban) ? $accountIban : $accountOther;
        $currency = $xpath->evaluate('string(ns:Acct/ns:Ccy)', $rptNode) ?: 'EUR';

        $accountOwner = $xpath->evaluate('string(ns:Acct/ns:Ownr/ns:Nm)', $rptNode) ?: null;
        $servicerBic = $xpath->evaluate('string(ns:Acct/ns:Svcr/ns:FinInstnId/ns:BICFI)', $rptNode)
            ?: $xpath->evaluate('string(ns:Acct/ns:Svcr/ns:FinInstnId/ns:BIC)', $rptNode) ?: null;
        $messageId = $xpath->evaluate('string(//ns:GrpHdr/ns:MsgId)') ?: null;
        $sequenceNumber = $xpath->evaluate('string(ns:ElctrncSeqNb)', $rptNode) ?: null;

        // Salden parsen
        $openingBalance = null;
        $closingBalance = null;

        foreach ($xpath->query('ns:Bal', $rptNode) as $balNode) {
            $balance = self::parseBalance($xpath, $balNode, $currency);
            if ($balance === null) continue;

            if ($balance->isOpeningBalance()) {
                $openingBalance = $balance;
            } elseif ($balance->isClosingBalance()) {
                $closingBalance = $balance;
            }
        }

        $document = new Camt052Document(
            id: $id,
            creationDateTime: $creationDateTime,
            accountIdentifier: $accountIdentifier,
            currency: $currency,
            accountOwner: $accountOwner,
            servicerBic: $servicerBic,
            messageId: $messageId,
            sequenceNumber: $sequenceNumber,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        // Transaktionen parsen
        foreach ($xpath->query('ns:Ntry', $rptNode) as $entry) {
            $transaction = self::parseTransaction052($xpath, $entry, $currency);
            if ($transaction !== null) {
                $document->addEntry($transaction);
            }
        }

        libxml_clear_errors();
        return $document;
    }

    /**
     * Parst ein CAMT.054 Dokument.
     */
    public static function parseCamt054(string $xmlContent): Camt054Document {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new RuntimeException("Ungültiges XML-Dokument: " . ($errors[0]->message ?? 'Unbekannter Fehler'));
        }

        $xpath = new DOMXPath($dom);
        $namespace = self::detectNamespace($dom);
        $xpath->registerNamespace('ns', $namespace);

        // Notification-Block finden
        $ntfctnNode = $xpath->query('//ns:Ntfctn')->item(0);
        if (!$ntfctnNode) {
            throw new RuntimeException("Kein <Ntfctn>-Block gefunden.");
        }

        // Basisdaten
        $id = $xpath->evaluate('string(ns:Id)', $ntfctnNode);
        $creationDateTime = $xpath->evaluate('string(//ns:GrpHdr/ns:CreDtTm)');
        $accountIban = $xpath->evaluate('string(ns:Acct/ns:Id/ns:IBAN)', $ntfctnNode);
        $accountOther = $xpath->evaluate('string(ns:Acct/ns:Id/ns:Othr/ns:Id)', $ntfctnNode);
        $accountIdentifier = !empty($accountIban) ? $accountIban : $accountOther;
        $currency = $xpath->evaluate('string(ns:Acct/ns:Ccy)', $ntfctnNode) ?: 'EUR';

        $accountOwnerBic = $xpath->evaluate('string(ns:Acct/ns:Ownr/ns:Id/ns:OrgId/ns:AnyBIC)', $ntfctnNode) ?: null;
        $messageId = $xpath->evaluate('string(//ns:GrpHdr/ns:MsgId)') ?: null;

        $document = new Camt054Document(
            id: $id,
            creationDateTime: $creationDateTime ?: 'now',
            accountIdentifier: $accountIdentifier,
            currency: $currency,
            accountOwner: $accountOwnerBic,
            messageId: $messageId
        );

        // Transaktionen parsen
        foreach ($xpath->query('ns:Ntry', $ntfctnNode) as $entry) {
            $transaction = self::parseTransaction054($xpath, $entry, $currency);
            if ($transaction !== null) {
                $document->addEntry($transaction);
            }
        }

        libxml_clear_errors();
        return $document;
    }

    /**
     * Erkennt den Namespace im Dokument.
     */
    private static function detectNamespace(DOMDocument $dom): string {
        $root = $dom->documentElement;

        if ($root) {
            $ns = $root->namespaceURI ?? $root->getAttribute('xmlns');
            if (!empty($ns)) {
                return $ns;
            }

            // Suche nach xmlns:n0 o.ä.
            foreach ($root->attributes as $attr) {
                if (str_starts_with($attr->name, 'xmlns') && str_contains($attr->value, 'camt.05')) {
                    return $attr->value;
                }
            }
        }

        return 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02';
    }

    /**
     * Parst einen Balance-Block.
     */
    private static function parseBalance(DOMXPath $xpath, DOMNode $balNode, string $defaultCurrency): ?CamtBalance {
        $amountStr = $xpath->evaluate('string(ns:Amt)', $balNode);
        if (empty($amountStr)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $amountStr);
        $currency = $xpath->evaluate('string(ns:Amt/@Ccy)', $balNode) ?: $defaultCurrency;
        $creditDebitStr = $xpath->evaluate('string(ns:CdtDbtInd)', $balNode);
        $dateStr = $xpath->evaluate('string(ns:Dt/ns:Dt)', $balNode)
            ?: $xpath->evaluate('string(ns:Dt/ns:DtTm)', $balNode);
        $balanceType = $xpath->evaluate('string(ns:Tp/ns:CdOrPrtry/ns:Cd)', $balNode);
        $balanceSubType = $xpath->evaluate('string(ns:Tp/ns:SubTp/ns:Cd)', $balNode) ?: null;

        if (empty($dateStr)) {
            return null;
        }

        $creditDebit = match (strtoupper($creditDebitStr)) {
            'CRDT' => CreditDebit::CREDIT,
            'DBIT' => CreditDebit::DEBIT,
            default => CreditDebit::CREDIT
        };

        return new CamtBalance(
            creditDebit: $creditDebit,
            date: $dateStr,
            currency: $currency,
            amount: $amount,
            type: $balanceType ?: 'CLBD',
            subType: $balanceSubType
        );
    }

    /**
     * Parst einen Entry-Block für CAMT.052.
     */
    private static function parseTransaction052(DOMXPath $xpath, DOMNode $entry, string $defaultCurrency): ?Camt052Transaction {
        $amountStr = $xpath->evaluate('string(ns:Amt)', $entry);
        if (empty($amountStr)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $amountStr);
        $entryCcy = $xpath->evaluate('string(ns:Amt/@Ccy)', $entry) ?: $defaultCurrency;
        $creditDebitStr = $xpath->evaluate('string(ns:CdtDbtInd)', $entry);
        $reversalIndicator = $xpath->evaluate('string(ns:RvslInd)', $entry);
        $status = $xpath->evaluate('string(ns:Sts/ns:Cd)', $entry) ?: 'BOOK';

        $bookingDateStr = $xpath->evaluate('string(ns:BookgDt/ns:Dt)', $entry)
            ?: $xpath->evaluate('string(ns:BookgDt/ns:DtTm)', $entry);
        $valutaDateStr = $xpath->evaluate('string(ns:ValDt/ns:Dt)', $entry)
            ?: $xpath->evaluate('string(ns:ValDt/ns:DtTm)', $entry);

        if (empty($bookingDateStr)) {
            return null;
        }

        $isReversal = strtolower($reversalIndicator) === 'true';
        $creditDebit = match (strtoupper($creditDebitStr)) {
            'CRDT' => $isReversal ? CreditDebit::DEBIT : CreditDebit::CREDIT,
            'DBIT' => $isReversal ? CreditDebit::CREDIT : CreditDebit::DEBIT,
            default => CreditDebit::CREDIT
        };

        $currency = CurrencyCode::tryFrom(strtoupper($entryCcy)) ?? CurrencyCode::Euro;

        $entryRef = $xpath->evaluate('string(ns:NtryRef)', $entry) ?: null;
        $acctSvcrRef = $xpath->evaluate('string(ns:AcctSvcrRef)', $entry) ?: null;

        // Bank Transaction Code
        $bankTxCode = $xpath->evaluate('string(ns:BkTxCd/ns:Prtry/ns:Cd)', $entry) ?: null;
        $domainCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Cd)', $entry) ?: null;
        $familyCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Fmly/ns:Cd)', $entry) ?: null;
        $subFamilyCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Fmly/ns:SubFmlyCd)', $entry) ?: null;

        // Purpose und Additional Info
        $txDtls = $xpath->query('ns:NtryDtls/ns:TxDtls', $entry)->item(0);
        $purpose = null;
        $purposeCode = null;
        $additionalInfo = null;
        $returnReason = null;

        if ($txDtls) {
            $purpose = $xpath->evaluate('string(ns:Purp/ns:Prtry)', $txDtls) ?: null;
            $purposeCode = $xpath->evaluate('string(ns:Purp/ns:Cd)', $txDtls) ?: null;
            $additionalInfo = $xpath->evaluate('string(ns:AddtlTxInf)', $txDtls) ?: null;
            $returnReason = $xpath->evaluate('string(ns:RtrInf/ns:Rsn/ns:Cd)', $txDtls) ?: null;
        }

        if ($additionalInfo === null) {
            $additionalInfo = $xpath->evaluate('string(ns:AddtlNtryInf)', $entry) ?: null;
        }

        return new Camt052Transaction(
            bookingDate: new DateTimeImmutable($bookingDateStr),
            valutaDate: !empty($valutaDateStr) ? new DateTimeImmutable($valutaDateStr) : null,
            amount: $amount,
            currency: $currency,
            creditDebit: $creditDebit,
            entryReference: $entryRef,
            accountServicerReference: $acctSvcrRef,
            status: $status,
            isReversal: $isReversal,
            purpose: $purpose,
            purposeCode: $purposeCode,
            additionalInfo: $additionalInfo,
            bankTransactionCode: $bankTxCode,
            domainCode: $domainCode,
            familyCode: $familyCode,
            subFamilyCode: $subFamilyCode,
            returnReason: $returnReason
        );
    }

    /**
     * Parst einen Entry-Block für CAMT.054.
     */
    private static function parseTransaction054(DOMXPath $xpath, DOMNode $entry, string $defaultCurrency): ?Camt054Transaction {
        $amountStr = $xpath->evaluate('string(ns:Amt)', $entry);
        if (empty($amountStr)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $amountStr);
        $entryCcy = $xpath->evaluate('string(ns:Amt/@Ccy)', $entry) ?: $defaultCurrency;
        $creditDebitStr = $xpath->evaluate('string(ns:CdtDbtInd)', $entry);
        $reversalIndicator = $xpath->evaluate('string(ns:RvslInd)', $entry);
        $status = $xpath->evaluate('string(ns:Sts/ns:Cd)', $entry) ?: 'BOOK';

        $bookingDateStr = $xpath->evaluate('string(ns:BookgDt/ns:DtTm)', $entry)
            ?: $xpath->evaluate('string(ns:BookgDt/ns:Dt)', $entry);
        $valutaDateStr = $xpath->evaluate('string(ns:ValDt/ns:Dt)', $entry)
            ?: $xpath->evaluate('string(ns:ValDt/ns:DtTm)', $entry);

        if (empty($bookingDateStr)) {
            return null;
        }

        $isReversal = strtolower($reversalIndicator) === 'true';
        $creditDebit = match (strtoupper($creditDebitStr)) {
            'CRDT' => $isReversal ? CreditDebit::DEBIT : CreditDebit::CREDIT,
            'DBIT' => $isReversal ? CreditDebit::CREDIT : CreditDebit::DEBIT,
            default => CreditDebit::CREDIT
        };

        $currency = CurrencyCode::tryFrom(strtoupper($entryCcy)) ?? CurrencyCode::Euro;

        $entryRef = $xpath->evaluate('string(ns:NtryRef)', $entry) ?: null;
        $acctSvcrRef = $xpath->evaluate('string(ns:AcctSvcrRef)', $entry) ?: null;

        // Bank Transaction Code (proprietär)
        $bankTxCode = $xpath->evaluate('string(ns:BkTxCd/ns:Prtry/ns:Cd)', $entry) ?: null;

        // ISO 20022 Domain/Family/SubFamily Codes
        $domainCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Cd)', $entry) ?: null;
        $familyCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Fmly/ns:Cd)', $entry) ?: null;
        $subFamilyCode = $xpath->evaluate('string(ns:BkTxCd/ns:Domn/ns:Fmly/ns:SubFmlyCd)', $entry) ?: null;

        // TxDtls
        $txDtls = $xpath->query('ns:NtryDtls/ns:TxDtls', $entry)->item(0);
        $instructionId = null;
        $endToEndId = null;
        $remittanceInfo = null;
        $purposeCode = null;
        $returnReason = null;
        $localInstrumentCode = null;
        $instructingAgentBic = null;
        $instructedAgentBic = null;
        $debtorAgentBic = null;
        $creditorAgentBic = null;

        if ($txDtls) {
            $instructionId = $xpath->evaluate('string(ns:Refs/ns:InstrId)', $txDtls) ?: null;
            $endToEndId = $xpath->evaluate('string(ns:Refs/ns:EndToEndId)', $txDtls) ?: null;
            $remittanceInfo = $xpath->evaluate('string(ns:RmtInf/ns:Ustrd)', $txDtls) ?: null;
            $purposeCode = $xpath->evaluate('string(ns:Purp/ns:Cd)', $txDtls) ?: null;
            $returnReason = $xpath->evaluate('string(ns:RtrInf/ns:Rsn/ns:Cd)', $txDtls) ?: null;
            $localInstrumentCode = $xpath->evaluate('string(ns:LclInstrm/ns:Prtry)', $txDtls)
                ?: $xpath->evaluate('string(ns:LclInstrm/ns:Cd)', $txDtls) ?: null;

            $instructingAgentBic = $xpath->evaluate('string(ns:RltdAgts/ns:InstgAgt/ns:FinInstnId/ns:BICFI)', $txDtls) ?: null;
            $instructedAgentBic = $xpath->evaluate('string(ns:RltdAgts/ns:InstdAgt/ns:FinInstnId/ns:BICFI)', $txDtls) ?: null;
            $debtorAgentBic = $xpath->evaluate('string(ns:RltdAgts/ns:DbtrAgt/ns:FinInstnId/ns:BICFI)', $txDtls) ?: null;
            $creditorAgentBic = $xpath->evaluate('string(ns:RltdAgts/ns:CdtrAgt/ns:FinInstnId/ns:BICFI)', $txDtls) ?: null;
        }

        return new Camt054Transaction(
            bookingDate: new DateTimeImmutable($bookingDateStr),
            valutaDate: !empty($valutaDateStr) ? new DateTimeImmutable($valutaDateStr) : null,
            amount: $amount,
            currency: $currency,
            creditDebit: $creditDebit,
            entryReference: $entryRef,
            accountServicerReference: $acctSvcrRef,
            status: $status,
            isReversal: $isReversal,
            instructionId: $instructionId,
            endToEndId: $endToEndId,
            remittanceInfo: $remittanceInfo,
            purposeCode: $purposeCode,
            bankTransactionCode: $bankTxCode,
            domainCode: $domainCode,
            familyCode: $familyCode,
            subFamilyCode: $subFamilyCode,
            returnReason: $returnReason,
            localInstrumentCode: $localInstrumentCode,
            instructingAgentBic: $instructingAgentBic,
            instructedAgentBic: $instructedAgentBic,
            debtorAgentBic: $debtorAgentBic,
            creditorAgentBic: $creditorAgentBic
        );
    }

    /**
     * Gibt den erkannten CAMT-Typ zurück.
     */
    public static function detectType(string $xmlContent): ?CamtType {
        return CamtType::fromXml($xmlContent);
    }

    /**
     * Parst eine CAMT-Datei direkt.
     * 
     * @param string $filePath Pfad zur XML-Datei
     * @param bool $validate Optional: XSD-Validierung durchführen
     * @return CamtDocumentAbstract|Camt053Document Geparstes Dokument
     */
    public static function parseFile(string $filePath, bool $validate = false): CamtDocumentAbstract|Camt053Document {
        $content = File::read($filePath);

        return self::parse($content, $validate);
    }
}
