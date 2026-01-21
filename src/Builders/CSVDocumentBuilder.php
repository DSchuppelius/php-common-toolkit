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

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Contracts\Interfaces\CSV\LineInterface;
use CommonToolkit\Entities\CSV\Document;
use CommonToolkit\Entities\CSV\HeaderLine;
use CommonToolkit\Entities\CSV\DataLine;
use CommonToolkit\Entities\CSV\ColumnWidthConfig;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use RuntimeException;

class CSVDocumentBuilder extends HelperAbstract {

    protected ?HeaderLine $header = null;
    /** @var DataLine[] */
    protected array $rows = [];
    protected string $delimiter;
    protected string $enclosure;
    protected ?ColumnWidthConfig $columnWidthConfig = null;
    protected string $encoding = Document::DEFAULT_ENCODING;

    public function __construct(string $delimiter = ',', string $enclosure = '"', ?ColumnWidthConfig $columnWidthConfig = null, string $encoding = Document::DEFAULT_ENCODING) {
        $this->delimiter           = $delimiter;
        $this->enclosure           = $enclosure;
        $this->columnWidthConfig   = $columnWidthConfig;
        $this->encoding            = $encoding;
    }

    /**
     * Fügt eine Zeile hinzu.
     * @param LineInterface $line
     * @return $this
     */
    public function addLine(LineInterface $line): self {
        match (true) {
            $line instanceof HeaderLine => $this->header = $line,
            $line instanceof DataLine   => $this->rows[] = $line,
            default => self::logErrorAndThrow(RuntimeException::class, 'Unsupported CSV line type: ' . $line::class),
        };
        return $this;
    }

    /**
     * Fügt mehrere Zeilen hinzu.
     * @param LineInterface[] $lines
     * @return $this
     */
    public function addLines(array $lines): self {
        foreach ($lines as $line) {
            if ($line instanceof LineInterface) {
                $this->addLine($line);
            } else {
                $this->logErrorAndThrow(RuntimeException::class, 'Ungültiger Zeilentyp: ' . get_debug_type($line));
            }
        }
        return $this;
    }

    /**
     * Setzt den Header der CSV-Datei.
     * @param HeaderLine $header
     * @return $this
     */
    public function setHeader(HeaderLine $header): self {
        $this->header = $header;
        return $this;
    }

    /**
     * Fügt eine Datenzeile hinzu.
     * @param DataLine $row
     * @return $this
     */
    public function addRow(DataLine $row): self {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Fügt mehrere Datenzeilen hinzu.
     * @param DataLine[] $rows
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
     * @param Document $document
     * @param string|null $delimiter
     * @param string|null $enclosure
     * @return self
     */
    public static function fromDocument(Document $document, ?string $delimiter = null, ?string $enclosure = null): self {
        $builder = new self(
            $delimiter ?? $document->getDelimiter(),
            $enclosure ?? $document->getEnclosure(),
            $document->getColumnWidthConfig(),
            $document->getEncoding()
        );

        $builder->header = $document->getHeader() ? clone $document->getHeader() : null;
        $builder->rows   = array_map(fn($row) => clone $row, $document->getRows());
        return $builder;
    }

    /**
     * Setzt die Zeichenkodierung des Dokuments.
     *
     * @param string $encoding Die Zeichenkodierung (z.B. 'UTF-8', 'ISO-8859-1')
     * @return $this
     */
    public function setEncoding(string $encoding): self {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Gibt die aktuelle Zeichenkodierung zurück.
     *
     * @return string
     */
    public function getEncoding(): string {
        return $this->encoding;
    }

    /**
     * Setzt die Spaltenbreiten-Konfiguration.
     *
     * @param ColumnWidthConfig|null $config
     * @return $this
     */
    public function setColumnWidthConfig(?ColumnWidthConfig $config): self {
        $this->columnWidthConfig = $config;
        return $this;
    }

    /**
     * Setzt eine Spaltenbreite für eine bestimmte Spalte.
     *
     * @param string|int $column Spaltenname oder Index
     * @param int $width Maximale Breite
     * @return $this
     */
    public function setColumnWidth(string|int $column, int $width): self {
        if ($this->columnWidthConfig === null) {
            $this->columnWidthConfig = new ColumnWidthConfig();
        }
        $this->columnWidthConfig->setColumnWidth($column, $width);
        return $this;
    }

    /**
     * Setzt eine Standard-Spaltenbreite für alle Spalten.
     *
     * @param int|null $width Standardbreite oder null zum Deaktivieren
     * @return $this
     */
    public function setDefaultColumnWidth(?int $width): self {
        if ($this->columnWidthConfig === null) {
            $this->columnWidthConfig = new ColumnWidthConfig();
        }
        $this->columnWidthConfig->setDefaultWidth($width);
        return $this;
    }

    /**
     * Setzt die Abschneidungsstrategie für zu lange Werte.
     *
     * @param TruncationStrategy $strategy Abschneidungsstrategie
     * @return $this
     */
    public function setTruncationStrategy(TruncationStrategy $strategy): self {
        if ($this->columnWidthConfig === null) {
            $this->columnWidthConfig = new ColumnWidthConfig();
        }
        $this->columnWidthConfig->setTruncationStrategy($strategy);
        return $this;
    }

    /**
     * Aktiviert/Deaktiviert Padding für feste Spaltenbreiten.
     *
     * @param bool $enable Padding aktivieren
     * @param string $char Padding-Zeichen (Standard: Leerzeichen)
     * @param int $type Padding-Richtung (STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH)
     * @return $this
     */
    public function setPadding(bool $enable, string $char = ' ', int $type = STR_PAD_RIGHT): self {
        if ($this->columnWidthConfig === null) {
            $this->columnWidthConfig = new ColumnWidthConfig();
        }
        $this->columnWidthConfig->setPadding($enable, $char, $type);
        return $this;
    }

    /**
     * Sortiert die Spalten der CSV-Datei neu.
     * @param string[] $newOrder
     * @return $this
     * @throws RuntimeException
     */
    public function reorderColumns(array $newOrder): self {
        if (!$this->header) {
            $this->logErrorAndThrow(RuntimeException::class, 'Kein Header vorhanden – Spalten können nicht umsortiert werden.');
        }

        $headerValues = array_map(fn($f) => $f->getValue(), $this->header->getFields());
        $headerMap = array_flip($headerValues);

        foreach ($newOrder as $name) {
            if (!isset($headerMap[$name])) {
                $this->logErrorAndThrow(RuntimeException::class, "Spalte '$name' existiert nicht im Header.");
            }
        }

        // Header neu sortieren
        $reorderedHeaderFields = array_map(
            fn($name) => $this->header->getFields()[$headerMap[$name]],
            $newOrder
        );
        $this->header = new HeaderLine($reorderedHeaderFields);

        // Zeilen neu sortieren
        $newRows = [];
        foreach ($this->rows as $row) {
            $fields = $row->getFields();
            $reorderedFields = array_map(
                fn($name) => $fields[$headerMap[$name]],
                $newOrder
            );
            $newRows[] = new DataLine($reorderedFields, $this->delimiter, $this->enclosure);
        }
        $this->rows = $newRows;

        return $this;
    }

    /**
     * Baut das CSV-Dokument.
     * @return Document
     */
    public function build(): Document {
        return new Document(
            $this->header,
            $this->rows,
            $this->delimiter,
            $this->enclosure,
            $this->columnWidthConfig,
            $this->encoding
        );
    }
}
