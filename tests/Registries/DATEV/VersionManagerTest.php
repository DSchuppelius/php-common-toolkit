<?php
/*
 * Created on   : Mon Dec 21 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VersionManagerTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Registries\DATEV;

use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Registries\DATEV\{HeaderRegistry, VersionManager, VersionDiscovery};
use Tests\Contracts\BaseTestCase;

final class VersionManagerTest extends BaseTestCase {

    protected function setUp(): void {
        parent::setUp();
        // Cache vor jedem Test leeren
        HeaderRegistry::clearCache();
        // Discovery aktualisieren
        VersionDiscovery::refresh();
    }

    public function testVersionDiscoveryFindsV700(): void {
        $availableVersions = VersionDiscovery::getAvailableVersions();
        $supportedVersions = VersionDiscovery::getSupportedVersions();

        $this->assertContains(700, $availableVersions, 'V700 muss durch Discovery erkannt werden');
        $this->assertContains(700, $supportedVersions, 'V700 muss als unterstützt erkannt werden');
    }

    public function testVersionOverviewDynamic(): void {
        $overview = VersionManager::getVersionOverview();

        $this->assertNotEmpty($overview, 'Dynamische Versionsübersicht darf nicht leer sein');
        $this->assertArrayHasKey(700, $overview, 'Version 700 muss in dynamischer Übersicht enthalten sein');

        $v700 = $overview[700];
        $this->assertTrue($v700['supported'], 'Version 700 muss dynamisch als unterstützt erkannt werden');
        $this->assertNotEmpty($v700['formats'], 'Version 700 muss dynamisch unterstützte Formate haben');
    }

    public function testCompatibilityMatrixDynamic(): void {
        $matrix = VersionManager::getCompatibilityMatrix();

        $this->assertNotEmpty($matrix, 'Dynamische Kompatibilitätsmatrix darf nicht leer sein');

        // Prüfe, dass alle Kategorien vorhanden sind
        foreach (Category::cases() as $category) {
            $formatName = $category->nameValue();
            $this->assertArrayHasKey($formatName, $matrix, "Format '{$formatName}' muss in dynamischer Matrix enthalten sein");
        }

        // Prüfe, dass V700 für Buchungsstapel unterstützt wird (wenn verfügbar)
        if (isset($matrix['Buchungsstapel']['v700'])) {
            $this->assertTrue(
                $matrix['Buchungsstapel']['v700'],
                'Buchungsstapel muss in V700 dynamisch unterstützt werden'
            );
        }
    }

    public function testBestVersionForFormatDynamic(): void {
        $bestVersion = VersionManager::getBestVersionForFormat(Category::Buchungsstapel);

        if (VersionDiscovery::getSupportedVersions()) {
            $this->assertNotNull($bestVersion, 'Beste Version für Buchungsstapel muss dynamisch gefunden werden');
            $this->assertEquals(700, $bestVersion, 'V700 sollte aktuell beste dynamisch erkannte Version sein');
        } else {
            $this->assertNull($bestVersion, 'Keine Version gefunden wenn keine unterstützt wird');
        }
    }

    public function testIsAvailableDynamic(): void {
        // Test für unterstütztes Format (wenn verfügbar)
        if (VersionDiscovery::isFormatSupported(Category::Buchungsstapel, 700)) {
            $this->assertTrue(
                VersionManager::isAvailable(Category::Buchungsstapel, 700),
                'Buchungsstapel muss in V700 dynamisch verfügbar sein'
            );
        }

        // Test für nicht existierende Version
        $this->assertFalse(
            VersionManager::isAvailable(Category::Buchungsstapel, 999),
            'Buchungsstapel darf in V999 nicht verfügbar sein'
        );
    }

    public function testHeaderRegistryDynamic(): void {
        // Test Integration mit HeaderRegistry über Discovery
        $supportedVersions = HeaderRegistry::getSupportedVersions();
        $this->assertNotEmpty($supportedVersions, 'HeaderRegistry muss dynamisch unterstützte Versionen finden');

        if (in_array(700, $supportedVersions)) {
            $this->assertContains(700, $supportedVersions, 'HeaderRegistry muss V700 dynamisch als unterstützt listen');

            // Test Format-Support
            $supportedFormats = HeaderRegistry::getSupportedFormats(700);
            $this->assertNotEmpty($supportedFormats, 'V700 muss dynamisch unterstützte Formate haben');
        }
    }

    public function testVersionDetailsAndValidation(): void {
        $versionDetails = HeaderRegistry::getVersionDetails();
        $this->assertNotEmpty($versionDetails, 'Versions-Details müssen verfügbar sein');

        // Test Validation
        $validationResults = VersionManager::validateAllVersions();
        $this->assertNotEmpty($validationResults, 'Validation-Ergebnisse müssen verfügbar sein');

        foreach ($validationResults as $version => $result) {
            $this->assertIsArray($result, "Validation-Ergebnis für Version {$version} muss Array sein");
            $this->assertArrayHasKey('valid', $result, "Validation muss 'valid' Key haben");
            $this->assertArrayHasKey('missing', $result, "Validation muss 'missing' Key haben");
            $this->assertArrayHasKey('issues', $result, "Validation muss 'issues' Key haben");
        }
    }

    public function testGetMigrationPlanDynamic(): void {
        $supportedVersions = VersionDiscovery::getSupportedVersions();

        if (count($supportedVersions) > 0) {
            $version = $supportedVersions[0];

            // Test Migration von gleicher Version zu gleicher Version
            $plan = VersionManager::getMigrationPlan($version, $version);

            $this->assertArrayHasKey('migratable', $plan);
            $this->assertArrayHasKey('not_migratable', $plan);
            $this->assertArrayHasKey('new_formats', $plan);

            // Bei gleicher Version sollten alle Formate migrierbar sein
            if (!empty($plan['migratable'])) {
                $this->assertEmpty($plan['not_migratable'], 'Nicht-migrierbare Formate sollten leer sein bei gleicher Version');
                $this->assertEmpty($plan['new_formats'], 'Neue Formate sollten leer sein bei gleicher Version');
            }
        } else {
            $this->markTestSkipped('Keine unterstützten Versionen verfügbar für Migration-Test');
        }
    }

    public function testGetVersionSummaryDynamic(): void {
        $summary = VersionManager::getVersionSummary();

        $this->assertNotEmpty($summary, 'Dynamische Versions-Summary darf nicht leer sein');
        $this->assertStringContainsString('dynamisch erkannt', $summary, 'Summary muss dynamische Erkennung erwähnen');

        if (VersionDiscovery::isVersionSupported(700)) {
            $this->assertStringContainsString('Version 700', $summary, 'Summary muss Version 700 enthalten wenn verfügbar');
        }
    }

    public function testRefreshFunctionality(): void {
        // Test dass Refresh funktioniert
        VersionManager::refresh();
        HeaderRegistry::refresh();

        // Nach Refresh sollten Versionen noch verfügbar sein
        $versionsAfterRefresh = VersionDiscovery::getSupportedVersions();
        $this->assertNotEmpty($versionsAfterRefresh, 'Nach Refresh sollten Versionen verfügbar sein');
    }

    public function testVersionDiscoveryDetails(): void {
        $details = VersionDiscovery::getVersionDetails();

        if (!empty($details)) {
            foreach ($details as $version => $info) {
                $this->assertIsInt($version, 'Version muss Integer sein');
                $this->assertArrayHasKey('version', $info, 'Version-Info muss version Key haben');
                $this->assertArrayHasKey('path', $info, 'Version-Info muss path Key haben');
                $this->assertArrayHasKey('metaHeaderClass', $info, 'Version-Info muss metaHeaderClass Key haben');
                $this->assertArrayHasKey('formatEnums', $info, 'Version-Info muss formatEnums Key haben');
                $this->assertArrayHasKey('formatCount', $info, 'Version-Info muss formatCount Key haben');

                // Wenn unterstützt, sollte MetaHeader-Klasse vorhanden sein
                if (VersionDiscovery::isVersionSupported($version)) {
                    $this->assertNotNull($info['metaHeaderClass'], "Unterstützte Version {$version} muss MetaHeader-Klasse haben");
                }
            }
        } else {
            $this->markTestSkipped('Keine Version-Details verfügbar (möglicherweise keine Header-Verzeichnisse)');
        }
    }
}
