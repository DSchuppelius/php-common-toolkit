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

```
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
    "convert": {
      "path": "convert",
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
|---------|--------------|
| `getConfiguredCommand($name, $params)` | Baut einen Shell-Befehl mit Platzhalter-Ersetzung |
| `getConfiguredJavaCommand($name, $params)` | Baut einen Java-Befehl (java -jar ...) |
| `isExecutableAvailable($name)` | Prüft ob ein Tool verfügbar ist |
| `getExecutablePath($name)` | Gibt den konfigurierten Pfad zurück |
| `getResolvedExecutableConfig($name, $params)` | Gibt die vollständige Tool-Konfiguration zurück |

---

## License

This project is licensed under the **MIT License**.

**Daniel Joerg Schuppelius**
📧 info@schuppelius.org