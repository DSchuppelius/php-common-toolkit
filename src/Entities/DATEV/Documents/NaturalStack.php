<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NaturalStack.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{Document, MetaHeaderLine};
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV-Natural-Stapel-Dokument.
 * Spezielle Document-Klasse für Natural-Stapel-Format (Kategorie 66).
 * Verwendet für Land-/Forstwirtschaft.
 */
final class NaturalStack extends Document {
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
        return Category::NaturalStapel;
    }

    /**
     * Validiert Natural-Stapel-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 66) {
            throw new \RuntimeException('Ungültige Kategorie für Natural-Stapel-Dokument. Erwartet: 66');
        }
    }

    /**
     * Prüft, ob eine Bewegungsart gültig ist.
     */
    public function validateMovementType(int $type): bool {
        $validTypes = [2, 21, 24, 25, 26, 27, 28, 29];
        return in_array($type, $validTypes);
    }
}
