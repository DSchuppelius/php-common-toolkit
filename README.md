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
- **Identifier & URL Checks**: `StringHelper::isUuid()`, `WebLinkHelper::isAbsoluteIri()` and `::origin()` for IRI/URL validation
- **Fuzzy Matching**: `StringHelper::similarity()` returns a normalised similarity score for two strings
- **HTML to Text**: `StringHelper::htmlToText()` turns HTML (mail bodies, OneNote pages) into readable text — paragraphs, `<br>`, table rows and list items become lines (`- ` bullets), scripts, styles and comments are dropped, entities are decoded; `::stripHtml()` remains the single-line variant
- **Person Names**: `PersonNameHelper::split()` breaks a free-text name into salutation (`mr`/`ms`, German, English, French, Spanish, Italian), academic title, first name, middle names and last name (particles such as von/van/de start the last name, "Last, First" is recognised); `::splitSalutation()` separates a salutation line such as "Herr Dr."; `::join()` reassembles a name without the salutation
- **iCalendar**: `ICalendarParser` reads `.ics` data (folding, escapes, TZID incl. Windows names, all-day, DURATION); `Document::expand()` resolves series (RRULE DAILY/WEEKLY/MONTHLY/YEARLY with BYDAY/BYMONTHDAY/BYMONTH/BYSETPOS, EXDATE, RECURRENCE-ID)
- **Creditor Reference (ISO 11649)**: `CreditorReferenceHelper::create()` / `::isValid()` / `::format()` / `::extract()` — build RF references from an identifier, check MOD 97-10 digits, print in groups of four and find them in remittance text
- **CEF (SIEM)**: `CEFGenerator::line()` writes Common Event Format lines with header and extension escaping
- **ZIP Entry Filtering**: `ZipFile::readEntries()` / `::readEntriesFromFile()` / `::extract()` with a `skipEntry` callback — inspect archives entry by entry and skip unsafe or unwanted members before extraction
- **Value Objects**: typed wrappers such as `WasteCode` (European waste catalogue)
- **Data URLs**: `DataUrlHelper::decode()` / `::parse()` / `::encode()` — strict RFC 2397 decoding with size limit and content-based type check (e.g. signature images from a canvas)

---

## Architecture

```text
src/
├── Builders/           # Fluent document builders (CSV, HTML, XLSX, XML, Payment/EPC QR)
├── Contracts/          # Abstract base classes and interfaces
├── Entities/           # Immutable domain models (CSV, Executables, HTML, ICalendar, XLSX, XML)
├── Enums/              # Typed enums with factory methods
├── Exceptions/         # Exceptions (Parsers)
├── Generators/         # Document generators (CEF, CSV, XLSX, XML)
├── Helper/             # Utility classes (Data, FileSystem, Geo, Media, Office, Shell, Java, Platform)
├── Parsers/            # Document parsers (CSV, HTML, ICalendar, XLSX, XML)
├── PHPStan/            # PHPStan rule: processes only via Shell
├── Traits/             # Reusable traits
└── ValueObjects/       # Typed value objects (Gtin, IpAddress, ByteSize, Duration, ...)
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

// Eigene Textform ist lesbar (Rundreise über __toString()/format())
Money::of('12.34 EUR', CurrencyCode::Euro)->getAmount();   // "12.34"
Percentage::of('19.00 %')->getNumericValue();              // "19.00"
Quantity::of('2.500 Stk', 'Stk')->getNumericValue();       // "2.500"

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
$gtin->toGtin14()->getValue();    // "04006381333931" (Prüfziffer bleibt gültig)

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

### iCalendar

```php
use CommonToolkit\Entities\ICalendar\Document;
use CommonToolkit\Parsers\ICalendarParser;

$zone = new DateTimeZone('Europe/Berlin');
$document = ICalendarParser::fromString($ics);
foreach ($document->getSeries() as $series) {
    foreach (Document::expand($series, $from, $until, $zone) as $occurrence) {
        $occurrence->getStart();                 // wall-clock kept across DST
        $occurrence->getEvent()->getSummary();   // override (RECURRENCE-ID) if present
    }
}
```

### CSV Processing

```php
use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\CSV\{DataLine, HeaderLine};

