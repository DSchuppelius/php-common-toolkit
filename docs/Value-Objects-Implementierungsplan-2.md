# Implementierungsplan 2: Weitere Value Objects im PHP Common Toolkit

## Zweck dieses Dokuments

Dieses Dokument ist der verbindliche Arbeitsauftrag für die zweite Welle
wiederverwendbarer, immutabler Value Objects in
`dschuppelius/php-common-toolkit`. Es setzt den abgeschlossenen ersten Plan
([Value-Objects-Implementierungsplan.md](Value-Objects-Implementierungsplan.md))
fort.

**Alle verbindlichen technischen Regeln aus Plan 1 gelten unverändert**,
insbesondere:

- Abschnitt 1 (Kompatibilität `>=8.1 <8.6`, Ablage unter `src/ValueObjects/`,
  `final`, privater Konstruktor, benannte Factories, Immutabilität,
  Fehlerbehandlung, deutsche PHPDoc),
- Abschnitt 1.2 (strikte Factories werden niemals still zu 0/Leerstring;
  `of()` wirft, `tryFrom()` liefert `null`),
- Abschnitt 1.5 (sensible Werte ohne `Stringable`/`JsonSerializable`),
- Abschnitt 2 (Qualitäts-Gates nach jedem Objekt: `vendor/bin/pint`,
  `vendor/bin/phpstan analyse`, `composer test`; vor Abschluss `composer qa`).

Die Umsetzung erfolgt in dieser Reihenfolge, jedes Objekt vollständig
inklusive Tests, bevor das nächste begonnen wird:

1. `Gtin`
2. `Gln`
3. `Lei`
4. `HrNumber`
5. `IpAddress`
6. `ByteSize`
7. `Duration`

Änderungen in konsumierenden Projekten gehören ausdrücklich nicht zu diesem
Auftrag.

### Ergänzende Regeln dieser Welle

- **Öffentliche Identifikatoren** (`Gtin`, `Gln`, `Lei`, `HrNumber`)
  bezeichnen Artikel, Standorte bzw. Unternehmen — keine Personen oder
  Konten. Sie dürfen wie `Bic` `Stringable` und `JsonSerializable`
  implementieren.
- **`IpAddress` ist sensibel** (personenbezogen im Sinne der DSGVO) und folgt
  dem Muster von `Iban`: kein `__toString()`, kein `jsonSerialize()`,
  Klarwert nur über `getValue()`. Die Rolle von `masked()` übernimmt hier
  `anonymized()` (liefert eine echte, gültige IP mit genulltem Host-Teil).
- **`Duration` ist die einzige bewusste Neu-Logik** dieser Welle (kein
  bestehender Helper). Begründung: reine Ganzzahl-Sekundenarithmetik — ein
  vorgeschalteter Helper wäre künstlich. Alle anderen Objekte kapseln
  vorhandene Helper und duplizieren keine Validierungsalgorithmen.

---

## 1. `Gtin`

**Dateien**

- `src/ValueObjects/Gtin.php`
- `tests/ValueObjects/GtinTest.php`

**Zweck**

Global Trade Item Number (EAN/UPC) für Artikelidentifikation — Verwendung in
E-Rechnungs-Positionsdaten (z.B. XRechnung/ZUGFeRD BT-157), openTRANS und
Artikelstammdaten.

**Verwendeter Helper**

