<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CreditorIdHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\CreditorIdHelper;
use Tests\Contracts\BaseTestCase;

class CreditorIdHelperTest extends BaseTestCase {

    // ========================================
    // Test: Format-Prüfung
    // ========================================

    public function testIsCreditorIdValid(): void {
        // Deutsche Gläubiger-ID (Beispiel)
        $this->assertTrue(CreditorIdHelper::isCreditorId('DE98ZZZ09999999999'));
        $this->assertTrue(CreditorIdHelper::isCreditorId('DE02ABC01234567890'));
        $this->assertTrue(CreditorIdHelper::isCreditorId('de98zzz09999999999')); // Kleinbuchstaben

        // Andere Länder
        $this->assertTrue(CreditorIdHelper::isCreditorId('AT61ZZZ01234567890'));
        $this->assertTrue(CreditorIdHelper::isCreditorId('NL91ABNA0417164300'));
        $this->assertTrue(CreditorIdHelper::isCreditorId('BE68ZZZ0123456789'));
    }

    public function testIsCreditorIdInvalid(): void {
        $this->assertFalse(CreditorIdHelper::isCreditorId(null));
        $this->assertFalse(CreditorIdHelper::isCreditorId(''));
        $this->assertFalse(CreditorIdHelper::isCreditorId('DE98ZZZ')); // Zu kurz
        $this->assertFalse(CreditorIdHelper::isCreditorId('DEZZZZZ09999999999')); // Keine Prüfziffern
        $this->assertFalse(CreditorIdHelper::isCreditorId('12ABZZZ09999999999')); // Kein Ländercode
    }

    // ========================================
    // Test: Validierung mit Prüfziffer
    // ========================================

    public function testValidateCreditorIdValid(): void {
        // Echte deutsche Gläubiger-IDs mit gültigen Prüfziffern
        // Deutsche Bundesbank Beispiel
        $this->assertTrue(CreditorIdHelper::validateCreditorId('DE98ZZZ09999999999'));
    }

    public function testValidateCreditorIdInvalid(): void {
        // Falsche Prüfziffern (MOD 97 ≠ 1)
        $this->assertFalse(CreditorIdHelper::validateCreditorId('DE00ZZZ09999999999')); // MOD 97 = 0
        $this->assertFalse(CreditorIdHelper::validateCreditorId('DE02ZZZ09999999999')); // MOD 97 = 2
        $this->assertFalse(CreditorIdHelper::validateCreditorId('DE99ZZZ09999999999')); // MOD 97 = 2

        // Ungültiges Format
        $this->assertFalse(CreditorIdHelper::validateCreditorId(null));
        $this->assertFalse(CreditorIdHelper::validateCreditorId(''));
    }

    // ========================================
    // Test: Normalisierung
    // ========================================

    public function testNormalize(): void {
        $this->assertEquals('DE98ZZZ09999999999', CreditorIdHelper::normalize('de98zzz09999999999'));
        $this->assertEquals('DE98ZZZ09999999999', CreditorIdHelper::normalize('DE 98 ZZZ 09999999999'));
        $this->assertEquals('DE98ZZZ09999999999', CreditorIdHelper::normalize('DE98 ZZZ 0999 9999 999'));
    }

    // ========================================
    // Test: Formatierung
    // ========================================

    public function testFormat(): void {
        // Standardformat mit Leerzeichen
        $this->assertEquals('DE98 ZZZ 0999 9999 999', CreditorIdHelper::format('DE98ZZZ09999999999'));

        // Mit anderem Separator
        $this->assertEquals('DE98-ZZZ-0999-9999-999', CreditorIdHelper::format('DE98ZZZ09999999999', '-'));
    }

    // ========================================
    // Test: Extraktion
    // ========================================

    public function testExtractCountryCode(): void {
        $this->assertEquals('DE', CreditorIdHelper::extractCountryCode('DE98ZZZ09999999999'));
        $this->assertEquals('AT', CreditorIdHelper::extractCountryCode('AT61ZZZ01234567890'));
        $this->assertEquals('NL', CreditorIdHelper::extractCountryCode('NL91ABNA0417164300'));

        $this->assertNull(CreditorIdHelper::extractCountryCode('D'));
        $this->assertNull(CreditorIdHelper::extractCountryCode('12'));
    }

    public function testExtractCheckDigits(): void {
        $this->assertEquals('98', CreditorIdHelper::extractCheckDigits('DE98ZZZ09999999999'));
        $this->assertEquals('61', CreditorIdHelper::extractCheckDigits('AT61ZZZ01234567890'));

        $this->assertNull(CreditorIdHelper::extractCheckDigits('DE'));
        $this->assertNull(CreditorIdHelper::extractCheckDigits('DEAB'));
    }

