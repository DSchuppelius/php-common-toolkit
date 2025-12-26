<?php
/*
 * Created on   : Mon Dec 21 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VersionDiscovery.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Registries\DATEV;

use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Contracts\Interfaces\DATEV\{FieldHeaderInterface, MetaHeaderDefinitionInterface};

/**
 * Automatische Erkennung verfügbarer DATEV-Versionen aus den Enum-Definitionen.
 * Durchsucht die HeaderFields-Verzeichnisse nach verfügbaren Versionen und deren Enums.
 * 
 * Die Enums implementieren FieldHeaderInterface und liefern über getCategory() und getVersion()
 * die notwendigen Informationen für die dynamische Format-Erkennung.
 */
final class VersionDiscovery {
    private const ENUM_BASE_PATH = __DIR__ . '/../../Enums/DATEV/HeaderFields';
    private const HEADER_BASE_PATH = __DIR__ . '/../../Entities/DATEV/Header';
    private const VERSION_PATTERN = '/^V(\d+)$/';

    /** @var array<int, array{version: int, path: string, metaHeaderClass: ?string, formatEnums: array<int, class-string<FieldHeaderInterface>>}> */
    private static array $discoveredVersions = [];

    /** @var bool */
    private static bool $discovered = false;

    /**
     * Führt die Erkennung verfügbarer Versionen durch.
     */
    public static function discover(): void {
        if (self::$discovered) {
            return;
        }

        self::$discoveredVersions = [];

        if (!is_dir(self::ENUM_BASE_PATH)) {
            self::$discovered = true;
            return;
        }

        $directories = scandir(self::ENUM_BASE_PATH);
        if (!$directories) {
            self::$discovered = true;
            return;
        }

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $fullPath = self::ENUM_BASE_PATH . '/' . $dir;
            if (!is_dir($fullPath)) {
                continue;
            }

            // Prüfe ob es ein Versionsverzeichnis ist (VXX Format)
            if (preg_match(self::VERSION_PATTERN, $dir, $matches)) {
                $version = (int)$matches[1];
                $versionInfo = self::analyzeVersion($version, $fullPath);
                if ($versionInfo) {
                    self::$discoveredVersions[$version] = $versionInfo;
                }
            }
        }

