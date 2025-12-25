<?php
/*
 * Created on   : Mon Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVColumnWidthTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\CommonToolkit\Builders;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\Common\CSV\HeaderField;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\Common\CSV\DataField;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use Tests\Contracts\BaseTestCase;

class CSVColumnWidthTest extends BaseTestCase {

    public function testCSVDocumentBuilderWithColumnWidths(): void {
        // Erstelle Header
        $header = new HeaderLine([
            new HeaderField('Name'),
            new HeaderField('Email'),
            new HeaderField('City')
        ]);

        // Erstelle Datenzeilen mit langen Werten
        $row1 = new DataLine([
            new DataField('John Doe Smith Johnson'),
            new DataField('john.doe.smith.johnson@example.com'),
            new DataField('San Francisco')
        ]);

        $row2 = new DataLine([
            new DataField('Jane Mary Elizabeth Wilson'),
            new DataField('jane.wilson@company.co.uk'),
            new DataField('New York City')
        ]);

        // Erstelle ColumnWidthConfig
        $widthConfig = new ColumnWidthConfig();
        $widthConfig->setColumnWidth('Name', 10);
        $widthConfig->setColumnWidth('Email', 15);
        $widthConfig->setDefaultWidth(8); // Für City
        $widthConfig->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

        // Baue das Dokument
        $builder = new CSVDocumentBuilder(',', '"', $widthConfig);
        $document = $builder
            ->setHeader($header)
            ->addRow($row1)
            ->addRow($row2)
            ->build();

        // Konvertiere zu String und prüfe Spaltenbreiten
        $csvString = $document->toString();
        $lines = explode("\n", $csvString);

        // Header sollte normal bleiben (da kurz genug)
        $this->assertEquals('Name,Email,City', $lines[0]);

        // Erste Datenzeile - lange Werte sollten gekürzt werden
        $this->assertStringContainsString('John Do...', $lines[1]); // Name auf 10 gekürzt
        $this->assertStringContainsString('john.doe.smi...', $lines[1]); // Email auf 15 gekürzt  
        $this->assertStringContainsString('San F...', $lines[1]); // City auf 8 gekürzt

        // Zweite Datenzeile
        $this->assertStringContainsString('Jane Ma...', $lines[2]); // Name auf 10 gekürzt
        $this->assertStringContainsString('jane.wilson@...', $lines[2]); // Email auf 15 gekürzt
        $this->assertStringContainsString('New Y...', $lines[2]); // City auf 8 gekürzt
    }

    public function testCSVDocumentBuilderWithTruncateStrategy(): void {
        $header = new HeaderLine([
            new HeaderField('Name'),
        ]);

        $row1 = new DataLine([
            new DataField('Very Long Name That Should Be Truncated'),
        ]);

        $widthConfig = new ColumnWidthConfig();
        $widthConfig->setColumnWidth('Name', 10);
        $widthConfig->setTruncationStrategy(TruncationStrategy::TRUNCATE);

        $builder = new CSVDocumentBuilder(',', '"', $widthConfig);
        $document = $builder
            ->setHeader($header)
            ->addRow($row1)
            ->build();

        $csvString = $document->toString();
        $lines = explode("\n", $csvString);

        $this->assertEquals('Name', $lines[0]);
        $this->assertEquals('Very Long ', $lines[1]); // Exactly 10 characters, no ellipsis
    }

    public function testCSVDocumentBuilderWithIndexBasedColumnWidths(): void {
        $header = new HeaderLine([
            new HeaderField('Col1'),
            new HeaderField('Col2'),
            new HeaderField('Col3')
        ]);

        $row1 = new DataLine([
            new DataField('Long content for column 1'),
            new DataField('Another long content for column 2'),
            new DataField('Short')
        ]);

        $widthConfig = new ColumnWidthConfig();
        $widthConfig->setColumnWidth(0, 8); // Erste Spalte
        $widthConfig->setColumnWidth(1, 12); // Zweite Spalte
        // Dritte Spalte bleibt ohne Beschränkung

        $builder = new CSVDocumentBuilder(',', '"', $widthConfig);
        $document = $builder
            ->setHeader($header)
            ->addRow($row1)
            ->build();

        $csvString = $document->toString();
        $lines = explode("\n", $csvString);

        $this->assertEquals('Col1,Col2,Col3', $lines[0]);
        $this->assertStringContainsString('Long con', $lines[1]); // Spalte 0 auf 8 gekürzt (truncate)
        $this->assertStringContainsString('Another long', $lines[1]); // Spalte 1 auf 12 gekürzt (truncate)
        $this->assertStringContainsString('Short', $lines[1]); // Spalte 2 unverändert
    }

    public function testCSVDocumentBuilderFluentColumnWidthSetup(): void {
        $header = new HeaderLine([
            new HeaderField('Name'),
            new HeaderField('Email')
        ]);

        $row1 = new DataLine([
            new DataField('Very Long Name'),
            new DataField('very.long.email@domain.com')
        ]);

        $builder = new CSVDocumentBuilder();
        $document = $builder
            ->setHeader($header)
            ->addRow($row1)
            ->setColumnWidth('Name', 8)
            ->setColumnWidth('Email', 12)
            ->setTruncationStrategy(TruncationStrategy::ELLIPSIS)
            ->build();

        $csvString = $document->toString();
        $lines = explode("\n", $csvString);

        $this->assertEquals('Name,Email', $lines[0]);
        $this->assertStringContainsString('Very ...', $lines[1]);
        $this->assertStringContainsString('very.long...', $lines[1]);
    }

    public function testCSVDocumentBuilderDefaultColumnWidth(): void {
        $header = new HeaderLine([
            new HeaderField('Col1'),
            new HeaderField('Col2'),
            new HeaderField('Col3')
        ]);

        $row1 = new DataLine([
            new DataField('This is a very long text for column 1'),
            new DataField('Another long text for column 2'),
            new DataField('And third column text')
        ]);

        $builder = new CSVDocumentBuilder();
        $document = $builder
            ->setHeader($header)
            ->addRow($row1)
            ->setDefaultColumnWidth(10)
            ->setTruncationStrategy(TruncationStrategy::ELLIPSIS)
            ->build();

        $csvString = $document->toString();
        $lines = explode("\n", $csvString);

        $this->assertEquals('Col1,Col2,Col3', $lines[0]);

        // Alle Spalten sollten auf 10 Zeichen begrenzt sein
        $parts = explode(',', $lines[1]);
        $this->assertTrue(mb_strlen($parts[0]) <= 10);
        $this->assertTrue(mb_strlen($parts[1]) <= 10);
        $this->assertTrue(mb_strlen($parts[2]) <= 10);

        // Alle sollten mit ... enden (ellipsis strategy)
        $this->assertStringEndsWith('...', $parts[0]);
        $this->assertStringEndsWith('...', $parts[1]);
        $this->assertStringEndsWith('...', $parts[2]);
    }

    public function testCSVDocumentFromDocumentPreservesColumnWidthConfig(): void {
        $header = new HeaderLine([
            new HeaderField('Name'),
        ]);

        $row1 = new DataLine([
            new DataField('Very Long Name'),
        ]);

        $widthConfig = new ColumnWidthConfig();
        $widthConfig->setColumnWidth('Name', 8);

        $originalBuilder = new CSVDocumentBuilder(',', '"', $widthConfig);
        $originalDocument = $originalBuilder
            ->setHeader($header)
            ->addRow($row1)
            ->build();

        // Erstelle neuen Builder aus bestehendem Dokument
        $newBuilder = CSVDocumentBuilder::fromDocument($originalDocument);
        $newDocument = $newBuilder->build();

        // Die Spaltenbreiten-Konfiguration sollte übernommen worden sein
        $this->assertNotNull($newDocument->getColumnWidthConfig());
        $this->assertEquals(8, $newDocument->getColumnWidthConfig()->getColumnWidth('Name'));

        $csvString = $newDocument->toString();
        $lines = explode("\n", $csvString);
        $this->assertStringContainsString('Very Lon', $lines[1]); // Truncate strategy, auf 8 Zeichen gekürzt
    }
}
