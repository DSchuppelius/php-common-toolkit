<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocument.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */


namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Contracts\Interfaces\Common\CSVLineInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSVFieldInterface;
use CommonToolkit\Helper\Data\StringHelper\CSVStringHelper;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class CSVDocument {
    use ErrorLog;

    protected ?CSVHeaderLine $header = null;
    /** @var CSVDataLine[] */
    protected array $rows = [];

    protected string $delimiter;
    protected string $enclosure;

    public function __construct(
        ?CSVHeaderLine $header = null,
        array $rows = [],
        string $delimiter = CSVLineInterface::DEFAULT_DELIMITER,
        string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE
    ) {
        $this->header = $header;
        $this->rows = $rows;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    // -----------------------------------------------------------------
    // Erzeugung & Parsing
    // -----------------------------------------------------------------

    /**
     * Erstellt ein CSVDocument aus einer Rohzeichenkette.
     */
    public static function fromString(string $csv, string $delimiter = CSVLineInterface::DEFAULT_DELIMITER, string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE, bool $hasHeader = true): self {
        if (trim($csv) === '') {
            throw new RuntimeException('Leere CSV-Datei oder Inhalt');
        }

        $lines = CSVStringHelper::splitCsvByLogicalLine(trim($csv), $delimiter, $enclosure);
        if ($lines === false || $lines === []) {
            static::logError('CSVDocument::fromString() – leere Eingabe');
            throw new RuntimeException('CSVDocument::fromString() – leere Eingabe');
        }

        $header = null;
        $rows = [];
        $buffer = '';

        foreach ($lines as $i => $line) {
            $trimmed = rtrim($line, "\r\n");

            // --- Normale Zeile (nicht multiline)
            if ($i === 0 && $hasHeader) {
                $header = CSVHeaderLine::fromString($trimmed, $delimiter, $enclosure);
            } else {
                $rows[] = CSVDataLine::fromString($trimmed, $delimiter, $enclosure);
            }
        }

        // --- Falls am Ende noch ein unvollständiger Buffer übrig bleibt
        if ($buffer !== '') {
            if ($header === null && $hasHeader) {
                $header = CSVHeaderLine::fromString($buffer, $delimiter, $enclosure);
            } else {
                $rows[] = CSVDataLine::fromString($buffer, $delimiter, $enclosure);
            }
        }

        return new self($header, $rows, $delimiter, $enclosure);
    }


    /**
     * Erstellt ein CSVDocument aus einer Datei.
     */
    public static function fromFile(
        string $file,
        string $delimiter = CSVLineInterface::DEFAULT_DELIMITER,
        string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE,
        bool $hasHeader = true
    ): self {
        if (!is_file($file) || !is_readable($file)) {
            static::logError("CSV-Datei nicht lesbar: $file");
            throw new RuntimeException("CSV-Datei nicht lesbar: $file");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            static::logError("Fehler beim Lesen der CSV-Datei: $file");
            throw new RuntimeException("Fehler beim Lesen der CSV-Datei: $file");
        }

        return static::fromString($content, $delimiter, $enclosure, $hasHeader);
    }

    // -----------------------------------------------------------------
    // Zugriff
    // -----------------------------------------------------------------

    public function hasHeader(): bool {
        return $this->header !== null;
    }

    public function getHeader(): ?CSVHeaderLine {
        return $this->header;
    }

    /**
     * @return CSVDataLine[]
     */
    public function getRows(): array {
        return $this->rows;
    }

    public function getRow(int $index): ?CSVDataLine {
        return $this->rows[$index] ?? null;
    }

    public function countRows(): int {
        return count($this->rows);
    }

    public function getDelimiter(): string {
        return $this->delimiter;
    }

    public function getEnclosure(): string {
        return $this->enclosure;
    }

    // -----------------------------------------------------------------
    // Validierung & Utilities
    // -----------------------------------------------------------------

    /**
     * Prüft, ob alle Zeilen die gleiche Spaltenanzahl haben.
     */
    public function isConsistent(): bool {
        if (empty($this->rows)) return true;

        $expected = $this->hasHeader()
            ? $this->header->countFields()
            : $this->rows[0]->countFields();

        foreach ($this->rows as $i => $row) {
            if ($row->countFields() !== $expected) {
                static::logError("CSV-Zeile $i hat abweichende Feldanzahl");
                return false;
            }
        }

        return true;
    }

    /**
     * Wandelt das gesamte Dokument wieder in eine CSV-Zeichenkette um.
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null): string {
        $delimiter = $delimiter ?? $this->delimiter;
        $enclosure = $enclosure ?? $this->enclosure;

        $lines = [];

        if ($this->header) {
            $lines[] = $this->header->toString($delimiter, $enclosure);
        }

        foreach ($this->rows as $row) {
            $lines[] = $row->toString($delimiter, $enclosure);
        }

        return implode("\n", $lines);
    }

    /**
     * Schreibt das Dokument in eine Datei.
     */
    public function toFile(string $file, ?string $delimiter = null, ?string $enclosure = null): void {
        $csv = $this->toString($delimiter, $enclosure);
        if (file_put_contents($file, $csv) === false) {
            static::logError("Fehler beim Schreiben der CSV-Datei: $file");
            throw new RuntimeException("Fehler beim Schreiben der CSV-Datei: $file");
        }
    }

    // -----------------------------------------------------------------
    // Helfer
    // -----------------------------------------------------------------

    /**
     * Gibt eine Assoziative Darstellung der CSV-Zeilen zurück
     * (nur sinnvoll, wenn Header vorhanden ist).
     *
     * @return array<int,array<string,string>>
     */
    public function toAssoc(): array {
        if (!$this->header) return [];

        $headerValues = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $assoc = [];

        foreach ($this->rows as $row) {
            $values = array_map(fn($f) => $f->getValue(), $row->getFields());
            $assoc[] = array_combine($headerValues, $values);
        }

        return $assoc;
    }

    public function equals(CSVDocument $other): bool {
        if ($this->getDelimiter() !== $other->getDelimiter()) return false;
        if ($this->getEnclosure() !== $other->getEnclosure()) return false;

        // Header-Vergleich
        if (($this->header && !$other->header) || (!$this->header && $other->header)) return false;
        if ($this->header && !$this->header->equals($other->header)) return false;

        // Zeilen-Vergleich
        if ($this->countRows() !== $other->countRows()) return false;
        foreach ($this->rows as $i => $row) {
            if (!$row->equals($other->rows[$i])) return false;
        }
        return true;
    }
}