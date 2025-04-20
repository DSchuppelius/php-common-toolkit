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

enum DateFormat: string {
    case DE = 'DE';
    case US = 'US';
    case ISO = 'ISO';
    case ISO8601 = 'ISO8601';
    case DE_SHORT = 'GERMANY_SHORT';
    case ISO_DATETIME = 'ISO_DATETIME';
    case MYSQL_DATETIME = 'MYSQL_DATETIME';
}