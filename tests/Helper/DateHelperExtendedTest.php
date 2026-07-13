<?php
/*
 * Created on   : Wed Jan 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateHelperExtendedTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Data\DateHelper;
use DateTimeImmutable;
use Tests\Contracts\BaseTestCase;

class DateHelperExtendedTest extends BaseTestCase {
    public function test_get_age(): void {
        $birthDate = new DateTimeImmutable('2000-01-01');
        $referenceDate = new DateTimeImmutable('2025-06-15');

        $age = DateHelper::getAge($birthDate, $referenceDate);
        $this->assertEquals(25, $age);
    }

    /**
     * Referenzwerte des Excel-1900-Datumssystems (inkl. Lotus-1-2-3-Schaltjahr-Bug):
     * Serial 1 = 01.01.1900, Serial 59 = 28.02.1900, Serial 60 = fiktiver
     * 29.02.1900 (existiert real nicht), Serial 61 = 01.03.1900.
     */
    public function test_from_excel_serial_reference_values(): void {
        $this->assertSame('1900-01-01', DateHelper::fromExcelSerial(1)?->format('Y-m-d'));
        $this->assertSame('1900-02-28', DateHelper::fromExcelSerial(59)?->format('Y-m-d'));
        $this->assertSame('1900-03-01', DateHelper::fromExcelSerial(61)?->format('Y-m-d'));
        $this->assertSame('2026-07-01', DateHelper::fromExcelSerial(46204)?->format('Y-m-d'));

        // Nachkommastellen sind der Tagesbruchteil (Uhrzeit)
        $this->assertSame('2026-07-01 12:00:00', DateHelper::fromExcelSerial(46204.5)?->format('Y-m-d H:i:s'));

        // 1904-System (Mac): Serial 0 = 01.01.1904, kein Lotus-Bug
        $this->assertSame('1904-01-01', DateHelper::fromExcelSerial(0, true)?->format('Y-m-d'));
        $this->assertSame('1904-01-02', DateHelper::fromExcelSerial(1, true)?->format('Y-m-d'));
    }

    public function test_get_quarter(): void {
        $this->assertEquals(1, DateHelper::getQuarter(new DateTimeImmutable('2025-01-15')));
        $this->assertEquals(1, DateHelper::getQuarter(new DateTimeImmutable('2025-03-31')));
        $this->assertEquals(2, DateHelper::getQuarter(new DateTimeImmutable('2025-04-01')));
        $this->assertEquals(3, DateHelper::getQuarter(new DateTimeImmutable('2025-07-15')));
        $this->assertEquals(4, DateHelper::getQuarter(new DateTimeImmutable('2025-12-31')));
    }

    public function test_start_of_month(): void {
        $date = new DateTimeImmutable('2025-03-15 14:30:00');
        $start = DateHelper::startOfMonth($date);

        $this->assertEquals('2025-03-01', $start->format('Y-m-d'));
        $this->assertEquals('00:00:00', $start->format('H:i:s'));
    }

    public function test_end_of_month(): void {
        $date = new DateTimeImmutable('2025-03-15');
        $end = DateHelper::endOfMonth($date);

        $this->assertEquals('2025-03-31', $end->format('Y-m-d'));
        $this->assertEquals('23:59:59', $end->format('H:i:s'));
    }

    public function test_start_of_week(): void {
        // Mittwoch, 15.01.2025
        $date = new DateTimeImmutable('2025-01-15');
        $start = DateHelper::startOfWeek($date);

        $this->assertEquals('2025-01-13', $start->format('Y-m-d')); // Montag
        $this->assertEquals('1', $start->format('N')); // Tag 1 = Montag
    }

    public function test_end_of_week(): void {
        // Mittwoch, 15.01.2025
        $date = new DateTimeImmutable('2025-01-15');
        $end = DateHelper::endOfWeek($date);

        $this->assertEquals('2025-01-19', $end->format('Y-m-d')); // Sonntag
        $this->assertEquals('7', $end->format('N')); // Tag 7 = Sonntag
    }

    public function test_start_of_year(): void {
        $date = new DateTimeImmutable('2025-06-15');
        $start = DateHelper::startOfYear($date);

        $this->assertEquals('2025-01-01', $start->format('Y-m-d'));
    }

    public function test_end_of_year(): void {
        $date = new DateTimeImmutable('2025-06-15');
        $end = DateHelper::endOfYear($date);

        $this->assertEquals('2025-12-31', $end->format('Y-m-d'));
    }

    public function test_start_of_quarter(): void {
        $this->assertEquals('2025-01-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-02-15'))->format('Y-m-d'));
        $this->assertEquals('2025-04-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-05-15'))->format('Y-m-d'));
        $this->assertEquals('2025-07-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-08-15'))->format('Y-m-d'));
        $this->assertEquals('2025-10-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-11-15'))->format('Y-m-d'));
    }

    public function test_end_of_quarter(): void {
        $this->assertEquals('2025-03-31', DateHelper::endOfQuarter(new DateTimeImmutable('2025-02-15'))->format('Y-m-d'));
        $this->assertEquals('2025-06-30', DateHelper::endOfQuarter(new DateTimeImmutable('2025-05-15'))->format('Y-m-d'));
        $this->assertEquals('2025-09-30', DateHelper::endOfQuarter(new DateTimeImmutable('2025-08-15'))->format('Y-m-d'));
        $this->assertEquals('2025-12-31', DateHelper::endOfQuarter(new DateTimeImmutable('2025-11-15'))->format('Y-m-d'));
    }

    public function test_get_working_days(): void {
        // Eine normale Woche: Mo-Fr = 5 Arbeitstage
        $start = new DateTimeImmutable('2025-01-06'); // Montag
        $end = new DateTimeImmutable('2025-01-10'); // Freitag

        $this->assertEquals(5, DateHelper::getWorkingDays($start, $end));

        // Mit Wochenende: Mo-So = 5 Arbeitstage
        $end2 = new DateTimeImmutable('2025-01-12'); // Sonntag
        $this->assertEquals(5, DateHelper::getWorkingDays($start, $end2));

        // Mit Feiertag
        $holidays = ['2025-01-08'];
        $this->assertEquals(4, DateHelper::getWorkingDays($start, $end, $holidays));
    }

    public function test_get_easter_date(): void {
        // Bekannte Osterdaten
        $this->assertEquals('2024-03-31', DateHelper::getEasterDate(2024)->format('Y-m-d'));
        $this->assertEquals('2025-04-20', DateHelper::getEasterDate(2025)->format('Y-m-d'));
        $this->assertEquals('2026-04-05', DateHelper::getEasterDate(2026)->format('Y-m-d'));
    }

    public function test_get_german_holidays(): void {
        $holidays = DateHelper::getGermanHolidays(2025);

        $this->assertArrayHasKey('Neujahr', $holidays);
        $this->assertArrayHasKey('Karfreitag', $holidays);
        $this->assertArrayHasKey('Ostersonntag', $holidays);
        $this->assertArrayHasKey('Tag der Deutschen Einheit', $holidays);
        $this->assertArrayHasKey('Erster Weihnachtstag', $holidays);

        $this->assertEquals('2025-01-01', $holidays['Neujahr']->format('Y-m-d'));
        $this->assertEquals('2025-10-03', $holidays['Tag der Deutschen Einheit']->format('Y-m-d'));
    }

    public function test_is_german_holiday(): void {
        $this->assertTrue(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-01-01')));
        $this->assertTrue(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-12-25')));
        $this->assertFalse(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-01-02')));
    }

    public function test_get_week_number(): void {
        $this->assertEquals(1, DateHelper::getWeekNumber(new DateTimeImmutable('2025-01-01')));
        $this->assertEquals(1, DateHelper::getWeekNumber(new DateTimeImmutable('2024-12-30'))); // Gehört zu KW 1 von 2025!
        $this->assertEquals(52, DateHelper::getWeekNumber(new DateTimeImmutable('2024-12-28'))); // KW 52 von 2024
    }

    public function test_get_day_of_year(): void {
        $this->assertEquals(1, DateHelper::getDayOfYear(new DateTimeImmutable('2025-01-01')));
        $this->assertEquals(365, DateHelper::getDayOfYear(new DateTimeImmutable('2025-12-31')));
        $this->assertEquals(366, DateHelper::getDayOfYear(new DateTimeImmutable('2024-12-31'))); // Schaltjahr
    }

    public function test_get_days_in_year(): void {
        $this->assertEquals(365, DateHelper::getDaysInYear(2025));
        $this->assertEquals(366, DateHelper::getDaysInYear(2024));
    }

    public function test_add_working_days(): void {
        $start = new DateTimeImmutable('2025-01-06'); // Montag
        $result = DateHelper::addWorkingDays($start, 5);

        $this->assertEquals('2025-01-13', $result->format('Y-m-d')); // Montag nächste Woche
    }

    public function test_is_working_day(): void {
        $this->assertTrue(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-06'))); // Montag
        $this->assertFalse(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-04'))); // Samstag
        $this->assertFalse(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-05'))); // Sonntag
    }

    public function test_get_next_working_day(): void {
        // Von Freitag zum Montag
        $friday = new DateTimeImmutable('2025-01-10');
        $next = DateHelper::getNextWorkingDay($friday);
        $this->assertEquals('2025-01-13', $next->format('Y-m-d'));

        // Von Montag zum Dienstag
        $monday = new DateTimeImmutable('2025-01-06');
        $next = DateHelper::getNextWorkingDay($monday);
        $this->assertEquals('2025-01-07', $next->format('Y-m-d'));
    }

    public function test_get_previous_working_day(): void {
        // Von Montag zum Freitag
        $monday = new DateTimeImmutable('2025-01-13');
        $prev = DateHelper::getPreviousWorkingDay($monday);
        $this->assertEquals('2025-01-10', $prev->format('Y-m-d'));
    }

    public function test_diff_in(): void {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-08');

        $this->assertEquals(7, DateHelper::diffIn($start, $end, 'days'));
        $this->assertEquals(1, DateHelper::diffIn($start, $end, 'weeks'));

        $start2 = new DateTimeImmutable('2024-01-01');
        $end2 = new DateTimeImmutable('2025-01-01');
        $this->assertEquals(1, DateHelper::diffIn($start2, $end2, 'years'));
        $this->assertEquals(12, DateHelper::diffIn($start2, $end2, 'months'));
    }

    public function test_human_diff(): void {
        $now = new DateTimeImmutable('2025-01-08 12:00:00');
        $twoHoursAgo = new DateTimeImmutable('2025-01-08 10:00:00');
        $inTwoDays = new DateTimeImmutable('2025-01-10 12:00:00');

        $this->assertEquals('vor 2 Stunden', DateHelper::humanDiff($twoHoursAgo, $now, 'de'));
        $this->assertEquals('in 2 Tagen', DateHelper::humanDiff($inTwoDays, $now, 'de'));
        $this->assertEquals('2 hours ago', DateHelper::humanDiff($twoHoursAgo, $now, 'en'));
    }
}
