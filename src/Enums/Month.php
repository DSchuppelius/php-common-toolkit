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

    public function getName(string $locale = 'en'): string {
        return match ($locale) {
            'de' => match ($this) {
                self::JANUARY => 'Januar',
                self::FEBRUARY => 'Februar',
                self::MARCH => 'März',
                self::APRIL => 'April',
                self::MAY => 'Mai',
                self::JUNE => 'Juni',
                self::JULY => 'Juli',
                self::AUGUST => 'August',
                self::SEPTEMBER => 'September',
                self::OCTOBER => 'Oktober',
                self::NOVEMBER => 'November',
                self::DECEMBER => 'Dezember',
            },
            default => match ($this) {
                self::JANUARY => 'January',
                self::FEBRUARY => 'February',
                self::MARCH => 'March',
                self::APRIL => 'April',
                self::MAY => 'May',
                self::JUNE => 'June',
                self::JULY => 'July',
                self::AUGUST => 'August',
                self::SEPTEMBER => 'September',
                self::OCTOBER => 'October',
                self::NOVEMBER => 'November',
                self::DECEMBER => 'December',
            },
        };
    }

    public static function toArray(bool $leadingZero = false, string $locale = 'en'): array {
        $monthsArray = [];
        foreach (self::cases() as $month) {
            $key = $leadingZero ? str_pad((string)$month->value, 2, '0', STR_PAD_LEFT) : $month->value;
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
}