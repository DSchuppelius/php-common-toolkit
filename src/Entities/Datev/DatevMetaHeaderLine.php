<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevMetaHeaderLine.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CKonverter\Core\Entities\Datev;

use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;

/**
 * Erste Kopfzeile einer DATEV-CSV-Datei (Metadatenheader).
 * Enthält z. B. EXTF, Formatname, Version, Erzeugt am, Erzeugt durch.
 */
final class DatevMetaHeaderLine extends CSVHeaderLine {
    public function getFormatName(): ?string {
        return $this->getFieldByName('Formatname')?->getValue();
    }

    public function getFormatVersion(): ?string {
        return $this->getFieldByName('Formatversion')?->getValue();
    }

    public function getCreatedBy(): ?string {
        return $this->getFieldByName('Erzeugt durch')?->getValue();
    }

    public function toAssoc(): array {
        $assoc = [];
        foreach ($this->getFields() as $field) {
            $assoc[] = $field->getValue();
        }
        return $assoc;
    }
}
