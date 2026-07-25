# Implementierungsplan: Value Objects im PHP Common Toolkit

## Zweck dieses Dokuments

Dieses Dokument ist der verbindliche Arbeitsauftrag für die schrittweise
Erweiterung von `dschuppelius/php-common-toolkit` um wiederverwendbare,
immutable Value Objects.

Die Implementierung soll die bereits vorhandenen Helper und Enums kapseln und
nicht duplizieren. Ziel sind typsichere Werte an Anwendungsgrenzen sowie exakte
Arithmetik ohne unbeabsichtigte `float`-Konvertierungen.

Die Umsetzung erfolgt in dieser Reihenfolge:

1. `Decimal`
2. `Percentage`
3. `Quantity`
4. `ExchangeRate`
5. `DateRange`
6. `DateTimeRange`
7. `Iban`
8. `Bic`
9. `EmailAddress`
10. `PhoneNumber`
11. `VatNumber`
12. `CreditorIdentifier`
13. `GermanTaxId`
14. `GermanTaxNumber`

Jedes Value Object wird vollständig implementiert und geprüft, bevor das
nächste begonnen wird. Änderungen in konsumierenden Projekten wie workDiary
gehören ausdrücklich nicht zu diesem Auftrag.

---

## 1. Verbindliche technische Regeln

### 1.1 Kompatibilität und Ablage

- Unterstützte PHP-Versionen: `>=8.1 <8.6`.
- Keine Sprachfeatures verwenden, die erst ab PHP 8.2 verfügbar sind, etwa
  `readonly class`.
- Neue Klassen liegen unter `src/ValueObjects/`.
- Tests spiegeln die Struktur unter `tests/ValueObjects/`.
- Jede PHP-Datei verwendet `declare(strict_types=1);`.
- Klassen sind `final`.
- Der Konstruktor ist `private`; Instanzen entstehen über benannte Factories.
- Eigenschaften sind, soweit unter PHP 8.1 möglich, einzeln `readonly`.
- Öffentliche APIs besitzen vollständige Typangaben und PHPStan-taugliche
  PHPDoc-Typen, insbesondere `numeric-string` und Array-Shapes.
- Kommentare, Exceptions und PHPDoc werden entsprechend der bestehenden
  Codebasis auf Deutsch formuliert.

### 1.2 Allgemeines Factory-Verhalten

Soweit für das jeweilige Objekt sinnvoll:

- `of(...)` normalisiert, validiert und wirft bei ungültigen Werten eine
  `InvalidArgumentException`.
- `tryFrom(...)` nimmt auch `null` entgegen und liefert bei leerer oder
  ungültiger Eingabe `null`.
- Numerische Objekte können ergänzend `ofNullable(...)` nach dem bestehenden
  `Money`-Muster anbieten.
- Ungültige Eingaben dürfen in einer strikten Factory niemals stillschweigend
  zu `0`, Leerstring oder einem anderen Fallback werden.
- Wenn `NumberHelper` verwendet wird, muss bei strikten Factories zunächst
  `normalizeDecimalStringOrNull()` benutzt werden. Das Verhalten von
  `normalizeDecimalString()`, nicht interpretierbare Eingaben zu `"0"` zu
  machen, ist an dieser Grenze nicht zulässig.
- Ein expliziter Float-Einstieg heißt `ofFloat(...)` und dokumentiert den
  bereits vor dem Aufruf möglichen Präzisionsverlust.

### 1.3 Immutabilität und Gleichheit

- Keine Methode verändert eine vorhandene Instanz.
- Rechen-, Skalierungs- und Konvertierungsmethoden liefern neue Instanzen.
- Bei einer Operation ohne effektive Änderung darf dieselbe Instanz
  zurückgegeben werden.
- `equals()` vergleicht die fachliche Bedeutung, nicht die
  Objektreferenz.
- Bei Dezimalwerten sind zum Beispiel `1.0` und `1.00` fachlich gleich.
- Für Vergleiche darf keine `float`-Konvertierung stattfinden.

### 1.4 Fehlerbehandlung

- Es gelten die vorhandenen Muster aus `Money`.
- Fachlich ungültige Konstruktion oder inkompatible Operationen werfen
  `InvalidArgumentException`.
- Division durch null verwendet das bereits von `NumberHelper` etablierte
  Exception-Verhalten.
- Vorhandene Helper bleiben Single Source of Truth für Normalisierung,
  Formatprüfung und Prüfsummen.
