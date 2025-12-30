<?php
/*
 * Created on   : Mon Dec 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MtInterConverter.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Converters\Banking;

use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document as Mt940Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction as Mt940Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Type941\Document as Mt941Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Document as Mt942Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Type942\Transaction as Mt942Transaction;
use CommonToolkit\Enums\CreditDebit;

/**
 * Konverter zwischen MT940, MT941 und MT942 Formaten.
 * 
 * Die SWIFT MT9xx-Formate haben unterschiedliche Zwecke:
 * - MT940: Tageskontoauszug mit vollständigen Transaktionen
 * - MT941: Nur Saldeninformation ohne Transaktionsdetails
 * - MT942: Untertägige (Interim) Umsätze
 * 
 * @package CommonToolkit\Converters\Banking
 */
final class MtInterConverter {
    /**
     * Konvertiert MT940 zu MT941 (nur Salden, keine Transaktionen).
     * 
     * Verwendung: Schnelle Saldenübersicht aus vollständigem Kontoauszug.
     */
    public static function mt940ToMt941(Mt940Document $mt940): Mt941Document {
        return new Mt941Document(
            accountId: $mt940->getAccountId(),
            referenceId: $mt940->getReferenceId(),
            statementNumber: $mt940->getStatementNumber(),
            openingBalance: $mt940->getOpeningBalance(),
            closingBalance: $mt940->getClosingBalance(),
            closingAvailableBalance: $mt940->getClosingAvailableBalance(),
            forwardAvailableBalances: [],
            creationDateTime: $mt940->getCreationDateTime()
        );
    }

    /**
     * Konvertiert MT940 zu MT942 (Interim-Format).
     * 
     * Verwendung: Untertägige Darstellung eines Tagesauszugs.
     * Die Transaktionen werden zu MT942-Transaktionen konvertiert.
     */
    public static function mt940ToMt942(Mt940Document $mt940): Mt942Document {
        $mt942Transactions = [];

        foreach ($mt940->getTransactions() as $mt940Txn) {
            $mt942Transactions[] = new Mt942Transaction(
                bookingDate: $mt940Txn->getBookingDate(),
                valutaDate: $mt940Txn->getValutaDate(),
                amount: $mt940Txn->getAmount(),
                creditDebit: $mt940Txn->getCreditDebit(),
                currency: $mt940Txn->getCurrency(),
                reference: $mt940Txn->getReference(),
                purpose: $mt940Txn->getPurpose()
            );
        }

        return new Mt942Document(
            accountId: $mt940->getAccountId(),
            referenceId: $mt940->getReferenceId(),
            statementNumber: $mt940->getStatementNumber(),
            closingBalance: $mt940->getClosingBalance(),
            transactions: $mt942Transactions,
            openingBalance: $mt940->getOpeningBalance(),
            floorLimitIndicator: null,
            dateTimeIndicator: null,
            creationDateTime: $mt940->getCreationDateTime()
        );
    }

    /**
     * Konvertiert MT942 zu MT940 (Final-Format).
     * 
     * Verwendung: Tagesabschluss aus untertägigen Umsätzen erstellen.
     * Bei fehlendem Opening Balance wird ein Null-Saldo verwendet.
     */
    public static function mt942ToMt940(Mt942Document $mt942): Mt940Document {
        $openingBalance = $mt942->getOpeningBalance();

        // Wenn kein Opening Balance vorhanden, berechne rückwärts aus Closing und Transaktionen
        if ($openingBalance === null) {
            $openingBalance = self::calculateOpeningBalance($mt942);
        }

        $mt940Transactions = [];

        foreach ($mt942->getTransactions() as $mt942Txn) {
            $mt940Transactions[] = new Mt940Transaction(
                bookingDate: $mt942Txn->getBookingDate(),
                valutaDate: $mt942Txn->getValutaDate(),
                amount: $mt942Txn->getAmount(),
                creditDebit: $mt942Txn->getCreditDebit(),
                currency: $mt942Txn->getCurrency(),
                reference: $mt942Txn->getReference(),
                purpose: $mt942Txn->getPurpose()
            );
        }

        return new Mt940Document(
            accountId: $mt942->getAccountId(),
            referenceId: $mt942->getReferenceId(),
            statementNumber: $mt942->getStatementNumber(),
            openingBalance: $openingBalance,
            closingBalance: $mt942->getClosingBalance(),
            transactions: $mt940Transactions,
            closingAvailableBalance: null,
            forwardAvailableBalance: null,
            creationDateTime: $mt942->getCreationDateTime()
        );
    }

    /**
     * Konvertiert MT941 zu MT940 (ohne Transaktionen).
     * 
     * Verwendung: Leeres MT940-Dokument aus Saldeninformation.
     * Achtung: Transaktionen können nicht rekonstruiert werden!
     */
    public static function mt941ToMt940(Mt941Document $mt941): Mt940Document {
        return new Mt940Document(
            accountId: $mt941->getAccountId(),
            referenceId: $mt941->getReferenceId(),
            statementNumber: $mt941->getStatementNumber(),
            openingBalance: $mt941->getOpeningBalance(),
            closingBalance: $mt941->getClosingBalance(),
            transactions: [], // Keine Transaktionen in MT941
            closingAvailableBalance: $mt941->getClosingAvailableBalance(),
            forwardAvailableBalance: null,
            creationDateTime: $mt941->getCreationDateTime()
        );
    }

    /**
     * Berechnet den Opening Balance aus Closing Balance und Transaktionen.
     * 
     * Opening = Closing - Credits + Debits
     */
    private static function calculateOpeningBalance(Mt942Document $mt942): Balance {
        $closingBalance = $mt942->getClosingBalance();
        $closingAmount = $closingBalance->getAmount();

        // Vorzeichen basierend auf Credit/Debit
        if ($closingBalance->getCreditDebit() === CreditDebit::DEBIT) {
            $closingAmount = -$closingAmount;
        }

        $totalMovement = 0.0;
        foreach ($mt942->getTransactions() as $txn) {
            if ($txn->getCreditDebit() === CreditDebit::CREDIT) {
                $totalMovement += $txn->getAmount();
            } else {
                $totalMovement -= $txn->getAmount();
            }
        }

        $openingAmount = $closingAmount - $totalMovement;

        $creditDebit = $openingAmount >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;

        return new Balance(
            creditDebit: $creditDebit,
            date: $closingBalance->getDate(),
            currency: $closingBalance->getCurrency(),
            amount: abs($openingAmount)
        );
    }
}