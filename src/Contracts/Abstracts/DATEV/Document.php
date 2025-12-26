<?php
/*
 * Created on   : Wed Nov 05 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\DATEV;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\Document as CSVDocument;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Entities\DATEV\{DataLine, MetaHeaderLine};
use CommonToolkit\Enums\{CreditDebit, CurrencyCode, CountryCode};
use InvalidArgumentException;
use RuntimeException;

abstract class Document extends CSVDocument {
    public const DEFAULT_DELIMITER = ';';

    private ?MetaHeaderLine $metaHeader = null;

    /** @param DataLine[] $rows */
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = [], ?ColumnWidthConfig $columnWidthConfig = null) {
        // Falls keine ColumnWidthConfig übergeben wurde, erstelle eine basierend auf DATEV-Spezifikation
        $columnWidthConfig ??= static::createDatevColumnWidthConfig();

        parent::__construct($header, $rows, ';', '"', $columnWidthConfig);
        $this->metaHeader  = $metaHeader;
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Muss von abgeleiteten Klassen überschrieben werden, um die spezifischen Feldbreiten zu definieren.
     * 
     * @return ColumnWidthConfig|null
     */
    public static function createDatevColumnWidthConfig(): ?ColumnWidthConfig {
        // Standardimplementierung gibt null zurück
        // Abgeleitete Klassen sollten dies überschreiben
        return null;
    }

    public function getMetaHeader(): ?MetaHeaderLine {
        return $this->metaHeader;
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
                'formatType' => $this->getFormatType(),
                'metaHeader' => $this->metaHeader?->toAssoc(),
                'columns' => $this->header?->countFields() ?? 0,
                'rows' => count($rows),
            ],
            'data' => $rows,
        ];
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     * Muss von abgeleiteten Klassen implementiert werden.
     */
    abstract public function getFormatType(): string;

    /**
     * Wandelt das gesamte DATEV-Dokument in eine rohe CSV-Zeichenkette um.
     * Überschreibt die Parent-Methode, um den MetaHeader mit einzubeziehen.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @param int|null $enclosureRepeat Die Anzahl der Enclosure-Wiederholungen.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null, ?int $enclosureRepeat = null): string {
        $delimiter ??= $this->delimiter;
        $enclosure ??= $this->enclosure;

        $lines = [];

        // MetaHeader als erste Zeile
        if ($this->metaHeader) {
            $lines[] = $this->metaHeader->toString($delimiter, $enclosure);
        }

        // Parent-Logik für Header und Datenzeilen
        $parentContent = parent::toString($delimiter, $enclosure, $enclosureRepeat);
        if ($parentContent !== '') {
            $lines[] = $parentContent;
        }

        return implode("\n", $lines);
    }

    /**
     * Gibt einen Feldwert als CreditDebit-Enum zurück.
     */
    protected function getCreditDebit(int $rowIndex, int $fieldIndex): ?CreditDebit {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        return match ($cleanValue) {
            'S' => CreditDebit::CREDIT,
            'H' => CreditDebit::DEBIT,
            default => null
        };
    }

    /**
     * Setzt einen Feldwert aus einem CreditDebit-Enum.
     */
    protected function setCreditDebit(int $rowIndex, int $fieldIndex, CreditDebit $creditDebit): void {
        $datevValue = match ($creditDebit) {
            CreditDebit::CREDIT => '"S"',
            CreditDebit::DEBIT => '"H"'
        };
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt einen Feldwert als CurrencyCode-Enum zurück.
     */
    protected function getCurrencyCode(int $rowIndex, int $fieldIndex): ?CurrencyCode {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        try {
            return CurrencyCode::fromCode($cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem CurrencyCode-Enum.
     */
    protected function setCurrencyCode(int $rowIndex, int $fieldIndex, CurrencyCode $currencyCode): void {
        $datevValue = '"' . $currencyCode->value . '"';
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt einen Feldwert als CountryCode-Enum zurück.
     */
    protected function getCountryCode(int $rowIndex, int $fieldIndex): ?CountryCode {
        $value = $this->getFieldValue($rowIndex, $fieldIndex);
        if (!$value) return null;

        $cleanValue = trim($value, '"');
        try {
            return CountryCode::fromStringValue($cleanValue);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Setzt einen Feldwert aus einem CountryCode-Enum.
     */
    protected function setCountryCode(int $rowIndex, int $fieldIndex, CountryCode $countryCode): void {
        $datevValue = '"' . $countryCode->value . '"';
        $this->setFieldValue($rowIndex, $fieldIndex, $datevValue);
    }

    /**
     * Gibt den Rohwert eines Feldes zurück.
     */
    private function getFieldValue(int $rowIndex, int $fieldIndex): ?string {
        if (!isset($this->rows[$rowIndex])) {
            return null;
        }

        $fields = $this->rows[$rowIndex]->getFields();
        if (!isset($fields[$fieldIndex])) {
            return null;
        }

        return $fields[$fieldIndex]->getValue();
    }

    /**
     * Setzt den Wert eines Feldes.
     * TODO: Implementierung für Field-Mutation
     */
    private function setFieldValue(int $rowIndex, int $fieldIndex, string $value): void {
        // Placeholder - Field-Objekte sind immutable
        // Eine vollständige Implementierung würde neue DataLine-Objekte erstellen
        throw new RuntimeException("Field-Mutation noch nicht implementiert");
    }
}
