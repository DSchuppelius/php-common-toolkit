<?php
/*
 * Created on   : Fri Oct 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Contracts\Interfaces\Common\CSVLineInterface;
use CommonToolkit\Entities\Common\CSV\CSVDocument;
use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;
use CommonToolkit\Entities\Common\CSV\CSVDataLine;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

final class CSVDocumentBuilder {
    use ErrorLog;

    private ?CSVHeaderLine $header = null;
    /** @var CSVDataLine[] */
    private array $rows = [];
    private string $delimiter;
    private string $enclosure;

    public function __construct(string $delimiter = ',', string $enclosure = '"') {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    /**
     * Fügt eine Zeile hinzu.
     * @param CSVLineInterface $line
     * @return $this
     */
    public function addLine(CSVLineInterface $line): self {
        match (true) {
            $line instanceof CSVHeaderLine => $this->header = $line,
            $line instanceof CSVDataLine   => $this->rows[] = $line,
            default => throw new RuntimeException('Unsupported CSV line type: ' . $line::class),
        };
        return $this;
    }

    /**
     * Fügt mehrere Zeilen hinzu.
     * @param CSVLineInterface[] $lines
     * @return $this
     */
    public function addLines(array $lines): self {
        foreach ($lines as $line) {
            if ($line instanceof CSVLineInterface) {
                $this->addLine($line);
            } else {
                $this->logError('Ungültiger Zeilentyp übergeben', ['line' => $line]);
                throw new RuntimeException('Ungültiger Zeilentyp: ' . get_debug_type($line));
            }
        }
        return $this;
    }

    /**
     * Setzt den Header der CSV-Datei.
     * @param CSVHeaderLine $header
     * @return $this
     */
    public function setHeader(CSVHeaderLine $header): self {
        $this->header = $header;
        return $this;
    }

    /**
     * Fügt eine Datenzeile hinzu.
     * @param CSVDataLine $row
     * @return $this
     */
    public function addRow(CSVDataLine $row): self {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Fügt mehrere Datenzeilen hinzu.
     * @param CSVDataLine[] $rows
     * @return $this
     */
    public function addRows(array $rows): self {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    /**
     * Erstellt einen Builder aus einem bestehenden CSV-Dokument.
     * @param CSVDocument $document
     * @param string|null $delimiter
     * @param string|null $enclosure
     * @return self
     */
    public static function fromDocument(CSVDocument $document, ?string $delimiter = null, ?string $enclosure = null): self {
        $builder = new self(
            $delimiter ?? $document->getDelimiter(),
            $enclosure ?? $document->getEnclosure()
        );

        $builder->header = $document->getHeader() ? clone $document->getHeader() : null;
        $builder->rows   = array_map(fn($row) => clone $row, $document->getRows());
        return $builder;
    }

    /**
     * Sortiert die Spalten der CSV-Datei neu.
     * @param string[] $newOrder
     * @return $this
     * @throws RuntimeException
     */
    public function reorderColumns(array $newOrder): self {
        if (!$this->header) {
            $this->logError('Kein Header vorhanden – Spalten können nicht umsortiert werden.');
            throw new RuntimeException('Kein Header vorhanden – Spalten können nicht umsortiert werden.');
        }

        $headerValues = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $headerMap = array_flip($headerValues);

        foreach ($newOrder as $name) {
            if (!isset($headerMap[$name])) {
                $this->logError("Spalte '$name' existiert nicht im Header.");
                throw new RuntimeException("Spalte '$name' existiert nicht im Header.");
            }
        }

        // Header neu sortieren
        $reorderedHeaderFields = array_map(
            fn($name) => $this->header->getFields()[$headerMap[$name]],
            $newOrder
        );
        $this->header = new CSVHeaderLine($reorderedHeaderFields);

        // Zeilen neu sortieren
        $newRows = [];
        foreach ($this->rows as $row) {
            $fields = $row->getFields();
            $reorderedFields = array_map(
                fn($name) => $fields[$headerMap[$name]],
                $newOrder
            );
            $newRows[] = new CSVDataLine($reorderedFields);
        }
        $this->rows = $newRows;

        return $this;
    }

    /**
     * Baut das CSV-Dokument.
     * @return CSVDocument
     */
    public function build(): CSVDocument {
        return new CSVDocument(
            $this->header,
            $this->rows,
            $this->delimiter,
            $this->enclosure
        );
    }
}
