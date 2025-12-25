<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDataLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSV\DataLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSV\HeaderLineInterface;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Enums\CountryCode;

class DataLine extends DataLineAbstract {
    public function __construct(
        array $fields,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE,
        ?HeaderLineInterface $headerLine = null
    ) {
        parent::__construct($fields, $delimiter, $enclosure, $headerLine);
    }

    protected static function createField(string $rawValue, string $enclosure): FieldInterface {
        return new DataField($rawValue, $enclosure, CountryCode::Germany);
    }
}
