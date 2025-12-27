# PHP Common Toolkit - AI Development Guide

## Architecture Overview

This is a banking/financial data processing toolkit with a layered architecture:
- **Entities**: Immutable domain models for Banking (MT940/CAMT053), DATEV accounting, and CSV processing
- **Builders/Parsers**: Document construction and parsing with strict validation
- **Helpers**: Platform-agnostic utilities with external executable integration via JSON configs
- **Contracts**: Abstract base classes following PSR patterns with consistent error handling
- **Enums**: Comprehensive financial standards with typed factory methods

## Directory Structure (Critical - Preserve!)

The directory structure is essential and must be maintained:
```
src/
├── Builders/           # Fluent document builders (CSV, MT940, DATEV)
├── Contracts/          # Abstract base classes and Interfaces
│   ├── Abstracts/      # Base classes (HelperAbstract, ExecutableAbstract, etc.)
│   └── Interfaces/     # Interface definitions
├── Converters/         # Format converters (DATEV)
├── Entities/           # Immutable domain models
│   ├── Common/         # Banking (CAMT, MT9, Swift), CSV entities
│   ├── DATEV/          # DATEV-specific entities (Documents, Headers)
│   └── Executables/    # Shell/Java executable wrappers
├── Enums/              # Typed enums with factory methods
│   ├── Common/         # CSV, Banking enums
│   └── DATEV/          # DATEV-specific enums (HeaderFields, MetaFields)
├── Helper/             # Utility classes
│   ├── Data/           # BankHelper, CurrencyHelper, etc.
│   ├── FileSystem/     # File, PdfFile, TiffFile, XmlFile
│   └── Shell/          # Process execution utilities
├── Parsers/            # Document parsers (CAMT, MT940, CSV, DATEV)
├── Registries/         # DATEV version registries
└── Traits/             # Reusable traits (LockFlagTrait, etc.)

tests/                  # Mirrors src/ structure exactly
config/                 # JSON configurations (executables, helper settings)
data/                   # Bundesbank data, XSD schemas
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
2. **Entities** are immutable with validation: MT940/CAMT053 banking transactions, DATEV bookings
3. **Parsers** handle complex formats: multi-line CSV parsing, banking format regex extraction
4. **Key pattern**: Use `match()` expressions for type-safe branching in PHP 8.1+

### Configuration-Driven External Dependencies
- **Bundesbank data**: Auto-downloads BLZ/BIC data with expiry tracking via `config/helper.json`
- **System executables**: Platform abstraction through `Shell::executeShellCommand()`
- **Required tools**: ImageMagick, TIFF tools, Xpdf, muPDF (install via `installscript/install-dependencies.sh`)
- **Config pattern**: `getResolvedExecutableConfig()` with parameter substitution

### Entity Design: Immutable Value Objects
Banking entities follow strict patterns:
```php
// Constructor validation with typed enums
new Transaction($date, $valutaDate, $amount, CreditDebit::DEBIT, CurrencyCode::EUR)
// Date handling: DateTimeImmutable with flexible string parsing
// Enum factories: CurrencyCode::fromSymbol('€'), DocumentLinkType::fromString('BEDI')
```

## Essential Development Patterns

### Enum Design with Traits
Financial enums use consistent patterns:
```php
// Binary state enums (0/1 flags)
enum PostingLock: int { 
    use LockFlagTrait;
    case NONE = 0; case LOCKED = 1; 
}
// Factory methods: fromInt(), fromStringValue(), isLocked()

// Complex enums with validation
CurrencyCode::fromSymbol('€') // → CurrencyCode::EUR
CountryCode::fromAlpha2('DE') // → CountryCode::Germany
```

### CSV Field Architecture
CSV processing uses strict field typing:
- **FieldAbstract**: Handles quoting, escaping, `enclosureRepeat` tracking
- **LineAbstract**: Manages field collections with delimiter/enclosure
- **Document**: Immutable container with header + data rows
- **Key method**: `StringHelper::splitCsvByLogicalLine()` for multi-line CSV parsing

### DATEV Integration Specifics
German accounting format with rigid structure:
- **MetaHeader**: 31-field header with regex validation patterns
- **Field validation**: Each field has specific regex via `MetaHeaderField::pattern()`
- **DocumentLink**: GUID-based document references with type validation
- **Builder pattern**: Fluent API with automatic field header generation

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