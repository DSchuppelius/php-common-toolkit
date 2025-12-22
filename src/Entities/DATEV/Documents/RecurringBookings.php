<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurringBookings.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * DATEV-Wiederkehrende Buchungen-Dokument.
 * Spezielle Document-Klasse für Wiederkehrende Buchungen-Format (Kategorie 65).
 */
final class RecurringBookings extends Document {
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
        return Category::WiederkehrendeBuchungen;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::WiederkehrendeBuchungen->nameValue();
    }

    /**
     * Validiert Wiederkehrende Buchungen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 65) {
            throw new \RuntimeException('Ungültige Kategorie für Wiederkehrende Buchungen-Dokument. Erwartet: 65');
        }
    }
}
