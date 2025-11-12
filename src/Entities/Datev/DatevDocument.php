<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DatevDocument.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Datev;

use CommonToolkit\Entities\Common\CSV\CSVDocument;
use RuntimeException;

final class DatevDocument extends CSVDocument {
    private ?DatevMetaHeaderLine $metaHeader = null;
    private ?DatevFieldHeaderLine $fieldHeader = null;

    /** @param DatevDataLine[] $rows */
    public function __construct(
        ?DatevMetaHeaderLine $metaHeader,
        ?DatevFieldHeaderLine $fieldHeader,
        array $rows = [],
        string $delimiter = ';',
        string $enclosure = '"'
    ) {
        parent::__construct($fieldHeader, $rows, $delimiter, $enclosure);
        $this->metaHeader  = $metaHeader;
        $this->fieldHeader = $fieldHeader;
    }

    public function getMetaHeader(): ?DatevMetaHeaderLine {
        return $this->metaHeader;
    }

    public function getFieldHeader(): ?DatevFieldHeaderLine {
        return $this->fieldHeader;
    }

    public function validate(): void {
        if (!$this->metaHeader) {
            throw new RuntimeException('DATEV-Metadatenheader fehlt.');
        }
        if (!$this->fieldHeader) {
            throw new RuntimeException('DATEV-Feldheader fehlt.');
        }

        $metaValues = array_map(fn($f) => trim($f->getValue(), "\"'"), $this->metaHeader->getFields());
        if ($metaValues[0] !== 'EXTF') {
            throw new RuntimeException('Ungültiger DATEV-Metadatenheader – "EXTF" erwartet.');
        }
    }

    public function toAssoc(): array {
        $rows = parent::toAssoc();

        return [
            'meta' => [
                'format' => 'DATEV',
                'metaHeader' => $this->metaHeader?->toAssoc(),
                'columns' => $this->fieldHeader?->countFields() ?? 0,
                'rows' => count($rows),
            ],
            'data' => $rows,
        ];
    }
}