    public function testExtractBusinessAreaCode(): void {
        $this->assertEquals('ZZZ', CreditorIdHelper::extractBusinessAreaCode('DE98ZZZ09999999999'));
        $this->assertEquals('ABC', CreditorIdHelper::extractBusinessAreaCode('DE02ABC01234567890'));

        $this->assertNull(CreditorIdHelper::extractBusinessAreaCode('DE98Z'));
    }

    public function testExtractNationalId(): void {
        $this->assertEquals('09999999999', CreditorIdHelper::extractNationalId('DE98ZZZ09999999999'));
        $this->assertEquals('01234567890', CreditorIdHelper::extractNationalId('DE02ABC01234567890'));

        $this->assertNull(CreditorIdHelper::extractNationalId('DE98ZZZ'));
    }

    // ========================================
    // Test: Länder-Zuordnung
    // ========================================

    public function testMatchesCountry(): void {
        $this->assertTrue(CreditorIdHelper::matchesCountry('DE98ZZZ09999999999', CountryCode::Germany));
        $this->assertTrue(CreditorIdHelper::matchesCountry('AT61ZZZ01234567890', CountryCode::Austria));

        $this->assertFalse(CreditorIdHelper::matchesCountry('DE98ZZZ09999999999', CountryCode::Austria));
        $this->assertFalse(CreditorIdHelper::matchesCountry('AT61ZZZ01234567890', CountryCode::Germany));
    }

    // ========================================
    // Test: Deutsche Gläubiger-ID
    // ========================================

    public function testIsGermanCreditorIdValid(): void {
        $this->assertTrue(CreditorIdHelper::isGermanCreditorId('DE98ZZZ09999999999'));
    }

    public function testIsGermanCreditorIdInvalid(): void {
        // Falsche Länge
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId('DE98ZZZ0999999999')); // 17 Zeichen
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId('DE98ZZZ099999999999')); // 19 Zeichen

        // Falscher Ländercode
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId('AT98ZZZ09999999999'));

        // Ungültiges Format
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId(null));
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId(''));

        // Nationale Kennung mit Buchstaben (bei DE nur Ziffern)
        $this->assertFalse(CreditorIdHelper::isGermanCreditorId('DE98ZZZ0999999999A'));
    }

    // ========================================
    // Test: Prüfziffern-Berechnung
    // ========================================

    public function testCalculateCheckDigits(): void {
        // Berechnung für bekannte Beispiele
        $checkDigits = CreditorIdHelper::calculateCheckDigits('DE', '09999999999');
        $this->assertEquals('98', $checkDigits);

        // Ungültige Eingaben
        $this->assertNull(CreditorIdHelper::calculateCheckDigits('D', '09999999999'));
        $this->assertNull(CreditorIdHelper::calculateCheckDigits('12', '09999999999'));
        $this->assertNull(CreditorIdHelper::calculateCheckDigits('DE', ''));
    }

    // ========================================
    // Test: Generierung
    // ========================================

    public function testGenerate(): void {
        $creditorId = CreditorIdHelper::generate('DE', 'ZZZ', '09999999999');
        $this->assertEquals('DE98ZZZ09999999999', $creditorId);

        // Mit 2-stelligem Geschäftsbereich (wird aufgefüllt)
        $creditorId = CreditorIdHelper::generate('DE', 'AB', '09999999999');
        $this->assertEquals('DE980AB09999999999', $creditorId);

        // Validierung der generierten ID
        $this->assertTrue(CreditorIdHelper::validateCreditorId($creditorId));
    }

    public function testGenerateInvalid(): void {
        // Ungültiger Ländercode
        $this->assertNull(CreditorIdHelper::generate('D', 'ZZZ', '09999999999'));
        $this->assertNull(CreditorIdHelper::generate('123', 'ZZZ', '09999999999'));

        // Fehlende nationale Kennung
        $this->assertNull(CreditorIdHelper::generate('DE', 'ZZZ', ''));
    }

    // ========================================
    // Test: Erwartete Länge
    // ========================================

    public function testGetExpectedLength(): void {
        $this->assertEquals(18, CreditorIdHelper::getExpectedLength('DE'));
        $this->assertEquals(18, CreditorIdHelper::getExpectedLength('AT'));
        $this->assertEquals(19, CreditorIdHelper::getExpectedLength('NL'));
        $this->assertEquals(23, CreditorIdHelper::getExpectedLength('IT'));

        // Unbekanntes Land
        $this->assertNull(CreditorIdHelper::getExpectedLength('XX'));
    }
}
