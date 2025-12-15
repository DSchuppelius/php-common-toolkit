<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Buchungsstapel.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{Document, MetaHeaderLine, FormatInfo};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV-Buchungsstapel-Dokument.
 * Spezielle Document-Klasse für Buchungsstapel-Format (Kategorie 21).
 */
final class Buchungsstapel extends Document {
    public function __construct(?MetaHeaderLine $metaHeader,        ?HeaderLine $header,        array $rows = []) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::Buchungsstapel->nameValue();
    }

    /**
     * Gibt die Format-Informationen zurück.
     */
    public function getFormatInfo(): FormatInfo {
        return new FormatInfo(Category::Buchungsstapel, 700);
    }

    /**
     * Validiert, dass es sich um ein Buchungsstapel-Format handelt.
     */
    public function validate(): void {
        parent::validate();

        // Zusätzliche Validierung für Buchungsstapel
        if ($this->getMetaHeader() !== null) {
            $metaFields = $this->getMetaHeader()->getFields();
            if (count($metaFields) > 2 && $metaFields[2]->getValue() !== '21') {
                throw new \RuntimeException('Document ist kein Buchungsstapel-Format');
            }
        }
    }
}