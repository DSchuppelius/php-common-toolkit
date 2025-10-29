<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVHeaderField.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Abstracts\Common\CSVFieldAbstract;

class CSVHeaderField extends CSVFieldAbstract {
    public function getValue(): string {
        return strtoupper(parent::getValue());
    }
}