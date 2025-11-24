<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV;

use CommonToolkit\Entities\Common\CSV\Document as CSVDocument;
use RuntimeException;

final class Document extends CSVDocument {
    private ?MetaHeaderLine $metaHeader = null;

    /** @param DatevDataLine[] $rows */
    public function __construct(
        ?MetaHeaderLine $metaHeader,
        ?HeaderLine $header,
        array $rows = []
    ) {
        parent::__construct($header, $rows, ';', '"');
        $this->metaHeader  = $metaHeader;
    }

    public function getMetaHeader(): ?MetaHeaderLine {
        return $this->metaHeader;
    }

    public function getHeader(): ?HeaderLine {
        return $this->header;
    }

    public function validate(): void {
        if (!$this->metaHeader) {
            throw new RuntimeException('DATEV-Metadatenheader fehlt.');
        }
        if (!$this->header) {
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
                'columns' => $this->header?->countFields() ?? 0,
                'rows' => count($rows),
            ],
            'data' => $rows,
        ];
    }
}
