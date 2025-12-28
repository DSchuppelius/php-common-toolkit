<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053Parser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Parser für CAMT.053 XML-Dokumente (Bank to Customer Statement).
 * 
 * Unterstützt die Versionen:
 * - camt.053.001.02 (ISO 20022)
 * - camt.053.001.08 (neuere Version)
 * 
 * @package CommonToolkit\Parsers
 */
class Camt053Parser {
    private const NAMESPACES = [
        'camt053v02' => 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02',
        'camt053v08' => 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.08',
    ];

    /**
     * Parst ein CAMT.053 XML-Dokument.
     * 
     * @param string $xmlContent Der XML-Inhalt
     * @return Document Das geparste Dokument
     * @throws RuntimeException Bei ungültigem XML oder fehlendem Statement
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

        // Statement-Block finden (mit oder ohne Namespace)
        $stmtNode = null;
        if ($useNamespace) {
            $stmtNode = $xpath->query('//ns:Stmt')->item(0);
        }
        if (!$stmtNode) {
            // Fallback: Suche ohne Namespace
            $stmtNode = $xpath->query('//Stmt')->item(0);
            $useNamespace = false;
        }
        if (!$stmtNode) {
            throw new RuntimeException("Kein <Stmt>-Block gefunden.");
        }

        // Prefix für XPath-Abfragen
        $prefix = $useNamespace ? 'ns:' : '';

        // Statement-Basisdaten
        $statementId       = $xpath->evaluate("string({$prefix}Id)", $stmtNode);
        $creationDateTime  = $xpath->evaluate("string({$prefix}CreDtTm)", $stmtNode);
        $accountIban       = $xpath->evaluate("string({$prefix}Acct/{$prefix}Id/{$prefix}IBAN)", $stmtNode);
        $currency          = $xpath->evaluate("string({$prefix}Acct/{$prefix}Ccy)", $stmtNode) ?: 'EUR';

        // Optionale Felder
        $accountOwner      = $xpath->evaluate("string({$prefix}Acct/{$prefix}Ownr/{$prefix}Nm)", $stmtNode);
        $accountBic        = $xpath->evaluate("string({$prefix}Acct/{$prefix}Svcr/{$prefix}FinInstnId/{$prefix}BIC)", $stmtNode)
            ?: $xpath->evaluate("string({$prefix}Acct/{$prefix}Svcr/{$prefix}FinInstnId/{$prefix}BICFI)", $stmtNode);
        $statementNumber   = $xpath->evaluate("string({$prefix}ElctrncSeqNb)", $stmtNode)
            ?: $xpath->evaluate("string({$prefix}LglSeqNb)", $stmtNode);

        // Salden parsen
        $openingBalance = null;
        $closingBalance = null;
        $availableBalance = null;

        foreach ($xpath->query("{$prefix}Bal", $stmtNode) as $balNode) {
            $balanceType = $xpath->evaluate("string({$prefix}Tp/{$prefix}CdOrPrtry/{$prefix}Cd)", $balNode);
            $balance = self::parseBalance($xpath, $balNode, $currency, $prefix);
            if ($balance === null) {
                continue;
            }

            match ($balanceType) {
                'PRCD', 'OPBD' => $openingBalance = $balance,
                'CLBD', 'CLAV' => $closingBalance = $balance,
                'FWAV' => $availableBalance = $balance,
                default => null
            };
        }

        $document = new Document(
            id: $statementId,
            creationDateTime: $creationDateTime,
            accountIdentifier: $accountIban,
            currency: $currency,
            accountOwner: $accountOwner ?: null,
            servicerBic: $accountBic ?: null,
            messageId: null,
            sequenceNumber: $statementNumber ?: null,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        // Transaktionen parsen
        foreach ($xpath->query("{$prefix}Ntry", $stmtNode) as $entry) {
            $transaction = self::parseTransaction($xpath, $entry, $currency, $prefix);
            if ($transaction !== null) {
                $document->addEntry($transaction);
            }
        }

        libxml_clear_errors();
        return $document;
    }

    /**
     * Erkennt den verwendeten Namespace im Dokument.
     */
    private static function detectNamespace(DOMDocument $dom): string {
        $root = $dom->documentElement;

        if ($root) {
            $ns = $root->namespaceURI ?? $root->getAttribute('xmlns');
            if (!empty($ns)) {
                return $ns;
            }
        }

        // Fallback auf Standard-Namespace
        return self::NAMESPACES['camt053v02'];
    }