- Validierungsalgorithmen aus Helpern werden nicht in Value Objects kopiert.

### 1.5 Serialisierung und sensible Daten

Nicht sensible numerische und zeitliche Value Objects implementieren
`JsonSerializable` und, sofern eine eindeutige maschinenlesbare Darstellung
existiert, `Stringable`.

Folgende Objekte enthalten personenbezogene oder finanzielle Identifikatoren
und dürfen standardmäßig weder `JsonSerializable` noch `Stringable`
implementieren:

- `Iban`
- `EmailAddress`
- `PhoneNumber`
- `VatNumber`
- `CreditorIdentifier`
- `GermanTaxId`
- `GermanTaxNumber`

Der Klarwert ist dort ausschließlich über einen bewusst aufgerufenen
`getValue()`-Getter verfügbar. Zusätzlich ist, soweit sinnvoll, `masked()`
anzubieten. `Bic` bezeichnet ein Institut statt ein Konto und darf
`Stringable` sowie `JsonSerializable` implementieren.

### 1.6 Abwärtskompatibilität

- Bestehende öffentliche APIs dürfen nicht entfernt, umbenannt oder in ihrem
  Verhalten verändert werden.
- Insbesondere wird `Money` in der ersten Ausbaustufe nicht intern auf
  `Decimal` umgestellt.
- `Money::convertTo()` bleibt unverändert.
- Neue Integration mit `Money` erfolgt zunächst von außen, beispielsweise über
  `ExchangeRate::convert(Money $money)`.
- Helper bleiben als eigenständig nutzbare APIs erhalten.

---

## 2. Qualitäts-Gates

Nach jedem vollständig implementierten Value Object müssen mindestens diese
Befehle erfolgreich laufen:

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
composer test
```

Vor Abschluss des Gesamtauftrags ist zusätzlich `composer qa` auszuführen.

Die Implementierung ist erst abgeschlossen, wenn:

- alle neuen öffentlichen Pfade durch Tests abgedeckt sind,
- Fehlerfälle getestet sind,
- PHPStan auf Level 8 ohne neue Ignorierungen grün ist,
- Pint keine offenen Formatänderungen meldet,
- alle bestehenden Tests weiterhin grün sind,
- die Beispiele in `README.md` um einen kompakten Abschnitt zu den neuen Value
  Objects ergänzt wurden.

Keine Qualitäts-Gates, Tests oder bestehenden Validierungen entfernen oder
abschwächen, um einen Lauf grün zu bekommen.

---

## 3. Phase A: Exakte numerische Value Objects

### 3.1 `Decimal`

**Dateien**

- `src/ValueObjects/Decimal.php`
- `tests/ValueObjects/DecimalTest.php`

**Zweck**

Allgemeiner, immutable Dezimalwert mit exakter BCMath-Arithmetik. `Decimal`
ist die technische Grundlage für `Percentage`, `Quantity` und
`ExchangeRate`, aber keine Oberklasse. Die anderen Objekte komponieren ein
`Decimal`.

**Interner Zustand**

```php
/** @var numeric-string */
private readonly string $value;

private readonly int $scale;
```

Der Wert ist kanonisch mit Punkt als Dezimaltrenner und besitzt exakt
`$scale` Nachkommastellen.

**Vorgesehene API**

```php
public static function of(
    string|int $value,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp,
    ?CountryCode $country = null
): self;

public static function ofNullable(
    string|int|null $value,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp,
    ?CountryCode $country = null
): ?self;

public static function ofFloat(
    float $value,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp
): self;

public static function zero(int $scale = 0): self;
public static function one(int $scale = 0): self;
public static function fromArray(array $data): self;
public static function sum(iterable $values, ?int $scale = null): self;
public static function min(self $first, self ...$rest): self;
public static function max(self $first, self ...$rest): self;

public function plus(self $other): self;
public function minus(self $other): self;
public function times(
    self $other,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp
): self;
public function dividedBy(
    self $other,
    int $scale,
    RoundingMode $mode = RoundingMode::HalfUp
): self;
public function negated(): self;
public function abs(): self;
public function withScale(
    int $scale,
    RoundingMode $mode = RoundingMode::HalfUp
): self;

