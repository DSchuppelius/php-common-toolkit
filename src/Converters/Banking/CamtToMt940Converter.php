<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtToMt940Converter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Camt\Balance as CamtBalance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction as Camt052Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction as Camt054Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance as Mt9Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference as Mt9Reference;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use DateTimeImmutable;
use RuntimeException;

/**
 * Konvertiert CAMT-Dokumente (ISO 20022) in das MT940-Format (SWIFT).
 * 
 * Diese Konvertierung ist nützlich für Legacy-Systeme, die nur das
 * ältere MT940-Format unterstützen. ISO 20022 CAMT-Nachrichten werden
 * in das kompaktere SWIFT-Format umgewandelt.
 * 
 * **Hinweis**: Bei der Konvertierung gehen Details verloren, da MT940
 * weniger strukturierte Felder als CAMT hat. Insbesondere:
 * - Detaillierte Referenzinformationen werden in den Verwendungszweck gepackt
 * - Counterparty-Daten werden als SEPA-Tags im Verwendungszweck codiert
 * - Nur Opening und Closing Balance werden übernommen
 * 
 * Unterstützte Quellformate:
 * - CAMT.052: Untertägige Kontobewegungen
 * - CAMT.053: Täglicher Kontoauszug (Standard-Quellformat)
 * - CAMT.054: Einzelumsatzbenachrichtigung
 * 
 * @package CommonToolkit\Converters\Banking
 */
final class CamtToMt940Converter {
    /**
     * Konvertiert ein CAMT.053-Dokument in ein MT940-Dokument.
     * 
     * Dies ist der Standard-Konvertierungspfad, da CAMT.053 und MT940
     * beide vollständige Tagesauszüge repräsentieren.
     * 
     * @param Camt053Document $camt053 Das zu konvertierende CAMT.053-Dokument
     * @param string|null $referenceId Optionale Referenz-ID (wird sonst generiert)
     * @return Mt940Document
     */
    public static function fromCamt053(Camt053Document $camt053, ?string $referenceId = null): Mt940Document {
        $referenceId ??= self::generateReferenceId($camt053->getId());

        $openingBalance = self::convertBalance($camt053->getOpeningBalance(), 'F');
        $closingBalance = self::convertBalance($camt053->getClosingBalance(), 'F');

        // Fallback-Balances falls nicht vorhanden
        if ($openingBalance === null) {
            $openingBalance = self::createZeroBalance($camt053->getCurrency(), $camt053->getCreationDateTime(), 'F');
        }
        if ($closingBalance === null) {
            $closingBalance = self::createZeroBalance($camt053->getCurrency(), $camt053->getCreationDateTime(), 'F');
        }

        $transactions = [];
        foreach ($camt053->getEntries() as $entry) {
            $transactions[] = self::convertTransactionFrom053($entry);
        }

        return new Mt940Document(
            accountId: $camt053->getAccountIdentifier(),
            referenceId: $referenceId,
            statementNumber: $camt053->getSequenceNumber() ?? '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions,
            creationDateTime: $camt053->getCreationDateTime()
        );
    }

    /**
     * Konvertiert ein CAMT.052-Dokument in ein MT940-Dokument.
     * 
     * CAMT.052 enthält untertägige Buchungen. Da MT940 keinen
     * direkten Intraday-Typ hat, wird ein Standard-MT940 erzeugt.
     * 
     * @param Camt052Document $camt052 Das zu konvertierende CAMT.052-Dokument
     * @param string|null $referenceId Optionale Referenz-ID
     * @return Mt940Document
     */
    public static function fromCamt052(Camt052Document $camt052, ?string $referenceId = null): Mt940Document {
        $referenceId ??= self::generateReferenceId($camt052->getId());

        $openingBalance = self::convertBalance($camt052->getOpeningBalance(), 'F');
        $closingBalance = self::convertBalance($camt052->getClosingBalance(), 'F');

        // Fallback-Balances
        if ($openingBalance === null) {
            $openingBalance = self::createZeroBalance($camt052->getCurrency(), $camt052->getCreationDateTime(), 'F');
        }
        if ($closingBalance === null) {
            $closingBalance = self::createZeroBalance($camt052->getCurrency(), $camt052->getCreationDateTime(), 'F');
        }

        $transactions = [];
        foreach ($camt052->getEntries() as $entry) {
            $transactions[] = self::convertTransactionFrom052($entry);
        }

        return new Mt940Document(
            accountId: $camt052->getAccountIdentifier(),
            referenceId: $referenceId,
            statementNumber: $camt052->getSequenceNumber() ?? '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions,
            creationDateTime: $camt052->getCreationDateTime()
        );
    }

