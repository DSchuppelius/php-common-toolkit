<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateRange.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Immutabler Kalendertag-Bereich, an beiden Grenzen inklusiv: [from, to].
 *
 * Bildet Kalendertage ab, keine Zeitpunkte: Übergebene Zeitanteile werden auf
 * 00:00:00 normalisiert, alle Vergleiche laufen über das Kalenderdatum
 * (zeitzonenunabhängig deterministisch). `from` muss kleiner oder gleich `to`
 * sein; ein eintägiger Bereich ist gültig. Für Zeiträume mit Uhrzeit siehe
 * {@see DateTimeRange} (halboffen).
 *
 * Verwendet ausschließlich PHP-eigene DateTime-Typen — keine
 * Carbon-/Laravel-Abhängigkeit.
 *
 * @example
 * ```php
 * $july = DateRange::fromStrings('2026-07-01', '2026-07-31');
 * $july->contains(new DateTimeImmutable('2026-07-31')); // true (inklusiv)
 * $july->calendarDays();                                 // 31
 * $july->touches(DateRange::fromStrings('2026-08-01', '2026-08-31')); // true
 * ```
 */
final class DateRange implements JsonSerializable, Stringable {
    use ErrorLog;

    private readonly DateTimeImmutable $from;

    private readonly DateTimeImmutable $to;

