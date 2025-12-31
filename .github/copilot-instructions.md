# PHP Common Toolkit - AI Development Guide

## Architecture Overview

This is a general-purpose PHP utility toolkit with a layered architecture:
- **Entities**: Immutable domain models for CSV processing and executable wrappers
- **Builders/Parsers**: Document construction and parsing with strict validation
- **Helpers**: Platform-agnostic utilities with external executable integration via JSON configs
- **Contracts**: Abstract base classes following PSR patterns with consistent error handling
- **Enums**: Typed enums with factory methods (CurrencyCode, CountryCode, CreditDebit)

**Note**: Banking formats (CAMT, MT940, Pain, Swift) and DATEV accounting have been moved to `php-financial-formats`.

## Directory Structure (Critical - Preserve!)

The directory structure is essential and must be maintained:
```
src/
├── Builders/           # Fluent document builders (CSV)
├── Contracts/          # Abstract base classes and Interfaces
│   ├── Abstracts/      # Base classes (HelperAbstract, ExecutableAbstract, etc.)
│   └── Interfaces/     # Interface definitions
├── Entities/           # Immutable domain models
│   ├── Common/         # CSV entities
│   └── Executables/    # Shell/Java executable wrappers
├── Enums/              # Typed enums with factory methods
│   └── Common/         # CSV enums
├── Helper/             # Utility classes
│   ├── Data/           # BankHelper, CurrencyHelper, StringHelper, etc.
│   ├── FileSystem/     # File, PdfFile, TiffFile, XmlFile
│   └── Shell/          # Process execution utilities
├── Parsers/            # Document parsers (CSV)
└── Traits/             # Reusable traits

tests/                  # Mirrors src/ structure exactly
config/                 # JSON configurations (executables, helper settings)
data/                   # Bundesbank data (BLZ/BIC)
```

**Important**: When adding new classes, place them in the correct subdirectory matching their domain and responsibility.

## Critical Architectural Patterns

### Helper Hierarchy & Configuration Management
All helpers extend either `HelperAbstract` or `ConfiguredHelperAbstract`:
```php
// Simple helpers: direct validation/utility methods
class BankHelper extends HelperAbstract
// Configured helpers: external executable integration
class PdfFile extends ConfiguredHelperAbstract
```
- **Required setup**: Call `setLogger()` before use - defaults to `ConsoleLoggerFactory`
- **File validation**: Use `resolveFile()` for safe file operations with automatic error logging
- **External executables**: JSON configs in `config/*_executables.json` with parameter replacement

### Data Flow Architecture: Builder → Entity → Parser
1. **Builders** create documents fluently: `CSVDocumentBuilder->addLine()->build()`
2. **Entities** are immutable with validation
3. **Parsers** handle complex formats: multi-line CSV parsing
4. **Key pattern**: Use `match()` expressions for type-safe branching in PHP 8.1+

### Configuration-Driven External Dependencies
- **Bundesbank data**: Auto-downloads BLZ/BIC data with expiry tracking via `config/helper.json`
- **System executables**: Platform abstraction through `Shell::executeShellCommand()`
- **Required tools**: ImageMagick, TIFF tools, Xpdf, muPDF (install via `installscript/install-dependencies.sh`)
- **Config pattern**: `getResolvedExecutableConfig()` with parameter substitution

## Essential Development Patterns

### Enum Design with Traits
Enums use consistent patterns:
```php
// Complex enums with validation
CurrencyCode::fromSymbol('€') // → CurrencyCode::EUR
CountryCode::fromStringValue('DE') // → CountryCode::Germany
```

### CSV Field Architecture
CSV processing uses strict field typing:
- **FieldAbstract**: Handles quoting, escaping, `enclosureRepeat` tracking
- **LineAbstract**: Manages field collections with delimiter/enclosure
- **Document**: Immutable container with header + data rows
- **Key method**: `StringHelper::splitCsvByLogicalLine()` for multi-line CSV parsing

### BankHelper Utilities
The `BankHelper` provides banking validation utilities:
```php
BankHelper::isValidIBAN('DE89370400440532013000') // true
BankHelper::isValidBIC('COBADEFFXXX') // true
BankHelper::getBankNameByBLZ('37040044') // "Commerzbank"
```

## Development Workflows

### Testing & Validation
```bash
composer test                           # Run all PHPUnit tests
vendor/bin/phpunit --testdox          # Verbose test descriptions
# Test structure mirrors src/ with BaseTestCase setup
```
- **BaseTestCase**: Auto-configures error logging via `LoggerRegistry`
- **Sample files**: Use `.samples/` directory for test data
- **Focus areas**: Validation logic, file processing, external executable integration

### Configuration Management
```php
// Auto-loading external data with expiry
ConfigLoader::getInstance()->get('Bundesbank', 'file', 'default-path')
// Executable resolution with parameter replacement
getResolvedExecutableConfig('mimetype', ['[INPUT]' => $file])
```
- **Config files**: `helper.json` (data sources), `*_executables.json` (system tools)
- **Singleton pattern**: ConfigLoader with lazy loading and file caching
- **Expiry system**: Automatic re-download when data exceeds expiry_days

## Critical Implementation Details

- **Date handling**: Prefer `DateTimeImmutable`, flexible constructor parsing (`'ymd'`, `'Ymd'`)
- **Validation**: Use `bcmod()` for IBAN checksum, regex patterns for BIC/BLZ
- **Error handling**: `ErrorLog` trait with PSR-3 logging, custom exceptions from `ERRORToolkit`
- **German locale**: Domain comments and error messages in German for banking compliance
- **Type safety**: Strict types enabled, comprehensive type hints, match expressions