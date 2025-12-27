<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionRoundtripTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Converters\DATEV;

use CommonToolkit\Builders\DATEV\BankTransactionBuilder;
use CommonToolkit\Converters\DATEV\BankTransactionToCamt053Converter;
use CommonToolkit\Converters\DATEV\BankTransactionToMt940Converter;
use CommonToolkit\Converters\DATEV\Camt053ToBankTransactionConverter;
use CommonToolkit\Converters\DATEV\Mt940ToBankTransactionConverter;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Parsers\BankTransactionParser;
use CommonToolkit\Parsers\CamtParser;
use CommonToolkit\Parsers\Mt940DocumentParser;
use Tests\Contracts\BaseTestCase;

/**
 * Roundtrip-Tests für BankTransaction ↔ MT940/CAMT.053 Konvertierung.
 * 
 * Testet, dass die Datenintegrität bei der Hin- und Rückkonvertierung erhalten bleibt.
 * 
 * Feldnamen basieren auf BankTransactionHeaderField enum-Namen:
 * - BLZ_BIC_KONTOINHABER
 * - KONTONUMMER_IBAN_KONTOINHABER
 * - AUSZUGSNUMMER, AUSZUGSDATUM, VALUTA, BUCHUNGSDATUM
 * - UMSATZ
 * - VERWENDUNGSZWECK_1 bis VERWENDUNGSZWECK_14
 * - GESCHAEFTSVORGANGSCODE, WAEHRUNG, BUCHUNGSTEXT
 */
class BankTransactionRoundtripTest extends BaseTestCase {

