<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateTimeRange.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Immutables, halboffenes Zeitintervall: [start, end).
 *
 * Der Start ist enthalten, das Ende nicht — dadurch überlappen direkt
 * aufeinanderfolgende Buchungen nicht. `start` muss echt kleiner als `end`
 * sein; ein Intervall ohne Dauer ist ungültig. Alle Vergleiche erfolgen nach
 * dem tatsächlichen Zeitpunkt (UTC-Instant), nicht nach der formatierten
 * lokalen Uhrzeit. Für reine Kalendertag-Bereiche siehe {@see DateRange}
 * (beidseitig inklusiv).
 *
 * Verwendet ausschließlich PHP-eigene DateTime-Typen — keine
 * Carbon-/Laravel-Abhängigkeit.
 *
 * @example
 * ```php
 * $morning = DateTimeRange::between(
 *     new DateTimeImmutable('2026-07-01 08:00:00'),
 *     new DateTimeImmutable('2026-07-01 12:00:00')
 * );
 * $afternoon = DateTimeRange::between(
 *     new DateTimeImmutable('2026-07-01 12:00:00'),
 *     new DateTimeImmutable('2026-07-01 16:00:00')
 * );
 * $morning->overlaps($afternoon); // false — halboffen
 * $morning->touches($afternoon);  // true
 * ```
 */
final class DateTimeRange implements JsonSerializable, Stringable {
    use ErrorLog;

    private readonly DateTimeImmutable $start;

    private readonly DateTimeImmutable $end;

    private function __construct(DateTimeImmutable $start, DateTimeImmutable $end) {
        $this->start = $start;
        $this->end = $end;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt ein Intervall. `start` muss echt vor `end` liegen — ein
     * Intervall ohne Dauer ist ungültig.
     *
     * @throws InvalidArgumentException Wenn start >= end.
     */
    public static function between(DateTimeInterface $start, DateTimeInterface $end): self {
        $immutableStart = DateTimeImmutable::createFromInterface($start);
        $immutableEnd = DateTimeImmutable::createFromInterface($end);

        if ($immutableStart >= $immutableEnd) {
            self::logErrorAndThrow(
                InvalidArgumentException::class,
                'Ungültiges Intervall: start (' . $immutableStart->format(DateTimeInterface::ATOM) . ') liegt nicht vor end (' . $immutableEnd->format(DateTimeInterface::ATOM) . ').'
            );
        }

        return new self($immutableStart, $immutableEnd);
    }

    /**
     * Rekonstruiert ein Intervall aus seiner Array-Darstellung — Gegenstück zu
     * {@see jsonSerialize()}.
     *
     * @param array{start?: string|null, end?: string|null} $data
     * @throws InvalidArgumentException Wenn 'start' oder 'end' fehlen oder ungültig sind.
     */
    public static function fromArray(array $data): self {
        $start = $data['start'] ?? null;
        $end = $data['end'] ?? null;

        if (!is_string($start) || $start === '' || !is_string($end) || $end === '') {
            self::logErrorAndThrow(InvalidArgumentException::class, "Array benötigt 'start' und 'end' als Zeitpunkt-Strings.");
        }

        return self::between(self::parse($start), self::parse($end));
    }

    // ========================================================================
    // Intervalllogik (zeitpunktbasiert)
    // ========================================================================

    /**
     * Liegt der Zeitpunkt im Intervall? Start inklusive, Ende exklusiv.
     */
    public function contains(DateTimeInterface $instant): bool {
        $immutable = DateTimeImmutable::createFromInterface($instant);

        return $immutable >= $this->start && $immutable < $this->end;
    }

    /**
     * Teilen sich beide Intervalle eine echte Zeitspanne? Direkt
     * aufeinanderfolgende Intervalle ([8,12) und [12,16)) überlappen NICHT.
     */
    public function overlaps(self $other): bool {
        return $this->start < $other->end && $other->start < $this->end;
    }

    /**
     * Grenzen die Intervalle exakt aneinander (Ende == Start des anderen)?
     * Überlappung ist keine Berührung.
     */
    public function touches(self $other): bool {
        return $this->end == $other->start || $other->end == $this->start;
    }

    /**
     * Schnittmenge beider Intervalle oder null, wenn sie keine echte
     * Zeitspanne teilen.
     */
    public function intersection(self $other): ?self {
        if (!$this->overlaps($other)) {
            return null;
        }

        $start = $this->start >= $other->start ? $this->start : $other->start;
        $end = $this->end <= $other->end ? $this->end : $other->end;

        return new self($start, $end);
    }

    /**
     * Hüllintervall beider Intervalle (inklusive einer eventuellen Lücke).
     */
    public function span(self $other): self {
        $start = $this->start <= $other->start ? $this->start : $other->start;
        $end = $this->end >= $other->end ? $this->end : $other->end;

        return new self($start, $end);
    }

    /**
     * Tatsächlich verstrichene Dauer in Sekunden (DST-sicher, da
     * instant-basiert): ein 23-Stunden-DST-Tag ergibt 82800.
     */
    public function durationInSeconds(): int {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * Dauer des Intervalls als {@see Duration} (immer positiv, da
     * start < end garantiert ist).
     */
    public function duration(): Duration {
        return Duration::ofSeconds($this->durationInSeconds());
    }

    // ========================================================================
    // Zugriff / Vergleich / Serialisierung
    // ========================================================================

    public function getStart(): DateTimeImmutable {
        return $this->start;
    }

    public function getEnd(): DateTimeImmutable {
        return $this->end;
    }

    /**
     * Fachliche Gleichheit: gleiche Zeitpunkte. Unterschiedliche Offsets mit
     * demselben Instant sind gleich (12:00+02:00 == 11:00+01:00).
     */
    public function equals(self $other): bool {
        return $this->start == $other->start && $this->end == $other->end;
    }

    /**
     * ISO-8601-Intervallnotation mit Offset:
     * "2026-07-01T08:00:00+02:00/2026-07-01T09:00:00+02:00".
     */
    public function __toString(): string {
        return $this->start->format(DateTimeInterface::ATOM) . '/' . $this->end->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array{start: string, end: string}
     */
    public function jsonSerialize(): array {
        return [
            'start' => $this->start->format(DateTimeInterface::ATOM),
            'end' => $this->end->format(DateTimeInterface::ATOM),
        ];
    }

    // ========================================================================
    // Intern
    // ========================================================================

    /**
     * Zeitpunkt-String parsen, Fehler als InvalidArgumentException.
     */
    private static function parse(string $instant): DateTimeImmutable {
        try {
            return new DateTimeImmutable($instant);
        } catch (Throwable $e) {
            return self::logErrorAndThrow(InvalidArgumentException::class, "Nicht deutbarer Zeitpunkt: '$instant' ({$e->getMessage()})");
        }
    }
}
