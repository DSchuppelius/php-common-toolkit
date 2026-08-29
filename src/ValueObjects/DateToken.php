<?php
/*
 * Created on   : Sat Aug 29 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateToken.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use DateTimeImmutable;

/**
 * Eine Datumsangabe in einer Textzeile. Jahr kann fehlen (TT.MM., TT/MM) und
 * wird bei der Auflösung aus dem Kontext ergänzt.
 */
final class DateToken {
    public function __construct(
        public readonly string $raw,
        /** Kennung der Schreibweise: iso, dmy-dash, dmy-slash, dmy, dmy2, d-month-y, d-mon-y2, dm, dm-slash. */
        public readonly string $form,
        public readonly int $day,
        public readonly int $month,
        public readonly ?int $year,
        /** Zeichenposition des ersten Zeichens. */
        public readonly int $start,
        /** Zeichenposition hinter dem letzten Zeichen. */
        public readonly int $end,
    ) {}

    public function hasYear(): bool {
        return $this->year !== null;
    }

    /**
     * Datum mit dem angegebenen Ersatzjahr, wenn die Schreibweise keins trägt;
     * null bei ungültigem Kalenderdatum.
     */
    public function resolve(?int $fallbackYear): ?DateTimeImmutable {
        $year = $this->year ?? $fallbackYear;
        if ($year === null || !checkdate($this->month, $this->day, $year)) {
            return null;
        }
        return (new DateTimeImmutable)->setDate($year, $this->month, $this->day)->setTime(0, 0);
    }
}
