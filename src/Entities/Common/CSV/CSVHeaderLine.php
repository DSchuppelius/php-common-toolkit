<?php

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSVLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSVFieldInterface;

class CSVHeaderLine extends CSVLineAbstract {
    protected static function createField(string $rawValue, string $enclosure): CSVFieldInterface {
        return new CSVHeaderField($rawValue, $enclosure);
    }
}