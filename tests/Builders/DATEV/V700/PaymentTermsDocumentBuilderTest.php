<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTermsDocumentBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV\V700;

use CommonToolkit\Builders\DATEV\V700\PaymentTermsDocumentBuilder;
use CommonToolkit\Entities\DATEV\Documents\PaymentTerms;
use CommonToolkit\Entities\DATEV\Header\PaymentTermsHeaderLine;
use CommonToolkit\Parsers\DatevDocumentParser;
use Tests\Contracts\BaseTestCase;

class PaymentTermsDocumentBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../../.samples/DATEV/EXTF_Zahlungsbedingungen.csv';

    public function testCanCreateBuilder(): void {
        $builder = new PaymentTermsDocumentBuilder();
        $this->assertInstanceOf(PaymentTermsDocumentBuilder::class, $builder);
    }

    public function testBuildMinimalDocument(): void {
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(12345, 67890)
            ->build();

        $this->assertInstanceOf(PaymentTerms::class, $document);
        $this->assertTrue($document->hasHeader());
        $this->assertInstanceOf(PaymentTermsHeaderLine::class, $document->getHeader());
    }

    public function testBuildWithPaymentTerm(): void {
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addPaymentTerm(
                '10',
                '30 Tage 2,0%; 60 Tage netto',
                1,
                60,
                30,
                2200
            )
            ->build();

        $this->assertInstanceOf(PaymentTerms::class, $document);
        $this->assertEquals(1, $document->countRows());

        // Prüfe Zahlungsbedingungswerte
        $nummerField = $document->getFieldsByName('Nummer')[0];
        $this->assertEquals('10', $nummerField->getValue());
    }

    public function testBuildWithMultiplePaymentTerms(): void {
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addPaymentTerm('10', '30 Tage 2,0%; 60 Tage netto', 1, 60, 30, 200)
            ->addPaymentTerm('11', '5 Tage 3,0%; 30 Tage netto', 1, 30, 5, 300)
            ->addPaymentTerm('12', '10 Tage 3,0%; 20 Tage netto', 1, 20, 10, 300)
            ->addPaymentTerm('13', '14 Tage netto', 1, 14, null, null)
            ->build();

        $this->assertEquals(4, $document->countRows());

        // Prüfe alle Zahlungsbedingungen
        $nummern = $document->getFieldsByName('Nummer');
        $this->assertEquals('10', $nummern[0]->getValue());
        $this->assertEquals('11', $nummern[1]->getValue());
        $this->assertEquals('12', $nummern[2]->getValue());
        $this->assertEquals('13', $nummern[3]->getValue());
    }

    public function testGetStats(): void {
        $builder = new PaymentTermsDocumentBuilder();
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

        $this->assertEquals('Zahlungsbedingungen', $analysis['format_type']);
        $this->assertEquals(700, $analysis['version']);
        $this->assertTrue($analysis['supported']);

        // Parse das Dokument
        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Erstelle ein ähnliches Dokument mit dem Builder
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addPaymentTerm('10', '30 Tage netto', 1, 60, 30, 200)
            ->build();

        // Vergleiche Feldanzahl der Header (beide sollten 31 Felder haben)
        $this->assertEquals(
            $parsedDocument->getHeader()->countFields(),
            $document->getHeader()->countFields(),
            'Feldanzahl der Header sollte übereinstimmen'
        );
        $this->assertEquals(31, $document->getHeader()->countFields(), 'PaymentTerms V700 sollte 31 Felder haben');
    }

    public function testBuilderMatchesSampleFileData(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $parsedDocument = DatevDocumentParser::fromFile(self::SAMPLE_FILE);

        // Die Sample-Datei enthält 5 Zahlungsbedingungen
        $this->assertEquals(5, $parsedDocument->countRows());

        // Hole erstes Feld aus der Sample-Datei - Note: Sample verwendet 'Nummer', Enum verwendet 'Zahlungsbedingung'
        // In der Sample-Datei ist der erste Eintrag '10'
        $firstRow = $parsedDocument->getRow(0);
        $this->assertNotNull($firstRow);
        $this->assertEquals('10', $firstRow->getFields()[0]->getValue());
    }

    public function testBuilderCreatesValidDocument(): void {
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addPaymentTerm('10', '30 Tage netto', 1, 60, 30, 200)
            ->build();

        $this->assertInstanceOf(PaymentTerms::class, $document);
        $this->assertEquals(1, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        $document = DatevDocumentParser::fromString($originalContent);
        $this->assertInstanceOf(PaymentTerms::class, $document);

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

        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $originalLines = explode("\n", str_replace("\r\n", "\n", $originalContent));

        // Die Sample-Datei hat 31 Felder (Zeitraum-basiert)
        $originalHeaderFields = count(explode(';', $originalLines[1]));
        $this->assertEquals(31, $originalHeaderFields, 'PaymentTerms V700 Sample-Datei sollte 31 Felder haben');

        $document = DatevDocumentParser::fromString($originalContent);
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

    public function testRoundTripBuilderCreatedDocument(): void {
        // Erstelle ein Dokument mit dem Builder und prüfe, ob es korrekt Round-Trip verarbeitet wird
        // Hinweis: Verwende keine Semikolons in den Werten, da diese als Delimiter interpretiert werden
        $builder = new PaymentTermsDocumentBuilder();
        $document = $builder
            ->setClient(29098, 55003)
            ->addPaymentTerm('10', '30 Tage netto', 1, 30, 30, 200)
            ->addPaymentTerm('20', '14 Tage 3 Prozent', 1, 14, 14, 300)
            ->build();

        // In String umwandeln und erneut parsen
        $csvContent = $document->toString();
        $reparsedDocument = DatevDocumentParser::fromString($csvContent);

        // Prüfe dass die Struktur erhalten bleibt
        $this->assertEquals($document->countRows(), $reparsedDocument->countRows());
        $this->assertEquals(
            $document->getHeader()->countFields(),
            $reparsedDocument->getHeader()->countFields()
        );

        // Prüfe MetaHeader
        $this->assertEquals(
            $document->getMetaHeader()->getKennzeichen(),
            $reparsedDocument->getMetaHeader()->getKennzeichen()
        );

        // Prüfe Datenwerte
        foreach ($document->getRows() as $rowIndex => $originalRow) {
            $reparsedRow = $reparsedDocument->getRow($rowIndex);
            $this->assertNotNull($reparsedRow);
            foreach ($originalRow->getFields() as $fieldIndex => $originalField) {
                $reparsedField = $reparsedRow->getField($fieldIndex);
                $this->assertEquals(
                    $originalField->getValue(),
                    $reparsedField->getValue(),
                    "Feld $fieldIndex in Zeile $rowIndex"
                );
            }
        }
    }
}
