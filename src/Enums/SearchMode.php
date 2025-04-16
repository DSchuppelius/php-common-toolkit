<?php
/*
 * Created on   : Wed Apr 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SearchMode.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum SearchMode: string {
    case EXACT = 'exact';
    case CONTAINS = 'contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case REGEX = 'regex';
}
