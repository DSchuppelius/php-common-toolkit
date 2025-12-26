<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurringBookingsDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\RecurringBookingsDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\RecurringBookings;
use CommonToolkit\Entities\DATEV\Header\RecurringBookingsHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class RecurringBookingsDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Wiederkehrende-Buchungen.csv';

    public function testCanCreateBuilder(): void {
        $builder = new RecurringBookingsDocumentBuilder();
        $this->assertInstanceOf(RecurringBookingsDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new RecurringBookingsDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(RecurringBookings::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(RecurringBookingsHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithRecurringBooking(): void {
        $builder = new RecurringBookingsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addRecurringBooking(
                488.00,
                'H',
                '980',
                '4360',
                'Gebäudeversicherung',
                '01032020'
            )
            ->build();

        $this->assertInstanceOf(RecurringBookings::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testBuildWithMultipleRecurringBookings(): void {
        $builder = new RecurringBookingsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addRecurringBooking(488.00, 'H', '980', '4360', 'Gebäudeversicherung', '01032020')
            ->addRecurringBooking(333.52, 'H', '980', '4360', 'Brandversicherung', '31082019')
            ->addRecurringBooking(150.00, 'S', '4400', '1200', 'Monatliche Miete', '01012020')
            ->build();

        $this->assertEquals(3, $document->countRows());
    }

    public function testGetStats(): void {
        $builder = new RecurringBookingsDocumentBuilder();
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

        $this->assertEquals('WiederkehrendeBuchungen', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new RecurringBookingsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addRecurringBooking(488.00, 'H', '980', '4360', 'Gebäudeversicherung', '01032020')
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

        // Die Sample-Datei enthält 2 wiederkehrende Buchungen
        $this->assertEquals(2, $parsedDocument->countRows());
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new RecurringBookingsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addRecurringBooking(488.00, 'H', '980', '4360', 'Gebäudeversicherung', '01032020')
            ->build();

        $this->assertInstanceOf(RecurringBookings::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        $document = DatevDocumentParser::fromString($originalContent);
        $this->assertInstanceOf(RecurringBookings::class, $document);

        $outputContent = $document->toString();
        $outputContent = rtrim($outputContent, "\n");

        $originalLines = explode("\n", $originalContent);
        $outputLines = explode("\n", $outputContent);

        $this->assertCount(count($originalLines), $outputLines, 'Zeilenanzahl sollte übereinstimmen');
        $this->assertEquals($originalLines[0], $outputLines[0], 'MetaHeader sollte übereinstimmen');

        $originalHeaderFields = count(explode(';', $originalLines[1]));
        $outputHeaderFields = count(explode(';', $outputLines[1]));
        $this->assertEquals($originalHeaderFields, $outputHeaderFields, 'Header-Feldanzahl sollte übereinstimmen');

        for ($i = 2; $i < count($originalLines); $i++) {
            $originalFieldCount = count(explode(';', $originalLines[$i]));
            $outputFieldCount = count(explode(';', $outputLines[$i]));
            $this->assertEquals($originalFieldCount, $outputFieldCount, "Zeile " . ($i + 1) . ": Feldanzahl sollte übereinstimmen");
        }
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
