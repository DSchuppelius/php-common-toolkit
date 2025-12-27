<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NaturalStackDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\NaturalStackDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\NaturalStack;
use CommonToolkit\Entities\DATEV\Header\NaturalStackHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class NaturalStackDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Naturalstapel.csv';

    public function testCanCreateBuilder(): void {
        $builder = new NaturalStackDocumentBuilder();
        $this->assertInstanceOf(NaturalStackDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(NaturalStack::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(NaturalStackHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithNaturalBooking(): void {
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55314)
            ->addNaturalBooking(
                '339',      // Textschlüssel
                '2',        // Art
                '2802',     // Datum (TTMM)
                null,       // Stück
                450,        // Gewicht
                '',         // Beleg
                'Milch'     // Text
            )
            ->build();

        $this->assertInstanceOf(NaturalStack::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Textschlüssel
        $textschluesselField = $document->getFieldsByName('Textschlüssel')[0];
        $this->assertEquals('339', $textschluesselField->getValue());
    }

    public function testBuildWithMultipleNaturalBookings(): void {
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55314)
            ->addNaturalBooking('339', '2', '2802', null, 450, '', 'Milch')
            ->addNaturalBooking('100', '21', '1503', 10, null, 'BE001', 'Rinder Versetzung', '200')
            ->addNaturalBooking('150', '24', '2005', 5, null, '', 'Futter')
            ->build();

        $this->assertEquals(3, $document->countRows());
    }

    public function testGetStats(): void {
        $builder = new NaturalStackDocumentBuilder();
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

        $this->assertEquals('NaturalStapel', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55314)
            ->addNaturalBooking('339', '2', '2802', null, 450, '', 'Milch')
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

        // Die Sample-Datei enthält 1 Natural-Buchung
        $this->assertEquals(1, $parsedDocument->countRows());

        // Hole ersten Textschlüssel aus der Sample-Datei
        $textschluessel = $parsedDocument->getFieldsByName('Textschlüssel');
        $this->assertNotEmpty($textschluessel);
        $this->assertEquals('339', $textschluessel[0]->getValue());
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55314)
            ->addNaturalBooking('339', '2', '2802', null, 450, '', 'Milch')
            ->build();

        // Prüfe dass das Dokument gültig ist
        $this->assertInstanceOf(NaturalStack::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testNaturalBookingWithAnFuerTextschluessel(): void {
        $builder = new NaturalStackDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55314)
            ->addNaturalBooking(
                '100',      // Textschlüssel
                '21',       // Art (Versetzung)
                '1503',     // Datum
                10,         // Stück
                null,       // Gewicht
                'BE001',    // Beleg
                'Rinder Versetzung', // Text
                '200'       // An/Für Textschlüssel
            )
            ->build();

        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Lese Original-Datei mit automatischer Encoding-Erkennung
        $document = DatevDocumentParser::fromFile(self::SAMPLE_FILE);
        $this->assertInstanceOf(NaturalStack::class, $document);

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
