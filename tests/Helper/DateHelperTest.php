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

use CommonToolkit\Enums\DateTimeFormat;
use CommonToolkit\Enums\Month;
use CommonToolkit\Enums\Weekday;
use CommonToolkit\Enums\CountryCode;
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
        $format = null;
        $this->assertTrue(DateHelper::isDate("2024-01-01", $format));
        $this->assertEquals(DateTimeFormat::ISO, $format);
        $this->assertFalse(DateHelper::isDate("29.02.2025 01:02", $format));
        $this->assertNull($format);
        $this->assertTrue(DateHelper::isDate("20250101", $format));
        $this->assertEquals(DateTimeFormat::ISO, $format);
        $this->assertTrue(DateHelper::isDate("01.01.2024", $format));
        $this->assertEquals(DateTimeFormat::DE, $format);
        $this->assertTrue(DateHelper::isDate("12/13/2024", $format));
        $this->assertEquals(DateTimeFormat::US, $format);
        $this->assertTrue(DateHelper::isDate("12/13/2024 01:02", $format));
        $this->assertEquals(DateTimeFormat::US, $format);
        $this->assertTrue(DateHelper::isDate("12/12/2024", $format));
        $this->assertEquals(DateTimeFormat::DE, $format);
        $this->assertFalse(DateHelper::isDate("invalid-date", $format));
        $this->assertNull($format);
    }

    public function testIsValidDate() {
        $this->assertTrue(DateHelper::isValidDate('2024-02-29')); // Schaltjahr
        $this->assertFalse(DateHelper::isValidDate('2024-02-31')); // Ungültiger Tag
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
        $this->assertEquals("2024-01-01", DateHelper::formatDate("01.01.2024", DateTimeFormat::ISO));
        $this->assertEquals("2024-01-01", DateHelper::formatDate("01.01.2024 01:02", DateTimeFormat::ISO));
        $this->assertEquals("2024-01-01", DateHelper::formatDate("01.01.2024 01:02", DateTimeFormat::ISO, DateTimeFormat::DE, true));
        $this->assertEquals("2024-01-01T01:02:00", DateHelper::formatDate("01.01.2024 01:02", DateTimeFormat::ISO_DATETIME, DateTimeFormat::DE, true));
        $this->assertEquals("2024-01-01", DateHelper::germanToIso("01.01.2024 01:02:03"));
        $this->assertFalse(DateHelper::germanToIso("12/13/2024"));
    }

    public function testIsoToGerman(): void {
        $this->assertEquals("01.01.2024", DateHelper::formatDate("2024-01-01", DateTimeFormat::DE));
        $this->assertEquals("01.01.2024", DateHelper::formatDate("2024-01-01 01:02", DateTimeFormat::DE));
        $this->assertEquals("01.01.2024 01:02", DateHelper::formatDate("2024-01-01T01:02:03", DateTimeFormat::DE, DateTimeFormat::DE, true));
        $this->assertEquals("01.01.2024 01:02", DateHelper::isoToGerman("2024-01-01T01:02:03", true));
        $this->assertEquals("01.01.2024", DateHelper::isoToGerman("2024-01-01T01:02:03", false));
        $this->assertEquals("01.01.2024 00:00", DateHelper::isoToGerman("2024-01-01", true));
        $this->assertFalse(DateHelper::isoToGerman("01.02.2024 01:02", true));
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

    public function testGetMonthAndWeekdayEnums() {
        $date = new DateTime('2024-04-16');
        $this->assertEquals(Month::APRIL, DateHelper::getMonth($date));
        $this->assertEquals(Weekday::TUESDAY, DateHelper::getWeekday($date));
    }

    public function testGetLocalizedMonthName() {
        $date = new DateTime('2024-04-16');
        $this->assertEquals('April', DateHelper::getLocalizedMonthName($date, 'de'));
    }

    // === Neue Tests für verschobene DateTime-Funktionen ===

    public function testParseDateTime(): void {
        // ISO Format
        $result = DateHelper::parseDateTime('2024-12-22');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-22', $result->format('Y-m-d'));

        // Deutsche Formate
        $result = DateHelper::parseDateTime('22.12.2024');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-22', $result->format('Y-m-d'));

        $result = DateHelper::parseDateTime('22.12.2024 15:30:45');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-22 15:30:45', $result->format('Y-m-d H:i:s'));

        // Unix Timestamp
        $result = DateHelper::parseDateTime('1734800123');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);

        // Ungültiges Format
        $result = DateHelper::parseDateTime('invalid-date');
        $this->assertNull($result);
    }

    public function testParseDateTimeWithCountryCode(): void {
        // Deutschland: dd/mm/yyyy
        $result = DateHelper::parseDateTime('22/12/2024', CountryCode::Germany);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-22', $result->format('Y-m-d'));

        // USA: mm/dd/yyyy  
        $result = DateHelper::parseDateTime('12/22/2024', CountryCode::UnitedStatesOfAmerica);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-22', $result->format('Y-m-d'));
    }

    public function testDetectDateTimeFormat(): void {
        // Standard Formate
        $this->assertEquals('Y-m-d', DateHelper::detectDateTimeFormat('2024-12-22'));
        $this->assertEquals('Y-m-d H:i:s', DateHelper::detectDateTimeFormat('2024-12-22 15:30:45'));
        $this->assertEquals('d.m.Y', DateHelper::detectDateTimeFormat('22.12.2024'));
        $this->assertEquals('d.m.Y H:i:s', DateHelper::detectDateTimeFormat('22.12.2024 15:30:45'));

        // Unix Timestamp
        $this->assertEquals('U', DateHelper::detectDateTimeFormat('1734800123'));

        // Unbekannt
        $this->assertNull(DateHelper::detectDateTimeFormat('invalid-date'));
    }

    public function testDetectDateTimeFormatWithCountryCode(): void {
        // Deutschland: d/m/Y interpretiert als europäisch
        $format = DateHelper::detectDateTimeFormat('22/12/2024', CountryCode::Germany);
        $this->assertEquals('d/m/Y', $format);

        // USA: m/d/Y interpretiert als amerikanisch
        $format = DateHelper::detectDateTimeFormat('12/22/2024', CountryCode::UnitedStatesOfAmerica);
        $this->assertEquals('m/d/Y', $format);
    }

    public function testIsDateTimeString(): void {
        // Gültige DateTime-Strings
        $this->assertTrue(DateHelper::isDateTime('2024-12-22'));
        $this->assertTrue(DateHelper::isDateTime('22.12.2024'));
        $this->assertTrue(DateHelper::isDateTime('22.12.2024 15:30:45'));
        $this->assertTrue(DateHelper::isDateTime('1734800123'));

        // Mit spezifischem Format
        $this->assertTrue(DateHelper::isDateTime('22.12.2024', 'd.m.Y'));
        $this->assertFalse(DateHelper::isDateTime('2024-12-22', 'd.m.Y'));

        // Ungültige DateTime-Strings
        $this->assertFalse(DateHelper::isDateTime('invalid-date'));
        $this->assertFalse(DateHelper::isDateTime(''));
    }

    public function testIsDateTimeWithCountryCode(): void {
        // Deutschland
        $this->assertTrue(DateHelper::isDateTime('22/12/2024', null, CountryCode::Germany));

        // USA
        $this->assertTrue(DateHelper::isDateTime('12/22/2024', null, CountryCode::UnitedStatesOfAmerica));
    }
}