public function compareTo(self $other): int;
public function equals(self $other): bool;
public function greaterThan(self $other): bool;
public function greaterThanOrEqual(self $other): bool;
public function lessThan(self $other): bool;
public function lessThanOrEqual(self $other): bool;
public function isZero(): bool;
public function isPositive(): bool;
public function isNegative(): bool;

/** @return numeric-string */
public function getValue(): string;
public function getScale(): int;
public function toFloat(): float;
public function format(
    string $decimalSeparator = ',',
    string $thousandsSeparator = '.'
): string;
public function __toString(): string;
public function jsonSerialize(): array;
```

**Skalenregeln**

- Bei `scale: null` wird die Zahl der Nachkommastellen aus dem normalisierten
  Eingabewert übernommen.
- Ganze Zahlen erhalten Skala `0`.
- Explizite Skalen kleiner als `0` werden abgelehnt.
- `plus()` und `minus()` verwenden die größere Skala beider Operanden.
- `times()` verwendet ohne explizite Zielskala die Summe der Operanden-Skalen.
- `dividedBy()` verlangt bewusst eine Zielskala, weil periodische Ergebnisse
  keine natürliche endliche Skala besitzen.
- `sum()` akkumuliert ohne Zwischenrundungen und verwendet standardmäßig die
  größte vorkommende Skala.
- Negative Null wird kanonisch als positive Null dargestellt.

**Serialisierung**

```json
{"value":"12.3400","scale":4}
```

`fromArray()` muss die eigene JSON-Darstellung verlustfrei rekonstruieren.

**Pflichttests**

- deutsche und US-amerikanische Dezimalformate,
- länderspezifisch eindeutige Tausendertrenner,
- ungültige, leere und nullable Eingaben,
- expliziter Float-Einstieg,
- Skaleninferenz und explizite Rundung,
- exakte Rechnung `0.1 + 0.2 = 0.3`,
- Multiplikation und Division mit allen relevanten Rundungsmodi,
- Division durch null,
- Vorzeichenfunktionen,
- fachliche Gleichheit bei abweichender Skala,
- unveränderte Ursprungsinstanz nach jeder Operation,
- `sum()`, `min()` und `max()`,
- Formatierung ohne Float-Zwischenschritt,
- JSON-/Array-Roundtrip,
- sehr große Werte jenseits des Integer- und Float-Bereichs.

### 3.2 `Percentage`

**Dateien**

- `src/ValueObjects/Percentage.php`
- `tests/ValueObjects/PercentageTest.php`

**Zweck**

Exakter Prozentwert für Steuern, Rabatte, Skonto, Zuschläge und statistische
Raten. Das Objekt ist grundsätzlich nicht auf `0..100` begrenzt, weil negative
Raten und Werte über 100 fachlich möglich sind.

**Interner Zustand**

```php
private readonly Decimal $value;
```

**Vorgesehene API**

```php
public static function of(
    string|int $value,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp,
    ?CountryCode $country = null
): self;

public static function tryFrom(
    string|int|null $value,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp,
    ?CountryCode $country = null
): ?self;

public static function betweenZeroAndHundred(
    string|int $value,
    ?int $scale = null
): self;

public static function fromRatio(
    Decimal $part,
    Decimal $whole,
    int $scale = 4,
    RoundingMode $mode = RoundingMode::HalfUp
): self;

public function amountOf(
    Money $money,
    RoundingMode $mode = RoundingMode::HalfUp
): Money;
public function addTo(
    Money $money,
    RoundingMode $mode = RoundingMode::HalfUp
): Money;
public function subtractFrom(
    Money $money,
    RoundingMode $mode = RoundingMode::HalfUp
): Money;
public function asFactor(
    int $scale = 8,
    RoundingMode $mode = RoundingMode::HalfUp
): Decimal;

public function plus(self $other): self;
public function minus(self $other): self;
public function compareTo(self $other): int;
public function equals(self $other): bool;
public function isZero(): bool;
public function isPositive(): bool;
public function isNegative(): bool;
public function isWithinZeroAndHundred(): bool;

