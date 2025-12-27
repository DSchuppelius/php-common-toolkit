<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionToMt940Converter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Builders\Mt940DocumentBuilder;
use CommonToolkit\Contracts\Abstracts\DATEV\BankTransactionConverterAbstract;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField as F;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Konvertiert DATEV ASCII-Weiterverarbeitungsdateien (Banktransaktionen) in das MT940-Format.
 * 
 * Die Konvertierung nutzt die Felddefinitionen aus BankTransactionHeaderField:
 * - Feld 1: BLZ/BIC Kontoinhaber → Teil von accountId
 * - Feld 2: Kontonummer/IBAN Kontoinhaber → Teil von accountId
 * - Feld 3: Auszugsnummer → statementNumber
 * - Feld 4: Auszugsdatum → für Salden-Datum
 * - Feld 5: Valuta → valutaDate
 * - Feld 6: Buchungsdatum → bookingDate
 * - Feld 7: Umsatz → amount (mit +/- Vorzeichen)
 * - Felder 8-9: Auftraggeber-Name → purpose
 * - Felder 10-11: BLZ/Kontonummer Auftraggeber → reference
 * - Felder 12-14: Verwendungszweck 1-3 → purpose
 * - Feld 16: Geschäftsvorgangscode → transactionCode
 * - Feld 17: Währung → currency
 * 
 * @package CommonToolkit\Converters\DATEV
 */
final class BankTransactionToMt940Converter extends BankTransactionConverterAbstract {

    /**
     * Konvertiert ein DATEV BankTransaction-Dokument in ein MT940-Dokument.
     * 
     * @param BankTransaction $document DATEV ASCII-Weiterverarbeitungsdokument
     * @param float|null $openingBalanceAmount Anfangssaldo (optional, wird sonst berechnet)
     * @param CreditDebit|null $openingBalanceCreditDebit Credit/Debit des Anfangssaldos
     * @return Mt940Document
     * @throws RuntimeException Bei fehlenden Pflichtfeldern
     */
    public static function convert(
        BankTransaction $document,
        ?float $openingBalanceAmount = null,
        ?CreditDebit $openingBalanceCreditDebit = null
    ): Mt940Document {
        $rows = $document->getRows();

        if (empty($rows)) {
            throw new RuntimeException('DATEV-Dokument enthält keine Transaktionen');
        }

        // Extrahiere Kontoinformationen aus der ersten Zeile
        $firstRow = $rows[0];
        $accountInfo = self::extractAccountInfo($firstRow);

        // Sammle alle Transaktionen
        $transactions = [];
        $totalAmount = 0.0;
        $currency = null;
        $firstDate = null;
        $lastDate = null;

        foreach ($rows as $row) {
            $txn = self::convertTransaction($row);
            if ($txn !== null) {
                $transactions[] = $txn;

                // Summiere für Saldo-Berechnung
                $sign = $txn->getCreditDebit() === CreditDebit::CREDIT ? 1 : -1;
                $totalAmount += $sign * $txn->getAmount();

                // Währung und Datum tracken
                $currency ??= $txn->getCurrency();
                $firstDate ??= $txn->getDate();
                $lastDate = $txn->getDate();
            }
        }

        if (empty($transactions)) {
            throw new RuntimeException('Keine gültigen Transaktionen gefunden');
        }

        $currency ??= CurrencyCode::Euro;
        $balanceDate = $firstDate ?? new DateTimeImmutable();

        // Opening Balance berechnen oder verwenden
        if ($openingBalanceAmount !== null) {
            $openingBalance = new Balance(
                $openingBalanceCreditDebit ?? ($openingBalanceAmount >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT),
                $balanceDate,
                $currency,
                abs($openingBalanceAmount)
            );
        } else {
            // Kein Anfangssaldo angegeben - setze auf 0
            $openingBalance = new Balance(
                CreditDebit::CREDIT,
                $balanceDate,
                $currency,
                0.0
            );
        }

        // Closing Balance wird vom Builder berechnet (basierend auf Opening + Transaktionen)
        return (new Mt940DocumentBuilder())
            ->setAccountId($accountInfo['accountId'])
            ->setReferenceId($accountInfo['referenceId'])
            ->setStatementNumber($accountInfo['statementNumber'])
            ->setOpeningBalance($openingBalance)
            ->addTransactions($transactions)
            ->build();
    }

