<?php
/*
 * Created on   : Thu May 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Banking\Mt940;

use CommonToolkit\Enums\CreditDebit;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Helper\Data\CurrencyHelper;
use DateTimeImmutable;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class Mt940Document {
    use ErrorLog;
    private string $rawBlock;

    /** @var Mt940Transaction[] */
    private array $transactions = [];

    private ?string $accountId = null;
    private ?Mt940Balance $openingBalance = null;
    private ?Mt940Balance $closingBalance = null;
    private CurrencyCode $currency = CurrencyCode::Euro;

    public function __construct(string $rawBlock) {
        $this->rawBlock = trim($rawBlock);
        $this->parseMeta();
        $this->parseTransactions();
    }

    public static function fromTransactions(array $transactions, string $accountId, Mt940Balance $openingBalance, Mt940Balance $closingBalance, string $referenceID = "COMMON", string $statementNumber = "00000"): self {
        $lines = [
            ':20:' . $referenceID,
            ':25:' . $accountId,
            ':28C:' . $statementNumber,
            ':60F:' . $openingBalance->toMt940(),
        ];

        foreach ($transactions as $txn) {
            $lines = array_merge($lines, $txn->toMt940Lines());
        }

        $lines[] = ':62F:' . $closingBalance->toMt940();
        $lines[] = '-';

        return new self(implode("\n", $lines));
    }

    public static function fromTransactionsSmart(array $transactions, string $accountId, Mt940Balance|string|null $openingBalance = null, Mt940Balance|string|null $closingBalance = null, string $referenceID = "COMMON", string $statementNumber = "00000"): self {
        if (!$openingBalance && !$closingBalance) {
            throw new InvalidArgumentException("Entweder openingBalance oder closingBalance muss angegeben werden.");
        }

        if (is_string($openingBalance)) {
            $openingBalance = Mt940Balance::parse($openingBalance);
        }
        if (is_string($closingBalance)) {
            $closingBalance = Mt940Balance::parse($closingBalance);
        }

        $doc = new self(':20:dummy' . "\n" . ':25:' . $accountId);
        foreach ($transactions as $txn) {
            $doc->addTransaction($txn);
        }

        if ($openingBalance === null) {
            $openingBalance = $doc->reverseCalculateBalance($closingBalance);
        } elseif ($closingBalance === null) {
            $closingBalance = $doc->calculateClosingBalance($openingBalance);
        } else {
            $expected = $doc->calculateClosingBalance($openingBalance);
            if ($expected->toMt940() !== $closingBalance->toMt940()) {
                throw new RuntimeException("Übergebene opening/closing balances stimmen nicht. Erwartet: " . $expected->toMt940());
            }
        }

        return self::fromTransactions($transactions, $accountId, $openingBalance, $closingBalance, $referenceID, $statementNumber);
    }

    public function addTransaction(Mt940Transaction $transaction): void {
        $this->transactions[] = $transaction;
    }

    public function getTransactions(): array {
        return $this->transactions;
    }

    public function getAccountId(): ?string {
        return $this->accountId;
    }

    public function getOpeningBalance(): ?Mt940Balance {
        return $this->openingBalance;
    }

    public function getClosingBalance(): ?Mt940Balance {
        return $this->closingBalance;
    }

    public function getCurrency(): CurrencyCode {
        return $this->currency;
    }

    public function setCurrency(CurrencyCode $currency): void {
        $this->currency = $currency;
    }

    public function getRaw(): string {
        return $this->rawBlock;
    }

    public function toLines(): array {
        return preg_split('/\r\n|\n|\r/', $this->rawBlock);
    }

    private function parseMeta(): void {
        foreach ($this->toLines() as $line) {
            if (str_starts_with($line, ':25:')) {
                $this->accountId = trim(substr($line, 4));
            } elseif (str_starts_with($line, ':60F:')) {
                $this->openingBalance = Mt940Balance::parse(trim(substr($line, 5)));
                $this->currency = $this->openingBalance->getCurrency();
            } elseif (str_starts_with($line, ':62F:')) {
                $this->closingBalance = Mt940Balance::parse(trim(substr($line, 5)));
            }
        }
    }

    private function parseTransactions(): void {
        $lines = $this->toLines();
        $i = 0;
        $lineCount = count($lines);

        while ($i < $lineCount) {
            $line = $lines[$i];

            if (str_starts_with($line, ':61:')) {
                $bookingLine = $line;
                $i++;

                $purposeLines = [];
                if (isset($lines[$i]) && str_starts_with($lines[$i], ':86:')) {
                    $purposeLines[] = trim(substr($lines[$i], 4));
                    $i++;

                    while ($i < $lineCount && str_starts_with($lines[$i], '?')) {
                        $purposeLines[] = trim(substr($lines[$i], 3));
                        $i++;
                    }
                }

                $purpose = implode(' ', $purposeLines);

                try {
                    if (preg_match('/^:61:(\d{6})(\d{4})?([CD])(\d+,\d+)([A-Z]{3,4})(.*)$/', $bookingLine, $match)) {
                        $date = DateTimeImmutable::createFromFormat('ymd', $match[1]) ?: throw new \RuntimeException("Ungültiges Buchungsdatum");
                        $valutaDate = isset($match[2])
                            ? DateTimeImmutable::createFromFormat('Ymd', $date->format('Y') . $match[2])
                            : null;

                        $creditDebit = CreditDebit::fromMt940Code($match[3]);
                        $amount = (float) CurrencyHelper::deToUs($match[4]);
                        $transactionCode = $match[5];
                        $reference = trim($match[6]);

                        $this->transactions[] = new Mt940Transaction(
                            date: $date,
                            valutaDate: $valutaDate,
                            amount: $amount,
                            creditDebit: $creditDebit,
                            currency: $this->currency,
                            transactionCode: $transactionCode,
                            reference: $reference,
                            purpose: $purpose
                        );
                    } else {
                        self::logWarning("Buchungszeile konnte nicht geparst werden: $bookingLine");
                    }
                } catch (Throwable $e) {
                    self::logError("Fehler beim Parsen einer Transaktion: {$e->getMessage()}");
                }

                continue;
            }

            $i++;
        }
    }


    private function calculateBalance(Mt940Balance $base, int $direction): Mt940Balance {
        $total = $base->isDebit() ? -$base->getAmount() : $base->getAmount();

        foreach ($this->transactions as $txn) {
            $sign = $txn->getCreditDebit() === CreditDebit::CREDIT ? 1 : -1;
            $total += $direction * $sign * $txn->getAmount();
        }

        $direction = $total >= 0 ? CreditDebit::CREDIT : CreditDebit::DEBIT;
        return new Mt940Balance($direction, $base->getDate(), $base->getCurrency(), abs($total));
    }

    private function calculateClosingBalance(Mt940Balance $opening): Mt940Balance {
        return $this->calculateBalance($opening, +1);
    }

    private function reverseCalculateBalance(Mt940Balance $closing): Mt940Balance {
        return $this->calculateBalance($closing, -1);
    }
}
