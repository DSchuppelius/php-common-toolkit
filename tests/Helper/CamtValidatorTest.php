<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtValidatorTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Helper;

use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Helper\Data\CamtValidator;
use CommonToolkit\Helper\Data\ValidationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für den CAMT-Validator.
 */
class CamtValidatorTest extends BaseTestCase {
    private string $samplesPath;
    private string $xsdPath;

    protected function setUp(): void {
        parent::setUp();
        $this->samplesPath = dirname(__DIR__, 2) . '/.samples/Banking/CAMT/';
        $this->xsdPath = dirname(__DIR__, 2) . '/data/xsd/camt/';
    }

    public function testGetAvailableSchemas(): void {
        $schemas = CamtValidator::getAvailableSchemas();

        $this->assertIsArray($schemas);
        $this->assertArrayHasKey('camt.052', $schemas);
        $this->assertArrayHasKey('camt.053', $schemas);

        // CAMT.052 Versionen prüfen
        $this->assertArrayHasKey('02', $schemas['camt.052']);
        $this->assertEquals('camt.052.001.02.xsd', $schemas['camt.052']['02']);

        // CAMT.053 Versionen prüfen
        $this->assertArrayHasKey('02', $schemas['camt.053']);
        $this->assertEquals('camt.053.001.02.xsd', $schemas['camt.053']['02']);
    }

    public function testDetectVersionFromNamespace(): void {
        $xml02 = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02"></Document>';
        $xml08 = '<?xml version="1.0"?><Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.08"></Document>';

        $this->assertEquals(CamtVersion::V02, CamtValidator::detectVersion($xml02));
        $this->assertEquals(CamtVersion::V08, CamtValidator::detectVersion($xml08));
    }

    public function testValidateCamt052Sample(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $result = CamtValidator::validateFile($file);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertEquals(CamtType::CAMT052, $result->type);

        // Ausgabe für Debugging
        if (!$result->isValid()) {
            echo "\nValidierungsfehler für " . basename($file) . ":\n";
            foreach ($result->getErrors() as $error) {
                echo "  - $error\n";
            }
        }
    }

    public function testValidateCamt053Sample(): void {
        $file = $this->samplesPath . '11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $result = CamtValidator::validateFile($file);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertEquals(CamtType::CAMT053, $result->type);
    }

    public function testValidateAllCamt052Samples(): void {
        $files = glob($this->samplesPath . '*camt.052*.xml');

        foreach ($files as $file) {
            $result = CamtValidator::validateFile($file);

            $this->assertInstanceOf(ValidationResult::class, $result, basename($file));
            $this->assertEquals(CamtType::CAMT052, $result->type, basename($file));
        }
    }

    public function testValidateAllCamt053Samples(): void {
        $files = glob($this->samplesPath . '*camt.053*.xml');

        foreach ($files as $file) {
            $result = CamtValidator::validateFile($file);

            $this->assertInstanceOf(ValidationResult::class, $result, basename($file));
            $this->assertEquals(CamtType::CAMT053, $result->type, basename($file));
        }
    }

    public function testValidateInvalidXml(): void {
        $invalidXml = '<?xml version="1.0"?><Document><Invalid></Document>';

        $result = CamtValidator::validate($invalidXml);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    public function testValidateUnknownType(): void {
        $unknownXml = '<?xml version="1.0"?><Unknown xmlns="urn:unknown"></Unknown>';

        $result = CamtValidator::validate($unknownXml);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Unbekannter CAMT-Dokumenttyp', $result->getFirstError());
    }

    public function testValidateFileNotFound(): void {
        $result = CamtValidator::validateFile('/nonexistent/path/file.xml');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Datei nicht gefunden', $result->getFirstError());
    }

    public function testValidationResultMethods(): void {
        $result = new ValidationResult(
            valid: false,
            errors: ['Fehler 1', 'Fehler 2', 'Fehler 3'],
            type: CamtType::CAMT053,
            version: CamtVersion::V02,
            xsdFile: '/path/to/schema.xsd'
        );

        $this->assertFalse($result->isValid());
        $this->assertEquals(3, $result->countErrors());
        $this->assertEquals('Fehler 1', $result->getFirstError());
        $this->assertStringContainsString('Fehler 2', $result->getErrorsAsString());
        $this->assertEquals(CamtType::CAMT053, $result->type);
        $this->assertEquals(CamtVersion::V02, $result->version);
        $this->assertEquals('/path/to/schema.xsd', $result->xsdFile);
    }

    public function testValidateWithExplicitType(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $result = CamtValidator::validateFile($file, CamtType::CAMT052);

        $this->assertEquals(CamtType::CAMT052, $result->type);
    }

    public function testValidateWithExplicitVersion(): void {
        $file = $this->samplesPath . '01_EBICS_camt.052_Bareinzahlung_auf_Dot.xml';
        if (!file_exists($file)) {
            $this->markTestSkipped('Sample file not found');
        }

        $result = CamtValidator::validateFile($file, null, CamtVersion::V02);

        $this->assertEquals(CamtVersion::V02, $result->version);
    }

    #[DataProvider('xsdFilesProvider')]
    public function testXsdFileExists(string $filename): void {
        $file = $this->xsdPath . $filename;
        $this->assertFileExists($file, "XSD-Datei $filename sollte existieren");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function xsdFilesProvider(): array {
        return [
            'camt.052.001.02' => ['camt.052.001.02.xsd'],
            'camt.052.001.06' => ['camt.052.001.06.xsd'],
            'camt.052.001.08' => ['camt.052.001.08.xsd'],
            'camt.052.001.10' => ['camt.052.001.10.xsd'],
            'camt.052.001.12' => ['camt.052.001.12.xsd'],
            'camt.053.001.02' => ['camt.053.001.02.xsd'],
            'camt.053.001.04' => ['camt.053.001.04.xsd'],
            'camt.053.001.08' => ['camt.053.001.08.xsd'],
            'camt.053.001.10' => ['camt.053.001.10.xsd'],
            'camt.053.001.12' => ['camt.053.001.12.xsd'],
        ];
    }
}
