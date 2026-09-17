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

use DateTimeInterface;

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
        $this->value = $value;
        $this->type = $type;
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
     *
     * null => '', bool => '1'/'0', Datum/Zeit => wie {@see toCanonicalString()}
     * (`DateTimeInterface` ist nicht string-castbar; ohne diesen Zweig stürzte
     * z.B. {@see Sheet::getHeaderNames()} über einer Datumszelle in Zeile 1).
     * Zahlen bleiben beim `(string)`-Cast — Floats also unverändert zur
     * bisherigen Ausgabe; für CSV-taugliche Floats {@see toCanonicalString()}.
     */
    public function getStringValue(): string {
        if ($this->value === null) {
            return '';
        }
        if (is_bool($this->value)) {
            return $this->value ? '1' : '0';
        }
        if ($this->value instanceof DateTimeInterface) {
            return self::formatDateTime($this->value);
        }
        return (string) $this->value;
    }

    /**
     * Kanonische String-Fassung für Text-/CSV-Pfade.
     *
     * Ergänzt {@see getStringValue()} um den Float-Fall: Floats kippen beim
     * `(string)`-Cast ab einer gewissen Größe in Exponentialschreibweise und
     * erzeugen in einem CSV-Export unbrauchbare Zellen.
     *
     * Regeln:
     *  - Datum/Zeit: `Y-m-d`, wenn die Uhrzeit exakt 00:00:00 ist, sonst
     *    `Y-m-d H:i:s`. XLSX kennt keinen reinen Datumstyp — ein Datum ohne
     *    Uhrzeit kommt als Mitternacht an, und „2026-08-20 00:00:00" wäre
     *    im Export eine erfundene Genauigkeit.
     *  - Float: feste Notation mit bis zu 10 Nachkommastellen, überflüssige
     *    Nullen (und ein übrig bleibender Punkt) fallen weg.
     *  - alles Übrige: {@see getStringValue()} (null => '', bool => '1'/'0').
     */
    public function toCanonicalString(): string {
        if ($this->value instanceof DateTimeInterface) {
            return self::formatDateTime($this->value);
        }

        if (is_float($this->value)) {
            return rtrim(rtrim(number_format($this->value, 10, '.', ''), '0'), '.');
        }

        return $this->getStringValue();
    }

    /**
     * `Y-m-d` bei exakt Mitternacht, sonst `Y-m-d H:i:s` (siehe {@see toCanonicalString()}).
     */
    private static function formatDateTime(DateTimeInterface $value): string {
        return $value->format('H:i:s') === '00:00:00'
            ? $value->format('Y-m-d')
            : $value->format('Y-m-d H:i:s');
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
