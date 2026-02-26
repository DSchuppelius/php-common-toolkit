<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


namespace CommonToolkit\Entities\CSV;

use Closure;
use CommonToolkit\Contracts\Abstracts\TextDocumentAbstract;
use CommonToolkit\Contracts\Interfaces\CSV\FieldInterface;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Generators\CSV\CSVGenerator;
use CommonToolkit\Helper\Data\NumberHelper;
use RuntimeException;

/**
 * CSV-Dokument mit Header und Datenzeilen.
 * Erbt von TextDocumentAbstract für Encoding-, BOM- und Zeilenumbruch-Handling.
 * 
 * Verwendet:
 * - CSVGenerator: Generierung von CSV-Strings
 * 
 * @package CommonToolkit\Entities\CSV
 */
class Document extends TextDocumentAbstract {

    protected ?HeaderLine $header;
    /** @var DataLine[] */
    protected array $rows;

    protected string $delimiter;
    protected string $enclosure;
    protected ?ColumnWidthConfig $columnWidthConfig = null;

    /**
     * Steuert ob beim CSV-Export der Header mit ausgegeben werden soll.
     * Standard ist true - kann in Subklassen überschrieben werden.
     */
    protected bool $exportWithHeader = true;

    public function __construct(?HeaderLine $header = null, array $rows = [], string $delimiter = ',', string $enclosure = '"', ?ColumnWidthConfig $columnWidthConfig = null, string $encoding = self::DEFAULT_ENCODING) {
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->columnWidthConfig   = $columnWidthConfig;
        $this->encoding            = $encoding;
        $this->header              = $header;
        $this->rows                = $rows;
    }

    /**
     * Prüft ob eine Spalte mit dem gegebenen Namen existiert.
     *
     * @param string $columnName Name der Spalte
     * @return bool True wenn die Spalte existiert
     */
    public function hasColumn(string $columnName): bool {
        return $this->header?->hasColumn($columnName) ?? false;
    }

    /**
     * Prüft ob ein Header vorhanden ist.
     *
     * @return bool
     */
    public function hasHeader(): bool {
        return $this->header !== null;
    }

    /** @return HeaderLine|null */
    public function getHeader(): ?HeaderLine {
        return $this->header;
    }

    /** @return DataLine[] */
    public function getRows(): array {
        return array_values($this->rows);
    }

    /** @return DataLine|null */
    public function getRow(int $index): ?DataLine {
        return $this->rows[$index] ?? null;
    }

    /**
     * Gibt die erste Datenzeile zurück.
     *
     * @return DataLine|null Die erste Datenzeile oder null wenn keine Zeilen vorhanden
     */
    public function getFirstRow(): ?DataLine {
        return $this->rows[0] ?? null;
    }

    /**
     * Gibt die letzte Datenzeile zurück.
     *
     * @return DataLine|null Die letzte Datenzeile oder null wenn keine Zeilen vorhanden
     */
    public function getLastRow(): ?DataLine {
        if ($this->rows === []) {
            return null;
        }

        return $this->rows[array_key_last($this->rows)];
    }

    /** @return int */
    public function countRows(): int {
        return count($this->rows);
    }

    /**
     * Filtert Zeilen anhand einer Callback-Funktion und gibt ein neues Document zurück.
     * Das Callback erhält jede DataLine und den Zeilen-Index als Parameter.
     *
     * Beispiel: Nur Zeilen mit Betrag > 100:
     *   $filtered = $doc->filterRows(fn(DataLine $row) => (float) $row->getField(2)?->getValue() > 100);
     *
     * @param Closure(DataLine, int): bool $predicate Callback das true für beizubehaltende Zeilen zurückgibt
     * @return static Neues Document mit gefilterten Zeilen
     */
    public function filterRows(Closure $predicate): static {
        $filtered = [];
        foreach ($this->rows as $index => $row) {
            if ($predicate($row, $index)) {
                $filtered[] = $row;
            }
        }

        return new static($this->header, $filtered, $this->delimiter, $this->enclosure, $this->columnWidthConfig, $this->encoding);
    }

    /**
     * Gibt ein neues Document mit einem Ausschnitt der Zeilen zurück.
     * Analog zu array_slice() – nützlich bei CSVs mit Vor-/Nachspann-Zeilen.
     *
     * @param int $offset Start-Index (0-basiert). Negative Werte zählen vom Ende.
     * @param int|null $length Anzahl der Zeilen. Null = bis zum Ende.
     * @return static Neues Document mit dem Zeilenausschnitt
     */
    public function sliceRows(int $offset, ?int $length = null): static {
        $sliced = array_slice($this->rows, $offset, $length);

        return new static($this->header, $sliced, $this->delimiter, $this->enclosure, $this->columnWidthConfig, $this->encoding);
    }

