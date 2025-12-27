<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940DocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Builders\Mt940DocumentBuilder;
use CommonToolkit\Entities\Common\Banking\Mt9\Balance;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Document;
use CommonToolkit\Entities\Common\Banking\Mt9\Purpose;
use CommonToolkit\Entities\Common\Banking\Mt9\Type940\Transaction;
use CommonToolkit\Entities\Common\Banking\Mt9\Reference;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class Mt940DocumentParser {
    public static function parse(string $rawBlock): Document {
        $lines = preg_split('/\r\n|\n|\r/', trim($rawBlock));
        $transactions = [];
        $accountId = null;
        $referenceId = 'COMMON';
        $statementNumber = '00000';
        $openingBalance = null;
        $closingBalance = null;

        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];

            if (str_starts_with($line, ':20:')) {
                $referenceId = trim(substr($line, 4));
            } elseif (str_starts_with($line, ':25:')) {
                $accountId = trim(substr($line, 4));
            } elseif (str_starts_with($line, ':28C:')) {
                $statementNumber = trim(substr($line, 5));
            } elseif (str_starts_with($line, ':60F:')) {
                $openingBalance = self::parseBalance(trim(substr($line, 5)));
            } elseif (str_starts_with($line, ':62F:')) {
                $closingBalance = self::parseBalance(trim(substr($line, 5)));
            } elseif (str_starts_with($line, ':61:')) {
                $bookingLine = $line;
                $i++;

                $purposeLines = [];
                if (isset($lines[$i]) && str_starts_with($lines[$i], ':86:')) {
                    $purposeLines[] = trim(substr($lines[$i], 4));
                    $i++;

                    while ($i < count($lines) && str_starts_with($lines[$i], '?')) {
                        $purposeLines[] = trim(substr($lines[$i], 3));
                        $i++;
                    }
                }

                $purpose = implode(' ', $purposeLines);

                try {
                    // MT940 :61: Format laut DATEV-Spezifikation (Dok.-Nr. 9226962):
                    // :61:[Valuta JJMMTT][Buchungsdatum MMTT optional][Soll/Haben C|D|RC|RD][Währung 1 Zeichen optional][Betrag][Buchungsschlüssel N+3][Referenz]//[Bankreferenz]
                    // Beispiel: 2201010101C100,00NTRFNONREF//123456
                    // - 220101 = Valutadatum (JJMMTT) - Pflicht
                    // - 0101 = Buchungsdatum (MMTT) - optional
                    // - C = Credit (oder D, RC, RD)
                    // - 100,00 = Betrag mit Komma
                    // - N = Kennzeichen (immer N für SWIFT)
                    // - TRF = Transaktionscode (3 Zeichen)
                    // - NONREF = Kundenreferenz
                    // - // = Separator
                    // - 123456 = Bankreferenz

                    // Erweitertes Regex für alle Fälle (mit und ohne //, mit RC/RD)
                    if (preg_match('/^:61:(\d{6})(\d{4})?(R?[CD])([A-Z])?([0-9,]+)N([A-Z]{3})([^\/]*?)(?:\/\/(.*))?$/i', $bookingLine, $match)) {
                        // Valutadatum ist immer das erste Datum (JJMMTT)
                        $valutaDate = DateTimeImmutable::createFromFormat('ymd', $match[1]) ?: throw new RuntimeException("Ungültiges Valutadatum");

                        // Buchungsdatum ist optional (MMTT), wenn vorhanden mit Jahr von Valuta ergänzen
                        $bookingDate = !empty($match[2])
                            ? DateTimeImmutable::createFromFormat('Ymd', $valutaDate->format('Y') . $match[2])
                            : $valutaDate; // Falls kein Buchungsdatum, verwende Valuta

                        if ($bookingDate === false) {
                            $bookingDate = $valutaDate;
                        }

                        $creditDebit = CreditDebit::fromMt940Code($match[3]);
                        // $match[4] wäre der optionale Währungsbuchstabe (letzte Stelle ISO-Code)
                        $amount = (float) CurrencyHelper::deToUs($match[5]);
                        $transactionCode = 'N' . $match[6]; // N + 3-stelliger Code
                        $reference = trim($match[7] ?? '');
                        $bankReference = trim($match[8] ?? '');

                        $transactions[] = new Transaction(
                            bookingDate: $bookingDate,
                            valutaDate: $valutaDate,
                            amount: $amount,
                            creditDebit: $creditDebit,
                            currency: $openingBalance?->getCurrency() ?? CurrencyCode::Euro,
                            reference: new Reference($transactionCode, $reference ?: 'NONREF'),
                            purpose: $purpose
                        );
                    }
                } catch (Throwable $e) {
                    // Logging optional
                }

                continue;
            }

            $i++;
        }

        if (!$accountId || !$openingBalance || !$closingBalance) {
            throw new RuntimeException("Fehlende Pflichtinformationen im MT940-Block");
        }

        return (new Mt940DocumentBuilder())
            ->setAccountId($accountId)
            ->setReferenceId($referenceId)
            ->setStatementNumber($statementNumber)
            ->setOpeningBalance($openingBalance)
            ->setClosingBalance($closingBalance)
            ->addTransactions($transactions)
            ->build();
    }

    private static function parseBalance(string $raw): Balance {
        if (!preg_match('/^([CD])(\d{6})([A-Z]{3})([0-9,]+)$/', $raw, $matches)) {
            throw new RuntimeException("Balance-String ungültig: $raw");
        }

        return new Balance(
            creditDebit: CreditDebit::fromMt940Code($matches[1]),
            date: DateTimeImmutable::createFromFormat('ymd', $matches[2]) ?: throw new RuntimeException("Datum ungültig"),
            currency: CurrencyCode::tryFrom($matches[3]) ?? throw new RuntimeException("Währung ungültig: {$matches[3]}"),
            amount: (float) str_replace(',', '.', $matches[4])
        );
    }
}
