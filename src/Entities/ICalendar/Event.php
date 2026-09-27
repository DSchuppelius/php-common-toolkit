<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Event.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\ICalendar;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use IntlTimeZone;
use Throwable;

/**
 * VEVENT of an iCalendar document with typed access to the common
 * properties. Date values honour TZID (IANA and Windows names), UTC ("Z") and
 * floating times; floating and unresolvable times use the given default zone.
 */
final class Event {
    /** @param list<Property> $properties */
    public function __construct(private readonly array $properties) {}

    /** @return list<Property> */
    public function getProperties(string $name): array {
        $name = strtoupper($name);

        return array_values(array_filter($this->properties, static fn (Property $p): bool => $p->getName() === $name));
    }

    public function getProperty(string $name): ?Property {
        return $this->getProperties($name)[0] ?? null;
    }

    public function has(string $name): bool {
        return $this->getProperty($name) !== null;
    }

    public function getText(string $name): ?string {
        $property = $this->getProperty($name);

        return $property === null ? null : trim($property->getText());
    }

    public function getUid(): string {
        return $this->getText('UID') ?? '';
    }

    public function getSummary(): string {
        return $this->getText('SUMMARY') ?? '';
    }

    public function getDescription(): string {
        return $this->getText('DESCRIPTION') ?? '';
    }

    public function getLocation(): ?string {
        $location = $this->getText('LOCATION');

        return $location === '' ? null : $location;
    }

    /** DTSTART without a time part (VALUE=DATE). */
    public function isAllDay(): bool {
        $start = $this->getProperty('DTSTART');

        return $start !== null && self::isDateValue($start);
    }

    public function getStart(DateTimeZone $default): ?DateTimeImmutable {
        return self::dateOf($this->getProperty('DTSTART'), $default);
    }

    /** DTEND, otherwise DTSTART + DURATION; null without either. */
    public function getEnd(DateTimeZone $default): ?DateTimeImmutable {
        $end = self::dateOf($this->getProperty('DTEND'), $default);
        if ($end !== null) {
            return $end;
        }
        $start = $this->getStart($default);
        $duration = $this->getDuration();

        return $start !== null && $duration !== null ? $start->add($duration) : null;
    }

    /** DTEND carries a time part (not VALUE=DATE). */
    public function endHasTime(): bool {
        $end = $this->getProperty('DTEND');

        return $end !== null && !self::isDateValue($end);
    }

    public function getDuration(): ?DateInterval {
        $value = $this->getText('DURATION');
        if ($value === null || $value === '') {
            return null;
        }
        $negative = str_starts_with($value, '-');
        try {
            $interval = new DateInterval(ltrim($value, '+-'));
        } catch (Throwable) {
            return null;
        }
        $interval->invert = $negative ? 1 : 0;

        return $interval;
    }

    public function getRecurrenceRule(): ?RecurrenceRule {
        $value = $this->getProperty('RRULE')?->getValue();

        return $value === null || trim($value) === '' ? null : RecurrenceRule::fromString($value);
    }

    public function getRecurrenceId(DateTimeZone $default): ?DateTimeImmutable {
        return self::dateOf($this->getProperty('RECURRENCE-ID'), $default);
    }

    /** @return list<DateTimeImmutable> */
    public function getExceptionDates(DateTimeZone $default): array {
        $dates = [];
        foreach ($this->getProperties('EXDATE') as $property) {
            foreach ($property->getParts() as $part) {
                $date = self::parseDate($part, $property, $default);
                if ($date !== null) {
                    $dates[] = $date;
                }
            }
        }

        return $dates;
    }

    /** @return list<string> */
    public function getCategories(): array {
        $categories = [];
        foreach ($this->getProperties('CATEGORIES') as $property) {
            array_push($categories, ...$property->getParts());
        }

        return array_values(array_unique($categories));
    }

    /** Organizer address without "mailto:". */
    public function getOrganizer(): ?string {
        $value = $this->getProperty('ORGANIZER')?->getValue();

        return $value === null ? null : self::stripMailto($value);
    }

    /** @return list<string> attendee addresses without "mailto:" */
    public function getAttendees(): array {
        return array_map(static fn (Property $p): string => self::stripMailto($p->getValue()), $this->getProperties('ATTENDEE'));
    }

    public function isTransparent(): bool {
        return strtoupper($this->getText('TRANSP') ?? '') === 'TRANSPARENT';
    }

    public function getLastModified(): ?DateTimeImmutable {
        return self::dateOf($this->getProperty('LAST-MODIFIED') ?? $this->getProperty('DTSTAMP'), new DateTimeZone('UTC'));
    }

    private static function stripMailto(string $value): string {
        $value = trim($value);

        return stripos($value, 'mailto:') === 0 ? trim(substr($value, 7)) : $value;
    }

    private static function isDateValue(Property $property): bool {
        return strtoupper((string) $property->getParameter('VALUE')) === 'DATE' || preg_match('/^\d{8}$/', trim($property->getValue())) === 1;
    }

    private static function dateOf(?Property $property, DateTimeZone $default): ?DateTimeImmutable {
        return $property === null ? null : self::parseDate(trim($property->getValue()), $property, $default);
    }

    private static function parseDate(string $value, Property $property, DateTimeZone $default): ?DateTimeImmutable {
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(?:T(\d{2})(\d{2})(\d{2})(Z)?)?$/', trim($value), $m) !== 1) {
            return null;
        }
        $zone = ($m[7] ?? '') === 'Z' ? new DateTimeZone('UTC') : (self::zoneOf($property->getParameter('TZID')) ?? $default);
        $time = ($m[4] ?? '') !== '' ? sprintf('%s:%s:%s', $m[4], $m[5] ?? '00', $m[6] ?? '00') : '00:00:00';
        try {
            return new DateTimeImmutable("{$m[1]}-{$m[2]}-{$m[3]} {$time}", $zone);
        } catch (Throwable) {
            return null;
        }
    }

    /** IANA name, a path-like variant ("/mozilla.org/.../Europe/Berlin") or a Windows name ("W. Europe Standard Time"). */
    public static function zoneOf(?string $tzid): ?DateTimeZone {
        $tzid = trim((string) $tzid, " \"");
        if ($tzid === '') {
            return null;
        }
        $candidates = [$tzid, ltrim($tzid, '/')];
        $segments = explode('/', trim($tzid, '/'));
        if (count($segments) >= 2) {
            $candidates[] = implode('/', array_slice($segments, -2));
        }
        if (class_exists(IntlTimeZone::class)) {
            $windows = IntlTimeZone::getIDForWindowsID($tzid);
            if ($windows) {
                $candidates[] = $windows;
            }
        }
        foreach ($candidates as $candidate) {
            try {
                return new DateTimeZone($candidate);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
