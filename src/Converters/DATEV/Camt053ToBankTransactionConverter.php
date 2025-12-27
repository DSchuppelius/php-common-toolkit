<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Camt053ToBankTransactionConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\DATEV;

use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\CSV\DataField;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Helper\Data\BankHelper;
use CommonToolkit\Helper\Data\CurrencyHelper;

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
final class Camt053ToBankTransactionConverter {
    private const VERWENDUNGSZWECK_MAX_LENGTH = 27;
    private const DATE_FORMAT = 'd.m.Y';

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
        $fields = [];

        // Feld 1: BIC des Kontoinhabers
        $fields[] = new DataField($bic);

        // Feld 2: IBAN des Kontoinhabers
        $fields[] = new DataField($iban);

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

        // Feld 8-9: Auftraggeber Name
        $counterparty = self::extractCounterparty($txn);
        $counterpartyLines = self::splitText($counterparty['name'], self::VERWENDUNGSZWECK_MAX_LENGTH);
        $fields[] = new DataField($counterpartyLines[0] ?? '');
        $fields[] = new DataField($counterpartyLines[1] ?? '');

        // Feld 10: BIC Auftraggeber
        $fields[] = new DataField($counterparty['bic']);

        // Feld 11: IBAN Auftraggeber
        $fields[] = new DataField($counterparty['iban']);

        // Feld 12-15: Verwendungszweck 1-4
        $purposeText = self::buildPurposeText($txn);
        $purposeLines = self::splitPurpose($purposeText);
        $fields[] = new DataField($purposeLines[0] ?? '');
        $fields[] = new DataField($purposeLines[1] ?? '');
        $fields[] = new DataField($purposeLines[2] ?? '');
        $fields[] = new DataField($purposeLines[3] ?? '');

        // Feld 16: Geschäftsvorgangscode (Bank Transaction Code)
        $bankTxnCode = $txn->getTransactionCode() ?? '';
        $fields[] = new DataField($bankTxnCode);

        // Feld 17: Währung
        $currency = $txn->getCurrency() ? $txn->getCurrency()->value : $defaultCurrency;
        $fields[] = new DataField($currency);

        // Feld 18: Buchungstext (additionalInfo)
        $additionalInfo = $txn->getAdditionalInfo() ?? '';
        $fields[] = new DataField(substr($additionalInfo, 0, self::VERWENDUNGSZWECK_MAX_LENGTH));

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
     * Extrahiert Gegenpartei-Informationen aus einer CAMT.053-Transaktion.
     * 
     * Die Gegenpartei-Informationen sind in Transaction direkt verfügbar.
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

        // End-to-End-Reference aus Reference
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
     * Extrahiert die BLZ oder BIC aus einer IBAN.
     * 
     * Nutzt BankHelper für IBAN-Validierung und BIC-Lookup aus der Bundesbank-Datenbank.
     * Fallback auf BLZ-Extraktion, wenn kein BIC gefunden wird.
     */
    private static function extractBlzFromIban(string $iban): string {
        // Prüfe zunächst, ob eine gültige IBAN vorliegt
        if (!BankHelper::isIBAN($iban)) {
            return '';
        }

        // Versuche BIC aus der Bundesbank-Datenbank zu ermitteln
        $bic = BankHelper::bicFromIBAN($iban);
        if (!empty($bic)) {
            return $bic;
        }

        // Fallback: BLZ aus IBAN extrahieren (für deutsche IBANs)
        $ibanParts = BankHelper::splitIBAN($iban);
        if ($ibanParts !== false) {
            return $ibanParts['BLZ'];
        }

        return '';
    }

    /**
     * Teilt Text in Zeilen mit maximaler Länge.
     * 
     * @return string[]
     */
    private static function splitText(string $text, int $maxLength): array {
        if ($text === '') {
            return [];
        }

        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';

        foreach ($words as $word) {
            if ($currentLine === '') {
                $currentLine = $word;
            } elseif (strlen($currentLine . ' ' . $word) <= $maxLength) {
                $currentLine .= ' ' . $word;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return array_map(fn($line) => substr($line, 0, $maxLength), $lines);
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

        // Normalisiere Whitespace
        $purpose = preg_replace('/\s+/', ' ', trim($purpose)) ?? $purpose;

        return self::splitText($purpose, self::VERWENDUNGSZWECK_MAX_LENGTH);
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
                } catch (\Throwable) {
                    // Überspringe fehlerhafte Dokumente
                }
            }
        }
        return $results;
    }
}
