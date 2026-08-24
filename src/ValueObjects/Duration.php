<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Duration.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use DateInterval;
use DateTimeInterface;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Immutable, exakte Zeitdauer in ganzen Sekunden.
 *
 * Für Arbeits-/Projektzeiten, Abwesenheiten und Zeitsalden. Negative Dauern
 * sind zulässig (Saldo-Korrekturen). Bewusst reine Ganzzahlarithmetik ohne
 * Helper-Unterbau und ohne Mikrosekunden.
 *
 * ISO-8601-Ein-/Ausgabe akzeptiert Zeitanteile und Tage (1 Tag = fest
 * 86400 s); Jahre und Monate werden abgelehnt, weil sie keine feste
 * Sekundenlänge besitzen. {@see between()} rechnet instant-basiert und ist
 * damit DST-sicher.
 *
 * @example
 * ```php
 * $work = Duration::of(8, 30);
 * $work->toClock();          // "8:30"
 * $work->toIso8601();        // "PT8H30M"
 * $work->minus(Duration::ofHours(9))->toClock(); // "-0:30"
 *
 * DateTimeRange::between($start, $end)->duration(); // Duration
 * ```
 */
final class Duration implements JsonSerializable, Stringable {
    use ErrorLog;

    /** Gesamtdauer in Sekunden (kann negativ sein). */
    private readonly int $seconds;

    private function __construct(int $seconds) {
        $this->seconds = $seconds;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    public static function ofSeconds(int $seconds): self {
        return new self($seconds);
    }

    public static function ofMinutes(int $minutes): self {
        return new self($minutes * 60);
    }

    public static function ofHours(int $hours): self {
        return new self($hours * 3600);
    }

    /**
     * Dauer aus Stunden, Minuten und Sekunden. Alle Komponenten müssen
     * nicht-negativ sein — negative Dauern entstehen über {@see ofSeconds()},
     * {@see negated()} oder {@see between()}.
     *
     * @throws InvalidArgumentException Bei negativen Komponenten.
     */
    public static function of(int $hours, int $minutes = 0, int $seconds = 0): self {
        if ($hours < 0 || $minutes < 0 || $seconds < 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "of() verlangt nicht-negative Komponenten: $hours/$minutes/$seconds");
        }

        return new self($hours * 3600 + $minutes * 60 + $seconds);
    }

    /**
     * Parst eine ISO-8601-Dauer ("PT8H30M", "P1DT2H", "-PT15M").
     *
     * Tage zählen fest 86400 s. Jahre und Monate werden abgelehnt (keine
     * feste Sekundenlänge), ebenso Mikrosekunden-Anteile.
     *
     * @throws InvalidArgumentException Bei nicht deutbarer oder unzulässiger Dauer.
     */
    public static function fromIso8601(string $duration): self {
        $trimmed = trim($duration);
        $negative = str_starts_with($trimmed, '-');
        if ($negative) {
            $trimmed = substr($trimmed, 1);
        }

        try {
            $interval = new DateInterval($trimmed);
        } catch (Throwable $e) {
            return self::logErrorAndThrow(InvalidArgumentException::class, "Nicht deutbare ISO-8601-Dauer: '$duration' ({$e->getMessage()})");
        }

        if ($interval->y !== 0 || $interval->m !== 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Jahre und Monate haben keine feste Sekundenlänge: '$duration'");
        }
        if ($interval->f !== 0.0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Mikrosekunden werden nicht unterstützt: '$duration'");
        }

        $seconds = $interval->d * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;

        return new self($negative ? -$seconds : $seconds);
    }

    /**
     * Parst ein Uhrenformat: "H:MM:SS", "H:MM" oder "MM:SS".
     *
     * Zweiteilige Werte sind mehrdeutig — $twoPartsAreHoursMinutes steuert
     * die Deutung: true (Standard) liest "8:30" als Stunden:Minuten
     * (z. B. Kimai), false als Minuten:Sekunden (z. B. Toggl). Führende
     * Nullen und Stunden über 24 ("123:45:06") sind zulässig; die erste
     * Komponente ist unbegrenzt, die folgenden müssen zweistellig und < 60
     * sein. Ein führendes '-' wird wie bei {@see fromIso8601()} als negative
     * Dauer gedeutet — damit ist {@see toClock()} roundtrip-fähig.
     *
     * @throws InvalidArgumentException Bei nicht deutbarem Uhrenformat.
     */
    public static function fromClock(string $value, bool $twoPartsAreHoursMinutes = true): self {
        $trimmed = trim($value);
        $negative = str_starts_with($trimmed, '-');
        if ($negative) {
            $trimmed = substr($trimmed, 1);
        }

        if (preg_match('/^(\d+):(\d{2})(?::(\d{2}))?$/', $trimmed, $matches) !== 1) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Nicht deutbares Uhrenformat: '$value'");
        }

        $first = (int) $matches[1];
        $second = (int) $matches[2];
        // Die optionale Sekundengruppe steht am Muster-Ende — PHP lässt sie
        // bei Nichttreffer komplett weg, isset() genügt.
        $third = isset($matches[3]) ? (int) $matches[3] : null;

