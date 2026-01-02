<?php
/*
 * Created on   : Tue Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : csv_column_width_example.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\CSV\HeaderLine;
use CommonToolkit\Entities\CSV\HeaderField;
use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Entities\CSV\DataField;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;

echo "=== CSV Spaltenbreiten-Funktionalität Demo ===\n\n";

// 1. Beispiel: Grundlegende Spaltenbreiten mit Ellipsis-Strategie
echo "1. Ellipsis-Strategie:\n";
echo "-" . str_repeat("-", 50) . "\n";

$header = new HeaderLine([
    new HeaderField('Name'),
    new HeaderField('Email'),
    new HeaderField('Stadt')
]);

$rows = [
    new DataLine([
        new DataField('Max Mustermann'),
        new DataField('max.mustermann@beispiel-firma.de'),
        new DataField('München')
    ]),
    new DataLine([
        new DataField('Anna Schmidt-Weber'),
        new DataField('anna.schmidt.weber@unternehmen.com'),
        new DataField('Hamburg')
    ])
];

$widthConfig = new ColumnWidthConfig();
$widthConfig->setColumnWidth('Name', 12);
$widthConfig->setColumnWidth('Email', 20);
$widthConfig->setColumnWidth('Stadt', 8);
$widthConfig->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

$builder = new CSVDocumentBuilder(',', '"', $widthConfig);
$document = $builder
    ->setHeader($header)
    ->addRows($rows)
    ->build();

echo $document->toString() . "\n\n";

// 2. Beispiel: Truncate-Strategie
echo "2. Truncate-Strategie:\n";
echo "-" . str_repeat("-", 50) . "\n";

$widthConfig2 = new ColumnWidthConfig();
$widthConfig2->setColumnWidth('Name', 12);
$widthConfig2->setColumnWidth('Email', 20);
$widthConfig2->setColumnWidth('Stadt', 8);
$widthConfig2->setTruncationStrategy(TruncationStrategy::TRUNCATE);

$builder2 = new CSVDocumentBuilder(',', '"', $widthConfig2);
$document2 = $builder2
    ->setHeader($header)
    ->addRows($rows)
    ->build();

echo $document2->toString() . "\n\n";

// 3. Beispiel: Standard-Spaltenbreite
echo "3. Standard-Spaltenbreite (10 Zeichen):\n";
echo "-" . str_repeat("-", 50) . "\n";

$widthConfig3 = new ColumnWidthConfig();
$widthConfig3->setDefaultWidth(10);
$widthConfig3->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

$builder3 = new CSVDocumentBuilder();
$document3 = $builder3
    ->setHeader($header)
    ->addRows($rows)
    ->setColumnWidthConfig($widthConfig3)
    ->build();

echo $document3->toString() . "\n\n";

// 4. Beispiel: Fluent API
echo "4. Fluent API:\n";
echo "-" . str_repeat("-", 50) . "\n";

$builder4 = new CSVDocumentBuilder();
$document4 = $builder4
    ->setHeader($header)
    ->addRows($rows)
    ->setColumnWidth('Name', 15)
    ->setColumnWidth('Email', 25)
    ->setDefaultColumnWidth(10)  // Für nicht explizit konfigurierte Spalten
    ->setTruncationStrategy(TruncationStrategy::ELLIPSIS)
    ->build();

echo $document4->toString() . "\n\n";

// 5. Beispiel: Index-basierte Spaltenbreiten
echo "5. Index-basierte Konfiguration:\n";
echo "-" . str_repeat("-", 50) . "\n";

$widthConfig5 = new ColumnWidthConfig();
$widthConfig5->setColumnWidth(0, 10);  // Erste Spalte
$widthConfig5->setColumnWidth(1, 15);  // Zweite Spalte
$widthConfig5->setColumnWidth(2, 8);   // Dritte Spalte
$widthConfig5->setTruncationStrategy(TruncationStrategy::ELLIPSIS);

$builder5 = new CSVDocumentBuilder(',', '"', $widthConfig5);
$document5 = $builder5
    ->setHeader($header)
    ->addRows($rows)
    ->build();

echo $document5->toString() . "\n\n";

// 6. Beispiel: Padding für feste Spaltenbreiten
echo "6. Feste Spaltenbreiten mit Padding:\n";
echo "-" . str_repeat("-", 50) . "\n";

$widthConfig6 = new ColumnWidthConfig();
$widthConfig6->setColumnWidth('Name', 20);
$widthConfig6->setColumnWidth('Email', 35);
$widthConfig6->setColumnWidth('Stadt', 12);
$widthConfig6->setTruncationStrategy(TruncationStrategy::TRUNCATE);
$widthConfig6->setPadding(true);  // Padding aktivieren

$builder6 = new CSVDocumentBuilder(',', '"', $widthConfig6);
$document6 = $builder6
    ->setHeader($header)
    ->addRows($rows)
    ->build();

echo $document6->toString() . "\n\n";

echo "=== Demo beendet ===\n";
