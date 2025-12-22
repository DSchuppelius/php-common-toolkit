<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSV\LineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Enums\CountryCode;

class HeaderLine extends LineAbstract {
    protected static function createField(string $rawValue, string $enclosure): FieldInterface {
        return new HeaderField($rawValue, $enclosure, CountryCode::Germany);
    }
}
