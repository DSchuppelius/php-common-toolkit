<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ICalendarParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\ICalendar\{Document, Event, Property};
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

/**
 * Parser for iCalendar data (RFC 5545), forgiving like typical calendar
 * exports: folded lines are unfolded, malformed content lines are skipped,
 * only VEVENTs are collected (nested components such as VALARM are ignored).
 */
final class ICalendarParser {
    use ErrorLog;

    /** @throws RuntimeException if the data contains no VCALENDAR */
    public static function fromString(string $data): Document {
        $data = preg_replace('/^\xEF\xBB\xBF/', '', $data) ?? $data;
        $lines = preg_split('/\r\n|\r|\n/', preg_replace('/\r?\n[ \t]/', '', $data) ?? $data) ?: [];

        $events = [];
        $stack = [];
        $properties = [];
        $seenCalendar = false;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $property = self::parseLine($line);
            if ($property === null) {
                continue;
            }
            $name = $property->getName();
            if ($name === 'BEGIN') {
                $component = strtoupper(trim($property->getValue()));
                $seenCalendar = $seenCalendar || $component === 'VCALENDAR';
                $stack[] = $component;
                if ($component === 'VEVENT') {
                    $properties = [];
                }

                continue;
            }
            if ($name === 'END') {
                $component = array_pop($stack);
                if ($component === 'VEVENT') {
                    $events[] = new Event($properties);
                }

                continue;
            }
            if (end($stack) === 'VEVENT') {
                $properties[] = $property;
            }
        }
        if (!$seenCalendar) {
            self::logErrorAndThrow(RuntimeException::class, 'No VCALENDAR found in iCalendar data.');
        }

        return new Document($events);
    }

    public static function fromFile(string $path): Document {
        return self::fromString(File::read($path));
    }

    /** name *(";" param "=" value) ":" value — parameter values may be quoted. */
    private static function parseLine(string $line): ?Property {
        if (preg_match('/^([A-Za-z0-9-]+)((?:;[A-Za-z0-9-]+=(?:"[^"]*"|[^";:]*)(?:,(?:"[^"]*"|[^";:]*))*)*):(.*)$/s', $line, $m) !== 1) {
            return null;
        }
        $parameters = [];
        if ($m[2] !== '' && preg_match_all('/;([A-Za-z0-9-]+)=((?:"[^"]*"|[^";:]*)(?:,(?:"[^"]*"|[^";:]*))*)/', $m[2], $params, PREG_SET_ORDER) > 0) {
            foreach ($params as $param) {
                $parameters[strtoupper($param[1])] = trim($param[2], '"');
            }
        }

        return new Property(strtoupper($m[1]), $m[3], $parameters);
    }
}
