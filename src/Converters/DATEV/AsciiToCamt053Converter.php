<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AsciiToCamt053Converter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use RuntimeException;

/**
 * Konvertiert DATEV ASCII-Weiterverarbeitungsdateien (Banktransaktionen) in das CAMT.053-Format.
 * 
 * Die Konvertierung nutzt die Felddefinitionen aus BankTransactionHeaderField:
 * - Feld 1: BLZ/BIC Kontoinhaber → accountBic
 * - Feld 2: Kontonummer/IBAN Kontoinhaber → accountIban
 * - Feld 3: Auszugsnummer → statementNumber
 * - Feld 4: Auszugsdatum → creationDateTime
 * - Feld 5: Valuta → valutaDate
 * - Feld 6: Buchungsdatum → bookingDate
 * - Feld 7: Umsatz → amount (mit +/- Vorzeichen)
 * - Felder 8-9: Auftraggeber-Name → counterpartyName
 * - Felder 10-11: BLZ/Kontonummer Auftraggeber → counterpartyIban
 * - Felder 12-14: Verwendungszweck 1-3 → purpose
 * - Feld 16: Geschäftsvorgangscode → transactionCode
 * - Feld 17: Währung → currency
 * 
 * Das Ausgabeformat entspricht ISO 20022 camt.053.001.02.
 * 
 * @package CommonToolkit\Converters\DATEV
 */
final class AsciiToCamt053Converter {

    private const DEFAULT_TRANSACTION_CODE = 'NTRF';
    private const DEFAULT_CURRENCY = 'EUR';