public function getValue(): Decimal;
/** @return numeric-string */
public function getNumericValue(): string;
public function format(string $decimalSeparator = ','): string;
public function __toString(): string;
public function jsonSerialize(): array;
```

`amountOf()`, `addTo()` und `subtractFrom()` delegieren an die präzisen
Methoden von `Money`. Es wird keine eigene Geldarithmetik dupliziert.

**Pflichttests**

- `19 %` von `8,15 EUR` ergibt bei kaufmännischer Rundung `1,55 EUR`,
- negative Prozente und Werte über 100 sind über `of()` erlaubt,
- `betweenZeroAndHundred()` lehnt Werte außerhalb des Bereichs ab,
- `fromRatio(1, 3)` rundet nur auf die angegebene Zielskala,
- Division durch null bei `fromRatio()` wird abgelehnt,
- `addTo()` und `subtractFrom()` bewahren die Währung,
- fachliche Gleichheit bei unterschiedlicher Skala,
- JSON-Roundtrip.

### 3.3 `Quantity`

**Dateien**

- `src/ValueObjects/Quantity.php`
- `tests/ValueObjects/QuantityTest.php`

**Zweck**

Exakte Menge zusammen mit einer Einheit. Das Objekt unterstützt sowohl
physikalische Einheiten als auch betriebliche Einheiten wie `Stk`,
`Pauschale`, `Karton` oder `h`.

In dieser Ausbaustufe findet keine automatische Einheitenumrechnung statt.
Arithmetik ist nur zwischen identischen, case-sensitiven Einheitencodes
zulässig.

**Interner Zustand**

```php
private readonly Decimal $value;
private readonly string $unit;
```

**Vorgesehene API**

```php
public static function of(
    string|int|Decimal $value,
    string|BackedEnum $unit,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp,
    ?CountryCode $country = null
): self;

public static function tryFrom(
    string|int|Decimal|null $value,
    string|BackedEnum $unit,
    ?int $scale = null
): ?self;

public static function zero(string|BackedEnum $unit, int $scale = 0): self;
public static function positive(
    string|int|Decimal $value,
    string|BackedEnum $unit,
    ?int $scale = null
): self;
public static function zeroOrPositive(
    string|int|Decimal $value,
    string|BackedEnum $unit,
    ?int $scale = null
): self;
public static function sum(
    iterable $quantities,
    string|BackedEnum|null $unit = null,
    ?int $scale = null
): self;

public function plus(self $other): self;
public function minus(self $other): self;
public function times(
    Decimal $factor,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp
): self;
public function dividedBy(
    Decimal $divisor,
    int $scale,
    RoundingMode $mode = RoundingMode::HalfUp
): self;
public function abs(): self;
public function negated(): self;
public function withScale(
    int $scale,
    RoundingMode $mode = RoundingMode::HalfUp
): self;

public function compareTo(self $other): int;
public function equals(self $other): bool;
public function isSameUnit(self $other): bool;
public function isZero(): bool;
public function isPositive(): bool;
public function isNegative(): bool;

public function getValue(): Decimal;
/** @return numeric-string */
public function getNumericValue(): string;
public function getUnit(): string;
public function format(
    string $decimalSeparator = ',',
    string $thousandsSeparator = '.'
): string;
public function __toString(): string;
public function jsonSerialize(): array;
```

**Einheitenregeln**

- Einheit wird getrimmt.
- Leere Einheiten und Steuerzeichen werden abgelehnt.
- Groß-/Kleinschreibung wird nicht verändert.
- `BackedEnum` ist nur zulässig, wenn der Backing-Wert ein String ist.
- Addition, Subtraktion und Vergleich unterschiedlicher Einheiten werfen
  `InvalidArgumentException`.
- Negative Mengen sind über `of()` zulässig, etwa für Lagerbewegungen.
- `positive()` verlangt einen Wert echt größer null.
- `zeroOrPositive()` erlaubt null, aber keine negativen Werte.

**Pflichttests**

- String- und String-Backed-Enum-Einheiten,
- Ablehnung leerer oder ungültiger Einheiten,
- exakte Mengenarithmetik mit vier und mehr Nachkommastellen,
- negative Lagerbewegungen,
- Constraints der positiven Factories,
- Ablehnung inkompatibler Einheiten,
- Summen-Invariante,
- Formatierung und JSON-Roundtrip.

### 3.4 `ExchangeRate`

**Dateien**

- `src/ValueObjects/ExchangeRate.php`
- `tests/ValueObjects/ExchangeRateTest.php`

**Zweck**

Ein Wechselkurs mit expliziter Ausgangs- und Zielwährung. Dadurch ist die
Richtung des Faktors eindeutig und ein versehentlich invertierter Kurs wird
vermieden.

**Interner Zustand**

```php
private readonly CurrencyCode $sourceCurrency;
private readonly CurrencyCode $targetCurrency;
private readonly Decimal $rate;
```

Die Bedeutung lautet stets:

```text
1 Einheit sourceCurrency = rate Einheiten targetCurrency
```

**Vorgesehene API**

```php
public static function of(
    CurrencyCode $sourceCurrency,
    CurrencyCode $targetCurrency,
    string|int|Decimal $rate,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp
): self;

