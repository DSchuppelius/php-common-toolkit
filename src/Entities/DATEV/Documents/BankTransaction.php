<?php
/*
 * Created on   : Mon Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BankTransaction.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\{HeaderField, DataLine, Document as CSVDocument, ColumnWidthConfig};
use CommonToolkit\Entities\DATEV\Header\ASCII\BankTransactionHeaderLine;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\HeaderFields\ASCII\BankTransactionHeaderField;
use RuntimeException;

/**
 * ASCII-Weiterverarbeitungsdokument für DATEV-Banktransaktionen.
 * 
 * Eigenständiges Dokument-Format OHNE MetaHeader und DATEV-Kategorien.
 * Verwendet das einfache CSV-Document als Basis, nicht das DATEV-Document.
 * Diese ASCII-Dateien sind unabhängig vom Standard DATEV V700-System.
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus BankTransactionHeaderField::getMaxLength() angewendet.
 * 
 * @package CommonToolkit\Entities\DATEV\Documents
 */
final class BankTransaction extends CSVDocument {

    public function __construct(array $rows = [], string $delimiter = ';', string $enclosure = '"', ?ColumnWidthConfig $columnWidthConfig = null) {
        // ASCII-Weiterverarbeitungsdateien haben keinen Header, aber wir erstellen einen internen aus der Definition
        $internalHeader = $this->createInternalHeader($delimiter, $enclosure);

        // Falls keine ColumnWidthConfig übergeben wurde, erstelle eine basierend auf DATEV-Spezifikation
        $columnWidthConfig ??= self::createDatevColumnWidthConfig();

        parent::__construct($internalHeader, $rows, $delimiter, $enclosure, $columnWidthConfig);

        // ASCII-Weiterverarbeitungsdateien werden OHNE Header exportiert
        $this->exportWithHeader = false;
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus BankTransactionHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (BankTransactionHeaderField::ordered() as $index => $field) {
            $maxLength = $field->getMaxLength();
            if ($maxLength !== null) {
                // Verwende Index-basierte Spaltenbreiten (0-basiert)
                $config->setColumnWidth($index, $maxLength);
            }
        }

        return $config;
    }

    /**
     * Erstellt einen internen Header aus der HeaderDefinition für interne Zwecke.
     */
    private function createInternalHeader(string $delimiter, string $enclosure): BankTransactionHeaderLine {
        $headerFields = [];

        // Nutze die geordneten Felder aus dem BankTransactionHeaderField Enum
        foreach (BankTransactionHeaderField::ordered() as $field) {
            $headerFields[] = new HeaderField($field->value, $enclosure);
        }

        return new BankTransactionHeaderLine($headerFields, $delimiter, $enclosure);
    }

    /**
     * Überschreibt getHeader() um den korrekten BankTransactionHeaderLine-Typ zurückzugeben.
     */
    public function getHeader(): ?BankTransactionHeaderLine {
        return $this->header instanceof BankTransactionHeaderLine ? $this->header : null;
    }

    /**
     * Gibt den Format-Typ zurück.
     */
    public function getFormatType(): string {
        return 'ASCII-Weiterverarbeitungsdatei';
    }

    /**
     * Validiert das ASCII-BankTransaction-Format.
     */
    public function validate(): void {
        $this->validateBankTransactionFormat();
    }

    /**
     * Validiert das spezifische BankTransaction-Format.
     * ASCII-Weiterverarbeitungsdateien haben keinen Header, nur Datenzeilen.
     * 
     * @throws RuntimeException
     */
    private function validateBankTransactionFormat(): void {
        // ASCII-Weiterverarbeitungsdateien haben keinen Header
        // Validiere alle Datenzeilen gegen die Definition
        foreach ($this->rows as $index => $row) {
            $this->validateDataRow($row, $index);
        }
    }

    /**
     * Validiert eine einzelne Datenzeile.
     * 
     * @param DataLine $row
     * @param int $index
     * @throws RuntimeException
     */
    private function validateDataRow(DataLine $row, int $index): void {
        $fieldCount = $row->countFields();
        $expectedCount = $this->header->countFields();

        if ($fieldCount !== $expectedCount) {
            throw new RuntimeException(
                sprintf(
                    'Zeile %d hat ungültige Feldanzahl. Erwartet: %d, Erhalten: %d',
                    $index + 1,
                    $expectedCount,
                    $fieldCount
                )
            );
        }

        // Vereinfachte Validierung - komplexe Feldvalidierung kann später hinzugefügt werden
    }

    /**
     * Prüft, ob das Dokument ASCII-Weiterverarbeitungsformat ist.
     * ASCII-Weiterverarbeitungsdateien haben keinen Header im Export.
     */
    public function isAsciiProcessingFormat(): bool {
        return $this->header instanceof BankTransactionHeaderLine;
    }

    /**
     * Gibt das Dokument als assoziatives Array zurück mit ASCII-spezifischen Metadaten.
     */
    public function toAssoc(): array {
        $rows = parent::toAssoc();

        return [
            'meta' => [
                'format' => 'ASCII-Weiterverarbeitung',
                'formatType' => $this->getFormatType(),
                'columns' => $this->header->countFields(),
                'rows' => count($rows),
                'hasMetaHeader' => false,
            ],
            'data' => $rows,
        ];
    }

    // ==== Banking-spezifische Methoden ====

