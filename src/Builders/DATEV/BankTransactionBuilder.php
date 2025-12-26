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
use CommonToolkit\Entities\Common\CSV\DataLine;
use CommonToolkit\Entities\DATEV\Documents\BankTransaction;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;

/**
 * Builder für DATEV ASCII-Weiterverarbeitungsdokumente mit automatischer ColumnWidthConfig.
 *
 * Dieser Builder konfiguriert automatisch die DATEV-konformen Feldlängen und Quoting.
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
     * Fügt eine BankTransaction-Zeile mit korrektem DATEV-Quoting hinzu.
     * 
     * @param array<string, string> $values Assoziatives Array mit Feldwerten (Schlüssel: HeaderField->value oder HeaderField->name)
     * @return self
     */
    public function addTransaction(array $values): self {
        $rawFields = [];

        foreach (BankTransactionHeaderField::ordered() as $field) {
            // Wert aus Values holen (über value oder name)
            $value = $values[$field->value] ?? $values[$field->name] ?? '';

            // Korrektes Quoting entsprechend DATEV-Spezifikation anwenden
            if ($field->isQuoted()) {
                // Alphanumerische Felder: Mit Anführungszeichen
                $rawFields[] = $this->enclosure . $value . $this->enclosure;
            } else {
                // Numerische/Datums-Felder: Ohne Anführungszeichen
                $rawFields[] = $value;
            }
        }

        $dataLine = DataLine::fromString(
            implode($this->delimiter, $rawFields),
            $this->delimiter,
            $this->enclosure
        );

        return $this->addRow($dataLine);
    }

    /**
     * Fügt mehrere BankTransaction-Zeilen hinzu.
     * 
     * @param array<int, array<string, string>> $transactions Array von Transaktionen
     * @return self
     */
    public function addTransactions(array $transactions): self {
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }
        return $this;
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
