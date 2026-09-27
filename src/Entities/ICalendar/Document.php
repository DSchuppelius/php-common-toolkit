<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\ICalendar;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Parsed iCalendar document: its VEVENTs, grouped into series by UID, and the
 * expansion of a series within a window.
 */
final class Document {
    /** @param list<Event> $events */
    public function __construct(private readonly array $events) {}

    /** @return list<Event> */
    public function getEvents(): array {
        return $this->events;
    }

    /**
     * Events grouped by UID (a series and its overrides); events without UID
     * form groups of their own.
     *
     * @return list<list<Event>>
     */
    public function getSeries(): array {
        $groups = [];
        foreach ($this->events as $index => $event) {
            $uid = $event->getUid();
            $groups[$uid !== '' ? 'u:' . $uid : 'i:' . $index][] = $event;
        }

        return array_values($groups);
    }

    /**
     * Instances of a series that overlap the window: ending after $from (or,
     * without end, starting after it) and starting before $until. EXDATEs are
     * removed, overrides (RECURRENCE-ID) replace their instance; a group
     * without RRULE yields its events as they are.
     *
     * @param list<Event> $group
     * @return list<Occurrence>
     */
    public static function expand(array $group, DateTimeImmutable $from, DateTimeImmutable $until, DateTimeZone $zone, int $max = 1000): array {
        $master = null;
        $overrides = [];
        foreach ($group as $event) {
            $recurrenceId = $event->getRecurrenceId($zone);
            if ($recurrenceId !== null) {
                $overrides[$recurrenceId->getTimestamp()] = $event;
            } elseif ($event->has('RRULE')) {
                $master = $event;
            }
        }

        $out = [];
        if ($master === null) {
            foreach ($group as $event) {
                $start = $event->getStart($zone);
                $end = $event->getEnd($zone);
                if ($start !== null && self::overlaps($start, $end, $from, $until)) {
                    $out[] = new Occurrence($event, $start, $end, $event->getRecurrenceId($zone) ?? $start);
                }
            }

            return $out;
        }

        $start = $master->getStart($zone);
        if ($start === null) {
            return [];
        }
        try {
            $rule = $master->getRecurrenceRule();
        } catch (Throwable) {
            $rule = null;
        }
        $masterEnd = $master->getEnd($zone);
        $length = $masterEnd !== null ? $masterEnd->getTimestamp() - $start->getTimestamp() : null;
        $excluded = array_map(static fn (DateTimeImmutable $d): int => $d->getTimestamp(), $master->getExceptionDates($zone));

        $starts = $rule === null ? [$start] : $rule->occurrences($start, $until, 100000);
        foreach ($starts as $instance) {
            if (count($out) >= $max) {
                break;
            }
            $key = $instance->getTimestamp();
            if (in_array($key, $excluded, true)) {
                continue;
            }
            $event = $overrides[$key] ?? $master;
            $instanceStart = isset($overrides[$key]) ? ($event->getStart($zone) ?? $instance) : $instance;
            $instanceEnd = isset($overrides[$key]) ? $event->getEnd($zone) : ($length !== null ? $instance->setTimestamp($instance->getTimestamp() + $length) : null);
            if (!self::overlaps($instanceStart, $instanceEnd, $from, $until)) {
                continue;
            }
            $out[] = new Occurrence($event, $instanceStart, $instanceEnd, $instance);
        }

        return $out;
    }

    private static function overlaps(DateTimeImmutable $start, ?DateTimeImmutable $end, DateTimeImmutable $from, DateTimeImmutable $until): bool {
        return ($end ?? $start) > $from && $start < $until;
    }
}
