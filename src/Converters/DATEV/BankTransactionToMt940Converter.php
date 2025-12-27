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
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
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
final class BankTransactionToMt940Converter {

    private const DEFAULT_TRANSACTION_CODE = 'NTRF';
    private const DEFAULT_CURRENCY = 'EUR';

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
        $blzBic = count($fields) > 0 ? trim($fields[0]->getValue()) : '';
        $accountNumber = count($fields) > 1 ? trim($fields[1]->getValue()) : '';

        // Kombiniere zu accountId (Format: BLZ/Kontonummer oder IBAN)
        $accountId = !empty($blzBic) && !empty($accountNumber)
            ? $blzBic . '/' . $accountNumber
            : ($accountNumber ?: $blzBic);

        // Feld 3: Auszugsnummer
        $statementNumber = count($fields) > 2 ? trim($fields[2]->getValue()) : '00001';
        if (empty($statementNumber)) {
            $statementNumber = '00001';
        }

        // Generiere Referenz-ID aus Auszugsnummer und Datum
        $statementDate = count($fields) > 3 ? trim($fields[3]->getValue()) : '';
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

        // Feld 6: Buchungsdatum (Index 5)
        $bookingDateStr = trim($fields[5]->getValue());
        $bookingDate = self::parseDate($bookingDateStr);
        if ($bookingDate === null) {
            return null;
        }

        // Feld 5: Valutadatum (Index 4) - optional
        $valutaDateStr = count($fields) > 4 ? trim($fields[4]->getValue()) : '';
        $valutaDate = !empty($valutaDateStr) ? self::parseDate($valutaDateStr) : null;

        // Feld 7: Umsatz (Index 6)
        $amountStr = trim($fields[6]->getValue());
        if (empty($amountStr)) {
            return null;
        }

        // Betrag parsen (deutsches Format mit Vorzeichen)
        $amount = (float) CurrencyHelper::deToUs(ltrim($amountStr, '+'));
        $creditDebit = str_starts_with($amountStr, '-') ? CreditDebit::DEBIT : CreditDebit::CREDIT;
        $amount = abs($amount);

        // Feld 17: Währung (Index 16) - optional
        $currencyStr = count($fields) > 16 ? trim($fields[16]->getValue()) : self::DEFAULT_CURRENCY;
        $currency = !empty($currencyStr)
            ? (CurrencyCode::tryFrom(strtoupper($currencyStr)) ?? CurrencyCode::Euro)
            : CurrencyCode::Euro;

        // Feld 16: Geschäftsvorgangscode (Index 15) - optional
        $transactionCode = count($fields) > 15 ? trim($fields[15]->getValue()) : '';
        if (empty($transactionCode) || strlen($transactionCode) < 3) {
            $transactionCode = self::DEFAULT_TRANSACTION_CODE;
        } else {
            // Kürze auf 4 Zeichen für MT940-Kompatibilität
            $transactionCode = substr($transactionCode, 0, 4);
        }

        // Referenz aus Auftraggeber-Daten zusammenstellen
        $payerBlz = count($fields) > 9 ? trim($fields[9]->getValue()) : '';
        $payerAccount = count($fields) > 10 ? trim($fields[10]->getValue()) : '';
        $referenceStr = trim($payerBlz . $payerAccount);
        if (strlen($referenceStr) > 12) {
            $referenceStr = substr($referenceStr, 0, 12);
        }
        if (empty($referenceStr)) {
            $referenceStr = 'NONREF';
        }

        // Purpose aus Auftraggeber-Name und Verwendungszweck zusammenstellen
        $purposeParts = [];

        // Auftraggeber-Name (Felder 8-9, Index 7-8)
        if (count($fields) > 7) {
            $name1 = trim($fields[7]->getValue());
            if (!empty($name1)) {
                $purposeParts[] = $name1;
            }
        }
        if (count($fields) > 8) {
            $name2 = trim($fields[8]->getValue());
            if (!empty($name2)) {
                $purposeParts[] = $name2;
            }
        }

        // Verwendungszweck (Felder 12-14, Index 11-13)
        for ($i = 11; $i <= 13; $i++) {
            if (count($fields) > $i) {
                $vz = trim($fields[$i]->getValue());
                if (!empty($vz)) {
                    $purposeParts[] = $vz;
                }
            }
        }

        // Erweiterte Verwendungszwecke (Felder 19-24, Index 18-23)
        for ($i = 18; $i <= 23; $i++) {
            if (count($fields) > $i) {
                $vz = trim($fields[$i]->getValue());
                if (!empty($vz)) {
                    $purposeParts[] = $vz;
                }
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
            amount: $amount,
            creditDebit: $creditDebit,
            currency: $currency,
            reference: $reference,
            purpose: $purpose ?: null
        );
    }

    /**
     * Parst ein Datum aus verschiedenen deutschen Formaten.
     */
    private static function parseDate(string $dateStr): ?DateTimeImmutable {
        if (empty($dateStr)) {
            return null;
        }

        // Versuche verschiedene Formate
        $formats = [
            'd.m.Y',      // 27.12.2025
            'd.m.y',      // 27.12.25
            'Y-m-d',      // 2025-12-27
            'Ymd',        // 20251227
            'ymd',        // 251227
            'd/m/Y',      // 27/12/2025
            'd-m-Y',      // 27-12-2025
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date;
            }
        }

        // Versuche mit strtotime als Fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return (new DateTimeImmutable())->setTimestamp($timestamp);
        }

        return null;
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