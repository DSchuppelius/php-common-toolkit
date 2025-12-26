<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GLAccountDescriptionDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\GLAccountDescriptionDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\GLAccountDescription;
use CommonToolkit\Entities\DATEV\Header\GLAccountDescriptionHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class GLAccountDescriptionDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Sachkontobeschriftungen.csv';

    public function testCanCreateBuilder(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
        $this->assertInstanceOf(GLAccountDescriptionDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(GLAccountDescription::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(GLAccountDescriptionHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithGLAccount(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addGLAccount(
                '1000',
                'Kasse',
                'de-DE',
                'Kasse Hauptbetrieb'
            )
            ->build();

        $this->assertInstanceOf(GLAccountDescription::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Kontowerte
        $kontoField = $document->getFieldsByName('Konto')[0];
        $this->assertEquals('1000', $kontoField->getValue());

        $beschriftungField = $document->getFieldsByName('Kontobeschriftung')[0];
        $this->assertEquals('Kasse', $beschriftungField->getValue());
    }

    public function testBuildWithMultipleGLAccounts(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addGLAccount('691', 'Teilzahlungskredit Citan Kastenwagen', 'de-DE', 'Teilzahlungskredit Citan Kastenwagen')
            ->addGLAccount('692', 'Darlehen Masterwood CNC', 'de-DE', 'Darlehen Masterwood CNC')
            ->addGLAccount('1010', 'Kasse Werkstatt', 'de-DE', 'Kasse Werkstatt')
            ->addGLAccount('1100', 'Aareal Bank München', 'de-DE', 'Aareal Bank München')
            ->build();

        $this->assertEquals(4, $document->countRows());

        // Prüfe alle Kontonummern
        $konten = $document->getFieldsByName('Konto');
        $this->assertEquals('691', $konten[0]->getValue());
        $this->assertEquals('692', $konten[1]->getValue());
        $this->assertEquals('1010', $konten[2]->getValue());
        $this->assertEquals('1100', $konten[3]->getValue());
    }

    public function testGetStats(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
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

        $this->assertEquals('Sachkontenbeschriftungen', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new GLAccountDescriptionDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addGLAccount('1000', 'Kasse', 'de-DE', 'Kasse Hauptbetrieb')
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

        // Die Sample-Datei enthält 52 Sachkonten (Zeilen 3-54)
        $this->assertGreaterThan(0, $parsedDocument->countRows());

        // Hole erste Kontobeschriftung aus der Sample-Datei
        $ersteKonten = $parsedDocument->getFieldsByName('Konto');
        $this->assertNotEmpty($ersteKonten);
        $this->assertEquals('691', $ersteKonten[0]->getValue());
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new GLAccountDescriptionDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addGLAccount('1000', 'Kasse', 'de-DE', 'Kasse Hauptbetrieb')
            ->build();

        $this->assertInstanceOf(GLAccountDescription::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        $document = DatevDocumentParser::fromString($originalContent);
        $this->assertInstanceOf(GLAccountDescription::class, $document);

        $outputContent = $document->toString();
        $outputContent = rtrim($outputContent, "\n");

        // Direkter String-Vergleich: Output muss exakt mit Original übereinstimmen
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
