<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DebitorenKreditoren.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{Document, MetaHeaderLine, FormatInfo};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV-Debitoren/Kreditoren-Dokument.
 * Spezielle Document-Klasse für Debitoren/Kreditoren-Format (Kategorie 16).
 */
final class DebitorenKreditoren extends Document {
    public function __construct(
        ?MetaHeaderLine $metaHeader,
        ?HeaderLine $header,
        array $rows = []
    ) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::DebitorenKreditoren->nameValue();
    }

    /**
     * Gibt die Format-Informationen zurück.
     */
    public function getFormatInfo(): FormatInfo {
        return new FormatInfo(Category::DebitorenKreditoren, 700);
    }

    /**
     * Validiert, dass es sich um ein Debitoren/Kreditoren-Format handelt.
     */
    public function validate(): void {
        parent::validate();

        // Zusätzliche Validierung für Debitoren/Kreditoren
        if ($this->getMetaHeader() !== null) {
            $metaFields = $this->getMetaHeader()->getFields();
            if (count($metaFields) > 2 && $metaFields[2]->getValue() !== '16') {
                throw new \RuntimeException('Document ist kein Debitoren/Kreditoren-Format');
            }
        }
    }
}
