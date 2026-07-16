<?php
/*
 * Created on   : Wed Jul 16 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : IpLocationHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Helper\Geo\IpLocationHelper;
use Tests\Contracts\BaseTestCase;

class IpLocationHelperTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        IpLocationHelper::resetConfig();
    }

    protected function tearDown(): void {
        IpLocationHelper::resetConfig();
        parent::tearDown();
    }

    // ===== Degradation (Kernvertrag für den Aufrufer) =====

    public function test_not_available_without_configured_database(): void {
        $this->assertFalse(IpLocationHelper::isAvailable());
    }

    public function test_not_available_with_missing_file(): void {
        IpLocationHelper::configure(['database' => '/nonexistent/path/geo.mmdb']);
        $this->assertFalse(IpLocationHelper::isAvailable());
    }

    public function test_lookup_returns_null_when_unavailable(): void {
        // Öffentliche IP, aber keine DB → null (nicht Exception).
        $this->assertNull(IpLocationHelper::lookup('8.8.8.8'));
    }

    public function test_lookup_returns_null_for_private_or_invalid_ip(): void {
        $this->assertNull(IpLocationHelper::lookup('192.168.1.10'));
        $this->assertNull(IpLocationHelper::lookup('127.0.0.1'));
        $this->assertNull(IpLocationHelper::lookup('not-an-ip'));
        $this->assertNull(IpLocationHelper::lookup(null));
    }

    // ===== Record-Mapping (GeoLite2-/DB-IP-Struktur) =====

    public function test_map_record_full(): void {
        $record = [
            'country' => ['iso_code' => 'DE', 'names' => ['en' => 'Germany', 'de' => 'Deutschland']],
            'city' => ['names' => ['en' => 'Berlin', 'de' => 'Berlin']],
        ];

        $this->assertSame(
            ['country' => 'Germany', 'country_iso' => 'DE', 'city' => 'Berlin'],
            IpLocationHelper::mapRecord($record, 'en'),
        );
    }

    public function test_map_record_prefers_configured_locale(): void {
        $record = ['country' => ['iso_code' => 'DE', 'names' => ['en' => 'Germany', 'de' => 'Deutschland']]];

        $mapped = IpLocationHelper::mapRecord($record, 'de');
        $this->assertSame('Deutschland', $mapped['country']);
        $this->assertNull($mapped['city']);
    }

    public function test_map_record_country_level_only(): void {
        $record = ['country' => ['iso_code' => 'US', 'names' => ['en' => 'United States']]];

        $this->assertSame(
            ['country' => 'United States', 'country_iso' => 'US', 'city' => null],
            IpLocationHelper::mapRecord($record, 'en'),
        );
    }

    public function test_map_record_null_for_empty_or_non_array(): void {
        $this->assertNull(IpLocationHelper::mapRecord(null));
        $this->assertNull(IpLocationHelper::mapRecord('nope'));
        $this->assertNull(IpLocationHelper::mapRecord([]));
        $this->assertNull(IpLocationHelper::mapRecord(['continent' => ['code' => 'EU']]));
    }

    public function test_map_record_falls_back_to_first_available_name(): void {
        $record = ['city' => ['names' => ['fr' => 'Genève']]];

        $mapped = IpLocationHelper::mapRecord($record, 'de');
        $this->assertSame('Genève', $mapped['city']);
        $this->assertNull($mapped['country']);
        $this->assertNull($mapped['country_iso']);
    }
}