    /**
     * Parst einen Balance-Block.
     */
    private static function parseBalance(DOMXPath $xpath, DOMNode $balNode, string $defaultCurrency, string $prefix = 'ns:'): ?Balance {
        $amountStr = $xpath->evaluate("string({$prefix}Amt)", $balNode);
        if (empty($amountStr)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $amountStr);
        $currency = $xpath->evaluate("string({$prefix}Amt/@Ccy)", $balNode) ?: $defaultCurrency;
        $creditDebitStr = $xpath->evaluate("string({$prefix}CdtDbtInd)", $balNode);
        $dateStr = $xpath->evaluate("string({$prefix}Dt/{$prefix}Dt)", $balNode)
            ?: $xpath->evaluate("string({$prefix}Dt/{$prefix}DtTm)", $balNode);

        if (empty($dateStr)) {
            return null;
        }

        $creditDebit = match (strtoupper($creditDebitStr)) {
            'CRDT' => CreditDebit::CREDIT,
            'DBIT' => CreditDebit::DEBIT,
            default => CreditDebit::CREDIT
        };

        $balanceType = $xpath->evaluate("string({$prefix}Tp/{$prefix}CdOrPrtry/{$prefix}Cd)", $balNode);

        return new Balance(
            creditDebit: $creditDebit,
            date: $dateStr,
            currency: $currency,
            amount: $amount,
            type: $balanceType ?: 'CLBD'
        );
    }