    /**
     * Sortiert die Zeilen nach einer Spalte und gibt ein neues Document zurück.
     *
     * @param string $columnName Name der Spalte zum Sortieren
     * @param bool $ascending Aufsteigend sortieren (Standard: true)
     * @param bool $numeric Numerische Sortierung verwenden (Standard: false = alphabetisch)
     * @param CountryCode|null $country Länder-Code für Zahlenformat bei numerischer Sortierung
     * @return static Neues Document mit sortierten Zeilen
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function sortByColumn(string $columnName, bool $ascending = true, bool $numeric = false, ?CountryCode $country = null): static {
        $index = $this->getColumnIndex($columnName);

        if ($index === null) {
            $available = implode(', ', $this->getColumnNames());
            $this->logErrorAndThrow(RuntimeException::class, "Spalte '$columnName' nicht im Header gefunden. Verfügbar: $available");
        }

        return $this->sortByColumnIndex($index, $ascending, $numeric, $country);
    }

    /**
     * Sortiert die Zeilen nach einem Spalten-Index und gibt ein neues Document zurück.
     *
     * @param int $index Index der Spalte zum Sortieren
     * @param bool $ascending Aufsteigend sortieren (Standard: true)
     * @param bool $numeric Numerische Sortierung verwenden (Standard: false = alphabetisch)
     * @param CountryCode|null $country Länder-Code für Zahlenformat bei numerischer Sortierung
     * @return static Neues Document mit sortierten Zeilen
     */
    public function sortByColumnIndex(int $index, bool $ascending = true, bool $numeric = false, ?CountryCode $country = null): static {
        $sorted = $this->rows;

        usort($sorted, function (DataLine $a, DataLine $b) use ($index, $ascending, $numeric, $country): int {
            $valA = $a->getField($index)?->getValue() ?? '';
            $valB = $b->getField($index)?->getValue() ?? '';

            if ($numeric) {
                $numA = NumberHelper::normalizeDecimal($valA, $country);
                $numB = NumberHelper::normalizeDecimal($valB, $country);
                $cmp = $numA <=> $numB;
            } else {
                $cmp = strnatcasecmp($valA, $valB);
            }

            return $ascending ? $cmp : -$cmp;
        });

        return new static($this->header, $sorted, $this->delimiter, $this->enclosure, $this->columnWidthConfig, $this->encoding);
    }

    /** @return string */
    public function getDelimiter(): string {
        return $this->delimiter;
    }

    /**
     * Gibt zurück, ob der Header beim Export ausgegeben wird.
     *
     * @return bool
     */
    public function getExportWithHeader(): bool {
        return $this->exportWithHeader;
    }

    /**
     * Setzt, ob der Header beim Export ausgegeben werden soll.
     *
     * @param bool $exportWithHeader
     * @return void
     */
    public function setExportWithHeader(bool $exportWithHeader): void {
        $this->exportWithHeader = $exportWithHeader;
    }

    /**
     * Setzt das Trennzeichen für den CSV-Export.
     *
     * @param string $delimiter Das Trennzeichen (z.B. ',' oder ';').
     * @return void
     */
    public function setDelimiter(string $delimiter): void {
        $this->delimiter = $delimiter;
    }

    /** @return string */
    public function getEnclosure(): string {
        return $this->enclosure;
    }

    /**
     * Setzt das Einschlusszeichen für den CSV-Export.
     *
     * @param string $enclosure Das Einschlusszeichen (z.B. '"').
     * @return void
     */
    public function setEnclosure(string $enclosure): void {
        $this->enclosure = $enclosure;
    }

    /**
     * Setzt die Spaltenbreiten-Konfiguration.
     *
     * @param ColumnWidthConfig|null $config
     * @return void
     */
    public function setColumnWidthConfig(?ColumnWidthConfig $config): void {
        $this->columnWidthConfig = $config;
    }

    /**
     * Gibt die Spaltenbreiten-Konfiguration zurück.
     *
     * @return ColumnWidthConfig|null
     */
    public function getColumnWidthConfig(): ?ColumnWidthConfig {
        return $this->columnWidthConfig;
    }

