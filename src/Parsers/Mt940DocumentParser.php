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
use CommonToolkit\Entities\Banking\Mt940\Mt940Balance;
use CommonToolkit\Entities\Banking\Mt940\Mt940Document;
use CommonToolkit\Entities\Banking\Mt940\Mt940Transaction;
use CommonToolkit\Entities\Banking\Mt940\Mt940Reference;
use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class Mt940DocumentParser {
    public static function parse(string $rawBlock): Mt940Document {
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
                    if (preg_match('/^:61:(\d{6})(\d{4})?([CD])(\d+,\d+)([A-Z]{3,4})([A-Z0-9]*)\/\/(.*)$/', $bookingLine, $match)) {
                        $date = DateTimeImmutable::createFromFormat('ymd', $match[1]) ?: throw new RuntimeException("Ungültiges Buchungsdatum");
                        $valutaDate = isset($match[2])
                            ? DateTimeImmutable::createFromFormat('Ymd', $date->format('Y') . $match[2])
                            : null;

                        $creditDebit = CreditDebit::fromMt940Code($match[3]);
                        $amount = (float) CurrencyHelper::deToUs($match[4]);
                        $transactionCode = $match[5];
                        $reference = trim($match[6]);

                        $transactions[] = new Mt940Transaction(
                            date: $date,
                            valutaDate: $valutaDate,
                            amount: $amount,
                            creditDebit: $creditDebit,
                            currency: $openingBalance?->getCurrency() ?? CurrencyCode::Euro,
                            reference: new Mt940Reference($transactionCode, $reference),
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

    private static function parseBalance(string $raw): Mt940Balance {
        if (!preg_match('/^([CD])(\d{6})([A-Z]{3})([0-9,]+)$/', $raw, $matches)) {
            throw new RuntimeException("Balance-String ungültig: $raw");
        }

        return new Mt940Balance(
            creditDebit: CreditDebit::fromMt940Code($matches[1]),
            date: DateTimeImmutable::createFromFormat('ymd', $matches[2]) ?: throw new RuntimeException("Datum ungültig"),
            currency: CurrencyCode::tryFrom($matches[3]) ?? throw new RuntimeException("Währung ungültig: {$matches[3]}"),
            amount: (float) str_replace(',', '.', $matches[4])
        );
    }
}
