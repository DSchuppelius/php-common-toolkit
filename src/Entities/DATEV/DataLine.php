<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataLine.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV;

use CommonToolkit\Entities\Common\CSV\DataLine as CSVDataLine;

/**
 * Repräsentiert eine Datenzeile innerhalb einer DATEV-CSV-Datei.
 */
final class DataLine extends CSVDataLine {
    public function getFieldValue(string $name): ?string {
        return $this->getFieldByName($name)?->getValue();
    }

    public function getBetrag(): ?float {
        $raw = $this->getFieldValue('Umsatz (ohne Soll/Haben-Kennzeichen)');
        return $raw !== null ? (float) str_replace(',', '.', $raw) : null;
    }

    public function getKonto(): ?string {
        return $this->getFieldValue('Konto');
    }

    public function getGegenkonto(): ?string {
        return $this->getFieldValue('Gegenkonto (ohne BU-Schlüssel)');
    }
}