$document = (new CSVDocumentBuilder(';', '"'))
    ->setHeader(new HeaderLine(['Name', 'Amount', 'Date'], ';', '"'))
    ->addRow(new DataLine(['Max Mustermann', '1000.00', '2025-01-15'], ';', '"'))
    ->addRow(new DataLine(['John Doe', '2500.00', '2025-01-16'], ';', '"'))
    ->build();

echo $document->toString();
```

### Bank Validation

```php
use CommonToolkit\Helper\Data\BankHelper;

// IBAN Validation (Aufbau und Prüfziffer, MOD 97-10)
$isValid = BankHelper::checkIBAN('DE89370400440532013000'); // true
$isValid = BankHelper::validateIBAN('DE89370400440532013000', strict: true); // dasselbe, null-tolerant

// BIC Validation (Aufbau und Ländercode)
$isValid = BankHelper::isBIC('COBADEFFXXX'); // true

// Get Bank Name by BLZ
$bankName = BankHelper::bankNameFromBLZ('37040044'); // "Commerzbank"
```

### Creditor Reference (RF, ISO 11649)

```php
use CommonToolkit\Helper\Data\CreditorReferenceHelper;

CreditorReferenceHelper::create('539007547034');          // "RF18539007547034"
CreditorReferenceHelper::format('RF18539007547034');      // "RF18 5390 0754 7034"
CreditorReferenceHelper::isValid('RF18 5390 0754 7034');  // true
CreditorReferenceHelper::extract('Zahlung RF18 5390 0754 7034'); // ["RF18539007547034"]
```

### CEF Lines (SIEM)

```php
use CommonToolkit\Generators\CEF\CEFGenerator;

CEFGenerator::line('Acme', 'App', '1.0', 'auth.failed', 'Login failed', 5, ['src' => '203.0.113.7']);
// "CEF:0|Acme|App|1.0|auth.failed|Login failed|5|src=203.0.113.7"
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
plus Inhaltsheuristik für XML, HTML, JSON und Text. Meldet finfo `text/plain`,
verfeinert dieselbe Heuristik auf `text/xml`, `application/json` oder
`text/html` – finfo kennt XML nur am `<?xml`-Prolog, UBL/CII-Rechnungen kommen
auch ohne. Unbekannter Binärinhalt ergibt `application/octet-stream`, ein
leerer String `false`.

### Data-URLs (Bilder aus dem Browser)

Eine Zeichenfläche oder ein eingefügter Screenshot kommt als
`data:image/png;base64,…` beim Server an – also als Eingabe vom Client.
`DataUrlHelper::decode()` dekodiert streng, begrenzt die Größe und prüft den Typ
am **Inhalt**, nicht an der Angabe in der URL:

```php
use CommonToolkit\Helper\Data\DataUrlHelper;

$png = DataUrlHelper::decode($request->input('signature'), ['image/png'], 1_000_000);
if ($png === false) {
    // ungültig, zu groß, kein PNG oder Angabe passt nicht zum Inhalt
}

DataUrlHelper::parse('data:text/html;charset=utf-8,<b>x</b>');
// ['mime_type' => 'text/html', 'base64' => false, 'data' => '<b>x</b>']

DataUrlHelper::encode($bytes); // "data:image/png;base64,…" (Typ am Inhalt erkannt)
```

Reines Base64 ohne `data:`-Präfix wird ebenfalls angenommen. Die Fehlermeldung
bleibt Sache des Aufrufers: `decode()` liefert `false` statt einer Exception.

### Currency Formatting

