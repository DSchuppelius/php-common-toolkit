<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XLSXDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XLSX\Cell;
use CommonToolkit\Entities\XLSX\Document;
use CommonToolkit\Entities\XLSX\Row;
use CommonToolkit\Entities\XLSX\Sheet;
use RuntimeException;

/**
 * Fluent Builder für XLSX-Dokumente.
 * Analog zu CSVDocumentBuilder.
 */
class XLSXDocumentBuilder extends HelperAbstract {
    /** @var Sheet[] */
    protected array $sheets = [];
    protected ?string $creator = null;
    protected ?string $title = null;
    protected ?string $description = null;

    // Aktuelles Sheet für fluent API
    protected ?string $currentSheetName = null;
    protected ?Row $currentHeader = null;
    /** @var Row[] */
    protected array $currentRows = [];

    public function __construct() {
        // Standardmäßig erstes Sheet starten
        $this->currentSheetName = 'Sheet1';
    }

    /**
     * Erstellt einen neuen Builder aus einem bestehenden Dokument.
     */
    public static function fromDocument(Document $document): self {
        $builder = new self();
        $builder->sheets = $document->getSheets();
        $builder->creator = $document->getCreator();
        $builder->title = $document->getTitle();
        $builder->description = $document->getDescription();
        $builder->currentSheetName = null;
        $builder->currentRows = [];
        $builder->currentHeader = null;
        return $builder;
    }

    /**
     * Startet ein neues Arbeitsblatt.
     *
     * @param string $name Der Name des Arbeitsblatts
     * @return $this
     */
    public function sheet(string $name): self {
        // Aktuelles Sheet finalisieren falls vorhanden
        $this->finalizeCurrentSheet();

        $this->currentSheetName = $name;
        $this->currentHeader = null;
        $this->currentRows = [];

        return $this;
    }

    /**
     * Setzt den Header für das aktuelle Sheet.
     *
     * @param array<string|Cell> $headers Die Header-Werte
     * @return $this
     */
    public function setHeader(array $headers): self {
        $cells = array_map(
            fn($h) => $h instanceof Cell ? $h : new Cell($h, 's'),
            $headers
        );
        $this->currentHeader = new Row($cells, 1);
        return $this;
    }

    /**
     * Setzt den Header aus einer Row.
     *
     * @param Row $header Die Header-Zeile
     * @return $this
     */
    public function setHeaderRow(Row $header): self {
        $this->currentHeader = $header;
        return $this;
    }

    /**
     * Fügt eine Datenzeile hinzu.
     *
     * @param array<mixed>|Row $row Die Zeilendaten
     * @return $this
     */
    public function addRow(array|Row $row): self {
        $rowIndex = count($this->currentRows) + ($this->currentHeader !== null ? 2 : 1);

        if ($row instanceof Row) {
            $this->currentRows[] = $row;
        } else {
            $this->currentRows[] = Row::fromArray($row, $rowIndex);
        }

        return $this;
    }

    /**
     * Fügt mehrere Datenzeilen hinzu.
     *
     * @param array<array<mixed>|Row> $rows Die Zeilen
     * @return $this
     */
    public function addRows(array $rows): self {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    /**
     * Setzt den Ersteller des Dokuments.
     *
     * @param string $creator Der Ersteller
     * @return $this
     */
    public function setCreator(string $creator): self {
        $this->creator = $creator;
        return $this;
    }

    /**
     * Setzt den Titel des Dokuments.
     *
     * @param string $title Der Titel
     * @return $this
     */
    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    /**
     * Setzt die Beschreibung des Dokuments.
     *
     * @param string $description Die Beschreibung
     * @return $this
     */
    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    /**
     * Finalisiert das aktuelle Sheet und fügt es zur Liste hinzu.
     */
    protected function finalizeCurrentSheet(): void {
        if ($this->currentSheetName !== null && (!empty($this->currentRows) || $this->currentHeader !== null)) {
            $this->sheets[] = new Sheet(
                $this->currentSheetName,
                $this->currentHeader,
                $this->currentRows,
                count($this->sheets)
            );
        }
    }

    /**
     * Baut das XLSX-Dokument.
     *
     * @return Document
     */
    public function build(): Document {
        // Aktuelles Sheet finalisieren
        $this->finalizeCurrentSheet();

        if (empty($this->sheets)) {
            self::logErrorAndThrow(RuntimeException::class, 'XLSX-Dokument benötigt mindestens ein Sheet mit Daten');
        }

        return new Document(
            $this->sheets,
            $this->creator,
            $this->title,
            $this->description,
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }

    /**
     * Setzt den Builder zurück.
     *
     * @return $this
     */
    public function reset(): self {
        $this->sheets = [];
        $this->currentSheetName = 'Sheet1';
        $this->currentHeader = null;
        $this->currentRows = [];
        $this->creator = null;
        $this->title = null;
        $this->description = null;
        return $this;
    }
}
