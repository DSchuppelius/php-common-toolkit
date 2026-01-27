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
            return self::logErrorAndReturn(0, "Fehler in Datumsberechnung für $month/$year: " . $e->getMessage());
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

        return self::logWarningAndReturn(null, "Kein {$n}. Wochentag ({$weekday->name}) im Monat $month/$year gefunden");
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
    public static function isValidDate(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y', 'dmY']): bool {
        return self::getValidDateFormat($value, $acceptedFormats) !== null;
    }

    /**
     * Gibt das erste gültige Datumsformat zurück, das dem gegebenen Wert entspricht.
     *
     * @param string $value Der zu überprüfende Datumswert.
     * @param array $acceptedFormats Eine Liste akzeptierter Formate.
     * @return string|null Das erste gültige Format oder null, wenn keines gefunden wurde.
     */
    public static function getValidDateFormat(string $value, array $acceptedFormats = ['Y-m-d', 'Ymd', 'd.m.Y', 'd.m.y', 'd-m-Y', 'd/m/Y', 'dmY']): ?string {
        foreach ($acceptedFormats as $format) {
            if (self::isCleanDateParse(DateTime::createFromFormat($format, $value))) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Erweitert ein Kurzdatum mit zweistelliger Jahreszahl zu einem vollständigen Datum.
     *
     * Unterstützt verschiedene Formate wie "1.1.26", "01.01.26", "01/01/26".
     * Verwendet ein dynamisches 50-Jahres-Fenster basierend auf dem aktuellen Jahr.
     * Daten mit bereits vierstelliger Jahreszahl werden normalisiert zurückgegeben.
     *
     * @param string $date Das Datum (mit zwei- oder vierstelliger Jahreszahl).
     * @return string Das Datum mit vierstelliger Jahreszahl im selben Format.
     * @throws InvalidArgumentException Wenn das Datum nicht erkannt werden kann.
     *
     * @see expandYear() Für die Jahres-Konvertierungslogik.
     */
    public static function expandShortYear(string $date): string {
        $date = trim($date);

        // Trennzeichen erkennen
        $separator = null;
        foreach (['.', '/', '-'] as $sep) {
            if (str_contains($date, $sep)) {
                $separator = $sep;
                break;
            }
        }

        // Kompaktes Format ohne Trennzeichen (6 oder 8 Ziffern)
        if ($separator === null && preg_match('/^\d{6,8}$/', $date)) {
            if (strlen($date) === 8) {
                return $date; // YYYYMMDD oder DDMMYYYY - bereits vollständig
            }
            // 6 Ziffern: DDMMYY
            if (preg_match('/^(\d{2})(\d{2})(\d{2})$/', $date, $m)) {
                $year = self::expandYear((int) $m[3]);
                return sprintf('%02d%02d%04d', (int) $m[1], (int) $m[2], $year);
            }
        }

        if ($separator === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Kein erkennbares Datumsformat: $date");
        }

        // Mit Trennzeichen: In Teile zerlegen
        $parts = explode($separator, $date);
        if (count($parts) !== 3) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiges Datumsformat: $date");
        }

        [$a, $b, $c] = array_map('intval', $parts);

        // Vierstellig am Ende (DE/US: TT.MM.JJJJ oder MM/DD/YYYY)
        if (strlen($parts[2]) === 4) {
            return sprintf('%02d%s%02d%s%04d', $a, $separator, $b, $separator, $c);
        }

        // Vierstellig am Anfang (ISO: JJJJ-MM-TT)
        if (strlen($parts[0]) === 4) {
            return sprintf('%04d%s%02d%s%02d', $a, $separator, $b, $separator, $c);
        }

        // Zweistellig: Expandieren
        // Jahr am Ende (DE/US Format) - häufigster Fall
        if (strlen($parts[2]) === 2) {
            $year = self::expandYear($c);
            return sprintf('%02d%s%02d%s%04d', $a, $separator, $b, $separator, $year);
        }

        // Jahr am Anfang (kurzes ISO: JJ-MM-TT) - selten, nur wenn > 31
        if (strlen($parts[0]) === 2 && $a > 31) {
            $year = self::expandYear($a);
            return sprintf('%04d%s%02d%s%02d', $year, $separator, $b, $separator, $c);
        }

        self::logErrorAndThrow(InvalidArgumentException::class, "Kein erkennbares Datumsformat: $date");
    }

    /**
     * Konvertiert eine zweistellige Jahreszahl in eine vierstellige.
     *
     * Verwendet ein dynamisches 50-Jahres-Fenster basierend auf dem aktuellen Jahr.
     * Das Ergebnis liegt immer im Bereich [aktuellesJahr - 50, aktuellesJahr + 49].
     *
     * Beispiel bei aktuellem Jahr 2026:
     * - 26 → 2026, 75 → 2075, 76 → 1976, 99 → 1999
     *
     * @param int $shortYear Die zweistellige Jahreszahl (0-99).
     * @return int Die vierstellige Jahreszahl.
     */
    public static function expandYear(int $shortYear): int {
        if ($shortYear < 0 || $shortYear > 99) {
            return $shortYear; // Bereits vierstellig oder ungültig
        }

        $currentYear = (int) date('Y');
        $currentCentury = (int) floor($currentYear / 100) * 100;
        $currentTwoDigit = $currentYear % 100;

        // Pivot = aktuelle zweistellige Jahreszahl + 50 (mod 100)
        $pivot = ($currentTwoDigit + 50) % 100;

        if ($pivot > $currentTwoDigit) {
            // Kein Überlauf: Jahre < pivot → aktuelles Jahrhundert, >= pivot → vorheriges
            return $shortYear < $pivot ? $currentCentury + $shortYear : ($currentCentury - 100) + $shortYear;
        } else {
            // Überlauf (z.B. 2076): Jahre >= pivot UND < 100 → aktuelles, < pivot → nächstes Jahrhundert
            return $shortYear >= $pivot ? $currentCentury + $shortYear : ($currentCentury + 100) + $shortYear;
        }
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

        self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiges Datumsformat: $date");
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

        return self::logWarningAndReturn(null, "Kein gültiges Format gefunden für: $dateString");
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
            return self::logErrorAndReturn(false, "Ungültiges DE-Datum: $value");
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
            return self::logErrorAndReturn(false, "Ungültiges ISO-Datum: $value");
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

    /**
     * Berechnet das Alter basierend auf einem Geburtsdatum.
     *
     * @param DateTimeInterface $birthDate Das Geburtsdatum.
     * @param DateTimeInterface|null $referenceDate Referenzdatum (Standard: heute).
     * @return int Das Alter in Jahren.
     */
    public static function getAge(DateTimeInterface $birthDate, ?DateTimeInterface $referenceDate = null): int {
        $referenceDate = $referenceDate ?? new DateTimeImmutable();
        $diff = $referenceDate->diff($birthDate);
        return $diff->y;
    }

    /**
     * Gibt das Quartal eines Datums zurück (1-4).
     *
     * @param DateTimeInterface $date Das Datum.
     * @return int Das Quartal (1-4).
     */
    public static function getQuarter(DateTimeInterface $date): int {
        $month = (int) $date->format('n');
        return (int) ceil($month / 3);
    }

    /**
     * Gibt den ersten Tag des Monats zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der erste Tag des Monats.
     */
    public static function startOfMonth(DateTimeInterface $date): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-01 00:00:00')
        );
    }

    /**
     * Gibt den letzten Tag des Monats zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der letzte Tag des Monats.
     */
    public static function endOfMonth(DateTimeInterface $date): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-t 23:59:59')
        );
    }

    /**
     * Gibt den ersten Tag der Woche zurück (Montag).
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der erste Tag der Woche (Montag).
     */
    public static function startOfWeek(DateTimeInterface $date): DateTimeImmutable {
        $dayOfWeek = (int) $date->format('N'); // 1 = Montag, 7 = Sonntag
        $diff = $dayOfWeek - 1;

        $start = DateTimeImmutable::createFromInterface($date);
        return $start->modify("-{$diff} days")->setTime(0, 0, 0);
    }

    /**
     * Gibt den letzten Tag der Woche zurück (Sonntag).
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der letzte Tag der Woche (Sonntag).
     */
    public static function endOfWeek(DateTimeInterface $date): DateTimeImmutable {
        $dayOfWeek = (int) $date->format('N'); // 1 = Montag, 7 = Sonntag
        $diff = 7 - $dayOfWeek;

        $end = DateTimeImmutable::createFromInterface($date);
        return $end->modify("+{$diff} days")->setTime(23, 59, 59);
    }

    /**
     * Gibt den ersten Tag des Jahres zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der erste Tag des Jahres.
     */
    public static function startOfYear(DateTimeInterface $date): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-01-01 00:00:00')
        );
    }

    /**
     * Gibt den letzten Tag des Jahres zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der letzte Tag des Jahres.
     */
    public static function endOfYear(DateTimeInterface $date): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-12-31 23:59:59')
        );
    }

    /**
     * Gibt den ersten Tag des Quartals zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der erste Tag des Quartals.
     */
    public static function startOfQuarter(DateTimeInterface $date): DateTimeImmutable {
        $quarter = self::getQuarter($date);
        $month = ($quarter - 1) * 3 + 1;
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y') . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-01 00:00:00'
        );
    }

    /**
     * Gibt den letzten Tag des Quartals zurück.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return DateTimeImmutable Der letzte Tag des Quartals.
     */
    public static function endOfQuarter(DateTimeInterface $date): DateTimeImmutable {
        $quarter = self::getQuarter($date);
        $month = $quarter * 3;
        $lastDay = self::getLastDay((int) $date->format('Y'), $month);
        return DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y') . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $lastDay, 2, '0', STR_PAD_LEFT) . ' 23:59:59'
        );
    }

    /**
     * Berechnet die Anzahl der Arbeitstage zwischen zwei Daten.
     * Wochenenden werden ausgeschlossen, Feiertage optional.
     *
     * @param DateTimeInterface $start Startdatum.
     * @param DateTimeInterface $end Enddatum.
     * @param array $holidays Array von Feiertagen (DateTimeInterface oder 'Y-m-d' Strings).
     * @return int Anzahl der Arbeitstage.
     */
    public static function getWorkingDays(DateTimeInterface $start, DateTimeInterface $end, array $holidays = []): int {
        // Sicherstellen, dass start <= end
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        // Feiertage in ein Set von 'Y-m-d' Strings konvertieren
        $holidaySet = [];
        foreach ($holidays as $holiday) {
            if ($holiday instanceof DateTimeInterface) {
                $holidaySet[$holiday->format('Y-m-d')] = true;
            } else {
                $holidaySet[$holiday] = true;
            }
        }

        $workingDays = 0;
        $current = DateTimeImmutable::createFromInterface($start);
        $endDate = DateTimeImmutable::createFromInterface($end);

        while ($current <= $endDate) {
            $dayOfWeek = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');

            // Wochenende (6 = Samstag, 7 = Sonntag) oder Feiertag überspringen
            if ($dayOfWeek < 6 && !isset($holidaySet[$dateStr])) {
                $workingDays++;
            }

            $current = $current->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Berechnet das Osterdatum für ein gegebenes Jahr (Gregorianischer Kalender).
     * Algorithmus nach Gauss/Lichtenberg.
     *
     * @param int $year Das Jahr.
     * @return DateTimeImmutable Das Osterdatum.
     */
    public static function getEasterDate(int $year): DateTimeImmutable {
        $a = $year % 19;
        $b = (int) floor($year / 100);
        $c = $year % 100;
        $d = (int) floor($b / 4);
        $e = $b % 4;
        $f = (int) floor(($b + 8) / 25);
        $g = (int) floor(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int) floor($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = (int) floor(($a + 11 * $h + 22 * $l) / 451);
        $month = (int) floor(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return DateTimeImmutable::createFromFormat('Y-m-d', "$year-$month-$day");
    }

    /**
     * Gibt die beweglichen Feiertage für ein Jahr zurück (Deutschland).
     * Basiert auf dem Osterdatum.
     *
     * @param int $year Das Jahr.
     * @return array<string, DateTimeImmutable> Feiertage mit Namen als Schlüssel.
     */
    public static function getGermanMovableHolidays(int $year): array {
        $easter = self::getEasterDate($year);

        return [
            'Karfreitag' => $easter->modify('-2 days'),
            'Ostersonntag' => $easter,
            'Ostermontag' => $easter->modify('+1 day'),
            'Christi Himmelfahrt' => $easter->modify('+39 days'),
            'Pfingstsonntag' => $easter->modify('+49 days'),
            'Pfingstmontag' => $easter->modify('+50 days'),
            'Fronleichnam' => $easter->modify('+60 days'),
        ];
    }

    /**
     * Gibt die festen Feiertage für ein Jahr zurück (Deutschland, bundesweit).
     *
     * @param int $year Das Jahr.
     * @return array<string, DateTimeImmutable> Feiertage mit Namen als Schlüssel.
     */
    public static function getGermanFixedHolidays(int $year): array {
        return [
            'Neujahr' => DateTimeImmutable::createFromFormat('Y-m-d', "$year-01-01"),
            'Tag der Arbeit' => DateTimeImmutable::createFromFormat('Y-m-d', "$year-05-01"),
            'Tag der Deutschen Einheit' => DateTimeImmutable::createFromFormat('Y-m-d', "$year-10-03"),
            'Erster Weihnachtstag' => DateTimeImmutable::createFromFormat('Y-m-d', "$year-12-25"),
            'Zweiter Weihnachtstag' => DateTimeImmutable::createFromFormat('Y-m-d', "$year-12-26"),
        ];
    }

    /**
     * Gibt alle bundesweiten deutschen Feiertage für ein Jahr zurück.
     *
     * @param int $year Das Jahr.
     * @return array<string, DateTimeImmutable> Feiertage mit Namen als Schlüssel.
     */
    public static function getGermanHolidays(int $year): array {
        return array_merge(
            self::getGermanFixedHolidays($year),
            self::getGermanMovableHolidays($year)
        );
    }

    /**
     * Prüft ob ein Datum ein deutscher Feiertag ist.
     *
     * @param DateTimeInterface $date Das zu prüfende Datum.
     * @return bool True wenn es ein Feiertag ist.
     */
    public static function isGermanHoliday(DateTimeInterface $date): bool {
        $year = (int) $date->format('Y');
        $holidays = self::getGermanHolidays($year);

        $dateStr = $date->format('Y-m-d');
        foreach ($holidays as $holiday) {
            if ($holiday->format('Y-m-d') === $dateStr) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt die Kalenderwoche eines Datums zurück (ISO 8601).
     *
     * @param DateTimeInterface $date Das Datum.
     * @return int Die Kalenderwoche (1-53).
     */
    public static function getWeekNumber(DateTimeInterface $date): int {
        return (int) $date->format('W');
    }

    /**
     * Gibt das Jahr der Kalenderwoche zurück (ISO 8601).
     * Kann sich am Jahresanfang/Ende vom Kalenderjahr unterscheiden.
     *
     * @param DateTimeInterface $date Das Datum.
     * @return int Das Jahr der Kalenderwoche.
     */
    public static function getWeekYear(DateTimeInterface $date): int {
        return (int) $date->format('o');
    }

    /**
     * Gibt den Tag des Jahres zurück (1-366).
     *
     * @param DateTimeInterface $date Das Datum.
     * @return int Der Tag des Jahres.
     */
    public static function getDayOfYear(DateTimeInterface $date): int {
        return (int) $date->format('z') + 1;
    }

    /**
     * Berechnet die Anzahl der Tage im Jahr.
     *
     * @param int $year Das Jahr.
     * @return int Anzahl der Tage (365 oder 366).
     */
    public static function getDaysInYear(int $year): int {
        return self::isLeapYear($year) ? 366 : 365;
    }

    /**
     * Addiert Arbeitstage zu einem Datum.
     *
     * @param DateTimeInterface $date Das Startdatum.
     * @param int $workingDays Anzahl der Arbeitstage.
     * @param array $holidays Array von Feiertagen.
     * @return DateTimeImmutable Das Enddatum.
     */
    public static function addWorkingDays(DateTimeInterface $date, int $workingDays, array $holidays = []): DateTimeImmutable {
        $current = DateTimeImmutable::createFromInterface($date);
        $direction = $workingDays >= 0 ? '+1 day' : '-1 day';
        $workingDays = abs($workingDays);

        // Feiertage in ein Set konvertieren
        $holidaySet = [];
        foreach ($holidays as $holiday) {
            if ($holiday instanceof DateTimeInterface) {
                $holidaySet[$holiday->format('Y-m-d')] = true;
            } else {
                $holidaySet[$holiday] = true;
            }
        }

        $addedDays = 0;
        while ($addedDays < $workingDays) {
            $current = $current->modify($direction);
            $dayOfWeek = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');

            if ($dayOfWeek < 6 && !isset($holidaySet[$dateStr])) {
                $addedDays++;
            }
        }

        return $current;
    }

    /**
     * Prüft ob ein Datum ein Arbeitstag ist.
     *
     * @param DateTimeInterface $date Das zu prüfende Datum.
     * @param array $holidays Array von Feiertagen.
     * @return bool True wenn es ein Arbeitstag ist.
     */
    public static function isWorkingDay(DateTimeInterface $date, array $holidays = []): bool {
        // Wochenende prüfen
        if (self::isWeekend($date)) {
            return false;
        }

        // Feiertage prüfen
        $dateStr = $date->format('Y-m-d');
        foreach ($holidays as $holiday) {
            $holidayStr = $holiday instanceof DateTimeInterface
                ? $holiday->format('Y-m-d')
                : $holiday;

            if ($dateStr === $holidayStr) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gibt das Datum des nächsten Arbeitstages zurück.
     *
     * @param DateTimeInterface $date Das Startdatum.
     * @param array $holidays Array von Feiertagen.
     * @return DateTimeImmutable Der nächste Arbeitstag.
     */
    public static function getNextWorkingDay(DateTimeInterface $date, array $holidays = []): DateTimeImmutable {
        $current = DateTimeImmutable::createFromInterface($date)->modify('+1 day');

        while (!self::isWorkingDay($current, $holidays)) {
            $current = $current->modify('+1 day');
        }

        return $current;
    }

    /**
     * Gibt das Datum des vorherigen Arbeitstages zurück.
     *
     * @param DateTimeInterface $date Das Startdatum.
     * @param array $holidays Array von Feiertagen.
     * @return DateTimeImmutable Der vorherige Arbeitstag.
     */
    public static function getPreviousWorkingDay(DateTimeInterface $date, array $holidays = []): DateTimeImmutable {
        $current = DateTimeImmutable::createFromInterface($date)->modify('-1 day');

        while (!self::isWorkingDay($current, $holidays)) {
            $current = $current->modify('-1 day');
        }

        return $current;
    }

    /**
     * Berechnet die Differenz zwischen zwei Daten in verschiedenen Einheiten.
     *
     * @param DateTimeInterface $start Startdatum.
     * @param DateTimeInterface $end Enddatum.
     * @param string $unit Einheit: 'years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'.
     * @return int Die Differenz in der angegebenen Einheit.
     */
    public static function diffIn(DateTimeInterface $start, DateTimeInterface $end, string $unit = 'days'): int {
        $diff = $start->diff($end);
        $totalDays = (int) $diff->format('%r%a');

        return match ($unit) {
            'years' => $diff->y * ($diff->invert ? -1 : 1),
            'months' => ($diff->y * 12 + $diff->m) * ($diff->invert ? -1 : 1),
            'weeks' => (int) floor($totalDays / 7),
            'days' => $totalDays,
            'hours' => $totalDays * 24 + $diff->h * ($diff->invert ? -1 : 1),
            'minutes' => ($totalDays * 24 * 60) + ($diff->h * 60) + $diff->i * ($diff->invert ? -1 : 1),
            'seconds' => ($totalDays * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s * ($diff->invert ? -1 : 1),
            default => $totalDays,
        };
    }

    /**
     * Erzeugt eine menschenlesbare Darstellung der Zeitdifferenz.
     *
     * @param DateTimeInterface $date Das Datum.
     * @param DateTimeInterface|null $reference Referenzdatum (Standard: jetzt).
     * @param string $locale Sprache ('de' oder 'en', Standard: 'de').
     * @return string Menschenlesbare Differenz (z.B. "vor 2 Stunden").
     */
    public static function humanDiff(DateTimeInterface $date, ?DateTimeInterface $reference = null, string $locale = 'de'): string {
        $reference = $reference ?? new DateTimeImmutable();
        $diff = $reference->diff($date);

        $isPast = $diff->invert === 1;
        $totalSeconds = abs($diff->days * 86400 + $diff->h * 3600 + $diff->i * 60 + $diff->s);

        $translations = [
            'de' => [
                'just_now' => 'gerade eben',
                'seconds' => ['Sekunde', 'Sekunden'],
                'minutes' => ['Minute', 'Minuten'],
                'hours' => ['Stunde', 'Stunden'],
                'days' => ['Tag', 'Tagen'],
                'weeks' => ['Woche', 'Wochen'],
                'months' => ['Monat', 'Monaten'],
                'years' => ['Jahr', 'Jahren'],
                'ago' => 'vor %s',
                'in' => 'in %s',
            ],
            'en' => [
                'just_now' => 'just now',
                'seconds' => ['second', 'seconds'],
                'minutes' => ['minute', 'minutes'],
                'hours' => ['hour', 'hours'],
                'days' => ['day', 'days'],
                'weeks' => ['week', 'weeks'],
                'months' => ['month', 'months'],
                'years' => ['year', 'years'],
                'ago' => '%s ago',
                'in' => 'in %s',
            ],
        ];

        $t = $translations[$locale] ?? $translations['de'];

        if ($totalSeconds < 60) {
            return $t['just_now'];
        }

        $value = 0;
        $unit = '';

        if ($diff->y > 0) {
            $value = $diff->y;
            $unit = $t['years'][$value === 1 ? 0 : 1];
        } elseif ($diff->m > 0) {
            $value = $diff->m;
            $unit = $t['months'][$value === 1 ? 0 : 1];
        } elseif ($diff->d >= 7) {
            $value = (int) floor($diff->d / 7);
            $unit = $t['weeks'][$value === 1 ? 0 : 1];
        } elseif ($diff->d > 0) {
            $value = $diff->d;
            $unit = $t['days'][$value === 1 ? 0 : 1];
        } elseif ($diff->h > 0) {
            $value = $diff->h;
            $unit = $t['hours'][$value === 1 ? 0 : 1];
        } elseif ($diff->i > 0) {
            $value = $diff->i;
            $unit = $t['minutes'][$value === 1 ? 0 : 1];
        }

        $formatted = "$value $unit";
        return sprintf($isPast ? $t['ago'] : $t['in'], $formatted);
    }
}