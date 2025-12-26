<?php
/*
 * Created on   : Tue Dec 24 2025
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
use Tests\Contracts\BaseTestCase;

/**
 * Tests für den BankTransactionBuilder.
 */
class BankTransactionBuilderTest extends BaseTestCase {

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
}