    private function __construct(DateTimeImmutable $from, DateTimeImmutable $to) {
        $this->from = $from;
        $this->to = $to;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt einen Bereich aus zwei Kalendertagen (Zeitanteile werden
     * entfernt). Umgekehrte Grenzen werden abgelehnt, nicht vertauscht.
     *
     * @throws InvalidArgumentException Wenn $from nach $to liegt.
     */
    public static function between(DateTimeInterface $from, DateTimeInterface $to): self {
        $normalizedFrom = self::normalize($from);
        $normalizedTo = self::normalize($to);

        if (self::dateKey($normalizedFrom) > self::dateKey($normalizedTo)) {
            self::logErrorAndThrow(
                InvalidArgumentException::class,
                'Ungültiger Bereich: from (' . $normalizedFrom->format('Y-m-d') . ') liegt nach to (' . $normalizedTo->format('Y-m-d') . ').'
            );
        }

        return new self($normalizedFrom, $normalizedTo);
    }

    /**
     * Eintägiger Bereich.
     */
    public static function singleDay(DateTimeInterface $date): self {
        $normalized = self::normalize($date);

        return new self($normalized, $normalized);
    }

    /**
     * Erzeugt einen Bereich aus Datums-Strings (z.B. "2026-07-01").
     *
     * @param DateTimeZone|null $timezone Zeitzone für die Interpretation (null = Server-Standard).
     * @throws InvalidArgumentException Bei nicht deutbaren Datums-Strings.
     */
    public static function fromStrings(string $from, string $to, ?DateTimeZone $timezone = null): self {
        return self::between(self::parse($from, $timezone), self::parse($to, $timezone));
    }

    /**
     * Rekonstruiert einen Bereich aus seiner Array-Darstellung — Gegenstück zu
     * {@see jsonSerialize()}.
     *
     * @param array{from?: string|null, to?: string|null} $data
     * @throws InvalidArgumentException Wenn 'from' oder 'to' fehlen oder ungültig sind.
     */
    public static function fromArray(array $data): self {
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;

        if (!is_string($from) || $from === '' || !is_string($to) || $to === '') {
            self::logErrorAndThrow(InvalidArgumentException::class, "Array benötigt 'from' und 'to' als Datums-Strings.");
        }

        return self::fromStrings($from, $to);
    }

    // ========================================================================
    // Bereichslogik (kalenderbasiert)
    // ========================================================================

    /**
     * Liegt der Kalendertag des Datums im Bereich (beide Grenzen inklusive)?
     */
    public function contains(DateTimeInterface $date): bool {
        $key = self::dateKey(self::normalize($date));

        return $key >= self::dateKey($this->from) && $key <= self::dateKey($this->to);
    }

    /**
     * Teilen sich beide Bereiche mindestens einen Kalendertag?
     */
    public function overlaps(self $other): bool {
        return self::dateKey($this->from) <= self::dateKey($other->to)
            && self::dateKey($other->from) <= self::dateKey($this->to);
    }

    /**
     * Liegen die Bereiche direkt nebeneinander (lückenlos, aber ohne
     * gemeinsamen Tag)? Überlappung ist keine Berührung.
     */
    public function touches(self $other): bool {
        return self::dateKey($this->to->modify('+1 day')) === self::dateKey($other->from)
            || self::dateKey($other->to->modify('+1 day')) === self::dateKey($this->from);
    }

    /**
     * Schnittmenge beider Bereiche oder null, wenn sie keinen gemeinsamen
     * Kalendertag haben.
     */
    public function intersection(self $other): ?self {
        if (!$this->overlaps($other)) {
            return null;
        }

        $from = self::dateKey($this->from) >= self::dateKey($other->from) ? $this->from : $other->from;
        $to = self::dateKey($this->to) <= self::dateKey($other->to) ? $this->to : $other->to;

        return new self($from, $to);
    }

    /**
     * Hüllbereich beider Bereiche (inklusive einer eventuellen Lücke).
     */
    public function span(self $other): self {
        $from = self::dateKey($this->from) <= self::dateKey($other->from) ? $this->from : $other->from;
        $to = self::dateKey($this->to) >= self::dateKey($other->to) ? $this->to : $other->to;

        return new self($from, $to);
    }

    /**
     * Verschiebt den Bereich um ganze Kalendertage (negativ = rückwärts).
     */
    public function shiftDays(int $days): self {
        if ($days === 0) {
            return $this;
        }

        $modifier = sprintf('%+d days', $days);

        return new self($this->from->modify($modifier), $this->to->modify($modifier));
    }

    /**
     * Anzahl der Kalendertage im Bereich — beide Grenzen zählen mit
     * (01.07. bis 31.07. = 31 Tage).
     */
    public function calendarDays(): int {
        // UTC-verankert rechnen, damit Zeitzonen/DST die Tageszählung nie verfälschen.
        return self::utcDate($this->from)->diff(self::utcDate($this->to))->days + 1;
    }

    // ========================================================================
    // Zugriff / Vergleich / Serialisierung
    // ========================================================================

    public function getFrom(): DateTimeImmutable {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable {
        return $this->to;
    }

    /**
     * Fachliche Gleichheit: gleiche Kalendertage (Zeitzone unerheblich).
     */
    public function equals(self $other): bool {
        return self::dateKey($this->from) === self::dateKey($other->from)
            && self::dateKey($this->to) === self::dateKey($other->to);
    }

    /**
     * ISO-8601-Intervallnotation: "2026-07-01/2026-07-31".
     */
    public function __toString(): string {
        return $this->from->format('Y-m-d') . '/' . $this->to->format('Y-m-d');
    }

    /**
     * @return array{from: string, to: string}
     */
    public function jsonSerialize(): array {
        return [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Zeitanteile entfernen (00:00:00.000000), Zeitzone der Eingabe erhalten.
     */
    private static function normalize(DateTimeInterface $date): DateTimeImmutable {
        return DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
    }

    /**
     * Datums-String parsen, Fehler als InvalidArgumentException.
     */
    private static function parse(string $date, ?DateTimeZone $timezone): DateTimeImmutable {
        try {
            return new DateTimeImmutable($date, $timezone);
        } catch (Throwable $e) {
            return self::logErrorAndThrow(InvalidArgumentException::class, "Nicht deutbares Datum: '$date' ({$e->getMessage()})");
        }
    }

    /**
     * Kalendertag als sortierbarer Schlüssel (zeitzonenunabhängig, weil auf
     * dem lokalen Kalenderdatum der Instanz basierend).
     */
    private static function dateKey(DateTimeImmutable $date): string {
        return $date->format('Y-m-d');
    }

    /**
     * Kalenderdatum UTC-verankert (für exakte Tagesdifferenzen).
     */
    private static function utcDate(DateTimeImmutable $date): DateTimeImmutable {
        return new DateTimeImmutable($date->format('Y-m-d'), new DateTimeZone('UTC'));
    }
}
