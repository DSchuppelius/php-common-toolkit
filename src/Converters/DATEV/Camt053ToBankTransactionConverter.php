<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053ToBankTransactionConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Contracts\Abstracts\DATEV\BankTransactionConverterAbstract;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField as F;
use Throwable;

/**
 * Konvertiert CAMT.053 ISO 20022 Kontoauszüge in das DATEV ASCII-Weiterverarbeitungsformat.
 * 
 * Die Konvertierung mappt CAMT.053-Felder auf die DATEV BankTransaction-Struktur:
 * - accountIdentifier → Feld 2 (IBAN)
 * - servicerBic → Feld 1 (BIC)
 * - sequenceNumber → Feld 3 (Auszugsnummer)
 * - creationDateTime → Feld 4 (Auszugsdatum)
 * - Transaction.valutaDate → Feld 5 (Valuta)
 * - Transaction.bookingDate → Feld 6 (Buchungsdatum)
 * - Transaction.amount → Feld 7 (Umsatz mit +/- Vorzeichen)
 * - Transaction.reference → Verwendungszweck-Felder
 * - Transaction.currency → Feld 17 (Währung)
 * 
 * @package CommonToolkit\Converters\DATEV
 */
final class Camt053ToBankTransactionConverter extends BankTransactionConverterAbstract {

    /**
     * Konvertiert ein CAMT.053-Dokument in ein DATEV BankTransaction-Dokument.
     */
    public static function convert(Camt053Document $document): BankTransaction {
        $rows = [];

        // Extrahiere Kontoinformationen
        $iban = $document->getAccountIdentifier() ?? '';
        $bic = $document->getServicerBic() ?? self::extractBlzFromIban($iban);
        $statementNumber = $document->getSequenceNumber() ?? '000';
        $creationDate = $document->getCreationDateTime();
        $statementDate = $creationDate ? $creationDate->format(self::DATE_FORMAT) : date(self::DATE_FORMAT);
        $currency = $document->getCurrency()->value;

        foreach ($document->getEntries() as $transaction) {
            $rows[] = self::convertTransaction(
                $transaction,
                $bic,
                $iban,
                $statementNumber,
                $statementDate,
                $currency
            );
        }

        return new BankTransaction($rows);
    }

    /**
     * Konvertiert eine CAMT.053-Transaktion in eine DATEV-Datenzeile.
     */
    private static function convertTransaction(Camt053Transaction $txn, string $bic, string $iban, string $statementNumber, string $statementDate, string $defaultCurrency): DataLine {
        $values = self::initializeFieldValues();

        // Grundfelder befüllen
        $values[F::BLZ_BIC_KONTOINHABER->index()] = $bic;
        $values[F::KONTONUMMER_IBAN_KONTOINHABER->index()] = $iban;
        $values[F::AUSZUGSNUMMER->index()] = $statementNumber;
        $values[F::AUSZUGSDATUM->index()] = $statementDate;

        // Datums- und Betragsfelder
        $valutaDate = $txn->getValutaDate() ?? $txn->getBookingDate();
        $values[F::VALUTA->index()] = $valutaDate->format(self::DATE_FORMAT);
        $values[F::BUCHUNGSDATUM->index()] = $txn->getBookingDate()->format(self::DATE_FORMAT);
        $values[F::UMSATZ->index()] = self::formatAmount($txn->getAmount(), $txn->getCreditDebit());

        // Auftraggeber (Counterparty)
        $counterparty = self::extractCounterparty($txn);
        $counterpartyLines = self::splitText($counterparty['name']);
        $values[F::AUFTRAGGEBERNAME_1->index()] = $counterpartyLines[0] ?? '';
        $values[F::AUFTRAGGEBERNAME_2->index()] = $counterpartyLines[1] ?? '';
        $values[F::BLZ_BIC_AUFTRAGGEBER->index()] = $counterparty['bic'];
        $values[F::KONTONUMMER_IBAN_AUFTRAGGEBER->index()] = $counterparty['iban'];

        // Verwendungszweck aufteilen
        $purposeText = self::buildPurposeText($txn);
        $purposeLines = self::splitPurpose($purposeText);
        self::fillVerwendungszweckFelder($values, $purposeLines);

        // Geschäftsvorgangscode und Metadaten
        $values[F::GESCHAEFTSVORGANGSCODE->index()] = $txn->getTransactionCode() ?? '';
        $currency = $txn->getCurrency() ? $txn->getCurrency()->value : $defaultCurrency;
        $values[F::WAEHRUNG->index()] = $currency;

        // Buchungstext (additionalInfo)
        $additionalInfo = $txn->getAdditionalInfo() ?? '';
        $values[F::BUCHUNGSTEXT->index()] = substr($additionalInfo, 0, self::VERWENDUNGSZWECK_MAX_LENGTH);

        return self::createDataLine($values);
    }

    /**
     * Extrahiert Gegenpartei-Informationen aus einer CAMT.053-Transaktion.
     * 
     * @return array{name: string, bic: string, iban: string}
     */
    private static function extractCounterparty(Camt053Transaction $txn): array {
        return [
            'name' => $txn->getCounterpartyName() ?? '',
            'bic' => $txn->getCounterpartyBic() ?? '',
            'iban' => $txn->getCounterpartyIban() ?? '',
        ];
    }

    /**
     * Baut den Verwendungszweck-Text aus den SEPA-Referenzen und Purpose.
     */
    private static function buildPurposeText(Camt053Transaction $txn): string {
        $reference = $txn->getReference();
        $parts = [];

        // End-to-End-Reference
        $endToEndId = $reference->getEndToEndId();
        if ($endToEndId !== null && $endToEndId !== '' && $endToEndId !== 'NOTPROVIDED') {
            $parts[] = 'EREF+' . $endToEndId;
        }

        // Mandatsreferenz
        $mandateId = $reference->getMandateId();
        if ($mandateId !== null && $mandateId !== '') {
            $parts[] = 'MREF+' . $mandateId;
        }

        // Gläubiger-ID
        $creditorId = $reference->getCreditorId();
        if ($creditorId !== null && $creditorId !== '') {
            $parts[] = 'CRED+' . $creditorId;
        }

        // Purpose aus Transaction
        $purpose = $txn->getPurpose();
        if ($purpose !== null && $purpose !== '') {
            if (!empty($parts)) {
                $parts[] = 'SVWZ+' . $purpose;
            } else {
                $parts[] = $purpose;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Konvertiert mehrere CAMT.053-Dokumente.
     * 
     * @param Camt053Document[] $documents
     * @return BankTransaction[]
     */
    public static function convertMultiple(array $documents): array {
        $results = [];
        foreach ($documents as $doc) {
            if ($doc instanceof Camt053Document) {
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