`CommonToolkit\Helper\Data\CompanyIdHelper` — der Helper unterstützt die
Längen **8, 12, 13 und 14** (`isEAN($value, $length)`,
`validateEAN($value)` inkl. Mod-10-Prüfziffer).

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;      // nur Ziffern, z.B. "4006381333931"
public function getLength(): int;        // 8, 12, 13 oder 14
public function getCheckDigit(): string; // letzte Ziffer
public function toGtin14(): self;        // mit führenden Nullen auf 14 Stellen
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): string;
```

**Invarianten**

- Normalisierung entfernt Trennzeichen/Whitespace; gespeichert wird die
  reine Ziffernfolge.
- Konstruktion verlangt `CompanyIdHelper::validateEAN()` (vollständige
  Prüfziffer); zulässige Längen sind 8, 12, 13, 14.
- `equals()` vergleicht die exakte normalisierte Ziffernfolge — eine GTIN-8
  ist NICHT gleich ihrer auf 14 Stellen gepaddeten Form. Wer
  GS1-übergreifend vergleichen will, vergleicht `toGtin14()`-Werte.
- `toGtin14()` paddet links mit Nullen; die Prüfziffer bleibt dabei
  mathematisch gültig (Mod-10 ist rechtsbündig positioniert).

**Pflichttests**

- gültige GTIN-8, GTIN-12 (UPC), GTIN-13 (EAN) und GTIN-14,
- ungültige Prüfziffer, unzulässige Länge (z.B. 10 Ziffern),
- Normalisierung von Leerzeichen/Bindestrichen,
- `tryFrom()` mit null/leer/ungültig,
- `toGtin14()` liefert eine gültige Instanz und ist idempotent,
- exakte Gleichheit (GTIN-8 ≠ gepaddete GTIN-14),
- String-/JSON-Ausgabe.

---

## 2. `Gln`

**Dateien**

- `src/ValueObjects/Gln.php`
- `tests/ValueObjects/GlnTest.php`

**Zweck**

Global Location Number (13-stellig) zur Identifikation von Unternehmen und
Standorten — Peppol-/XRechnung-Adressierung (EAS 0088), openTRANS-Partner.

**Verwendeter Helper**

`CommonToolkit\Helper\Data\CompanyIdHelper` (`validateGLN`, `normalizeGLN`,
`formatGLN`).

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;      // 13 Ziffern
public function getCheckDigit(): string;
public function formatted(): string;     // delegiert an CompanyIdHelper::formatGLN()
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): string;
```

**Invarianten**

- Konstruktion verlangt `validateGLN()` (13 Stellen, Mod-10-Prüfziffer).
- Gespeichert wird die normalisierte Ziffernfolge.

**Pflichttests**

- gültige GLN, ungültige Prüfziffer, falsche Länge,
- Normalisierung, `tryFrom()`, Formatierung, Gleichheit, String/JSON.

---

## 3. `Lei`

**Dateien**

- `src/ValueObjects/Lei.php`
- `tests/ValueObjects/LeiTest.php`

**Zweck**

Legal Entity Identifier (ISO 17442, 20 Zeichen alphanumerisch) —
Finanzkontext (ISO-20022-/CAMT-Felder, Geschäftspartner-Stammdaten).

**Verwendeter Helper**

`CommonToolkit\Helper\Data\CompanyIdHelper` (`validateLEI` mit
Mod-97-10-Prüfsumme, `normalizeLEI`, `formatLEI`).

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;       // 20 Zeichen, Großschreibung
public function getLouCode(): string;     // Stellen 1-4 (vergebende Stelle)
public function getEntityPart(): string;  // Stellen 5-18
public function getCheckDigits(): string; // Stellen 19-20
public function formatted(): string;      // delegiert an CompanyIdHelper::formatLEI()
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): string;
```

**Invarianten**

- Konstruktion verlangt `validateLEI()` (vollständige Mod-97-10-Prüfung).
- Normalisierung: Großschreibung, ohne Trennzeichen.

**Pflichttests**

- gültiger LEI, ungültige Prüfsumme, falsche Länge, Kleinschreibung wird
  normalisiert, Strukturzugriffe, `tryFrom()`, Gleichheit, String/JSON.

---

## 4. `HrNumber`

**Dateien**

- `src/ValueObjects/HrNumber.php`
- `tests/ValueObjects/HrNumberTest.php`

**Zweck**

Deutsche Registernummer (Handels-, Genossenschafts-, Partnerschafts-,
Vereinsregister) für Belegpflichtangaben und Stammdaten. Der Helper
unterstützt die Präfixe **HRA, HRB, GNR, PR, VR** mit 1-6 Ziffern und
optionalem Buchstaben-Suffix.

**Verwendeter Helper**

`CommonToolkit\Helper\Data\CompanyIdHelper` (`isHRNumber`, `parseHRNumber`
→ `{prefix, number, suffix}`, `normalizeHRNumber`, `formatHRNumber`).

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;        // kanonisch, z.B. "HRB 12345 B"
public function getRegisterType(): string; // "HRA" | "HRB" | "GNR" | "PR" | "VR"
public function getNumber(): string;       // z.B. "12345"
public function getSuffix(): ?string;      // z.B. "B" oder null
public function formatted(): string;
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): string;
```

