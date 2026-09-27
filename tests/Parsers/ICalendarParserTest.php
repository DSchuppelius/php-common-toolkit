<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ICalendarParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\ICalendar\{Document, Occurrence, RecurrenceRule};
use CommonToolkit\Parsers\ICalendarParser;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class ICalendarParserTest extends BaseTestCase {
    private DateTimeZone $berlin;

    protected function setUp(): void {
        parent::setUp();
        $this->berlin = new DateTimeZone('Europe/Berlin');
    }

    private function ics(string $events): string {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//DE\r\n{$events}END:VCALENDAR\r\n";
    }

    /**
     * @param list<Occurrence> $occurrences
     * @return list<string>
     */
    private function starts(array $occurrences): array {
        return array_map(static fn (Occurrence $o): string => $o->getStart()->format('Y-m-d H:i'), $occurrences);
    }

    public function test_reads_properties_with_unfolding_escapes_and_parameters(): void {
        $document = ICalendarParser::fromString($this->ics(
            "BEGIN:VEVENT\r\nUID:a-1\r\nSUMMARY:Baustelle\\, Nord\r\nDESCRIPTION:Zeile 1\\nZeile\r\n  2\r\n"
            . "DTSTART;TZID=\"W. Europe Standard Time\":20260915T080000\r\nDURATION:PT2H30M\r\n"
            . "ORGANIZER;CN=\"Chef: Anna\":mailto:anna@example.org\r\nATTENDEE;CN=Bob:MAILTO:bob@example.org\r\n"
            . "CATEGORIES:Arbeit,Kunde A\r\nCATEGORIES:Arbeit\r\nTRANSP:TRANSPARENT\r\nLOCATION:Köln\r\n"
            . "DIES IST KEINE GUELTIGE ZEILE\r\nBEGIN:VALARM\r\nSUMMARY:Erinnerung\r\nEND:VALARM\r\nEND:VEVENT\r\n"
        ));
        $event = $document->getEvents()[0];

        $this->assertSame('Baustelle, Nord', $event->getSummary());
        $this->assertSame("Zeile 1\nZeile 2", $event->getDescription());
        $this->assertSame('2026-09-15 08:00 Europe/Berlin', $event->getStart($this->berlin)?->format('Y-m-d H:i e'));
        $this->assertSame('10:30', $event->getEnd($this->berlin)?->format('H:i'));
        $this->assertSame('anna@example.org', $event->getOrganizer());
        $this->assertSame(['bob@example.org'], $event->getAttendees());
        $this->assertSame(['Arbeit', 'Kunde A'], $event->getCategories());
        $this->assertTrue($event->isTransparent());
        $this->assertFalse($event->isAllDay());
        $this->assertSame('Köln', $event->getLocation());
    }

    public function test_utc_floating_and_all_day_values(): void {
        $document = ICalendarParser::fromString($this->ics(
            "BEGIN:VEVENT\nUID:u\nDTSTART:20260915T060000Z\nDTEND:20260915T070000Z\nEND:VEVENT\n"
            . "BEGIN:VEVENT\nUID:f\nDTSTART:20260915T080000\nEND:VEVENT\n"
            . "BEGIN:VEVENT\nUID:d\nDTSTART;VALUE=DATE:20260915\nDTEND;VALUE=DATE:20260916\nEND:VEVENT\n"
        ));
        [$utc, $floating, $allDay] = $document->getEvents();

        $this->assertSame('08:00', $utc->getStart($this->berlin)?->setTimezone($this->berlin)->format('H:i'));
        $this->assertSame('08:00 Europe/Berlin', $floating->getStart($this->berlin)?->format('H:i e'));
        $this->assertTrue($allDay->isAllDay());
        $this->assertFalse($allDay->endHasTime());
        $this->assertSame('2026-09-15', $allDay->getStart($this->berlin)?->format('Y-m-d'));
    }

    public function test_weekly_series_with_exdate_and_override_keeps_wall_clock_over_dst(): void {
        $document = ICalendarParser::fromString($this->ics(
            "BEGIN:VEVENT\nUID:s\nSUMMARY:Jour fixe\nDTSTART;TZID=Europe/Berlin:20261014T090000\nDTEND;TZID=Europe/Berlin:20261014T100000\n"
            . "RRULE:FREQ=WEEKLY;BYDAY=WE;COUNT=5\nEXDATE;TZID=Europe/Berlin:20261021T090000\nEND:VEVENT\n"
            . "BEGIN:VEVENT\nUID:s\nSUMMARY:Jour fixe (verschoben)\nRECURRENCE-ID;TZID=Europe/Berlin:20261028T090000\n"
            . "DTSTART;TZID=Europe/Berlin:20261029T140000\nDTEND;TZID=Europe/Berlin:20261029T150000\nEND:VEVENT\n"
        ));
        $series = $document->getSeries();
        $this->assertCount(1, $series);

        $occurrences = Document::expand($series[0], new DateTimeImmutable('2026-10-01', $this->berlin), new DateTimeImmutable('2026-12-31', $this->berlin), $this->berlin);

        $this->assertSame(['2026-10-14 09:00', '2026-10-29 14:00', '2026-11-04 09:00', '2026-11-11 09:00'], $this->starts($occurrences));
        $this->assertSame('Jour fixe (verschoben)', $occurrences[1]->getEvent()->getSummary());
        $this->assertSame('2026-10-28 09:00', $occurrences[1]->getRecurrenceStart()->format('Y-m-d H:i'));
        $this->assertSame('10:00', $occurrences[2]->getEnd()?->format('H:i'));
        $this->assertSame('+01:00', $occurrences[2]->getStart()->format('P'));
    }

    public function test_window_limits_the_expansion(): void {
        $document = ICalendarParser::fromString($this->ics("BEGIN:VEVENT\nUID:d\nDTSTART:20260901T070000\nRRULE:FREQ=DAILY;INTERVAL=2\nEND:VEVENT\n"));
        $occurrences = Document::expand($document->getSeries()[0], new DateTimeImmutable('2026-09-05', $this->berlin), new DateTimeImmutable('2026-09-10', $this->berlin), $this->berlin);

        $this->assertSame(['2026-09-05 07:00', '2026-09-07 07:00', '2026-09-09 07:00'], $this->starts($occurrences));
    }

    public function test_monthly_and_yearly_rules(): void {
        $start = new DateTimeImmutable('2026-01-30 10:00', $this->berlin);
        $end = new DateTimeImmutable('2026-12-31', $this->berlin);
        $fmt = static fn (array $dates): array => array_map(static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'), $dates);

        $this->assertSame(['2026-01-30', '2026-02-27', '2026-03-27'], $fmt(RecurrenceRule::fromString('FREQ=MONTHLY;BYDAY=-1FR;COUNT=3')->occurrences($start, $end)));
        $this->assertSame(['2026-01-30', '2026-02-27', '2026-03-31'], $fmt(RecurrenceRule::fromString('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1;COUNT=3')->occurrences($start, $end)));
        $this->assertSame(['2026-01-30', '2026-03-30', '2026-04-30'], $fmt(RecurrenceRule::fromString('FREQ=MONTHLY;BYMONTHDAY=30;UNTIL=20260501T000000Z')->occurrences($start, $end)));
        $this->assertSame(['2026-01-30', '2026-05-10'], $fmt(RecurrenceRule::fromString('FREQ=YEARLY;BYMONTH=5;BYDAY=2SU')->occurrences($start, $end)));
        $this->assertSame(['2026-01-30', '2026-02-02', '2026-02-04'], $fmt(RecurrenceRule::fromString('FREQ=WEEKLY;BYDAY=MO,WE;COUNT=3')->occurrences($start, $end)));
    }

    public function test_rejects_data_without_calendar(): void {
        $this->expectException(RuntimeException::class);
        ICalendarParser::fromString("BEGIN:VEVENT\nUID:x\nEND:VEVENT\n");
    }
}
