<?php
/*
 * Created on   : Wed Dec 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionBuilderTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Builders\DATEV;

use CommonToolkit\Builders\DATEV\BankTransactionBuilder;
use CommonToolkit\Entities\Common\CSV\{DataLine, DataField};
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Entities\DATEV\Header\ASCII\BankTransactionHeaderLine;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use CommonToolkit\Parsers\BankTransactionParser;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für den BankTransactionBuilder.
 */
class BankTransactionBuilderTest extends BaseTestCase {
    private const SAMPLE_FILE = __DIR__ . '/../../../.samples/DATEV/ASCII.csv';

    public function testBuilderCreatesDocumentWithDatevConfig(): void {
        $builder = new BankTransactionBuilder();
        $document = $builder->build();

        $this->assertInstanceOf(BankTransaction::class, $document);
        $this->assertEquals('ASCII-Weiterverarbeitungsdatei', $document->getFormatType());
        $this->assertTrue($document->isAsciiProcessingFormat());
    }

    public function testBuilderWithCustomDelimiterAndEnclosure(): void {
        $builder = new BankTransactionBuilder('|', "'");

        // Füge eine Testzeile hinzu um Output zu generieren
        $sampleData = [
            '"70030000"',
            '"1234567"',
            '"433"',
            '""',
            '"01.01.2024"',
            '"01.01.2024"',
            '"100,00"',
            '"Test Zahler"',
            '""',
            '"50010517"',
            '"12345678"',
            '"Verwendungszweck 1"',
            '""',
            '""',
        ];

        $fields = array_map(fn($value) => new DataField($value, '"'), $sampleData);
        $dataLine = new DataLine($fields, '|', "'");
        $builder->addRow($dataLine);

        $document = $builder->build();

        $this->assertInstanceOf(BankTransaction::class, $document);

        // Test ob Custom-Einstellungen richtig gesetzt sind
        $csvOutput = $document->toString('|', "'");
        $this->assertStringContainsString('|', $csvOutput);
    }

    public function testTruncationStrategyChange(): void {
        $builder = new BankTransactionBuilder();

        // Test mit truncate
        $builder->setTruncationStrategy(TruncationStrategy::TRUNCATE);
        $document1 = $builder->build();

        // Test mit ellipsis
        $builder->setTruncationStrategy(TruncationStrategy::ELLIPSIS);
        $document2 = $builder->build();

        // Test mit none
        $builder->setTruncationStrategy(TruncationStrategy::NONE);
        $document3 = $builder->build();

        // Alle Dokumente sollten gültige BankTransaction-Instanzen sein
        $this->assertInstanceOf(BankTransaction::class, $document1);
        $this->assertInstanceOf(BankTransaction::class, $document2);
        $this->assertInstanceOf(BankTransaction::class, $document3);
    }

    public function testBuilderWithSampleData(): void {
        $builder = new BankTransactionBuilder();

        // Füge Beispieldaten hinzu
        $sampleData = [
            '"70030000"',
            '"1234567"',
            '"433"',
            '"29.12.15"',
            '"29.12.15"',
            '"29.12.15"',
            '10.00',
            '"HANS MUSTERMANN"',
            '""',
            '"80550000"',
            '"7654321"',
            '"Kd.Nr. 12345"',
            '"RECHNUNG v. 12.12.15"',
            '""',
            '""',
            '"051"',
            '"EUR"',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""',
            '""'
        ];

        $fields = array_map(fn($value) => new DataField($value, '"'), $sampleData);
        $dataLine = new DataLine($fields);

        $builder->addLine($dataLine);
        $document = $builder->build();

        $this->assertEquals(1, count($document->getRows()));
        $this->assertTrue($document->hasValidBankData());
    }

    public function testAutomaticHeaderCreation(): void {
        $builder = new BankTransactionBuilder();
        $document = $builder->build();

        $header = $document->getHeader();
        $this->assertNotNull($header);
        $this->assertInstanceOf(BankTransactionHeaderLine::class, $header);

        // Header sollte BankTransactionHeaderLine sein (intern aus Definition erstellt)
        $this->assertTrue($document->isAsciiProcessingFormat());
        $this->assertEquals(34, $document->getHeader()->getDefinition()->getExpectedFieldCount());
    }

    public function testParseSampleFileAndCompareStructure(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse die echte Sample-Datei
        $document = BankTransactionParser::fromFile(self::SAMPLE_FILE);

        $this->assertInstanceOf(BankTransaction::class, $document);
        $this->assertEquals('ASCII-Weiterverarbeitungsdatei', $document->getFormatType());
        $this->assertTrue($document->isAsciiProcessingFormat());
        $this->assertGreaterThan(0, $document->countRows());
    }

