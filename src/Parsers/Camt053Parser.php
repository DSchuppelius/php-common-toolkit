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

use CommonToolkit\Entities\Banking\Camt053\Document;
use CommonToolkit\Entities\Banking\Camt053\Transaction;
use DOMDocument;
use DOMXPath;
use RuntimeException;

class Camt053Parser {
    public static function fromXml(string $xmlContent): Document {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            throw new RuntimeException("Ungültiges XML-Dokument");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ns', 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02');

        $stmtNode = $xpath->query('//ns:Stmt')->item(0);
        if (!$stmtNode) {
            throw new RuntimeException("Kein <Stmt>-Block gefunden.");
        }

        $statementId       = $xpath->evaluate('string(ns:Id)', $stmtNode);
        $creationDateTime  = $xpath->evaluate('string(ns:CreDtTm)', $stmtNode);
        $accountIban       = $xpath->evaluate('string(ns:Acct/ns:Id/ns:IBAN)', $stmtNode);
        $currency          = $xpath->evaluate('string(ns:Acct/ns:Ccy)', $stmtNode) ?: 'EUR';

        $document = new Document($statementId, $creationDateTime, $accountIban, $currency);

        foreach ($xpath->query('.//ns:Ntry', $stmtNode) as $entry) {
            $amount     = (float) str_replace(',', '.', $xpath->evaluate('string(ns:Amt)', $entry));
            $entryCcy   = $xpath->evaluate('string(ns:Amt/@Ccy)', $entry) ?: $currency;
            $creditDebit = $xpath->evaluate('string(ns:CdtDbtInd)', $entry);
            $bookingDate = $xpath->evaluate('string(ns:BookgDt/ns:Dt)', $entry);
            $valutaDate  = $xpath->evaluate('string(ns:ValDt/ns:Dt)', $entry);
            $ref         = $xpath->evaluate('string(ns:NtryRef)', $entry);
            $purpose     = $xpath->evaluate('string(ns:AddtlNtryInf)', $entry);

            $transaction = new Transaction(
                bookingDate: $bookingDate,
                valutaDate: $valutaDate,
                amount: $amount,
                currency: $entryCcy,
                creditDebit: $creditDebit,
                reference: $ref,
                purpose: $purpose
            );

            $document->addTransaction($transaction);
        }

        libxml_clear_errors();
        return $document;
    }
}
