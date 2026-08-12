<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Month.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

enum Month: int {
    case JANUARY = 1;
    case FEBRUARY = 2;
    case MARCH = 3;
    case APRIL = 4;
    case MAY = 5;
    case JUNE = 6;
    case JULY = 7;
    case AUGUST = 8;
    case SEPTEMBER = 9;
    case OCTOBER = 10;
    case NOVEMBER = 11;
    case DECEMBER = 12;

    /**
     * Lokalisierte Monatsnamen (Schreibweise gemäß CLDR: de/en großgeschrieben,
     * fr/it/es/nl/pt/pl kleingeschrieben).
     */
    private const NAMES = [
        'de' => [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'fr' => [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        'it' => [1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'],
        'es' => [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
        'nl' => [1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        'pt' => [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'],
        'pl' => [1 => 'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'],
    ];

    /**
     * Lokalisierte Kurzformen (ohne Punkt), Schlüssel = Monatszahl.
     */
    private const SHORT_NAMES = [
        'de' => [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        'en' => [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'fr' => [1 => 'janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'],
        'it' => [1 => 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'],
        'es' => [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
        'nl' => [1 => 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        'pt' => [1 => 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'],
        'pl' => [1 => 'sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'],
    ];

    /**
     * Gibt den lokalisierten Monatsnamen zurück.
     *
     * Unterstützte Sprachen: de, en, fr, it, es, nl, pt, pl.
     * Akzeptiert auch volle Locales wie "de_DE" oder "fr-FR";
     * unbekannte Sprachen fallen auf Englisch zurück.
     */
    public function getName(string $locale = 'en'): string {
        return (self::NAMES[self::normalizeLocale($locale)] ?? self::NAMES['en'])[$this->value];
    }

    /**
     * Lokalisierte Kurzform ohne Punkt (z. B. Jan/janv/gen/sty).
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
     * @return array<array-key, string>
     */
    public static function toArray(bool $leadingZero = false, string $locale = 'en', bool $short = false): array {
        $monthsArray = [];
        foreach (self::cases() as $month) {
            $key = $leadingZero ? str_pad((string) $month->value, 2, '0', STR_PAD_LEFT) : $month->value;
            $monthsArray[$key] = $short ? $month->getShortName($locale) : $month->getName($locale);
        }
        return $monthsArray;
    }

    public static function fromDate(DateTimeInterface $date): self {
        return self::from((int) $date->format('n'));
    }

    /**
     * Gibt den Monat des heutigen Datums zurück.
     */
    public static function current(): self {
        return self::fromDate(new DateTimeImmutable);
    }

    /**
     * Gibt die Monatszahl als zweistelligen String zurück (01-12).
     */
    public function toTwoDigitString(): string {
        return str_pad((string) $this->value, 2, '0', STR_PAD_LEFT);
    }

    // ==================== QUARTAL ====================

    /**
     * Gibt das Quartal dieses Monats zurück (1-4).
     */
    public function getQuarter(): int {
        return intdiv($this->value - 1, 3) + 1;
    }

    /**
     * Prüft, ob dieser Monat ein Quartal eröffnet (Jan, Apr, Jul, Okt).
     */
    public function isQuarterStart(): bool {
        return $this->value % 3 === 1;
    }

    /**
     * Prüft, ob dieser Monat ein Quartal abschließt (Mär, Jun, Sep, Dez).
     */
    public function isQuarterEnd(): bool {
        return $this->value % 3 === 0;
    }

    /**
     * Gibt die drei Monate eines Quartals zurück.
     *
     * @param int $quarter Quartal (1-4).
     * @return array{self, self, self}
     */
    public static function fromQuarter(int $quarter): array {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException("Ungültiges Quartal: $quarter");
        }
        $first = ($quarter - 1) * 3 + 1;
        return [self::from($first), self::from($first + 1), self::from($first + 2)];
    }

    // ==================== ARITHMETIK ====================

    /**
     * Gibt den Folgemonat zurück (Dezember → Januar).
     */
    public function next(): self {
        return $this->add(1);
    }

    /**
     * Gibt den Vormonat zurück (Januar → Dezember).
     */
    public function previous(): self {
        return $this->add(-1);
    }

    /**
     * Addiert Monate mit Jahres-Überlauf (negative Werte erlaubt).
     */
    public function add(int $months): self {
        return self::from(((($this->value - 1 + $months) % 12) + 12) % 12 + 1);
    }

    /**
     * Anzahl Monate vorwärts bis zum anderen Monat (0-11).
     */
    public function distanceTo(self $other): int {
        return ($other->value - $this->value + 12) % 12;
    }

    /**
     * Erzeugt ein Datum in diesem Monat (00:00 Uhr).
     *
     * @param int $year Jahr.
     * @param int $day Tag im Monat (Standard: 1).
     */
    public function toDate(int $year, int $day = 1): DateTimeImmutable {
        if (!checkdate($this->value, $day, $year)) {
            throw new InvalidArgumentException("Ungültiges Datum: $year-{$this->value}-$day");
        }
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $year, $this->value, $day));
    }

    /**
     * Parst einen Monatsnamen (DE/EN/FR/IT/ES/NL/PT/PL) in verschiedenen Formaten.
     *
     * Unterstützt volle Namen und gängige Abkürzungen, jeweils mit/ohne Punkt
     * und bei Akzenten zusätzlich in akzentfreier Schreibweise
     * (z. B. März/Maerz, février/fevrier, styczeń/styczen).
     *
     * @param string $name Monatsname (Case-insensitive, mit/ohne Punkt).
     * @return self|null Der entsprechende Monat oder null wenn nicht erkannt.
     */
    public static function fromName(string $name): ?self {
        $name = mb_strtolower(trim($name, '. '));

        return match ($name) {
            // Januar
            'jan', 'januar', 'january', 'januari', 'janvier', 'janv', 'gennaio', 'gen', 'enero', 'ene', 'janeiro', 'styczeń', 'styczen', 'sty' => self::JANUARY,
            // Februar
            'feb', 'februar', 'february', 'februari', 'février', 'fevrier', 'févr', 'fevr', 'febbraio', 'febrero', 'fevereiro', 'fev', 'luty', 'lut' => self::FEBRUARY,
            // März
            'mär', 'mar', 'märz', 'maerz', 'march', 'mrt', 'maart', 'mars', 'marzo', 'março', 'marco', 'marzec' => self::MARCH,
            // April
            'apr', 'april', 'avril', 'avr', 'aprile', 'abril', 'abr', 'kwiecień', 'kwiecien', 'kwi' => self::APRIL,
            // Mai
            'mai', 'may', 'mei', 'maggio', 'mag', 'mayo', 'maio', 'maj' => self::MAY,
            // Juni
            'jun', 'juni', 'june', 'juin', 'giugno', 'giu', 'junio', 'junho', 'czerwiec', 'cze' => self::JUNE,
            // Juli
            'jul', 'juli', 'july', 'juillet', 'juil', 'luglio', 'lug', 'julio', 'julho', 'lipiec', 'lip' => self::JULY,
            // August
            'aug', 'august', 'augustus', 'août', 'aout', 'agosto', 'ago', 'sierpień', 'sierpien', 'sie' => self::AUGUST,
            // September
            'sep', 'sept', 'september', 'septembre', 'settembre', 'set', 'septiembre', 'setiembre', 'setembro', 'wrzesień', 'wrzesien', 'wrz' => self::SEPTEMBER,
            // Oktober
            'okt', 'oct', 'oktober', 'october', 'octobre', 'ottobre', 'ott', 'octubre', 'outubro', 'out', 'październik', 'pazdziernik', 'paź', 'paz' => self::OCTOBER,
            // November
            'nov', 'november', 'novembre', 'noviembre', 'novembro', 'listopad', 'lis' => self::NOVEMBER,
            // Dezember
            'dez', 'dec', 'dezember', 'december', 'décembre', 'decembre', 'déc', 'dicembre', 'dic', 'diciembre', 'dezembro', 'grudzień', 'grudzien', 'gru' => self::DECEMBER,
            default => null,
        };
    }
}
