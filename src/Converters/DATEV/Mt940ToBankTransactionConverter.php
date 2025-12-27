<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940ToBankTransactionConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\CSV\DataField;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Helper\Data\BankHelper;
use CommonToolkit\Helper\Data\CurrencyHelper;
use Throwable;

/**
 * Konvertiert MT940 SWIFT-Kontoauszüge in das DATEV ASCII-Weiterverarbeitungsformat.
 * 
 * Die Konvertierung mappt MT940-Felder auf die DATEV BankTransaction-Struktur:
 * - accountId → Feld 1 (BLZ/BIC) + Feld 2 (Kontonummer/IBAN)
 * - statementNumber → Feld 3 (Auszugsnummer)
 * - openingBalance.date → Feld 4 (Auszugsdatum)
 * - Transaction.valutaDate → Feld 5 (Valuta)
 * - Transaction.bookingDate → Feld 6 (Buchungsdatum)
 * - Transaction.amount → Feld 7 (Umsatz mit +/- Vorzeichen)
 * - Transaction.purpose → Felder 12-14, 19-24 (Verwendungszweck)
 * - Transaction.reference → Feld 16 (Geschäftsvorgangscode)
 * - currency → Feld 17 (Währung)
 * 
 * @package CommonToolkit\Converters\DATEV
 */
final class Mt940ToBankTransactionConverter {
    private const VERWENDUNGSZWECK_MAX_LENGTH = 27;
    private const DATE_FORMAT = 'd.m.Y';

    /**
     * Konvertiert ein MT940-Dokument in ein DATEV BankTransaction-Dokument.
     */
    public static function convert(Mt940Document $document): BankTransaction {
        $rows = [];

        // Extrahiere Kontoinformationen
        $accountInfo = self::parseAccountId($document->getAccountId());
        $statementNumber = $document->getStatementNumber();
        $statementDate = $document->getOpeningBalance()->getDate()->format(self::DATE_FORMAT);
        $currency = $document->getCurrency()->value;

        foreach ($document->getTransactions() as $transaction) {
            $rows[] = self::convertTransaction(
                $transaction,
                $accountInfo,
                $statementNumber,
                $statementDate,
                $currency
            );
        }

        return new BankTransaction($rows);
    }

    /**
     * Konvertiert eine MT940-Transaktion in eine DATEV-Datenzeile.
     */
    private static function convertTransaction(Mt940Transaction $txn, array $accountInfo, string $statementNumber, string $statementDate, string $currency): DataLine {
        $fields = [];

        // Feld 1: BLZ/BIC des Kontoinhabers
        $fields[] = new DataField($accountInfo['blz'] ?? '');

        // Feld 2: Kontonummer/IBAN des Kontoinhabers
        $fields[] = new DataField($accountInfo['account'] ?? '');

        // Feld 3: Auszugsnummer
        $fields[] = new DataField($statementNumber);

        // Feld 4: Auszugsdatum
        $fields[] = new DataField($statementDate);

        // Feld 5: Valuta
        $valutaDate = $txn->getValutaDate() ?? $txn->getBookingDate();
        $fields[] = new DataField($valutaDate->format(self::DATE_FORMAT));

        // Feld 6: Buchungsdatum
        $fields[] = new DataField($txn->getBookingDate()->format(self::DATE_FORMAT));

        // Feld 7: Umsatz (mit Vorzeichen, deutsches Format) - quoted um Parsing zu verhindern
        $sign = $txn->getCreditDebit() === CreditDebit::CREDIT ? '+' : '-';
        $amountStr = '"' . $sign . CurrencyHelper::usToDe(number_format($txn->getAmount(), 2, '.', '')) . '"';
        $fields[] = new DataField($amountStr);

        // Feld 8-9: Auftraggeber (leer bei MT940, da nicht direkt verfügbar)
        $fields[] = new DataField('');
        $fields[] = new DataField('');

        // Feld 10-11: BLZ/Kontonummer Auftraggeber (leer)
        $fields[] = new DataField('');
        $fields[] = new DataField('');

        // Feld 12-14: Verwendungszweck 1-3
        $purposeLines = self::splitPurpose($txn->getPurpose() ?? '');
        $fields[] = new DataField($purposeLines[0] ?? '');
        $fields[] = new DataField($purposeLines[1] ?? '');
        $fields[] = new DataField($purposeLines[2] ?? '');

        // Feld 15: Verwendungszweck 4
        $fields[] = new DataField($purposeLines[3] ?? '');

        // Feld 16: Geschäftsvorgangscode (aus Reference.transactionCode)
        $txnCode = $txn->getReference()->getTransactionCode();
        $fields[] = new DataField($txnCode);

        // Feld 17: Währung
        $fields[] = new DataField($currency);

        // Feld 18: Buchungstext (aus Reference.reference)
        $fields[] = new DataField($txn->getReference()->getReference());

        // Feld 19-24: Verwendungszweck 5-10
        for ($i = 4; $i <= 9; $i++) {
            $fields[] = new DataField($purposeLines[$i] ?? '');
        }

        // Felder 25-30: Ursprungsbetrag, Äquivalenzbetrag, Gebühr (leer)
        for ($i = 0; $i < 6; $i++) {
            $fields[] = new DataField('');
        }

        // Felder 31-34: Verwendungszweck 11-14
        for ($i = 10; $i <= 13; $i++) {
            $fields[] = new DataField($purposeLines[$i] ?? '');
        }

        return new DataLine($fields);
    }

