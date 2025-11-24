<?php
/*
 * Created on   : Wed Nov 12 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderDefinition.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderFieldInterface;
use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderInterface;
use CommonToolkit\Enums\DATEV\V700\MetaHeaderField;
use InvalidArgumentException;

/**
 * Definition des DATEV-Metaheaders (Version 700).
 */
final class MetaHeaderDefinition implements MetaHeaderInterface {
    public function getVersion(): int {
        return 700;
    }

    public function getFieldEnum(): string {
        return MetaHeaderField::class;
    }

    public function getFields(): array {
        return MetaHeaderField::ordered();
    }

    public function getValidationPattern(MetaHeaderField $field): ?string {
        return $field->pattern();
    }

    public function getDefaultValue(MetaHeaderFieldInterface $field): mixed {
        if (!$field instanceof MetaHeaderField) {
            throw new InvalidArgumentException('Inkompatibles Feldobjekt übergeben.');
        }

        return match ($field) {
            MetaHeaderField::Kennzeichen          => 'EXTF',
            MetaHeaderField::Versionsnummer       => 700,
            MetaHeaderField::Formatkategorie      => 21,
            MetaHeaderField::Formatname           => 'Buchungsstapel',
            MetaHeaderField::Formatversion        => 13,
            MetaHeaderField::Festschreibung       => 0,
            MetaHeaderField::Waehrungskennzeichen => 'EUR',
            default                                    => null,
        };
    }
}