```php
use CommonToolkit\Helper\Data\CurrencyHelper;
use CommonToolkit\Enums\CurrencyCode;

$formatted = CurrencyHelper::format(1234.56, CurrencyCode::Euro, 'de_DE'); // "1.234,56 €" (ohne Locale: Locale::getDefault())
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
| ------- | ------------ |
| `getConfiguredCommand($name, $params)` | Baut einen Shell-Befehl mit Platzhalter-Ersetzung |
| `getConfiguredArgv($name, $params)` | Liefert Programm und Argumente als Liste fuer `Shell::run()`/`Shell::execute()` (ohne Shell, mit Zeitgrenze) |
| `getConfiguredJavaCommand($name, $params)` | Baut einen Java-Befehl (java -jar ...) |
| `isExecutableAvailable($name)` | Prüft ob ein Tool verfügbar ist |
| `getExecutablePath($name)` | Gibt den konfigurierten Pfad zurück |
| `getResolvedExecutableConfig($name, $params)` | Gibt die vollständige Tool-Konfiguration zurück |

---

## Zeitgrenze für externe Programme

Jeder Prozessaufruf über `Shell` hat eine Zeitgrenze. `executeShellCommand()`, `executeShell()` und
`executeParallel()` nehmen sie als letzten Parameter `$timeout` (Sekunden); ohne Angabe gilt
`Shell::DEFAULT_TIMEOUT` (180 s). Für kurze Proben (pdfinfo, identify, file) gibt es `Shell::TIMEOUT_PROBE` (30 s).
Seit 2.10 gilt das auch für `run()` und `execute()`: `null` heißt dort ebenfalls Standard-Zeitgrenze, nicht mehr
„unbegrenzt“.

```php
use CommonToolkit\Helper\Shell;