**Invarianten**

- Gespeichert wird die kanonische Form aus `formatHRNumber()`
  ("PRÄFIX NUMMER[ SUFFIX]"); `equals()` vergleicht diese Form.
- Das **Registergericht ist bewusst nicht Teil** dieses Value Objects — der
  Helper kennt keins; das Gericht ist ein Kontextdatum der Anwendung.

**Pflichttests**

- alle fünf Registertypen, mit und ohne Suffix,
- Normalisierung ("hrb12345", "HRB 12.345"),
- ungültige Präfixe/Längen, `tryFrom()`,
- Strukturzugriffe, Gleichheit unterschiedlich formatierter Eingaben,
- String/JSON.

---

## 5. `IpAddress` (sensibel)

**Dateien**

- `src/ValueObjects/IpAddress.php`
- `tests/ValueObjects/IpAddressTest.php`

**Zweck**

Validierte IPv4-/IPv6-Adresse für Audit-Logs, Sicherheits- und
Zugriffskontexte. IP-Adressen sind personenbezogene Daten — das Objekt
erzwingt bewussten Umgang und bietet DSGVO-taugliche Anonymisierung.

**Verwendeter Helper**

`CommonToolkit\Helper\Data\IPHelper` (`isValidIP`, `normalize`,
`getIPVersion`, `isPrivateIP`/`isPublicIP`/`isLoopback`/`isLinkLocal`/
`isMulticast`/`isReservedIP`, `isInRange`, `getNetworkAddress`, `compare`).

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;     // normalisiert (IPv6 komprimiert)
public function getVersion(): int;      // 4 oder 6
public function isIpv4(): bool;
public function isIpv6(): bool;
public function isPrivate(): bool;
public function isPublic(): bool;
public function isLoopback(): bool;
public function isLinkLocal(): bool;
public function isMulticast(): bool;
public function isReserved(): bool;
public function isInRange(string $cidr): bool;
public function anonymized(?int $prefix = null): self;
public function equals(self $other): bool;
```

**Invarianten**

- Konstruktion verlangt `IPHelper::isValidIP()`; gespeichert wird die über
  `IPHelper::normalize()` kanonisierte Form (IPv6 komprimiert).
- `anonymized()` delegiert an `IPHelper::getNetworkAddress()` und nullt den
  Host-Teil: Standard-Präfix **/24 für IPv4** und **/48 für IPv6** (übliche
  Analytics-/DSGVO-Konvention). Das Ergebnis ist eine gültige `IpAddress`.
- Ein expliziter `$prefix` außerhalb von 0..32 (v4) bzw. 0..128 (v6) wird
  abgelehnt.
- `equals()` vergleicht die normalisierte Darstellung — "::1" und
  "0:0:0:0:0:0:0:1" sind gleich.
- Kein `__toString()`, kein `jsonSerialize()` (sensibler Wert; die Rolle von
  `masked()` übernimmt `anonymized()`).

**Pflichttests**

- gültige IPv4 und IPv6, Normalisierung (Groß-/Kleinschreibung, expandierte
  IPv6-Form → komprimiert),
- ungültige Eingaben und `tryFrom()`,
- Klassifizierung (privat/öffentlich/Loopback/Link-Local/Multicast/reserviert),
- CIDR-Range-Prüfung,
- Anonymisierung v4 (/24) und v6 (/48), expliziter Präfix, ungültiger Präfix,
- Gleichheit expandierter/komprimierter IPv6-Formen,
- sicherstellen, dass kein String-/JSON-Interface implementiert wird.

---

## 6. `ByteSize`

**Dateien**

- `src/ValueObjects/ByteSize.php`
- `tests/ValueObjects/ByteSizeTest.php`

**Zweck**

Exakte Datenmenge in Bytes (Uploads, Quoten, Dateigrößen) — statt roher
Integers mit unklaren Einheiten und float-basierter Umrechnung.

**Verwendeter Helper**

`CommonToolkit\Helper\Data\NumberHelper` (`formatBytes` — 1024er-Basis,
`parseByteString`). Achtung Helper-Vertrag: `parseByteString()` **verlangt
eine Einheit** ("1024" ohne Einheit wirft `RuntimeException`) und versteht
Punkt- wie Komma-Dezimaltrenner ("1.5 MB", "1,5 GB").

**Vorgesehene API**

```php
public static function ofBytes(int $bytes): self;         // >= 0
public static function parse(string $input): self;        // "1.5 MB", "1,5 GB"
public static function tryParse(?string $input): ?self;
public static function zero(): self;
public static function sum(iterable $sizes): self;

