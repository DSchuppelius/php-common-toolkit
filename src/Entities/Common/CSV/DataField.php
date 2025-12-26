<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDataField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSV\FieldAbstract;
use CommonToolkit\Enums\CountryCode;

class DataField extends FieldAbstract {
    public function __construct(string $raw, string $enclosure = self::DEFAULT_ENCLOSURE, CountryCode $country = CountryCode::Germany) {
        parent::__construct($raw, $enclosure, $country);
    }
}
