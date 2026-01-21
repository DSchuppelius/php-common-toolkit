<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Cell.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XLSX;

/**
 * Repräsentiert eine einzelne Zelle in einer XLSX-Zeile.
 */
class Cell {
    protected mixed $value;
    protected ?string $type;
    protected ?string $format;

    /**
     * @param mixed       $value  Der Zellwert
     * @param string|null $type   Der Zelltyp (s=string, n=number, b=boolean, d=date, inlineStr)
     * @param string|null $format Das Zahlenformat (z.B. für Datum/Währung)
     */
    public function __construct(mixed $value, ?string $type = null, ?string $format = null) {
        $this->value  = $value;
        $this->type   = $type;
        $this->format = $format;
    }

    /**
     * Gibt den Zellwert zurück.
     */
    public function getValue(): mixed {
        return $this->value;
    }

    /**
     * Gibt den Zellwert als String zurück.
     */
    public function getStringValue(): string {
        if ($this->value === null) {
            return '';
        }
        if (is_bool($this->value)) {
            return $this->value ? '1' : '0';
        }
        return (string) $this->value;
    }

    /**
     * Gibt den Zelltyp zurück.
     */
    public function getType(): ?string {
        return $this->type;
    }

    /**
     * Gibt das Zahlenformat zurück.
     */
    public function getFormat(): ?string {
        return $this->format;
    }

    /**
     * Prüft ob die Zelle leer ist.
     */
    public function isEmpty(): bool {
        return $this->value === null || $this->value === '';
    }

    /**
     * Prüft ob die Zelle einen numerischen Wert enthält.
     */
    public function isNumeric(): bool {
        return $this->type === 'n' || (is_numeric($this->value) && $this->type !== 's');
    }

    /**
     * Prüft ob die Zelle einen String-Wert enthält.
     */
    public function isString(): bool {
        return $this->type === 's' || $this->type === 'inlineStr';
    }

    /**
     * Prüft ob die Zelle einen Boolean-Wert enthält.
     */
    public function isBoolean(): bool {
        return $this->type === 'b';
    }

    /**
     * Prüft ob die Zelle einen Datumswert enthält.
     */
    public function isDate(): bool {
        return $this->type === 'd';
    }
}