    /**
     * Überprüft, ob alle Zeilen die gleiche Anzahl an Feldern haben wie der Header (falls vorhanden) oder die erste Zeile.
     *
     * @return bool
     */
    public function isConsistent(): bool {
        if ($this->rows === []) return true;
        $expected = $this->header?->countFields() ?? $this->rows[0]->countFields();

        foreach ($this->rows as $i => $row) {
            $actual = $row->countFields();
            if ($actual !== $expected) {
                return $this->logErrorAndReturn(false, "CSV-Zeile $i hat abweichende Feldanzahl (erwartet: $expected, gefunden: $actual)");
            }
        }
        return true;
    }

    /**
     * Wandelt das gesamte CSV-Dokument in eine rohe CSV-Zeichenkette um.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null, ?string $targetEncoding = null): string {
        return (new CSVGenerator())->generate($this, $delimiter, $enclosure, $enclosureRepeat, $targetEncoding, $this->exportWithHeader);
    }

    /**
     * Schreibt das gesamte CSV-Dokument in eine Datei.
     *
     * @param string      $file            Der Pfad zur Zieldatei.
     * @param string|null $delimiter       Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure       Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null    $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @param string|null $targetEncoding  Das Ziel-Encoding. Wenn null, wird das Dokument-Encoding verwendet.
     * @param bool        $withBom         Ob ein BOM (Byte Order Mark) am Anfang der Datei geschrieben werden soll (Standard: true für bessere Encoding-Erkennung).
     * @return void
     *
     * @throws RuntimeException
     */
    public function toFile(string $file, ?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null, ?string $targetEncoding = null, bool $withBom = true): void {
        (new CSVGenerator())->toFile($this, $file, $delimiter, $enclosure, $enclosureRepeat, $targetEncoding, $withBom, $this->exportWithHeader);
    }

    /**
     * Wandelt das CSV-Dokument in ein assoziatives Array um.
     *
     * @return array
     */
    public function toAssoc(): array {
        if (!$this->header) return [];
        $keys = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $assoc = [];
        foreach ($this->rows as $row) {
            $values = array_map(fn($f) => $f->getValue(), $row->getFields());
            $assoc[] = array_combine($keys, $values);
        }
        return $assoc;
    }

    /**
     * Findet den Index einer Spalte anhand des Header-Namens.
     * Delegiert an HeaderLine::getColumnIndex().
     *
     * @param string $columnName Name der Spalte
     * @return int|null Index der Spalte oder null wenn nicht gefunden
     */
    public function getColumnIndex(string $columnName): ?int {
        return $this->header?->getColumnIndex($columnName);
    }

