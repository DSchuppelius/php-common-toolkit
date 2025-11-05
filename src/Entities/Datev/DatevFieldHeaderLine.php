<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevFieldHeaderLine.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CKonverter\Core\Entities\Datev;

use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;

/**
 * Zweite Kopfzeile der DATEV-CSV-Datei.
 * Beschreibt die eigentlichen Spaltennamen der Buchungsdaten.
 */
final class DatevFieldHeaderLine extends CSVHeaderLine {
    public function hasField(string $name): bool {
        return $this->getFieldByName($name) !== null;
    }

    public function toAssoc(): array {
        return array_map(fn($f) => $f->getValue(), $this->getFields());
    }
}
