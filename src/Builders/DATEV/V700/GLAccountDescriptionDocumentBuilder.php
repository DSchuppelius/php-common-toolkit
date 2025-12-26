<?php
/*
 * Created on   : Thu Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GLAccountDescriptionDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV\V700;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Entities\DATEV\Documents\GLAccountDescription;
use CommonToolkit\Entities\DATEV\Header\V700\MetaHeaderDefinition;
use CommonToolkit\Entities\DATEV\Header\GLAccountDescriptionHeaderLine;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\{MetaHeaderField, GLAccountDescriptionHeaderField};
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use DateTimeImmutable;

/**
 * Builder für DATEV Sachkontenbeschriftungen-Dokumente (V700).
 * Erstellt komplette DATEV-Export-Dateien mit MetaHeader, FieldHeader und Kontenbeschriftungen.
 */
final class GLAccountDescriptionDocumentBuilder extends CSVDocumentBuilder {
    use ErrorLog;

    private ?MetaHeaderLine $metaHeader = null;
    private ?GLAccountDescriptionHeaderLine $fieldHeader = null;
    /** @var DataLine[] */
    private array $dataLines = [];

    public function __construct(string $delimiter = Document::DEFAULT_DELIMITER, string $enclosure = '"') {
        parent::__construct($delimiter, $enclosure);
    }

    /**
     * Setzt den MetaHeader mit Standard-GLAccountDescription-Konfiguration.
     */
    public function setMetaHeader(?MetaHeaderLine $metaHeader = null): self {
        $this->metaHeader = $metaHeader ?? $this->createDefaultMetaHeader();
        return $this;
    }

    /**
     * Setzt den FieldHeader (Spaltenbeschreibungen).
     */
    public function setFieldHeader(?GLAccountDescriptionHeaderLine $fieldHeader = null): self {
        $this->fieldHeader = $fieldHeader ?? GLAccountDescriptionHeaderLine::createV700();
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
     * Convenience-Methode zum Hinzufügen einer Sachkontenbeschriftung.
     */
    public function addGLAccount(
        string $account,
        string $description,
        ?string $longDescription = null
    ): self {
        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        $fieldCount = $this->fieldHeader->countFields();
        $values = array_fill(0, $fieldCount, '');

        $values[$this->fieldHeader->getFieldIndex(GLAccountDescriptionHeaderField::Konto)] = $account;
        $values[$this->fieldHeader->getFieldIndex(GLAccountDescriptionHeaderField::Kontenbeschriftung)] = $description;

        if ($longDescription !== null) {
            $values[$this->fieldHeader->getFieldIndex(GLAccountDescriptionHeaderField::KontenbeschriftungLang)] = $longDescription;
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
    public function build(): GLAccountDescription {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        if (empty($this->dataLines)) {
            static::logWarning('GLAccountDescription ohne Datenzeilen erstellt');
        }

        $this->validate();

        return new GLAccountDescription(
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
     * Erstellt einen Standard-MetaHeader für GLAccountDescription.
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
