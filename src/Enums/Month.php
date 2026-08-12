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

use DateTimeInterface;

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
     * Gibt den lokalisierten Monatsnamen zurück.
     *
     * Unterstützte Sprachen: de, en, fr, it, es, nl, pt, pl.
     * Akzeptiert auch volle Locales wie "de_DE" oder "fr-FR";
     * unbekannte Sprachen fallen auf Englisch zurück.
     */
    public function getName(string $locale = 'en'): string {
        $language = strtolower(explode('_', str_replace('-', '_', $locale), 2)[0]);
        return (self::NAMES[$language] ?? self::NAMES['en'])[$this->value];
    }

    /**
     * @return array<array-key, string>
     */
    public static function toArray(bool $leadingZero = false, string $locale = 'en'): array {
        $monthsArray = [];
        foreach (self::cases() as $month) {
            $key = $leadingZero ? str_pad((string) $month->value, 2, '0', STR_PAD_LEFT) : $month->value;
            $monthsArray[$key] = $month->getName($locale);
        }
        return $monthsArray;
    }

    public static function fromDate(DateTimeInterface $date): self {
        return self::from((int) $date->format('n'));
    }

    /**
     * Erstellt Month aus englischem 3-Buchstaben-Kürzel (JAN, FEB, MAR, ...).
     *
     * @param string $abbreviation Das 3-Buchstaben-Kürzel (case-insensitive).
     * @return self|null Der entsprechende Monat oder null wenn nicht erkannt.
     */
    public static function fromAbbreviation(string $abbreviation): ?self {
        return match (strtoupper(trim($abbreviation))) {
            'JAN' => self::JANUARY,
            'FEB' => self::FEBRUARY,
            'MAR' => self::MARCH,
            'APR' => self::APRIL,
            'MAY' => self::MAY,
            'JUN' => self::JUNE,
            'JUL' => self::JULY,
            'AUG' => self::AUGUST,
            'SEP' => self::SEPTEMBER,
            'OCT' => self::OCTOBER,
            'NOV' => self::NOVEMBER,
            'DEC' => self::DECEMBER,
            default => null,
        };
    }

    /**
     * Gibt das 3-Buchstaben-Kürzel (englisch) zurück.
     */
    public function getAbbreviation(): string {
        return match ($this) {
            self::JANUARY => 'JAN',
            self::FEBRUARY => 'FEB',
            self::MARCH => 'MAR',
            self::APRIL => 'APR',
            self::MAY => 'MAY',
            self::JUNE => 'JUN',
            self::JULY => 'JUL',
            self::AUGUST => 'AUG',
            self::SEPTEMBER => 'SEP',
            self::OCTOBER => 'OCT',
            self::NOVEMBER => 'NOV',
            self::DECEMBER => 'DEC',
        };
    }

    /**
     * Gibt die Monatszahl als zweistelligen String zurück (01-12).
     */
    public function toTwoDigitString(): string {
        return str_pad((string) $this->value, 2, '0', STR_PAD_LEFT);
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