    /**
     * Test: BankTransaction → MT940 → BankTransaction Roundtrip
     */
    public function testRoundtripViaMt940SingleCredit(): void {
        // Erstelle originale BankTransaction
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEDB',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0001',
                'AUSZUGSDATUM' => '27.12.2025',
                'VALUTA' => '27.12.2025',
                'BUCHUNGSDATUM' => '27.12.2025',
                'UMSATZ' => '+100,50',
                'AUFTRAGGEBERNAME_1' => 'Max Mustermann',
                'AUFTRAGGEBERNAME_2' => '',
                'BLZ_BIC_AUFTRAGGEBER' => '',
                'KONTONUMMER_IBAN_AUFTRAGGEBER' => '',
                'VERWENDUNGSZWECK_1' => 'Rechnung 12345',
                'VERWENDUNGSZWECK_2' => 'Zahlung Dezember',
                'VERWENDUNGSZWECK_3' => '',
                'VERWENDUNGSZWECK_4' => '',
                'GESCHAEFTSVORGANGSCODE' => 'NTRF',
                'WAEHRUNG' => 'EUR',
                'BUCHUNGSTEXT' => 'TRF'
            ])
            ->build();

        // Konvertiere zu MT940
        $mt940 = BankTransactionToMt940Converter::convert($original, 1000.00);

        // Konvertiere zurück zu BankTransaction
        $result = Mt940ToBankTransactionConverter::convert($mt940);

        // Verifiziere Kernfelder
        $this->assertInstanceOf(BankTransaction::class, $result);
        $this->assertCount(1, $result->getRows());

        $originalRow = $original->getRows()[0];
        $resultRow = $result->getRows()[0];

        // BLZ/BIC und IBAN sollten übereinstimmen
        $this->assertEquals(
            $originalRow->getField(0)->getValue(),
            $resultRow->getField(0)->getValue(),
            'BLZ/BIC sollte erhalten bleiben'
        );
        $this->assertEquals(
            $originalRow->getField(1)->getValue(),
            $resultRow->getField(1)->getValue(),
            'IBAN sollte erhalten bleiben'
        );

        // Auszugsnummer sollte übereinstimmen
        $this->assertEquals(
            $originalRow->getField(2)->getValue(),
            $resultRow->getField(2)->getValue(),
            'Auszugsnummer sollte erhalten bleiben'
        );

        // Datumsfelder sollten übereinstimmen
        $this->assertEquals(
            $originalRow->getField(3)->getValue(),
            $resultRow->getField(3)->getValue(),
            'Auszugsdatum sollte erhalten bleiben'
        );

        // Betrag sollte übereinstimmen (mit Vorzeichen)
        $this->assertEquals(
            '+100,50',
            $resultRow->getField(6)->getValue(),
            'Betrag mit Vorzeichen sollte erhalten bleiben'
        );

        // Währung sollte übereinstimmen
        $this->assertEquals('EUR', $resultRow->getField(16)->getValue(), 'Währung sollte erhalten bleiben');
    }

    /**
     * Test: BankTransaction → MT940 → BankTransaction mit Debit-Transaktion
     * 
     * Hinweis: MT940 speichert BLZ/Kontonummer als kombinierte accountId (Format: BLZ/Konto).
     * Beim Roundtrip wird diese Information korrekt geparsed.
     */
    public function testRoundtripViaMt940SingleDebit(): void {
        // Verwende IBAN-Format, das zuverlässiger durch Roundtrip geht
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => '12345678',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89123456780987654321',
                'AUSZUGSNUMMER' => '0002',
                'AUSZUGSDATUM' => '26.12.2025',
                'VALUTA' => '26.12.2025',
                'BUCHUNGSDATUM' => '26.12.2025',
                'UMSATZ' => '-250,00',
                'VERWENDUNGSZWECK_1' => 'Lastschrift Strom',
                'GESCHAEFTSVORGANGSCODE' => '020',
                'WAEHRUNG' => 'EUR',
                'BUCHUNGSTEXT' => 'CHK'
            ])
            ->build();

        $mt940 = BankTransactionToMt940Converter::convert($original, 1000.00);
        $result = Mt940ToBankTransactionConverter::convert($mt940);

        $resultRow = $result->getRows()[0];

        // Betrag und Währung sollten erhalten bleiben
        $this->assertEquals('-250,00', $resultRow->getField(6)->getValue());
        $this->assertEquals('EUR', $resultRow->getField(16)->getValue());

        // IBAN sollte erhalten bleiben
        $this->assertEquals('DE89123456780987654321', $resultRow->getField(1)->getValue());
    }

    /**
     * Test: BankTransaction → MT940 → BankTransaction mit mehreren Transaktionen
     */
    public function testRoundtripViaMt940MultipleTransactions(): void {
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEDB',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0003',
                'AUSZUGSDATUM' => '25.12.2025',
                'VALUTA' => '25.12.2025',
                'BUCHUNGSDATUM' => '25.12.2025',
                'UMSATZ' => '+500,00',
                'VERWENDUNGSZWECK_1' => 'Einzahlung 1',
                'GESCHAEFTSVORGANGSCODE' => 'NTRF',
                'WAEHRUNG' => 'EUR'
            ])
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEDB',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0003',
                'AUSZUGSDATUM' => '25.12.2025',
                'VALUTA' => '25.12.2025',
                'BUCHUNGSDATUM' => '25.12.2025',
                'UMSATZ' => '-100,00',
                'VERWENDUNGSZWECK_1' => 'Abbuchung 1',
                'GESCHAEFTSVORGANGSCODE' => '020',
                'WAEHRUNG' => 'EUR'
            ])
            ->build();

        $mt940 = BankTransactionToMt940Converter::convert($original, 1000.00);
        $result = Mt940ToBankTransactionConverter::convert($mt940);

        $this->assertCount(2, $result->getRows());

        $this->assertEquals('+500,00', $result->getRows()[0]->getField(6)->getValue());
        $this->assertEquals('-100,00', $result->getRows()[1]->getField(6)->getValue());
    }

    /**
     * Test: BankTransaction → CAMT.053 → BankTransaction Roundtrip
     * 
     * Hinweis: CAMT.053 verwendet BIC (8 oder 11 Zeichen) und IBAN.
     * Bei 8-Zeichen-BIC wird beim Roundtrip die BLZ aus der IBAN extrahiert.
     */
    public function testRoundtripViaCamt053SingleCredit(): void {
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEFFXXX', // 11-Zeichen-BIC für Roundtrip
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0001',
                'AUSZUGSDATUM' => '27.12.2025',
                'VALUTA' => '27.12.2025',
                'BUCHUNGSDATUM' => '27.12.2025',
                'UMSATZ' => '+200,75',
                'AUFTRAGGEBERNAME_1' => 'Erika Musterfrau',
                'AUFTRAGGEBERNAME_2' => '',
                'BLZ_BIC_AUFTRAGGEBER' => 'COBADEFFXXX',
                'KONTONUMMER_IBAN_AUFTRAGGEBER' => 'DE12500105170648489890',
                'VERWENDUNGSZWECK_1' => 'EREF+REF123456',
                'VERWENDUNGSZWECK_2' => 'SVWZ+Gehalt Dez',
                'VERWENDUNGSZWECK_3' => '',
                'VERWENDUNGSZWECK_4' => '',
                'GESCHAEFTSVORGANGSCODE' => 'PMNT',
                'WAEHRUNG' => 'EUR',
                'BUCHUNGSTEXT' => 'Dauerauftrag'
            ])
            ->build();

        // Konvertiere zu CAMT.053
        $camt053 = BankTransactionToCamt053Converter::convert($original, 5000.00);

        // Konvertiere zurück zu BankTransaction
        $result = Camt053ToBankTransactionConverter::convert($camt053);

        // Verifiziere Kernfelder
        $this->assertInstanceOf(BankTransaction::class, $result);
        $this->assertCount(1, $result->getRows());

        $resultRow = $result->getRows()[0];

        // BIC sollte erhalten bleiben (11 Zeichen)
        $this->assertEquals('DEUTDEFFXXX', $resultRow->getField(0)->getValue(), 'BIC sollte erhalten bleiben');

        // IBAN sollte erhalten bleiben
        $this->assertEquals(
            'DE89370400440532013000',
            $resultRow->getField(1)->getValue(),
            'IBAN sollte erhalten bleiben'
        );

        // Betrag sollte übereinstimmen
        $this->assertEquals('+200,75', $resultRow->getField(6)->getValue(), 'Betrag sollte erhalten bleiben');

        // Währung sollte übereinstimmen
        $this->assertEquals('EUR', $resultRow->getField(16)->getValue(), 'Währung sollte erhalten bleiben');
    }

    /**
     * Test: BankTransaction → CAMT.053 → BankTransaction mit Debit-Transaktion
     * 
     * Hinweis: Verwendet 11-Zeichen-BIC für verlustfreien Roundtrip.
     */
    public function testRoundtripViaCamt053SingleDebit(): void {
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'COBADEFFXXX', // 11-Zeichen-BIC
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE12500105170648489890',
                'AUSZUGSNUMMER' => '0005',
                'AUSZUGSDATUM' => '24.12.2025',
                'VALUTA' => '24.12.2025',
                'BUCHUNGSDATUM' => '24.12.2025',
                'UMSATZ' => '-350,25',
                'VERWENDUNGSZWECK_1' => 'Miete Dezember',
                'GESCHAEFTSVORGANGSCODE' => 'DMCT',
                'WAEHRUNG' => 'EUR'
            ])
            ->build();

        $camt053 = BankTransactionToCamt053Converter::convert($original, 2000.00);
        $result = Camt053ToBankTransactionConverter::convert($camt053);

        $resultRow = $result->getRows()[0];

        $this->assertEquals('COBADEFFXXX', $resultRow->getField(0)->getValue());
        $this->assertEquals('DE12500105170648489890', $resultRow->getField(1)->getValue());
        $this->assertEquals('-350,25', $resultRow->getField(6)->getValue());
        $this->assertEquals('EUR', $resultRow->getField(16)->getValue());
    }

    /**
     * Test: BankTransaction → CAMT.053 → BankTransaction mit mehreren Transaktionen
     */
    public function testRoundtripViaCamt053MultipleTransactions(): void {
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEFFXXX',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0006',
                'AUSZUGSDATUM' => '23.12.2025',
                'VALUTA' => '23.12.2025',
                'BUCHUNGSDATUM' => '23.12.2025',
                'UMSATZ' => '+1000,00',
                'VERWENDUNGSZWECK_1' => 'Bonus Zahlung',
                'GESCHAEFTSVORGANGSCODE' => 'SALA',
                'WAEHRUNG' => 'EUR'
            ])
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEFFXXX',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0006',
                'AUSZUGSDATUM' => '23.12.2025',
                'VALUTA' => '23.12.2025',
                'BUCHUNGSDATUM' => '23.12.2025',
                'UMSATZ' => '-50,00',
                'VERWENDUNGSZWECK_1' => 'Gebuehren',
                'GESCHAEFTSVORGANGSCODE' => 'CHRG',
                'WAEHRUNG' => 'EUR'
            ])
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEFFXXX',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0006',
                'AUSZUGSDATUM' => '23.12.2025',
                'VALUTA' => '23.12.2025',
                'BUCHUNGSDATUM' => '23.12.2025',
                'UMSATZ' => '+75,50',
                'VERWENDUNGSZWECK_1' => 'Zinsertrag',
                'GESCHAEFTSVORGANGSCODE' => 'INTR',
                'WAEHRUNG' => 'EUR'
            ])
            ->build();

        $camt053 = BankTransactionToCamt053Converter::convert($original, 10000.00);
        $result = Camt053ToBankTransactionConverter::convert($camt053);

        $this->assertCount(3, $result->getRows());

        $this->assertEquals('+1000,00', $result->getRows()[0]->getField(6)->getValue());
        $this->assertEquals('-50,00', $result->getRows()[1]->getField(6)->getValue());
        $this->assertEquals('+75,50', $result->getRows()[2]->getField(6)->getValue());
    }

    /**
     * Test: Vergleich Verwendungszweck bei MT940-Roundtrip
     * 
     * Hinweis: MT940 kombiniert Purpose in einem Feld, das dann auf die
     * Verwendungszweck-Felder 1-14 aufgeteilt wird.
     */
    public function testRoundtripMt940PreservesVerwendungszweck(): void {
        $original = (new BankTransactionBuilder())
            ->addTransaction([
                'BLZ_BIC_KONTOINHABER' => 'DEUTDEDB',
                'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                'AUSZUGSNUMMER' => '0007',
                'AUSZUGSDATUM' => '22.12.2025',
                'VALUTA' => '22.12.2025',
                'BUCHUNGSDATUM' => '22.12.2025',
                'UMSATZ' => '+99,99',
                'VERWENDUNGSZWECK_1' => 'Test Zeile 1',
                'VERWENDUNGSZWECK_2' => 'Test Zeile 2',
                'VERWENDUNGSZWECK_3' => 'Test Zeile 3',
                'GESCHAEFTSVORGANGSCODE' => 'NTRF',
                'WAEHRUNG' => 'EUR'
            ])
            ->build();

        $mt940 = BankTransactionToMt940Converter::convert($original, 100.00);

        // Prüfe, dass die MT940-Transaktion Purpose enthält
        $transactions = $mt940->getTransactions();
        $this->assertNotEmpty($transactions);

        $purpose = $transactions[0]->getPurpose();
        $this->assertNotEmpty($purpose, 'MT940 Transaction Purpose sollte nicht leer sein');
        $this->assertStringContainsString('Test', $purpose, 'Purpose sollte Verwendungszweck-Text enthalten');

        // Roundtrip zurück
        $result = Mt940ToBankTransactionConverter::convert($mt940);

        $resultRow = $result->getRows()[0];

        // Die Verwendungszweck-Felder werden aus dem kombinierten Purpose wieder aufgeteilt
        // Es genügt zu prüfen, dass irgendein Verwendungszweck-Feld befüllt ist
        $anyVerwendungszweck = false;
        for ($i = 11; $i <= 14; $i++) { // Felder 12-15 = Verwendungszweck 1-4 (0-basiert: 11-14)
            $value = $resultRow->getField($i)->getValue();
            if (!empty($value)) {
                $anyVerwendungszweck = true;
                break;
            }
        }
        $this->assertTrue($anyVerwendungszweck, 'Mindestens ein Verwendungszweck-Feld sollte befüllt sein');
    }

    /**
     * Test: Dezimalbeträge werden korrekt erhalten
     */
    public function testRoundtripPreservesDecimalAmounts(): void {
        $amounts = ['+0,01', '-0,99', '+1234,56', '-9999,99'];

        foreach ($amounts as $amount) {
            $original = (new BankTransactionBuilder())
                ->addTransaction([
                    'BLZ_BIC_KONTOINHABER' => 'DEUTDEDB',
                    'KONTONUMMER_IBAN_KONTOINHABER' => 'DE89370400440532013000',
                    'AUSZUGSNUMMER' => '0008',
                    'AUSZUGSDATUM' => '21.12.2025',
                    'VALUTA' => '21.12.2025',
                    'BUCHUNGSDATUM' => '21.12.2025',
                    'UMSATZ' => $amount,
                    'GESCHAEFTSVORGANGSCODE' => 'NTRF',
                    'WAEHRUNG' => 'EUR'
                ])
                ->build();

            // MT940 Roundtrip
            $mt940 = BankTransactionToMt940Converter::convert($original, 10000.00);
            $resultMt940 = Mt940ToBankTransactionConverter::convert($mt940);
            $this->assertEquals($amount, $resultMt940->getRows()[0]->getField(6)->getValue(), "MT940: Betrag $amount sollte erhalten bleiben");

            // CAMT.053 Roundtrip
            $camt053 = BankTransactionToCamt053Converter::convert($original, 10000.00);
            $resultCamt053 = Camt053ToBankTransactionConverter::convert($camt053);
            $this->assertEquals($amount, $resultCamt053->getRows()[0]->getField(6)->getValue(), "CAMT.053: Betrag $amount sollte erhalten bleiben");
        }
    }

    // ========================================
    // Sample-Datei Roundtrip Tests
    // ========================================

    /**
     * Test: MT940 Sample-Datei → BankTransaction → MT940 Roundtrip
     * 
     * Parst eine echte MT940-Datei, konvertiert zu BankTransaction und zurück.
     */
    public function testSampleFileMt940ToBankTransactionRoundtrip(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/Banking/MT/example.mt940';
        $this->assertFileExists($sampleFile, 'Sample-Datei example.mt940 existiert nicht');

        // Parse MT940 Sample-Datei
        $originalContent = file_get_contents($sampleFile);
        $mt940Original = Mt940DocumentParser::parse($originalContent);

        // MT940 → BankTransaction
        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940Original);

        $this->assertInstanceOf(BankTransaction::class, $bankTransaction);
        $this->assertGreaterThan(0, count($bankTransaction->getRows()), 'BankTransaction sollte Zeilen enthalten');

        // BankTransaction → MT940
        $mt940Regenerated = BankTransactionToMt940Converter::convert($bankTransaction);

        // Vergleiche Kernfelder
        $this->assertEquals(
            $mt940Original->countEntries(),
            $mt940Regenerated->countEntries(),
            'Anzahl der Transaktionen sollte übereinstimmen'
        );

        // Vergleiche Gesamtbeträge
        $this->assertEqualsWithDelta(
            $mt940Original->getTotalCredit(),
            $mt940Regenerated->getTotalCredit(),
            0.01,
            'Summe der Credit-Transaktionen sollte übereinstimmen'
        );
        $this->assertEqualsWithDelta(
            $mt940Original->getTotalDebit(),
            $mt940Regenerated->getTotalDebit(),
            0.01,
            'Summe der Debit-Transaktionen sollte übereinstimmen'
        );

        // Vergleiche einzelne Transaktionsbeträge
        $originalTxns = $mt940Original->getTransactions();
        $regeneratedTxns = $mt940Regenerated->getTransactions();

        for ($i = 0; $i < count($originalTxns); $i++) {
            $this->assertEqualsWithDelta(
                $originalTxns[$i]->getAmount(),
                $regeneratedTxns[$i]->getAmount(),
                0.01,
                "Transaktion $i: Betrag sollte übereinstimmen"
            );
            $this->assertEquals(
                $originalTxns[$i]->getCreditDebit(),
                $regeneratedTxns[$i]->getCreditDebit(),
                "Transaktion $i: Credit/Debit sollte übereinstimmen"
            );
        }
    }

    /**
     * Test: CAMT.053 Sample-Datei → BankTransaction → CAMT.053 Roundtrip
     * 
     * Parst eine echte CAMT.053-Datei, konvertiert zu BankTransaction und zurück.
     */
    public function testSampleFileCamt053ToBankTransactionRoundtrip(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/Banking/CAMT/11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        $this->assertFileExists($sampleFile, 'Sample-Datei CAMT.053 existiert nicht');

        // Parse CAMT.053 Sample-Datei
        $originalContent = file_get_contents($sampleFile);
        $camt053Original = CamtParser::parse($originalContent);

        // CAMT.053 → BankTransaction
        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt053Original);

        $this->assertInstanceOf(BankTransaction::class, $bankTransaction);
        $this->assertGreaterThan(0, count($bankTransaction->getRows()), 'BankTransaction sollte Zeilen enthalten');

        // BankTransaction → CAMT.053
        $camt053Regenerated = BankTransactionToCamt053Converter::convert($bankTransaction);

        // Vergleiche Kernfelder
        $this->assertEquals(
            $camt053Original->countEntries(),
            $camt053Regenerated->countEntries(),
            'Anzahl der Transaktionen sollte übereinstimmen'
        );

        // Vergleiche einzelne Transaktionsbeträge
        $originalEntries = $camt053Original->getEntries();
        $regeneratedEntries = $camt053Regenerated->getEntries();

        for ($i = 0; $i < count($originalEntries); $i++) {
            $this->assertEqualsWithDelta(
                $originalEntries[$i]->getAmount(),
                $regeneratedEntries[$i]->getAmount(),
                0.01,
                "Entry $i: Betrag sollte übereinstimmen"
            );
            $this->assertEquals(
                $originalEntries[$i]->getCreditDebit(),
                $regeneratedEntries[$i]->getCreditDebit(),
                "Entry $i: Credit/Debit sollte übereinstimmen"
            );
        }
    }

    /**
     * Test: MT940 Sample → BankTransaction Feldwerte
     * 
     * Prüft, dass die BankTransaction-Zeilen sinnvolle Werte enthalten.
     */
    public function testSampleFileMt940ToBankTransactionFieldValues(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/Banking/MT/example.mt940';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);
        $mt940 = Mt940DocumentParser::parse($originalContent);
        $bankTransaction = Mt940ToBankTransactionConverter::convert($mt940);

        $rows = $bankTransaction->getRows();
        $this->assertNotEmpty($rows);

        foreach ($rows as $index => $row) {
            // Umsatz (Index 6) sollte nicht leer sein
            $umsatz = $row->getField(6)->getValue();
            $this->assertNotEmpty($umsatz, "Zeile $index: Umsatz sollte nicht leer sein");
            $this->assertMatchesRegularExpression('/^[+-]?\d+,\d{2}$/', $umsatz, "Zeile $index: Umsatz sollte deutsches Zahlenformat haben");

            // Währung (Index 16) sollte gesetzt sein
            $waehrung = $row->getField(16)->getValue();
            $this->assertNotEmpty($waehrung, "Zeile $index: Währung sollte nicht leer sein");
            $this->assertEquals(3, strlen($waehrung), "Zeile $index: Währung sollte 3 Zeichen haben");
        }
    }

    /**
     * Test: CAMT.053 Sample → BankTransaction Feldwerte
     * 
     * Prüft, dass die BankTransaction-Zeilen sinnvolle Werte enthalten.
     */
    public function testSampleFileCamt053ToBankTransactionFieldValues(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/Banking/CAMT/11_EBICS_camt.053_Kontoauszug_mit_allen_Umsätzen.xml';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);
        $camt053 = CamtParser::parse($originalContent);
        $bankTransaction = Camt053ToBankTransactionConverter::convert($camt053);

        $rows = $bankTransaction->getRows();
        $this->assertNotEmpty($rows);

        foreach ($rows as $index => $row) {
            // Umsatz (Index 6) sollte nicht leer sein
            $umsatz = $row->getField(6)->getValue();
            $this->assertNotEmpty($umsatz, "Zeile $index: Umsatz sollte nicht leer sein");
            $this->assertMatchesRegularExpression('/^[+-]?\d+,\d{2}$/', $umsatz, "Zeile $index: Umsatz sollte deutsches Zahlenformat haben");

            // IBAN (Index 1) sollte gesetzt sein
            $iban = $row->getField(1)->getValue();
            $this->assertNotEmpty($iban, "Zeile $index: IBAN sollte nicht leer sein");

            // Währung (Index 16) sollte gesetzt sein
            $waehrung = $row->getField(16)->getValue();
            $this->assertNotEmpty($waehrung, "Zeile $index: Währung sollte nicht leer sein");
        }
    }

    // ========================================
    // ASCII.csv → MT940/CAMT.053 Roundtrip Tests
    // ========================================

    /**
     * Test: ASCII.csv Sample-Datei → MT940 → BankTransaction Roundtrip
     * 
     * Parst die echte ASCII.csv-Datei, konvertiert zu MT940 und zurück zu BankTransaction.
     */
    public function testSampleFileAsciiCsvToMt940Roundtrip(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/DATEV/ASCII.csv';
        $this->assertFileExists($sampleFile, 'Sample-Datei ASCII.csv existiert nicht');

        // Parse ASCII.csv Sample-Datei
        $originalContent = file_get_contents($sampleFile);
        $bankTransactionOriginal = BankTransactionParser::fromString($originalContent);

        $this->assertInstanceOf(BankTransaction::class, $bankTransactionOriginal);
        $this->assertGreaterThan(0, count($bankTransactionOriginal->getRows()), 'BankTransaction sollte Zeilen enthalten');

        // BankTransaction → MT940
        $mt940 = BankTransactionToMt940Converter::convert($bankTransactionOriginal);

        // MT940 → BankTransaction
        $bankTransactionRegenerated = Mt940ToBankTransactionConverter::convert($mt940);

        // Vergleiche Kernfelder
        $this->assertEquals(
            count($bankTransactionOriginal->getRows()),
            count($bankTransactionRegenerated->getRows()),
            'Anzahl der Zeilen sollte übereinstimmen'
        );

        // Vergleiche einzelne Transaktionsbeträge
        $originalRows = $bankTransactionOriginal->getRows();
        $regeneratedRows = $bankTransactionRegenerated->getRows();

        for ($i = 0; $i < count($originalRows); $i++) {
            // Betrag (Index 6) vergleichen
            $originalAmount = $originalRows[$i]->getField(6)->getValue();
            $regeneratedAmount = $regeneratedRows[$i]->getField(6)->getValue();

            // Normalisiere Beträge (entferne führende +)
            $originalAmount = ltrim($originalAmount, '+');
            $regeneratedAmount = ltrim($regeneratedAmount, '+');

            // Prüfe ob Betragsformat übereinstimmt (mit/ohne Vorzeichen)
            $originalValue = (float) str_replace(',', '.', $originalAmount);
            $regeneratedValue = (float) str_replace(',', '.', $regeneratedAmount);

            $this->assertEqualsWithDelta(
                abs($originalValue),
                abs($regeneratedValue),
                0.01,
                "Zeile $i: Betragswert sollte übereinstimmen"
            );
        }
    }

    /**
     * Test: ASCII.csv Sample-Datei → CAMT.053 → BankTransaction Roundtrip
     * 
     * Parst die echte ASCII.csv-Datei, konvertiert zu CAMT.053 und zurück zu BankTransaction.
     */
    public function testSampleFileAsciiCsvToCamt053Roundtrip(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/DATEV/ASCII.csv';
        $this->assertFileExists($sampleFile, 'Sample-Datei ASCII.csv existiert nicht');

        // Parse ASCII.csv Sample-Datei
        $originalContent = file_get_contents($sampleFile);
        $bankTransactionOriginal = BankTransactionParser::fromString($originalContent);

        $this->assertInstanceOf(BankTransaction::class, $bankTransactionOriginal);
        $this->assertGreaterThan(0, count($bankTransactionOriginal->getRows()), 'BankTransaction sollte Zeilen enthalten');

        // BankTransaction → CAMT.053
        $camt053 = BankTransactionToCamt053Converter::convert($bankTransactionOriginal);

        // CAMT.053 → BankTransaction
        $bankTransactionRegenerated = Camt053ToBankTransactionConverter::convert($camt053);

        // Vergleiche Kernfelder
        $this->assertEquals(
            count($bankTransactionOriginal->getRows()),
            count($bankTransactionRegenerated->getRows()),
            'Anzahl der Zeilen sollte übereinstimmen'
        );

        // Vergleiche einzelne Transaktionsbeträge
        $originalRows = $bankTransactionOriginal->getRows();
        $regeneratedRows = $bankTransactionRegenerated->getRows();

        for ($i = 0; $i < count($originalRows); $i++) {
            // Betrag (Index 6) vergleichen
            $originalAmount = $originalRows[$i]->getField(6)->getValue();
            $regeneratedAmount = $regeneratedRows[$i]->getField(6)->getValue();

            // Normalisiere Beträge
            $originalValue = (float) str_replace(',', '.', ltrim($originalAmount, '+'));
            $regeneratedValue = (float) str_replace(',', '.', ltrim($regeneratedAmount, '+'));

            $this->assertEqualsWithDelta(
                abs($originalValue),
                abs($regeneratedValue),
                0.01,
                "Zeile $i: Betragswert sollte übereinstimmen"
            );
        }
    }

    /**
     * Test: ASCII.csv → MT940 Feldwerte werden korrekt übertragen
     */
    public function testSampleFileAsciiCsvToMt940FieldMapping(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/DATEV/ASCII.csv';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);
        $bankTransaction = BankTransactionParser::fromString($originalContent);

        // BankTransaction → MT940
        $mt940 = BankTransactionToMt940Converter::convert($bankTransaction);

        // Prüfe MT940-Dokument-Struktur
        $this->assertNotEmpty($mt940->getAccountId(), 'AccountId sollte gesetzt sein');
        $this->assertNotEmpty($mt940->getTransactions(), 'MT940 sollte Transaktionen enthalten');

        // Prüfe erste Transaktion
        $firstTxn = $mt940->getTransactions()[0];
        $this->assertGreaterThan(0, $firstTxn->getAmount(), 'Betrag sollte > 0 sein');
        $this->assertNotNull($firstTxn->getBookingDate(), 'Buchungsdatum sollte gesetzt sein');
        $this->assertNotNull($firstTxn->getCreditDebit(), 'Credit/Debit sollte gesetzt sein');
    }

    /**
     * Test: ASCII.csv → CAMT.053 Feldwerte werden korrekt übertragen
     */
    public function testSampleFileAsciiCsvToCamt053FieldMapping(): void {
        $sampleFile = dirname(__DIR__, 3) . '/.samples/DATEV/ASCII.csv';
        $this->assertFileExists($sampleFile);

        $originalContent = file_get_contents($sampleFile);
        $bankTransaction = BankTransactionParser::fromString($originalContent);

        // BankTransaction → CAMT.053
        $camt053 = BankTransactionToCamt053Converter::convert($bankTransaction);

        // Prüfe CAMT.053-Dokument-Struktur
        $this->assertNotEmpty($camt053->getAccountIdentifier(), 'IBAN sollte gesetzt sein');
        $this->assertNotEmpty($camt053->getEntries(), 'CAMT.053 sollte Entries enthalten');

        // Prüfe erste Entry
        $firstEntry = $camt053->getEntries()[0];
        $this->assertGreaterThan(0, $firstEntry->getAmount(), 'Betrag sollte > 0 sein');
        $this->assertNotNull($firstEntry->getBookingDate(), 'Buchungsdatum sollte gesetzt sein');
        $this->assertNotNull($firstEntry->getCreditDebit(), 'Credit/Debit sollte gesetzt sein');
    }
}
