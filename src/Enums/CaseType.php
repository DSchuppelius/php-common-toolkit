<?php
/*
 * Created on   : Thu Apr 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CaseType.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums;

enum CaseType: string {
    case LOWER = 'lower';          // klein + extras
    case UPPER = 'upper';          // groß + extras
    case CAMEL = 'camel';          // camelCase
    case TITLE = 'title';          // z. B. "Hallo Welt"
    case LOOSE_CAMEL = 'looseCamel'; // optionale Erweiterung
}