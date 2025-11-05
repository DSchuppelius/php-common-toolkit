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

use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

final class CSVDocument {
    use ErrorLog;

    private ?CSVHeaderLine $header;
    /** @var CSVDataLine[] */
    private array $rows;

    private string $delimiter;
    private string $enclosure;

    public function __construct(?CSVHeaderLine $header = null, array $rows = [], string $delimiter = ',', string $enclosure = '"') {
        $this->header    = $header;
        $this->rows      = $rows;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    public function hasHeader(): bool {
        return $this->header !== null;
    }

    public function getHeader(): ?CSVHeaderLine {
        return $this->header;
    }

    /** @return CSVDataLine[] */
    public function getRows(): array {
        return array_values($this->rows);
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

    /**
     * Überprüft, ob alle Zeilen die gleiche Anzahl an Feldern haben wie der Header (falls vorhanden) oder die erste Zeile.
     *
     * @return bool
     */
    public function isConsistent(): bool {
        if ($this->rows === []) return true;
        $expected = $this->header?->countFields() ?? $this->rows[0]->countFields();

        foreach ($this->rows as $i => $row) {
            if ($row->countFields() !== $expected) {
                static::logError("CSV-Zeile $i hat abweichende Feldanzahl");
                return false;
            }
        }
        return true;
    }

    /**
     * Wandelt das gesamte CSV-Dokument in eine rohe CSV-Zeichenkette um.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null): string {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;

        if ($enclosureRepeat !== null) {
            foreach (array_merge($this->rows, $this->header ? [$this->header] : []) as $line) {
                foreach ($line->getFields() as $field) {
                    $field->setEnclosureRepeat($enclosureRepeat);
                }
            }
        }

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
     * Schreibt das gesamte CSV-Dokument in eine Datei.
     *
     * @param string      $file      Der Pfad zur Zieldatei.
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @return void
     *
     * @throws RuntimeException
     */
    public function toFile(string $file, ?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null): void {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;

        $csv = $this->toString($delimiter, $enclosure, $enclosureRepeat);

        $result = @file_put_contents($file, $csv);
        if ($result === false) {
            static::logError("Fehler beim Schreiben der CSV-Datei: $file");
            throw new RuntimeException("Fehler beim Schreiben der CSV-Datei: $file");
        }
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
     * Vergleicht dieses CSV-Dokument mit einem anderen auf Gleichheit.
     *
     * @param CSVDocument $other Das andere CSV-Dokument zum Vergleichen.
     * @return bool
     */
    public function equals(CSVDocument $other): bool {
        if ($this->delimiter !== $other->delimiter) return false;
        if ($this->enclosure !== $other->enclosure) return false;
        if (($this->header && !$other->header) || (!$this->header && $other->header)) return false;
        if ($this->header && !$this->header->equals($other->header)) return false;
        if (count($this->rows) !== count($other->rows)) return false;
        foreach ($this->rows as $i => $row) {
            if (!$row->equals($other->rows[$i])) return false;
        }
        return true;
    }
}