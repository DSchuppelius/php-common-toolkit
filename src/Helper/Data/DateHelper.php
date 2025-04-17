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

use CommonToolkit\Enums\Month;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use ERRORToolkit\Traits\ErrorLog;
use CommonToolkit\Enums\Weekday;
use InvalidArgumentException;
use Throwable;

class DateHelper {
    use ErrorLog;

    private static function isCleanDateParse(DateTime|false $date): bool {
        $errors = DateTime::getLastErrors();
        return $date !== false && ($errors === false || is_array($errors) && ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    public static function getLastDay(int $year, int $month): int {
        try {
            $date = new DateTime("$year-$month-01");
            $date->modify('last day of this month');
            return (int) $date->format('d');
        } catch (Throwable $e) {
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

    public static function isDate(string $value, ?string &$format = null, string $preferredFormat = 'DE'): bool {
        if (strlen($value) < 6 || strlen($value) > 10) return false;

        // ISO
        $cleaned = preg_replace('#[^0-9]#', '', $value);
        if (strlen($cleaned) === 8) {
            if (self::isCleanDateParse(DateTime::createFromFormat('Ymd', $cleaned))) {
                $format = 'ISO';
                return true;
            }
        }

        // US vs DE Format: beide potenziell gültig → prüfen
        $sepNormalized = str_replace(['.', '/'], '-', $value);

        $preferredFormat = strtoupper($preferredFormat) === 'US' ? 'US' : 'DE';

        $tryFormats = $preferredFormat === 'US'
            ? ['m-d-Y', 'd-m-Y']
            : ['d-m-Y', 'm-d-Y'];

        foreach ($tryFormats as $try => $fmt) {
            if (self::isCleanDateParse(DateTime::createFromFormat($fmt, $sepNormalized))) {
                $format = $try === 0 ? $preferredFormat : ($preferredFormat === 'DE' ? 'US' : 'DE');
                return true;
            }
        }

        return false;
    }

    public static function isValidDate(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y']): bool {
        return self::getValidDateFormat($value, $acceptedFormats) !== null;
    }

    public static function getValidDateFormat(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y']): ?string {
        foreach ($acceptedFormats as $format) {
            if (self::isCleanDateParse(DateTime::createFromFormat($format, $value))) {
                return $format;
            }
        }

        return null;
    }

    public static function fixDate(string $date): string {
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})/', $date, $matches)) {
            return sprintf('%02d.%02d.%04d', $matches[1], $matches[2], (int) $matches[3]);
        }

        self::logError("Ungültiges Datumsformat: $date");
        throw new InvalidArgumentException("Ungültiges Datumsformat: $date");
    }

    public static function parseFlexible(string $dateString): ?DateTimeImmutable {
        $format = self::getValidDateFormat($dateString);
        if ($format !== null) {
            $dt = DateTime::createFromFormat($format, $dateString);
            return DateTimeImmutable::createFromMutable($dt);
        }

        self::logWarning("Kein gültiges Format gefunden für: $dateString");
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
        return in_array((int) $date->format('w'), [0, 6], true);
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

    public static function germanToIso(string $value): string {
        $value = trim($value);
        if (preg_match("/^\d{1,2}\.\d{1,2}\.\d{2}$/", $value)) {
            $value = preg_replace_callback('/(\d{1,2})\.(\d{1,2})\.(\d{2})$/', function ($matches) {
                return sprintf('%02d.%02d.20%02d', $matches[1], $matches[2], $matches[3]);
            }, $value);
        }
        if (preg_match("/^(\d{1,2})\\.(\d{1,2})\\.(\d{4})/", $value, $matches)) {
            return $matches[3] . "-" . $matches[2] . "-" . $matches[1];
        }
        return $value;
    }

    public static function isoToGerman(?string $value, bool $withTime = false): string {
        if ($value === null || $value === "0000-00-00" || $value === "1970-01-01" || $value === "00:00:00") {
            return '';
        }
        $value = trim($value);
        if (strlen($value) < 8) return $value;

        if ($withTime || strlen($value) > 10) {
            if (preg_match("/^\\d{4}-\\d{2}-\\d{2}/", $value)) {
                return date("d.m.Y H:i", strtotime($value));
            }
            return $value;
        }
        if (preg_match("/^\\d{2}:\\d{2}:\\d{2}/", $value)) {
            return date("H:i", strtotime($value));
        }
        if (preg_match("/^\\d{4}-\\d{2}-\\d{2}/", $value)) {
            return date("d.m.Y", strtotime($value));
        }
        return $value;
    }

    public static function addToDate(string $date, int $days = 0, int $months = 0, int $years = 0): string {
        $timestamp = strtotime($date);
        $newDate = date('Y-m-d H:i:s', mktime(
            (int) date('H', $timestamp),
            (int) date('i', $timestamp),
            (int) date('s', $timestamp),
            (int) date('m', $timestamp) + $months,
            (int) date('d', $timestamp) + $days,
            (int) date('Y', $timestamp) + $years
        ));
        return substr($newDate, 0, strlen($date));
    }

    public static function diffDetailed(DateTimeInterface $start, DateTimeInterface $end): array {
        $diff = $start->diff($end);
        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'total_days' => $diff->days,
            'weeks' => intdiv($diff->days, 7),
        ];
    }

    public static function isBetween(DateTimeInterface $date, DateTimeInterface $start, DateTimeInterface $end): bool {
        return $date >= $start && $date <= $end;
    }

    public static function getMonth(DateTimeInterface $date): Month {
        return Month::fromDate($date);
    }

    public static function getWeekday(DateTimeInterface $date): Weekday {
        return Weekday::fromDate($date);
    }

    public static function getLocalizedMonthName(DateTimeInterface $date, string $locale = 'de'): string {
        return self::getMonth($date)->getName($locale);
    }
}