    /**
     * Konvertiert ein DATEV BankTransaction-Dokument in ein CAMT.053-Dokument.
     * 
     * @param BankTransaction $document DATEV ASCII-Weiterverarbeitungsdokument
     * @param float|null $openingBalanceAmount Anfangssaldo (optional, wird sonst auf 0 gesetzt)
     * @param CreditDebit|null $openingBalanceCreditDebit Credit/Debit des Anfangssaldos
     * @param string|null $accountOwner Name des Kontoinhabers (optional)
     * @return Camt053Document
     * @throws RuntimeException Bei fehlenden Pflichtfeldern
     */
    public static function convert(
        BankTransaction $document,
        ?float $openingBalanceAmount = null,
        ?CreditDebit $openingBalanceCreditDebit = null,
        ?string $accountOwner = null
    ): Camt053Document {
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
                $totalAmount += $txn->getSignedAmount();

                // Währung und Datum tracken
                $currency ??= $txn->getCurrency();
                $firstDate ??= $txn->getBookingDate();
                $lastDate = $txn->getBookingDate();
            }
        }

        if (empty($transactions)) {
            throw new RuntimeException('Keine gültigen Transaktionen gefunden');
        }

        $currency ??= CurrencyCode::Euro;
        $creationDateTime = new DateTimeImmutable();

        // Opening Balance berechnen oder verwenden
        $openingBalanceValue = $openingBalanceAmount ?? 0.0;
        $openingBalanceCd = $openingBalanceCreditDebit ?? ($openingBalanceValue >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT);

        $openingBalance = new Balance(
            creditDebit: $openingBalanceCd,
            date: $firstDate ?? $creationDateTime,
            currency: $currency,
            amount: abs($openingBalanceValue),
            type: 'PRCD'
        );

        // Closing Balance berechnen
        $closingBalanceValue = $openingBalanceValue + $totalAmount;
        $closingBalanceCd = $closingBalanceValue >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;

        $closingBalance = new Balance(
            creditDebit: $closingBalanceCd,
            date: $lastDate ?? $creationDateTime,
            currency: $currency,
            amount: abs($closingBalanceValue),
            type: 'CLBD'
        );

        $document = new Camt053Document(
            id: $accountInfo['statementId'],
            creationDateTime: $creationDateTime,
            accountIdentifier: $accountInfo['accountIban'],
            currency: $currency,
            accountOwner: $accountOwner,
            servicerBic: $accountInfo['accountBic'],
            messageId: $accountInfo['messageId'],
            sequenceNumber: $accountInfo['statementNumber'],
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        // Transaktionen hinzufügen
        foreach ($transactions as $txn) {
            $document->addEntry($txn);
        }

        return $document;
    }

    /**
     * Konvertiert mehrere DATEV-Dokumente in CAMT.053-Dokumente.
     * 
     * @param BankTransaction[] $documents Liste der DATEV-Dokumente
     * @param float $startingBalance Anfangssaldo für das erste Dokument
     * @return Camt053Document[]
     */
    public static function convertMultiple(array $documents, float $startingBalance = 0.0): array {
        $results = [];
        $currentBalance = $startingBalance;
        $currentCreditDebit = $currentBalance >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;

        foreach ($documents as $document) {
            $camt053 = self::convert($document, $currentBalance, $currentCreditDebit);
            $results[] = $camt053;

            // Closing Balance für nächstes Dokument übernehmen
            $closingBalance = $camt053->getClosingBalance();
            if ($closingBalance !== null) {
                $currentBalance = $closingBalance->isCredit()
                    ? $closingBalance->getAmount()
                    : -$closingBalance->getAmount();
                $currentCreditDebit = $closingBalance->getCreditDebit();
            }
        }

        return $results;
    }

    /**
     * Extrahiert Kontoinformationen aus einer Datenzeile.
     */
    private static function extractAccountInfo(DataLine $row): array {
        $fields = $row->getFields();

        // Feld 1: BLZ/BIC, Feld 2: Kontonummer/IBAN
        $blzBic = count($fields) > 0 ? trim($fields[0]->getValue()) : '';
        $accountNumber = count($fields) > 1 ? trim($fields[1]->getValue()) : '';

        // IBAN ermitteln (direkt oder aus Kontonummer)
        $accountIban = $accountNumber;
        if (!str_starts_with(strtoupper($accountNumber), 'DE') && strlen($accountNumber) < 22) {
            // Kein IBAN - als Platzhalter formatieren
            $accountIban = 'DE00' . str_pad($blzBic, 8, '0', STR_PAD_LEFT) . str_pad($accountNumber, 10, '0', STR_PAD_LEFT);
        }

        // BIC ermitteln
        $accountBic = strlen($blzBic) === 11 ? $blzBic : null;

        // Feld 3: Auszugsnummer
        $statementNumber = count($fields) > 2 ? trim($fields[2]->getValue()) : '00001';
        if (empty($statementNumber)) {
            $statementNumber = '00001';
        }

        // Feld 4: Auszugsdatum
        $statementDate = count($fields) > 3 ? trim($fields[3]->getValue()) : date('Ymd');

        // Statement-ID generieren
        $statementId = 'CAMT053' . preg_replace('/[^A-Z0-9]/i', '', $accountNumber . $statementNumber);
        $statementId = substr($statementId, 0, 35);

        // Message-ID generieren
        $messageId = 'CAMT053' . date('YmdHis') . sprintf('%06d', rand(0, 999999));
        $messageId = substr($messageId, 0, 35);

        return [
            'accountIban' => $accountIban,
            'accountBic' => $accountBic,
            'statementNumber' => $statementNumber,
            'statementId' => $statementId ?: 'DATEV',
            'messageId' => $messageId,
        ];
    }

    /**
     * Konvertiert eine einzelne DATEV-Datenzeile in eine CAMT.053-Transaktion.
     */
    private static function convertTransaction(DataLine $row): ?Camt053Transaction {
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
        }

        // Referenz aus verschiedenen Quellen zusammenstellen
        $payerBlz = count($fields) > 9 ? trim($fields[9]->getValue()) : '';
        $payerAccount = count($fields) > 10 ? trim($fields[10]->getValue()) : '';

        // EntryReference generieren
        $entryReference = date('dmy') . sprintf('%010d', abs(crc32($bookingDateStr . $amountStr)));
        $entryReference = substr($entryReference, 0, 25);

        // End-to-End-ID aus Referenzfeld wenn vorhanden
        $endToEndId = null;
        // Prüfe auf strukturierte Daten in Verwendungszweck
        for ($i = 11; $i <= 13; $i++) {
            if (count($fields) > $i) {
                $vz = trim($fields[$i]->getValue());
                if (preg_match('/EREF\+([^\s+]+)/', $vz, $matches)) {
                    $endToEndId = $matches[1];
                    break;
                }
            }
        }

        $reference = new Reference(
            endToEndId: $endToEndId,
            mandateId: null,
            creditorId: null,
            entryReference: $entryReference,
            accountServicerReference: null
        );

        // Counterparty aus Auftraggeber-Daten
        $counterpartyName = null;
        $counterpartyIban = null;
        $counterpartyBic = null;

        // Auftraggeber-Name (Felder 8-9, Index 7-8)
        $nameParts = [];
        if (count($fields) > 7) {
            $name1 = trim($fields[7]->getValue());
            if (!empty($name1)) {
                $nameParts[] = $name1;
            }
        }
        if (count($fields) > 8) {
            $name2 = trim($fields[8]->getValue());
            if (!empty($name2)) {
                $nameParts[] = $name2;
            }
        }
        if (!empty($nameParts)) {
            $counterpartyName = implode(' ', $nameParts);
        }

        // Auftraggeber-Konto (Felder 10-11, Index 9-10)
        if (!empty($payerAccount)) {
            // Prüfen ob IBAN oder Kontonummer
            if (strlen($payerAccount) >= 15 && preg_match('/^[A-Z]{2}/', $payerAccount)) {
                $counterpartyIban = $payerAccount;
            } else if (!empty($payerBlz)) {
                // Pseudo-IBAN aus BLZ + Kontonummer
                $counterpartyIban = 'DE00' . str_pad($payerBlz, 8, '0', STR_PAD_LEFT) . str_pad($payerAccount, 10, '0', STR_PAD_LEFT);
            }
        }

        // BIC falls BLZ 11-stellig
        if (strlen($payerBlz) === 11) {
            $counterpartyBic = $payerBlz;
        }

        // Purpose aus Verwendungszweck zusammenstellen (Felder 12-14, Index 11-13)
        $purposeParts = [];
        for ($i = 11; $i <= 13; $i++) {
            if (count($fields) > $i) {
                $vz = trim($fields[$i]->getValue());
                if (!empty($vz)) {
                    $purposeParts[] = $vz;
                }
            }
        }
        $purpose = !empty($purposeParts) ? implode(' ', $purposeParts) : null;

        // Additional Info aus Buchungstext wenn vorhanden (Feld 15, Index 14)
        $additionalInfo = count($fields) > 14 ? trim($fields[14]->getValue()) : null;
        if (empty($additionalInfo)) {
            $additionalInfo = null;
        }

        return new Camt053Transaction(
            bookingDate: $bookingDate,
            valutaDate: $valutaDate,
            amount: $amount,
            currency: $currency,
            creditDebit: $creditDebit,
            reference: $reference,
            entryReference: $entryReference,
            accountServicerReference: null,
            status: 'BOOK',
            isReversal: false,
            purpose: $purpose,
            additionalInfo: $additionalInfo,
            transactionCode: $transactionCode,
            counterpartyName: $counterpartyName,
            counterpartyIban: $counterpartyIban,
            counterpartyBic: $counterpartyBic
        );
    }

    /**
     * Parst ein Datum in verschiedenen Formaten.
     */
    private static function parseDate(string $dateStr): ?DateTimeImmutable {
        $dateStr = trim($dateStr);

        if (empty($dateStr)) {
            return null;
        }

        // Verschiedene Formate probieren
        $formats = [
            'Y-m-d',      // ISO Format
            'd.m.Y',      // Deutsches Format
            'd.m.y',      // Deutsches Format kurz
            'Ymd',        // Kompakt
            'ymd',        // Kompakt kurz
            'dmY',        // Kompakt deutsch
            'dmy',        // Kompakt deutsch kurz
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $dateStr);
            if ($date !== false) {
                // Bei 2-stelligem Jahr: 00-30 = 20XX, 31-99 = 19XX
                if (in_array($format, ['ymd', 'dmy', 'd.m.y'])) {
                    $year = (int) $date->format('Y');
                    if ($year < 100) {
                        $year = $year <= 30 ? 2000 + $year : 1900 + $year;
                        $date = $date->setDate($year, (int) $date->format('m'), (int) $date->format('d'));
                    }
                }
                return $date;
            }
        }

        return null;
    }
}