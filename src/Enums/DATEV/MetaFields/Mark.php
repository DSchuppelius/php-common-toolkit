<?php
/*
 * Created on   : Mon Dec 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mark.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\DATEV\MetaFields;

enum Mark: string {
    case EXTF = 'EXTF'; // Export aus 3rd-Party App
    case DTVF = 'DTVF'; // Export aus DATEV App

    /**
     * DATEV-Metaheader Regex-Muster für dieses Enum.
     */
    public static function pattern(): string {
        return '^(EXTF|DTVF)$';
    }
}