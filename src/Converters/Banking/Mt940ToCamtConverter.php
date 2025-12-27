<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940ToCamtConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Camt\Balance;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Document as Camt052Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type52\Transaction as Camt052Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Document as Camt053Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Reference as Camt053Reference;
use CommonToolkit\Entities\Common\Banking\Camt\Type53\Transaction as Camt053Transaction;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Document as Camt054Document;
use CommonToolkit\Entities\Common\Banking\Camt\Type54\Transaction as Camt054Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance as Mt9Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Helper\Data\BankHelper;
use DateTimeImmutable;

/**
 * Konvertiert MT940 (SWIFT) Kontoauszüge in das CAMT-Format (ISO 20022).
 * 
 * MT940 ist das ältere SWIFT-Format für Kontoauszüge, während CAMT.053
 * der moderne ISO 20022 Standard ist. Die Konvertierung ermöglicht die
 * Migration von Legacy-Systemen auf den aktuellen Standard.
 * 
 * Unterstützte Zielformate:
 * - CAMT.052: Untertägige Kontobewegungen (Intraday)
 * - CAMT.053: Täglicher Kontoauszug (Standard für MT940-Migration)
 * - CAMT.054: Einzelumsatzbenachrichtigung
 * 
 * @package CommonToolkit\Converters\Banking
 */