public static function fromArray(array $data): self;

public function convert(
    Money $money,
    ?int $scale = null,
    RoundingMode $mode = RoundingMode::HalfUp
): Money;
public function inverse(
    int $scale = 10,
    RoundingMode $mode = RoundingMode::HalfUp
): self;
public function supports(
    CurrencyCode $sourceCurrency,
    CurrencyCode $targetCurrency
): bool;
public function equals(self $other): bool;

public function getSourceCurrency(): CurrencyCode;
public function getTargetCurrency(): CurrencyCode;
public function getRate(): Decimal;
public function __toString(): string;
public function jsonSerialize(): array;
```

**Invarianten**

- Der Kurs ist echt größer null.
- Bei identischer Ausgangs- und Zielwährung ist ausschließlich Kurs `1`
  zulässig.
- `convert()` akzeptiert nur `Money` in der Ausgangswährung.
- Die Zielskala folgt ohne explizite Angabe dem ISO-4217-Standard der
  Zielwährung.
- `convert()` delegiert an `Money::convertTo()` und dupliziert dessen
  Rundungslogik nicht.
- `inverse()` vertauscht die Währungen und berechnet `1 / rate` mit der
  angegebenen Skala.
- Ein Gültigkeitszeitpunkt gehört zunächst nicht in dieses Value Object. Ein
  historisches Kursangebot ist ein späteres, separates Domänenobjekt.

**Serialisierung**

```json
{"source":"EUR","target":"CHF","rate":"0.9385","scale":4}
```

**Pflichttests**

- EUR nach CHF mit eindeutiger Richtung,
- Ablehnung von null und negativen Kursen,
- Ablehnung einer falschen Geld-Ausgangswährung,
- ISO-Zielskalen für EUR, JPY und KWD,
- identische Währung nur mit Kurs eins,
- Invertierung und Währungstausch,
- JSON-/Array-Roundtrip.

---

## 4. Phase B: Zeiträume

Die zeitlichen Value Objects verwenden ausschließlich PHP-eigene
`DateTimeImmutable`-/`DateTimeInterface`-Typen. Das Toolkit erhält keine
Abhängigkeit auf Carbon oder Laravel.

### 4.1 `DateRange`

**Dateien**

- `src/ValueObjects/DateRange.php`
- `tests/ValueObjects/DateRangeTest.php`

**Semantik**

Ein `DateRange` bildet Kalendertage ab und ist an beiden Grenzen inklusiv:

```text
[from, to]
```

Übergebene Zeitanteile werden auf `00:00:00` normalisiert. `from` muss kleiner
oder gleich `to` sein. Ein eintägiger Bereich ist gültig.

**Vorgesehene API**

```php
public static function between(
    DateTimeInterface $from,
    DateTimeInterface $to
): self;
public static function singleDay(DateTimeInterface $date): self;
public static function fromStrings(
    string $from,
    string $to,
    ?DateTimeZone $timezone = null
): self;
public static function fromArray(array $data): self;

public function contains(DateTimeInterface $date): bool;
public function overlaps(self $other): bool;
public function touches(self $other): bool;
public function intersection(self $other): ?self;
public function span(self $other): self;
public function shiftDays(int $days): self;
public function calendarDays(): int;

public function getFrom(): DateTimeImmutable;
public function getTo(): DateTimeImmutable;
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): array;
```

**Serialisierung**

```json
{"from":"2026-07-01","to":"2026-07-31"}
```

**Pflichttests**

- eintägiger und mehrtägiger Bereich,
- Zeitanteile werden entfernt,
- umgekehrte Grenzen werden abgelehnt und nicht automatisch vertauscht,
- inklusive Grenzprüfung,
- überlappende, getrennte und direkt benachbarte Bereiche,
- Schnittmenge mit und ohne Ergebnis,
- `calendarDays()` zählt beide Grenzen,
- Monats- und Jahreswechsel einschließlich Schaltjahr,
- Zeitzonen bleiben deterministisch,
- JSON-/Array-Roundtrip.

### 4.2 `DateTimeRange`

**Dateien**

- `src/ValueObjects/DateTimeRange.php`
- `tests/ValueObjects/DateTimeRangeTest.php`

**Semantik**

Ein `DateTimeRange` bildet ein halboffenes Intervall ab:

```text
[start, end)
```

Der Start ist enthalten, das Ende nicht. Dadurch überlappen direkt
aufeinanderfolgende Buchungen nicht. `start` muss echt kleiner als `end` sein;
ein Intervall ohne Dauer ist ungültig.

**Vorgesehene API**

```php
public static function between(
    DateTimeInterface $start,
    DateTimeInterface $end
): self;
public static function fromArray(array $data): self;

