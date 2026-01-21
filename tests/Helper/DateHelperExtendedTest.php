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
    public function testGetAge(): void {
        $birthDate = new DateTimeImmutable('2000-01-01');
        $referenceDate = new DateTimeImmutable('2025-06-15');

        $age = DateHelper::getAge($birthDate, $referenceDate);
        $this->assertEquals(25, $age);
    }

    public function testGetQuarter(): void {
        $this->assertEquals(1, DateHelper::getQuarter(new DateTimeImmutable('2025-01-15')));
        $this->assertEquals(1, DateHelper::getQuarter(new DateTimeImmutable('2025-03-31')));
        $this->assertEquals(2, DateHelper::getQuarter(new DateTimeImmutable('2025-04-01')));
        $this->assertEquals(3, DateHelper::getQuarter(new DateTimeImmutable('2025-07-15')));
        $this->assertEquals(4, DateHelper::getQuarter(new DateTimeImmutable('2025-12-31')));
    }

    public function testStartOfMonth(): void {
        $date = new DateTimeImmutable('2025-03-15 14:30:00');
        $start = DateHelper::startOfMonth($date);

        $this->assertEquals('2025-03-01', $start->format('Y-m-d'));
        $this->assertEquals('00:00:00', $start->format('H:i:s'));
    }

    public function testEndOfMonth(): void {
        $date = new DateTimeImmutable('2025-03-15');
        $end = DateHelper::endOfMonth($date);

        $this->assertEquals('2025-03-31', $end->format('Y-m-d'));
        $this->assertEquals('23:59:59', $end->format('H:i:s'));
    }

    public function testStartOfWeek(): void {
        // Mittwoch, 15.01.2025
        $date = new DateTimeImmutable('2025-01-15');
        $start = DateHelper::startOfWeek($date);

        $this->assertEquals('2025-01-13', $start->format('Y-m-d')); // Montag
        $this->assertEquals('1', $start->format('N')); // Tag 1 = Montag
    }

    public function testEndOfWeek(): void {
        // Mittwoch, 15.01.2025
        $date = new DateTimeImmutable('2025-01-15');
        $end = DateHelper::endOfWeek($date);

        $this->assertEquals('2025-01-19', $end->format('Y-m-d')); // Sonntag
        $this->assertEquals('7', $end->format('N')); // Tag 7 = Sonntag
    }

    public function testStartOfYear(): void {
        $date = new DateTimeImmutable('2025-06-15');
        $start = DateHelper::startOfYear($date);

        $this->assertEquals('2025-01-01', $start->format('Y-m-d'));
    }

    public function testEndOfYear(): void {
        $date = new DateTimeImmutable('2025-06-15');
        $end = DateHelper::endOfYear($date);

        $this->assertEquals('2025-12-31', $end->format('Y-m-d'));
    }

    public function testStartOfQuarter(): void {
        $this->assertEquals('2025-01-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-02-15'))->format('Y-m-d'));
        $this->assertEquals('2025-04-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-05-15'))->format('Y-m-d'));
        $this->assertEquals('2025-07-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-08-15'))->format('Y-m-d'));
        $this->assertEquals('2025-10-01', DateHelper::startOfQuarter(new DateTimeImmutable('2025-11-15'))->format('Y-m-d'));
    }

    public function testEndOfQuarter(): void {
        $this->assertEquals('2025-03-31', DateHelper::endOfQuarter(new DateTimeImmutable('2025-02-15'))->format('Y-m-d'));
        $this->assertEquals('2025-06-30', DateHelper::endOfQuarter(new DateTimeImmutable('2025-05-15'))->format('Y-m-d'));
        $this->assertEquals('2025-09-30', DateHelper::endOfQuarter(new DateTimeImmutable('2025-08-15'))->format('Y-m-d'));
        $this->assertEquals('2025-12-31', DateHelper::endOfQuarter(new DateTimeImmutable('2025-11-15'))->format('Y-m-d'));
    }

    public function testGetWorkingDays(): void {
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

    public function testGetEasterDate(): void {
        // Bekannte Osterdaten
        $this->assertEquals('2024-03-31', DateHelper::getEasterDate(2024)->format('Y-m-d'));
        $this->assertEquals('2025-04-20', DateHelper::getEasterDate(2025)->format('Y-m-d'));
        $this->assertEquals('2026-04-05', DateHelper::getEasterDate(2026)->format('Y-m-d'));
    }

    public function testGetGermanHolidays(): void {
        $holidays = DateHelper::getGermanHolidays(2025);

        $this->assertArrayHasKey('Neujahr', $holidays);
        $this->assertArrayHasKey('Karfreitag', $holidays);
        $this->assertArrayHasKey('Ostersonntag', $holidays);
        $this->assertArrayHasKey('Tag der Deutschen Einheit', $holidays);
        $this->assertArrayHasKey('Erster Weihnachtstag', $holidays);

        $this->assertEquals('2025-01-01', $holidays['Neujahr']->format('Y-m-d'));
        $this->assertEquals('2025-10-03', $holidays['Tag der Deutschen Einheit']->format('Y-m-d'));
    }

    public function testIsGermanHoliday(): void {
        $this->assertTrue(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-01-01')));
        $this->assertTrue(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-12-25')));
        $this->assertFalse(DateHelper::isGermanHoliday(new DateTimeImmutable('2025-01-02')));
    }

    public function testGetWeekNumber(): void {
        $this->assertEquals(1, DateHelper::getWeekNumber(new DateTimeImmutable('2025-01-01')));
        $this->assertEquals(1, DateHelper::getWeekNumber(new DateTimeImmutable('2024-12-30'))); // Gehört zu KW 1 von 2025!
        $this->assertEquals(52, DateHelper::getWeekNumber(new DateTimeImmutable('2024-12-28'))); // KW 52 von 2024
    }

    public function testGetDayOfYear(): void {
        $this->assertEquals(1, DateHelper::getDayOfYear(new DateTimeImmutable('2025-01-01')));
        $this->assertEquals(365, DateHelper::getDayOfYear(new DateTimeImmutable('2025-12-31')));
        $this->assertEquals(366, DateHelper::getDayOfYear(new DateTimeImmutable('2024-12-31'))); // Schaltjahr
    }

    public function testGetDaysInYear(): void {
        $this->assertEquals(365, DateHelper::getDaysInYear(2025));
        $this->assertEquals(366, DateHelper::getDaysInYear(2024));
    }

    public function testAddWorkingDays(): void {
        $start = new DateTimeImmutable('2025-01-06'); // Montag
        $result = DateHelper::addWorkingDays($start, 5);

        $this->assertEquals('2025-01-13', $result->format('Y-m-d')); // Montag nächste Woche
    }

    public function testIsWorkingDay(): void {
        $this->assertTrue(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-06'))); // Montag
        $this->assertFalse(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-04'))); // Samstag
        $this->assertFalse(DateHelper::isWorkingDay(new DateTimeImmutable('2025-01-05'))); // Sonntag
    }

    public function testGetNextWorkingDay(): void {
        // Von Freitag zum Montag
        $friday = new DateTimeImmutable('2025-01-10');
        $next = DateHelper::getNextWorkingDay($friday);
        $this->assertEquals('2025-01-13', $next->format('Y-m-d'));

        // Von Montag zum Dienstag
        $monday = new DateTimeImmutable('2025-01-06');
        $next = DateHelper::getNextWorkingDay($monday);
        $this->assertEquals('2025-01-07', $next->format('Y-m-d'));
    }

    public function testGetPreviousWorkingDay(): void {
        // Von Montag zum Freitag
        $monday = new DateTimeImmutable('2025-01-13');
        $prev = DateHelper::getPreviousWorkingDay($monday);
        $this->assertEquals('2025-01-10', $prev->format('Y-m-d'));
    }

    public function testDiffIn(): void {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-08');

        $this->assertEquals(7, DateHelper::diffIn($start, $end, 'days'));
        $this->assertEquals(1, DateHelper::diffIn($start, $end, 'weeks'));

        $start2 = new DateTimeImmutable('2024-01-01');
        $end2 = new DateTimeImmutable('2025-01-01');
        $this->assertEquals(1, DateHelper::diffIn($start2, $end2, 'years'));
        $this->assertEquals(12, DateHelper::diffIn($start2, $end2, 'months'));
    }

    public function testHumanDiff(): void {
        $now = new DateTimeImmutable('2025-01-08 12:00:00');
        $twoHoursAgo = new DateTimeImmutable('2025-01-08 10:00:00');
        $inTwoDays = new DateTimeImmutable('2025-01-10 12:00:00');

        $this->assertEquals('vor 2 Stunden', DateHelper::humanDiff($twoHoursAgo, $now, 'de'));
        $this->assertEquals('in 2 Tagen', DateHelper::humanDiff($inTwoDays, $now, 'de'));
        $this->assertEquals('2 hours ago', DateHelper::humanDiff($twoHoursAgo, $now, 'en'));
    }
}
