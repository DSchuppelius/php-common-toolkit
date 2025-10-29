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

use CommonToolkit\Contracts\Abstracts\Common\CSVLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSVFieldInterface;

class CSVHeaderLine extends CSVLineAbstract {
    protected static function createField(string $rawValue, string $enclosure): CSVFieldInterface {
        return new CSVHeaderField($rawValue, $enclosure);
    }
}