        self::$discovered = true;
    }

    /**
     * Analysiert eine spezifische Version und ihre verfügbaren Enums.
     * 
     * @param int $version Die Versionsnummer
     * @param string $enumPath Pfad zum Enum-Versionsverzeichnis
     * @return array{version: int, path: string, metaHeaderClass: ?string, formatEnums: array<int, class-string<FieldHeaderInterface>>}|null
     */
    private static function analyzeVersion(int $version, string $enumPath): ?array {
        $versionInfo = [
            'version' => $version,
            'path' => $enumPath,
            'metaHeaderClass' => null,
            'formatEnums' => [],
        ];

        // Prüfe auf MetaHeaderDefinition im Header-Verzeichnis (weiterhin benötigt)
        $headerPath = self::HEADER_BASE_PATH . "/V{$version}";
        $metaHeaderFile = $headerPath . '/MetaHeaderDefinition.php';
        if (file_exists($metaHeaderFile)) {
            $metaHeaderClass = "CommonToolkit\\Entities\\DATEV\\Header\\V{$version}\\MetaHeaderDefinition";
            if (class_exists($metaHeaderClass) && is_subclass_of($metaHeaderClass, MetaHeaderDefinitionInterface::class)) {
                $versionInfo['metaHeaderClass'] = $metaHeaderClass;
            }
        }

        // Durchsuche nach HeaderField-Enums
        $files = scandir($enumPath);
        if ($files) {
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php' || $file === 'MetaHeaderField.php') {
                    continue;
                }

                $className = pathinfo($file, PATHINFO_FILENAME);
                $fullClassName = "CommonToolkit\\Enums\\DATEV\\HeaderFields\\V{$version}\\{$className}";

                // Prüfe ob es ein Enum ist, das FieldHeaderInterface implementiert
                if (enum_exists($fullClassName) && is_subclass_of($fullClassName, FieldHeaderInterface::class)) {
                    // Hole die Kategorie direkt vom Enum
                    /** @var class-string<FieldHeaderInterface> $fullClassName */
                    $category = $fullClassName::getCategory();
                    $versionInfo['formatEnums'][$category->value] = $fullClassName;
                }
            }
        }

        // Nur Versionen zurückgeben, die zumindest eine MetaHeaderDefinition haben
        return $versionInfo['metaHeaderClass'] ? $versionInfo : null;
    }

    /**
     * Gibt alle entdeckten Versionen zurück.
     * 
     * @return int[]
     */
    public static function getAvailableVersions(): array {
        self::discover();
        return array_keys(self::$discoveredVersions);
    }

    /**
     * Gibt alle unterstützten Versionen zurück (die eine MetaHeaderDefinition haben).
     * 
     * @return int[]
     */
    public static function getSupportedVersions(): array {
        self::discover();
        return array_keys(array_filter(
            self::$discoveredVersions,
            fn($versionInfo) => $versionInfo['metaHeaderClass'] !== null
        ));
    }

    /**
     * Prüft, ob eine Version unterstützt wird.
     */
    public static function isVersionSupported(int $version): bool {
        self::discover();
        return isset(self::$discoveredVersions[$version]) &&
            self::$discoveredVersions[$version]['metaHeaderClass'] !== null;
    }

    /**
     * Gibt die MetaHeader-Klasse für eine Version zurück.
     * 
     * @return class-string<MetaHeaderDefinitionInterface>|null
     */
    public static function getMetaHeaderClass(int $version): ?string {
        self::discover();
        return self::$discoveredVersions[$version]['metaHeaderClass'] ?? null;
    }

    /**
     * Gibt alle Format-Enums für eine Version zurück.
     * 
     * @return array<int, class-string<FieldHeaderInterface>>
     */
    public static function getFormatEnums(int $version): array {
        self::discover();
        return self::$discoveredVersions[$version]['formatEnums'] ?? [];
    }

    /**
     * Alias für getFormatEnums für Abwärtskompatibilität.
     * @deprecated Use getFormatEnums() instead
     * 
     * @return array<int, class-string<FieldHeaderInterface>>
     */
    public static function getFormatDefinitions(int $version): array {
        return self::getFormatEnums($version);
    }

    /**
     * Prüft, ob ein Format in einer Version unterstützt wird.
     */
    public static function isFormatSupported(Category $category, int $version): bool {
        $formatEnums = self::getFormatEnums($version);
        return isset($formatEnums[$category->value]);
    }

    /**
     * Gibt den Format-Enum für eine Kategorie und Version zurück.
     * 
     * @return class-string<FieldHeaderInterface>|null
     */
    public static function getFormatEnum(Category $category, int $version): ?string {
        $formatEnums = self::getFormatEnums($version);
        return $formatEnums[$category->value] ?? null;
    }

    /**
     * Alias für getFormatEnum für Abwärtskompatibilität.
     * @deprecated Use getFormatEnum() instead
     * 
     * @return class-string<FieldHeaderInterface>|null
     */
    public static function getFormatDefinition(Category $category, int $version): ?string {
        return self::getFormatEnum($category, $version);
    }

    /**
     * Gibt alle unterstützten Formate für eine Version zurück.
     * 
     * @return Category[]
     */
    public static function getSupportedFormats(int $version): array {
        $formatEnums = self::getFormatEnums($version);
        $supportedFormats = [];

        foreach (Category::cases() as $category) {
            if (isset($formatEnums[$category->value])) {
                $supportedFormats[] = $category;
            }
        }

        return $supportedFormats;
    }

    /**
     * Gibt detaillierte Informationen über alle entdeckten Versionen zurück.
     * 
     * @return array<int, array{version: int, path: string, metaHeaderClass: ?string, formatEnums: array<int, class-string<FieldHeaderInterface>>, formatCount: int}>
     */
    public static function getVersionDetails(): array {
        self::discover();

        $details = [];
        foreach (self::$discoveredVersions as $version => $info) {
            $details[$version] = $info + ['formatCount' => count($info['formatEnums'])];
        }

        return $details;
    }

    /**
     * Erzwingt eine erneute Erkennung (für Tests oder nach Dateisystem-Änderungen).
     */
    public static function refresh(): void {
        self::$discovered = false;
        self::$discoveredVersions = [];
        self::discover();
    }

    /**
     * Prüft die Konsistenz einer Version (ob alle erwarteten Dateien vorhanden sind).
     * 
     * @return array{valid: bool, missing: string[], issues: string[]}
     */
    public static function validateVersion(int $version): array {
        self::discover();

        if (!isset(self::$discoveredVersions[$version])) {
            return [
                'valid' => false,
                'missing' => ["Versionsverzeichnis V{$version}"],
                'issues' => ["Version {$version} wurde nicht gefunden"]
            ];
        }

        $versionInfo = self::$discoveredVersions[$version];
        $missing = [];
        $issues = [];

        // Prüfe MetaHeaderDefinition
        if (!$versionInfo['metaHeaderClass']) {
            $missing[] = 'MetaHeaderDefinition.php';
            $issues[] = 'MetaHeaderDefinition fehlt oder ist ungültig';
        }

        // Prüfe auf mindestens einen Format-Enum
        if (empty($versionInfo['formatEnums'])) {
            $issues[] = 'Keine gültigen Format-Enums gefunden';
        }

        return [
            'valid' => empty($missing) && empty($issues),
            'missing' => $missing,
            'issues' => $issues
        ];
    }
}
