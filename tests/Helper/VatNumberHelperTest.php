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

    public function testIsVatIdWithValidGermanVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('DE123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('DE999999999'));
    }

    public function testIsVatIdWithInvalidGermanVatId(): void {
        $this->assertFalse(VatNumberHelper::isVatId('DE12345678')); // Zu kurz
        $this->assertFalse(VatNumberHelper::isVatId('DE1234567890')); // Zu lang
        $this->assertFalse(VatNumberHelper::isVatId('DE12345678A')); // Buchstabe enthalten
    }

    public function testIsVatIdWithValidAustrianVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('ATU12345678'));
    }

    public function testIsVatIdWithInvalidAustrianVatId(): void {
        $this->assertFalse(VatNumberHelper::isVatId('AT12345678')); // Fehlendes U
        $this->assertFalse(VatNumberHelper::isVatId('ATU1234567')); // Zu kurz
    }

    public function testIsVatIdWithValidBelgianVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('BE0123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('BE1234567890'));
    }

    public function testIsVatIdWithValidDutchVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('NL123456789B01'));
        $this->assertTrue(VatNumberHelper::isVatId('NL123456789B99'));
    }

    public function testIsVatIdWithValidFrenchVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('FR12345678901'));
        $this->assertTrue(VatNumberHelper::isVatId('FRXX123456789'));
    }

    public function testIsVatIdWithValidItalianVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('IT12345678901'));
    }

    public function testIsVatIdWithValidSpanishVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('ESA12345678'));
        $this->assertTrue(VatNumberHelper::isVatId('ES12345678A'));
    }

    public function testIsVatIdWithValidPolishVatId(): void {
        $this->assertTrue(VatNumberHelper::isVatId('PL1234567890'));
    }

    public function testIsVatIdWithNullOrEmpty(): void {
        $this->assertFalse(VatNumberHelper::isVatId(null));
        $this->assertFalse(VatNumberHelper::isVatId(''));
    }

    public function testIsVatIdWithInvalidCountryCode(): void {
        $this->assertFalse(VatNumberHelper::isVatId('XX123456789'));
        $this->assertFalse(VatNumberHelper::isVatId('123456789')); // Kein Ländercode
    }

    // ========================================
    // Normalisierung-Tests
    // ========================================

    public function testNormalizeRemovesSpaces(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE 123 456 789'));
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE123456789'));
    }

    public function testNormalizeRemovesDots(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE.123.456.789'));
    }

    public function testNormalizeRemovesDashes(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('DE-123-456-789'));
    }

    public function testNormalizeConvertsToUppercase(): void {
        $this->assertEquals('DE123456789', VatNumberHelper::normalize('de123456789'));
    }

    // ========================================
    // Extraktions-Tests
    // ========================================

    public function testExtractCountryCode(): void {
        $this->assertEquals('DE', VatNumberHelper::extractCountryCode('DE123456789'));
        $this->assertEquals('AT', VatNumberHelper::extractCountryCode('ATU12345678'));
        $this->assertEquals('CHE', VatNumberHelper::extractCountryCode('CHE123456789MWST'));
    }

    public function testExtractCountryCodeWithInvalidInput(): void {
        $this->assertNull(VatNumberHelper::extractCountryCode(''));
        $this->assertNull(VatNumberHelper::extractCountryCode('1'));
        $this->assertNull(VatNumberHelper::extractCountryCode('12'));
    }

    public function testExtractNumber(): void {
        $this->assertEquals('123456789', VatNumberHelper::extractNumber('DE123456789'));
        $this->assertEquals('U12345678', VatNumberHelper::extractNumber('ATU12345678'));
    }

    // ========================================
    // Format-Tests
    // ========================================

    public function testFormatGermanVatId(): void {
        $this->assertEquals('DE 123 456 789', VatNumberHelper::format('DE123456789'));
    }

    public function testFormatAustrianVatId(): void {
        $this->assertEquals('AT U 1234 5678', VatNumberHelper::format('ATU12345678'));
    }

    public function testFormatOtherVatId(): void {
        $this->assertEquals('FR XX123456789', VatNumberHelper::format('FRXX123456789'));
    }

    // ========================================
    // Prüfsummen-Validierung (strict mode)
    // ========================================

    public function testValidateGermanVatIdWithValidChecksum(): void {
        // Gültige deutsche USt-IDs mit korrekter Prüfsumme
        $this->assertTrue(VatNumberHelper::validateVatId('DE136695976', true));
        $this->assertTrue(VatNumberHelper::validateVatId('DE811128135', true));
    }

    public function testValidateGermanVatIdWithInvalidChecksum(): void {
        // Ungültige Prüfsumme
        $this->assertFalse(VatNumberHelper::validateVatId('DE123456789', true));
        $this->assertFalse(VatNumberHelper::validateVatId('DE111111111', true));
    }

    public function testValidateGermanVatIdStartingWithZero(): void {
        // Deutsche USt-IDs dürfen nicht mit 0 beginnen
        $this->assertFalse(VatNumberHelper::validateVatId('DE012345678', true));
    }

    public function testValidateAustrianVatIdWithValidChecksum(): void {
        // Gültige österreichische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('ATU10223006', true));
    }

    public function testValidateBelgianVatIdWithValidChecksum(): void {
        // Gültige belgische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('BE0411905847', true));
    }

    public function testValidateBelgianVatIdWithInvalidChecksum(): void {
        $this->assertFalse(VatNumberHelper::validateVatId('BE0123456789', true));
    }

    public function testValidateItalianVatIdWithValidChecksum(): void {
        // Gültige italienische USt-IDs (Luhn-Algorithmus)
        $this->assertTrue(VatNumberHelper::validateVatId('IT00743110157', true)); // Fiat
    }

    public function testValidatePolishVatIdWithValidChecksum(): void {
        // Gültige polnische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('PL5261040567', true)); // PKP SA
    }

    public function testValidatePolishVatIdWithInvalidChecksum(): void {
        $this->assertFalse(VatNumberHelper::validateVatId('PL1234567890', true));
    }

    public function testValidateLuxembourgVatIdWithValidChecksum(): void {
        // Gültige luxemburgische USt-IDs
        $this->assertTrue(VatNumberHelper::validateVatId('LU10000356', true));
    }

    public function testValidateWithoutStrictMode(): void {
        // Ohne strict mode wird nur das Format geprüft
        $this->assertTrue(VatNumberHelper::validateVatId('DE123456789', false));
        $this->assertTrue(VatNumberHelper::validateVatId('DE123456789')); // Default ist false
    }

    // ========================================
    // CountryCode-Mapping Tests
    // ========================================

    public function testGetVatPrefixForGermany(): void {
        $this->assertEquals('DE', VatNumberHelper::getVatPrefix(CountryCode::Germany));
    }

    public function testGetVatPrefixForGreece(): void {
        // Griechenland verwendet EL statt GR für USt-IDs
        $this->assertEquals('EL', VatNumberHelper::getVatPrefix(CountryCode::Greece));
    }

    public function testGetVatPrefixForSwitzerland(): void {
        $this->assertEquals('CHE', VatNumberHelper::getVatPrefix(CountryCode::Switzerland));
    }

    public function testGetVatPrefixForUnsupportedCountry(): void {
        $this->assertNull(VatNumberHelper::getVatPrefix(CountryCode::Antarctica));
    }

    // ========================================
    // Unterstützte Länder Tests
    // ========================================

    public function testGetSupportedCountries(): void {
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

    public function testValidationWithNormalization(): void {
        // Mit Leerzeichen und Kleinbuchstaben
        $this->assertTrue(VatNumberHelper::isVatId('de 123 456 789'));
        $this->assertTrue(VatNumberHelper::validateVatId('de 123 456 789', false));
    }

    public function testSwissVatIdFormat(): void {
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789MWST'));
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789TVA'));
        $this->assertTrue(VatNumberHelper::isVatId('CHE123456789IVA'));
    }

    public function testNorwegianVatIdFormat(): void {
        $this->assertTrue(VatNumberHelper::isVatId('NO123456789MVA'));
    }

    public function testNorthernIrelandVatIdFormat(): void {
        $this->assertTrue(VatNumberHelper::isVatId('XI123456789'));
        $this->assertTrue(VatNumberHelper::isVatId('XI123456789012'));
    }
}