    /**
     * Extrahiert Kontoinformationen aus einer Datenzeile.
     */
    private static function extractAccountInfo(DataLine $row): array {
        $fields = $row->getFields();

        // Feld 1: BLZ/BIC, Feld 2: Kontonummer/IBAN
        $blzBic = self::getField($fields, F::BLZ_BIC_KONTOINHABER);
        $accountNumber = self::getField($fields, F::KONTONUMMER_IBAN_KONTOINHABER);

        // Kombiniere zu accountId (Format: BLZ/Kontonummer oder IBAN)
        $accountId = !empty($blzBic) && !empty($accountNumber)
            ? $blzBic . '/' . $accountNumber
            : ($accountNumber ?: $blzBic);

        // Feld 3: Auszugsnummer
        $statementNumber = self::getField($fields, F::AUSZUGSNUMMER);
        if (empty($statementNumber)) {
            $statementNumber = '00001';
        }

        // Generiere Referenz-ID aus Auszugsnummer und Datum
        $statementDate = self::getField($fields, F::AUSZUGSDATUM);
        $referenceId = 'DATEV' . preg_replace('/[^A-Z0-9]/i', '', $statementNumber . $statementDate);

        // Kürze auf max. 16 Zeichen (MT940-Limit)
        $referenceId = substr($referenceId, 0, 16);

        return [
            'accountId' => $accountId,
            'statementNumber' => $statementNumber,
            'referenceId' => $referenceId ?: 'DATEV',
        ];
    }

    /**
     * Konvertiert eine einzelne DATEV-Datenzeile in eine MT940-Transaktion.
     */
    private static function convertTransaction(DataLine $row): ?Mt940Transaction {
        $fields = $row->getFields();

        // Mindestens 7 Felder erforderlich (Pflichtfelder 1, 2, 6, 7)
        if (count($fields) < 7) {
            return null;
        }

        // Buchungsdatum (Pflichtfeld)
        $bookingDateStr = self::getField($fields, F::BUCHUNGSDATUM);
        $bookingDate = self::parseDate($bookingDateStr);
        if ($bookingDate === null) {
            return null;
        }

        // Valutadatum (optional)
        $valutaDateStr = self::getField($fields, F::VALUTA);
        $valutaDate = !empty($valutaDateStr) ? self::parseDate($valutaDateStr) : null;

        // Umsatz (Pflichtfeld)
        $amountStr = self::getField($fields, F::UMSATZ);
        if (empty($amountStr)) {
            return null;
        }

        // Betrag und Richtung parsen
        $amountData = self::parseAmount($amountStr);
        $currency = self::parseCurrency(self::getField($fields, F::WAEHRUNG));

        // Geschäftsvorgangscode (optional)
        $transactionCode = self::getField($fields, F::GESCHAEFTSVORGANGSCODE);
        if (empty($transactionCode) || strlen($transactionCode) < 3) {
            $transactionCode = self::DEFAULT_TRANSACTION_CODE;
        } else {
            // Kürze auf 4 Zeichen für MT940-Kompatibilität
            $transactionCode = substr($transactionCode, 0, 4);
        }

        // Referenz aus Auftraggeber-Daten zusammenstellen
        $payerBlz = self::getField($fields, F::BLZ_BIC_AUFTRAGGEBER);
        $payerAccount = self::getField($fields, F::KONTONUMMER_IBAN_AUFTRAGGEBER);
        $referenceStr = trim($payerBlz . $payerAccount);
        if (strlen($referenceStr) > 12) {
            $referenceStr = substr($referenceStr, 0, 12);
        }
        if (empty($referenceStr)) {
            $referenceStr = 'NONREF';
        }

        // Purpose aus Auftraggeber-Name und Verwendungszweck zusammenstellen
        $purposeParts = [];

        // Auftraggeber-Name (Felder 8-9)
        $name1 = self::getField($fields, F::AUFTRAGGEBERNAME_1);
        if (!empty($name1)) {
            $purposeParts[] = $name1;
        }
        $name2 = self::getField($fields, F::AUFTRAGGEBERNAME_2);
        if (!empty($name2)) {
            $purposeParts[] = $name2;
        }

        // Alle Verwendungszweck-Felder sammeln
        foreach (self::getVerwendungszweckFelder() as $vzFeld) {
            $vz = self::getField($fields, $vzFeld);
            if (!empty($vz)) {
                $purposeParts[] = $vz;
            }
        }

        $purpose = implode(' ', $purposeParts);

        try {
            $reference = new Reference($transactionCode, $referenceStr);
        } catch (Throwable) {
            // Fallback bei ungültiger Referenz
            $reference = new Reference('NTRF', 'NONREF');
        }

        return new Mt940Transaction(
            bookingDate: $bookingDate,
            valutaDate: $valutaDate,
            amount: $amountData['amount'],
            creditDebit: $amountData['creditDebit'],
            currency: $currency,
            reference: $reference,
            purpose: $purpose ?: null
        );
    }

    /**
     * Konvertiert mehrere BankTransaction-Dokumente in MT940-Dokumente.
     * 
     * @param BankTransaction[] $documents
     * @return Mt940Document[]
     */
    public static function convertMultiple(array $documents): array {
        $results = [];
        foreach ($documents as $doc) {
            if ($doc instanceof BankTransaction) {
                try {
                    $results[] = self::convert($doc);
                } catch (Throwable) {
                    // Überspringe fehlerhafte Dokumente
                }
            }
        }
        return $results;
    }
}