Shell::executeShellCommand($command, $output, $code);                                   // höchstens 180 s
Shell::executeShellCommand($command, $output, $code, timeout: Shell::TIMEOUT_PROBE);    // höchstens 30 s
Shell::setDefaultTimeout(3600.0);   // prozessweit, z. B. im Worker für lange OCR-Läufe; null = wieder 180 s
```

Nach Ablauf liefert der Aufruf `false`, `$code` ist `Shell::EXIT_TIMEOUT` (124), die letzte Zeile von `$output`
lautet `Zeitgrenze N s ueberschritten`; mit `$throwException` gibt es eine Exception. Eine Einstellung
„unbegrenzt“ gibt es für diese Wege bewusst nicht — wer länger braucht, gibt eine große Zahl an.
`Shell::getDefaultTimeout()` liefert den gerade geltenden Standard.

Wächst die Arbeit mit der Eingabe, liefert `Shell::scaledTimeout()` eine Frist: Grundwert plus Zuschlag je Einheit
(MB, Sekunde Medienlänge, Zeichen, Seite), höchstens `Shell::MAX_SCALED_TIMEOUT` (4 h) und nie unter der
Standard-Zeitgrenze — wer die prozessweit hochsetzt, hebt damit auch die skalierten Fristen an:

```php
$timeout = Shell::scaledTimeout($megabytes, perUnit: 10.0);               // 180 s + 10 s je MB
$timeout = Shell::scaledTimeout($pages, perUnit: 60.0, base: 300.0);      // 300 s + 60 s je Seite
```

Die Helper des Pakets nutzen das, wenn der Aufrufer keine Zeitgrenze angibt: `MediaHelper` nach der Medienlänge
(`MediaHelper::mediaTimeout()`, eine kurze Probe vorab; Whisper mit eigenem Faktor und 600 s Anlauf), die
Sprachsynthese nach der Textlänge, `OfficeHelper` nach der Dateigröße (`OfficeHelper::convertTimeout()`: 300 s +
60 s je MB), chardet nach der Dateigröße; FFmpeg-Proben (`getAudioInfo()`/`getVideoInfo()`) bekommen
`Shell::TIMEOUT_PROBE`.

Warnungen, Fehler und Exceptions nennen nur den Programmnamen, nie die Befehlszeile — sie trägt Pfade und Inhalte,
und Anwendungen reichen Exception-Meldungen oft bis zum Kunden durch. Bei unerwartetem Exit-Code lautet die
Warnung z. B. `pdftotext endete mit Exit-Code 1 (erwartet 0)`; die Exception (`$throwException`) hängt die letzten
fünf Ausgabezeilen an (je Zeile höchstens 300 Zeichen). Startet ein Programm nicht, nennt die Meldung nur den Grund.
Die ganze Befehlszeile steht im DEBUG-Log.

Programme, deren Exit-Code ein fachliches Urteil ist (Validatoren: 0 = angenommen, 1 = abgelehnt), ruft man mit
`expectedResultCode: null` auf: der Exit-Code wird dann nicht bewertet, es gibt weder Warnung noch Exception,
`$resultCode` trägt den Wert und `executeShell()` liefert die Ausgabe. Zeitüberschreitung und Startfehler bleiben
Fehler. `Java::execute()` und `Java::executeClass()` reichen den Parameter durch.

```php
$output = [];
$code = 0;
Shell::executeShellCommand($command, $output, $code, expectedResultCode: null); // true, $code = 1 bei Ablehnung
$report = Java::execute($validatorJar, $arguments, expectedResultCode: null);    // Ausgabe auch bei Exit 1
```

Für unabhängige Arbeit je Datei oder Seite (z. B. OCR je Seite) startet `executeParallel()` mehrere Befehle
nebenläufig, jeden mit eigener Zeitgrenze:

```php
$codes = Shell::executeParallel(['s1' => $cmd1, 's2' => $cmd2], maxParallel: 4, timeout: 120.0);
// ['s1' => 0, 's2' => Shell::EXIT_TIMEOUT]
```

Zurück kommt der Exit-Code je Schlüssel (`Shell::EXIT_TIMEOUT` bei Zeitüberschreitung, `-1`, wenn ein Befehl
nicht startet); die Ausgaben werden verworfen, Ergebnisse gehören in Dateien. Ohne `maxParallel` gilt die
Kernzahl, höchstens 16.

Unter Linux läuft der Befehl dazu unter GNU `timeout` (coreutils) in einer eigenen Prozessgruppe: Beim Abbruch
endet der **ganze Prozessbaum** (Pipelines wie `a | b`, Kinder von `sh -c`), nach einer Nachfrist von höchstens
5 s auch Prozesse, die SIGTERM ignorieren — und zwar auch dann, wenn der aufrufende PHP-Prozess vorher stirbt
(FPM-`request_terminate_timeout`, `kill -9`). Die Gruppe bleibt in der Sitzung des Aufrufers: Wer einen Job in
einer eigenen Sitzung startet (`setsid`), beendet mit `pkill -s <sid>` auch alle seine Werkzeuge. Ohne GNU
`timeout` (z. B. macOS, BusyBox) greift die Zeitgrenze weiterhin, beendet aber nur den direkten Kindprozess;
unter Windows beendet Symfony Process den Baum (`taskkill /T`).

`Shell::run()`/`Shell::execute()` (Argumentliste ohne Shell) behalten ihre Signatur (`run()` mit 60 s als Vorgabe,
`execute()` mit `null` = Standard-Zeitgrenze); auch dort endet beim Abbruch der ganze Baum. Die Umgebung des
PHP-Prozesses gilt auf allen Wegen samt `putenv()`-Änderungen, wie bei `exec()`.

### PHPStan-Regel: Prozesse nur über `Shell`

`exec()`, `shell_exec()`, `system()`, `passthru()`, `popen()`, `proc_open()`, `pcntl_exec()`, der
Backtick-Operator und Symfony Process außerhalb von `CommonToolkit\Helper\Shell` umgehen die Zeitgrenze. Die
mitgelieferte Regel meldet sie (Kennung `commonToolkit.processCall`):

```neon
includes:
    - vendor/dschuppelius/php-common-toolkit/phpstan-rules.neon

parameters:
    processCallAllowedClasses:      # optional: eigene Prozess-Wrapper zusätzlich erlauben
        - App\Helper\MeinWrapper
