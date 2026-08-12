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
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;

    /**
     * Lokalisierte Wochentagsnamen, Schlüssel = Enum-Wert (0=So...6=Sa).
     * Schreibweise gemäß CLDR: de/en großgeschrieben, fr/it/es/nl/pt/pl kleingeschrieben.
     */
    private const NAMES = [
        'de' => [0 => 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
        'en' => [0 => 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'fr' => [0 => 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
        'it' => [0 => 'domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato'],
        'es' => [0 => 'domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
        'nl' => [0 => 'zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        'pt' => [0 => 'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'],
        'pl' => [0 => 'niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'],
    ];

    /**
     * Lokalisierte Kurzformen, Schlüssel = Enum-Wert (0=So...6=Sa).
     */
    private const SHORT_NAMES = [
        'de' => [0 => 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        'en' => [0 => 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'fr' => [0 => 'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam'],
        'it' => [0 => 'dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab'],
        'es' => [0 => 'dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'],
        'nl' => [0 => 'zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
        'pt' => [0 => 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'],
        'pl' => [0 => 'nie', 'pon', 'wt', 'śr', 'czw', 'pt', 'sob'],
    ];

    /**
     * Gibt den lokalisierten Wochentagsnamen zurück.
     *
     * Unterstützte Sprachen: de, en, fr, it, es, nl, pt, pl.
     * Akzeptiert auch volle Locales wie "de_DE" oder "fr-FR";
     * unbekannte Sprachen fallen auf Englisch zurück.
     */
    public function getName(string $locale = 'en'): string {
        return (self::NAMES[self::normalizeLocale($locale)] ?? self::NAMES['en'])[$this->value];
    }

    /**
     * Lokalisierte Kurzform (z. B. Mo/Mon/lun/seg).
     *
     * Unterstützte Sprachen und Locale-Behandlung wie bei getName().
     */
    public function getShortName(string $locale = 'en'): string {
        return (self::SHORT_NAMES[self::normalizeLocale($locale)] ?? self::SHORT_NAMES['en'])[$this->value];
    }

    /**
     * Reduziert eine Locale wie "de_DE" oder "fr-FR" auf den Sprachcode.
     */
    private static function normalizeLocale(string $locale): string {
        return strtolower(explode('_', str_replace('-', '_', $locale), 2)[0]);
    }

    /**
     * @return array<array-key, string>
     */
    public static function toArray(bool $leadingZero = false, string $locale = 'en'): array {
        $weekdaysArray = [];
        foreach (self::cases() as $weekday) {
            $key = $leadingZero ? str_pad((string) $weekday->value, 2, '0', STR_PAD_LEFT) : $weekday->value;
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
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
            self::SUNDAY => 7,
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
            self::MONDAY => 1,   // 2^0
            self::TUESDAY => 2,   // 2^1
            self::WEDNESDAY => 4,   // 2^2
            self::THURSDAY => 8,   // 2^3
            self::FRIDAY => 16,  // 2^4
            self::SATURDAY => 32,  // 2^5
            self::SUNDAY => 64,  // 2^6
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
        usort($days, fn (self $a, self $b) => $a->getIsoWeekday() <=> $b->getIsoWeekday());
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
        return implode(', ', array_map(fn (self $d) => $d->getShortName($locale), $days));
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
