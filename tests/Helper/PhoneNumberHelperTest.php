<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PhoneNumberHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\PhoneNumberHelper;
use Tests\Contracts\BaseTestCase;

class PhoneNumberHelperTest extends BaseTestCase {
    // ========================================
    // E.164 Format Tests
    // ========================================

    public function test_is_e164_with_valid_format(): void {
        $this->assertTrue(PhoneNumberHelper::isE164('+4930123456'));
        $this->assertTrue(PhoneNumberHelper::isE164('+493012345678'));
        $this->assertTrue(PhoneNumberHelper::isE164('+15551234567'));
        $this->assertTrue(PhoneNumberHelper::isE164('+41441234567'));
    }

    public function test_is_e164_with_invalid_format(): void {
        $this->assertFalse(PhoneNumberHelper::isE164(null));
        $this->assertFalse(PhoneNumberHelper::isE164(''));
        $this->assertFalse(PhoneNumberHelper::isE164('4930123456')); // Fehlendes +
        $this->assertFalse(PhoneNumberHelper::isE164('+0301234567')); // Führende 0
        $this->assertFalse(PhoneNumberHelper::isE164('+49')); // Zu kurz
        $this->assertFalse(PhoneNumberHelper::isE164('+4930123456789012345')); // Zu lang (>15)
        $this->assertFalse(PhoneNumberHelper::isE164('+49 30 123456')); // Mit Leerzeichen
    }

    // ========================================
    // Allgemeine Telefonnummer Tests
    // ========================================

    public function test_is_phone_number_with_valid_formats(): void {
        $this->assertTrue(PhoneNumberHelper::isPhoneNumber('030123456'));
        $this->assertTrue(PhoneNumberHelper::isPhoneNumber('030 123 456'));
        $this->assertTrue(PhoneNumberHelper::isPhoneNumber('030-123-456'));
        $this->assertTrue(PhoneNumberHelper::isPhoneNumber('+49 30 123456'));
        $this->assertTrue(PhoneNumberHelper::isPhoneNumber('0049 30 123456'));
    }

    public function test_is_phone_number_with_invalid_formats(): void {
        $this->assertFalse(PhoneNumberHelper::isPhoneNumber(null));
        $this->assertFalse(PhoneNumberHelper::isPhoneNumber(''));
        $this->assertFalse(PhoneNumberHelper::isPhoneNumber('12')); // Zu kurz
    }

    // ========================================
    // Deutsche Telefonnummern Tests
    // ========================================

    public function test_is_german_phone_number_with_valid_formats(): void {
        $this->assertTrue(PhoneNumberHelper::isGermanPhoneNumber('030 12345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanPhoneNumber('030-12345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanPhoneNumber('03012345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanPhoneNumber('+49 30 12345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanPhoneNumber('0049 30 12345678'));
    }

    public function test_is_german_phone_number_with_invalid_formats(): void {
        $this->assertFalse(PhoneNumberHelper::isGermanPhoneNumber(null));
        $this->assertFalse(PhoneNumberHelper::isGermanPhoneNumber(''));
        $this->assertFalse(PhoneNumberHelper::isGermanPhoneNumber('123')); // Zu kurz
        $this->assertFalse(PhoneNumberHelper::isGermanPhoneNumber('+33 1 12345678')); // Französisch
    }

    // ========================================
    // Deutsche Mobilfunknummern Tests
    // ========================================

    public function test_is_german_mobile_number_with_valid_formats(): void {
        $this->assertTrue(PhoneNumberHelper::isGermanMobileNumber('0151 12345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanMobileNumber('0171 1234567'));
        $this->assertTrue(PhoneNumberHelper::isGermanMobileNumber('0176 12345678'));
        $this->assertTrue(PhoneNumberHelper::isGermanMobileNumber('+49 151 12345678'));
    }

    public function test_is_german_mobile_number_with_landline(): void {
        $this->assertFalse(PhoneNumberHelper::isGermanMobileNumber('030 12345678')); // Berlin Festnetz
        $this->assertFalse(PhoneNumberHelper::isGermanMobileNumber('089 12345678')); // München Festnetz
    }

    // ========================================
    // Normalisierungs-Tests
    // ========================================

    public function test_normalize(): void {
        $this->assertEquals('4930123456', PhoneNumberHelper::normalize('+49 30 123456'));
        $this->assertEquals('030123456', PhoneNumberHelper::normalize('030-123-456'));
        $this->assertEquals('030123456', PhoneNumberHelper::normalize('030 123 456'));
        $this->assertEquals('4930123456', PhoneNumberHelper::normalize('0049 30 123456'));
    }

    // ========================================
    // E.164 Konvertierungs-Tests
    // ========================================

    public function test_to_e164_with_german_number(): void {
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164('030 12345678', 'DE'));
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164('03012345678', 'DE'));
    }

    public function test_to_e164_with_international_format(): void {
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164('+49 30 12345678', 'DE'));
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164('0049 30 12345678', 'DE'));
    }

    public function test_to_e164_with_other_country(): void {
        $this->assertEquals('+43112345678', PhoneNumberHelper::toE164('01 12345678', 'AT'));
        $this->assertEquals('+41441234567', PhoneNumberHelper::toE164('044 1234567', 'CH'));
    }

