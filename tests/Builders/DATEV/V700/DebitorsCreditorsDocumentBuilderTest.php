<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DebitorsCreditorsDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\DebitorsCreditorsDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\DebitorsCreditors;
use CommonToolkit\Entities\DATEV\Header\DebitorsCreditorsHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class DebitorsCreditorsDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_DebKred_Stamm.csv';

    public function testCanCreateBuilder(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $this->assertInstanceOf(DebitorsCreditorsDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(DebitorsCreditors::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(DebitorsCreditorsHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithDebitorCreditor(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addDebitorCreditor(
                '10000',
                'Testfirma GmbH',
                'Teststraße 123',
                '90000',
                'Nürnberg'
            )
            ->build();

        $this->assertInstanceOf(DebitorsCreditors::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Kontowerte
        $kontoField = $document->getFieldsByName('Konto')[0];
        $this->assertEquals('10000', $kontoField->getValue());

        $nameField = $document->getFieldsByName('Name (Adressattyp Unternehmen)')[0];
        $this->assertEquals('Testfirma GmbH', $nameField->getValue());
    }

    public function testBuildWithMultipleDebitorsCreditors(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addDebitorCreditor('10000', 'Möbel Testgruber', 'Nelkenteststraße 125', '90482', 'Nürnberg')
            ->addDebitorCreditor('20000', 'Einrichtungshaus Muster', 'Feldgasse 15', '90409', 'Nürnberg')
            ->addDebitorCreditor('30000', 'Mustermann Hans', 'Musterweg 14b', '90489', 'Nürnberg')
            ->build();

        $this->assertEquals(3, $document->countRows());

        // Prüfe alle Kontonummern
        $konten = $document->getFieldsByName('Konto');
        $this->assertEquals('10000', $konten[0]->getValue());
        $this->assertEquals('20000', $konten[1]->getValue());
        $this->assertEquals('30000', $konten[2]->getValue());
    }

    public function testGetStats(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $stats = $builder->getStats();

        $this->assertArrayHasKey('metaHeader_set', $stats);
        $this->assertArrayHasKey('fieldHeader_set', $stats);
        $this->assertArrayHasKey('data_count', $stats);
        $this->assertArrayHasKey('field_count', $stats);

        $this->assertFalse($stats['metaHeader_set']);
        $this->assertFalse($stats['fieldHeader_set']);
        $this->assertEquals(0, $stats['data_count']);

        // Nach Hinzufügen von Daten
        $builder->setClient(12345, 67890);
        $builder->addDebitorCreditor('10000', 'Test GmbH', 'Teststr. 1', '12345', 'Teststadt');

        $stats = $builder->getStats();
        $this->assertTrue($stats['metaHeader_set']);
        $this->assertTrue($stats['fieldHeader_set']);
        $this->assertEquals(1, $stats['data_count']);
    }

    public function testParseSampleFileAndCompareStructure(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse die echte Sample-Datei
        $csvContent = file_get_contents(self::SAMPLE_FILE);
        $analysis = DatevDocumentParser::analyzeFormat($csvContent);

        $this->assertEquals('DebitorenKreditoren', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new DebitorsCreditorsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addDebitorCreditor('10000', 'Test GmbH', 'Teststr. 1', '12345', 'Teststadt')
            ->build();

        // Vergleiche Feldanzahl der Header
        $this->assertEquals(
            $parsedDocument->getHeader()->countFields(),
            $document->getHeader()->countFields(),
            'Feldanzahl der Header sollte übereinstimmen'
        );
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new DebitorsCreditorsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addDebitorCreditor('10000', 'Test GmbH', 'Teststr. 1', '12345', 'Teststadt')
            ->build();

        // Prüfe dass das Dokument gültig ist
        $this->assertInstanceOf(DebitorsCreditors::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Lese Original-Datei mit automatischer Encoding-Erkennung
        $document = DatevDocumentParser::fromFile(self::SAMPLE_FILE);
        $this->assertInstanceOf(DebitorsCreditors::class, $document);

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