    /**
     * Parst einen Entry-Block (Transaktion).
     */
    private static function parseTransaction(DOMXPath $xpath, DOMNode $entry, string $defaultCurrency, string $prefix = 'ns:'): ?Transaction {
        $amountStr = $xpath->evaluate("string({$prefix}Amt)", $entry);
        if (empty($amountStr)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $amountStr);
        $entryCcy = $xpath->evaluate("string({$prefix}Amt/@Ccy)", $entry) ?: $defaultCurrency;
        $creditDebitStr = $xpath->evaluate("string({$prefix}CdtDbtInd)", $entry);
        $reversalIndicator = $xpath->evaluate("string({$prefix}RvslInd)", $entry);

        $bookingDateStr = $xpath->evaluate("string({$prefix}BookgDt/{$prefix}Dt)", $entry)
            ?: $xpath->evaluate("string({$prefix}BookgDt/{$prefix}DtTm)", $entry);
        $valutaDateStr = $xpath->evaluate("string({$prefix}ValDt/{$prefix}Dt)", $entry)
            ?: $xpath->evaluate("string({$prefix}ValDt/{$prefix}DtTm)", $entry);

        if (empty($bookingDateStr)) {
            return null;
        }

        // CreditDebit bestimmen (mit Storno-Behandlung)
        $isReversal = strtolower($reversalIndicator) === 'true';
        $creditDebit = match (strtoupper($creditDebitStr)) {
            'CRDT' => $isReversal ? CreditDebit::DEBIT : CreditDebit::CREDIT,
            'DBIT' => $isReversal ? CreditDebit::CREDIT : CreditDebit::DEBIT,
            default => CreditDebit::CREDIT
        };

        // Currency
        $currency = CurrencyCode::tryFrom(strtoupper($entryCcy)) ?? CurrencyCode::Euro;

        // Referenzen parsen
        $entryRef = $xpath->evaluate("string({$prefix}NtryRef)", $entry);
        $acctSvcrRef = $xpath->evaluate("string({$prefix}AcctSvcrRef)", $entry);

        // TxDtls Referenzen
        $txDtls = $xpath->query("{$prefix}NtryDtls/{$prefix}TxDtls", $entry)->item(0);
        $endToEndId = null;
        $mandateId = null;
        $creditorId = null;

        if ($txDtls) {
            $endToEndId = $xpath->evaluate("string({$prefix}Refs/{$prefix}EndToEndId)", $txDtls) ?: null;
            $mandateId = $xpath->evaluate("string({$prefix}Refs/{$prefix}MndtId)", $txDtls) ?: null;
            $creditorId = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}Cdtr/{$prefix}Id/{$prefix}OrgId/{$prefix}Othr/{$prefix}Id)", $txDtls) ?: null;
        }

        $reference = new Reference(
            endToEndId: $endToEndId,
            mandateId: $mandateId,
            creditorId: $creditorId,
            entryReference: $entryRef ?: null,
            accountServicerReference: $acctSvcrRef ?: null
        );

        // Verwendungszweck
        $purpose = null;
        $purposeCode = null;
        $returnReason = null;
        if ($txDtls) {
            // Unstrukturiert
            $ustrd = $xpath->evaluate("string({$prefix}RmtInf/{$prefix}Ustrd)", $txDtls);
            if (!empty($ustrd)) {
                $purpose = $ustrd;
            }
            // Strukturiert (Creditor Reference)
            $strd = $xpath->evaluate("string({$prefix}RmtInf/{$prefix}Strd/{$prefix}CdtrRefInf/{$prefix}Ref)", $txDtls);
            if (!empty($strd)) {
                $purpose = $purpose ? $purpose . ' / ' . $strd : $strd;
            }
            // ISO 20022 Purpose Code
            $purposeCode = $xpath->evaluate("string({$prefix}Purp/{$prefix}Cd)", $txDtls) ?: null;
            // Return Reason
            $returnReason = $xpath->evaluate("string({$prefix}RtrInf/{$prefix}Rsn/{$prefix}Cd)", $txDtls) ?: null;
        }

        // Zusatzinfo (Buchungstext)
        $additionalInfo = $xpath->evaluate("string({$prefix}AddtlNtryInf)", $entry) ?: null;

        // Transaktionscode (proprietär)
        $transactionCode = $xpath->evaluate("string({$prefix}BkTxCd/{$prefix}Prtry/{$prefix}Cd)", $entry) ?: null;

        // ISO 20022 Domain/Family/SubFamily Codes
        $domainCode = $xpath->evaluate("string({$prefix}BkTxCd/{$prefix}Domn/{$prefix}Cd)", $entry) ?: null;
        $familyCode = $xpath->evaluate("string({$prefix}BkTxCd/{$prefix}Domn/{$prefix}Fmly/{$prefix}Cd)", $entry) ?: null;
        $subFamilyCode = $xpath->evaluate("string({$prefix}BkTxCd/{$prefix}Domn/{$prefix}Fmly/{$prefix}SubFmlyCd)", $entry) ?: null;

        // Gegenseite ermitteln (Debtor bei CRDT, Creditor bei DBIT)
        $counterpartyName = null;
        $counterpartyIban = null;
        $counterpartyBic = null;

        if ($txDtls) {
            if ($creditDebit === CreditDebit::CREDIT) {
                // Eingehende Zahlung: Debtor = Auftraggeber
                $counterpartyName = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}Dbtr/{$prefix}Nm)", $txDtls) ?: null;
                // Prüfe auch Pty/Nm-Struktur (neuere Versionen)
                if (empty($counterpartyName)) {
                    $counterpartyName = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}Dbtr/{$prefix}Pty/{$prefix}Nm)", $txDtls) ?: null;
                }
                $counterpartyIban = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}DbtrAcct/{$prefix}Id/{$prefix}IBAN)", $txDtls) ?: null;
                $counterpartyBic = $xpath->evaluate("string({$prefix}RltdAgts/{$prefix}DbtrAgt/{$prefix}FinInstnId/{$prefix}BIC)", $txDtls)
                    ?: $xpath->evaluate("string({$prefix}RltdAgts/{$prefix}DbtrAgt/{$prefix}FinInstnId/{$prefix}BICFI)", $txDtls) ?: null;
            } else {
                // Ausgehende Zahlung: Creditor = Zahlungsempfänger
                $counterpartyName = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}Cdtr/{$prefix}Nm)", $txDtls) ?: null;
                // Prüfe auch Pty/Nm-Struktur (neuere Versionen)
                if (empty($counterpartyName)) {
                    $counterpartyName = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}Cdtr/{$prefix}Pty/{$prefix}Nm)", $txDtls) ?: null;
                }
                $counterpartyIban = $xpath->evaluate("string({$prefix}RltdPties/{$prefix}CdtrAcct/{$prefix}Id/{$prefix}IBAN)", $txDtls) ?: null;
                $counterpartyBic = $xpath->evaluate("string({$prefix}RltdAgts/{$prefix}CdtrAgt/{$prefix}FinInstnId/{$prefix}BIC)", $txDtls)
                    ?: $xpath->evaluate("string({$prefix}RltdAgts/{$prefix}CdtrAgt/{$prefix}FinInstnId/{$prefix}BICFI)", $txDtls) ?: null;
            }
        }

        return new Transaction(
            bookingDate: new DateTimeImmutable($bookingDateStr),
            valutaDate: !empty($valutaDateStr) ? new DateTimeImmutable($valutaDateStr) : null,
            amount: $amount,
            currency: $currency,
            creditDebit: $creditDebit,
            reference: $reference,
            entryReference: $entryRef ?: null,
            accountServicerReference: $acctSvcrRef ?: null,
            status: 'BOOK',
            isReversal: $isReversal,
            purpose: $purpose,
            purposeCode: $purposeCode,
            additionalInfo: $additionalInfo,
            transactionCode: $transactionCode,
            domainCode: $domainCode,
            familyCode: $familyCode,
            subFamilyCode: $subFamilyCode,
            returnReason: $returnReason,
            counterpartyName: $counterpartyName,
            counterpartyIban: $counterpartyIban,
            counterpartyBic: $counterpartyBic
        );
    }
}
