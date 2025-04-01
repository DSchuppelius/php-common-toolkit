<?php
/*
 * Created on   : Tue Apr 02 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use ERRORToolkit\Traits\ErrorLog;
use CommonToolkit\Enums\Weekday;

class DateHelper {
    use ErrorLog;

    public static function getLastDay(int $year, int $month): int {
        try {
            $date = new DateTime("$year-$month-01");
            $date->modify('last day of this month');
            return (int) $date->format('d');
        } catch (\Throwable $e) {
            self::logError("Fehler in Datumsberechnung für $month/$year: " . $e->getMessage());
            return 0;
        }
    }

    public static function getNthWeekdayOfMonth(int $year, int $month, Weekday $weekday, int $n = 1, bool $fromEnd = false): ?DateTimeImmutable {
        $base = new DateTimeImmutable("$year-$month-01");

        if ($fromEnd) {
            $date = $base->modify('last day of this month');
            $count = 0;
            while ((int) $date->format('n') === $month) {
                if ((int) $date->format('w') === $weekday->value) {
                    $count++;
                    if ($count === $n) {
                        return $date;
                    }
                }
                $date = $date->modify('-1 day');
            }
        } else {
            $date = $base;
            $count = 0;
            while ((int) $date->format('n') === $month) {
                if ((int) $date->format('w') === $weekday->value) {
                    $count++;
                    if ($count === $n) {
                        return $date;
                    }
                }
                $date = $date->modify('+1 day');
            }
        }

        self::logWarning("Kein {$n}. Wochentag ({$weekday->name}) im Monat $month/$year gefunden");
        return null;
    }

    public static function isDate(string $value, string &$format = ''): bool {
        if (strlen($value) < 6) return false;

        if (preg_match('/^(20[0-9]{2})[-/]?(0[1-9]|1[0-2])[-/]?(0[1-9]|[12][0-9]|3[01])$/', $value)) {
            $format = 'ISO';
            return true;
        }

        if (preg_match('/^([0-3]?[0-9])[.\-/]{1}([0-3]?[0-9])[.\-/]{1}(?:20)?([0-9]{2})$/', $value)) {
            $format = 'DE';
            return true;
        }

        return false;
    }

    public static function fixDate(string $date): string {
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})/', $date, $matches)) {
            return sprintf('%02d.%02d.%04d', $matches[1], $matches[2], (int) $matches[3]);
        }

        self::logError("Ungültiges Datumsformat: $date");
        throw new \InvalidArgumentException("Ungültiges Datumsformat: $date");
    }

    public static function parseFlexible(string $dateString): ?DateTimeImmutable {
        $formats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y'];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $dateString);
            if ($dt !== false) {
                return DateTimeImmutable::createFromMutable($dt);
            }
        }

        self::logWarning("Kein passendes Format für: $dateString");
        return null;
    }

    public static function getCurrentDateTime(): DateTimeImmutable {
        return new DateTimeImmutable();
    }

    public static function nowFormatted(string $format = 'Y-m-d H:i:s'): string {
        return self::getCurrentDateTime()->format($format);
    }

    public static function addDays(DateTimeInterface $date, int $days): DateTimeInterface {
        return $date->add(new DateInterval("P{$days}D"));
    }

    public static function subtractDays(DateTimeInterface $date, int $days): DateTimeInterface {
        return $date->sub(new DateInterval("P{$days}D"));
    }

    public static function isWeekend(DateTimeInterface $date): bool {
        return in_array((int) $date->format('w'), [0, 6], true); // 0 = Sonntag, 6 = Samstag
    }

    public static function isLeapYear(int $year): bool {
        return (bool) date('L', mktime(0, 0, 0, 1, 1, $year));
    }

    public static function getDayOfWeek(DateTimeInterface $date): string {
        return $date->format('l');
    }

    public static function diffInDays(DateTimeInterface $start, DateTimeInterface $end): int {
        return $start->diff($end)->days;
    }

    public static function isFuture(DateTimeInterface $date): bool {
        return $date > new DateTimeImmutable();
    }

    public static function isPast(DateTimeInterface $date): bool {
        return $date < new DateTimeImmutable();
    }

    public static function isToday(DateTimeInterface $date): bool {
        return $date->format('Y-m-d') === (new DateTimeImmutable())->format('Y-m-d');
    }
}
