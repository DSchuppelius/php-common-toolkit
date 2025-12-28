<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PostalCodeHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\PostalCodeHelper;
use Tests\Contracts\BaseTestCase;

class PostalCodeHelperTest extends BaseTestCase {

    // ========================================
    // Test: Deutsche Postleitzahlen
    // ========================================

    public function testIsGermanPostalCodeValid(): void {
        // Gültige deutsche PLZ
        $this->assertTrue(PostalCodeHelper::isGermanPostalCode('10115'));
        $this->assertTrue(PostalCodeHelper::isGermanPostalCode('80331'));
        $this->assertTrue(PostalCodeHelper::isGermanPostalCode('01067'));
        $this->assertTrue(PostalCodeHelper::isGermanPostalCode('99999'));
        $this->assertTrue(PostalCodeHelper::isGermanPostalCode('00000'));
    }

    public function testIsGermanPostalCodeInvalid(): void {
        // Ungültige deutsche PLZ
        $this->assertFalse(PostalCodeHelper::isGermanPostalCode('1011'));  // Zu kurz
        $this->assertFalse(PostalCodeHelper::isGermanPostalCode('101155')); // Zu lang
        $this->assertFalse(PostalCodeHelper::isGermanPostalCode('1011A'));  // Mit Buchstaben
        $this->assertFalse(PostalCodeHelper::isGermanPostalCode(''));       // Leer
        $this->assertFalse(PostalCodeHelper::isGermanPostalCode(null));     // Null
    }

    // ========================================
    // Test: Österreichische Postleitzahlen
    // ========================================