    /**
     * Parst eine Account-ID in BLZ und Kontonummer.
     * 
     * Unterstützte Formate:
     * - BLZ/Kontonummer: "12345678/0123456789"
     * - Nur IBAN: "DE89370400440532013000"
     * - Nur Kontonummer: "0123456789"
     * 
     * @return array{blz: string, account: string}
     */
    private static function parseAccountId(string $accountId): array {
        // Format: BLZ/Kontonummer oder BIC/IBAN
        if (str_contains($accountId, '/')) {
            $parts = explode('/', $accountId, 2);
            return [
                'blz' => $parts[0],
                'account' => $parts[1] ?? '',
            ];
        }

        // IBAN-Format erkennen und validieren via BankHelper
        if (BankHelper::isIBAN($accountId)) {
            // Nutze BankHelper für BLZ-Extraktion aus deutscher IBAN
            $ibanParts = BankHelper::splitIBAN($accountId);
            if ($ibanParts !== false) {
                return [
                    'blz' => $ibanParts['BLZ'],
                    'account' => $accountId, // Speichere vollständige IBAN
                ];
            }
            // Andere IBANs: Speichere vollständig als account
            return [
                'blz' => '',
                'account' => $accountId,
            ];
        }

        // Nur Kontonummer
        return [
            'blz' => '',
            'account' => $accountId,
        ];
    }

    /**
     * Teilt den Verwendungszweck in Zeilen mit max. 27 Zeichen.
     * 
     * @return string[]
     */
    private static function splitPurpose(?string $purpose): array {
        if ($purpose === null || $purpose === '') {
            return [];
        }

        // Entferne strukturierte SWIFT-Codes und normalisiere
        $purpose = preg_replace('/\?[\d]{2}/', ' ', $purpose) ?? $purpose;
        $purpose = preg_replace('/\s+/', ' ', trim($purpose));

        $lines = [];
        $words = explode(' ', $purpose);
        $currentLine = '';

        foreach ($words as $word) {
            if ($currentLine === '') {
                $currentLine = $word;
            } elseif (strlen($currentLine . ' ' . $word) <= self::VERWENDUNGSZWECK_MAX_LENGTH) {
                $currentLine .= ' ' . $word;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        // Kürze einzelne Zeilen auf max. Länge
        return array_map(
            fn($line) => substr($line, 0, self::VERWENDUNGSZWECK_MAX_LENGTH),
            $lines
        );
    }

    /**
     * Konvertiert mehrere MT940-Dokumente.
     * 
     * @param Mt940Document[] $documents
     * @return BankTransaction[]
     */
    public static function convertMultiple(array $documents): array {
        $results = [];
        foreach ($documents as $doc) {
            if ($doc instanceof Mt940Document) {
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