public function contains(DateTimeInterface $instant): bool;
public function overlaps(self $other): bool;
public function touches(self $other): bool;
public function intersection(self $other): ?self;
public function span(self $other): self;
public function durationInSeconds(): int;

public function getStart(): DateTimeImmutable;
public function getEnd(): DateTimeImmutable;
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): array;
```

ISO-8601-Ausgaben müssen den Offset enthalten. Vergleiche erfolgen nach dem
tatsächlichen Zeitpunkt, nicht nach der formatierten lokalen Uhrzeit.

**Pflichttests**

- Start enthalten, Ende ausgeschlossen,
- gleiches Start-/Ende-Paar wird abgelehnt,
- direkt aneinandergrenzende Intervalle berühren sich, überlappen aber nicht,
- Schnittmenge und Hüllintervall,
- korrekte Dauer über Tages- und Zeitzonenwechsel,
- gleiche Zeitpunkte mit verschiedenen Offsets,
- JSON-/Array-Roundtrip.

---

## 5. Phase C: Validierte Identifikatoren

### 5.1 Gemeinsame Regeln

- Value Objects delegieren an vorhandene Helper.
- Konstruktionsfactories validieren deterministisch und führen keine
  Netzwerkabfragen durch.
- Der intern gespeicherte Wert ist immer normalisiert.
- `equals()` vergleicht die normalisierte Darstellung.
- Sensible Identifikatoren geben den Klarwert nur über `getValue()` zurück.
- `masked()` darf nie mehr Zeichen offenlegen als dokumentiert.
- Es gibt keine automatische JSON-Serialisierung sensibler Werte.

### 5.2 `Iban`

**Dateien**

- `src/ValueObjects/Iban.php`
- `tests/ValueObjects/IbanTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\BankHelper`

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;
public function formatted(string $separator = ' '): string;
public function masked(int $visibleStart = 4, int $visibleEnd = 4): string;
public function getCountry(): CountryCode;
public function isSepa(): bool;
public function getBankCode(): ?string;
public function getAccountNumber(): ?string;
public function equals(self $other): bool;
```

**Invarianten**

- Konstruktion verwendet Normalisierung und vollständige Prüfsummenprüfung:
  `BankHelper::validateIBAN($value, true)`.
- Maskierte oder anonymisierte IBANs sind keine gültigen Instanzen.
- `formatted()` gruppiert von links in Viererblöcke.
- `getCountry()` muss für eine gültige IBAN einen bekannten `CountryCode`
  liefern; andernfalls ist die Konstruktion abzulehnen.
- Kein `__toString()` und kein `jsonSerialize()`.

**Pflichttests**

- normalisierte Großschreibung und entfernte Leerzeichen,
- gültige IBANs mehrerer Länder,
- ungültige Prüfziffer, falsche Länderlänge und Masken,
- Formatierung und Maskierung,
- Länder-, SEPA-, Bankcode- und Kontonummernzugriff,
- Gleichheit unterschiedlich formatierter Eingaben,
- sicherstellen, dass kein String-/JSON-Interface implementiert wird.

### 5.3 `Bic`

**Dateien**

- `src/ValueObjects/Bic.php`
- `tests/ValueObjects/BicTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\BankHelper`

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;
public function getCountry(): CountryCode;
public function getInstitutionCode(): string;
public function getLocationCode(): string;
public function getBranchCode(): ?string;
public function asBic11(): string;
public function equals(self $other): bool;
public function __toString(): string;
public function jsonSerialize(): string;
```

Die Normalisierung erfolgt auf Großschreibung ohne Whitespace. Gültigkeit
delegiert an `BankHelper::isBIC()`. BIC8 und die dazugehörige BIC11 mit
`XXX`-Filialcode sollen fachlich als gleich gelten; intern wird deshalb
kanonisch BIC11 gespeichert.

### 5.4 `EmailAddress`

**Dateien**

- `src/ValueObjects/EmailAddress.php`
- `tests/ValueObjects/EmailAddressTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\EmailHelper`

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;
public function getLocalPart(): string;
public function getDomain(): string;
public function masked(int $showChars = 2): string;
public function isDisposable(): bool;
public function isFreeProvider(): bool;
public function equals(self $other): bool;
```

