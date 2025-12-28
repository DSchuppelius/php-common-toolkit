<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940ToBankTransactionConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Contracts\Abstracts\DATEV\BankTransactionConverterAbstract;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField as F;
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
final class Mt940ToBankTransactionConverter extends BankTransactionConverterAbstract {

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
        $values = self::initializeFieldValues();

        // Grundfelder befüllen
        $values[F::BLZ_BIC_KONTOINHABER->index()] = $accountInfo['blz'] ?? '';
        $values[F::KONTONUMMER_IBAN_KONTOINHABER->index()] = $accountInfo['account'] ?? '';
        $values[F::AUSZUGSNUMMER->index()] = $statementNumber;
        $values[F::AUSZUGSDATUM->index()] = $statementDate;

        // Datums- und Betragsfelder
        $valutaDate = $txn->getValutaDate() ?? $txn->getBookingDate();
        $values[F::VALUTA->index()] = $valutaDate->format(self::DATE_FORMAT);
        $values[F::BUCHUNGSDATUM->index()] = $txn->getBookingDate()->format(self::DATE_FORMAT);
        $values[F::UMSATZ->index()] = self::formatAmount($txn->getAmount(), $txn->getCreditDebit());

        // Verwendungszweck aufteilen (SWIFT-Codes entfernen)
        $purpose = $txn->getPurpose() ?? '';
        $purpose = preg_replace('/\?[\d]{2}/', ' ', $purpose) ?? $purpose;
        $purposeLines = self::splitPurpose($purpose);
        self::fillVerwendungszweckFelder($values, $purposeLines);

        // Geschäftsvorgangscode und Metadaten
        $values[F::GESCHAEFTSVORGANGSCODE->index()] = $txn->getReference()->getTransactionCode();
        $values[F::WAEHRUNG->index()] = $currency;
        $values[F::BUCHUNGSTEXT->index()] = $txn->getReference()->getReference();

        return self::createDataLine($values);
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
