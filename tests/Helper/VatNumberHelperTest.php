<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VatNumberHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\VatNumberHelper;
use Tests\Contracts\BaseTestCase;

class VatNumberHelperTest extends BaseTestCase {
    // ========================================
    // Format-Tests (isVatId)
    // ========================================

    public function test_is_vat_id_with_valid_german_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('DE123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('DE999999999'));
    }

    public function test_is_vat_id_with_invalid_german_vat_id(): void {
        $this->assertFalse(VatNumberHelper::isVatId('DE12345678')); // Zu kurz
        $this->assertFalse(VatNumberHelper::isVatId('DE1234567890')); // Zu lang
        $this->assertFalse(VatNumberHelper::isVatId('DE12345678A')); // Buchstabe enthalten
    }

    public function test_is_vat_id_with_valid_austrian_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('ATU12345678'));
    }

    public function test_is_vat_id_with_invalid_austrian_vat_id(): void {
        $this->assertFalse(VatNumberHelper::isVatId('AT12345678')); // Fehlendes U
        $this->assertFalse(VatNumberHelper::isVatId('ATU1234567')); // Zu kurz
    }

    public function test_is_vat_id_with_valid_belgian_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('BE0123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('BE1234567890'));
    }

    public function test_is_vat_id_with_valid_dutch_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('NL123456789B01'));
        $this->assertTrue(VatNumberHelper::isVatId('NL123456789B99'));
    }

    public function test_is_vat_id_with_valid_french_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('FR12345678901'));
        $this->assertTrue(VatNumberHelper::isVatId('FRXX123456789'));
    }

    public function test_is_vat_id_with_valid_italian_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('IT12345678901'));
    }

    public function test_is_vat_id_with_valid_spanish_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('ESA12345678'));
        $this->assertTrue(VatNumberHelper::isVatId('ES12345678A'));
    }

    public function test_is_vat_id_with_valid_polish_vat_id(): void {
        $this->assertTrue(VatNumberHelper::isVatId('PL1234567890'));
    }

    public function test_is_vat_id_with_null_or_empty(): void {
        $this->assertFalse(VatNumberHelper::isVatId(null));
        $this->assertFalse(VatNumberHelper::isVatId(''));
    }

    public function test_is_vat_id_with_invalid_country_code(): void {
        $this->assertFalse(VatNumberHelper::isVatId('XX123456789'));
        $this->assertFalse(VatNumberHelper::isVatId('123456789')); // Kein Ländercode
    }

    // ========================================
    // Normalisierung-Tests
    // ========================================

    public function test_normalize_removes_spaces(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE 123 456 789'));
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE123456789'));
    }

    public function test_normalize_removes_dots(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE.123.456.789'));
    }

    public function test_normalize_removes_dashes(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE-123-456-789'));
    }

    public function test_normalize_converts_to_uppercase(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('de123456789'));
    }

    // ========================================
    // Extraktions-Tests
    // ========================================

    public function test_extract_country_code(): void {
        $this->assertEquals('DE', VatNumberHelper::extractCountryCode('DE123456789'));
        $this->assertEquals('AT', VatNumberHelper::extractCountryCode('ATU12345678'));
        $this->assertEquals('CHE', VatNumberHelper::extractCountryCode('CHE123456789MWST'));
    }

    public function test_extract_country_code_with_invalid_input(): void {
        $this->assertNull(VatNumberHelper::extractCountryCode(''));
        $this->assertNull(VatNumberHelper::extractCountryCode('1'));
        $this->assertNull(VatNumberHelper::extractCountryCode('12'));
    }

    public function test_extract_number(): void {
        $this->assertEquals('123456789', VatNumberHelper::extractNumber('DE123456789'));
        $this->assertEquals('U12345678', VatNumberHelper::extractNumber('ATU12345678'));
    }

    // ========================================
    // Format-Tests
    // ========================================

    public function test_format_german_vat_id(): void {
        $this->assertEquals('DE 123 456 789', VatNumberHelper::format('DE123456789'));
    }

    public function test_format_austrian_vat_id(): void {
        $this->assertEquals('AT U 1234 5678', VatNumberHelper::format('ATU12345678'));
    }

    public function test_format_other_vat_id(): void {
        $this->assertEquals('FR XX123456789', VatNumberHelper::format('FRXX123456789'));
    }

    // ========================================
    // Prüfsummen-Validierung (strict mode)
    // ========================================

    public function test_validate_german_vat_id_with_valid_checksum(): void {
        // Gültige deutsche USt-IDs mit korrekter Prüfsumme
        $this->assertTrue(VatNumberHelper::validateVatId('DE136695976', true));
        $this->assertTrue(VatNumberHelper::validateVatId('DE811128135', true));
    }

    public function test_validate_german_vat_id_with_invalid_checksum(): void {
        // Ungültige Prüfsumme
        $this->assertFalse(VatNumberHelper::validateVatId('DE123456789', true));
        $this->assertFalse(VatNumberHelper::validateVatId('DE111111111', true));
    }

    public function test_validate_german_vat_id_starting_with_zero(): void {
        // Deutsche USt-IDs dürfen nicht mit 0 beginnen
        $this->assertFalse(VatNumberHelper::validateVatId('DE012345678', true));
    }

    public function test_validate_austrian_vat_id_with_valid_checksum(): void {
        // Gültige österreichische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('ATU10223006', true));
    }

    public function test_validate_belgian_vat_id_with_valid_checksum(): void {
        // Gültige belgische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('BE0411905847', true));
    }

    public function test_validate_belgian_vat_id_with_invalid_checksum(): void {
        $this->assertFalse(VatNumberHelper::validateVatId('BE0123456789', true));
    }

    public function test_validate_italian_vat_id_with_valid_checksum(): void {
        // Gültige italienische USt-IDs (Luhn-Algorithmus)
        $this->assertTrue(VatNumberHelper::validateVatId('IT00743110157', true)); // Fiat
    }

    public function test_validate_polish_vat_id_with_valid_checksum(): void {
        // Gültige polnische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('PL5261040567', true)); // PKP SA
    }

    public function test_validate_polish_vat_id_with_invalid_checksum(): void {
        $this->assertFalse(VatNumberHelper::validateVatId('PL1234567890', true));
    }

    public function test_validate_luxembourg_vat_id_with_valid_checksum(): void {
        // Gültige luxemburgische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('LU10000356', true));
    }

    public function test_validate_without_strict_mode(): void {
        // Ohne strict mode wird nur das Format geprüft
        $this->assertTrue(VatNumberHelper::validateVatId('DE123456789', false));
        $this->assertTrue(VatNumberHelper::validateVatId('DE123456789')); // Default ist false
    }

    // ========================================
    // CountryCode-Mapping Tests
    // ========================================

    public function test_get_vat_prefix_for_germany(): void {
        $this->assertEquals('DE', VatNumberHelper::getVatPrefix(CountryCode::Germany));
    }

    public function test_get_vat_prefix_for_greece(): void {
        // Griechenland verwendet EL statt GR für USt-IDs
        $this->assertEquals('EL', VatNumberHelper::getVatPrefix(CountryCode::Greece));
    }

    public function test_get_vat_prefix_for_switzerland(): void {
        $this->assertEquals('CHE', VatNumberHelper::getVatPrefix(CountryCode::Switzerland));
    }

    public function test_get_vat_prefix_for_unsupported_country(): void {
        $this->assertNull(VatNumberHelper::getVatPrefix(CountryCode::Antarctica));
    }

    // ========================================
    // Unterstützte Länder Tests
    // ========================================

    public function test_get_supported_countries(): void {
        $countries = VatNumberHelper::getSupportedCountries();

        $this->assertIsArray($countries);
        $this->assertContains('DE', $countries);
        $this->assertContains('AT', $countries);
        $this->assertContains('FR', $countries);
        $this->assertContains('IT', $countries);
        $this->assertContains('NL', $countries);
        $this->assertContains('CHE', $countries);
    }

    // ========================================
    // Edge Cases
    // ========================================

    public function test_validation_with_normalization(): void {
        // Mit Leerzeichen und Kleinbuchstaben
        $this->assertTrue(VatNumberHelper::isVatId('de 123 456 789'));
        $this->assertTrue(VatNumberHelper::validateVatId('de 123 456 789', false));
    }

    public function test_swiss_vat_id_format(): void {
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789MWST'));
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789TVA'));
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789IVA'));
    }

    public function test_norwegian_vat_id_format(): void {
        $this->assertTrue(VatNumberHelper::isVatId('NO123456789MVA'));
    }

    public function test_northern_ireland_vat_id_format(): void {
        $this->assertTrue(VatNumberHelper::isVatId('XI123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('XI123456789012'));
    }
}