    public function testRoundTripParsedDocumentOutputMatchesOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Lies die Original-Datei ein
        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $this->assertNotEmpty($originalContent, 'Sample-Datei darf nicht leer sein');

        // Normalisiere Zeilenenden
        $originalContent = str_replace("\r\n", "\n", $originalContent);
        $originalContent = rtrim($originalContent, "\n");

        // Parse das Dokument
        $document = BankTransactionParser::fromString($originalContent);
        $this->assertInstanceOf(BankTransaction::class, $document);

        // Gib das Dokument als String aus
        $outputContent = BankTransactionParser::toCSV($document);
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

        // Parse das Dokument
        $document = BankTransactionParser::fromFile(self::SAMPLE_FILE);

        // Gib es als String aus und parse erneut
        $outputContent = BankTransactionParser::toCSV($document);
        $reparsedDocument = BankTransactionParser::fromString($outputContent);

        // Prüfe Format-Typ
        $this->assertEquals(
            $document->getFormatType(),
            $reparsedDocument->getFormatType(),
            'FormatType sollte übereinstimmen'
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

    /**
     * Test: addTransaction() erstellt Zeilen mit korrektem DATEV-Quoting.
     */
    public function testAddTransactionCreatesCorrectlyQuotedFields(): void {
        $builder = new BankTransactionBuilder();

        // Füge eine Transaktion mit Werten hinzu
        $builder->addTransaction([
            'BLZ_BIC_KONTOINHABER' => '70030000',
            'KONTONUMMER_IBAN_KONTOINHABER' => '1234567',
            'AUSZUGSNUMMER' => '433',
            'AUSZUGSDATUM' => '29.12.15',
            'VALUTA' => '29.12.15',
            'BUCHUNGSDATUM' => '29.12.15',
            'UMSATZ' => '10.00',
            'AUFTRAGGEBERNAME_1' => 'HANS MUSTERMANN',
            'AUFTRAGGEBERNAME_2' => '',
            'BLZ_BIC_AUFTRAGGEBER' => '80550000',
            'KONTONUMMER_IBAN_AUFTRAGGEBER' => '7654321',
            'VERWENDUNGSZWECK_1' => 'Kd.Nr. 12345',
            'VERWENDUNGSZWECK_2' => 'RECHNUNG v. 12.12.15',
            'VERWENDUNGSZWECK_3' => '',
            'VERWENDUNGSZWECK_4' => '',
            'GESCHAEFTSVORGANGSCODE' => '',
            'WAEHRUNG' => 'EUR',
        ]);

        $document = $builder->build();
        $this->assertEquals(1, count($document->getRows()));

        // Prüfe die erste Zeile
        $row = $document->getRows()[0];
        $fields = $row->getFields();

        // Feld 1 (BLZ_BIC_KONTOINHABER): Alphanumerisch -> muss gequotet sein
        $this->assertTrue($fields[0]->isQuoted(), 'BLZ_BIC_KONTOINHABER sollte gequotet sein');
        $this->assertEquals('70030000', $fields[0]->getValue());

        // Feld 3 (AUSZUGSNUMMER): Numerisch -> nicht gequotet
        $this->assertFalse($fields[2]->isQuoted(), 'AUSZUGSNUMMER sollte nicht gequotet sein');
        $this->assertEquals('433', $fields[2]->getValue());

        // Feld 4 (AUSZUGSDATUM): Datum -> nicht gequotet
        $this->assertFalse($fields[3]->isQuoted(), 'AUSZUGSDATUM sollte nicht gequotet sein');

        // Feld 7 (UMSATZ): Numerisch -> nicht gequotet
        $this->assertFalse($fields[6]->isQuoted(), 'UMSATZ sollte nicht gequotet sein');
        $this->assertEquals('10.00', $fields[6]->getValue());

        // Feld 8 (AUFTRAGGEBERNAME_1): Alphanumerisch -> muss gequotet sein
        $this->assertTrue($fields[7]->isQuoted(), 'AUFTRAGGEBERNAME_1 sollte gequotet sein');
        $this->assertEquals('HANS MUSTERMANN', $fields[7]->getValue());

        // Feld 17 (WAEHRUNG): Alphanumerisch -> muss gequotet sein
        $this->assertTrue($fields[16]->isQuoted(), 'WAEHRUNG sollte gequotet sein');
        $this->assertEquals('EUR', $fields[16]->getValue());
    }

    /**
     * Test: Builder-generierte Zeile entspricht exakt einer geparseten Zeile.
     */
    public function testBuilderOutputMatchesParsedOriginal(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Parse die Original-Datei
        $originalDocument = BankTransactionParser::fromFile(self::SAMPLE_FILE);
        $originalRow = $originalDocument->getRow(0);
        $this->assertNotNull($originalRow);

        // Extrahiere die Werte aus der ersten Zeile
        $originalFields = $originalRow->getFields();
        $originalFieldCount = count($originalFields);

        $values = [];
        $orderedFields = BankTransactionHeaderField::ordered();

        // Nur die Felder übernehmen, die im Original vorhanden sind
        for ($index = 0; $index < min($originalFieldCount, count($orderedFields)); $index++) {
            $field = $orderedFields[$index];
            $values[$field->name] = $originalFields[$index]->getValue();
        }

        // Baue eine neue Zeile mit dem Builder
        $builder = new BankTransactionBuilder();
        $builder->addTransaction($values);
        $builtDocument = $builder->build();

        // Vergleiche die einzelnen Felder (nicht die gesamte CSV-Zeile, da der Builder alle 34 Felder erstellt)
        $builtRow = $builtDocument->getRow(0);
        $this->assertNotNull($builtRow);
        $builtFields = $builtRow->getFields();

        // Prüfe, dass die ersten N Felder übereinstimmen
        for ($i = 0; $i < $originalFieldCount; $i++) {
            $originalValue = $originalFields[$i]->getValue();
            $builtValue = $builtFields[$i]->getValue();

            $this->assertEquals(
                $originalValue,
                $builtValue,
                sprintf('Feld %d: Wert sollte übereinstimmen', $i + 1)
            );

            // Quoting nur für nicht-leere Felder prüfen (leere Felder können unterschiedlich formatiert sein)
            if ($originalValue !== '') {
                $this->assertEquals(
                    $originalFields[$i]->isQuoted(),
                    $builtFields[$i]->isQuoted(),
                    sprintf('Feld %d: Quoting sollte übereinstimmen (Wert: "%s")', $i + 1, $originalValue)
                );
            }
        }
    }

    /**
     * Test: addTransactions() fügt mehrere Transaktionen korrekt hinzu.
     */
    public function testAddTransactionsAddsMultipleRows(): void {
        $builder = new BankTransactionBuilder();

        $transactions = [
            [
                'BLZ_BIC_KONTOINHABER' => '70030000',
                'KONTONUMMER_IBAN_KONTOINHABER' => '1234567',
                'AUSZUGSNUMMER' => '433',
                'AUSZUGSDATUM' => '29.12.15',
                'VALUTA' => '29.12.15',
                'BUCHUNGSDATUM' => '29.12.15',
                'UMSATZ' => '10.00',
            ],
            [
                'BLZ_BIC_KONTOINHABER' => '70030000',
                'KONTONUMMER_IBAN_KONTOINHABER' => '1234567',
                'AUSZUGSNUMMER' => '434',
                'AUSZUGSDATUM' => '30.12.15',
                'VALUTA' => '30.12.15',
                'BUCHUNGSDATUM' => '30.12.15',
                'UMSATZ' => '-25.50',
            ],
        ];

        $builder->addTransactions($transactions);
        $document = $builder->build();

        $this->assertEquals(2, count($document->getRows()));
    }

    /**
     * Round-Trip-Test: Sample-Datei parsen und exportieren - Ergebnis sollte identisch sein.
     */
    public function testRoundTripParseThenExport(): void {
        $this->assertFileExists(self::SAMPLE_FILE, 'Sample-Datei muss existieren');

        // Original-Inhalt lesen
        $originalContent = file_get_contents(self::SAMPLE_FILE);
        $this->assertNotFalse($originalContent, 'Sample-Datei muss lesbar sein');

        // Parsen
        $document = BankTransactionParser::fromFile(self::SAMPLE_FILE, ';', '"', false);
        $this->assertInstanceOf(BankTransaction::class, $document);

        // Exportieren
        $exportedContent = $document->toString(';', '"');

        // Vergleich: Original und Export sollten identisch sein
        $this->assertEquals(
            trim($originalContent),
            trim($exportedContent),
            'Round-Trip: Geparstes und exportiertes Dokument muss mit Original übereinstimmen'
        );
    }
}
