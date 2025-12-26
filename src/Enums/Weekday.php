<?php
/*
 * Created on   : Tue Apr 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Weekday.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


declare(strict_types=1);

namespace CommonToolkit\Enums;

use DateTimeInterface;
use InvalidArgumentException;

enum Weekday: int {
    case SUNDAY    = 0;
    case MONDAY    = 1;
    case TUESDAY   = 2;
    case WEDNESDAY = 3;
    case THURSDAY  = 4;
    case FRIDAY    = 5;
    case SATURDAY  = 6;

    public function getName(string $locale = 'en'): string {
        return match ($locale) {
            'de' => match ($this) {
                self::MONDAY => 'Montag',
                self::TUESDAY => 'Dienstag',
                self::WEDNESDAY => 'Mittwoch',
                self::THURSDAY => 'Donnerstag',
                self::FRIDAY => 'Freitag',
                self::SATURDAY => 'Samstag',
                self::SUNDAY => 'Sonntag',
            },
            default => match ($this) {
                self::MONDAY => 'Monday',
                self::TUESDAY => 'Tuesday',
                self::WEDNESDAY => 'Wednesday',
                self::THURSDAY => 'Thursday',
                self::FRIDAY => 'Friday',
                self::SATURDAY => 'Saturday',
                self::SUNDAY => 'Sunday',
            },
        };
    }

    /**
     * Kurzform (Mo, Di, Mi, Do, Fr, Sa, So / Mon, Tue, Wed, Thu, Fri, Sat, Sun).
     */
    public function getShortName(string $locale = 'en'): string {
        return match ($locale) {
            'de' => match ($this) {
                self::MONDAY => 'Mo',
                self::TUESDAY => 'Di',
                self::WEDNESDAY => 'Mi',
                self::THURSDAY => 'Do',
                self::FRIDAY => 'Fr',
                self::SATURDAY => 'Sa',
                self::SUNDAY => 'So',
            },
            default => match ($this) {
                self::MONDAY => 'Mon',
                self::TUESDAY => 'Tue',
                self::WEDNESDAY => 'Wed',
                self::THURSDAY => 'Thu',
                self::FRIDAY => 'Fri',
                self::SATURDAY => 'Sat',
                self::SUNDAY => 'Sun',
            },
        };
    }

    public static function toArray(bool $leadingZero = false, string $locale = 'en'): array {
        $weekdaysArray = [];
        foreach (self::cases() as $weekday) {
            $key = $leadingZero ? str_pad((string)$weekday->value, 2, '0', STR_PAD_LEFT) : $weekday->value;
            $weekdaysArray[$key] = $weekday->getName($locale);
        }
        return $weekdaysArray;
    }

    public static function fromDate(DateTimeInterface $date): self {
        return self::from((int) $date->format('w'));
    }

    // ==================== ISO-8601 WOCHENTAG ====================

    /**
     * ISO-8601 Wochentag-Nummer (1=Mo...7=So).
     */
    public function getIsoWeekday(): int {
        return match ($this) {
            self::MONDAY    => 1,
            self::TUESDAY   => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY  => 4,
            self::FRIDAY    => 5,
            self::SATURDAY  => 6,
            self::SUNDAY    => 7,
        };
    }

    /**
     * Factory über ISO-8601 Wochentag (1=Mo...7=So).
     */
    public static function fromIsoWeekday(int $isoDay): self {
        return match ($isoDay) {
            1 => self::MONDAY,
            2 => self::TUESDAY,
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,
            5 => self::FRIDAY,
            6 => self::SATURDAY,
            7 => self::SUNDAY,
            default => throw new InvalidArgumentException("Ungültiger ISO-Wochentag: $isoDay"),
        };
    }

    // ==================== BITMASKE (DATEV) ====================

    /**
     * Gibt den Bitmaskenwert für diesen Wochentag zurück (2^(ISO-1)).
     * Montag=1, Dienstag=2, Mittwoch=4, Donnerstag=8, Freitag=16, Samstag=32, Sonntag=64
     */
    public function toBitmask(): int {
        return match ($this) {
            self::MONDAY    => 1,   // 2^0
            self::TUESDAY   => 2,   // 2^1
            self::WEDNESDAY => 4,   // 2^2
            self::THURSDAY  => 8,   // 2^3
            self::FRIDAY    => 16,  // 2^4
            self::SATURDAY  => 32,  // 2^5
            self::SUNDAY    => 64,  // 2^6
        };
    }

    /**
     * Prüft, ob dieser Wochentag in einer Bitmaske enthalten ist.
     */
    public function isInMask(int $mask): bool {
        return ($mask & $this->toBitmask()) === $this->toBitmask();
    }

    /**
     * Erstellt Bitmaske aus mehreren Wochentagen.
     *
     * @param Weekday ...$days
     */
    public static function createMask(self ...$days): int {
        $mask = 0;
        foreach ($days as $day) {
            $mask |= $day->toBitmask();
        }
        return $mask;
    }

    /**
     * Gibt alle Wochentage aus einer Bitmaske zurück.
     *
     * @return array<Weekday>
     */
    public static function fromMask(int $mask): array {
        $days = [];
        foreach (self::cases() as $day) {
            if ($day->isInMask($mask)) {
                $days[] = $day;
            }
        }
        // Sortiere nach ISO-Wochentag (Mo-So)
        usort($days, fn(self $a, self $b) => $a->getIsoWeekday() <=> $b->getIsoWeekday());
        return $days;
    }

    /**
     * Prüft, ob die Bitmaske nur Werktage enthält (Mo-Fr).
     */
    public static function isWorkdaysOnly(int $mask): bool {
        return ($mask & (self::SATURDAY->toBitmask() | self::SUNDAY->toBitmask())) === 0 && $mask > 0;
    }

    /**
     * Prüft, ob die Bitmaske Wochenendtage enthält (Sa, So).
     */
    public static function containsWeekend(int $mask): bool {
        return ($mask & (self::SATURDAY->toBitmask() | self::SUNDAY->toBitmask())) > 0;
    }

    /**
     * Gibt eine Bitmaske für alle Werktage zurück (Mo-Fr).
     */
    public static function workdaysMask(): int {
        return self::MONDAY->toBitmask() | self::TUESDAY->toBitmask() | self::WEDNESDAY->toBitmask()
            | self::THURSDAY->toBitmask() | self::FRIDAY->toBitmask();
    }

    /**
     * Gibt eine Bitmaske für das Wochenende zurück (Sa, So).
     */
    public static function weekendMask(): int {
        return self::SATURDAY->toBitmask() | self::SUNDAY->toBitmask();
    }

    /**
     * Gibt eine Bitmaske für alle Tage zurück.
     */
    public static function allDaysMask(): int {
        return 127; // 1+2+4+8+16+32+64
    }

    /**
     * Formatiert eine Bitmaske als lesbaren String.
     */
    public static function formatMask(int $mask, string $locale = 'de'): string {
        $days = self::fromMask($mask);
        if (empty($days)) {
            return $locale === 'de' ? 'Keine Tage' : 'No days';
        }
        return implode(', ', array_map(fn(self $d) => $d->getShortName($locale), $days));
    }

    /**
     * Prüft, ob dieser Tag ein Werktag ist (Mo-Fr).
     */
    public function isWorkday(): bool {
        return $this !== self::SATURDAY && $this !== self::SUNDAY;
    }

    /**
     * Prüft, ob dieser Tag ein Wochenendtag ist (Sa, So).
     */
    public function isWeekend(): bool {
        return $this === self::SATURDAY || $this === self::SUNDAY;
    }
}
