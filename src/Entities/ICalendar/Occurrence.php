<?php
/*
 * Created on   : Mon Sep 28 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Occurrence.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\ICalendar;

use DateTimeImmutable;

/**
 * One instance of a (possibly recurring) event: the effective event (an
 * override with RECURRENCE-ID replaces the series instance), its start/end and
 * the original series start that identifies the instance.
 */
final class Occurrence {
    public function __construct(
        private readonly Event $event,
        private readonly DateTimeImmutable $start,
        private readonly ?DateTimeImmutable $end,
        private readonly DateTimeImmutable $recurrenceStart,
    ) {}

    public function getEvent(): Event {
        return $this->event;
    }

    public function getStart(): DateTimeImmutable {
        return $this->start;
    }

    public function getEnd(): ?DateTimeImmutable {
        return $this->end;
    }

    /** Series start of this instance (equals RECURRENCE-ID of an override). */
    public function getRecurrenceStart(): DateTimeImmutable {
        return $this->recurrenceStart;
    }
}