```

Dynamische Aufrufe (`$f = 'exec'; $f()`) erkennt die Regel nicht.

### Passwörter für qpdf (`PdfFile`)

`PdfFile::decrypt()`, `encrypt()` und `getMetaData($datei, $passwort)` geben Passwörter nie auf der Befehlszeile
weiter (dort stünden sie für jeden lokalen Nutzer lesbar in der Prozessliste), sondern über eine qpdf-Argumentdatei
(`@datei`, Rechte 0600, privater Temp-Ordner, sofort gelöscht; qpdf ≥ 7.0). pdfinfo kennt nur `-opw`/`-upw` —
geschützte Metadaten liest `getMetaData()` deshalb aus einer mit qpdf entschlüsselten Kopie im selben privaten
Ordner (`Encrypted` ist dann `yes`).

---

## Externe Programme und nicht vertrauenswürdige Dateien

`MediaHelper`, `OfficeHelper`, `ImageCropHelper` und `TifFile` reichen Dateien des Aufrufers an FFmpeg,
LibreOffice und ImageMagick weiter. Diese Programme folgen von sich aus Verweisen im Dateiinhalt. Stammt die
Datei von Dritten (Upload), kann sie den Rechner so eine eigene Datei lesen oder eine ausgehende Anfrage
stellen lassen. Die Helper begrenzen das wie folgt:

### FFmpeg (`MediaHelper`)

`convert()`, `getAudioInfo()` und `getVideoInfo()` starten FFmpeg mit `-protocol_whitelist file,pipe` vor `-i`.
Die Eingabe ist damit eine lokale Datei: eine präparierte Playlist oder ein Container mit Verweisen öffnet weder
Netz- noch Fremdprotokolle (http, tcp, concat, subfile, crypto, data …), und eine Netzadresse als `$input` wird
abgelehnt. Wer `ffmpeg-info` in einer eigenen Konfiguration überschreibt, bekommt die Grenze trotzdem (eine
eigene `-protocol_whitelist` bleibt stehen).

`transcribeWhisper()` reicht die Datei an Whisper weiter; Whisper startet sein eigenes FFmpeg ohne diese Grenze.
Fremde Dateien deshalb vorher mit `MediaHelper::convert()` in WAV wandeln und die WAV-Datei transkribieren.

### LibreOffice (`OfficeHelper`)

Jeder Aufruf startet mit einem eigenen Profil, das die Einträge aus `config/libreoffice/registrymodifications.xcu`
trägt, und mit `--norestore --nolockcheck --nodefault`:

- Verknüpfte Inhalte (Bilder, Abschnitte, Tabellen, OLE) aus dem Dokument werden nicht geladen, es gibt keine
  vertrauenswürdigen Orte, Verknüpfungen werden nie aktualisiert.
- Makros sind abgeschaltet, die Inhaltsanbieter für Netzadressen (http/https/WebDAV, CMIS, GIO) entfernt.

Folge für die Ausgabe: **verknüpfte** Inhalte fehlen, eingebettete bleiben. Ohne die mitgelieferte Datei startet
kein Aufruf (`convert()` liefert `false`). Den Netzwerk-Egress des Prozesses selbst kann ein Profil nicht
sperren — das gehört in den Betrieb (z. B. systemd `IPAddressDeny=`).

### ImageMagick (`ImageCropHelper`, `TifFile`)

ImageMagick wählt den Coder aus Inhalt und Endung der Datei. Eine Datei mit Bild-Endung kann so als Zeichen-,
Skript- oder Dokumentformat gelesen werden (SVG/MVG mit Verweisen auf andere Dateien, PostScript über
Ghostscript). Zwei Maßnahmen gehören zusammen:

**1. Coder festnageln.** Die `crop*`-Methoden und `getImageDimensions()` nehmen als letzten Parameter den Coder
der Eingabe (`"png"` oder `"png:"`); ImageMagick liest die Datei dann ausschließlich als dieses Format. Der
Coder kommt aus dem **geprüften Typ** der Datei, nicht aus ihrem Namen:

```php
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\FileTypes\ImageCropHelper;

$coder = match (File::mimeType($upload)) {
    'image/png' => 'png',
    'image/jpeg' => 'jpeg',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'image/tiff' => 'tiff',
    default => null,
};
if ($coder === null) {
    throw new RuntimeException('Kein unterstütztes Rasterbild');
}

