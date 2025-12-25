# CSV Spaltenbreiten-Funktionalität

Die CSV-Logik wurde um die Möglichkeit erweitert, Spaltenbreiten zu fixieren (maximale Zeichenanzahl pro Spalte).

## Features

- **Spaltenspezifische Breiten**: Definiere verschiedene maximale Breiten für verschiedene Spalten
- **Standard-Spaltenbreite**: Setze eine Standardbreite für alle nicht explizit konfigurierten Spalten
- **Flexible Konfiguration**: Spalten können über Namen oder Index konfiguriert werden
- **Abschneidungsstrategien**: Wähle zwischen `truncate` (einfaches Abschneiden) und `ellipsis` (mit "..." am Ende)
- **Fluent API**: Einfache Konfiguration über den CSVDocumentBuilder

## Klassen

### `ColumnWidthConfig`

Die Hauptkonfigurationsklasse für Spaltenbreiten.

```php
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;

$config = new ColumnWidthConfig();
$config->setColumnWidth('Name', 10);           // Spalte 'Name' max. 10 Zeichen
$config->setColumnWidth(0, 15);               // Erste Spalte max. 15 Zeichen
$config->setDefaultWidth(12);                 // Standard für alle anderen
$config->setTruncationStrategy('ellipsis');   // Verwende "..." beim Kürzen
```

#### Methoden

- `setColumnWidth(string|int $column, int $width)`: Setzt Breite für spezifische Spalte
- `setColumnWidths(array $widths)`: Setzt mehrere Spaltenbreiten gleichzeitig
- `setDefaultWidth(?int $width)`: Setzt Standardbreite für alle Spalten
- `setTruncationStrategy(string $strategy)`: 'truncate' oder 'ellipsis'
- `getColumnWidth(string|int $column)`: Gibt konfigurierte Breite zurück
- `truncateValue(string $value, string|int $column)`: Kürzt Wert basierend auf Konfiguration

### Erweiterte CSV-Klassen

Alle bestehenden CSV-Klassen wurden erweitert:

- **`CSVDocumentBuilder`**: Unterstützt ColumnWidthConfig im Konstruktor und als fluent API
- **`Document`**: Speichert und verwendet ColumnWidthConfig beim toString()
- **`LineAbstract`**: Wendet Spaltenbreiten beim toString() an
- **`FieldAbstract`**: Unterstützt maximale Breite im toString()

## Verwendung

### 1. Mit CSVDocumentBuilder (Konstruktor)

```php
use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;

$widthConfig = new ColumnWidthConfig();
$widthConfig->setColumnWidth('Name', 15);
$widthConfig->setColumnWidth('Email', 25);
$widthConfig->setDefaultWidth(10);
$widthConfig->setTruncationStrategy('ellipsis');

$builder = new CSVDocumentBuilder(',', '"', $widthConfig);
$document = $builder->setHeader($header)->addRows($rows)->build();
```

### 2. Fluent API

```php
$document = (new CSVDocumentBuilder())
    ->setHeader($header)
    ->addRows($rows)
    ->setColumnWidth('Name', 15)
    ->setColumnWidth('Email', 25)
    ->setDefaultColumnWidth(10)
    ->setTruncationStrategy('ellipsis')
    ->build();
```

### 3. Index-basierte Konfiguration

```php
$widthConfig = new ColumnWidthConfig();
$widthConfig->setColumnWidth(0, 10);  // Erste Spalte
$widthConfig->setColumnWidth(1, 15);  // Zweite Spalte
$widthConfig->setColumnWidth(2, 8);   // Dritte Spalte
```

### 4. Bestehende Dokumente erweitern

```php
$document = // ... bestehendes CSV-Dokument

$widthConfig = new ColumnWidthConfig();
$widthConfig->setDefaultWidth(12);
$document->setColumnWidthConfig($widthConfig);

$csvString = $document->toString(); // Verwendet jetzt die Spaltenbreiten
```

## Abschneidungsstrategien

### Truncate (Standard)

```php
$config->setTruncationStrategy('truncate');
```

- Schneidet bei der maximalen Breite ab
- Beispiel: "Very Long Text" → "Very Long " (bei Breite 10)

### Ellipsis

```php
$config->setTruncationStrategy('ellipsis');
```

- Fügt "..." am Ende hinzu wenn gekürzt wird
- Beispiel: "Very Long Text" → "Very Lo..." (bei Breite 10)
- Bei sehr kurzen Breiten (≤3 Zeichen) wird auf truncate zurückgefallen

## Beispiel

```php
use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\{HeaderLine, HeaderField, DataLine, DataField};

// Daten erstellen
$header = new HeaderLine([
    new HeaderField('Name'),
    new HeaderField('Email'),
    new HeaderField('Stadt')
]);

$row = new DataLine([
    new DataField('Max Mustermann'),
    new DataField('max.mustermann@beispiel-firma.de'),
    new DataField('München')
]);

// Mit Spaltenbreiten
$document = (new CSVDocumentBuilder())
    ->setHeader($header)
    ->addRow($row)
    ->setColumnWidth('Name', 12)
    ->setColumnWidth('Email', 20)
    ->setColumnWidth('Stadt', 8)
    ->setTruncationStrategy('ellipsis')
    ->build();

echo $document->toString();
```

Ausgabe:
```
Name,Email,Stadt
Max Muste...,max.mustermann@be...,München
```

## Kompatibilität

Die neue Funktionalität ist vollständig rückwärtskompatibel. Bestehender Code funktioniert unverändert weiter. Die Spaltenbreiten-Funktionalität ist optional und wird nur angewendet, wenn eine `ColumnWidthConfig` gesetzt wurde.
