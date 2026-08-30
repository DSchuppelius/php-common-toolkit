<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : AmountTokenizer.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\ValueObjects\AmountToken;
use InvalidArgumentException;

/**
 * Findet Geldbeträge in Textzeilen – bankneutral, mit Zeichenposition.
 *
 * Formen: DE 1.234,56 · EN 1,234.56 · CH 1'234.56 · FR/CH 1 234,56; Vorzeichen
 * voran- oder nachgestellt (−/+), Soll/Haben (S/H), DR/CR; Währung davor oder
 * dahinter. Zahlen mit mehr oder weniger als zwei Nachkommastellen (Prozentsätze,
 * Kurse, Referenznummern) sind keine Beträge.
 */
final class AmountTokenizer {
    private const AMT_DE = '\d{1,3}(?:\.\d{3})*,\d{2}|\d+,\d{2}';
    private const AMT_EN = '\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2}';
    private const AMT_CH = '\d{1,3}(?:\'\d{3})+\.\d{2}|\d{1,3}(?: \d{3})+[.,]\d{2}';
    private const AMT = '(?:' . self::AMT_CH . '|' . self::AMT_DE . '|' . self::AMT_EN . ')';
    private static ?string $amountRe = null;

    /**
     * Alle Betrags-Token einer Zeile, von links nach rechts.
     *
     * @return list<AmountToken>
     */
    public static function tokens(string $line): array {
        if (preg_match_all(self::amountRe(), $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $tokens = [];
        foreach ($matches as $m) {
            $lead = $m[1][0];
            $currencyBefore = self::group($m, 2);
            $number = $m[3][0];
            $currencyAfter = self::group($m, 4);
            $trail = self::group($m, 5);

            $value = self::toFloat($number);
            // "Af"/"Bij" sind die niederländischen Soll-/Haben-Marker (ABN Amro, ING NL)
            $negative = in_array($lead, ['-', '−', '–'], true) || in_array($trail, ['-', '−', '–', 'S', 'DR', 'Af'], true);
            $hasSign = $lead !== '' || $trail !== '';

            $start = self::charPos($line, (int) $m[3][1]);
            $tokens[] = new AmountToken(
                $number,
                $negative ? -$value : $value,
                $hasSign,
                $start,
                $start + mb_strlen($number),
                self::normalizeCurrency($currencyBefore !== '' ? $currencyBefore : $currencyAfter),
            );
        }
        return $tokens;
    }

    public static function first(string $line): ?AmountToken {
        $tokens = self::tokens($line);
        return $tokens[0] ?? null;
    }

    public static function last(string $line): ?AmountToken {
        $tokens = self::tokens($line);
        return $tokens === [] ? null : $tokens[count($tokens) - 1];
    }

    /**
     * Ein-Wert-Komfort: der vorzeichenbehaftete Wert, wenn der Text genau einen
     * Betrag enthält; null sonst (kein Betrag oder mehrere).
     */
    public static function parse(string $text): ?float {
        $tokens = self::tokens($text);
        return count($tokens) === 1 ? $tokens[0]->value : null;
    }

    /**
     * Zahlenteil eines Betrags in einen Float: erkennt DE-, EN- und CH-/FR-Schreibweise.
     */
    public static function toFloat(string $number): float {
        $s = str_replace(["'", ' '], '', $number);
        if (preg_match('/^(?:\d{1,3}(?:\.\d{3})*,\d{2}|\d+,\d{2})$/', $s) === 1) {
            return (float) str_replace(',', '.', str_replace('.', '', $s));
        }
        if (preg_match('/^(?:\d{1,3}(?:,\d{3})*\.\d{2}|\d+\.\d{2})$/', $s) === 1) {
            return (float) str_replace(',', '', $s);
        }
        return (float) str_replace(',', '.', $s);
    }

    /**
     * @param array<int, array{0: string, 1: int}> $match
     */
    private static function group(array $match, int $index): string {
        return isset($match[$index]) && $match[$index][1] >= 0 ? $match[$index][0] : '';
    }

    private static function charPos(string $line, int $byteOffset): int {
        return mb_strlen(substr($line, 0, $byteOffset));
    }

    /** Muster mit allen erlaubten Währungen (Codes, Symbole, regionale Präfixe). */
    private static function amountRe(): string {
        if (self::$amountRe !== null) {
            return self::$amountRe;
        }
        $tokens = [];
        foreach (CurrencyCode::cases() as $currency) {
            // Eindeutige Präfixe ("AU$") sind immer sicher; Code und geteiltes Symbol nur für
            // die Währungen, die laut Enum am Betrag stehen
            foreach ($currency->getUniqueSymbols() as $symbol) {
                $tokens[] = $symbol;
            }
            if (!$currency->isStatementCurrency()) {
                continue;
            }
            $tokens[] = $currency->value;
            $shared = $currency->getSymbol();
            if ($shared !== '') {
                $tokens[] = $shared;
            }
        }
        $tokens = array_values(array_unique($tokens));
        usort($tokens, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $cur = '(?:' . implode('|', array_map(static fn (string $t): string => preg_quote($t, '/'), $tokens)) . ')';

        return self::$amountRe = '/(?<![\d.,\'])([-+−–]?)\s?(' . $cur . ')?\s?(' . self::AMT . ')\s?(' . $cur . ')?\s{0,16}([-+−–]|\b[SH]\b|\bDR\b|\bCR\b|\bAf\b|\bBij\b)?(?![\d.,])/u';
    }

    /** Symbol oder Code am Betrag → ISO-4217-Code ({@see CurrencyCode}); null, wenn keine Währung dastand. */
    private static function normalizeCurrency(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $code = CurrencyCode::tryFrom(strtoupper($raw));
        if ($code !== null) {
            return $code->value;
        }
        try {
            return CurrencyCode::fromSymbol($raw)->value;
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