ImageCropHelper::cropUpperHalf($upload, $target, $coder);
```

Ohne den Parameter bleibt das Verhalten wie bisher (ImageMagick wählt selbst) — das ist nur für Dateien aus
eigener Quelle gedacht. `TifFile::repair()` setzt den Coder selbst (`jpeg:` bzw. `tiff:`), weil die Methode den
MIME-Typ ohnehin prüft.

**2. Restriktive `policy.xml`.** Der Coder schützt nur die Eingabe selbst. Was ImageMagick darüber hinaus darf
(Fremdprogramme starten, Netzadressen abrufen, Dateien per `@datei` oder `text:` lesen), regelt die Policy.
Eine eigene Datei in einem eigenen Ordner ablegen und den Ordner vor dem Aufruf über `MAGICK_CONFIGURE_PATH`
bekannt machen — die Helper starten ImageMagick über die Shell, die Umgebung des PHP-Prozesses gilt also:

```xml
<policymap>
  <policy domain="path" rights="none" pattern="@*"/>
  <policy domain="delegate" rights="none" pattern="*"/>
  <policy domain="coder" rights="none" pattern="{URL,HTTP,HTTPS,FTP,FILE,EPHEMERAL,MSL,SCRIPT,TEXT,LABEL,CAPTION,PANGO}"/>
