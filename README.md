# php-common-toolkit

General-purpose PHP utility toolkit providing platform-agnostic helpers, CSV processing, and executable wrappers.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## Features

- **CSV Processing**: Fluent builders and parsers for CSV documents with strict field typing
- **Executable Wrappers**: Platform-agnostic integration with external tools (ImageMagick, TIFF tools, PDF tools)
- **Helper Utilities**: Bank validation (IBAN, BIC, BLZ), currency formatting, string manipulation
- **Enum Support**: Typed enums with factory methods (CurrencyCode, CountryCode, CreditDebit, LanguageCode)
- **XML Builders**: Extended DOM document builder for structured XML generation
- **Bundesbank Data**: Auto-downloading BLZ/BIC data with expiry tracking

---

## Architecture

```text
src/
├── Builders/           # Fluent document builders (CSV, XML)
├── Contracts/          # Abstract base classes and interfaces
├── Entities/           # Immutable domain models (CSV, Executables, XML)
├── Enums/              # Typed enums with factory methods
├── Generators/         # Code generators
├── Helper/             # Utility classes (Data, FileSystem, Shell)
├── Parsers/            # Document parsers (CSV)
└── Traits/             # Reusable traits
```

---

## Requirements

The following tools are required to successfully run `dschuppelius/php-common-toolkit`:

### 1. TIFF Tools

Required for processing and handling TIFF files.

