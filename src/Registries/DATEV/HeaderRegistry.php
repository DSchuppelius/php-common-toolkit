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

use CommonToolkit\Contracts\Interfaces\DATEV\{HeaderDefinitionInterface, MetaHeaderDefinitionInterface};
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use RuntimeException;

/**
 * Zentrale Registry für DATEV-Header-Definitionen.
 * Nutzt das VersionDiscovery-System zur automatischen Erkennung verfügbarer Versionen.
 * 
 * @see VersionDiscovery Für automatische Versionserkennung
 */
final class HeaderRegistry {
    /** @var array<int, MetaHeaderDefinitionInterface> */
    private static array $metaHeaderInstances = [];

    /** @var array<string, HeaderDefinitionInterface> */
    private static array $formatDefinitionInstances = [];


    /**
     * Liefert die MetaHeader-Definition für eine Version (dynamisch über Discovery).
     */
    public static function get(int $version): MetaHeaderDefinitionInterface {
        // Prüfe zuerst ob durch Discovery verfügbar
        if (!VersionDiscovery::isVersionSupported($version)) {
            throw new RuntimeException("DATEV-Version {$version} wird nicht unterstützt oder wurde nicht gefunden.");
        }

        // Singleton-Pattern für Instanzen
        if (!isset(self::$metaHeaderInstances[$version])) {
            $class = VersionDiscovery::getMetaHeaderClass($version);
            if (!$class) {
                throw new RuntimeException("Keine MetaHeader-Definition für Version {$version} gefunden.");
            }
            self::$metaHeaderInstances[$version] = new $class();
        }

        return self::$metaHeaderInstances[$version];
    }

    /**
     * Liefert die Format-spezifische Header-Definition für eine Kategorie und Version (dynamisch).
     */
    public static function getFormatDefinition(Category $category, int $version): HeaderDefinitionInterface {
        if (!VersionDiscovery::isFormatSupported($category, $version)) {
            throw new RuntimeException(
                "Format '{$category->nameValue()}' wird in Version {$version} nicht unterstützt."
            );
        }

        // Singleton-Pattern für Format-Definitionen
        $cacheKey = "{$version}_{$category->value}";
        if (!isset(self::$formatDefinitionInstances[$cacheKey])) {
            $class = VersionDiscovery::getFormatDefinition($category, $version);
            if (!$class) {
                throw new RuntimeException(
                    "Keine Format-Definition für '{$category->nameValue()}' Version {$version} gefunden."
                );
            }
            self::$formatDefinitionInstances[$cacheKey] = new $class();
        }

        return self::$formatDefinitionInstances[$cacheKey];
    }

    /**
     * Prüft ob eine Format/Version-Kombination unterstützt wird (dynamisch).
     */
    public static function isFormatSupported(Category $category, int $version): bool {
        return VersionDiscovery::isFormatSupported($category, $version);
    }

    /**
     * Automatische Erkennung aus dem rohen Werte-Array.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromValues(array $values): ?MetaHeaderDefinitionInterface {
        // Versionsnummer muss an Position 1 stehen (DATEV-Standard)
        if (isset($values[1]) && preg_match('/^\d+$/', (string)$values[1])) {
            $version = (int)$values[1];
            if (VersionDiscovery::isVersionSupported($version)) {
                return self::get($version);
            }
        }

        return null;
    }

    /**
     * Automatische Erkennung direkt aus einer geparsten DataLine.
     * Prüft die Versionsnummer an Position 1 (feste DATEV-Struktur).
     */
    public static function detectFromDataLine(DataLine $dataLine): ?MetaHeaderDefinitionInterface {
        $fields = $dataLine->getFields();

        // Versionsnummer an Position 1 prüfen (DATEV-Standard)
        if (isset($fields[1])) {
            $versionValue = $fields[1]->getValue();
            if (preg_match('/^\d+$/', $versionValue)) {
                $version = (int)$versionValue;

                // Direct fallback for version 700 if discovery fails
                if ($version === 700) {
                    try {
                        return self::get($version);
                    } catch (\Exception $e) {
                        // If discovery fails, still allow version 700
                        return null;
                    }
                }

                if (VersionDiscovery::isVersionSupported($version)) {
                    return self::get($version);
                }
            }
        }

        return null;
    }

    /**
     * Gibt alle unterstützten Versionen zurück (dynamisch über Discovery).
     * 
     * @return int[]
     */
    public static function getSupportedVersions(): array {
        return VersionDiscovery::getSupportedVersions();
    }

    /**
     * Gibt alle verfügbaren Versionen zurück (auch nicht unterstützte, dynamisch).
     * 
     * @return int[]
     */
    public static function getAvailableVersions(): array {
        return VersionDiscovery::getAvailableVersions();
    }

    /**
     * Gibt alle unterstützten Formate für eine Version zurück (dynamisch).
     * 
     * @return Category[]
     */
    public static function getSupportedFormats(int $version): array {
        return VersionDiscovery::getSupportedFormats($version);
    }

    /**
     * Gibt alle unterstützten Format-Versionen-Kombinationen zurück (dynamisch).
     * 
     * @return array<string, array{category: Category, version: int, supported: bool}>
     */
    public static function getAllFormatVersionCombinations(): array {
        $combinations = [];
        $availableVersions = VersionDiscovery::getAvailableVersions();

        foreach ($availableVersions as $version) {
            foreach (Category::cases() as $category) {
                $key = "{$category->nameValue()}_v{$version}";
                $combinations[$key] = [
                    'category' => $category,
                    'version' => $version,
                    'supported' => VersionDiscovery::isFormatSupported($category, $version),
                ];
            }
        }

        return $combinations;
    }

    /**
     * Gibt detaillierte Informationen über alle erkannten Versionen zurück.
     * 
     * @return array<int, array{version: int, path: string, metaHeaderClass: ?string, formatDefinitions: array<int, string>, formatCount: int}>
     */
    public static function getVersionDetails(): array {
        return VersionDiscovery::getVersionDetails();
    }

    /**
     * Prüft die Konsistenz einer Version.
     * 
     * @return array{valid: bool, missing: string[], issues: string[]}
     */
    public static function validateVersion(int $version): array {
        return VersionDiscovery::validateVersion($version);
    }

    /**
     * Erzwingt eine erneute Erkennung der verfügbaren Versionen.
     */
    public static function refresh(): void {
        VersionDiscovery::refresh();
        self::clearCache();
    }

    /**
     * Leert den Cache für Instanzen (für Tests).
     */
    public static function clearCache(): void {
        self::$metaHeaderInstances = [];
        self::$formatDefinitionInstances = [];
    }
}
