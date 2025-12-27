<?php
/*
 * Created on   : Fri Dec 26 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddressesDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV\V700;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Entities\DATEV\Documents\VariousAddresses;
use CommonToolkit\Entities\DATEV\Header\V700\MetaHeaderDefinition;
use CommonToolkit\Entities\DATEV\Header\VariousAddressesHeaderLine;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\{MetaHeaderField, VariousAddressesHeaderField};
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use DateTimeImmutable;

/**
 * Builder für DATEV Diverse Adressen-Dokumente (V700).
 * Erstellt komplette DATEV-Export-Dateien mit MetaHeader, FieldHeader und Adressdaten.
 */
final class VariousAddressesDocumentBuilder extends CSVDocumentBuilder {
    use ErrorLog;

    private ?MetaHeaderLine $metaHeader = null;
    private ?VariousAddressesHeaderLine $fieldHeader = null;
    /** @var DataLine[] */
    private array $dataLines = [];

    public function __construct(string $delimiter = Document::DEFAULT_DELIMITER, string $enclosure = '"') {
        parent::__construct($delimiter, $enclosure);
    }

    /**
     * Setzt den MetaHeader mit Standard-VariousAddresses-Konfiguration.
     */
    public function setMetaHeader(?MetaHeaderLine $metaHeader = null): self {
        $this->metaHeader = $metaHeader ?? $this->createDefaultMetaHeader();
        return $this;
    }

    /**
     * Setzt den FieldHeader (Spaltenbeschreibungen).
     */
    public function setFieldHeader(?VariousAddressesHeaderLine $fieldHeader = null): self {
        $this->fieldHeader = $fieldHeader ?? VariousAddressesHeaderLine::createV700();
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
     * Convenience-Methode zum Hinzufügen einer Adresse.
     */
    public function addAddress(
        string $addressNumber,
        string $account,
        ?string $name = null,
        ?string $street = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $country = null
    ): self {
        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        $fieldCount = $this->fieldHeader->countFields();
        $values = array_fill(0, $fieldCount, '');

        $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Adressnummer)] = $addressNumber;
        $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Konto)] = $account;

        if ($name !== null) {
            $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::NameUnternehmen)] = $name;
        }
        if ($street !== null) {
            $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Strasse)] = $street;
        }
        if ($postalCode !== null) {
            $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Postleitzahl)] = $postalCode;
        }
        if ($city !== null) {
            $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Ort)] = $city;
        }
        if ($country !== null) {
            $values[$this->fieldHeader->getFieldIndex(VariousAddressesHeaderField::Land)] = $country;
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
    public function build(): VariousAddresses {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        if (empty($this->dataLines)) {
            static::logWarning('VariousAddresses ohne Datenzeilen erstellt');
        }

        $this->validate();

        return new VariousAddresses(
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
     * Erstellt einen Standard-MetaHeader für VariousAddresses.
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
