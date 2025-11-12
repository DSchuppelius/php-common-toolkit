<?php
/*
 * Created on   : Wed Nov 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevMetaHeaderDefinition.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Datev\Header\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\DatevMetaHeaderFieldInterface;
use CommonToolkit\Contracts\Interfaces\DATEV\DatevMetaHeaderInterface;
use CommonToolkit\Enums\DATEV\V700\DatevMetaHeaderField;
use InvalidArgumentException;

/**
 * Definition des DATEV-Metaheaders (Version 700).
 */
final class DatevMetaHeaderDefinition implements DatevMetaHeaderInterface {
    public function getVersion(): int {
        return 700;
    }

    public function getFieldEnum(): string {
        return DatevMetaHeaderField::class;
    }

    public function getFields(): array {
        return DatevMetaHeaderField::ordered();
    }

    public function getValidationPattern(DatevMetaHeaderField $field): ?string {
        return $field->pattern();
    }

    public function getDefaultValue(DatevMetaHeaderFieldInterface $field): mixed {
        if (!$field instanceof DatevMetaHeaderField) {
            throw new InvalidArgumentException('Inkompatibles Feldobjekt übergeben.');
        }

        return match ($field) {
            DatevMetaHeaderField::Kennzeichen          => 'EXTF',
            DatevMetaHeaderField::Versionsnummer       => 700,
            DatevMetaHeaderField::Formatkategorie      => 21,
            DatevMetaHeaderField::Formatname           => 'Buchungsstapel',
            DatevMetaHeaderField::Formatversion        => 13,
            DatevMetaHeaderField::Festschreibung       => 0,
            DatevMetaHeaderField::Waehrungskennzeichen => 'EUR',
            default                                    => null,
        };
    }
}