</policymap>
```

```php
putenv('MAGICK_CONFIGURE_PATH=/pfad/zum/ordner/mit/policy.xml');
ImageCropHelper::cropUpperHalf($upload, $target, $coder);
```

ImageMagick liest diese Datei zusätzlich zur `policy.xml` des Systems; gibt die System-Policy einen dieser Coder
ausdrücklich frei, gewinnt sie. Prüfen: `MAGICK_CONFIGURE_PATH=/pfad/zum/ordner convert -list policy`.

---

## License

This project is licensed under the **MIT License**.

**Daniel Joerg Schuppelius**
📧 <info@schuppelius.org>

## Versions

### 2.13.0 — IBAN mit Folgetext, sicherer DOM-Lader, Wörter und Zeilen

- `BankHelper::extractIBAN()`/`extractIBANs()` mit `$spaceTolerant`: Folgt einer gruppierten IBAN auf
  derselben Zeile weiterer Text („IBAN DE89 3704 0044 0532 0130 00 BIC COBADEFFXXX“), gilt der Teil bis zur
  Länderlänge als IBAN, wenn er an einer Gruppengrenze endet. Bis 2.12 lieferte das `null`, weil das Muster
  bis zu 30 Zeichen in den Folgetext griff. Endet die Länderlänge mitten in einer Gruppe, bleibt es keine IBAN.
- Neu: `XmlHelper::safeLoadDom()` lädt XML als `DOMDocument`, weist DOCTYPE und Entity-Deklarationen ab, hält
  libxml-Fehler intern und lädt ohne Netzwerk. Neu: `XmlHelper::escape()` maskiert für XML (fünf vordefinierte
  Entities, ungültiges UTF-8 ersetzt).
- Neu: `CryptoHelper::hmac()` liefert HMAC als Hex oder Rohbytes, erlaubt leere Daten und protokolliert nichts
  (`createHmac()` bleibt Base64). `secureRandomInt()` schreibt den erzeugten Wert nicht mehr ins Debug-Log.
- Neu: `StringHelper::words()` (Leerraum einschließlich Unicode-Leerzeichen, ohne leere Teile) und
  `StringHelper::lines()` (`\r\n`, `\r`, `\n` gleich, optional ohne Leerzeilen).

### 2.12.2 — Franken und US-Dollar als Schreibweise

- `CurrencyCode::getUniqueSymbols()` kennt `Fr.`/`SFr.` für den Schweizer Franken und `US$` für den
  US-Dollar. `fromSymbol('Fr.')` liefert CHF, `getSymbols()` nennt die Schreibweisen vor dem geteilten
  Zeichen, und der `AmountTokenizer` liest „Total Fr. 1'234.50“ als CHF-Betrag; „Fr.“ ohne Betrag daneben
  (Anrede) bleibt keine Währung.

### 2.10.1 — Whisper nur mit geprüfter WAV

- `MediaHelper::transcribeWhisper()` gibt Whisper nicht mehr die Rohdatei: Erst wandelt das eigene FFmpeg mit
  Protokollgrenze (`-protocol_whitelist file,pipe`) die Quelle in eine WAV (16 kHz mono), nur sie geht an Whisper.
  Whispers internes FFmpeg kennt diese Grenze nicht; eine präparierte Playlist oder ein Container mit Verweisen
  scheitert jetzt an der Wandlung, statt nach außen zu greifen. Die Zeitgrenze gilt für Wandlung und Whisper
  zusammen.

### 2.10 — Nichts läuft mehr unbegrenzt

- `Shell::run()` und `Shell::execute()`: `$timeout = null` heißt jetzt Standard-Zeitgrenze
  (`Shell::getDefaultTimeout()`, 180 s), nicht mehr „unbegrenzt“. `0`, negative Werte, `INF` und `NAN` werfen
  wie bei `executeShellCommand()` eine `InvalidArgumentException` (bis 2.9 hießen `0`/`INF` „ohne Grenze“).
  Startet ein Programm nicht, wirft `run()` eine `RuntimeException` ohne Argumente in der Meldung (statt der
  Symfony-Exception mit der ganzen Befehlszeile).
- Neu: `Shell::scaledTimeout()` und `Shell::MAX_SCALED_TIMEOUT` (4 h) für Fristen, die mit der Eingabe wachsen.
- `MediaHelper`, `OfficeHelper` und die chardet-Erkennung in `File` leiten ohne eigene Angabe eine skalierte
  Frist ab (Medienlänge, Textlänge, Dateigröße), FFmpeg-Proben nehmen `Shell::TIMEOUT_PROBE`. Neu dafür:
  `MediaHelper::mediaTimeout()` mit `TRANSCODE_SECONDS_PER_MEDIA_SECOND`, `TRANSCRIBE_SECONDS_PER_MEDIA_SECOND`,
  `TRANSCRIBE_TIMEOUT_BASE`, `SPEECH_SECONDS_PER_CHARACTER`; `OfficeHelper::convertTimeout()` mit `TIMEOUT_BASE`
  und `TIMEOUT_PER_MEGABYTE`.
- `executeShellCommand()`: Warnung und Exception nennen nur Programmname und Exit-Code, die Exception zusätzlich
  die letzten fünf Ausgabezeilen — nie mehr die Befehlszeile (vorher „Fehler bei der Ausführung des Kommandos:
  <Befehlszeile>“ und die ganze Ausgabe). Startfehler setzen `$resultCode` auf `-1` und folgen `$throwException`.
- Migration: Wer `null`, `0` oder `INF` an `run()`/`execute()` gab, um bewusst ohne Grenze zu laufen, gibt
  eine große, endliche Zahl an. Wer Meldungen nach der Befehlszeile durchsucht hat, liest den Programmnamen.
- Signaturen bleiben unverändert.

### 2.9 — Zeitgrenze für jeden Prozessaufruf

- `Shell::executeShellCommand()`/`executeShell()` laufen über Symfony Process statt `exec()` und haben eine
  Zeitgrenze: neuer letzter Parameter `?float $timeout` (Standard `Shell::DEFAULT_TIMEOUT` = 180 s, prozessweit
  über `Shell::setDefaultTimeout()`), `Shell::TIMEOUT_PROBE` (30 s) für Proben. Ausgabe, Exit-Code, `2>&1`,
  `$expectedResultCode`, `$throwException`, PowerShell und Logging bleiben wie bei `exec()`; neu ist nur der
  Abbruch (`Shell::EXIT_TIMEOUT`). Die Standardeingabe ist leer statt geerbt.
- Beim Abbruch endet der ganze Prozessbaum (GNU `timeout`, eigene Prozessgruppe) — auch bei `Shell::run()`
  mit Zeitgrenze und auch, wenn der PHP-Prozess vorher stirbt. Siehe „Zeitgrenze für externe Programme“.
- Neu: `Shell::executeParallel()` (mehrere Befehle nebenläufig, Zeitgrenze je Befehl).
- Neu: PHPStan-Regel `CommonToolkit\PHPStan\NoDirectProcessCallRule` mit `phpstan-rules.neon`.
- `PdfFile`: Passwörter über eine qpdf-Argumentdatei statt auf der Befehlszeile; ein leeres Passwort bei
  `encrypt()` ist jetzt wirklich leer.
- `ShellChardet`: jeder Aufruf startet chardet mit Zeitgrenze; `start()`/`stop()` sind ohne Wirkung und
  `@deprecated` (der dauerhafte Prozess beendete sich nach der ersten Antwort und konnte bei unklarem Text
  hängen bleiben). `ShellExecutable::execute()` startet ohne Shell mit Standard-Zeitgrenze.
- `TifFile::merge()` wirft bei Fehlschlag oder Abbruch und löscht die halbe Ausgabe, statt Erfolg zu melden.
- `Java::execute()`/`executeClass()`: optionaler letzter Parameter `$timeout`.
- Signaturen bleiben kompatibel (neue Parameter nur am Ende, mit Vorgabewert).

### 2.6 — Externe Programme gehärtet (FFmpeg, LibreOffice, ImageMagick)

- `MediaHelper`: FFmpeg öffnet für die Eingabe nur noch lokale Dateien (`-protocol_whitelist file,pipe`).
  Eine Netzadresse als Eingabe von `convert()` wird abgelehnt.
- `OfficeHelper`: jeder Aufruf läuft mit einem gehärteten Profil (`config/libreoffice/registrymodifications.xcu`)
  und `--norestore --nolockcheck --nodefault`. **Verknüpfte** Inhalte eines Dokuments (Bilder, Abschnitte,
  Tabellen) werden nicht mehr geladen, Makros nicht ausgeführt; eingebettete Inhalte bleiben. Die Profilvorlage
  liegt jetzt unter `<tmp>/commontoolkit-lo-template-<uid>-<fingerabdruck>` — das alte Verzeichnis ohne
  Fingerabdruck wird nicht mehr benutzt und kann gelöscht werden.
- `ImageCropHelper`: optionaler letzter Parameter `$coder` in den `crop*`-Methoden und `getImageDimensions()`;
  ohne ihn unverändert. `TifFile::repair()` liest die Eingabe mit dem Coder ihres geprüften MIME-Typs.
- Signaturen bleiben kompatibel. Einzelheiten und die empfohlene ImageMagick-Policy stehen unter
  „Externe Programme und nicht vertrauenswürdige Dateien“.

### 2.0 — `Money::of()` ist streng

- `Money::of()` wirft jetzt eine `InvalidArgumentException` bei Leerstring und
  nicht deutbarer Eingabe. Bis 1.x ergab das still `0.00` — ein falsch
  gelesenes Betragsfeld fiel so nie auf. Für „nicht angegeben“ gibt es
  `Money::ofNullable()` (liefert `null`, unverändert nachsichtig).
- Ein Währungscode in der Eingabe muss zur angeforderten Währung passen
  (`Money::of('12.34 USD', CurrencyCode::Euro)` wirft).
- Neu: `Money`, `Percentage` und `Quantity` lesen ihre eigene Textform
  (`"12.34 EUR"`, `"19.00 %"`, `"2.500 Stk"`) in `of()`/`ofNullable()`/`tryFrom()`.
  Eine fremde Einheit (`"2.5 kg"` für `Stk`) gilt als nicht deutbar.

Migration: Aufrufer, die leere oder fremde Strings an `Money::of()` geben,
auf `Money::ofNullable()` umstellen oder den Fall vorher behandeln.


Releases are tagged in Git; `git tag --sort=-v:refname` lists them (latest: v2.10.1). There is no separate changelog file — the tags and the commit history are the record.
