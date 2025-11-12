<?php

declare(strict_types=1);

namespace CommonToolkit\Registries;

use CommonToolkit\Contracts\Interfaces\DATEV\DatevMetaHeaderInterface;
use CommonToolkit\Entities\Datev\Header\V700\DatevMetaHeaderDefinition as DatevMetaHeaderDefinition700;
use RuntimeException;

final class DatevHeaderRegistry {
    /** @var array<int, class-string<DatevMetaHeaderInterface>> */
    private static array $definitions = [
        700 => DatevMetaHeaderDefinition700::class,
        // später weitere Versionen hinzufügen
    ];

    public static function get(int $version): DatevMetaHeaderInterface {
        $class = self::$definitions[$version] ?? null;
        if (!$class) {
            throw new RuntimeException("Keine DATEV-Headerdefinition für Version {$version} registriert.");
        }
        return new $class();
    }

    /** Automatische Erkennung aus Metaheader-Inhalt */
    public static function detectFromValues(array $values): ?DatevMetaHeaderInterface {
        foreach ($values as $val) {
            if (preg_match('/^(700|800|510)$/', (string)$val, $m)) {
                return self::get((int)$m[1]);
            }
        }
        return null;
    }
}
