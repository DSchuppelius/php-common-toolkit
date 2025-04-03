<?php
/*
 * Created on   : Wed Apr 02 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\Data\DateHelper;
use Tests\Contracts\BaseTestCase;

class DateHelperTest extends BaseTestCase {
    public function testGetLastDay(): void {
        $this->assertEquals(31, DateHelper::getLastDay(2024, 1));
        $this->assertEquals(29, DateHelper::getLastDay(2024, 2)); // Schaltjahr
        $this->assertEquals(30, DateHelper::getLastDay(2024, 6));
    }

    public function testIsLeapYear(): void {
        $this->assertTrue(DateHelper::isLeapYear(2024));
        $this->assertFalse(DateHelper::isLeapYear(2023));
    }

    public function testIsDate(): void {
        $format = '';
        $this->assertTrue(DateHelper::isDate("2024-01-01", $format));
        $this->assertEquals("ISO", $format);
        $this->assertTrue(DateHelper::isDate("01.01.2024", $format));
        $this->assertEquals("DE", $format);
        $this->assertFalse(DateHelper::isDate("invalid-date", $format));
    }

    public function testFixDate(): void {
        $this->assertEquals("01.01.2024", DateHelper::fixDate("1.1.2024"));
    }

    public function testParseFlexible(): void {
        $this->assertInstanceOf(DateTimeImmutable::class, DateHelper::parseFlexible("01.01.2024"));
        $this->assertNull(DateHelper::parseFlexible("invalid"));
    }

    public function testAddSubtractDays(): void {
        $date = new DateTimeImmutable('2024-01-01');
        $this->assertEquals('2024-01-06', DateHelper::addDays($date, 5)->format('Y-m-d'));
        $this->assertEquals('2023-12-27', DateHelper::subtractDays($date, 5)->format('Y-m-d'));
    }

    public function testIsWeekend(): void {
        $this->assertTrue(DateHelper::isWeekend(new DateTimeImmutable('2024-03-30'))); // Samstag
        $this->assertFalse(DateHelper::isWeekend(new DateTimeImmutable('2024-03-27'))); // Mittwoch
    }

    public function testGetDayOfWeek(): void {
        $this->assertEquals("Monday", DateHelper::getDayOfWeek(new DateTimeImmutable('2024-04-01')));
    }

    public function testDiffInDays(): void {
        $start = new DateTimeImmutable('2024-04-01');
        $end = new DateTimeImmutable('2024-04-06');
        $this->assertEquals(5, DateHelper::diffInDays($start, $end));
    }

    public function testIsFuturePastToday(): void {
        $today = new DateTimeImmutable();
        $past = $today->sub(new DateInterval('P1D'));
        $future = $today->add(new DateInterval('P1D'));

        $this->assertTrue(DateHelper::isFuture($future));
        $this->assertTrue(DateHelper::isPast($past));
        $this->assertTrue(DateHelper::isToday($today));
    }

    public function testGermanToIso(): void {
        $this->assertEquals("2024-01-01", DateHelper::germanToIso("01.01.2024"));
    }

    public function testIsoToGerman(): void {
        $this->assertEquals("01.01.2024", DateHelper::isoToGerman("2024-01-01"));
    }

    public function testAddToDate(): void {
        $this->assertEquals("2024-02-01", DateHelper::addToDate("2024-01-01", 0, 1, 0));
    }

    public function testDiffDetailed(): void {
        $start = new DateTimeImmutable('2024-01-01');
        $end = new DateTimeImmutable('2025-03-01');
        $diff = DateHelper::diffDetailed($start, $end);

        $this->assertEquals(1, $diff['years']);
        $this->assertEquals(2, $diff['months']);
        $this->assertEquals(0, $diff['days']);
        $this->assertEquals(425, $diff['total_days']);
        $this->assertEquals(60, $diff['weeks']);
    }

    public function testIsBetween(): void {
        $start = new DateTimeImmutable('2024-01-01');
        $end = new DateTimeImmutable('2024-12-31');
        $this->assertTrue(DateHelper::isBetween(new DateTimeImmutable('2024-06-15'), $start, $end));
        $this->assertFalse(DateHelper::isBetween(new DateTimeImmutable('2023-12-31'), $start, $end));
    }
}
