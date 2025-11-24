<?php

declare(strict_types=1);

namespace CommonToolkit\Registries\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderInterface;
use CommonToolkit\Entities\DATEV\Header\V700\MetaHeaderDefinition as MetaHeaderDefinition700;
use RuntimeException;

final class HeaderRegistry {
    /** @var array<int, class-string<MetaHeaderInterface>> */
    private static array $definitions = [
        700 => MetaHeaderDefinition700::class,
        // später weitere Versionen hinzufügen
    ];

    public static function get(int $version): MetaHeaderInterface {
        $class = self::$definitions[$version] ?? null;
        if (!$class) {
            throw new RuntimeException("Keine DATEV-Headerdefinition für Version {$version} registriert.");
        }
        return new $class();
    }

    /** Automatische Erkennung aus Metaheader-Inhalt */
    public static function detectFromValues(array $values): ?MetaHeaderInterface {
        foreach ($values as $val) {
            if (preg_match('/^(700|800|510)$/', (string)$val, $m)) {
                return self::get((int)$m[1]);
            }
        }
        return null;
    }
}