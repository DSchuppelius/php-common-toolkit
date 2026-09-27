<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurrenceRule.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\ICalendar;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * RRULE (RFC 5545 §3.3.10) for the frequencies DAILY, WEEKLY, MONTHLY and
 * YEARLY with INTERVAL, COUNT, UNTIL, BYDAY (with ordinals), BYMONTHDAY,
 * BYMONTH, BYSETPOS and WKST. Occurrences keep the wall-clock time of DTSTART
 * across daylight-saving changes. Sub-daily frequencies are not supported.
 */
final class RecurrenceRule {
    private const WEEKDAYS = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7];

    /**
     * @param list<array{0: int, 1: int}> $byDay       [ordinal (0 = every), ISO weekday]
     * @param list<int>                   $byMonthDay
     * @param list<int>                   $byMonth
     * @param list<int>                   $bySetPos
     */
    private function __construct(
        private readonly string $frequency,
        private readonly int $interval,
        private readonly ?int $count,
        private readonly ?DateTimeImmutable $until,
        private readonly array $byDay,
        private readonly array $byMonthDay,
        private readonly array $byMonth,
        private readonly array $bySetPos,
        private readonly int $weekStart,
        private readonly string $raw,
    ) {}

    /** @throws InvalidArgumentException for unsupported frequencies */
    public static function fromString(string $rule): self {
        $parts = [];
        foreach (explode(';', trim(preg_replace('/^RRULE:/i', '', trim($rule)) ?? '')) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if ($key !== '') {
                $parts[strtoupper(trim($key))] = strtoupper(trim($value));
            }
        }
        $frequency = $parts['FREQ'] ?? '';
        if (!in_array($frequency, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
            throw new InvalidArgumentException("Unsupported recurrence frequency: '{$frequency}'");
        }
        $byDay = [];
        foreach (self::list($parts['BYDAY'] ?? '') as $day) {
            if (preg_match('/^([+-]?\d{1,2})?(MO|TU|WE|TH|FR|SA|SU)$/', $day, $m) === 1) {
                $byDay[] = [(int) $m[1], self::WEEKDAYS[$m[2]]];
            }
        }
        $until = null;
        if (isset($parts['UNTIL']) && preg_match('/^(\d{4})(\d{2})(\d{2})(?:T(\d{2})(\d{2})(\d{2})(Z)?)?$/', $parts['UNTIL'], $m) === 1) {
            $time = ($m[4] ?? '') !== '' ? sprintf('%s:%s:%s', $m[4], $m[5] ?? '00', $m[6] ?? '00') : '23:59:59';
            $until = new DateTimeImmutable("{$m[1]}-{$m[2]}-{$m[3]} {$time}", new DateTimeZone('UTC'));
        }

        return new self(
            frequency: $frequency,
            interval: max(1, (int) ($parts['INTERVAL'] ?? 1)),
            count: isset($parts['COUNT']) ? max(0, (int) $parts['COUNT']) : null,
            until: $until,
            byDay: $byDay,
            byMonthDay: array_map('intval', self::list($parts['BYMONTHDAY'] ?? '')),
            byMonth: array_map('intval', self::list($parts['BYMONTH'] ?? '')),
            bySetPos: array_map('intval', self::list($parts['BYSETPOS'] ?? '')),
            weekStart: self::WEEKDAYS[$parts['WKST'] ?? 'MO'] ?? 1,
            raw: trim($rule),
        );
    }

    public function getFrequency(): string {
        return $this->frequency;
    }

    public function getCount(): ?int {
        return $this->count;
    }

    public function getUntil(): ?DateTimeImmutable {
        return $this->until;
    }

    public function __toString(): string {
        return $this->raw;
    }

    /**
     * Occurrence starts from DTSTART (inclusive, counted even if it does not
     * match the rule) up to and including $until of the window.
     *
     * @return list<DateTimeImmutable>
     */
    public function occurrences(DateTimeImmutable $start, DateTimeImmutable $windowEnd, int $max = 1000): array {
        $out = [$start];
        $generated = 1;
        $periodStart = $this->periodStart($start);
        for ($period = 0; $period < 10000 && count($out) < $max; $period++) {
            $candidates = $this->candidates($this->advance($periodStart, $period), $start);
            foreach ($candidates as $candidate) {
                if ($candidate <= $start) {
                    continue;
                }
                if (($this->until !== null && $candidate > $this->until) || $candidate > $windowEnd
                    || ($this->count !== null && $generated >= $this->count)) {
                    return $out;
                }
                $out[] = $candidate;
                $generated++;
                if (count($out) >= $max) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /** First day of the period that contains DTSTART. */
    private function periodStart(DateTimeImmutable $start): DateTimeImmutable {
        $day = $start->setTime(0, 0);

        return match ($this->frequency) {
            'DAILY' => $day,
            'WEEKLY' => $day->modify('-' . (((int) $day->format('N') - $this->weekStart + 7) % 7) . ' days'),
            'MONTHLY' => $day->modify('first day of this month'),
            default => $day->setDate((int) $day->format('Y'), 1, 1),
        };
    }

    private function advance(DateTimeImmutable $periodStart, int $period): DateTimeImmutable {
        $steps = $period * $this->interval;

        return match ($this->frequency) {
            'DAILY' => $periodStart->modify("+{$steps} days"),
            'WEEKLY' => $periodStart->modify('+' . ($steps * 7) . ' days'),
            'MONTHLY' => $periodStart->modify("+{$steps} months"),
            default => $periodStart->modify("+{$steps} years"),
        };
    }

    /** @return list<DateTimeImmutable> sorted candidates of one period with the DTSTART time */
    private function candidates(DateTimeImmutable $period, DateTimeImmutable $start): array {
        $days = match ($this->frequency) {
            'DAILY' => [$period],
            'WEEKLY' => $this->weekDays($period, $start),
            'MONTHLY' => $this->monthDays($period, $start),
            default => $this->yearDays($period, $start),
        };
        $days = array_values(array_filter($days, fn (DateTimeImmutable $d): bool => $this->matchesFilters($d)));
        usort($days, static fn (DateTimeImmutable $a, DateTimeImmutable $b): int => $a <=> $b);
        $days = $this->applySetPos($days);
        $time = [(int) $start->format('H'), (int) $start->format('i'), (int) $start->format('s')];

        return array_map(static fn (DateTimeImmutable $d): DateTimeImmutable => $d->setTime(...$time), $days);
    }

    /** @return list<DateTimeImmutable> */
    private function weekDays(DateTimeImmutable $weekStart, DateTimeImmutable $start): array {
        $weekdays = $this->byDay === [] ? [(int) $start->format('N')] : array_map(static fn (array $d): int => $d[1], $this->byDay);
        $days = [];
        foreach ($weekdays as $weekday) {
            $days[] = $weekStart->modify('+' . (($weekday - $this->weekStart + 7) % 7) . ' days');
        }

        return $days;
    }

    /** @return list<DateTimeImmutable> */
    private function monthDays(DateTimeImmutable $month, DateTimeImmutable $start): array {
        $length = (int) $month->format('t');
        if ($this->byMonthDay !== []) {
            $days = [];
            foreach ($this->byMonthDay as $day) {
                $day = $day < 0 ? $length + $day + 1 : $day;
                if ($day >= 1 && $day <= $length) {
                    $days[] = $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day);
                }
            }

            return $days;
        }
        if ($this->byDay !== []) {
            return $this->weekdaysInRange($month, $month->modify('last day of this month'));
        }
        $day = (int) $start->format('j');

        return $day <= $length ? [$month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day)] : [];
    }

    /** @return list<DateTimeImmutable> */
    private function yearDays(DateTimeImmutable $year, DateTimeImmutable $start): array {
        $months = $this->byMonth !== [] ? $this->byMonth : [(int) $start->format('n')];
        $days = [];
        foreach ($months as $month) {
            $first = $year->setDate((int) $year->format('Y'), $month, 1);
            array_push($days, ...$this->monthDays($first, $start));
        }

        return $days;
    }

    /**
     * Weekdays of BYDAY between two dates; an ordinal selects the n-th (or
     * n-th last) match within the range.
     *
     * @return list<DateTimeImmutable>
     */
    private function weekdaysInRange(DateTimeImmutable $from, DateTimeImmutable $to): array {
        $days = [];
        foreach ($this->byDay as [$ordinal, $weekday]) {
            $matches = [];
            for ($day = $from->modify('+' . (($weekday - (int) $from->format('N') + 7) % 7) . ' days'); $day <= $to; $day = $day->modify('+7 days')) {
                $matches[] = $day;
            }
            if ($ordinal === 0) {
                array_push($days, ...$matches);
            } else {
                $index = $ordinal > 0 ? $ordinal - 1 : count($matches) + $ordinal;
                if (isset($matches[$index])) {
                    $days[] = $matches[$index];
                }
            }
        }

        return $days;
    }

    private function matchesFilters(DateTimeImmutable $day): bool {
        if ($this->byMonth !== [] && !in_array((int) $day->format('n'), $this->byMonth, true)) {
            return false;
        }
        if ($this->frequency === 'DAILY' && $this->byDay !== [] && !in_array((int) $day->format('N'), array_map(static fn (array $d): int => $d[1], $this->byDay), true)) {
            return false;
        }
        if ($this->frequency === 'DAILY' && $this->byMonthDay !== []) {
            $length = (int) $day->format('t');
            $monthDays = array_map(static fn (int $d): int => $d < 0 ? $length + $d + 1 : $d, $this->byMonthDay);
            if (!in_array((int) $day->format('j'), $monthDays, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<DateTimeImmutable> $days
     * @return list<DateTimeImmutable>
     */
    private function applySetPos(array $days): array {
        if ($this->bySetPos === [] || $days === []) {
            return $days;
        }
        $selected = [];
        foreach ($this->bySetPos as $position) {
            $index = $position > 0 ? $position - 1 : count($days) + $position;
            if (isset($days[$index])) {
                $selected[] = $days[$index];
            }
        }
        usort($selected, static fn (DateTimeImmutable $a, DateTimeImmutable $b): int => $a <=> $b);

        return array_values(array_unique($selected, SORT_REGULAR));
    }

    /** @return list<string> */
    private static function list(string $value): array {
        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $v): bool => $v !== ''));
    }
}
