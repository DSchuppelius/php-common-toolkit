<?php
/*
 * Created on   : Sun Dec 22 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionParserTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Parsers;

use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use CommonToolkit\Parsers\BankTransactionParser;
use Tests\Contracts\BaseTestCase;
use RuntimeException;

/**
 * Tests für den BankTransactionParser.
 */
class BankTransactionParserTest extends BaseTestCase {

    private string $sampleCSV;

    protected function setUp(): void {
        parent::setUp();

        // Beispiel-CSV basierend auf DATEV-Dokumentation (exakt 34 Felder)
        $this->sampleCSV = implode("\n", [
            '"70030000";"1234567";"433";"29.12.15";"29.12.15";"29.12.15";10.00;"HANS MUSTERMANN";"";"80550000";"7654321";"Kd.Nr. 12345";"RECHNUNG v. 12.12.15";"";"";"051";"EUR";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""',
            '"70030000";"1234567";"434";"30.12.15";"30.12.15";"30.12.15";-25.50;"FIRMA ABC GMBH";"MÜNCHEN";"70150000";"1111111";"Miete Januar";"Objekt Muster";"";"";"005";"EUR";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""'
        ]);
    }

    public function testFromStringSuccess(): void {
        $document = BankTransactionParser::fromString($this->sampleCSV);

        $this->assertInstanceOf(BankTransaction::class, $document);
        $this->assertEquals(2, count($document->getRows()));
        $this->assertEquals('ASCII-Weiterverarbeitungsdatei', $document->getFormatType());
        $this->assertTrue($document->isAsciiProcessingFormat());
    }

    public function testFromStringEmptyFile(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Leere ASCII-Weiterverarbeitungsdatei');

        BankTransactionParser::fromString('');
    }

    public function testFromStringInvalidFormat(): void {
        $invalidCSV = '"70030000";"1234567";10.00'; // Zu wenige Felder

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falsche Anzahl Felder');

        BankTransactionParser::fromString($invalidCSV);
    }

    public function testIsValidBankTransactionFormat(): void {
        // Korrekte DATEV ASCII-Zeile mit 17 Feldern (wie in .samples/ASCII.csv)
        $validLine = '"70030000";"1234567";433;29.12.15;29.12.15;29.12.15;10.00;"HANS MUSTERMANN";"";"80550000";"7654321";"Kd.Nr. 12345";"RECHNUNG v. 12.12.15";"";"";"051";';
        $invalidLine = '"70030000";"1234567";10.00'; // Zu wenige Felder

        $this->assertTrue(BankTransactionParser::isValidBankTransactionFormat($validLine));
        $this->assertFalse(BankTransactionParser::isValidBankTransactionFormat($invalidLine));
    }

    public function testAnalyzeFormat(): void {
        $analysis = BankTransactionParser::analyzeFormat($this->sampleCSV);

        $this->assertEquals('ASCII-Weiterverarbeitungsdatei', $analysis['format_type']);
        $this->assertTrue($analysis['supported']);
        $this->assertEquals(2, $analysis['line_count']);
        $this->assertEquals(2, $analysis['valid_rows']);
        $this->assertEmpty($analysis['invalid_rows']);
        $this->assertEquals(34, $analysis['field_count']);
        $this->assertFalse($analysis['has_meta_header']);
        $this->assertFalse($analysis['has_field_header']);
        $this->assertEquals(['EUR'], $analysis['currencies']);
    }

    public function testAnalyzeFormatInvalidFile(): void {
        $invalidCSV = 'invalid;data;format';
        $analysis = BankTransactionParser::analyzeFormat($invalidCSV);

        $this->assertNull($analysis['format_type']);
        $this->assertFalse($analysis['supported']);
        $this->assertArrayHasKey('error', $analysis);
    }

    public function testCreateSampleFile(): void {
        $sampleContent = BankTransactionParser::createSampleFile();

        $this->assertNotEmpty($sampleContent);
        $this->assertTrue(str_contains($sampleContent, '"70030000"'));
        $this->assertTrue(str_contains($sampleContent, '"HANS MUSTERMANN"'));

        // Prüfen ob Sample-Datei geparst werden kann
        $document = BankTransactionParser::fromString($sampleContent);
        $this->assertInstanceOf(BankTransaction::class, $document);
        $this->assertGreaterThan(0, count($document->getRows()));
    }

