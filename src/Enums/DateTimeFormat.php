<?php
/*
 * Created on   : Thu Apr 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateFormat.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum DateTimeFormat: string {
    case DE = 'DE';
    case US = 'US';
    case ISO = 'ISO';
    case ISO8601 = 'ISO8601';
    case DE_SHORT = 'GERMANY_SHORT';
    case ISO_DATETIME = 'ISO_DATETIME';
    case MYSQL_DATETIME = 'MYSQL_DATETIME';

    /**
     * Gibt die entsprechende DateTimeFormatGroup für dieses Format zurück.
     *
     * @return DateTimeFormatGroup Die zugehörige Formatgruppe
     */
    public function getFormatGroup(): DateTimeFormatGroup {
        return match ($this) {
            self::DE,
            self::DE_SHORT => DateTimeFormatGroup::European,
            self::US => DateTimeFormatGroup::American,
            self::ISO,
            self::ISO8601,
            self::ISO_DATETIME,
            self::MYSQL_DATETIME => DateTimeFormatGroup::Asian, // ISO basiert auf asiatischem YYYY-MM-DD
        };
    }

    /**
     * Gibt das PHP DateTime-Format Pattern für dieses Format zurück.
     *
     * @param bool $withTime Ob Zeit enthalten sein soll
     * @return string Das PHP DateTime Pattern
     */
    public function getPattern(bool $withTime = false): string {
        return match ($this) {
            self::ISO => 'Y-m-d',
            self::DE => $withTime ? 'd.m.Y H:i' : 'd.m.Y',
            self::US => $withTime ? 'm/d/Y H:i' : 'm/d/Y',
            self::MYSQL_DATETIME => 'Y-m-d H:i:s',
            self::ISO_DATETIME,
            self::ISO8601 => 'Y-m-d\TH:i:s',
            self::DE_SHORT => 'd.m.y',
        };
    }

    /**
     * Erstellt DateTimeFormat aus DateTimeFormatGroup und Präferenz.
     *
     * @param DateTimeFormatGroup $group Die Formatgruppe
     * @return self Das entsprechende DateTimeFormat
     */
    public static function fromFormatGroup(DateTimeFormatGroup $group): self {
        return match ($group) {
            DateTimeFormatGroup::European,
            DateTimeFormatGroup::Mixed,
            DateTimeFormatGroup::Russian => self::DE,
            DateTimeFormatGroup::American => self::US,
            DateTimeFormatGroup::Asian => self::ISO,
        };
    }
}