    public function test_to_e164_already_e164(): void {
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164('+493012345678'));
    }

    public function test_to_e164_with_invalid_country(): void {
        $this->assertNull(PhoneNumberHelper::toE164('030 12345678', 'XX'));
    }

    // ========================================
    // Formatierungs-Tests
    // ========================================

    public function test_format_international(): void {
        $formatted = PhoneNumberHelper::formatInternational('+493012345678');
        $this->assertStringStartsWith('+49 ', $formatted);
    }

    public function test_format_national(): void {
        $formatted = PhoneNumberHelper::formatNational('+493012345678', 'DE');
        $this->assertStringStartsWith('0', $formatted);
    }

    public function test_format_e164(): void {
        $formatted = PhoneNumberHelper::format('030 12345678', 'e164', 'DE');
        $this->assertEquals('+493012345678', $formatted);
    }

    // ========================================
    // Ländervorwahl-Extraktion Tests
    // ========================================

    public function test_extract_country_code(): void {
        $this->assertEquals('49', PhoneNumberHelper::extractCountryCode('+493012345678'));
        $this->assertEquals('43', PhoneNumberHelper::extractCountryCode('+43112345678'));
        $this->assertEquals('1', PhoneNumberHelper::extractCountryCode('+15551234567'));
    }

    public function test_extract_country_code_with_invalid(): void {
        $this->assertNull(PhoneNumberHelper::extractCountryCode('493012345678'));
        $this->assertNull(PhoneNumberHelper::extractCountryCode(''));
    }

    public function test_extract_country(): void {
        $this->assertEquals('DE', PhoneNumberHelper::extractCountry('+493012345678'));
        $this->assertEquals('AT', PhoneNumberHelper::extractCountry('+43112345678'));
        $this->assertEquals('US', PhoneNumberHelper::extractCountry('+15551234567'));
    }

    // ========================================
    // Sonstige Tests
    // ========================================

    public function test_get_supported_countries(): void {
        $countries = PhoneNumberHelper::getSupportedCountries();

        $this->assertIsArray($countries);
        $this->assertArrayHasKey('DE', $countries);
        $this->assertArrayHasKey('AT', $countries);
        $this->assertArrayHasKey('CH', $countries);
        $this->assertEquals('49', $countries['DE']);
        $this->assertEquals('43', $countries['AT']);
    }

    // ========================================
    // CountryCode-Enum Tests
    // ========================================

    public function test_get_country_calling_code(): void {
        $this->assertEquals('49', PhoneNumberHelper::getCountryCallingCode(CountryCode::Germany));
        $this->assertEquals('43', PhoneNumberHelper::getCountryCallingCode(CountryCode::Austria));
        $this->assertEquals('41', PhoneNumberHelper::getCountryCallingCode(CountryCode::Switzerland));
        $this->assertEquals('1', PhoneNumberHelper::getCountryCallingCode(CountryCode::UnitedStatesOfAmerica));

        // Nicht unterstütztes Land
        $this->assertNull(PhoneNumberHelper::getCountryCallingCode(CountryCode::Japan));
    }

    public function test_matches_country(): void {
        $this->assertTrue(PhoneNumberHelper::matchesCountry('+493012345678', CountryCode::Germany));
        $this->assertTrue(PhoneNumberHelper::matchesCountry('030 12345678', CountryCode::Germany));
        $this->assertTrue(PhoneNumberHelper::matchesCountry('+43112345678', CountryCode::Austria));

        $this->assertFalse(PhoneNumberHelper::matchesCountry('+493012345678', CountryCode::Austria));
        $this->assertFalse(PhoneNumberHelper::matchesCountry('+43112345678', CountryCode::Germany));
    }

    public function test_extract_country_enum(): void {
        $this->assertEquals(CountryCode::Germany, PhoneNumberHelper::extractCountryEnum('+493012345678'));
        $this->assertEquals(CountryCode::Austria, PhoneNumberHelper::extractCountryEnum('+43112345678'));
        $this->assertEquals(CountryCode::Switzerland, PhoneNumberHelper::extractCountryEnum('+41441234567'));

        // Ungültige Nummer
        $this->assertNull(PhoneNumberHelper::extractCountryEnum('493012345678'));
    }

    public function test_to_e164_with_country_code(): void {
        $this->assertEquals('+493012345678', PhoneNumberHelper::toE164WithCountryCode('030 12345678', CountryCode::Germany));
        $this->assertEquals('+43112345678', PhoneNumberHelper::toE164WithCountryCode('01 12345678', CountryCode::Austria));
        $this->assertEquals('+41441234567', PhoneNumberHelper::toE164WithCountryCode('044 1234567', CountryCode::Switzerland));
    }

    public function test_format_with_country_code(): void {
        $formatted = PhoneNumberHelper::formatWithCountryCode('030 12345678', 'e164', CountryCode::Germany);
        $this->assertEquals('+493012345678', $formatted);

        $formatted = PhoneNumberHelper::formatWithCountryCode('030 12345678', 'international', CountryCode::Germany);
        $this->assertStringStartsWith('+49 ', $formatted);
    }
}