    /**
     * Konvertiert ein CAMT.054-Dokument in ein MT940-Dokument.
     * 
     * CAMT.054 enthält Einzelumsätze ohne Salden. Die Opening/Closing
     * Balances werden daher auf 0 gesetzt.
     * 
     * @param Camt054Document $camt054 Das zu konvertierende CAMT.054-Dokument
     * @param string|null $referenceId Optionale Referenz-ID
     * @param Mt9Balance|null $openingBalance Optionaler Opening Balance
     * @param Mt9Balance|null $closingBalance Optionaler Closing Balance
     * @return Mt940Document
     */
    public static function fromCamt054(
        Camt054Document $camt054,
        ?string $referenceId = null,
        ?Mt9Balance $openingBalance = null,
        ?Mt9Balance $closingBalance = null
    ): Mt940Document {
        $referenceId ??= self::generateReferenceId($camt054->getId());

        // CAMT.054 hat keine Salden, daher Fallbacks
        $openingBalance ??= self::createZeroBalance($camt054->getCurrency(), $camt054->getCreationDateTime(), 'F');
        $closingBalance ??= self::createZeroBalance($camt054->getCurrency(), $camt054->getCreationDateTime(), 'F');

        $transactions = [];
        foreach ($camt054->getEntries() as $entry) {
            $transactions[] = self::convertTransactionFrom054($entry);
        }

        return new Mt940Document(
            accountId: $camt054->getAccountIdentifier(),
            referenceId: $referenceId,
            statementNumber: $camt054->getSequenceNumber() ?? '00001',
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            transactions: $transactions,
            creationDateTime: $camt054->getCreationDateTime()
        );
    }

    /**
     * Generische Konvertierung - erkennt den CAMT-Typ automatisch.
     * 
     * @param Camt052Document|Camt053Document|Camt054Document $camtDocument
     * @param string|null $referenceId Optionale Referenz-ID
     * @return Mt940Document
     */
    public static function convert(
        Camt052Document|Camt053Document|Camt054Document $camtDocument,
        ?string $referenceId = null
    ): Mt940Document {
        return match (true) {
            $camtDocument instanceof Camt053Document => self::fromCamt053($camtDocument, $referenceId),
            $camtDocument instanceof Camt052Document => self::fromCamt052($camtDocument, $referenceId),
            $camtDocument instanceof Camt054Document => self::fromCamt054($camtDocument, $referenceId),
        };
    }

    /**
     * Konvertiert mehrere CAMT.053-Dokumente in MT940-Dokumente.
     * 
     * @param Camt053Document[] $documents Liste der CAMT.053-Dokumente
     * @return Mt940Document[]
     */
    public static function convertMultipleFromCamt053(array $documents): array {
        return array_map(fn(Camt053Document $doc) => self::fromCamt053($doc), $documents);
    }

    /**
     * Konvertiert einen CAMT-Balance in einen MT9-Balance.
     */
    private static function convertBalance(?CamtBalance $camtBalance, string $type): ?Mt9Balance {
        if ($camtBalance === null) {
            return null;
        }

        return new Mt9Balance(
            creditDebit: $camtBalance->getCreditDebit(),
            date: $camtBalance->getDate(),
            currency: $camtBalance->getCurrency(),
            amount: $camtBalance->getAmount(),
            type: $type
        );
    }

    /**
     * Erstellt einen Zero-Balance als Fallback.
     */
    private static function createZeroBalance(
        CurrencyCode $currency,
        DateTimeImmutable $date,
        string $type
    ): Mt9Balance {
        return new Mt9Balance(
            creditDebit: CreditDebit::CREDIT,
            date: $date,
            currency: $currency,
            amount: 0.0,
            type: $type
        );
    }

    /**
     * Konvertiert eine CAMT.053-Transaktion in eine MT940-Transaktion.
     */
    private static function convertTransactionFrom053(Camt053Transaction $camtTxn): Mt940Transaction {
        $reference = self::createReference($camtTxn);
        $purpose = self::buildPurpose053($camtTxn);

        return new Mt940Transaction(
            bookingDate: $camtTxn->getBookingDate(),
            valutaDate: $camtTxn->getValutaDate(),
            amount: $camtTxn->getAmount(),
            creditDebit: $camtTxn->getCreditDebit(),
            currency: $camtTxn->getCurrency(),
            reference: $reference,
            purpose: $purpose
        );
    }

    /**
     * Konvertiert eine CAMT.052-Transaktion in eine MT940-Transaktion.
     */
    private static function convertTransactionFrom052(Camt052Transaction $camtTxn): Mt940Transaction {
        $reference = new Mt9Reference(
            transactionCode: 'TRF',
            reference: self::truncate($camtTxn->getEntryReference() ?? 'NOTPROVIDED', 13),
            bankReference: $camtTxn->getAccountServicerReference()
        );

        return new Mt940Transaction(
            bookingDate: $camtTxn->getBookingDate(),
            valutaDate: $camtTxn->getValutaDate(),
            amount: $camtTxn->getAmount(),
            creditDebit: $camtTxn->getCreditDebit(),
            currency: $camtTxn->getCurrency(),
            reference: $reference,
            purpose: null
        );
    }