public function plus(self $other): self;
public function minus(self $other): self;                 // Ergebnis < 0 → Exception
public function times(int $factor): self;                 // factor >= 0

public function compareTo(self $other): int;
public function equals(self $other): bool;
public function isZero(): bool;

public function getBytes(): int;
public function format(int $precision = 2): string;       // "1.5 MB" (1024er-Basis)
public function __toString(): string;                     // "<bytes> B"
public function jsonSerialize(): int;                     // Bytes, verlustfrei
```

**Invarianten**

- Datenmengen sind nie negativ: `ofBytes()` lehnt negative Werte ab,
  `minus()` wirft bei negativem Ergebnis (`InvalidArgumentException`).
- `parse()` delegiert an `parseByteString()`; dessen Exception-Verhalten
  bleibt sichtbar. `tryParse()` fängt sie und liefert `null`.
- Keine float-Zwischenschritte beim Rechnen — der Zustand ist `int` Bytes.

**Pflichttests**

- `ofBytes()` inkl. Ablehnung negativer Werte,
- `parse()` mit Punkt- und Komma-Dezimaltrenner, `parse('1024')` (ohne
  Einheit) wirft, `tryParse()` liefert dafür null,
- Arithmetik inkl. `minus()`-Unterlauf und `times()`,
- `sum()`, Vergleich, Gleichheit,
- `format()`-Roundtrip (`parse(format(x))` innerhalb der Präzisionsgrenzen),
- JSON-/String-Ausgabe, Immutabilität.

---

## 7. `Duration`

**Dateien**

- `src/ValueObjects/Duration.php`
- `tests/ValueObjects/DurationTest.php`

**Zweck**

Exakte Zeitdauer in ganzen Sekunden — Arbeits-/Projektzeiten, Abwesenheiten,
Zeitsalden. Bewusste Neu-Logik ohne Helper (siehe ergänzende Regeln): reine
Ganzzahlarithmetik.

**Interner Zustand**

```php
private readonly int $seconds;   // kann negativ sein (Saldo-Korrekturen)
```

**Vorgesehene API**

```php
public static function ofSeconds(int $seconds): self;
public static function ofMinutes(int $minutes): self;
public static function ofHours(int $hours): self;
public static function of(int $hours, int $minutes = 0, int $seconds = 0): self; // alle >= 0
public static function fromIso8601(string $duration): self;   // "PT8H30M"
public static function between(DateTimeInterface $start, DateTimeInterface $end): self;
public static function sum(iterable $durations): self;
public static function zero(): self;

public function plus(self $other): self;
public function minus(self $other): self;
public function times(int $factor): self;
public function negated(): self;
public function abs(): self;

public function compareTo(self $other): int;
public function equals(self $other): bool;
public function isZero(): bool;
public function isPositive(): bool;
public function isNegative(): bool;