Die Konstruktion verwendet ausschließlich die deterministische Formatprüfung.
DNS-/MX-Prüfungen sind ausdrücklich kein Konstruktorbestandteil. Die
Normalisierung delegiert an `EmailHelper::normalize()` ohne
providerspezifisches Entfernen von Punkten.

Kein `__toString()` und kein `jsonSerialize()`.

### 5.5 `PhoneNumber`

**Dateien**

- `src/ValueObjects/PhoneNumber.php`
- `tests/ValueObjects/PhoneNumberTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\PhoneNumberHelper`

**Vorgesehene API**

```php
public static function of(
    string $value,
    CountryCode $defaultCountry = CountryCode::Germany
): self;
public static function tryFrom(
    ?string $value,
    CountryCode $defaultCountry = CountryCode::Germany
): ?self;

public function getValue(): string;
public function getCountry(): ?CountryCode;
public function international(): string;
public function national(?CountryCode $country = null): string;
public function masked(int $visibleEnd = 3): string;
public function isFromCountry(CountryCode $country): bool;
public function equals(self $other): bool;
```

Intern wird ausschließlich E.164 gespeichert. Die Umwandlung delegiert an
`PhoneNumberHelper::toE164WithCountryCode()`.

Kein `__toString()` und kein `jsonSerialize()`.

### 5.6 `VatNumber`

**Dateien**

- `src/ValueObjects/VatNumber.php`
- `tests/ValueObjects/VatNumberTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\VatNumberHelper`

**Vorgesehene API**

```php
public static function of(string $value, bool $strict = true): self;
public static function tryFrom(
    ?string $value,
    bool $strict = true
): ?self;

public function getValue(): string;
public function getCountryCode(): string;
public function getNationalNumber(): string;
public function formatted(): string;
public function masked(int $visibleEnd = 4): string;
public function equals(self $other): bool;
```

`getCountryCode()` liefert bewusst `string`, weil vorhandene gültige Präfixe
wie `CHE` und `XI` nicht durchgehend ISO-3166-Alpha-2-Fälle sind.

Kein `__toString()` und kein `jsonSerialize()`.

### 5.7 `CreditorIdentifier`

**Dateien**

- `src/ValueObjects/CreditorIdentifier.php`
- `tests/ValueObjects/CreditorIdentifierTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\CreditorIdHelper`

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;
public function getCountry(): CountryCode;
public function getBusinessAreaCode(): string;
public function getNationalIdentifier(): string;
public function formatted(string $separator = ' '): string;
public function masked(int $visibleStart = 7, int $visibleEnd = 4): string;
public function equals(self $other): bool;
```

Die Konstruktion verlangt die vollständige Prüfziffernvalidierung über
`CreditorIdHelper::validateCreditorId()`.

Kein `__toString()` und kein `jsonSerialize()`.

### 5.8 `GermanTaxId`

**Dateien**

- `src/ValueObjects/GermanTaxId.php`
- `tests/ValueObjects/GermanTaxIdTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\TaxNumberHelper`

Dieses Objekt bildet ausschließlich die persönliche deutsche
Steuer-Identifikationsnummer mit elf Ziffern ab. Es darf nicht mit einer
betrieblichen Steuernummer vermischt werden.

**Vorgesehene API**

```php
public static function of(string $value): self;
public static function tryFrom(?string $value): ?self;

public function getValue(): string;
public function formatted(): string;
public function masked(int $visibleEnd = 3): string;
public function equals(self $other): bool;
```

Die Konstruktion verwendet `TaxNumberHelper::validateIdNr()`.

Kein `__toString()` und kein `jsonSerialize()`.

### 5.9 `GermanTaxNumber`

**Dateien**

- `src/ValueObjects/GermanTaxNumber.php`
- `tests/ValueObjects/GermanTaxNumberTest.php`

**Verwendeter Helper**

`CommonToolkit\Helper\Data\TaxNumberHelper`

Dieses Objekt bildet eine deutsche betriebliche Steuernummer ab. Der Name ist
absichtlich länderspezifisch; ein generisches `TaxNumber` würde eine
internationale Semantik vortäuschen, die der vorhandene Helper nicht besitzt.

**Vorgesehene API**

```php
public static function of(
    string $value,
    ?string $federalState = null
): self;
public static function tryFrom(
    ?string $value,
    ?string $federalState = null
): ?self;