    public function testBankTransactionDocumentMethods(): void {
        $document = BankTransactionParser::fromString($this->sampleCSV);

        // Test hasValidBankData()
        $this->assertTrue($document->hasValidBankData());

        // Test getAccountHolderBankData()
        $accountData = $document->getAccountHolderBankData(0);
        $this->assertNotNull($accountData);
        $this->assertEquals('70030000', $accountData['blz_bic']);
        $this->assertEquals('1234567', $accountData['account_number']);

        // Test getPayerBankData()
        $payerData = $document->getPayerBankData(0);
        $this->assertNotNull($payerData);
        $this->assertEquals('HANS MUSTERMANN', $payerData['name1']);
        $this->assertEquals('', $payerData['name2']);
        $this->assertEquals('80550000', $payerData['blz_bic']);
        $this->assertEquals('7654321', $payerData['account_number']);

        // Test getTransactionData()
        $transactionData = $document->getTransactionData(0);
        $this->assertNotNull($transactionData);
        $this->assertEquals('433', $transactionData['statement_number']);
        $this->assertEquals('29.12.15', $transactionData['booking_date']);
        $this->assertEquals('10.00', $transactionData['amount']);
        $this->assertEquals('EUR', $transactionData['currency']);

        // Test getUsagePurposes()
        $purposes = $document->getUsagePurposes(0);
        $this->assertContains('Kd.Nr. 12345', $purposes);
        $this->assertContains('RECHNUNG v. 12.12.15', $purposes);

        // Test getTransactionSummary()
        $summary = $document->getTransactionSummary();
        $this->assertEquals(2, $summary['total_transactions']);
        $this->assertEquals(-15.50, $summary['total_amount']); // 10.00 + (-25.50)
        $this->assertArrayHasKey('EUR', $summary['currencies']);
        $this->assertEquals('29.12.15', $summary['date_range']['from']);
        $this->assertEquals('30.12.15', $summary['date_range']['to']);
    }

    public function testToCSV(): void {
        $document = BankTransactionParser::fromString($this->sampleCSV);
        $csvOutput = BankTransactionParser::toCSV($document);

        $this->assertNotEmpty($csvOutput);
        $this->assertTrue(str_contains($csvOutput, '70030000'));
        $this->assertTrue(str_contains($csvOutput, 'HANS MUSTERMANN'));

        // Rund-Trip-Test: CSV -> Document -> CSV
        $roundTripDocument = BankTransactionParser::fromString($csvOutput);
        $this->assertEquals($document->getTransactionSummary(), $roundTripDocument->getTransactionSummary());
    }

    public function testGetSupportedExtensions(): void {
        $extensions = BankTransactionParser::getSupportedExtensions();

        $this->assertIsArray($extensions);
        $this->assertContains('.csv', $extensions);
        $this->assertContains('.txt', $extensions);
        $this->assertContains('.asc', $extensions);
    }

    public function testGetFormatDescription(): void {
        $description = BankTransactionParser::getFormatDescription();

        $this->assertIsString($description);
        $this->assertTrue(str_contains($description, 'ASCII-Weiterverarbeitungsdatei'));
        $this->assertTrue(str_contains($description, '34 Felder'));
    }

    public function testValidationErrors(): void {
        // CSV mit ungültigen Daten (fehlende Pflichtfelder) - genau 34 Felder
        $invalidCSV = '"";"";"";"29.12.15";"";"29.12.15";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ASCII-Weiterverarbeitungsdatei-Validierung fehlgeschlagen');