    public function testAustrianPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('1010', 'AT'));
        $this->assertTrue(PostalCodeHelper::isValid('8010', 'AT'));
        $this->assertTrue(PostalCodeHelper::isValid('5020', 'AT'));
    }

    public function testAustrianPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('10100', 'AT')); // 5 Ziffern
        $this->assertFalse(PostalCodeHelper::isValid('101', 'AT'));   // 3 Ziffern
    }

    // ========================================
    // Test: Schweizer Postleitzahlen
    // ========================================

    public function testSwissPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('8001', 'CH'));
        $this->assertTrue(PostalCodeHelper::isValid('3000', 'CH'));
        $this->assertTrue(PostalCodeHelper::isValid('1200', 'CH'));
    }

    public function testSwissPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('80010', 'CH'));
        $this->assertFalse(PostalCodeHelper::isValid('800', 'CH'));
    }

    // ========================================
    // Test: Niederländische Postleitzahlen
    // ========================================

    public function testDutchPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('1012 AB', 'NL'));
        $this->assertTrue(PostalCodeHelper::isValid('1012AB', 'NL'));
        $this->assertTrue(PostalCodeHelper::isValid('3511 GH', 'NL'));
    }

    public function testDutchPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('1012', 'NL'));     // Ohne Buchstaben
        $this->assertFalse(PostalCodeHelper::isValid('1012 ABC', 'NL')); // Zu viele Buchstaben
    }

    // ========================================
    // Test: UK Postleitzahlen
    // ========================================

    public function testUKPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('EC1A 1BB', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('W1A 0AX', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('M1 1AE', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('B33 8TH', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('CR2 6XH', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('DN55 1PT', 'GB'));
        $this->assertTrue(PostalCodeHelper::isValid('SW1A1AA', 'GB')); // Ohne Leerzeichen
    }

    public function testUKPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('12345', 'GB'));
        $this->assertFalse(PostalCodeHelper::isValid('ABCDE', 'GB'));
    }

    // ========================================
    // Test: Polnische Postleitzahlen
    // ========================================

    public function testPolishPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('00-001', 'PL'));
        $this->assertTrue(PostalCodeHelper::isValid('31-154', 'PL'));
        $this->assertTrue(PostalCodeHelper::isValid('00001', 'PL')); // Ohne Bindestrich
    }

    public function testPolishPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('0001', 'PL'));
        $this->assertFalse(PostalCodeHelper::isValid('000-001', 'PL'));
    }

    // ========================================
    // Test: US Postleitzahlen
    // ========================================

    public function testUSPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('12345', 'US'));
        $this->assertTrue(PostalCodeHelper::isValid('12345-6789', 'US'));
        $this->assertTrue(PostalCodeHelper::isValid('90210', 'US'));
    }

    public function testUSPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('1234', 'US'));
        $this->assertFalse(PostalCodeHelper::isValid('123456', 'US'));
        $this->assertFalse(PostalCodeHelper::isValid('12345-678', 'US'));
    }

    // ========================================
    // Test: Kanadische Postleitzahlen
    // ========================================

    public function testCanadianPostalCodeValid(): void {
        $this->assertTrue(PostalCodeHelper::isValid('K1A 0B1', 'CA'));
        $this->assertTrue(PostalCodeHelper::isValid('K1A0B1', 'CA'));
        $this->assertTrue(PostalCodeHelper::isValid('V5K 0A1', 'CA'));
    }

    public function testCanadianPostalCodeInvalid(): void {
        $this->assertFalse(PostalCodeHelper::isValid('12345', 'CA'));
        $this->assertFalse(PostalCodeHelper::isValid('ABCDEF', 'CA'));
    }

    // ========================================
    // Test: Normalisierung
    // ========================================

    public function testNormalize(): void {
        // Niederlande - Großbuchstaben
        $this->assertEquals('1012 AB', PostalCodeHelper::normalize('1012 ab', 'NL'));

        // UK - Großbuchstaben
        $this->assertEquals('SW1A 1AA', PostalCodeHelper::normalize('sw1a 1aa', 'GB'));

        // Kanada - Großbuchstaben
        $this->assertEquals('K1A 0B1', PostalCodeHelper::normalize('k1a 0b1', 'CA'));

        // Deutschland - keine Änderung
        $this->assertEquals('10115', PostalCodeHelper::normalize('10115', 'DE'));
    }

    // ========================================
    // Test: Formatierung
    // ========================================

    public function testFormat(): void {
        // Niederlande
        $this->assertEquals('1012 AB', PostalCodeHelper::format('1012AB', 'NL'));

        // Schweden
        $this->assertEquals('123 45', PostalCodeHelper::format('12345', 'SE'));

        // Tschechien
        $this->assertEquals('123 45', PostalCodeHelper::format('12345', 'CZ'));

        // Polen
        $this->assertEquals('00-001', PostalCodeHelper::format('00001', 'PL'));

        // UK
        $this->assertEquals('SW1A 1AA', PostalCodeHelper::format('SW1A1AA', 'GB'));
        $this->assertEquals('M1 1AE', PostalCodeHelper::format('M11AE', 'GB'));
    }

    // ========================================
    // Test: Bundesland-Ermittlung
    // ========================================

    public function testGetGermanState(): void {
        // Berlin
        $this->assertEquals('BE', PostalCodeHelper::getGermanState('10115'));
        $this->assertEquals('BE', PostalCodeHelper::getGermanState('12099'));

        // Bayern
        $this->assertEquals('BY', PostalCodeHelper::getGermanState('80331'));
        $this->assertEquals('BY', PostalCodeHelper::getGermanState('90402'));

        // Hamburg
        $this->assertEquals('HH', PostalCodeHelper::getGermanState('20095'));

        // Sachsen
        $this->assertEquals('SN', PostalCodeHelper::getGermanState('01067'));

        // Ungültige PLZ
        $this->assertNull(PostalCodeHelper::getGermanState('1234'));
    }

    // ========================================
    // Test: Unterstützte Länder
    // ========================================

    public function testGetSupportedCountries(): void {
        $countries = PostalCodeHelper::getSupportedCountries();

        $this->assertContains('DE', $countries);
        $this->assertContains('AT', $countries);
        $this->assertContains('CH', $countries);
        $this->assertContains('GB', $countries);
        $this->assertContains('US', $countries);
        $this->assertGreaterThan(10, count($countries));
    }

    // ========================================
    // Test: Erwartetes Format
    // ========================================

    public function testGetExpectedFormat(): void {
        $this->assertEquals('5 Ziffern', PostalCodeHelper::getExpectedFormat('DE'));
        $this->assertEquals('4 Ziffern', PostalCodeHelper::getExpectedFormat('AT'));
        $this->assertEquals('1234 AB', PostalCodeHelper::getExpectedFormat('NL'));
        $this->assertEquals('AA9A 9AA', PostalCodeHelper::getExpectedFormat('GB'));

        // Unbekanntes Land
        $this->assertNull(PostalCodeHelper::getExpectedFormat('XX'));
    }

    // ========================================
    // Test: CountryCode-Integration
    // ========================================

    public function testMatchesCountry(): void {
        $this->assertTrue(PostalCodeHelper::matchesCountry('10115', CountryCode::Germany));
        $this->assertTrue(PostalCodeHelper::matchesCountry('1010', CountryCode::Austria));
        $this->assertTrue(PostalCodeHelper::matchesCountry('8001', CountryCode::Switzerland));

        $this->assertFalse(PostalCodeHelper::matchesCountry('10115', CountryCode::Austria));
        $this->assertFalse(PostalCodeHelper::matchesCountry('1010', CountryCode::Germany));
    }

    // ========================================
    // Test: Fallback für unbekannte Länder
    // ========================================

    public function testUnknownCountryFallback(): void {
        // Alphanumerisch, 3-10 Zeichen
        $this->assertTrue(PostalCodeHelper::isValid('ABC123', 'XX'));
        $this->assertTrue(PostalCodeHelper::isValid('12345', 'XX'));

        // Zu kurz
        $this->assertFalse(PostalCodeHelper::isValid('AB', 'XX'));
    }
}
