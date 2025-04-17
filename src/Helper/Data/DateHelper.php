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

use CommonToolkit\Enums\DateFormat;
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

    public static function isDate(string $value, ?DateFormat &$format = null, DateFormat $preferredFormat = DateFormat::DE): bool {
        $len = strlen($value);
        if ($len < 6 || $len > 19) return false;

        // ISO ohne oder mit Uhrzeit
        $cleaned = preg_replace('#[^0-9]#', '', $value);
        $formatMap = [
            8  => ['Ymd', DateFormat::ISO],
            12 => ['YmdHi', DateFormat::ISO],
            14 => ['YmdHis', DateFormat::ISO],
        ];
        if (isset($formatMap[strlen($cleaned)])) {
            [$fmt, $fmtType] = $formatMap[strlen($cleaned)];
            if (self::isCleanDateParse(DateTime::createFromFormat($fmt, $cleaned))) {
                $format = $fmtType;
                return true;
            }
        }

        // Trennzeichen-basierte Formate
        $sepNormalized = str_replace(['.', '/'], '-', $value);
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{2,4})(.*)?$#', $sepNormalized, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $hasTime = str_contains($value, ':');

            // Entscheide Format nach Zahlenlogik
            $isAmbiguous = $a <= 12 && $b <= 12;
            $detected = null;

            if ($a > 12) {
                $detected = 'd-m-Y';
                $format = DateFormat::DE;
            } elseif ($b > 12) {
                $detected = 'm-d-Y';
                $format = DateFormat::US;
            } elseif ($isAmbiguous) {
                // Fallback auf preferredFormat
                $format = $preferredFormat;
                $detected = $preferredFormat === DateFormat::US ? 'm-d-Y' : 'd-m-Y';
            }

            // Zeit prüfen
            if ($hasTime) {
                $detected .= substr_count($m[4], ':') === 2 ? ' H:i:s' : ' H:i';
            }

            if ($detected && self::isCleanDateParse(DateTime::createFromFormat($detected, $sepNormalized))) {
                return true;
            }
        }
        $format = null;
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

    public static function germanToIso(string $value): string|false {
        if (!self::isDate($value, $detectedFormat) || $detectedFormat !== DateFormat::DE) {
            self::logError("Ungültiges DE-Datum: $value");
            return false;
        }
        return self::formatDate($value, DateFormat::ISO, DateFormat::DE) ?? false;
    }

    public static function isoToGerman(?string $value, bool $withTime = false): string|false {
        if ($value === null || in_array($value, ['0000-00-00', '1970-01-01', '00:00:00'], true)) {
            return false;
        } elseif (!self::isDate($value, $detectedFormat) || $detectedFormat !== DateFormat::ISO) {
            self::logError("Ungültiges ISO-Datum: $value");
            return false;
        }

        return self::formatDate($value, DateFormat::DE, DateFormat::ISO, $withTime) ?? false;
    }

    public static function formatDate(string $value, DateFormat $targetFormat, DateFormat $preferredInputFormat = DateFormat::DE, bool $withTime = false): ?string {
        $dateIso = self::normalizeToIso($value, $preferredInputFormat);
        if ($dateIso === null) return null;

        $hasTime = str_contains($dateIso, ':');

        // Sicherstellen, dass ein vollständiger Zeitanteil vorliegt
        $dateTimeString = $hasTime
            ? $dateIso
            : ($withTime ? $dateIso . ' 00:00:00' : $dateIso);

        $dt = DateTime::createFromFormat($hasTime || $withTime ? 'Y-m-d H:i:s' : 'Y-m-d', $dateTimeString);
        if (!$dt) return null;

        return match ($targetFormat) {
            DateFormat::ISO => $dt->format('Y-m-d'),
            DateFormat::DE  => $dt->format($withTime ? 'd.m.Y H:i' : 'd.m.Y'),
            DateFormat::US  => $dt->format($withTime ? 'm/d/Y H:i' : 'm/d/Y'),
            DateFormat::MYSQL_DATETIME => $dt->format('Y-m-d H:i:s'),
            DateFormat::ISO_DATETIME,
            DateFormat::ISO8601 => $dt->format('Y-m-d\TH:i:s'),
        };
    }


    public static function normalizeToIso(string $value, DateFormat $preferredFormat = DateFormat::DE): ?string {
        $detectedFormat = null;

        if (!self::isDate($value, $detectedFormat, $preferredFormat)) {
            return null;
        }

        // ISO direkt zurückgeben (ggf. mit Zeit)
        $cleaned = preg_replace('#[^0-9]#', '', $value);
        if ($detectedFormat === DateFormat::ISO && strlen($cleaned) >= 8) {
            $format = match (strlen($cleaned)) {
                14 => 'YmdHis',
                12 => 'YmdHi',
                8  => 'Ymd',
                default => null
            };

            if ($format !== null) {
                $date = DateTime::createFromFormat($format, $cleaned);
                return $date?->format(strlen($cleaned) > 8 ? 'Y-m-d H:i:s' : 'Y-m-d');
            }
        }

        // DE oder US → passenden Formatstring bestimmen
        $sepNormalized = str_replace(['.', '/'], '-', $value);
        $colonCount = substr_count($value, ':');
        $hasSeconds = $colonCount === 2;
        $hasTime = $colonCount > 0;

        $formatString = match ($detectedFormat) {
            DateFormat::DE => $hasTime ? ($hasSeconds ? 'd-m-Y H:i:s' : 'd-m-Y H:i') : 'd-m-Y',
            DateFormat::US => $hasTime ? ($hasSeconds ? 'm-d-Y H:i:s' : 'm-d-Y H:i') : 'm-d-Y',
            default => 'Y-m-d',
        };

        $date = DateTime::createFromFormat($formatString, $sepNormalized);
        return self::isCleanDateParse($date) ? $date->format($hasTime ? 'Y-m-d H:i:s' : 'Y-m-d') : null;
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