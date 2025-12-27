<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionToCamt053Converter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Contracts\Abstracts\DATEV\BankTransactionConverterAbstract;
use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField as F;
use CommonToolkit\Helper\Data\BankHelper;
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
final class BankTransactionToCamt053Converter extends BankTransactionConverterAbstract {

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
        $blzBic = self::getField($fields, F::BLZ_BIC_KONTOINHABER);
        $accountNumber = self::getField($fields, F::KONTONUMMER_IBAN_KONTOINHABER);

        // IBAN ermitteln (direkt oder aus Kontonummer)
        $accountIban = $accountNumber;
        if (!BankHelper::isIBAN($accountNumber)) {
            $accountIban = self::generatePseudoIban($blzBic, $accountNumber);
        }

        // BIC ermitteln via BankHelper
        $accountBic = BankHelper::isBIC($blzBic) ? $blzBic : null;

        // Feld 3: Auszugsnummer
        $statementNumber = self::getField($fields, F::AUSZUGSNUMMER);
        if (empty($statementNumber)) {
            $statementNumber = '00001';
        }

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

        // Betrag und Währung parsen
        $amountData = self::parseAmount($amountStr);
        $currency = self::parseCurrency(self::getField($fields, F::WAEHRUNG));

        // Geschäftsvorgangscode (optional)
        $transactionCode = self::getField($fields, F::GESCHAEFTSVORGANGSCODE);
        if (empty($transactionCode) || strlen($transactionCode) < 3) {
            $transactionCode = self::DEFAULT_TRANSACTION_CODE;
        }

        // Auftraggeber-Daten
        $payerBlz = self::getField($fields, F::BLZ_BIC_AUFTRAGGEBER);
        $payerAccount = self::getField($fields, F::KONTONUMMER_IBAN_AUFTRAGGEBER);

        // EntryReference generieren
        $entryReference = date('dmy') . sprintf('%010d', abs(crc32($bookingDateStr . $amountStr)));
        $entryReference = substr($entryReference, 0, 25);

        // End-to-End-ID aus Verwendungszweck-Feldern
        $endToEndId = self::extractEndToEndId($fields);

        $reference = new Reference(
            endToEndId: $endToEndId,
            mandateId: null,
            creditorId: null,
            entryReference: $entryReference,
            accountServicerReference: null
        );

        // Counterparty aus Auftraggeber-Daten
        $counterpartyName = self::buildCounterpartyName($fields);
        $counterpartyIban = self::buildCounterpartyIban($payerBlz, $payerAccount);
        $counterpartyBic = BankHelper::isBIC($payerBlz) ? $payerBlz : null;

        // Purpose und AdditionalInfo
        $purpose = self::buildPurpose($fields);
        $additionalInfo = self::getField($fields, F::BUCHUNGSTEXT) ?: null;

        return new Camt053Transaction(
            bookingDate: $bookingDate,
            valutaDate: $valutaDate,
            amount: $amountData['amount'],
            currency: $currency,
            creditDebit: $amountData['creditDebit'],
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
     * Extrahiert End-to-End-ID aus Verwendungszweck-Feldern.
     */
    private static function extractEndToEndId(array $fields): ?string {
        $verwendungszweckFelder = [F::VERWENDUNGSZWECK_1, F::VERWENDUNGSZWECK_2, F::VERWENDUNGSZWECK_3];
        foreach ($verwendungszweckFelder as $vzFeld) {
            $vz = self::getField($fields, $vzFeld);
            if (preg_match('/EREF\+([^\s+]+)/', $vz, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Baut den Counterparty-Namen aus Auftraggeber-Feldern.
     */
    private static function buildCounterpartyName(array $fields): ?string {
        $nameParts = [];
        $name1 = self::getField($fields, F::AUFTRAGGEBERNAME_1);
        if (!empty($name1)) {
            $nameParts[] = $name1;
        }
        $name2 = self::getField($fields, F::AUFTRAGGEBERNAME_2);
        if (!empty($name2)) {
            $nameParts[] = $name2;
        }
        return !empty($nameParts) ? implode(' ', $nameParts) : null;
    }

    /**
     * Baut die Counterparty-IBAN aus BLZ und Kontonummer.
     */
    private static function buildCounterpartyIban(string $payerBlz, string $payerAccount): ?string {
        if (empty($payerAccount)) {
            return null;
        }
        if (BankHelper::isIBAN($payerAccount)) {
            return $payerAccount;
        }
        if (!empty($payerBlz)) {
            return self::generatePseudoIban($payerBlz, $payerAccount);
        }
        return null;
    }

    /**
     * Baut den Purpose aus Verwendungszweck-Feldern.
     */
    private static function buildPurpose(array $fields): ?string {
        $verwendungszweckFelder = [F::VERWENDUNGSZWECK_1, F::VERWENDUNGSZWECK_2, F::VERWENDUNGSZWECK_3];
        $purposeParts = [];
        foreach ($verwendungszweckFelder as $vzFeld) {
            $vz = self::getField($fields, $vzFeld);
            if (!empty($vz)) {
                $purposeParts[] = $vz;
            }
        }
        return !empty($purposeParts) ? implode(' ', $purposeParts) : null;
    }
}