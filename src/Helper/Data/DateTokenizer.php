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
        'month-d-y' => '%MONTH% \d{1,2}, \d{2,4}',
        'd-mon-y2' => '\d{1,2} %MONTH% \d{2}(?!\d)',
        'dm' => '\d{2}\.\d{2}\.(?![\d])',
        'dm-slash' => '\d{2}\/\d{2}(?![\d\/])',
        'dm-dash' => '\d{2}-\d{2}(?![\d-])',
        'd-month' => '\d{1,2}\.? ?%MONTH%\.?(?![\p{L}\d])',
    ];

    /** Sprachen, deren Monatsnamen in Kontoauszügen vorkommen. */
    private const MONTH_LOCALES = ['de', 'en', 'nl'];

    /** @var array<string, string> */
    /** @var array<string, array<string, string>> Verankerte Muster je Variante (default/nodot) */
    private static array $anchored = [];
    /** @var array<string, string> Alternation je Variante */
    private static array $alternation = [];

    /**
     * Alle Datums-Token einer Zeile, nach Position sortiert.
     *
     * @return list<DateToken>
     */
    /**
     * Alle Datums-Token einer Zeile, nach Position sortiert.
     *
     * @param bool $monthFirst Schrägstrich-Daten als MM/DD(/YYYY) lesen (US-Auszüge)
     * @param bool $monthFirstDotted Punktierte Daten als MM.DD.YYYY lesen (db-direct, US-Reports).
     *                               Bewusst getrennt von $monthFirst: eine Schrägstrich-Referenz im
     *                               Text darf die Deutung punktierter Daten nicht kippen.
     * @return list<DateToken>
     */
    public static function tokens(string $line, bool $monthFirst = false, bool $dayMonthWithoutDot = false, bool $monthFirstDotted = false): array {
        // Monatsnamen ohne Rücksicht auf Groß-/Kleinschreibung ("16 SEP", "16 Sep", J.P.-Morgan-Auszüge)
        if (preg_match_all('/(?<![\d.\/-])(' . self::alternation($dayMonthWithoutDot) . ')/iu', $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $tokens = [];
        foreach ($matches as $m) {
            $raw = $m[1][0];
            $token = self::token($raw, mb_strlen(substr($line, 0, (int) $m[1][1])), $monthFirst, $dayMonthWithoutDot, $monthFirstDotted);
            if ($token !== null) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    public static function first(string $line, bool $monthFirst = false, bool $dayMonthWithoutDot = false, bool $monthFirstDotted = false): ?DateToken {
        $tokens = self::tokens($line, $monthFirst, $dayMonthWithoutDot, $monthFirstDotted);
        return $tokens[0] ?? null;
    }

    /** Zeichenposition des ersten Nicht-Leerzeichens (Einrückung). */
    public static function indent(string $line): int {
        return mb_strlen($line) - mb_strlen(ltrim($line));
    }

    private static function token(string $raw, int $start, bool $monthFirst = false, bool $dayMonthWithoutDot = false, bool $monthFirstDotted = false): ?DateToken {
        foreach (self::anchored($dayMonthWithoutDot) as $form => $pattern) {
            if (preg_match($pattern, $raw) !== 1) {
                continue;
            }
            [$day, $month, $year] = self::parts($raw, $form);
            // Der monthFirst-Tausch gilt nur für Schrägstrich-Formen: punktierte Daten sind in
            // deutschen Auszügen immer TT.MM., und eine einzelne "12/27"-Referenz im Text darf
            // sie nicht kippen. Eindeutige US-Daten fängt der Zweig darunter ab.
            if ($monthFirst && in_array($form, ['dmy-slash', 'dm-slash'], true)) {
                [$day, $month] = [$month, $day];
            } elseif ($monthFirstDotted && in_array($form, ['dmy', 'dmy2'], true)) {
                // Dokument ist als MM.DD.JJJJ belegt (db-direct: "08.13.2025" neben "08.04.2025")
                [$day, $month] = [$month, $day];
            } elseif ($month > 12 && $day <= 12 && in_array($form, ['dmy-slash', 'dmy', 'dmy2'], true)) {
                // Eindeutig US-Form mit Jahr ("08.13.2025", "05/13/2025"): der zweite Teil kann kein
                // Monat sein. Ohne Jahr ("05/13") bleibt es ungelesen – dort ist Monat/Jahr gemeint.
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
            case 'dm-nodot':
                [$d, $m] = explode('.', $raw);
                return [(int) $d, (int) $m, null];
            case 'dm-slash':
                [$d, $m] = explode('/', $raw);
                return [(int) $d, (int) $m, null];
            case 'dm-dash':
                [$d, $m] = explode('-', $raw);
                return [(int) $d, (int) $m, null];
            case 'd-month':
                if (preg_match('/^(\d{1,2})\.? ?([^\d\s.]+)\.?$/u', $raw, $p) !== 1) {
                    return [0, 0, null];
                }
                $found = Month::fromName($p[2]);
                return [(int) $p[1], $found === null ? 0 : $found->value, null];
            case 'month-d-y':
                // "Apr 16, 2025"
                if (preg_match('/^([^\d\s.]+)\.? (\d{1,2}), (\d{2,4})$/u', $raw, $p) !== 1) {
                    return [0, 0, null];
                }
                $found = Month::fromName($p[1]);
                $year = (int) $p[3];
                return [(int) $p[2], $found === null ? 0 : $found->value, strlen($p[3]) === 2 ? 2000 + $year : $year];
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
    private static function anchored(bool $dayMonthWithoutDot = false): array {
        $key = $dayMonthWithoutDot ? 'nodot' : 'default';
        if (!isset(self::$anchored[$key])) {
            self::$anchored[$key] = [];
            foreach (self::forms($dayMonthWithoutDot) as $form => $pattern) {
                self::$anchored[$key][$form] = '/^' . $pattern . '$/iu';
            }
        }

        return self::$anchored[$key];
    }

    private static function alternation(bool $dayMonthWithoutDot = false): string {
        $key = $dayMonthWithoutDot ? 'nodot' : 'default';

        return self::$alternation[$key] ??= implode('|', array_values(self::forms($dayMonthWithoutDot)));
    }

    /** @var array<string, string>|null */
    /** @var array<string, array<string, string>> Formen je Variante */
    private static array $forms = [];

    /**
     * Formen mit eingesetzter Monats-Alternation: volle und kurze Namen aus
     * {@see Month} (deutsch/englisch, März auch als "Maerz"), längste zuerst.
     *
     * @return array<string, string>
     */
    private static function forms(bool $dayMonthWithoutDot = false): array {
        $key = $dayMonthWithoutDot ? 'nodot' : 'default';
        if (isset(self::$forms[$key])) {
            return self::$forms[$key];
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
        $forms = self::FORMS;
        if ($dayMonthWithoutDot) {
            // "02.11   DO   RESERV. BETRAG GAA" – Tag.Monat ohne abschließenden Punkt. Nur auf
            // Anforderung, weil die Form mit englischen Beträgen kollidiert ("16.03" = 16,03).
            $forms['dm-nodot'] = '\d{2}\.\d{2}(?![\d.,])';
        }
        self::$forms[$key] = [];
        foreach ($forms as $form => $pattern) {
            self::$forms[$key][$form] = str_replace('%MONTH%', $alternation, $pattern);
        }

        return self::$forms[$key];
    }
}
