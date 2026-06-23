<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TaxNumberHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

use CommonToolkit\Helper\Data\TaxNumberHelper;
use Tests\Contracts\BaseTestCase;

class TaxNumberHelperTest extends BaseTestCase {
    // ========================================
    // IdNr Format-Tests
    // ========================================

    public function test_is_id_nr_with_valid_format(): void {
        // Gültige IdNr-Formate (11 Ziffern, mindestens eine doppelt)
        $this->assertTrue(TaxNumberHelper::isIdNr('86095742719')); // Beispiel mit Prüfziffer
        $this->assertTrue(TaxNumberHelper::isIdNr('12312345678')); // 1 und 3 doppelt
    }

    public function test_is_id_nr_with_spaces(): void {
        // Mit Leerzeichen formatiert
        $this->assertTrue(TaxNumberHelper::isIdNr('86 095 742 719'));
        $this->assertTrue(TaxNumberHelper::isIdNr('12 312 345 678'));
    }

    public function test_is_id_nr_with_invalid_format(): void {
        $this->assertFalse(TaxNumberHelper::isIdNr(null));
        $this->assertFalse(TaxNumberHelper::isIdNr(''));
        $this->assertFalse(TaxNumberHelper::isIdNr('1234567890')); // Zu kurz (10 Ziffern)
        $this->assertFalse(TaxNumberHelper::isIdNr('123456789012')); // Zu lang (12 Ziffern)
        $this->assertFalse(TaxNumberHelper::isIdNr('0234567890A')); // Buchstabe enthalten
    }

    public function test_is_id_nr_with_leading_zero(): void {
        // Erste Ziffer darf nicht 0 sein
        $this->assertFalse(TaxNumberHelper::isIdNr('01234567890'));
    }

    // ========================================
    // IdNr Validierungs-Tests (mit Prüfziffer)
    // ========================================

    public function test_validate_id_nr_with_valid_checksum(): void {
        // Bekannte gültige IdNr mit korrekter Prüfziffer
        $this->assertTrue(TaxNumberHelper::validateIdNr('86095742719'));
    }

    public function test_validate_id_nr_with_invalid_checksum(): void {
        // Ungültige Prüfziffer
        $this->assertFalse(TaxNumberHelper::validateIdNr('12345678901'));
        $this->assertFalse(TaxNumberHelper::validateIdNr('86095742710'));
    }

    // ========================================
    // Steuernummer Format-Tests
    // ========================================

    public function test_is_st_nr_with_valid_format(): void {
        $this->assertTrue(TaxNumberHelper::isStNr('1234567890'));
        $this->assertTrue(TaxNumberHelper::isStNr('12345678901'));
        $this->assertTrue(TaxNumberHelper::isStNr('123456789012'));
        $this->assertTrue(TaxNumberHelper::isStNr('1234567890123'));
    }

    public function test_is_st_nr_with_formatted_input(): void {
        // Mit Schrägstrichen
        $this->assertTrue(TaxNumberHelper::isStNr('123/456/78901'));
        $this->assertTrue(TaxNumberHelper::isStNr('12/345/67890'));
    }

    public function test_is_st_nr_with_invalid_format(): void {
        $this->assertFalse(TaxNumberHelper::isStNr(null));
        $this->assertFalse(TaxNumberHelper::isStNr(''));
        $this->assertFalse(TaxNumberHelper::isStNr('123456789')); // Zu kurz
        $this->assertFalse(TaxNumberHelper::isStNr('12345678901234')); // Zu lang
    }

    // ========================================
    // Bundeseinheitliche Steuernummer Tests
    // ========================================

    public function test_is_unified_st_nr_with_valid_format(): void {
        $this->assertTrue(TaxNumberHelper::isUnifiedStNr('1121081508150'));
        $this->assertTrue(TaxNumberHelper::isUnifiedStNr('5133081508159'));
    }

    public function test_is_unified_st_nr_with_invalid_format(): void {
        $this->assertFalse(TaxNumberHelper::isUnifiedStNr('123456789012')); // 12 Ziffern
        $this->assertFalse(TaxNumberHelper::isUnifiedStNr('12345678901234')); // 14 Ziffern
    }

    // ========================================
    // Normalisierungs-Tests
    // ========================================

    public function test_normalize_removes_non_digits(): void {
        $this->assertEquals('12345678901', TaxNumberHelper::normalize('12 345 678 901'));
        $this->assertEquals('1234567890123', TaxNumberHelper::normalize('123/456/7890123'));
        $this->assertEquals('1234567890123', TaxNumberHelper::normalize('123-456-7890123'));
    }

    // ========================================
    // Format-Tests
    // ========================================

    public function test_format_id_nr(): void {
        $this->assertEquals('12 345 678 901', TaxNumberHelper::formatIdNr('12345678901'));
        $this->assertEquals('86 095 742 719', TaxNumberHelper::formatIdNr('86095742719'));
    }

    public function test_format_id_nr_with_invalid_length(): void {
        // Bei ungültiger Länge wird unverändert zurückgegeben
        $this->assertEquals('1234567890', TaxNumberHelper::formatIdNr('1234567890'));
    }

    public function test_format_st_nr_with_state(): void {
        // NRW Format: FFF/BBBB/UUUP
        $formatted = TaxNumberHelper::formatStNr('5133081508159', 'NW');
        $this->assertEquals('513/3081/508159', $formatted);
    }

    public function test_format_st_nr_default(): void {
        // Standard-Formatierung für 13-stellige Nummer
        $formatted = TaxNumberHelper::formatStNr('1234567890123');
        $this->assertEquals('1234/5678/90123', $formatted);
    }

    // ========================================
    // Bundesland-Tests
    // ========================================

    public function test_get_federal_states(): void {
        $states = TaxNumberHelper::getFederalStates();

        $this->assertIsArray($states);
        $this->assertArrayHasKey('BY', $states);
        $this->assertArrayHasKey('NW', $states);
        $this->assertArrayHasKey('BE', $states);
        $this->assertEquals('Bayern', $states['BY']);
        $this->assertEquals('Nordrhein-Westfalen', $states['NW']);
    }

    public function test_get_federal_state_from_st_nr(): void {
        // Berlin (11)
        $this->assertEquals('BE', TaxNumberHelper::getFederalStateFromStNr('1121081508150'));

        // NRW (5)
        $this->assertEquals('NW', TaxNumberHelper::getFederalStateFromStNr('5133081508159'));

        // Bayern (9)
        $this->assertEquals('BY', TaxNumberHelper::getFederalStateFromStNr('9123456789012'));

        // Hamburg (22)
        $this->assertEquals('HH', TaxNumberHelper::getFederalStateFromStNr('2212345678901'));
    }

    public function test_get_federal_state_from_st_nr_with_unknown(): void {
        $this->assertNull(TaxNumberHelper::getFederalStateFromStNr(''));
        $this->assertNull(TaxNumberHelper::getFederalStateFromStNr('0'));
    }

    // ========================================
    // Konvertierungs-Tests
    // ========================================

    public function test_to_unified_format_with_valid_input(): void {
        // 10-stellige StNr aus NRW
        $result = TaxNumberHelper::toUnifiedFormat('1234567890', 'NW');
        $this->assertNotNull($result);
        $this->assertEquals(13, strlen($result));
    }

    public function test_to_unified_format_with_invalid_state(): void {
        $result = TaxNumberHelper::toUnifiedFormat('1234567890', 'XX');
        $this->assertNull($result);
    }

    public function test_to_unified_format_with_invalid_length(): void {
        $result = TaxNumberHelper::toUnifiedFormat('12345', 'NW');
        $this->assertNull($result);
    }
}
