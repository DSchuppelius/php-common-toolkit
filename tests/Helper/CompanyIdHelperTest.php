<?php
/*
 * Created on   : Sat Dec 28 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CompanyIdHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

use CommonToolkit\Helper\Data\CompanyIdHelper;
use Tests\Contracts\BaseTestCase;

class CompanyIdHelperTest extends BaseTestCase {
    // ========================================
    // Handelsregisternummer Tests
    // ========================================

    public function testIsHRNumberWithValidFormats(): void {
        $this->assertTrue(CompanyIdHelper::isHRNumber('HRB 12345'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('HRA 1234'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('HRB12345'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('GnR 123'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('PR 12345'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('VR 1234'));
        $this->assertTrue(CompanyIdHelper::isHRNumber('HRB 12345B')); // Mit Suffix
    }

    public function testIsHRNumberWithInvalidFormats(): void {
        $this->assertFalse(CompanyIdHelper::isHRNumber(null));
        $this->assertFalse(CompanyIdHelper::isHRNumber(''));
        $this->assertFalse(CompanyIdHelper::isHRNumber('12345')); // Kein Präfix
        $this->assertFalse(CompanyIdHelper::isHRNumber('XYZ 12345')); // Ungültiges Präfix
        $this->assertFalse(CompanyIdHelper::isHRNumber('HRB 1234567')); // Zu lang (7 Ziffern)
        $this->assertFalse(CompanyIdHelper::isHRNumber('HRB')); // Keine Nummer
    }

    public function testParseHRNumber(): void {
        $result = CompanyIdHelper::parseHRNumber('HRB 12345');
        $this->assertEquals('HRB', $result['prefix']);
        $this->assertEquals('12345', $result['number']);
        $this->assertNull($result['suffix']);

        $result = CompanyIdHelper::parseHRNumber('HRA 1234B');
        $this->assertEquals('HRA', $result['prefix']);
        $this->assertEquals('1234', $result['number']);
        $this->assertEquals('B', $result['suffix']);
    }

    public function testParseHRNumberWithInvalid(): void {
        $result = CompanyIdHelper::parseHRNumber('invalid');
        $this->assertNull($result['prefix']);
        $this->assertNull($result['number']);
    }

    public function testFormatHRNumber(): void {
        $this->assertEquals('HRB 12345', CompanyIdHelper::formatHRNumber('HRB12345'));
        $this->assertEquals('HRA 1234 B', CompanyIdHelper::formatHRNumber('HRA1234B'));
    }

    // ========================================
    // LEI Tests
    // ========================================

    public function testIsLEIWithValidFormat(): void {
        $this->assertTrue(CompanyIdHelper::isLEI('529900RDKKCVES7WJZ75'));
        $this->assertTrue(CompanyIdHelper::isLEI('5299 00RD KKCV ES7W JZ75')); // Mit Leerzeichen
    }

    public function testIsLEIWithInvalidFormat(): void {
        $this->assertFalse(CompanyIdHelper::isLEI(null));
        $this->assertFalse(CompanyIdHelper::isLEI(''));
        $this->assertFalse(CompanyIdHelper::isLEI('529900RDKKCVES7WJZ7')); // Zu kurz (19)
        $this->assertFalse(CompanyIdHelper::isLEI('529900RDKKCVES7WJZ756')); // Zu lang (21)
        $this->assertFalse(CompanyIdHelper::isLEI('529900RDKKCVES7WJZ7!')); // Ungültiges Zeichen
    }

    public function testValidateLEIWithValidChecksum(): void {
        // Bekannte gültige LEIs (aus öffentlichen Registern)
        $this->assertTrue(CompanyIdHelper::validateLEI('7LTWFZYICNSX8D621K86')); // Apple Inc.
        $this->assertTrue(CompanyIdHelper::validateLEI('HWUPKR0MPOU8FGXBT394')); // Deutsche Bank
    }

    public function testValidateLEIWithInvalidChecksum(): void {
        $this->assertFalse(CompanyIdHelper::validateLEI('529900RDKKCVES7WJZ70'));
        $this->assertFalse(CompanyIdHelper::validateLEI('12345678901234567890'));
    }

    public function testFormatLEI(): void {
        $this->assertEquals(
            '5299 00RD KKCV ES7W JZ75',
            CompanyIdHelper::formatLEI('529900RDKKCVES7WJZ75')
        );
    }

    // ========================================
    // D-U-N-S Tests
    // ========================================

    public function testIsDUNSWithValidFormat(): void {
        $this->assertTrue(CompanyIdHelper::isDUNS('123456789'));
        $this->assertTrue(CompanyIdHelper::isDUNS('12-345-6789')); // Mit Bindestrichen
        $this->assertTrue(CompanyIdHelper::isDUNS('12 345 6789')); // Mit Leerzeichen
    }

    public function testIsDUNSWithInvalidFormat(): void {
        $this->assertFalse(CompanyIdHelper::isDUNS(null));
        $this->assertFalse(CompanyIdHelper::isDUNS(''));
        $this->assertFalse(CompanyIdHelper::isDUNS('12345678')); // Zu kurz (8)
        $this->assertFalse(CompanyIdHelper::isDUNS('1234567890')); // Zu lang (10)
        $this->assertFalse(CompanyIdHelper::isDUNS('12345678A')); // Buchstabe
    }

    public function testFormatDUNS(): void {
        $this->assertEquals('12-345-6789', CompanyIdHelper::formatDUNS('123456789'));
    }

    // ========================================
    // GLN Tests
    // ========================================

    public function testIsGLNWithValidFormat(): void {
        $this->assertTrue(CompanyIdHelper::isGLN('4012345000016'));
        $this->assertTrue(CompanyIdHelper::isGLN('4005999999997'));
    }

    public function testIsGLNWithInvalidFormat(): void {
        $this->assertFalse(CompanyIdHelper::isGLN(null));
        $this->assertFalse(CompanyIdHelper::isGLN(''));
        $this->assertFalse(CompanyIdHelper::isGLN('401234500001')); // Zu kurz (12)
        $this->assertFalse(CompanyIdHelper::isGLN('40123450000161')); // Zu lang (14)
    }

    public function testValidateGLNWithValidChecksum(): void {
        $this->assertTrue(CompanyIdHelper::validateGLN('4012345000016'));
        $this->assertTrue(CompanyIdHelper::validateGLN('4005999999997'));
    }

    public function testValidateGLNWithInvalidChecksum(): void {
        $this->assertFalse(CompanyIdHelper::validateGLN('4012345000010'));
        $this->assertFalse(CompanyIdHelper::validateGLN('4012345000011'));
    }

    public function testFormatGLN(): void {
        $this->assertEquals('4-012345-00001-6', CompanyIdHelper::formatGLN('4012345000016'));
    }

    // ========================================
    // W-IdNr Tests
    // ========================================

    public function testIsWIdNrWithValidFormat(): void {
        $this->assertTrue(CompanyIdHelper::isWIdNr('DE123456789'));
        $this->assertTrue(CompanyIdHelper::isWIdNr('de123456789')); // Kleinbuchstaben
        $this->assertTrue(CompanyIdHelper::isWIdNr('DE 123 456 789')); // Mit Leerzeichen
    }

    public function testIsWIdNrWithInvalidFormat(): void {
        $this->assertFalse(CompanyIdHelper::isWIdNr(null));
        $this->assertFalse(CompanyIdHelper::isWIdNr(''));
        $this->assertFalse(CompanyIdHelper::isWIdNr('DE12345678')); // Zu kurz
        $this->assertFalse(CompanyIdHelper::isWIdNr('DE1234567890')); // Zu lang
        $this->assertFalse(CompanyIdHelper::isWIdNr('FR123456789')); // Falscher Ländercode
    }

    // ========================================
    // EAN Tests
    // ========================================

    public function testIsEANWithValidFormats(): void {
        $this->assertTrue(CompanyIdHelper::isEAN('12345678', 8)); // EAN-8
        $this->assertTrue(CompanyIdHelper::isEAN('123456789012', 12)); // UPC-A
        $this->assertTrue(CompanyIdHelper::isEAN('1234567890123', 13)); // EAN-13
        $this->assertTrue(CompanyIdHelper::isEAN('12345678901234', 14)); // GTIN-14
    }

    public function testIsEANWithInvalidFormats(): void {
        $this->assertFalse(CompanyIdHelper::isEAN(null));
        $this->assertFalse(CompanyIdHelper::isEAN(''));
        $this->assertFalse(CompanyIdHelper::isEAN('123456789012', 13)); // Falsche Länge
        $this->assertFalse(CompanyIdHelper::isEAN('12345', 5)); // Ungültige Länge
    }

    public function testValidateEANWithValidChecksum(): void {
        $this->assertTrue(CompanyIdHelper::validateEAN('4006381333931')); // EAN-13
        $this->assertTrue(CompanyIdHelper::validateEAN('96385074')); // EAN-8
    }

    public function testValidateEANWithInvalidChecksum(): void {
        $this->assertFalse(CompanyIdHelper::validateEAN('4006381333932'));
        $this->assertFalse(CompanyIdHelper::validateEAN('96385075'));
    }

    // ========================================
    // Normalisierungs-Tests
    // ========================================

    public function testNormalizeLEI(): void {
        $this->assertEquals('529900RDKKCVES7WJZ75', CompanyIdHelper::normalizeLEI('5299 00RD KKCV ES7W JZ75'));
        $this->assertEquals('529900RDKKCVES7WJZ75', CompanyIdHelper::normalizeLEI('529900rdkkcves7wjz75'));
    }

    public function testNormalizeDUNS(): void {
        $this->assertEquals('123456789', CompanyIdHelper::normalizeDUNS('12-345-6789'));
        $this->assertEquals('123456789', CompanyIdHelper::normalizeDUNS('12 345 6789'));
    }
}
