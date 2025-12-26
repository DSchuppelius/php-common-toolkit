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

use CommonToolkit\Contracts\Abstracts\Common\CSV\LineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Enums\CountryCode;

/**
 * Einfache CSV-Datenzeile ohne Header-Wissen.
 * Alle Header-bezogenen Operationen werden über Document abgewickelt.
 */
class DataLine extends LineAbstract {
    public function __construct(
        array $fields,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ) {
        parent::__construct($fields, $delimiter, $enclosure);
    }

    protected static function createField(string $rawValue, string $enclosure): FieldInterface {
        return new DataField($rawValue, $enclosure, CountryCode::Germany);
    }
}
