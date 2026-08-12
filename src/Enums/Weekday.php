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

use DateTimeImmutable;
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
     * @param bool $short Kurzformen statt voller Namen ausgeben.
     * @param bool $isoOrdered ISO-Reihenfolge Mo-So mit ISO-Schlüsseln (1-7) statt So-Sa (0-6).
     * @return array<array-key, string>
     */
    public static function toArray(bool $leadingZero = false, string $locale = 'en', bool $short = false, bool $isoOrdered = false): array {
        $days = self::cases();
        if ($isoOrdered) {
            usort($days, fn (self $a, self $b) => $a->getIsoWeekday() <=> $b->getIsoWeekday());
        }
        $weekdaysArray = [];
        foreach ($days as $weekday) {
            $value = $isoOrdered ? $weekday->getIsoWeekday() : $weekday->value;
            $key = $leadingZero ? str_pad((string) $value, 2, '0', STR_PAD_LEFT) : $value;
            $weekdaysArray[$key] = $short ? $weekday->getShortName($locale) : $weekday->getName($locale);
        }
        return $weekdaysArray;
    }

    public static function fromDate(DateTimeInterface $date): self {
        return self::from((int) $date->format('w'));
    }

    /**
     * Gibt den heutigen Wochentag zurück.
     */
    public static function today(): self {
        return self::fromDate(new DateTimeImmutable);
    }

    /**
     * Parst einen Wochentagsnamen (DE/EN/FR/IT/ES/NL/PT/PL) in verschiedenen Formaten.
     *
     * Unterstützt volle Namen und gängige Kurzformen, jeweils mit/ohne Punkt
     * und bei Akzenten zusätzlich in akzentfreier Schreibweise
     * (z. B. lunedì/lunedi, środa/sroda). Mehrdeutige Kürzel wie das polnische
     * zweibuchstabige "So" (= Samstag, kollidiert mit deutschem "So" = Sonntag)
     * werden bewusst nicht unterstützt.
     *
     * @param string $name Wochentagsname (Case-insensitive, mit/ohne Punkt).
     * @return self|null Der entsprechende Wochentag oder null wenn nicht erkannt.
     */
    public static function fromName(string $name): ?self {
        $name = mb_strtolower(trim($name, '. '));

        return match ($name) {
            // Montag
            'mo', 'mon', 'montag', 'monday', 'lundi', 'lun', 'lunedì', 'lunedi', 'lunes', 'ma', 'maandag', 'seg', 'segunda', 'segunda-feira', 'pon', 'poniedziałek', 'poniedzialek' => self::MONDAY,
            // Dienstag
            'di', 'dienstag', 'tue', 'tues', 'tuesday', 'mardi', 'mar', 'martedì', 'martedi', 'martes', 'dinsdag', 'ter', 'terça', 'terca', 'terça-feira', 'terca-feira', 'wt', 'wtorek' => self::TUESDAY,
            // Mittwoch
            'mi', 'mittwoch', 'wed', 'wednesday', 'mercredi', 'mer', 'mercoledì', 'mercoledi', 'miércoles', 'miercoles', 'mié', 'mie', 'wo', 'woensdag', 'qua', 'quarta', 'quarta-feira', 'śr', 'sr', 'środa', 'sroda' => self::WEDNESDAY,
            // Donnerstag
            'do', 'donnerstag', 'thu', 'thur', 'thurs', 'thursday', 'jeudi', 'jeu', 'giovedì', 'giovedi', 'gio', 'jueves', 'jue', 'donderdag', 'qui', 'quinta', 'quinta-feira', 'czw', 'czwartek' => self::THURSDAY,
            // Freitag
            'fr', 'freitag', 'fri', 'friday', 'vendredi', 'ven', 'venerdì', 'venerdi', 'viernes', 'vie', 'vr', 'vrijdag', 'sex', 'sexta', 'sexta-feira', 'pt', 'pią', 'pia', 'piątek', 'piatek' => self::FRIDAY,
            // Samstag
            'sa', 'samstag', 'sonnabend', 'sat', 'saturday', 'samedi', 'sam', 'sabato', 'sab', 'sábado', 'sabado', 'za', 'zaterdag', 'sob', 'sobota' => self::SATURDAY,
            // Sonntag
            'so', 'sonntag', 'sun', 'sunday', 'dimanche', 'dim', 'domenica', 'dom', 'domingo', 'zo', 'zondag', 'nie', 'ndz', 'niedziela' => self::SUNDAY,
            default => null,
        };
    }

    // ==================== ARITHMETIK ====================

    /**
     * Gibt den Folgetag zurück (Samstag → Sonntag).
     */
    public function next(): self {
        return $this->add(1);
    }

    /**
     * Gibt den Vortag zurück (Sonntag → Samstag).
     */
    public function previous(): self {
        return $this->add(-1);
    }

    /**
     * Addiert Tage mit Wochen-Überlauf (negative Werte erlaubt).
     */
    public function add(int $days): self {
        return self::from(((($this->value + $days) % 7) + 7) % 7);
    }

    /**
     * Anzahl Tage vorwärts bis zum anderen Wochentag (0-6).
     */
    public function daysUntil(self $other): int {
        return ($other->value - $this->value + 7) % 7;
    }

    /**
     * Gibt den nächsten Werktag zurück (Freitag/Samstag → Montag).
     */
    public function nextWorkday(): self {
        $day = $this->add(1);
        while (!$day->isWorkday()) {
            $day = $day->add(1);
        }
        return $day;
    }

    /**
     * Gibt den vorherigen Werktag zurück (Sonntag/Montag → Freitag).
     */
    public function previousWorkday(): self {
        $day = $this->add(-1);
        while (!$day->isWorkday()) {
            $day = $day->add(-1);
        }
        return $day;
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
