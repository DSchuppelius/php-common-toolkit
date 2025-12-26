<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\BookingDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BookingBatch;
use CommonToolkit\Entities\DATEV\Header\BookingBatchHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use DateTimeImmutable;
use RuntimeException;
use Tests\Contracts\BaseTestCase;

class BookingDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Buchungsstapel.csv';

    public function testCanCreateBuilder(): void {
        $builder = new BookingDocumentBuilder();
        $this->assertInstanceOf(BookingDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new BookingDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(BookingBatch::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(BookingBatchHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithSimpleBooking(): void {
        $builder = new BookingDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->setDateRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-08-31'))
            ->setDescription('Test Buchungsstapel')
            ->addSimpleBooking(
                100.18,
                'S',
                '48400',
                '8401',
                new DateTimeImmutable('2024-03-01'),
                'TEST001',
                'Test Anzahlung'
            )
            ->build();

        $this->assertInstanceOf(BookingBatch::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Buchungswerte
        $umsatzField = $document->getFieldsByName('Umsatz (ohne Soll/Haben-Kz)')[0];
        $this->assertEquals('100,18', $umsatzField->getValue());

        $kontoField = $document->getFieldsByName('Konto')[0];
        $this->assertEquals('48400', $kontoField->getValue());
    }

    public function testBuildWithMultipleBookings(): void {
        $builder = new BookingDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addSimpleBooking(100.00, 'S', '4400', '1200', '2024-01-15', 'RE001', 'Büromaterial')
            ->addSimpleBooking(250.50, 'H', '8400', '10000', '2024-01-20', 'AR001', 'Verkauf Möbel')
            ->addSimpleBooking(75.30, 'S', '4530', '1100', '2024-01-25', 'TK001', 'Benzin')
            ->build();

        $this->assertEquals(3, $document->countRows());

        // Prüfe alle Buchungstexte
        $buchungstexte = $document->getFieldsByName('Buchungstext');
        $this->assertEquals('Büromaterial', $buchungstexte[0]->getValue());
        $this->assertEquals('Verkauf Möbel', $buchungstexte[1]->getValue());
        $this->assertEquals('Benzin', $buchungstexte[2]->getValue());
    }

    public function testBuildWithRawDataLine(): void {
        $builder = new BookingDocumentBuilder();
        $builder->setFieldHeader();

        // Erstelle eine DataLine manuell
        $fieldCount = 125; // V700 hat 125 Felder
        $values = array_fill(0, $fieldCount, '');
        $values[0] = '500,00'; // Umsatz
        $values[1] = 'H';      // Soll/Haben
        $values[6] = '8400';   // Konto
        $values[7] = '10100';  // Gegenkonto
        $values[9] = '1501';   // Belegdatum
        $values[13] = 'Manuell erstellte Buchung';

        $dataLine = new DataLine($values, ';', '"');

        $document = $builder
            ->setClient(12345, 67890)
            ->addBooking($dataLine)
            ->build();

        $this->assertEquals(1, $document->countRows());
    }

    public function testGetStats(): void {
        $builder = new BookingDocumentBuilder();
        $stats = $builder->getStats();

        $this->assertArrayHasKey('metaHeader_set', $stats);
        $this->assertArrayHasKey('fieldHeader_set', $stats);
        $this->assertArrayHasKey('booking_count', $stats);
        $this->assertArrayHasKey('field_count', $stats);

        $this->assertFalse($stats['metaHeader_set']);
        $this->assertFalse($stats['fieldHeader_set']);
        $this->assertEquals(0, $stats['booking_count']);

        // Nach Hinzufügen von Buchungen
        $builder->setClient(12345, 67890);
        $builder->addSimpleBooking(100.00, 'S', '4400', '1200', '2024-01-15', 'RE001', 'Test');

        $stats = $builder->getStats();
        $this->assertTrue($stats['metaHeader_set']);
        $this->assertTrue($stats['fieldHeader_set']);
        $this->assertEquals(1, $stats['booking_count']);
        $this->assertEquals(125, $stats['field_count']);
    }

    public function testValidationFailsWithWrongFieldCount(): void {
        $builder = new BookingDocumentBuilder();
        $builder->setFieldHeader();

        // DataLine mit falscher Feldanzahl
        $values = ['100,00', 'S', '4400']; // Nur 3 Felder statt 125
        $dataLine = new DataLine($values, ';', '"');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Buchungszeile 0 hat 3 Felder, erwartet: 125');

        $builder
            ->setClient(12345, 67890)
            ->addBooking($dataLine)
            ->build();
    }

    public function testBuilderOutputMatchesParsedSampleFile(): void {
        // Prüfe ob Sample-Datei existiert
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse die echte Sample-Datei
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new BookingDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->setDateRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-08-31'))
            ->setDescription('Buchungsstapel')
            ->addSimpleBooking(100.18, 'S', '48400', '8401', '2024-03-01', '', 'Test Anzahlung')
            ->build();

        // Vergleiche Struktur
        $this->assertEquals(
            $parsedDocument->getHeader()->countFields(),
            $document->getHeader()->countFields(),
            'Feldanzahl sollte übereinstimmen'
        );
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new BookingDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addSimpleBooking(100.00, 'S', '4400', '1200', '2024-01-15', 'RE001', 'Test')
            ->build();

        // Prüfe dass das Dokument gültig ist
        $this->assertInstanceOf(BookingBatch::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        // Prüfe ob Sample-Datei existiert
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Lies die Original-Datei ein
        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $this->assertNotEmpty($originalContent, 'Sample-Datei darf nicht leer sein');

        // Normalisiere Zeilenenden
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        // Parse das Dokument
        $document = DatevDocumentParser::fromString($originalContent);
        $this->assertInstanceOf(BookingBatch::class, $document);

        // Gib das Dokument als String aus
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
        // Prüfe ob Sample-Datei existiert
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse das Dokument
        $document = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Gib es als String aus und parse erneut
        $outputContent = $document->toString();
        $reparsedDocument = DatevDocumentParser::fromString($outputContent);

        // Prüfe MetaHeader-Werte
        $originalMeta = $document->getMetaHeader();
        $reparsedMeta = $reparsedDocument->getMetaHeader();

        $this->assertEquals(
            $originalMeta->getKennzeichen(),
            $reparsedMeta->getKennzeichen(),
            'MetaHeader Kennzeichen sollte übereinstimmen'
        );
        $this->assertEquals(
            $originalMeta->getVersionsnummer(),
            $reparsedMeta->getVersionsnummer(),
            'MetaHeader Versionsnummer sollte übereinstimmen'
        );
        $this->assertEquals(
            $originalMeta->getFormatkategorie(),
            $reparsedMeta->getFormatkategorie(),
            'MetaHeader Formatkategorie sollte übereinstimmen'
        );

        // Prüfe Datenzeilen-Anzahl
        $this->assertEquals(
            $document->countRows(),
            $reparsedDocument->countRows(),
            'Anzahl Datenzeilen sollte übereinstimmen'
        );

        // Prüfe einzelne Datenwerte
        foreach ($document->getRows() as $rowIndex => $originalRow) {
            $reparsedRow = $reparsedDocument->getRow($rowIndex);
            $this->assertNotNull($reparsedRow, "Zeile $rowIndex sollte existieren");

            foreach ($originalRow->getFields() as $fieldIndex => $originalField) {
                $reparsedField = $reparsedRow->getField($fieldIndex);
                $this->assertNotNull($reparsedField, "Feld $fieldIndex in Zeile $rowIndex sollte existieren");
                $this->assertEquals(
                    $originalField->getValue(),
                    $reparsedField->getValue(),
                    sprintf('Feld %d in Zeile %d sollte übereinstimmen', $fieldIndex, $rowIndex + 1)
                );
            }
        }
    }
}