public function getTotalSeconds(): int;
public function getTotalMinutes(): int;                   // Richtung Null abgeschnitten
/** @return array{hours: int, minutes: int, seconds: int} */
public function toParts(): array;                          // Vorzeichen auf allen Teilen einheitlich
public function toClock(bool $withSeconds = false): string; // "8:30", "-0:15", "129:05"
public function toIso8601(): string;                       // "PT8H30M", "-PT15M"
public function __toString(): string;                      // ISO-8601
public function jsonSerialize(): array;                    // {"seconds": 30600}
```

**Invarianten**

- Ganze Sekunden; keine Mikrosekunden in dieser Ausbaustufe.
- Negative Dauern sind zulässig (Saldo/Korrektur); `of(h, m, s)` verlangt
  nicht-negative Komponenten — negative Dauern entstehen über
  `ofSeconds()`, `negated()` oder `between()`.
- `between()` rechnet instant-basiert (`getTimestamp()`-Differenz) und darf
  negativ sein; sie normalisiert NICHT auf Kalendertage (DST-sicher).
- `fromIso8601()` akzeptiert `PT…`-Anteile sowie Tage (`P1D` = fest
  86400 s). **Jahre und Monate werden abgelehnt** (keine feste
  Sekundenlänge) — `InvalidArgumentException`.
- `toClock()` läuft über 24 Stunden hinaus ("129:05"), kein Tagesumbruch.

**Integration (additiv, im selben Auftrag)**

`DateTimeRange` erhält eine Methode `duration(): Duration` (delegiert an
`durationInSeconds()`); bestehende API bleibt unverändert.

**Pflichttests**

- Konstruktion über alle Factories inkl. Grenzfälle (0, negative Sekunden),
- `of()` lehnt negative Komponenten ab,
- ISO-8601-Roundtrip (`fromIso8601(toIso8601(x))`), Ablehnung von
  `P1Y`/`P1M`, Akzeptanz von `P1DT2H`,
- `between()` über Tages- und DST-Wechsel (23-Stunden-Tag), negative Richtung,
- Arithmetik, `sum()` und Vergleiche,
- `toClock()` mit/ohne Sekunden, negativ, über 24 h,
- `toParts()` mit einheitlichem Vorzeichen,
- `DateTimeRange::duration()`,
- JSON-Ausgabe, Immutabilität.

---

## Bewusst zurückgestellt (nicht Teil dieses Auftrags)

- **`Duns`, `WIdNr`** — Helper vorhanden (`isDUNS`, `isWIdNr`); nachziehen,
  sobald ein Abnehmer sie braucht. W-IdNr ist derzeit nur Formatprüfung.
- **`Url`** — Design offen: URLs tragen oft Tokens im Query;
  `Stringable`-Frage und `withoutQuery()`/Maskierung zuerst klären.
- **`PostalCode`** — klein und helper-gestützt, aber sinnvoll erst zusammen
  mit einem konkreten Adress-Thema der Anwendung.
- **`Pan` (Kreditkartennummer)** — Muster würde passen (sensibel + Luhn),
  aber PCI-seitig sollen PANs gar nicht erst persistiert werden; nur bei
  echtem Flow bauen.
- **`Blz`** — Legacy-Relevanz (MT940/DTA); bei Bedarf aus financial-formats
  heraus beauftragen.
- **`Measurement`** — braucht zuerst einen Vertrag auf den Einheiten-Enums
  (Dimension + exakter Faktor als Decimal; `UnitConversionHelper` ist
  float-basiert). Eigener Plan.
- **`LeitwegId`** — fachlich sinnvoll, gehört aber ins erechnung-toolkit.
- `Address`, `CustomerNumber`, `InvoiceNumber`, `TaxedMoney` u.ä. — bleiben
  gemäß Plan 1 §6 ausgeschlossen (anwendungs-/domänenspezifisch).

---

## Vorgehensweise für Claude

Wie Plan 1 §7, unverändert:

1. Vorhandene Helper und zugehörige Tests lesen.
2. API-Umsetzbarkeit mit PHP 8.1 und den realen Helper-Signaturen prüfen.
3. Widersprüche benennen statt still umzudeuten.
4. Zuerst Tests, dann Implementierung.
5. Pint, PHPStan und die vollständige Testsuite nach jedem Objekt.
6. Erst bei grünen Gates zum nächsten Objekt.

## Abschlusskriterien

Der Auftrag gilt als erfüllt, wenn:

- alle sieben aufgeführten Value Objects vorhanden und getestet sind,
- `IpAddress` keine impliziten String-/JSON-Pfade besitzt,
- `DateTimeRange::duration()` ergänzt ist, ohne bestehende API zu ändern,
- keine vorhandene öffentliche API gebrochen wurde,
- `composer qa` erfolgreich läuft,
- die `README.md` um kurze Beispiele zu mindestens `Gtin`, `IpAddress`,
  `ByteSize` und `Duration` ergänzt wurde.