    /**
     * Liefert alle Field-Objekte einer Spalte anhand des Header-Namens.
     * Bietet vollständigen Zugriff auf Field-Metadaten (Wert, Raw-Wert, Quote-Info, etc.).
     *
     * @param string $columnName Name der Spalte
     * @return FieldInterface[] Array mit allen Field-Objekten der Spalte
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function getFieldsByName(string $columnName): array {
        $index = $this->getColumnIndex($columnName);

        if ($index === null) {
            $available = implode(', ', $this->getColumnNames());
            $this->logErrorAndThrow(RuntimeException::class, "Spalte '$columnName' nicht im Header gefunden. Verfügbar: $available");
        }

        return $this->getFieldsByIndex($index);
    }

    /**
     * Liefert alle Werte einer Spalte anhand des Header-Namens.
     *
     * @param string $columnName Name der Spalte
     * @return array Array mit allen Werten der Spalte
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function getColumnByName(string $columnName): array {
        $fields = $this->getFieldsByName($columnName);
        return array_map(fn($field) => $field ? $field->getValue() : '', $fields);
    }

    /**
     * Liefert alle Field-Objekte einer Spalte anhand des Index.
     * Bietet vollständigen Zugriff auf Field-Metadaten (Wert, Raw-Wert, Quote-Info, etc.).
     *
     * @param int $index Index der Spalte
     * @return FieldInterface[] Array mit allen Field-Objekten der Spalte
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function getFieldsByIndex(int $index): array {
        if ($index < 0) {
            $maxIndex = ($this->header?->countFields() ?? 0) - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Spalten-Index '$index' ist ungültig (gültiger Bereich: 0-$maxIndex)");
        }

        $fields = [];
        foreach ($this->rows as $row) {
            $field = $row->getField($index);
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Liefert alle Werte einer Spalte anhand des Index.
     *
     * @param int $index Index der Spalte
     * @return array Array mit allen Werten der Spalte
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function getColumnByIndex(int $index): array {
        $fields = $this->getFieldsByIndex($index);
        return array_map(fn($field) => $field ? $field->getValue() : '', $fields);
    }

    /**
     * Liefert eindeutige Werte einer Spalte anhand des Header-Namens.
     *
     * @param string $columnName Name der Spalte
     * @return array Array mit eindeutigen Werten (Reihenfolge des ersten Vorkommens)
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function uniqueColumnByName(string $columnName): array {
        $index = $this->getColumnIndex($columnName);

        if ($index === null) {
            $available = implode(', ', $this->getColumnNames());
            $this->logErrorAndThrow(RuntimeException::class, "Spalte '$columnName' nicht im Header gefunden. Verfügbar: $available");
        }

        return $this->uniqueColumnByIndex($index);
    }

    /**
     * Liefert eindeutige Werte einer Spalte anhand des Index.
     *
     * @param int $index Index der Spalte
     * @return array Array mit eindeutigen Werten (Reihenfolge des ersten Vorkommens)
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function uniqueColumnByIndex(int $index): array {
        $values = $this->getColumnByIndex($index);

        return array_values(array_unique($values));
    }

    /**
     * Berechnet die Summe einer Spalte anhand des Header-Namens.
     * Verwendet NumberHelper für präzise Berechnung (BC Math).
     *
     * @param string $columnName Name der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Die Summe als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function sumColumnByName(string $columnName, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $index = $this->resolveColumnIndex($columnName);

        return $this->sumColumnByIndex($index, $scale, $country, $skipNonNumeric);
    }

    /**
     * Berechnet die Summe einer Spalte anhand des Index.
     * Verwendet NumberHelper für präzise Berechnung (BC Math).
     *
     * @param int $index Index der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Die Summe als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn der Index ungültig ist oder nicht-numerische Werte gefunden werden
     */
    public function sumColumnByIndex(int $index, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $numbers = $this->extractNumericValues($index, $scale, $country, $skipNonNumeric);

        // bcadd sicherstellt korrekte Dezimalstellen auch bei leerem Array
        return bcadd(NumberHelper::sumPrecise($numbers, $scale), '0', $scale);
    }

    /**
     * Berechnet den Durchschnitt einer Spalte anhand des Header-Namens.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param string $columnName Name der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Der Durchschnitt als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function avgColumnByName(string $columnName, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $index = $this->resolveColumnIndex($columnName);

        return $this->avgColumnByIndex($index, $scale, $country, $skipNonNumeric);
    }

    /**
     * Berechnet den Durchschnitt einer Spalte anhand des Index.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param int $index Index der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Der Durchschnitt als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function avgColumnByIndex(int $index, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $numbers = $this->extractNumericValues($index, $scale, $country, $skipNonNumeric);

        if ($numbers === []) {
            return bcadd('0', '0', $scale);
        }

        $sum = NumberHelper::sumPrecise($numbers, $scale);
        $count = (string) count($numbers);

        return bcdiv($sum, $count, $scale);
    }

    /**
     * Ermittelt den Minimalwert einer Spalte anhand des Header-Namens.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param string $columnName Name der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string|null Der Minimalwert als String oder null bei leerem Ergebnis
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function minColumnByName(string $columnName, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): ?string {
        $index = $this->resolveColumnIndex($columnName);

        return $this->minColumnByIndex($index, $scale, $country, $skipNonNumeric);
    }

    /**
     * Ermittelt den Minimalwert einer Spalte anhand des Index.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param int $index Index der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string|null Der Minimalwert als String oder null bei leerem Ergebnis
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function minColumnByIndex(int $index, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): ?string {
        $numbers = $this->extractNumericValues($index, $scale, $country, $skipNonNumeric);

        if ($numbers === []) {
            return null;
        }

        $min = $numbers[0];
        foreach ($numbers as $number) {
            if (bccomp($number, $min, $scale) < 0) {
                $min = $number;
            }
        }

        return $min;
    }

    /**
     * Ermittelt den Maximalwert einer Spalte anhand des Header-Namens.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param string $columnName Name der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string|null Der Maximalwert als String oder null bei leerem Ergebnis
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    public function maxColumnByName(string $columnName, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): ?string {
        $index = $this->resolveColumnIndex($columnName);

        return $this->maxColumnByIndex($index, $scale, $country, $skipNonNumeric);
    }

    /**
     * Ermittelt den Maximalwert einer Spalte anhand des Index.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param string $columnName Name der Spalte
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string|null Der Maximalwert als String oder null bei leerem Ergebnis
     * @throws RuntimeException Wenn der Index ungültig ist
     */
    public function maxColumnByIndex(int $index, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): ?string {
        $numbers = $this->extractNumericValues($index, $scale, $country, $skipNonNumeric);

        if ($numbers === []) {
            return null;
        }

        $max = $numbers[0];
        foreach ($numbers as $number) {
            if (bccomp($number, $max, $scale) > 0) {
                $max = $number;
            }
        }

        return $max;
    }

