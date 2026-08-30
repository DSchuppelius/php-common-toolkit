<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateTokenizer.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\Month;
use CommonToolkit\ValueObjects\DateToken;

/**
 * Findet Datumsangaben in Textzeilen – mit Zeichenposition und ohne Jahrespflicht.
 *
 * Formen: ISO, TT-MM-JJJJ, TT/MM/JJJJ, TT.MM.JJJJ, TT.MM.JJ, "1. Juni 2023",
 * "3 Jan 24", TT.MM., TT/MM – deutsche und englische Monatsnamen.
 */
final class DateTokenizer {
    /** @var array<string, string> Form => Muster (Monatsnamen kommen aus {@see Month}) */
    private const FORMS = [
        'iso' => '\d{4}-\d{2}-\d{2}',
        'dmy-dash' => '\d{2}-\d{2}-\d{4}',
        'dmy-slash' => '\d{2}\/\d{2}\/\d{4}',
        'dmy' => '\d{1,2}\.\d{2}\.\d{4}',
        'dmy2' => '\d{2}\.\d{2}\.\d{2}(?!\d)',
        'd-month-y' => '\d{1,2}\.? ?%MONTH% \d{4}',
        'd-mon-y2' => '\d{1,2} %MONTH% \d{2}(?!\d)',
        'dm' => '\d{2}\.\d{2}\.(?![\d])',
        'dm-slash' => '\d{2}\/\d{2}(?![\d\/])',
        'd-month' => '\d{1,2}\.? ?%MONTH%\.?(?![\p{L}\d])',
    ];

    /** Sprachen, deren Monatsnamen in Kontoauszügen vorkommen. */
    private const MONTH_LOCALES = ['de', 'en'];

    /** @var array<string, string> */
    private static array $anchored = [];
    private static ?string $alternation = null;

    /**
     * Alle Datums-Token einer Zeile, nach Position sortiert.
     *
     * @return list<DateToken>
     */
    /**
     * Alle Datums-Token einer Zeile, nach Position sortiert.
     *
     * @param bool $monthFirst Schrägstrich-Daten als MM/DD(/YYYY) lesen (US-Auszüge)
     * @return list<DateToken>
     */
    public static function tokens(string $line, bool $monthFirst = false): array {
        if (preg_match_all('/(?<![\d.\/-])(' . self::alternation() . ')/u', $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $tokens = [];
        foreach ($matches as $m) {
            $raw = $m[1][0];
            $token = self::token($raw, mb_strlen(substr($line, 0, (int) $m[1][1])), $monthFirst);
            if ($token !== null) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    public static function first(string $line, bool $monthFirst = false): ?DateToken {
        $tokens = self::tokens($line, $monthFirst);
        return $tokens[0] ?? null;
    }

    /** Zeichenposition des ersten Nicht-Leerzeichens (Einrückung). */
    public static function indent(string $line): int {
        return mb_strlen($line) - mb_strlen(ltrim($line));
    }

    private static function token(string $raw, int $start, bool $monthFirst = false): ?DateToken {
        foreach (self::anchored() as $form => $pattern) {
            if (preg_match($pattern, $raw) !== 1) {
                continue;
            }
            [$day, $month, $year] = self::parts($raw, $form);
            if ($monthFirst && in_array($form, ['dmy-slash', 'dm-slash'], true)) {
                [$day, $month] = [$month, $day];
            }
            if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
                return null;
            }
            return new DateToken($raw, $form, $day, $month, $year, $start, $start + mb_strlen($raw));
        }
        return null;
    }

    /**
     * @return array{0: int, 1: int, 2: ?int}
     */
    private static function parts(string $raw, string $form): array {
        switch ($form) {
            case 'iso':
                [$y, $m, $d] = explode('-', $raw);
                return [(int) $d, (int) $m, (int) $y];
            case 'dmy-dash':
                [$d, $m, $y] = explode('-', $raw);
                return [(int) $d, (int) $m, (int) $y];
            case 'dmy-slash':
                [$d, $m, $y] = explode('/', $raw);
                return [(int) $d, (int) $m, (int) $y];
            case 'dmy':
            case 'dmy2':
                [$d, $m, $y] = explode('.', $raw);
                $year = (int) $y;
                return [(int) $d, (int) $m, strlen($y) === 2 ? 2000 + $year : $year];
            case 'dm':
                [$d, $m] = explode('.', $raw);
                return [(int) $d, (int) $m, null];
            case 'dm-slash':
                [$d, $m] = explode('/', $raw);
                return [(int) $d, (int) $m, null];
            case 'd-month':
                if (preg_match('/^(\d{1,2})\.? ?([^\d\s.]+)\.?$/u', $raw, $p) !== 1) {
                    return [0, 0, null];
                }
                $found = Month::fromName($p[2]);
                return [(int) $p[1], $found === null ? 0 : $found->value, null];
            default:
                // "8. Juni 2023", "3 Jan 24"
                if (preg_match('/^(\d{1,2})\.? ?([^\d\s.]+)\.? (\d{2,4})$/u', $raw, $p) !== 1) {
                    return [0, 0, null];
                }
                $found = Month::fromName($p[2]);
                $month = $found === null ? 0 : $found->value;
                $year = (int) $p[3];
                return [(int) $p[1], $month, strlen($p[3]) === 2 ? 2000 + $year : $year];
        }
    }

    /** @return array<string, string> */
    private static function anchored(): array {
        if (self::$anchored === []) {
            foreach (self::forms() as $form => $pattern) {
                self::$anchored[$form] = '/^' . $pattern . '$/u';
            }
        }
        return self::$anchored;
    }

    private static function alternation(): string {
        return self::$alternation ??= implode('|', array_values(self::forms()));
    }

    /** @var array<string, string>|null */
    private static ?array $forms = null;

    /**
     * Formen mit eingesetzter Monats-Alternation: volle und kurze Namen aus
     * {@see Month} (deutsch/englisch, März auch als "Maerz"), längste zuerst.
     *
     * @return array<string, string>
     */
    private static function forms(): array {
        if (self::$forms !== null) {
            return self::$forms;
        }
        $names = ['Maerz', 'Sept'];
        foreach (self::MONTH_LOCALES as $locale) {
            foreach (Month::cases() as $month) {
                $names[] = $month->getName($locale);
                $names[] = $month->getShortName($locale);
            }
        }
        $names = array_values(array_unique($names));
        usort($names, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $alternation = '(?:' . implode('|', array_map(static fn (string $n): string => preg_quote($n, '/'), $names)) . ')\.?';
        self::$forms = [];
        foreach (self::FORMS as $form => $pattern) {
            self::$forms[$form] = str_replace('%MONTH%', $alternation, $pattern);
        }
        return self::$forms;
    }
}