    /**
     * Prüft, ob das Dokument gültige Bankdaten enthält.
     */
    public function hasValidBankData(): bool {
        foreach ($this->rows as $row) {
            $fields = $row->getFields();
            if (count($fields) < 11) continue;

            $blz = trim($fields[0]->getValue());
            $account = trim($fields[1]->getValue());

            if (!empty($blz) && !empty($account)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt Kontoinhaber-Bankdaten für eine bestimmte Zeile zurück.
     */
    public function getAccountHolderBankData(int $rowIndex): ?array {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (count($fields) < 2) {
            return null;
        }

        return [
            'blz_bic' => trim($fields[0]->getValue()),
            'account_number' => trim($fields[1]->getValue()),
        ];
    }

    /**
     * Gibt Zahler-Bankdaten für eine bestimmte Zeile zurück.
     */
    public function getPayerBankData(int $rowIndex): ?array {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (count($fields) < 11) {
            return null;
        }

        return [
            'name1' => trim($fields[7]->getValue()),
            'name2' => trim($fields[8]->getValue()),
            'blz_bic' => trim($fields[9]->getValue()),
            'account_number' => trim($fields[10]->getValue()),
        ];
    }

    /**
     * Gibt Transaktionsdaten für eine bestimmte Zeile zurück.
     */
    public function getTransactionData(int $rowIndex): ?array {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (count($fields) < 17) {
            return null;
        }

        // Für 34-Feld Format: 
        // - Auszugsnummer ist Feld 3 (Index 2)
        // - Buchungsdatum ist Feld 6 (Index 5)  
        // - Valutadatum ist Feld 5 (Index 4)
        // - Betrag ist Feld 7 (Index 6)
        // - Währung ist bei Position 17 (Index 16) falls vorhanden

        $currency = 'EUR'; // Default
        if (count($fields) > 16) {
            $currencyField = trim($fields[16]->getValue());
            if (!empty($currencyField)) {
                $currency = $currencyField;
            }
        }

        return [
            'statement_number' => trim($fields[2]->getValue()),
            'booking_date' => trim($fields[5]->getValue()),
            'valuta_date' => trim($fields[4]->getValue()),
            'amount' => trim($fields[6]->getValue()),
            'currency' => $currency,
        ];
    }

    /**
     * Gibt Verwendungszwecke für eine bestimmte Zeile zurück.
     */
    public function getUsagePurposes(int $rowIndex): array {
        if (!isset($this->rows[$rowIndex])) {
            return [];
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (count($fields) < 14) {
            return [];
        }

        $purposes = [];
        for ($i = 11; $i <= 13; $i++) {
            $purpose = trim($fields[$i]->getValue());
            if (!empty($purpose)) {
                $purposes[] = $purpose;
            }
        }

        return $purposes;
    }

    /**
     * Gibt eine Zusammenfassung aller Transaktionen zurück.
     */
    public function getTransactionSummary(): array {
        $totalAmount = 0.0;
        $currencies = ['EUR' => 0];
        $dates = [];
        $totalTransactions = count($this->rows);

        foreach ($this->rows as $row) {
            $fields = $row->getFields();
            if (count($fields) < 7) continue;

            // Betrag summieren
            $amount = (float) trim($fields[6]->getValue());
            $totalAmount += $amount;

            // Datum sammeln
            if (count($fields) >= 6) {
                $date = trim($fields[5]->getValue());
                if (!empty($date)) {
                    $dates[] = $date;
                }
            }
        }

        sort($dates);

        return [
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'currencies' => $currencies,
            'date_range' => [
                'from' => $dates[0] ?? '',
                'to' => end($dates) ?: '',
            ],
        ];
    }

    /**
     * Extrahiert zusätzliche Beträge aus den erweiterten Feldern (25-34).
     * Diese Felder enthalten oft Ursprungsbeträge in anderen Währungen,
     * Umrechnungsbeträge und Gebühren.
     *
     * @param int $rowIndex Index der Zeile (0-basiert)
     * @return array<string, string> Zusätzliche Beträge mit Währungen
     */
    public function getAdditionalAmounts(int $rowIndex): array {
        $row = $this->getRow($rowIndex);
        if ($row === null) {
            return [];
        }

        $fields = $row->getFields();
        $additionalAmounts = [];

        // Felder 26-34 für zusätzliche Beträge (Index 25-33)
        // Feld 27 (Index 26): Ursprungsbetrag
        // Feld 28 (Index 27): Ursprungswährung
        // Feld 29 (Index 28): Äquivalentbetrag
        // Feld 30 (Index 29): Äquivalentwährung
        // Feld 31 (Index 30): Gebührenbetrag
        // Feld 32 (Index 31): Gebührenwährung

        if (count($fields) >= 27) {
            $additionalAmounts['original_amount'] = trim($fields[26]->getValue());
        }
        if (count($fields) >= 28) {
            $additionalAmounts['original_currency'] = trim($fields[27]->getValue());
        }
        if (count($fields) >= 29) {
            $additionalAmounts['equivalent_amount'] = trim($fields[28]->getValue());
        }
        if (count($fields) >= 30) {
            $additionalAmounts['equivalent_currency'] = trim($fields[29]->getValue());
        }
        if (count($fields) >= 31) {
            $additionalAmounts['fee_amount'] = trim($fields[30]->getValue());
        }
        if (count($fields) >= 32) {
            $additionalAmounts['fee_currency'] = trim($fields[31]->getValue());
        }

        return $additionalAmounts;
    }
}
