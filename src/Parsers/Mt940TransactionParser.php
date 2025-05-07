<?php

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Banking\Mt940Transaction;
use CommonToolkit\Helper\Data\CurrencyHelper;
use RuntimeException;

class Mt940TransactionParser {
    public static function parse(string $line, ?string $purpose = null): Mt940Transaction {
        if (!preg_match('/(\d{6})\d{4}([CD])(\d+,\d+)([A-Z]{4})(.+)/', substr($line, 4), $match)) {
            throw new RuntimeException("Ungültige MT940-Zeile: $line");
        }

        return new Mt940Transaction(
            date: $match[1],
            amount: (float) CurrencyHelper::deToUs($match[3]),
            debitCredit: $match[2],
            currency: 'EUR',
            transactionCode: $match[4],
            reference: trim($match[5]),
            purpose: $purpose
        );
    }
}