    /**
     * Berechnet die Differenz zweier Spalten (Soll - Haben) anhand der Header-Namen.
     * Nützlich für Soll/Haben-Verrechnung in Buchhaltungs-CSVs.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param string $positiveColumn Name der Soll-Spalte (wird addiert)
     * @param string $negativeColumn Name der Haben-Spalte (wird subtrahiert)
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Die Differenz als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn eine Spalte nicht gefunden wird
     */
    public function diffColumnsByName(string $positiveColumn, string $negativeColumn, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $positiveIndex = $this->resolveColumnIndex($positiveColumn);
        $negativeIndex = $this->resolveColumnIndex($negativeColumn);

        return $this->diffColumnsByIndex($positiveIndex, $negativeIndex, $scale, $country, $skipNonNumeric);
    }

    /**
     * Berechnet die Differenz zweier Spalten (A - B) anhand der Spalten-Indizes.
     * Nützlich für Soll/Haben-Verrechnung in Buchhaltungs-CSVs.
     * Verwendet BC Math für präzise Berechnung.
     *
     * @param int $positiveIndex Index der Spalte die addiert wird
     * @param int $negativeIndex Index der Spalte die subtrahiert wird
     * @param int $scale Anzahl Dezimalstellen für die Berechnung (Standard: 2)
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung (Standard: null)
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler (Standard: true)
     * @return string Die Differenz als String (im US-Format für BC Math Kompatibilität)
     * @throws RuntimeException Wenn ein Index ungültig ist
     */
    public function diffColumnsByIndex(int $positiveIndex, int $negativeIndex, int $scale = 2, ?CountryCode $country = null, bool $skipNonNumeric = true): string {
        $sumPositive = $this->sumColumnByIndex($positiveIndex, $scale, $country, $skipNonNumeric);
        $sumNegative = $this->sumColumnByIndex($negativeIndex, $scale, $country, $skipNonNumeric);

        return bcsub($sumPositive, $sumNegative, $scale);
    }

    /**
     * Liefert alle Header-Namen als Array.
     *
     * @return array Array mit allen Spalten-Namen
     */
    public function getColumnNames(): array {
        return $this->header?->getColumnNames() ?? [];
    }

    /**
     * Vergleicht dieses CSV-Dokument mit einem anderen auf Gleichheit.
     *
     * @param Document $other Das andere CSV-Dokument zum Vergleichen.
     * @return bool
     */
    public function equals(Document $other): bool {
        if ($this->delimiter !== $other->delimiter) return false;
        if ($this->enclosure !== $other->enclosure) return false;
        if (($this->header === null) !== ($other->header === null)) return false;
        if ($this->header !== null) {
            if (!$this->header->equals($other->header)) return false;
        }
        if ($this->countRows() !== $other->countRows()) return false;
        foreach ($this->rows as $i => $row) {
            if (!$row->equals($other->rows[$i])) return false;
        }
        return true;
    }

    /**
     * Löst einen Spalten-Namen zu einem Index auf.
     * Wirft eine Exception wenn die Spalte nicht gefunden wird.
     *
     * @param string $columnName Name der Spalte
     * @return int Index der Spalte
     * @throws RuntimeException Wenn die Spalte nicht gefunden wird
     */
    protected function resolveColumnIndex(string $columnName): int {
        $index = $this->getColumnIndex($columnName);

        if ($index === null) {
            $available = implode(', ', $this->getColumnNames());
            $this->logErrorAndThrow(RuntimeException::class, "Spalte '$columnName' nicht im Header gefunden. Verfügbar: $available");
        }

        return $index;
    }