final class Mt940ToCamtConverter {
    /**
     * Konvertiert ein MT940-Dokument in ein CAMT.053-Dokument.
     * 
     * Dies ist der Standard-Konvertierungspfad, da MT940 und CAMT.053
     * beide vollständige Tagesauszüge repräsentieren.
     * 
     * @param Mt940Document $mt940 Das zu konvertierende MT940-Dokument
     * @param string|null $messageId Optionale Message-ID (wird sonst generiert)
     * @return Camt053Document
     */
    public static function toCamt053(Mt940Document $mt940, ?string $messageId = null): Camt053Document {
        $messageId ??= self::generateMessageId($mt940);

        $openingBalance = self::convertBalance($mt940->getOpeningBalance(), 'PRCD');
        $closingBalance = self::convertBalance($mt940->getClosingBalance(), 'CLBD');

        $document = new Camt053Document(
            id: $mt940->getReferenceId(),
            creationDateTime: new DateTimeImmutable(),
            accountIdentifier: $mt940->getAccountId(),
            currency: $mt940->getCurrency(),
            accountOwner: null,
            servicerBic: self::extractBicFromAccountId($mt940->getAccountId()),
            messageId: $messageId,
            sequenceNumber: $mt940->getStatementNumber(),
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        foreach ($mt940->getTransactions() as $mt940Txn) {
            $document->addEntry(self::convertTransactionTo053($mt940Txn));
        }

        return $document;
    }

    /**
     * Konvertiert ein MT940-Dokument in ein CAMT.052-Dokument.
     * 
     * CAMT.052 wird für untertägige Reports verwendet. Diese Konvertierung
     * ist sinnvoll, wenn die MT940-Daten Intraday-Bewegungen darstellen.
     * 
     * @param Mt940Document $mt940 Das zu konvertierende MT940-Dokument
     * @param string|null $messageId Optionale Message-ID
     * @return Camt052Document
     */
    public static function toCamt052(Mt940Document $mt940, ?string $messageId = null): Camt052Document {
        $messageId ??= self::generateMessageId($mt940);

        $openingBalance = self::convertBalance($mt940->getOpeningBalance(), 'PRCD');
        $closingBalance = self::convertBalance($mt940->getClosingBalance(), 'CLAV');

        $document = new Camt052Document(
            id: $mt940->getReferenceId(),
            creationDateTime: new DateTimeImmutable(),
            accountIdentifier: $mt940->getAccountId(),
            currency: $mt940->getCurrency(),
            accountOwner: null,
            servicerBic: self::extractBicFromAccountId($mt940->getAccountId()),
            messageId: $messageId,
            sequenceNumber: $mt940->getStatementNumber(),
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        foreach ($mt940->getTransactions() as $mt940Txn) {
            $document->addEntry(self::convertTransactionTo052($mt940Txn));
        }

        return $document;
    }

    /**
     * Konvertiert ein MT940-Dokument in ein CAMT.054-Dokument.
     * 
     * CAMT.054 wird für Einzelumsatzbenachrichtigungen verwendet.
     * Bei dieser Konvertierung werden alle Transaktionen als
     * einzelne Benachrichtigungen behandelt.
     * 
     * @param Mt940Document $mt940 Das zu konvertierende MT940-Dokument
     * @param string|null $messageId Optionale Message-ID
     * @return Camt054Document
     */
    public static function toCamt054(Mt940Document $mt940, ?string $messageId = null): Camt054Document {
        $messageId ??= self::generateMessageId($mt940);

        $document = new Camt054Document(
            id: $mt940->getReferenceId(),
            creationDateTime: new DateTimeImmutable(),
            accountIdentifier: $mt940->getAccountId(),
            currency: $mt940->getCurrency(),
            accountOwner: null,
            servicerBic: self::extractBicFromAccountId($mt940->getAccountId()),
            messageId: $messageId,
            sequenceNumber: $mt940->getStatementNumber()
        );

        foreach ($mt940->getTransactions() as $mt940Txn) {
            $document->addEntry(self::convertTransactionTo054($mt940Txn));
        }

        return $document;
    }

    /**
     * Generische Konvertierung mit wählbarem CAMT-Typ.
     * 
     * @param Mt940Document $mt940 Das zu konvertierende MT940-Dokument
     * @param CamtType $targetType Ziel-CAMT-Typ
     * @param string|null $messageId Optionale Message-ID
     * @return Camt052Document|Camt053Document|Camt054Document
     */
    public static function convert(Mt940Document $mt940, CamtType $targetType, ?string $messageId = null): Camt052Document|Camt053Document|Camt054Document {
        return match ($targetType) {
            CamtType::CAMT052 => self::toCamt052($mt940, $messageId),
            CamtType::CAMT053 => self::toCamt053($mt940, $messageId),
            CamtType::CAMT054 => self::toCamt054($mt940, $messageId),
        };
    }

    /**
     * Konvertiert mehrere MT940-Dokumente in CAMT.053-Dokumente.
     * 
     * @param Mt940Document[] $documents Liste der MT940-Dokumente
     * @return Camt053Document[]
     */
    public static function convertMultipleTo053(array $documents): array {
        return array_map(fn(Mt940Document $doc) => self::toCamt053($doc), $documents);
    }

    /**
     * Konvertiert einen MT940-Balance in einen CAMT-Balance.
     */
    private static function convertBalance(Mt9Balance $mt9Balance, string $type): Balance {
        return new Balance(
            creditDebit: $mt9Balance->getCreditDebit(),
            date: $mt9Balance->getDate(),
            currency: $mt9Balance->getCurrency(),
            amount: $mt9Balance->getAmount(),
            type: $type
        );
    }

    /**
     * Konvertiert eine MT940-Transaktion in eine CAMT.053-Transaktion.
     */
    private static function convertTransactionTo053(Mt940Transaction $mt940Txn): Camt053Transaction {
        $mt940Ref = $mt940Txn->getReference();

        // Referenzen aus MT940 Purpose extrahieren (SEPA-Format)
        $references = self::extractReferencesFromPurpose($mt940Txn->getPurpose());

        $reference = new Camt053Reference(
            endToEndId: $references['endToEndId'],
            mandateId: $references['mandateId'],
            creditorId: $references['creditorId'],
            entryReference: $mt940Ref->getReference(),
            accountServicerReference: null,
            paymentInformationId: null,
            instructionId: $references['instructionId'],
            additional: null
        );

        // Counterparty aus Purpose extrahieren
        $counterparty = self::extractCounterpartyFromPurpose($mt940Txn->getPurpose());

        return new Camt053Transaction(
            bookingDate: $mt940Txn->getDate(),
            valutaDate: $mt940Txn->getValutaDate(),
            amount: $mt940Txn->getAmount(),
            currency: $mt940Txn->getCurrency(),
            creditDebit: $mt940Txn->getCreditDebit(),
            reference: $reference,
            entryReference: $mt940Ref->getReference(),
            accountServicerReference: null,
            status: 'BOOK',
            isReversal: false,
            purpose: self::cleanPurpose($mt940Txn->getPurpose()),
            additionalInfo: null,
            transactionCode: self::mapTransactionCode($mt940Ref->getTransactionCode()),
            counterpartyName: $counterparty['name'],
            counterpartyIban: $counterparty['iban'],
            counterpartyBic: $counterparty['bic']
        );
    }

    /**
     * Konvertiert eine MT940-Transaktion in eine CAMT.052-Transaktion.
     */
    private static function convertTransactionTo052(Mt940Transaction $mt940Txn): Camt052Transaction {
        $mt940Ref = $mt940Txn->getReference();

        return new Camt052Transaction(
            bookingDate: $mt940Txn->getDate(),
            valutaDate: $mt940Txn->getValutaDate(),
            amount: $mt940Txn->getAmount(),
            currency: $mt940Txn->getCurrency(),
            creditDebit: $mt940Txn->getCreditDebit(),
            entryReference: $mt940Ref->getReference(),
            accountServicerReference: null,
            status: 'BOOK',
            isReversal: false
        );
    }

    /**
     * Konvertiert eine MT940-Transaktion in eine CAMT.054-Transaktion.
     */
    private static function convertTransactionTo054(Mt940Transaction $mt940Txn): Camt054Transaction {
        $mt940Ref = $mt940Txn->getReference();

        return new Camt054Transaction(
            bookingDate: $mt940Txn->getDate(),
            valutaDate: $mt940Txn->getValutaDate(),
            amount: $mt940Txn->getAmount(),
            currency: $mt940Txn->getCurrency(),
            creditDebit: $mt940Txn->getCreditDebit(),
            entryReference: $mt940Ref->getReference(),
            accountServicerReference: null,
            status: 'BOOK',
            isReversal: false
        );
    }

    /**
     * Generiert eine Message-ID aus dem MT940-Dokument.
     */
    private static function generateMessageId(Mt940Document $mt940): string {
        $base = 'MT940-' . $mt940->getReferenceId() . '-' . date('YmdHis');
        return substr(preg_replace('/[^A-Za-z0-9\-]/', '', $base), 0, 35);
    }

    /**
     * Extrahiert BIC aus der Account-ID wenn möglich.
     * 
     * MT940 Account-ID kann verschiedene Formate haben:
     * - IBAN (DE89370400440532013000)
     * - BIC/Kontonummer (COBADEFFXXX/1234567890)
     * - BLZ/Kontonummer (37040044/532013000)
     */
    private static function extractBicFromAccountId(string $accountId): ?string {
        // Prüfe auf BIC am Anfang (vor / oder Leerzeichen)
        $parts = preg_split('/[\/\s]/', $accountId, 2);
        if (!empty($parts[0]) && BankHelper::isBIC($parts[0])) {
            return $parts[0];
        }

        return null;
    }

    /**
     * Extrahiert SEPA-Referenzen aus dem MT940 Verwendungszweck.
     * 
     * SEPA-Felder im Verwendungszweck:
     * - EREF+ = Ende-zu-Ende-Referenz
     * - MREF+ = Mandatsreferenz
     * - CRED+ = Gläubiger-ID
     * - KREF+ = Kundenreferenz (Instruction-ID)
     * - SVWZ+ = Verwendungszweck
     * 
     * @return array{endToEndId: ?string, mandateId: ?string, creditorId: ?string, instructionId: ?string}
     */
    private static function extractReferencesFromPurpose(?string $purpose): array {
        $result = [
            'endToEndId' => null,
            'mandateId' => null,
            'creditorId' => null,
            'instructionId' => null,
        ];

        if ($purpose === null) {
            return $result;
        }

        // EREF+ (Ende-zu-Ende-Referenz)
        if (preg_match('/EREF\+([^\s+]+)/', $purpose, $matches)) {
            $result['endToEndId'] = $matches[1];
        }

        // MREF+ (Mandatsreferenz)
        if (preg_match('/MREF\+([^\s+]+)/', $purpose, $matches)) {
            $result['mandateId'] = $matches[1];
        }

        // CRED+ (Gläubiger-ID)
        if (preg_match('/CRED\+([^\s+]+)/', $purpose, $matches)) {
            $result['creditorId'] = $matches[1];
        }

        // KREF+ (Kundenreferenz / Instruction-ID)
        if (preg_match('/KREF\+([^\s+]+)/', $purpose, $matches)) {
            $result['instructionId'] = $matches[1];
        }

        return $result;
    }

    /**
     * Extrahiert Counterparty-Informationen aus dem MT940 Verwendungszweck.
     * 
     * @return array{name: ?string, iban: ?string, bic: ?string}
     */
    private static function extractCounterpartyFromPurpose(?string $purpose): array {
        $result = [
            'name' => null,
            'iban' => null,
            'bic' => null,
        ];

        if ($purpose === null) {
            return $result;
        }

        // IBAN via BankHelper extrahieren
        if (preg_match('/([A-Z]{2}\d{2}[A-Z0-9]{4}\d{7}[A-Z0-9]*)/', $purpose, $matches)) {
            $potentialIban = $matches[1];
            if (BankHelper::isIBAN($potentialIban)) {
                $result['iban'] = $potentialIban;
            }
        }

        // BIC via BankHelper validieren
        if (preg_match('/\b([A-Z]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?)\b/', $purpose, $matches)) {
            $potentialBic = $matches[1];
            // Nur wenn es nicht die IBAN ist und ein gültiger BIC
            if (!str_contains($purpose, $potentialBic . '/') && BankHelper::isBIC($potentialBic)) {
                $result['bic'] = $potentialBic;
            }
        }

        return $result;
    }

    /**
     * Bereinigt den Verwendungszweck von SEPA-Tags.
     */
    private static function cleanPurpose(?string $purpose): ?string {
        if ($purpose === null) {
            return null;
        }

        // SVWZ+ extrahieren wenn vorhanden
        if (preg_match('/SVWZ\+(.+?)(?:\+[A-Z]{4}|$)/s', $purpose, $matches)) {
            return trim($matches[1]);
        }

        // Ansonsten SEPA-Tags entfernen und bereinigen
        $cleaned = preg_replace('/[A-Z]{4}\+[^\s+]*/', '', $purpose);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned) ?: null;
    }

    /**
     * Mappt MT940 Transaktionscodes auf ISO 20022 Codes.
     * 
     * MT940 verwendet 3-stellige Transaktionscodes (GVC),
     * während ISO 20022 4-stellige Codes verwendet.
     */
    private static function mapTransactionCode(string $mt940Code): string {
        // Häufige MT940-Codes zu ISO 20022 Mapping
        return match (strtoupper($mt940Code)) {
            'TRF', 'TRA' => 'NTRF',  // Transfer
            'CHK' => 'NCHK',          // Cheque
            'BOE' => 'NBOE',          // Bill of Exchange
            'DCR' => 'NDCR',          // Documentary Credit
            'LCR' => 'NLCR',          // Letter of Credit
            'MSC' => 'NMSC',          // Miscellaneous
            'CHG' => 'NCHG',          // Charges
            'INT' => 'NINT',          // Interest
            'DIV' => 'NDIV',          // Dividend
            'RTI' => 'NRTI',          // Return Item
            default => 'NTRF',        // Default: Transfer
        };
    }
}