public function getValue(): string;
public function getFederalState(): ?string;
public function isUnifiedFormat(): bool;
public function formatted(): string;
public function masked(int $visibleEnd = 3): string;
public function equals(self $other): bool;
```

Bundesland-Kürzel werden normalisiert und gegen die tatsächlich vom Helper
unterstützten Werte geprüft. Falls diese Liste derzeit nur intern im Helper
vorliegt, ist eine kleine öffentliche Abfragemethode im Helper einer
duplizierten Liste im Value Object vorzuziehen.

Kein `__toString()` und kein `jsonSerialize()`.

---

## 6. Bewusste Nicht-Ziele

Folgende Erweiterungen sind in diesem Auftrag nicht zu implementieren:

- automatischer Umbau bestehender Anwendungen auf die neuen Value Objects,
- Eloquent- oder Symfony-Casts,
- Laravel-/Carbon-Abhängigkeiten,
- automatische physikalische Einheitenumrechnung in `Quantity`,
- ein abstraktes universelles `ValueObject`,
- ein semantikloses universelles `Identifier`,
- `Address`, `CustomerNumber`, `InvoiceNumber` oder andere
  anwendungsspezifische Objekte,
- `TaxedMoney`, `InvoicePrice`, `DiscountedPrice` oder andere Objekte aus der
  Rechnungsdomäne,
- Netzwerkvalidierung bei der Konstruktion,
- implizite Währungsumrechnung,
- implizite Konvertierung exakter Werte zu `float`,
- persistenz- oder framework-spezifische Logik.

Ein generisches physikalisches `Measurement` kann später separat geplant
werden. Dafür müssten die vorhandenen Einheiten-Enums zunächst über einen
gemeinsamen Vertrag Dimension und exakten Umrechnungsfaktor bereitstellen.

---

## 7. Vorgehensweise für Claude

Für jedes Objekt ist in dieser Reihenfolge vorzugehen:

1. Vorhandene Helper, Enums, `NumberHelper`, `Money` und zugehörige Tests lesen.
2. Prüfen, ob die in diesem Dokument vorgesehene API vollständig mit PHP 8.1
   und den vorhandenen Helper-Signaturen umsetzbar ist.
3. Bei einem echten Widerspruch nicht still eine andere Semantik erfinden,
   sondern den Widerspruch benennen und die kleinste robuste Anpassung
   vorschlagen.
4. Zuerst Tests für Konstruktion, Invarianten, Normalisierung und Fehlerfälle
   ergänzen.
5. Danach das Value Object implementieren.
6. Pint, PHPStan und die vollständige Testsuite ausführen.
7. Erst bei grünen Gates mit dem nächsten Objekt fortfahren.

Implementierungen sollen vorhandene Muster übernehmen, aber erkennbare
Altlasten nicht ungeprüft vervielfältigen. Insbesondere darf ein Value Object
niemals eine ungültige Eingabe still als echten Nullwert akzeptieren.

Nach Abschluss aller Phasen ist die `README.md` um kurze, ausführbare Beispiele
für mindestens `Decimal`, `Percentage`, `Quantity`, `ExchangeRate`,
`DateRange`, `Iban`, `EmailAddress` und `PhoneNumber` zu ergänzen.

---

## 8. Abschlusskriterien

Der Auftrag gilt als erfüllt, wenn:

- alle vierzehn aufgeführten Value Objects vorhanden sind,
- alle beschriebenen Invarianten umgesetzt sind,
- sensible Werte nicht versehentlich durch implizite String- oder
  JSON-Konvertierung offengelegt werden,
- keine vorhandene öffentliche API gebrochen wurde,
- `Money` weiterhin vollständig rückwärtskompatibel ist,
- `composer qa` erfolgreich läuft,
- die README die wichtigsten Einstiegspunkte dokumentiert,
- keine Änderungen außerhalb des CommonToolkit-Repositories erforderlich
  sind.

