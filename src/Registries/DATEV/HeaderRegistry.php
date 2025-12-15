<?php
/*
 * Created on   : Mon Dec 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HeaderRegistry.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Registries\DATEV;

use CommonToolkit\Contracts\Interfaces\DATEV\MetaHeaderInterface;
use CommonToolkit\Entities\Common\CSV\DataLine;
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

    /**
     * Automatische Erkennung aus dem rohen Werte-Array.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromValues(array $values): ?MetaHeaderInterface {
        // Versionsnummer muss an Position 1 stehen (DATEV-Standard)
        if (isset($values[1]) && preg_match('/^\d+$/', (string)$values[1])) {
            $version = (int)$values[1];
            if (isset(self::$definitions[$version])) {
                return self::get($version);
            }
        }

        return null;
    }

    /**
     * Automatische Erkennung direkt aus einer geparsten DataLine.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromDataLine(DataLine $dataLine): ?MetaHeaderInterface {
        $fields = $dataLine->getFields();

        // Versionsnummer an Position 1 prüfen (DATEV-Standard)
        if (isset($fields[1])) {
            $versionValue = $fields[1]->getValue();
            if (preg_match('/^\d+$/', $versionValue)) {
                $version = (int)$versionValue;
                if (isset(self::$definitions[$version])) {
                    return self::get($version);
                }
            }
        }

        return null;
    }

    /**
     * Registriert eine neue Header-Definition für eine Version.
     */
    public static function register(int $version, string $definitionClass): void {
        if (!is_subclass_of($definitionClass, MetaHeaderInterface::class)) {

            throw new RuntimeException("Definition class must implement MetaHeaderInterface");
        }
        self::$definitions[$version] = $definitionClass;
    }

    /**
     * Gibt alle registrierten Versionen zurück.
     */
    public static function getSupportedVersions(): array {
        return array_keys(self::$definitions);
    }
}