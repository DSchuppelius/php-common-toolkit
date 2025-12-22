<?php
/*
 * Created on   : Sun Dec 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DateTimeFormatGroup.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum DateTimeFormatGroup: string {
    case American = 'american';
    case Asian    = 'asian';
    case European = 'european';
    case Mixed    = 'mixed';
    case Russian  = 'russian';

    /**
     * Gibt die DateTime-Formate für diese Gruppe zurück.
     *
     * @return array<string> Array von DateTime-Formaten
     */
    public function getFormats(): array {
        return match ($this) {
            self::American => ['m/d/Y', 'm/d/Y H:i:s', 'm-d-Y', 'm-d-Y H:i:s'],
            self::Asian    => ['Y/m/d', 'Y/m/d H:i:s', 'Y-m-d', 'Y-m-d H:i:s', 'd/m/Y'],
            self::European => ['d/m/Y', 'd/m/Y H:i:s', 'd-m-Y', 'd-m-Y H:i:s'],
            self::Mixed    => ['d/m/Y', 'd/m/Y H:i:s', 'd.m.Y', 'd.m.Y H:i:s'],
            self::Russian  => ['d.m.Y', 'd.m.Y H:i:s', 'd/m/Y', 'd-m-Y'],
        };
    }

    /**
     * Gibt eine Beschreibung der Formatgruppe zurück.
     *
     * @return string Beschreibung der Formatgruppe
     */
    public function getDescription(): string {
        return match ($this) {
            self::American => 'Amerikanisches Format (MM/DD/YYYY)',
            self::Asian    => 'Asiatisches Format (YYYY/MM/DD)',
            self::European => 'Europäisches Format (DD/MM/YYYY)',
            self::Mixed    => 'Gemischte Formate (DD/MM/YYYY + DD.MM.YYYY)',
            self::Russian  => 'Russisches Format (DD.MM.YYYY)',
        };
    }
}
