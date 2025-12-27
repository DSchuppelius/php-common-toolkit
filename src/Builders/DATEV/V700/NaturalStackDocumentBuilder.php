<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : NaturalStackDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV\V700;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Entities\DATEV\Documents\NaturalStack;
use CommonToolkit\Entities\DATEV\Header\V700\MetaHeaderDefinition;
use CommonToolkit\Entities\DATEV\Header\NaturalStackHeaderLine;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\{MetaHeaderField, NaturalStackHeaderField};
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use DateTimeImmutable;

/**
 * Builder für DATEV Natural-Stapel (V700).
 * Erstellt komplette DATEV-Export-Dateien mit MetaHeader, FieldHeader und
 * Natural-Buchungsdaten für Land-/Forstwirtschaft (SKR14).
 */
final class NaturalStackDocumentBuilder extends CSVDocumentBuilder {
    use ErrorLog;

    private ?MetaHeaderLine $metaHeader = null;
    private ?NaturalStackHeaderLine $fieldHeader = null;
    /** @var DataLine[] */
    private array $dataLines = [];

    public function __construct(string $delimiter = Document::DEFAULT_DELIMITER, string $enclosure = '"') {
        parent::__construct($delimiter, $enclosure);
    }

    /**
     * Setzt den MetaHeader mit Standard-NaturalStack-Konfiguration.
     */
    public function setMetaHeader(?MetaHeaderLine $metaHeader = null): self {
        $this->metaHeader = $metaHeader ?? $this->createDefaultMetaHeader();
        return $this;
    }

    /**
     * Setzt den FieldHeader (Spaltenbeschreibungen).
     */
    public function setFieldHeader(?NaturalStackHeaderLine $fieldHeader = null): self {
        $this->fieldHeader = $fieldHeader ?? NaturalStackHeaderLine::createV700();
        return $this;
    }

    /**
     * Fügt eine Datenzeile hinzu.
     */
    public function addDataLine(DataLine $dataLine): self {
        $this->dataLines[] = $dataLine;
        return $this;
    }

    /**
     * Convenience-Methode zum Hinzufügen einer Natural-Buchung (Land-/Forstwirtschaft).
     * 
     * @param string $textschluessel Textschlüssel gem. SKR14 (1-9 Stellen)
     * @param string $art Art der Bewegung (2=Erzeugung, 21=Versetzung, 24=Verfüttert, etc.)
     * @param string $datum Datum im TTMM-Format
     * @param int|null $stueck Anzahl Stück (bei Tieren)
     * @param int|null $gewicht Gewicht (bei anderen Textschlüsseln)
     * @param string|null $beleg Belegnummer
     * @param string|null $text Buchungstext
     * @param string|null $anFuerTextschluessel Ziel-Textschlüssel bei Versetzungen
     */
    public function addNaturalBooking(
        string $textschluessel,
        string $art,
        string $datum,
        ?int $stueck = null,
        ?int $gewicht = null,
        ?string $beleg = null,
        ?string $text = null,
        ?string $anFuerTextschluessel = null
    ): self {
        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        $fieldCount = $this->fieldHeader->countFields();
        $values = array_fill(0, $fieldCount, '');

        $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Textschluessel)] = $textschluessel;
        $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Art)] = $art;
        $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Datum)] = $datum;

        if ($stueck !== null) {
            $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Stueck)] = (string) $stueck;
        }
        if ($gewicht !== null) {
            $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Gewicht)] = (string) $gewicht;
        }
        if ($beleg !== null) {
            $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Beleg)] = $beleg;
        }
        if ($text !== null) {
            $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::Text)] = $text;
        }
        if ($anFuerTextschluessel !== null) {
            $values[$this->fieldHeader->getFieldIndex(NaturalStackHeaderField::AnFuerTextschluessel)] = $anFuerTextschluessel;
        }

        $dataLine = new DataLine($values, $this->delimiter, $this->enclosure);
        return $this->addDataLine($dataLine);
    }

    /**
     * Setzt Berater- und Mandantennummer im MetaHeader.
     */
    public function setClient(int $advisorNumber, int $clientNumber): self {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        $this->metaHeader->set(MetaHeaderField::Beraternummer, $advisorNumber);
        $this->metaHeader->set(MetaHeaderField::Mandantennummer, $clientNumber);

        return $this;
    }

    /**
     * Setzt die Beschreibung.
     */
    public function setDescription(string $description): self {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        $this->metaHeader->set(MetaHeaderField::Bezeichnung, $description);

        return $this;
    }

    /**
     * Erstellt das komplette DATEV-Dokument.
     */
    public function build(): NaturalStack {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        if (empty($this->dataLines)) {
            static::logWarning('NaturalStack ohne Datenzeilen erstellt');
        }

        $this->validate();

        return new NaturalStack(
            $this->metaHeader,
            $this->fieldHeader,
            $this->dataLines
        );
    }

    /**
     * Validiert den Builder-Zustand vor dem Build.
     */
    private function validate(): void {
        if (!$this->metaHeader) {
            throw new RuntimeException('MetaHeader muss gesetzt sein');
        }

        if (!$this->fieldHeader) {
            throw new RuntimeException('FieldHeader muss gesetzt sein');
        }

        $expectedFieldCount = $this->fieldHeader->countFields();
        foreach ($this->dataLines as $index => $dataLine) {
            $actualFieldCount = count($dataLine->getFields());
            if ($actualFieldCount !== $expectedFieldCount) {
                throw new RuntimeException(
                    "Datenzeile $index hat $actualFieldCount Felder, erwartet: $expectedFieldCount"
                );
            }
        }
    }

    /**
     * Erstellt einen Standard-MetaHeader für NaturalStack.
     */
    private function createDefaultMetaHeader(): MetaHeaderLine {
        $definition = new MetaHeaderDefinition();
        $metaHeader = new MetaHeaderLine($definition);

        $metaHeader->set(
            MetaHeaderField::ErzeugtAm,
            (new DateTimeImmutable())->format('YmdHis') . '000'
        );

        return $metaHeader;
    }

    /**
     * Liefert Statistiken über den aktuellen Builder-Zustand.
     */
    public function getStats(): array {
        return [
            'metaHeader_set' => $this->metaHeader !== null,
            'fieldHeader_set' => $this->fieldHeader !== null,
            'data_count' => count($this->dataLines),
            'field_count' => $this->fieldHeader?->countFields() ?? 0,
        ];
    }
}
