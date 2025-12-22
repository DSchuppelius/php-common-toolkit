<?php
/*
 * Created on   : Mon Dec 21 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VersionManager.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Registries\DATEV;

use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Entities\DATEV\DocumentInfo;

/**
 * Zentrale Verwaltung für DATEV-Versionen und Format-Informationen.
 * Bietet High-Level-Funktionen für die Abfrage von Versionen und Formaten.
 * Nutzt das VersionDiscovery-System zur automatischen Erkennung.
 */
final class VersionManager {

    /**
     * Gibt Informationen zu allen verfügbaren DATEV-Versionen zurück (dynamisch).
     * 
     * @return array<int, array{version: int, supported: bool, formats: string[]}>
     */
    public static function getVersionOverview(): array {
        $overview = [];
        $availableVersions = VersionDiscovery::getAvailableVersions();

        foreach ($availableVersions as $version) {
            $supportedFormats = [];
            $isSupported = VersionDiscovery::isVersionSupported($version);

            if ($isSupported) {
                foreach (Category::cases() as $category) {
                    if (VersionDiscovery::isFormatSupported($category, $version)) {
                        $supportedFormats[] = $category->nameValue();
                    }
                }
            }

            $overview[$version] = [
                'version' => $version,
                'supported' => $isSupported,
                'formats' => $supportedFormats,
            ];
        }

        return $overview;
    }

    /**
     * Gibt eine Matrix aller Format-Version-Kombinationen zurück (dynamisch).
     * 
     * @return array<string, array<string, bool>>
     */
    public static function getCompatibilityMatrix(): array {
        $matrix = [];
        $availableVersions = VersionDiscovery::getAvailableVersions();

        // Alle Kategorien als Zeilen
        foreach (Category::cases() as $category) {
            $formatName = $category->nameValue();
            $matrix[$formatName] = [];

            // Alle verfügbaren Versionen als Spalten
            foreach ($availableVersions as $version) {
                $versionKey = "v{$version}";
                $matrix[$formatName][$versionKey] = VersionDiscovery::isFormatSupported($category, $version);
            }
        }

        return $matrix;
    }

    /**
     * Ermittelt die beste verfügbare Version für ein Format (dynamisch).
     * 
     * @param Category $category Das gewünschte Format
     * @return int|null Die beste verfügbare Version oder null
     */
    public static function getBestVersionForFormat(Category $category): ?int {
        // Sortiere Versionen absteigend (neueste zuerst)
        $supportedVersions = VersionDiscovery::getSupportedVersions();
        rsort($supportedVersions);

        foreach ($supportedVersions as $version) {
            if (VersionDiscovery::isFormatSupported($category, $version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Prüft, ob ein Format in einer spezifischen Version verfügbar ist (dynamisch).
     */
    public static function isAvailable(Category $category, int $version): bool {
        return VersionDiscovery::isFormatSupported($category, $version);
    }

    /**
     * Erstellt DocumentInfo für ein Format und eine Version.
     */
    public static function createDocumentInfo(Category $category, int $version): DocumentInfo {
        $definitionClass = null;

        if (VersionDiscovery::isVersionSupported($version) && VersionDiscovery::isFormatSupported($category, $version)) {
            $definitionClass = VersionDiscovery::getFormatDefinition($category, $version);
        }

        return new DocumentInfo($category, $version, $definitionClass);
    }

    /**
     * Gibt alle unterstützten Formate für die neueste Version zurück (dynamisch).
     * 
     * @return Category[]
     */
    public static function getCurrentSupportedFormats(): array {
        $supportedVersions = VersionDiscovery::getSupportedVersions();
        if (empty($supportedVersions)) {
            return [];
        }

        $latestVersion = max($supportedVersions);
        return VersionDiscovery::getSupportedFormats($latestVersion);
    }

    /**
     * Migration-Hilfe: Zeigt, welche Formate von einer alten Version in eine neue migriert werden können (dynamisch).
     * 
     * @param int $fromVersion Alte Version
     * @param int $toVersion Neue Version
     * @return array{migratable: Category[], not_migratable: Category[], new_formats: Category[]}
     */
    public static function getMigrationPlan(int $fromVersion, int $toVersion): array {
        $fromFormats = VersionDiscovery::getSupportedFormats($fromVersion);
        $toFormats = VersionDiscovery::getSupportedFormats($toVersion);

        $migratable = [];
        $notMigratable = [];
        $newFormats = [];

        // Prüfe, welche alten Formate in der neuen Version verfügbar sind
        foreach ($fromFormats as $category) {
            if (in_array($category, $toFormats, true)) {
                $migratable[] = $category;
            } else {
                $notMigratable[] = $category;
            }
        }

        // Prüfe auf neue Formate in der Zielversion
        foreach ($toFormats as $category) {
            if (!in_array($category, $fromFormats, true)) {
                $newFormats[] = $category;
            }
        }

        return [
            'migratable' => $migratable,
            'not_migratable' => $notMigratable,
            'new_formats' => $newFormats,
        ];
    }

    /**
     * Gibt eine Human-readable Übersicht über alle Versionen aus (dynamisch).
     */
    public static function getVersionSummary(): string {
        $overview = self::getVersionOverview();
        $lines = [];

        $lines[] = "DATEV Versionen-Übersicht (dynamisch erkannt):";
        $lines[] = str_repeat('=', 50);

        foreach ($overview as $info) {
            $status = $info['supported'] ? '✅ Unterstützt' : '❌ Nicht unterstützt';
            $formatCount = count($info['formats']);

            $lines[] = sprintf(
                "Version %d: %s (%d Formate)",
                $info['version'],
                $status,
                $formatCount
            );

            if ($formatCount > 0) {
                $lines[] = "  Formate: " . implode(', ', $info['formats']);
            }
            $lines[] = "";
        }

        // Zusätzliche Discovery-Informationen
        $versionDetails = VersionDiscovery::getVersionDetails();
        if (!empty($versionDetails)) {
            $lines[] = "Discovery Details:";
            $lines[] = str_repeat('-', 30);

            foreach ($versionDetails as $version => $details) {
                $lines[] = sprintf(
                    "V%d: %s (%d Klassen)",
                    $version,
                    $details['metaHeaderClass'] ? 'Gültig' : 'Unvollständig',
                    $details['formatCount'] + 1
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Erzwingt eine Aktualisierung der Discovery-Erkennung.
     */
    public static function refresh(): void {
        VersionDiscovery::refresh();
    }

    /**
     * Gibt detaillierte Validation-Informationen für alle Versionen zurück.
     * 
     * @return array<int, array{valid: bool, missing: string[], issues: string[]}>
     */
    public static function validateAllVersions(): array {
        $results = [];
        $availableVersions = VersionDiscovery::getAvailableVersions();

        foreach ($availableVersions as $version) {
            $results[$version] = VersionDiscovery::validateVersion($version);
        }

        return $results;
    }
}