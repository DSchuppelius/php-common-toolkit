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

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\DateTimeFormat;
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

    /**
     * Überprüft, ob ein DateTime-Objekt ohne Fehler erstellt wurde.
     *
     * @param DateTime|false $date Das zu überprüfende Datum.
     * @return bool True, wenn das Datum gültig ist, andernfalls false.
     */
    private static function isCleanDateParse(DateTime|false $date): bool {
        $errors = DateTime::getLastErrors();
        return $date !== false && ($errors === false || is_array($errors) && ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    /**
     * Gibt den letzten Tag eines Monats zurück.
     *
     * @param int $year Das Jahr.
     * @param int $month Der Monat (1-12).
     * @return int Der letzte Tag des Monats oder 0 bei Fehler.
     */
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

    /**
     * Gibt den n-ten Wochentag eines Monats zurück.
     *
     * @param int $year Das Jahr.
     * @param int $month Der Monat (1-12).
     * @param Weekday $weekday Der gesuchte Wochentag.
     * @param int $n Die n-te Instanz des Wochentags (1 = erster, 2 = zweiter, ...).
     * @param bool $fromEnd Ob vom Ende des Monats gezählt werden soll.
     * @return DateTimeImmutable|null Das Datum des n-ten Wochentags oder null, wenn nicht gefunden.
     */
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

    /**
     * Überprüft, ob ein Datum gültig ist.
     *
     * @param string $value Der zu überprüfende Datumswert.
     * @param DateTimeFormat|null $format Das erkannte Datumsformat (optional).
     * @param DateTimeFormat $preferredFormat Bevorzugtes Format (DE oder US).
     * @return bool True, wenn der Wert ein gültiges Datum ist, andernfalls false.
     */
    public static function isDate(string $value, ?DateTimeFormat &$format = null, DateTimeFormat $preferredFormat = DateTimeFormat::DE): bool {
        $len = strlen($value);
        if ($len < 6 || $len > 19) return false;

        // ISO ohne oder mit Uhrzeit
        $cleaned = preg_replace('#[^0-9]#', '', $value);
        $formatMap = [
            8  => ['Ymd', DateTimeFormat::ISO],
            12 => ['YmdHi', DateTimeFormat::ISO],
            14 => ['YmdHis', DateTimeFormat::ISO],
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
                $format = DateTimeFormat::DE;
            } elseif ($b > 12) {
                $detected = 'm-d-Y';
                $format = DateTimeFormat::US;
            } elseif ($isAmbiguous) {
                // Fallback auf preferredFormat
                $format = $preferredFormat;
                $detected = $preferredFormat === DateTimeFormat::US ? 'm-d-Y' : 'd-m-Y';
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

    /**
     * Überprüft, ob ein Datum gültig ist.
     *
     * @param string $value Der zu überprüfende Datumswert.
     * @param array $acceptedFormats Eine Liste akzeptierter Formate.
     * @return bool True, wenn das Datum gültig ist, andernfalls false.
     */
    public static function isValidDate(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y']): bool {
        return self::getValidDateFormat($value, $acceptedFormats) !== null;
    }

    /**
     * Gibt das erste gültige Datumsformat zurück, das dem gegebenen Wert entspricht.
     *
     * @param string $value Der zu überprüfende Datumswert.
     * @param array $acceptedFormats Eine Liste akzeptierter Formate.
     * @return string|null Das erste gültige Format oder null, wenn keines gefunden wurde.
     */
    public static function getValidDateFormat(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y']): ?string {
        foreach ($acceptedFormats as $format) {
            if (self::isCleanDateParse(DateTime::createFromFormat($format, $value))) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Konvertiert ein Datum in das Format 'dd.mm.yyyy'.
     *
     * @param string $date Das Datum, das konvertiert werden soll.
     * @return string Das konvertierte Datum im Format 'dd.mm.yyyy'.
     * @throws InvalidArgumentException Wenn das Datum nicht im erwarteten Format vorliegt.
     */
    public static function fixDate(string $date): string {
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})/', $date, $matches)) {
            return sprintf('%02d.%02d.%04d', $matches[1], $matches[2], (int) $matches[3]);
        }

        self::logError("Ungültiges Datumsformat: $date");
        throw new InvalidArgumentException("Ungültiges Datumsformat: $date");
    }

    /**
     * Konvertiert einen Datumsstring in ein DateTimeImmutable-Objekt.
     *
     * @param string $dateString Der Datumsstring, der konvertiert werden soll.
     * @return DateTimeImmutable|null Das konvertierte DateTimeImmutable-Objekt oder null bei ungültigem Format.
     */
    public static function parseFlexible(string $dateString): ?DateTimeImmutable {
        $format = self::getValidDateFormat($dateString);
        if ($format !== null) {
            $dt = DateTime::createFromFormat($format, $dateString);
            return DateTimeImmutable::createFromMutable($dt);
        }

        self::logWarning("Kein gültiges Format gefunden für: $dateString");
        return null;
    }

    /**
     * Gibt das aktuelle Datum und die Uhrzeit zurück.
     *
     * @return DateTimeImmutable Das aktuelle Datum und die Uhrzeit.
     */
    public static function getCurrentDateTime(): DateTimeImmutable {
        return new DateTimeImmutable();
    }

    /**
     * Gibt das aktuelle Datum und die Uhrzeit im angegebenen Format zurück.
     *
     * @param string $format Das Format, in dem das Datum und die Uhrzeit zurückgegeben werden sollen.
     * @return string Das aktuelle Datum und die Uhrzeit im angegebenen Format.
     */
    public static function nowFormatted(string $format = 'Y-m-d H:i:s'): string {
        return self::getCurrentDateTime()->format($format);
    }

    /**
     * Fügt eine bestimmte Anzahl von Tagen zu einem Datum hinzu.
     *
     * @param DateTime|DateTimeImmutable $date Das Datum, zu dem Tage hinzugefügt werden sollen.
     * @param int $days Die Anzahl der Tage, die hinzugefügt werden sollen.
     * @return DateTime|DateTimeImmutable Das neue Datum nach der Addition.
     */
    public static function addDays(DateTime|DateTimeImmutable $date, int $days): DateTime|DateTimeImmutable {
        return $date->add(new DateInterval("P{$days}D"));
    }

    /**
     * Subtrahiert eine bestimmte Anzahl von Tagen von einem Datum.
     *
     * @param DateTime|DateTimeImmutable $date Das Datum, von dem Tage subtrahiert werden sollen.
     * @param int $days Die Anzahl der Tage, die subtrahiert werden sollen.
     * @return DateTime|DateTimeImmutable Das neue Datum nach der Subtraktion.
     */
    public static function subtractDays(DateTime|DateTimeImmutable $date, int $days): DateTime|DateTimeImmutable {
        return $date->sub(new DateInterval("P{$days}D"));
    }

    /**
     * Überprüft, ob ein Datum auf ein Wochenende fällt (Samstag oder Sonntag).
     *
     * @param DateTimeInterface $date Das zu überprüfende Datum.
     * @return bool True, wenn das Datum auf ein Wochenende fällt, andernfalls false.
     */
    public static function isWeekend(DateTimeInterface $date): bool {
        return in_array((int) $date->format('w'), [0, 6], true);
    }

    /**
     * Überprüft, ob ein Jahr ein Schaltjahr ist.
     *
     * @param int $year Das Jahr, das überprüft werden soll.
     * @return bool True, wenn es ein Schaltjahr ist, andernfalls false.
     */
    public static function isLeapYear(int $year): bool {
        return (bool) date('L', mktime(0, 0, 0, 1, 1, $year));
    }

    /**
     * Gibt den Wochentag für ein gegebenes Datum zurück.
     *
     * @param DateTimeInterface $date Das Datum, dessen Wochentag abgerufen werden soll.
     * @return string Der Name des Wochentags (z.B. "Montag").
     */
    public static function getDayOfWeek(DateTimeInterface $date): string {
        return $date->format('l');
    }

    /**
     * Berechnet die Differenz in Tagen zwischen zwei Datumsangaben.
     *
     * @param DateTimeInterface $start Das Startdatum.
     * @param DateTimeInterface $end Das Enddatum.
     * @return int Die Differenz in Tagen.
     */
    public static function diffInDays(DateTimeInterface $start, DateTimeInterface $end): int {
        return $start->diff($end)->days;
    }

    /**
     * Überprüft, ob ein Datum in der Zukunft liegt.
     *
     * @param DateTimeInterface $date Das zu überprüfende Datum.
     * @return bool True, wenn das Datum in der Zukunft liegt, andernfalls false.
     */
    public static function isFuture(DateTimeInterface $date): bool {
        return $date > new DateTimeImmutable();
    }

    /**
     * Überprüft, ob ein Datum in der Vergangenheit liegt.
     *
     * @param DateTimeInterface $date Das zu überprüfende Datum.
     * @return bool True, wenn das Datum in der Vergangenheit liegt, andernfalls false.
     */
    public static function isPast(DateTimeInterface $date): bool {
        return $date < new DateTimeImmutable();
    }

    /**
     * Überprüft, ob ein Datum heute ist.
     *
     * @param DateTimeInterface $date Das zu überprüfende Datum.
     * @return bool True, wenn das Datum heute ist, andernfalls false.
     */
    public static function isToday(DateTimeInterface $date): bool {
        return $date->format('Y-m-d') === (new DateTimeImmutable())->format('Y-m-d');
    }

    /**
     * Konvertiert ein Datum im deutschen Format (DD.MM.YYYY) in das ISO-Format (YYYY-MM-DD).
     *
     * @param string $value Das Datum im deutschen Format.
     * @return string|false Das Datum im ISO-Format oder false bei ungültigem Datum.
     */
    public static function germanToIso(string $value): string|false {
        if (!self::isDate($value, $detectedFormat) || $detectedFormat !== DateTimeFormat::DE) {
            self::logError("Ungültiges DE-Datum: $value");
            return false;
        }
        return self::formatDate($value, DateTimeFormat::ISO, DateTimeFormat::DE) ?? false;
    }

    /**
     * Konvertiert ein Datum im ISO-Format (YYYY-MM-DD) in das deutsche Format (DD.MM.YYYY).
     *
     * @param string|null $value Das Datum im ISO-Format.
     * @param bool $withTime Ob die Zeit im Ergebnis enthalten sein soll.
     * @return string|false Das Datum im deutschen Format oder false bei ungültigem Datum.
     */
    public static function isoToGerman(?string $value, bool $withTime = false): string|false {
        if ($value === null || in_array($value, ['0000-00-00', '1970-01-01', '00:00:00'], true)) {
            return false;
        } elseif (!self::isDate($value, $detectedFormat) || $detectedFormat !== DateTimeFormat::ISO) {
            self::logError("Ungültiges ISO-Datum: $value");
            return false;
        }

        return self::formatDate($value, DateTimeFormat::DE, DateTimeFormat::ISO, $withTime) ?? false;
    }

    /**
     * Formatiert ein Datum in das angegebene Ziel-Format.
     *
     * @param string $value Das Datum, das formatiert werden soll.
     * @param DateTimeFormat $targetFormat Das Ziel-Format (ISO, DE, US, MYSQL_DATETIME, ISO_DATETIME).
     * @param DateTimeFormat $preferredInputFormat Bevorzugtes Eingabeformat (DE oder US).
     * @param bool $withTime Ob die Zeit im Ergebnis enthalten sein soll.
     * @return string|null Das formatierte Datum oder null, wenn ungültig.
     */
    public static function formatDate(string $value, DateTimeFormat $targetFormat, DateTimeFormat $preferredInputFormat = DateTimeFormat::DE, bool $withTime = false): ?string {
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
            DateTimeFormat::ISO => $dt->format($targetFormat->getPattern()),
            DateTimeFormat::DE  => $dt->format($targetFormat->getPattern($withTime)),
            DateTimeFormat::US  => $dt->format($targetFormat->getPattern($withTime)),
            DateTimeFormat::MYSQL_DATETIME => $dt->format($targetFormat->getPattern()),
            DateTimeFormat::ISO_DATETIME,
            DateTimeFormat::ISO8601 => $dt->format($targetFormat->getPattern()),
        };
    }

    /**
     * Normalisiert ein Datum in ISO-Format (YYYY-MM-DD) und gibt es zurück.
     *
     * @param string $value Das Datum, das normalisiert werden soll.
     * @param DateTimeFormat $preferredFormat Bevorzugtes Format (DE oder US).
     * @return string|null Das normalisierte Datum im ISO-Format oder null, wenn ungültig.
     */
    public static function normalizeToIso(string $value, DateTimeFormat $preferredFormat = DateTimeFormat::DE): ?string {
        $detectedFormat = null;

        if (!self::isDate($value, $detectedFormat, $preferredFormat)) {
            return null;
        }

        // ISO direkt zurückgeben (ggf. mit Zeit)
        $cleaned = preg_replace('#[^0-9]#', '', $value);
        if ($detectedFormat === DateTimeFormat::ISO && strlen($cleaned) >= 8) {
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
            DateTimeFormat::DE => $hasTime ? ($hasSeconds ? 'd-m-Y H:i:s' : 'd-m-Y H:i') : 'd-m-Y',
            DateTimeFormat::US => $hasTime ? ($hasSeconds ? 'm-d-Y H:i:s' : 'm-d-Y H:i') : 'm-d-Y',
            default => 'Y-m-d',
        };

        $date = DateTime::createFromFormat($formatString, $sepNormalized);
        return self::isCleanDateParse($date) ? $date->format($hasTime ? 'Y-m-d H:i:s' : 'Y-m-d') : null;
    }

    /**
     * Fügt einem Datum eine bestimmte Anzahl von Tagen, Monaten und Jahren hinzu.
     *
     * @param string $date Das Datum im Format 'Y-m-d H:i:s'.
     * @param int $days Die Anzahl der Tage, die hinzugefügt werden sollen.
     * @param int $months Die Anzahl der Monate, die hinzugefügt werden sollen.
     * @param int $years Die Anzahl der Jahre, die hinzugefügt werden sollen.
     * @return string Das neue Datum im gleichen Format wie das Eingabedatum.
     */
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

    /**
     * Berechnet die Differenz zwischen zwei Datumsangaben und gibt sie als Array zurück.
     *
     * @param DateTimeInterface $start Das Startdatum.
     * @param DateTimeInterface $end Das Enddatum.
     * @return array Ein Array mit den Differenzen in Jahren, Monaten, Tagen und Gesamtanzahl der Tage.
     */
    public static function diffDetailed(DateTimeInterface $start, DateTimeInterface $end): array {
        $diff = $start->diff($end);
        return [
            'years'      => $diff->y,
            'months'     => $diff->m,
            'days'       => $diff->d,
            'total_days' => $diff->days,
            'weeks'      => intdiv($diff->days, 7),
        ];
    }

    /**
     * Überprüft, ob ein Datum zwischen zwei anderen Daten liegt.
     *
     * @param DateTimeInterface $date Das zu überprüfende Datum.
     * @param DateTimeInterface $start Das Startdatum.
     * @param DateTimeInterface $end Das Enddatum.
     * @return bool True, wenn das Datum zwischen den beiden anderen liegt, andernfalls false.
     */
    public static function isBetween(DateTimeInterface $date, DateTimeInterface $start, DateTimeInterface $end): bool {
        return $date >= $start && $date <= $end;
    }

    /**
     * Gibt den Monat für ein gegebenes Datum zurück.
     *
     * @param DateTimeInterface $date Das Datum, dessen Monat abgerufen werden soll.
     * @return Month Der Monat des angegebenen Datums.
     */
    public static function getMonth(DateTimeInterface $date): Month {
        return Month::fromDate($date);
    }

    /**
     * Gibt den Wochentag für ein gegebenes Datum zurück.
     *
     * @param DateTimeInterface $date Das Datum, dessen Wochentag abgerufen werden soll.
     * @return Weekday Der Wochentag des angegebenen Datums.
     */
    public static function getWeekday(DateTimeInterface $date): Weekday {
        return Weekday::fromDate($date);
    }

    /**
     * Gibt den Namen des Monats in der angegebenen Sprache zurück.
     *
     * @param DateTimeInterface $date Das Datum, dessen Monatname abgerufen werden soll.
     * @param string $locale Die Sprache, in der der Monatname zurückgegeben werden soll (Standard: 'de').
     * @return string Der Name des Monats in der angegebenen Sprache.
     */
    public static function getLocalizedMonthName(DateTimeInterface $date, string $locale = 'de'): string {
        return self::getMonth($date)->getName($locale);
    }

    /**
     * Parst einen DateTime-String länder-spezifisch.
     *
     * @param string $value Der zu parsende DateTime-String
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return DateTimeImmutable|null Das geparste Datum oder null wenn nicht erkannt
     */
    public static function parseDateTime(string $value, CountryCode $country = CountryCode::Germany): ?DateTimeImmutable {
        $format = self::detectDateTimeFormat($value, $country);
        if ($format === null) {
            return null;
        }

        if ($format === 'U') {
            $timestamp = (int) $value;
            if (strlen($value) === 13) {
                $timestamp = intval($timestamp / 1000);
            }
            return DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: null;
        }

        if ($format === 'strtotime') {
            $timestamp = strtotime($value);
            return DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: null;
        }

        return DateTimeImmutable::createFromFormat($format, $value) ?: null;
    }

    /**
     * Prüft, ob ein String ein gültiges Datum/Zeit ist.
     *
     * @param string $value Der zu prüfende String
     * @param string|null $format Spezifisches Format oder null für Auto-Detection
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return bool True wenn gültiges Datum
     */
    public static function isDateTime(string $value, ?string $format = null, CountryCode $country = CountryCode::Germany): bool {
        if ($format) {
            return DateTimeImmutable::createFromFormat($format, $value) !== false;
        }

        return self::detectDateTimeFormat($value, $country) !== null;
    }

    /**
     * Erkennt das DateTime-Format eines Strings.
     *
     * @param string $value Der DateTime-String
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return string|null Das erkannte Format oder null
     */
    public static function detectDateTimeFormat(string $value, CountryCode $country = CountryCode::Germany): ?string {
        return self::detectFormatInternal($value, $country);
    }

    /**
     * Interne Methode zur Format-Erkennung.
     *
     * @param string $value Der DateTime-String
     * @param CountryCode $country Das Land für länder-spezifische Formatinterpretation
     * @return string|null Das erkannte Format oder null ('strtotime' für strtotime-Fallback)
     */
    private static function detectFormatInternal(string $value, CountryCode $country): ?string {
        // Unix timestamp prüfen (10 oder 13 Stellen)
        if (ctype_digit($value) && (strlen($value) === 10 || strlen($value) === 13)) {
            $timestamp = (int) $value;
            if ($timestamp > 0 && $timestamp < 2147483647) {
                return 'U';
            }
        }

        // Alle möglichen Formate sammeln
        $formats = [
            // Standard-Formate (vorsichtig - nur eindeutige Formate)
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            'Y-m-d',        // ISO Format: YYYY-MM-DD
            'd.m.Y',        // Deutsch: DD.MM.YYYY (sicherer als DD-MM-YYYY)
            'd.m.Y H:i:s',  // Deutsch mit Zeit
            'd.m.y',        // Deutsch 2-stellig: DD.MM.YY
            'd.m.y H:i:s',  // Deutsch 2-stellig mit Zeit
        ];

        // Länder-spezifische Formate hinzufügen
        $countryFormats = $country->getDateTimeFormatGroup()->getFormats();
        $formats = array_merge($formats, $countryFormats);

        // Formate durchprobieren mit Round-Trip-Validierung
        foreach ($formats as $fmt) {
            $date = DateTimeImmutable::createFromFormat($fmt, $value);
            if ($date !== false) {
                // Prüfe, ob das Format korrekt rück-formatiert wird (Round-Trip)
                // Dies verhindert, dass d.m.Y für "29.12.15" matched (ergibt "29.12.0015")
                if ($date->format($fmt) === $value) {
                    return $fmt;
                }
            }
        }

        // Fallback: strtotime (nur bei längeren Strings und wenn sie wie typische Datums-Strings aussehen)
        if (strlen($value) >= 8 && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return 'strtotime'; // Spezial-Indikator für strtotime
            }
        }

        return null;
    }

    /**
     * Gibt länder-spezifische DateTime-Formate zurück.
     * Diese Formate können mehrdeutig sein und sollten kontextbezogen interpretiert werden.
     *
     * @param CountryCode $country Das Land
     * @return array<string> Array von DateTime-Formaten
     * @deprecated Use $country->getDateTimeFormatGroup()->getFormats() directly
     */
    private static function getCountrySpecificFormats(CountryCode $country): array {
        return $country->getDateTimeFormatGroup()->getFormats();
    }

    /**
     * Konvertiert ein Land zu einem bevorzugten DateTimeFormat.
     * Nutzt die Verbindung zwischen CountryCode, DateTimeFormatGroup und DateTimeFormat.
     *
     * @param CountryCode $country Das Land
     * @return DateTimeFormat Das bevorzugte Format für dieses Land
     */
    public static function getPreferredFormatForCountry(CountryCode $country): DateTimeFormat {
        return DateTimeFormat::fromFormatGroup($country->getDateTimeFormatGroup());
    }

    /**
     * Parst einen DateTime-String mit automatischer Länder-spezifischer Format-Erkennung.
     * Diese Methode demonstriert die elegante Verbindung der drei Enums.
     *
     * @param string $value Der DateTime-String
     * @param CountryCode $country Das Land für Formatpräferenz
     * @return DateTimeImmutable|null Das geparste Datum
     */
    public static function parseWithCountryPreference(string $value, CountryCode $country): ?DateTimeImmutable {
        // Zuerst mit länder-spezifischen Formaten versuchen
        $parsed = self::parseDateTime($value, $country);

        if ($parsed !== null) {
            return $parsed;
        }

        // Fallback: Mit bevorzugtem Format des Landes versuchen
        $preferredFormat = self::getPreferredFormatForCountry($country);
        $pattern = $preferredFormat->getPattern();

        return DateTimeImmutable::createFromFormat($pattern, $value) ?: null;
    }
}
