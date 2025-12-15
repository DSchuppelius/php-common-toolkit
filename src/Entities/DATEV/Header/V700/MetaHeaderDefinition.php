<?php
/*
 * Created on   : Mon Dec 01 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\MetaHeaderDefinitionAbstract;
use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderFieldInterface;
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Enums\DATEV\MetaFields\{AccountingPurpose, BookingType, Establishing, Mark};
use CommonToolkit\Enums\DATEV\MetaFields\Format\{Category, Version};
use CommonToolkit\Enums\DATEV\V700\MetaHeaderField;
use InvalidArgumentException;

final class MetaHeaderDefinition extends MetaHeaderDefinitionAbstract {
    protected const VERSION = 700;

    /**
     * Liefert den Enum-Typ, der die Meta-Header-Felder beschreibt.
     *
     * @return class-string<MetaHeaderField>
     */
    public function getFieldEnum(): string {
        return MetaHeaderField::class;
    }

    /**
     * Reihenfolge der Meta-Header-Felder wie von DATEV spezifiziert.
     *
     * @return MetaHeaderFieldInterface[]
     */
    public function getFields(): array {
        // MetaHeaderField::ordered() muss die Positionen 1–31 exakt abbilden.
        return MetaHeaderField::ordered();
    }

    /**
     * Liefert die fachlichen Default-Werte für den Metaheader.
     *
     * Enums werden hier auf ihre skalaren Werte (int/string) abgebildet, damit
     * CSV-Ausgabe und Regex-Validierung konsistent bleiben.
     */
    public function getDefaultValue(MetaHeaderFieldInterface $field): mixed {
        if (!$field instanceof MetaHeaderField) {
            throw new InvalidArgumentException('Inkompatibles Feldobjekt übergeben.');
        }

        // Default-Format für diese Definition (V700 BookingBatch)
        $category = Category::Buchungsstapel;

        return match ($field) {
            MetaHeaderField::Kennzeichen           => Mark::EXTF->value,
            MetaHeaderField::Versionsnummer        => $this->getVersion(),
            MetaHeaderField::Formatkategorie       => $category->value,
            MetaHeaderField::Formatname            => $category->nameValue(),
            MetaHeaderField::Formatversion         => Version::forCategory($category)->value,
            MetaHeaderField::Buchungstyp           => BookingType::FinancialAccounting->value,
            MetaHeaderField::Rechnungslegungszweck => AccountingPurpose::INDEPENDENT->value,
            MetaHeaderField::Festschreibung        => Establishing::NONE->value,
            MetaHeaderField::Waehrungskennzeichen  => CurrencyCode::Euro->value,
            default => null,
        };
    }
}
