<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddresses.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{Document, MetaHeaderLine};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV-Diverse Adressen-Dokument.
 * Spezielle Document-Klasse für Diverse Adressen-Format (Kategorie 48).
 */
final class VariousAddresses extends Document {
    public function __construct(
        ?MetaHeaderLine $metaHeader,
        ?HeaderLine $header,
        array $rows = []
    ) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Liefert die DATEV-Kategorie für diese Document-Art.
     */
    public function getCategory(): Category {
        return Category::DiverseAdressen;
    }

    /**
     * Validiert Diverse Adressen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 48) {
            throw new \RuntimeException('Ungültige Kategorie für Diverse Adressen-Dokument. Erwartet: 48');
        }
    }
}
