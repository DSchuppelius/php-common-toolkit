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

    public function build(): CSVDocument {
        return new CSVDocument(
            $this->header,
            $this->rows,
            $this->delimiter,
            $this->enclosure
        );
    }
}
