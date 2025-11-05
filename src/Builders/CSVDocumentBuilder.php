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

use CommonToolkit\Entities\Common\CSV\CSVDocument;
use CommonToolkit\Entities\Common\CSV\CSVHeaderLine;
use CommonToolkit\Entities\Common\CSV\CSVDataLine;
use RuntimeException;

final class CSVDocumentBuilder {
    private ?CSVHeaderLine $header = null;
    /** @var CSVDataLine[] */
    private array $rows = [];
    private string $delimiter;
    private string $enclosure;

    public function __construct(string $delimiter = ',', string $enclosure = '"') {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    public function setHeader(CSVHeaderLine $header): self {
        $this->header = $header;
        return $this;
    }

    public function addRow(CSVDataLine $row): self {
        $this->rows[] = $row;
        return $this;
    }

    /** @param CSVDataLine[] $rows */
    public function addRows(array $rows): self {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    public static function fromDocument(CSVDocument $document, ?string $delimiter = null, ?string $enclosure = null): self {
        $builder = new self(
            $delimiter ?? $document->getDelimiter(),
            $enclosure ?? $document->getEnclosure()
        );

        $builder->header = $document->getHeader() ? clone $document->getHeader() : null;
        $builder->rows   = array_map(fn($row) => clone $row, $document->getRows());
        return $builder;
    }

    public function reorderColumns(array $newOrder): self {
        if (!$this->header) {
            throw new RuntimeException('Kein Header vorhanden – Spalten können nicht umsortiert werden.');
        }

        $headerValues = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $headerMap = array_flip($headerValues);

        foreach ($newOrder as $name) {
            if (!isset($headerMap[$name])) {
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

    public function build(): CSVDocument {
        return new CSVDocument(
            $this->header,
            $this->rows,
            $this->delimiter,
            $this->enclosure
        );
    }
}