    /**
     * Konvertiert eine CAMT.054-Transaktion in eine MT940-Transaktion.
     */
    private static function convertTransactionFrom054(Camt054Transaction $camtTxn): Mt940Transaction {
        $reference = new Mt9Reference(
            transactionCode: 'TRF',
            reference: self::truncate($camtTxn->getEntryReference() ?? 'NOTPROVIDED', 13),
            bankReference: $camtTxn->getAccountServicerReference()
        );

        return new Mt940Transaction(
            bookingDate: $camtTxn->getBookingDate(),
            valutaDate: $camtTxn->getValutaDate(),
            amount: $camtTxn->getAmount(),
            creditDebit: $camtTxn->getCreditDebit(),
            currency: $camtTxn->getCurrency(),
            reference: $reference,
            purpose: null
        );
    }

    /**
     * Erstellt eine MT9-Reference aus einer CAMT.053-Transaktion.
     */
    private static function createReference(Camt053Transaction $camtTxn): Mt9Reference {
        $transactionCode = self::mapTransactionCodeToMt940($camtTxn->getTransactionCode());

        // Referenz aus Entry-Reference oder End-to-End-ID
        $ref = $camtTxn->getEntryReference()
            ?? $camtTxn->getReference()->getEndToEndId()
            ?? 'NOTPROVIDED';

        return new Mt9Reference(
            transactionCode: $transactionCode,
            reference: self::truncate($ref, 13), // Max. 13 Zeichen nach dem 3-stelligen Code
            bankReference: $camtTxn->getAccountServicerReference()
        );
    }

    /**
     * Baut den MT940-Verwendungszweck aus einer CAMT.053-Transaktion.
     * 
     * Formatiert SEPA-Tags im MT940-kompatiblen Format:
     * - EREF+ Ende-zu-Ende-Referenz
     * - MREF+ Mandatsreferenz
     * - CRED+ Gläubiger-ID
     * - KREF+ Kundenreferenz
     * - SVWZ+ Verwendungszweck
     * - IBAN+ Gegenseiten-IBAN
     * - BIC+  Gegenseiten-BIC
     * - NAME+ Gegenseiten-Name
     */
    private static function buildPurpose053(Camt053Transaction $camtTxn): string {
        $parts = [];

        $ref = $camtTxn->getReference();

        // SEPA-Referenzen
        if ($ref->getEndToEndId() !== null && $ref->getEndToEndId() !== 'NOTPROVIDED') {
            $parts[] = 'EREF+' . $ref->getEndToEndId();
        }
        if ($ref->getMandateId() !== null) {
            $parts[] = 'MREF+' . $ref->getMandateId();
        }
        if ($ref->getCreditorId() !== null) {
            $parts[] = 'CRED+' . $ref->getCreditorId();
        }
        if ($ref->getInstructionId() !== null) {
            $parts[] = 'KREF+' . $ref->getInstructionId();
        }

        // Counterparty-Daten
        if ($camtTxn->getCounterpartyName() !== null) {
            $parts[] = 'NAME+' . $camtTxn->getCounterpartyName();
        }
        if ($camtTxn->getCounterpartyIban() !== null) {
            $parts[] = 'IBAN+' . $camtTxn->getCounterpartyIban();
        }
        if ($camtTxn->getCounterpartyBic() !== null) {
            $parts[] = 'BIC+' . $camtTxn->getCounterpartyBic();
        }

        // Verwendungszweck
        if ($camtTxn->getPurpose() !== null) {
            $parts[] = 'SVWZ+' . $camtTxn->getPurpose();
        }

        return implode(' ', $parts);
    }

    /**
     * Mappt ISO 20022 Transaktionscodes auf MT940 Codes.
     */
    private static function mapTransactionCodeToMt940(?string $iso20022Code): string {
        if ($iso20022Code === null) {
            return 'TRF';
        }

        return match (strtoupper($iso20022Code)) {
            'NTRF' => 'TRF',  // Transfer
            'NCHK' => 'CHK',  // Cheque
            'NBOE' => 'BOE',  // Bill of Exchange
            'NDCR' => 'DCR',  // Documentary Credit
            'NLCR' => 'LCR',  // Letter of Credit
            'NMSC' => 'MSC',  // Miscellaneous
            'NCHG' => 'CHG',  // Charges
            'NINT' => 'INT',  // Interest
            'NDIV' => 'DIV',  // Dividend
            'NRTI' => 'RTI',  // Return Item
            default => 'TRF', // Default: Transfer
        };
    }

    /**
     * Generiert eine Referenz-ID aus der CAMT Message-ID.
     */
    private static function generateReferenceId(string $camtId): string {
        // MT940 Referenz max. 16 Zeichen
        $clean = preg_replace('/[^A-Za-z0-9\-]/', '', $camtId);
        return substr($clean, 0, 16) ?: 'CAMT-REF';
    }

    /**
     * Kürzt einen String auf die maximale Länge.
     */
    private static function truncate(string $value, int $maxLength): string {
        return substr($value, 0, $maxLength);
    }
}