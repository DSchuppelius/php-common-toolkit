<?php
/*
 * Created on   : Tue Dec 24 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransactionBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;

/**
 * Builder für DATEV ASCII-Weiterverarbeitungsdokumente mit automatischer ColumnWidthConfig.
 *
 * Dieser Builder konfiguriert automatisch die DATEV-konformen Feldlängen.
 *
 * @package CommonToolkit\Builders\DATEV
 */
final class BankTransactionBuilder extends CSVDocumentBuilder {

    public function __construct(string $delimiter = ';', string $enclosure = '"', TruncationStrategy $truncationStrategy = TruncationStrategy::TRUNCATE) {
        $columnWidthConfig = (new ColumnWidthConfig())
            ->setTruncationStrategy($truncationStrategy);

        foreach (BankTransactionHeaderField::cases() as $field) {
            $columnWidthConfig->setColumnWidth($field->value, $field->getMaxLength());
        }

        parent::__construct($delimiter, $enclosure, $columnWidthConfig);
    }

    /**
     * Baut das BankTransaction-Dokument mit DATEV-konformer Konfiguration.
     * 
     * @return BankTransaction
     */
    public function build(): BankTransaction {
        // ASCII-Weiterverarbeitungsdatei wird mit internem Header erstellt
        return new BankTransaction(
            $this->rows,
            $this->delimiter,
            $this->enclosure,
            $this->columnWidthConfig
        );
    }
}