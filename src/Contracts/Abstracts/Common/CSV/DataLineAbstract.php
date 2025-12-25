<?php
/*
 * Created on   : Wed Dec 25 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DataLineAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Abstracts\Common\CSV;

use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSV\HeaderLineInterface;
use CommonToolkit\Contracts\Abstracts\Common\CSV\DataFieldAbstract;
use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderField;

abstract class DataLineAbstract extends LineAbstract {
    protected ?HeaderLineInterface $headerLine = null;

    public function __construct(array $fields, string $delimiter = self::DEFAULT_DELIMITER, string $enclosure = FieldInterface::DEFAULT_ENCLOSURE, ?HeaderLineInterface $headerLine = null) {
        parent::__construct($fields, $delimiter, $enclosure);
        $this->headerLine = $headerLine;

        // Setze HeaderField-Referenzen in DataFields, wenn HeaderLine vorhanden ist
        $this->linkDataFieldsToHeaders();
    }

    /**
     * Setzt die HeaderLine und verknüpft die DataFields.
     */
    public function setHeaderLine(?HeaderLineInterface $headerLine): void {
        $this->headerLine = $headerLine;
        $this->linkDataFieldsToHeaders();
    }

    /**
     * Verknüpft DataFields mit ihren entsprechenden HeaderFields.
     */
    private function linkDataFieldsToHeaders(): void {
        if ($this->headerLine === null) {
            return;
        }

        foreach ($this->fields as $index => $field) {
            if ($field instanceof DataFieldAbstract) {
                $headerField = $this->headerLine->getField($index);
                if ($headerField) {
                    $field->setHeaderField($headerField);
                }
            }
        }
    }

    /**
     * Überschreibt getFieldValue() um Spaltenbreiten-Verarbeitung zu implementieren.
     * Header-Felder werden NICHT gekürzt, nur Daten-Felder.
     * Diese Methode wird von LineAbstract.toString() mit ColumnWidthConfig aufgerufen.
     *
     * @param FieldInterface $field Das Feld
     * @param ColumnWidthConfig|null $columnWidthConfig Spaltenbreiten-Konfiguration
     * @return string Der ggf. gekürzte Feldwert
     */
    protected function getFieldValue(FieldInterface $field, ?ColumnWidthConfig $columnWidthConfig = null): string {
        $value = $field->getValue();

        // Keine Kürzung ohne ColumnWidthConfig
        if (!$columnWidthConfig) {
            return $value;
        }

        // Header-Felder werden niemals gekürzt
        if ($field instanceof HeaderField) {
            return $value;
        }

        // Nur Daten-Felder kürzen
        $columnKey = $this->determineColumnKey($field);
        $width = $columnWidthConfig->getColumnWidth($columnKey);

        if ($width !== null && mb_strlen($value) > $width) {
            return $columnWidthConfig->truncateValue($value, $columnKey);
        }

        return $value;
    }

    /**
     * Ermittelt den Index eines Fields basierend auf seiner Position im Array.
     */
    private function getFieldIndex(FieldInterface $field): int {
        $index = array_search($field, $this->fields, true);
        return $index !== false ? $index : 0;
    }

    /**
     * Bestimmt den Spalten-Key für die ColumnWidthConfig.
     *
     * @param FieldInterface $field Das Feld
     * @return string|int Der Spalten-Key (Spaltenname oder Index)
     */
    private function determineColumnKey(FieldInterface $field): string|int {
        if ($field instanceof HeaderField) {
            // HeaderField: Verwende den Feldwert als Spaltenname
            return $field->getValue();
        }

        if ($field instanceof DataFieldAbstract && $field->hasHeaderField()) {
            // DataField mit HeaderField-Referenz: Verwende Spaltenname direkt
            return $field->getColumnName();
        }

        // Fallback: Ermittle Index über Array-Position
        $index = $this->getFieldIndex($field);
        if ($this->headerLine && $this->headerLine->getField($index)) {
            return $this->headerLine->getField($index)->getValue();
        }

        // Letzter Fallback: Verwende Index
        return $index;
    }
}
