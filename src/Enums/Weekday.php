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
    case Sunday    = 0;
    case Monday    = 1;
    case Tuesday   = 2;
    case Wednesday = 3;
    case Thursday  = 4;
    case Friday    = 5;
    case Saturday  = 6;

    public function label(): string {
        return match ($this) {
            self::Sunday    => 'Sonntag',
            self::Monday    => 'Montag',
            self::Tuesday   => 'Dienstag',
            self::Wednesday => 'Mittwoch',
            self::Thursday  => 'Donnerstag',
            self::Friday    => 'Freitag',
            self::Saturday  => 'Samstag',
        };
    }

    public static function fromDate(DateTimeInterface $date): self {
        return self::from((int) $date->format('w'));
    }
}
