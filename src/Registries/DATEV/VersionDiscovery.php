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
use CommonToolkit\Contracts\Interfaces\DATEV\{HeaderDefinitionInterface, MetaHeaderDefinitionInterface};

/**
 * Automatische Erkennung verfügbarer DATEV-Versionen aus dem Dateisystem.
 * Durchsucht die Header-Verzeichnisse nach verfügbaren Versionen und deren Definitionen.
 */
final class VersionDiscovery {
    private const HEADER_BASE_PATH = __DIR__ . '/../../Entities/DATEV/Header';
    private const VERSION_PATTERN = '/^V(\d+)$/';

    /** @var array<int, array{version: int, path: string, metaHeaderClass: ?string, formatDefinitions: array<int, string>}> */
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

        if (!is_dir(self::HEADER_BASE_PATH)) {
            self::$discovered = true;
            return;
        }

        $directories = scandir(self::HEADER_BASE_PATH);
        if (!$directories) {
            self::$discovered = true;
            return;
        }

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $fullPath = self::HEADER_BASE_PATH . '/' . $dir;
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
     * Analysiert eine spezifische Version und ihre verfügbaren Definitionen.
     * 
     * @param int $version Die Versionsnummer
     * @param string $versionPath Pfad zum Versionsverzeichnis
     * @return array{version: int, path: string, metaHeaderClass: ?string, formatDefinitions: array<int, string>}|null
     */
    private static function analyzeVersion(int $version, string $versionPath): ?array {
        $versionInfo = [
            'version' => $version,
            'path' => $versionPath,
            'metaHeaderClass' => null,
            'formatDefinitions' => [],
        ];

        // Prüfe auf MetaHeaderDefinition
        $metaHeaderFile = $versionPath . '/MetaHeaderDefinition.php';
        if (file_exists($metaHeaderFile)) {
            $metaHeaderClass = "CommonToolkit\\Entities\\DATEV\\Header\\V{$version}\\MetaHeaderDefinition";
            if (class_exists($metaHeaderClass) && is_subclass_of($metaHeaderClass, MetaHeaderDefinitionInterface::class)) {
                $versionInfo['metaHeaderClass'] = $metaHeaderClass;
            }
        }

        // Durchsuche nach Format-Definitionen
        $files = scandir($versionPath);
        if ($files) {
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php' || $file === 'MetaHeaderDefinition.php') {
                    continue;
                }

                $className = pathinfo($file, PATHINFO_FILENAME);
                $fullClassName = "CommonToolkit\\Entities\\DATEV\\Header\\V{$version}\\{$className}";

                if (class_exists($fullClassName) && is_subclass_of($fullClassName, HeaderDefinitionInterface::class)) {
                    // Versuche die Kategorie aus dem Klassennamen zu ermitteln
                    $category = self::mapClassNameToCategory($className);
                    if ($category) {
                        $versionInfo['formatDefinitions'][$category->value] = $fullClassName;
                    }
                }
            }
        }

        // Nur Versionen zurückgeben, die zumindest eine MetaHeaderDefinition haben
        return $versionInfo['metaHeaderClass'] ? $versionInfo : null;
    }

    /**
     * Mappt Klassennamen auf Kategorien.
     */
    private static function mapClassNameToCategory(string $className): ?Category {
        // Mapping bekannter Klassennamen auf Kategorien
        $mappings = [
            'BookingBatchHeaderDefinition' => Category::Buchungsstapel,
            'DebitorsCreditorsHeaderDefinition' => Category::DebitorenKreditoren,
            'VariousAddressesHeaderDefinition' => Category::DiverseAdressen,
            'GLAccountDescriptionHeaderDefinition' => Category::Sachkontenbeschriftungen,
            'RecurringBookingsHeaderDefinition' => Category::WiederkehrendeBuchungen,
            'PaymentTermsHeaderDefinition' => Category::Zahlungsbedingungen,
            'NaturalStackHeaderDefinition' => Category::NaturalStapel,
        ];

        return $mappings[$className] ?? null;
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
     * Gibt alle Format-Definitionen für eine Version zurück.
     * 
     * @return array<int, class-string<HeaderDefinitionInterface>>
     */
    public static function getFormatDefinitions(int $version): array {
        self::discover();
        return self::$discoveredVersions[$version]['formatDefinitions'] ?? [];
    }

    /**
     * Prüft, ob ein Format in einer Version unterstützt wird.
     */
    public static function isFormatSupported(Category $category, int $version): bool {
        $formatDefs = self::getFormatDefinitions($version);
        return isset($formatDefs[$category->value]);
    }

    /**
     * Gibt die Format-Definition für eine Kategorie und Version zurück.
     * 
     * @return class-string<HeaderDefinitionInterface>|null
     */
    public static function getFormatDefinition(Category $category, int $version): ?string {
        $formatDefs = self::getFormatDefinitions($version);
        return $formatDefs[$category->value] ?? null;
    }

    /**
     * Gibt alle unterstützten Formate für eine Version zurück.
     * 
     * @return Category[]
     */
    public static function getSupportedFormats(int $version): array {
        $formatDefs = self::getFormatDefinitions($version);
        $supportedFormats = [];

        foreach (Category::cases() as $category) {
            if (isset($formatDefs[$category->value])) {
                $supportedFormats[] = $category;
            }
        }

        return $supportedFormats;
    }

    /**
     * Gibt detaillierte Informationen über alle entdeckten Versionen zurück.
     * 
     * @return array<int, array{version: int, path: string, metaHeaderClass: ?string, formatDefinitions: array<int, string>, formatCount: int}>
     */
    public static function getVersionDetails(): array {
        self::discover();

        $details = [];
        foreach (self::$discoveredVersions as $version => $info) {
            $details[$version] = $info + ['formatCount' => count($info['formatDefinitions'])];
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

        // Prüfe auf mindestens eine Format-Definition
        if (empty($versionInfo['formatDefinitions'])) {
            $issues[] = 'Keine gültigen Format-Definitionen gefunden';
        }

        return [
            'valid' => empty($missing) && empty($issues),
            'missing' => $missing,
            'issues' => $issues
        ];
    }
}
