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
}