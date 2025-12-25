<?php
/*
 * Created on   : Wed Dec 25 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataFieldAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Abstracts\Common\CSV;

use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Enums\CountryCode;

abstract class DataFieldAbstract extends FieldAbstract {
    protected ?FieldInterface $headerField = null;

    public function __construct(string $raw, string $enclosure = self::DEFAULT_ENCLOSURE, CountryCode $country = CountryCode::Germany, ?FieldInterface $headerField = null) {
        parent::__construct($raw, $enclosure, $country);
        $this->headerField = $headerField;
    }

    /**
     * Gibt das zugehörige HeaderField zurück.
     */
    public function getHeaderField(): ?FieldInterface {
        return $this->headerField;
    }

    /**
     * Setzt das zugehörige HeaderField.
     */
    public function setHeaderField(?FieldInterface $headerField): void {
        $this->headerField = $headerField;
    }

    /**
     * Gibt den Spaltennamen zurück, falls eine HeaderField-Referenz vorhanden ist.
     */
    public function getColumnName(): ?string {
        return $this->headerField?->getValue();
    }

    /**
     * Prüft ob ein HeaderField zugewiesen ist.
     */
    public function hasHeaderField(): bool {
        return $this->headerField !== null;
    }
}