    /**
     * Extrahiert alle numerischen Werte einer Spalte als normalisierte Strings.
     * Zentrale Helper-Methode für sum, avg, min, max Berechnungen.
     *
     * @param int $index Index der Spalte
     * @param int $scale Anzahl Dezimalstellen
     * @param CountryCode|null $country Länder-Code für Zahlenformat-Erkennung
     * @param bool $skipNonNumeric Nicht-numerische Werte überspringen statt Fehler
     * @return string[] Normalisierte Zahlenwerte als Strings (US-Format)
     * @throws RuntimeException Bei ungültigem Index oder nicht-numerischen Werten (wenn skipNonNumeric=false)
     */
    protected function extractNumericValues(int $index, int $scale, ?CountryCode $country, bool $skipNonNumeric): array {
        $fields = $this->getFieldsByIndex($index);
        $numbers = [];

        foreach ($fields as $rowIndex => $field) {
            if ($field === null || $field->isBlank()) {
                if ($skipNonNumeric) {
                    continue;
                }
                $this->logErrorAndThrow(RuntimeException::class, "Leerer Wert in Zeile $rowIndex, Spalte $index");
            }

            $typed = $field->getTypedValue();

            if (is_int($typed) || is_float($typed)) {
                $numbers[] = number_format((float) $typed, $scale, '.', '');
                continue;
            }

            $value = is_string($typed) ? trim($typed) : trim($field->getValue());

            if ($value === '') {
                if ($skipNonNumeric) {
                    continue;
                }
                $this->logErrorAndThrow(RuntimeException::class, "Leerer Wert in Zeile $rowIndex, Spalte $index");
            }

            $normalized = NumberHelper::normalizeDecimal($value, $country);

            if (!is_finite($normalized)) {
                if ($skipNonNumeric) {
                    continue;
                }
                $this->logErrorAndThrow(RuntimeException::class, "Nicht-numerischer Wert '$value' in Zeile $rowIndex, Spalte $index");
            }

            $numbers[] = number_format($normalized, $scale, '.', '');
        }

        return $numbers;
    }

    /**
     * Gibt das CSV-Dokument als String zurück.
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Gibt den Wert eines Feldes zurück.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @return string|null Der Wert oder null wenn nicht vorhanden.
     */
    protected function getFieldValue(int $rowIndex, int $fieldIndex): ?string {
        $row = $this->rows[$rowIndex] ?? null;
        if ($row === null) {
            return null;
        }

        $field = $row->getField($fieldIndex);
        return $field?->getValue();
    }

    /**
     * Setzt den Wert eines Feldes.
     * Nutzt das immutable Pattern: Erstellt neue Field- und Line-Objekte.
     * Behält den bisherigen Quoting-Status des Feldes bei.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @param mixed $value Der neue Wert (wird zu String konvertiert).
     * @throws RuntimeException Wenn Zeile oder Feld nicht existiert.
     */
    protected function setFieldValue(int $rowIndex, int $fieldIndex, mixed $value): void {
        // Ermittle den aktuellen Quoting-Status (Validierung erfolgt in setFieldValueWithQuoting)
        $quoted = $this->rows[$rowIndex]?->getField($fieldIndex)?->isQuoted() ?? false;
        $this->setFieldValueWithQuoting($rowIndex, $fieldIndex, $value, $quoted);
    }

    /**
     * Setzt den Wert eines Feldes mit expliziter Quoting-Kontrolle.
     * Nutzt das immutable Pattern: Erstellt neue Field- und Line-Objekte.
     *
     * @param int $rowIndex Index der Zeile.
     * @param int $fieldIndex Index des Feldes.
     * @param mixed $value Der neue Wert (wird zu String konvertiert).
     * @param bool $quoted Ob der Wert gequotet sein soll.
     * @throws RuntimeException Wenn Zeile oder Feld nicht existiert.
     */
    protected function setFieldValueWithQuoting(int $rowIndex, int $fieldIndex, mixed $value, bool $quoted): void {
        if (!isset($this->rows[$rowIndex])) {
            $maxRow = $this->countRows() - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Zeile $rowIndex existiert nicht (gültiger Bereich: 0-$maxRow)");
        }

        $row = $this->rows[$rowIndex];
        $fields = $row->getFields();

        if (!isset($fields[$fieldIndex])) {
            $maxField = $row->countFields() - 1;
            $this->logErrorAndThrow(RuntimeException::class, "Feld $fieldIndex existiert nicht in Zeile $rowIndex (gültiger Bereich: 0-$maxField)");
        }

        $stringValue = match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_null($value) => '',
            default => (string)$value,
        };

        $fields[$fieldIndex] = $fields[$fieldIndex]->withValue($stringValue)->withQuoted($quoted);

        $this->rows[$rowIndex] = new DataLine($fields, $this->delimiter, $this->enclosure);
    }
}