- **Windows**: [GnuWin32 TIFF Tools](https://gnuwin32.sourceforge.net/packages/tiff.htm)
- **Debian/Ubuntu**:

  ```bash
  apt install libtiff-tools
  ```

### 2. Xpdf

Required for handling PDF files.

- **Windows**: [Xpdf Download](https://www.xpdfreader.com/download.html)
- **Debian/Ubuntu**:

  ```bash
  apt install xpdf
  ```

### 3. ImageMagick

For converting and processing image files.

- **Windows**: [ImageMagick Installer](https://imagemagick.org/archive/binaries/ImageMagick-7.1.1-39-Q16-HDRI-x64-dll.exe)
- **Debian/Ubuntu**:

  ```bash
  apt install imagemagick-6.q16hdri
  ```

### 4. muPDF Tools

For processing PDF and XPS documents.

- **Debian/Ubuntu**:

  ```bash
  apt install mupdf-tools
  ```

### 5. QPDF

For advanced PDF manipulation and processing.

- **Windows**: [QPDF Download](https://github.com/qpdf/qpdf/releases)
- **Debian/Ubuntu**:

  ```bash
  apt install qpdf
  ```

### Install the Toolkit into your Project

The Toolkit requires a PHP version of 8.1 or higher. The recommended way to install the SDK is through [Composer](http://getcomposer.org).

```bash
composer require dschuppelius/php-common-toolkit
```

---

## Usage Examples

### Value Objects

Immutable, exakt rechnende Value Objects unter `CommonToolkit\ValueObjects` —
Konstruktion über benannte Factories (`of()` wirft bei ungültiger Eingabe,
`tryFrom()`/`ofNullable()` liefern `null`). Sensible Identifikatoren (`Iban`,
`EmailAddress`, `PhoneNumber`, `VatNumber`, `CreditorIdentifier`,
`GermanTaxId`, `GermanTaxNumber`) implementieren bewusst weder `Stringable`
noch `JsonSerializable` — Klarwert nur über `getValue()`, Anzeige über
`masked()`.

```php
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\ValueObjects\{Decimal, ExchangeRate, Money, Percentage, Quantity};

// Decimal: exakte bcmath-Arithmetik, kein float
Decimal::of('0.1')->plus(Decimal::of('0.2'))->getValue();   // "0.3"
Decimal::of('1.234,56')->getValue();                        // "1234.56" (DE-Format)
Decimal::of('10')->dividedBy(Decimal::of('3'), 2)->getValue(); // "3.33"

// Percentage: delegiert Geldrechnung an Money
$vat = Percentage::of(19);
$vat->amountOf(Money::of('8.15', CurrencyCode::Euro))->getAmount(); // "1.55"
$vat->addTo(Money::of('100.00', CurrencyCode::Euro))->getAmount();  // "119.00"

// Quantity: Menge + Einheit, Arithmetik nur bei gleicher Einheit
$hours = Quantity::of('2,5', 'h')->plus(Quantity::of('0.25', 'h'));
$hours->format(); // "2,75 h"

// ExchangeRate: eindeutige Kursrichtung, ISO-Zielskala
$rate = ExchangeRate::of(CurrencyCode::Euro, CurrencyCode::SwissFranc, '0.9385');
$rate->convert(Money::of('100.00', CurrencyCode::Euro))->getAmount(); // "93.85"
```

```php
use CommonToolkit\ValueObjects\{DateRange, DateTimeRange, EmailAddress, Iban, PhoneNumber};

// DateRange: Kalendertage, beidseitig inklusiv
$july = DateRange::fromStrings('2026-07-01', '2026-07-31');
$july->calendarDays();                                    // 31
$july->contains(new DateTimeImmutable('2026-07-31'));     // true

// DateTimeRange: halboffen [start, end) — Folgebuchungen überlappen nicht
$morning = DateTimeRange::between(new DateTimeImmutable('08:00'), new DateTimeImmutable('12:00'));

// Sensible Identifikatoren: validiert, maskierbar, kein implizites Leaken
$iban = Iban::of('de89 3704 0044 0532 0130 00');
$iban->formatted(); // "DE89 3704 0044 0532 0130 00"
$iban->masked();    // "DE89XXXXXXXXXXXXXX3000"

EmailAddress::of('Max@EXAMPLE.com')->masked();  // "m**@example.com"
PhoneNumber::of('089 / 12 34 56 78')->getValue(); // "+498912345678" (E.164)
```

```php
use CommonToolkit\ValueObjects\{ByteSize, Duration, Gtin, IpAddress};

// Gtin: EAN/UPC mit Prüfziffer (Längen 8/12/13/14)
$gtin = Gtin::of('4006381-333931');
$gtin->getValue();    // "4006381333931"
$gtin->toGtin14();    // "04006381333931" (Prüfziffer bleibt gültig)

// IpAddress: sensibel — kein implizites Leaken, DSGVO-Anonymisierung
$ip = IpAddress::of('192.168.2.77');
$ip->isPrivate();                  // true
$ip->anonymized()->getValue();     // "192.168.2.0" (/24; IPv6: /48)

// ByteSize: exakte Bytes statt float-Umrechnung
ByteSize::parse('1,5 GB')->getBytes();     // 1610612736
ByteSize::ofBytes(1572864)->format();      // "1.5 MB"

// Duration: exakte Sekunden für Zeiterfassung und Salden
$work = Duration::of(8, 30);
$work->toClock();                          // "8:30"
$work->minus(Duration::ofHours(9))->toClock(); // "-0:30"
Duration::fromIso8601('PT8H30M')->equals($work); // true
```

### CSV Processing

```php
use CommonToolkit\Builders\CSVDocumentBuilder;

$document = CSVDocumentBuilder::create()
    ->setDelimiter(';')
    ->setEnclosure('"')
    ->addHeaderLine(['Name', 'Amount', 'Date'])
    ->addDataLine(['Max Mustermann', '1000.00', '2025-01-15'])
    ->addDataLine(['John Doe', '2500.00', '2025-01-16'])
    ->build();

echo $document->toString();
```

### Bank Validation

```php
use CommonToolkit\Helper\Data\BankHelper;

// IBAN Validation
$isValid = BankHelper::isValidIBAN('DE89370400440532013000'); // true

// BIC Validation
$isValid = BankHelper::isValidBIC('COBADEFFXXX'); // true

// Get Bank Name by BLZ
$bankName = BankHelper::getBankNameByBLZ('37040044'); // "Commerzbank"
```

### Bankleitzahl-/BIC-Daten (BLZ/BIC data)

Die Bundesbank-Datendateien werden **mit dem Paket ausgeliefert**, daher funktionieren
`BankHelper::bicFromIBAN()`, `bicFromBLZ()`, `blzFromBIC()` und `checkBIC()` **out-of-the-box
auch offline** – ohne vorherigen Online-Lauf:

- `data/blz-aktuell-txt-data.txt` (Bankleitzahlen, ~2,3 MB)
- `data/verzeichnis-der-erreichbaren-zahlungsdienstleister-data.csv` (BIC-Verzeichnis)

Bei Ablauf (`expiry_days` in `config/helper.json`, Default 365 Tage) werden die Daten beim
nächsten Zugriff **online von bundesbank.de aktualisiert**. Schlägt die Aktualisierung fehl
(z.B. offline), wird die vorhandene – ggf. veraltete, aber gültige – ausgelieferte Datei
weiterverwendet (Stale-Fallback) statt leerer Ergebnisse.

Den Netzzugriff kannst du programmatisch steuern:

```php
use CommonToolkit\Helper\Data\BankHelper;

// Online-Aktualisierung hart abschalten -> garantiert offline (nur ausgelieferte Datei)
BankHelper::setNetworkEnabled(false);

$bic = BankHelper::bicFromBLZ('10040000'); // "COBADEBBXXX" – kein Netzabruf

// Effektiven Schalter abfragen (Override > config network_enabled > Default true)
BankHelper::isNetworkEnabled(); // false

// Zurück auf Config-Default; clearCache() setzt den Override ebenfalls zurück
BankHelper::setNetworkEnabled(null);
BankHelper::clearCache();
```

**Manuelles Aktualisieren:** Die beiden Dateien in `data/` können jederzeit durch die
aktuellen Versionen von bundesbank.de ersetzt werden (URLs in `config/helper.json` unter
`Bundesbank.resourceurl` bzw. `Zahlungsdienstleister.resourceurl`). Nach dem Ersetzen
`BankHelper::clearCache()` aufrufen, falls der Prozess weiterläuft.

### MIME-Typ ohne Datei (Byte-basiert)

`File::mimeType()` braucht einen Pfad. Liegt der Inhalt nur im Speicher (Upload,
HTTP-Antwort, entpackter Archiv-Eintrag), erkennt `File::mimeTypeFromContent()`
den Typ direkt aus den Bytes – gleiche Rückgabe-Semantik (`string|false`):

```php
use CommonToolkit\Helper\FileSystem\File;

$bytes = $request->getContent();

$mimeType = File::mimeTypeFromContent($bytes);   // "application/pdf" | false (leerer Inhalt)
$encoding = File::mimeEncodingFromContent($bytes); // "us-ascii" | "utf-8" | "binary" | false
$extension = File::extensionForMimeType($mimeType ?: ''); // "pdf"
```

Erkannt wird primär über `finfo::buffer()`. Fehlt `ext-fileinfo` oder liefert es
kein belastbares Ergebnis (`application/x-empty`, `application/octet-stream`),
greift `File::mimeTypeFromMagicBytes()` – eine deterministische
Magic-Bytes-Tabelle (PDF, PNG, JPEG, GIF, ZIP, TIFF, GZIP, BMP, RIFF/WebP)
plus Inhaltsheuristik für XML, HTML, JSON und Text. Unbekannter Binärinhalt
ergibt `application/octet-stream`, ein leerer String `false`.

### Currency Formatting

```php
use CommonToolkit\Helper\Data\CurrencyHelper;
use CommonToolkit\Enums\CurrencyCode;

$formatted = CurrencyHelper::format(1234.56, CurrencyCode::Euro); // "1.234,56 €"
```

### Enum Usage

```php
use CommonToolkit\Enums\CurrencyCode;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Enums\CreditDebit;

// Currency from Symbol
$currency = CurrencyCode::fromSymbol('€'); // CurrencyCode::Euro

// Country from Code
$country = CountryCode::fromStringValue('DE'); // CountryCode::Germany

// Credit/Debit from MT940 Code
$creditDebit = CreditDebit::fromMt940Code('C'); // CreditDebit::CREDIT
```

### Configured Helper mit CommandBuilder

Das Toolkit nutzt den `CommandBuilder` aus dem `php-config-toolkit` für elegantes Command-Building mit externen Tools:

```php
use CommonToolkit\Helper\FileSystem\FileTypes\PdfFile;

// PDF-Metadaten abrufen (nutzt intern pdfinfo)
$metadata = PdfFile::getMetaData('/path/to/document.pdf');
echo $metadata['Title'];
echo $metadata['Pages'];

// Prüfen ob PDF verschlüsselt ist
if (PdfFile::isEncrypted('/path/to/document.pdf')) {
    // PDF entschlüsseln
    PdfFile::decrypt('/path/to/encrypted.pdf', '/path/to/decrypted.pdf', 'password');
}

// PDF validieren
if (PdfFile::isValid('/path/to/document.pdf')) {
    echo "PDF ist gültig!";
}
```

### Eigene Helper mit Executable-Konfiguration

Erstelle eigene Helper-Klassen die externe Tools nutzen:

```php
use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\Shell;

class MyImageHelper extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../config/image_executables.json';

    public static function convertToJpeg(string $input, string $output): bool {
        $command = self::getConfiguredCommand('convert', [
            '[INPUT]' => $input,
            '[OUTPUT]' => $output
        ]);
        
        if ($command === null) {
            return false;
        }
        
        return Shell::executeShellCommand($command);
    }
    
    public static function isToolAvailable(string $toolName): bool {
        return self::isExecutableAvailable($toolName);
    }
}
```

Mit passender Konfigurationsdatei (`config/image_executables.json`):

```json
{
  "shellExecutables": {
    "tiffconvert": {
      "path": "tiffconvert",
      "required": false,
      "description": "ImageMagick Converter",
      "package": "imagemagick",
      "arguments": ["[INPUT]", "-quality", "85", "[OUTPUT]"]
    }
  }
}
```

---

## Executable Configuration

Das Toolkit nutzt JSON-Konfigurationsdateien für externe Tools. Die Konfiguration ermöglicht:

- **Platzhalter-Ersetzung**: `[INPUT]`, `[OUTPUT]` werden zur Laufzeit ersetzt
- **Pfad-Validierung**: Automatische Prüfung ob Tools installiert sind
- **Cross-Platform**: Unterschiedliche Pfade für Windows/Linux möglich
- **Zentrale Verwaltung**: Alle Tool-Konfigurationen an einem Ort

### Verfügbare Methoden in ConfiguredHelperAbstract

| Methode | Beschreibung |
| ------- | ------------ |
| `getConfiguredCommand($name, $params)` | Baut einen Shell-Befehl mit Platzhalter-Ersetzung |
| `getConfiguredJavaCommand($name, $params)` | Baut einen Java-Befehl (java -jar ...) |
| `isExecutableAvailable($name)` | Prüft ob ein Tool verfügbar ist |
| `getExecutablePath($name)` | Gibt den konfigurierten Pfad zurück |
| `getResolvedExecutableConfig($name, $params)` | Gibt die vollständige Tool-Konfiguration zurück |

---

## License

This project is licensed under the **MIT License**.

**Daniel Joerg Schuppelius**
📧 <info@schuppelius.org>
