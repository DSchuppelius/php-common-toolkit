<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddressesDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\VariousAddressesDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\VariousAddresses;
use CommonToolkit\Entities\DATEV\Header\VariousAddressesHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class VariousAddressesDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Div-Adressen.csv';

    public function testCanCreateBuilder(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $this->assertInstanceOf(VariousAddressesDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(VariousAddresses::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(VariousAddressesHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithAddress(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addAddress(
                'DIV500',
                '30000',
                'Testmöbel GmbH',
                'Feldweg 28',
                '90409',
                'Nürnberg'
            )
            ->build();

        $this->assertInstanceOf(VariousAddresses::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Adresswerte
        $adressnummerField = $document->getFieldsByName('Adressnummer')[0];
        $this->assertEquals('DIV500', $adressnummerField->getValue());

        $kontoField = $document->getFieldsByName('Konto')[0];
        $this->assertEquals('30000', $kontoField->getValue());
    }

    public function testBuildWithMultipleAddresses(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addAddress('DIV500', '30000', 'Testmöbel GmbH', 'Feldweg 28', '90409', 'Nürnberg')
            ->addAddress('DIV600', '30000', 'Mustermann', 'Musterweg 5', '90000', 'Nürnberg')
            ->addAddress('DIV700', '30000', 'Testmann, Elke', 'Wiesenweg 125', '90600', 'Fürth')
            ->build();

        $this->assertEquals(3, $document->countRows());

        // Prüfe alle Adressnummern
        $adressen = $document->getFieldsByName('Adressnummer');
        $this->assertEquals('DIV500', $adressen[0]->getValue());
        $this->assertEquals('DIV600', $adressen[1]->getValue());
        $this->assertEquals('DIV700', $adressen[2]->getValue());
    }

    public function testGetStats(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $stats = $builder->getStats();

        $this->assertArrayHasKey('metaHeader_set', $stats);
        $this->assertArrayHasKey('fieldHeader_set', $stats);
        $this->assertArrayHasKey('data_count', $stats);
        $this->assertArrayHasKey('field_count', $stats);

        $this->assertFalse($stats['metaHeader_set']);
        $this->assertFalse($stats['fieldHeader_set']);
        $this->assertEquals(0, $stats['data_count']);
    }

    public function testParseSampleFileAndCompareStructure(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse die echte Sample-Datei
        $csvContent = file_get_contents(self::SAMPLE_FILE);
        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertEquals('DiverseAdressen', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new VariousAddressesDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addAddress('DIV500', '30000', 'Test GmbH', 'Teststr. 1', '12345', 'Teststadt')
            ->build();

        // Vergleiche Feldanzahl der Header
        $this->assertEquals(
            $parsedDocument->getHeader()->countFields(),
            $document->getHeader()->countFields(),
            'Feldanzahl der Header sollte übereinstimmen'
        );
    }

    public function testBuilderMatchesSampleFileData(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Die Sample-Datei enthält 3 Diverse Adressen
        $this->assertEquals(3, $parsedDocument->countRows());

        // Hole erste Adressnummer aus der Sample-Datei
        $adressnummern = $parsedDocument->getFieldsByName('Adressnummer');
        $this->assertNotEmpty($adressnummern);
        $this->assertEquals('"DIV500"', $adressnummern[0]->toString());
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new VariousAddressesDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addAddress('DIV500', '30000', 'Test GmbH', 'Teststr. 1', '12345', 'Teststadt')
            ->build();

        $this->assertInstanceOf(VariousAddresses::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Lese Original-Datei mit automatischer Encoding-Erkennung
        $document = DatevDocumentParser::fromFile(self::SAMPLE_FILE);
        $this->assertInstanceOf(VariousAddresses::class, $document);

        // Original-Content in normalisierten UTF-8 konvertieren für Vergleich
        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $originalContent = mb_convert_encoding($originalContent, 'UTF-8', 'ISO-8859-1');
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        // Output ist bereits UTF-8 intern
        $outputContent = $document->toString(null, null, null, 'UTF-8');
        $outputContent = rtrim($outputContent, "\n");

        // Direkter String-Vergleich: Output muss exakt mit konvertierten Original übereinstimmen
        $this->assertEquals(
            $originalContent,
            $outputContent,
            'Round-Trip: Generierter Output sollte exakt mit Original übereinstimmen'
        );
    }

    public function testRoundTripDataValuesArePreserved(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $document = DatevDocumentParser::fromFile(self::SAMPLE_FILE);
        $outputContent = $document->toString();
        $reparsedDocument = DatevDocumentParser::fromString($outputContent);

        $this->assertEquals($document->getMetaHeader()->getKennzeichen(), $reparsedDocument->getMetaHeader()->getKennzeichen());
        $this->assertEquals($document->getMetaHeader()->getVersionsnummer(), $reparsedDocument->getMetaHeader()->getVersionsnummer());
        $this->assertEquals($document->countRows(), $reparsedDocument->countRows());

        foreach ($document->getRows() as $rowIndex => $originalRow) {
            $reparsedRow = $reparsedDocument->getRow($rowIndex);
            $this->assertNotNull($reparsedRow);
            foreach ($originalRow->getFields() as $fieldIndex => $originalField) {
                $reparsedField = $reparsedRow->getField($fieldIndex);
                $this->assertEquals($originalField->getValue(), $reparsedField->getValue(), "Feld $fieldIndex in Zeile $rowIndex");
            }
        }
    }
}