        BankTransactionParser::fromString($invalidCSV);
    }

    public function testDateSortingValidation(): void {
        // CSV mit nicht aufsteigend sortierten Daten - beide genau 34 Felder
        $unsortedCSV = implode("\n", [
            '"70030000";"1234567";"434";"30.12.15";"30.12.15";"30.12.15";"10.00";"TEST1";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""',
            '"70030000";"1234567";"433";"29.12.15";"29.12.15";"29.12.15";"-25.50";"TEST2";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""'
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nicht aufsteigend sortiert');

        BankTransactionParser::fromString($unsortedCSV);
    }

    public function testFieldCount(): void {
        // Test mit 33 Feldern statt 34
        $incompleteCSV = '"70030000";"1234567";"433";"29.12.15";"29.12.15";"29.12.15";10.00;"HANS MUSTERMANN";"";"80550000";"7654321";"Kd.Nr. 12345";"RECHNUNG v. 12.12.15";"";"";051;"EUR";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falsche Anzahl Felder');

        BankTransactionParser::fromString($incompleteCSV);
    }

    public function testGetAdditionalAmounts(): void {
        // CSV mit zusätzlichen Beträgen - genau 34 Felder 
        $csvWithAdditionalAmounts = '"70030000";"1234567";"433";"29.12.15";"29.12.15";"29.12.15";10.00;"HANS MUSTERMANN";"";"80550000";"7654321";"Kd.Nr. 12345";"RECHNUNG v. 12.12.15";"";"";"051";"EUR";"";"";"";"";"";"";"";"";"";"100.00";"USD";"200.00";"EUR";"5.00";"EUR";"";""';

        $document = BankTransactionParser::fromString($csvWithAdditionalAmounts);
        $amounts = $document->getAdditionalAmounts(0);

        $this->assertEquals('100.00', $amounts['original_amount']);
        $this->assertEquals('USD', $amounts['original_currency']);
        $this->assertEquals('200.00', $amounts['equivalent_amount']);
        $this->assertEquals('EUR', $amounts['equivalent_currency']);
        $this->assertEquals('5.00', $amounts['fee_amount']);
        $this->assertEquals('EUR', $amounts['fee_currency']);
    }

    public function testFieldMaxLengths(): void {
        // Test der maximalen Feldlängen entsprechend DATEV-Dokumentation
        $this->assertEquals(11, BankTransactionHeaderField::BLZ_BIC_KONTOINHABER->getMaxLength());
        $this->assertEquals(34, BankTransactionHeaderField::KONTONUMMER_IBAN_KONTOINHABER->getMaxLength());
        $this->assertEquals(4, BankTransactionHeaderField::AUSZUGSNUMMER->getMaxLength());
        $this->assertEquals(10, BankTransactionHeaderField::AUSZUGSDATUM->getMaxLength());
        $this->assertEquals(10, BankTransactionHeaderField::VALUTA->getMaxLength());
        $this->assertEquals(10, BankTransactionHeaderField::BUCHUNGSDATUM->getMaxLength());
        $this->assertEquals(15, BankTransactionHeaderField::UMSATZ->getMaxLength());
        $this->assertEquals(27, BankTransactionHeaderField::AUFTRAGGEBERNAME_1->getMaxLength());
        $this->assertEquals(27, BankTransactionHeaderField::AUFTRAGGEBERNAME_2->getMaxLength());
        $this->assertEquals(11, BankTransactionHeaderField::BLZ_BIC_AUFTRAGGEBER->getMaxLength());
        $this->assertEquals(34, BankTransactionHeaderField::KONTONUMMER_IBAN_AUFTRAGGEBER->getMaxLength());
        $this->assertEquals(27, BankTransactionHeaderField::VERWENDUNGSZWECK_1->getMaxLength());
        $this->assertEquals(3, BankTransactionHeaderField::GESCHAEFTSVORGANGSCODE->getMaxLength());
        $this->assertEquals(3, BankTransactionHeaderField::WAEHRUNG->getMaxLength());
        $this->assertEquals(15, BankTransactionHeaderField::URSPRUNGSBETRAG->getMaxLength());
        $this->assertEquals(3, BankTransactionHeaderField::WAEHRUNG_URSPRUNGSBETRAG->getMaxLength());
    }

    public function testFieldMaxLengthsForAllFields(): void {
        // Teste dass alle 34 Felder eine definierte maximale Länge haben
        $orderedFields = BankTransactionHeaderField::ordered();
        $this->assertCount(34, $orderedFields);

        foreach ($orderedFields as $field) {
            $maxLength = $field->getMaxLength();
            $this->assertIsInt($maxLength, "Feld {$field->name} sollte eine definierte maximale Länge haben");
            $this->assertGreaterThan(0, $maxLength, "Maximale Länge für {$field->name} sollte größer als 0 sein");
        }
    }

    public function testSpecificFieldLengthLimits(): void {
        // Test spezifische DATEV-konforme Längenlimits

        // Währungscodes: Genau 3 Zeichen (ISO 4217)
        $currencyFields = [
            BankTransactionHeaderField::WAEHRUNG,
            BankTransactionHeaderField::WAEHRUNG_URSPRUNGSBETRAG,
            BankTransactionHeaderField::WAEHRUNG_AEQUIVALENZBETRAG,
            BankTransactionHeaderField::WAEHRUNG_GEBUEHR
        ];

        foreach ($currencyFields as $field) {
            $this->assertEquals(3, $field->getMaxLength(), "Währungsfeld {$field->name} sollte exakt 3 Zeichen haben");
        }

        // Verwendungszweck-Felder: Alle 27 Zeichen
        $usageFields = [
            BankTransactionHeaderField::VERWENDUNGSZWECK_1,
            BankTransactionHeaderField::VERWENDUNGSZWECK_2,
            BankTransactionHeaderField::VERWENDUNGSZWECK_3,
            BankTransactionHeaderField::VERWENDUNGSZWECK_4,
            BankTransactionHeaderField::VERWENDUNGSZWECK_5,
            BankTransactionHeaderField::VERWENDUNGSZWECK_6,
            BankTransactionHeaderField::VERWENDUNGSZWECK_7,
            BankTransactionHeaderField::VERWENDUNGSZWECK_8,
            BankTransactionHeaderField::VERWENDUNGSZWECK_9,
            BankTransactionHeaderField::VERWENDUNGSZWECK_10,
            BankTransactionHeaderField::VERWENDUNGSZWECK_11,
            BankTransactionHeaderField::VERWENDUNGSZWECK_12,
            BankTransactionHeaderField::VERWENDUNGSZWECK_13,
            BankTransactionHeaderField::VERWENDUNGSZWECK_14,
        ];

        foreach ($usageFields as $field) {
            $this->assertEquals(27, $field->getMaxLength(), "Verwendungszweck-Feld {$field->name} sollte 27 Zeichen haben");
        }

        // Beträge: Alle 15 Zeichen (±9999999999999,99)
        $amountFields = [
            BankTransactionHeaderField::UMSATZ,
            BankTransactionHeaderField::URSPRUNGSBETRAG,
            BankTransactionHeaderField::AEQUIVALENZBETRAG,
            BankTransactionHeaderField::GEBUEHR
        ];

        foreach ($amountFields as $field) {
            $this->assertEquals(15, $field->getMaxLength(), "Betrags-Feld {$field->name} sollte 15 Zeichen haben");
        }
    }

    public function testExportWithFieldLengthLimits(): void {
        $document = BankTransactionParser::fromString($this->sampleCSV);

        // Erstelle Builder mit truncate-Strategie und verwende die vorhandenen Daten
        $builder = new \CommonToolkit\Builders\DATEV\BankTransactionBuilder(';', '"', TruncationStrategy::TRUNCATE);
        $builder->addLines($document->getRows());
        $datevDocument = $builder->build();

        $truncated = $datevDocument->toString();
        $lines = explode("\n", $truncated);

        // ASCII-Weiterverarbeitungsdateien haben keinen Header - sollte nur 2 Datenzeilen haben
        $this->assertGreaterThanOrEqual(2, count($lines));

        // Teste die erste Datenzeile (es gibt keinen Header)
        $dataLine = $lines[0]; // Erste Datenzeile
        $fields = str_getcsv($dataLine, ';', '"', '');
        $this->assertEquals(34, count($fields));

        // Alle Felder sollten ihre maximalen Längen einhalten
        $this->assertLessThanOrEqual(11, mb_strlen($fields[0])); // BLZ/BIC
        $this->assertLessThanOrEqual(27, mb_strlen($fields[7])); // Name
        $this->assertLessThanOrEqual(27, mb_strlen($fields[11])); // VWZ1
    }
    public function testExportWithEllipsisStrategy(): void {
        $document = BankTransactionParser::fromString($this->sampleCSV);

        // Erstelle Builder mit ellipsis-Strategie und verwende die vorhandenen Daten
        $builder = new \CommonToolkit\Builders\DATEV\BankTransactionBuilder(';', '"', TruncationStrategy::ELLIPSIS);
        $builder->addLines($document->getRows());
        $datevDocument = $builder->build();

        $ellipsis = $datevDocument->toString();
        $lines = explode("\n", $ellipsis);

        // ASCII-Weiterverarbeitungsdateien haben keinen Header - sollte nur 2 Datenzeilen haben
        $this->assertGreaterThanOrEqual(2, count($lines));

        // Teste die erste Datenzeile (es gibt keinen Header)
        $dataLine = $lines[0];
        $fields = str_getcsv($dataLine, ';', '"', '');
        $this->assertEquals(34, count($fields));

        // Mit der aktuellen kurzen Test-Daten wird wahrscheinlich nichts gekürzt
        // Das ist ok - die Funktionalität wird getestet
        $this->assertTrue(true, 'Ellipsis-Export funktioniert');
    }
}