        if ($second > 59 || ($third !== null && $third > 59)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Uhrenformat mit Komponente ≥ 60: '$value'");
        }

        if ($third !== null) {
            $seconds = $first * 3600 + $second * 60 + $third;
        } elseif ($twoPartsAreHoursMinutes) {
            $seconds = $first * 3600 + $second * 60;
        } else {
            $seconds = $first * 60 + $second;
        }

        return new self($negative ? -$seconds : $seconds);
    }

    /**
     * Tatsächlich verstrichene Zeit zwischen zwei Zeitpunkten
     * (instant-basiert, DST-sicher). Negativ, wenn $end vor $start liegt.
     */
    public static function between(DateTimeInterface $start, DateTimeInterface $end): self {
        return new self($end->getTimestamp() - $start->getTimestamp());
    }

    /**
     * Summiert Dauern (leere Liste → 0).
     *
     * @param iterable<self> $durations
     */
    public static function sum(iterable $durations): self {
        $total = 0;
        foreach ($durations as $duration) {
            $total += $duration->seconds;
        }

        return new self($total);
    }

    public static function zero(): self {
        return new self(0);
    }

    // ========================================================================
    // Arithmetik (liefert stets eine neue Instanz)
    // ========================================================================

    public function plus(self $other): self {
        return new self($this->seconds + $other->seconds);
    }

    public function minus(self $other): self {
        return new self($this->seconds - $other->seconds);
    }

    public function times(int $factor): self {
        return new self($this->seconds * $factor);
    }

    public function negated(): self {
        return $this->seconds === 0 ? $this : new self(-$this->seconds);
    }

    public function abs(): self {
        return $this->seconds < 0 ? $this->negated() : $this;
    }

    // ========================================================================
    // Vergleich
    // ========================================================================

    /**
     * @return int -1, 0 oder 1.
     */
    public function compareTo(self $other): int {
        return $this->seconds <=> $other->seconds;
    }

    public function equals(self $other): bool {
        return $this->seconds === $other->seconds;
    }

    public function isZero(): bool {
        return $this->seconds === 0;
    }

    public function isPositive(): bool {
        return $this->seconds > 0;
    }

    public function isNegative(): bool {
        return $this->seconds < 0;
    }

    // ========================================================================
    // Zugriff / Zerlegung
    // ========================================================================

    public function getTotalSeconds(): int {
        return $this->seconds;
    }

    /**
     * Gesamtminuten, Richtung Null abgeschnitten (90:30 min → 90).
     */
    public function getTotalMinutes(): int {
        return intdiv($this->seconds, 60);
    }

    /**
     * Dezimalstunden ohne Rundung (90 min → 1.5, -15 min → -0.25) —
     * für Faktura-Mengen und Industriestunden-Anzeigen.
     */
    public function toDecimalHours(): float {
        return $this->seconds / 3600;
    }

    /**
     * Zerlegung in Stunden/Minuten/Sekunden — bei negativen Dauern tragen
     * alle Teile einheitlich das negative Vorzeichen.
     *
     * @return array{hours: int, minutes: int, seconds: int}
     */
    public function toParts(): array {
        $abs = abs($this->seconds);
        $sign = $this->seconds < 0 ? -1 : 1;

        return [
            'hours' => $sign * intdiv($abs, 3600),
            'minutes' => $sign * intdiv($abs % 3600, 60),
            'seconds' => $sign * ($abs % 60),
        ];
    }

    // ========================================================================
    // Formatierung / Serialisierung
    // ========================================================================

    /**
     * Uhrenformat ohne Tagesumbruch: "8:30", "-0:15", "129:05" — optional
     * mit Sekunden ("8:30:15").
     */
    public function toClock(bool $withSeconds = false): string {
        $abs = abs($this->seconds);
        $result = sprintf('%d:%02d', intdiv($abs, 3600), intdiv($abs % 3600, 60));

        if ($withSeconds) {
            $result .= sprintf(':%02d', $abs % 60);
        }

        return ($this->seconds < 0 ? '-' : '') . $result;
    }

    /**
     * ISO-8601-Dauer: "PT8H30M", "-PT15M", "PT0S". Es werden nur
     * Zeitanteile ausgegeben (Stunden dürfen 24 überschreiten).
     */
    public function toIso8601(): string {
        if ($this->seconds === 0) {
            return 'PT0S';
        }

        $parts = $this->toParts();
        $result = 'PT';
        if ($parts['hours'] !== 0) {
            $result .= abs($parts['hours']) . 'H';
        }
        if ($parts['minutes'] !== 0) {
            $result .= abs($parts['minutes']) . 'M';
        }
        if ($parts['seconds'] !== 0) {
            $result .= abs($parts['seconds']) . 'S';
        }

        return ($this->seconds < 0 ? '-' : '') . $result;
    }

    public function __toString(): string {
        return $this->toIso8601();
    }

    /**
     * @return array{seconds: int}
     */
    public function jsonSerialize(): array {
        return ['seconds' => $this->seconds];
    }
}
