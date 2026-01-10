<?php
/*
 * Created on   : Tue Oct 28 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FieldAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Abstracts\CSV;

use CommonToolkit\Contracts\Interfaces\CSV\FieldInterface;
use CommonToolkit\Enums\CountryCode;
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\Data\NumberHelper;
use CommonToolkit\Helper\Data\DateHelper;
use DateTimeImmutable;
use ERRORToolkit\Traits\ErrorLog;
use Psr\Log\LogLevel;

class FieldAbstract implements FieldInterface {
    use ErrorLog;

    private mixed $typedValue;
    private bool $quoted = false;
    private int $enclosureRepeat = 0;
    private ?string $raw;
    private ?string $originalFormat = null;
    private CountryCode $country;

    public function __construct(string $raw, string $enclosure = self::DEFAULT_ENCLOSURE, CountryCode $country = CountryCode::Germany) {
        $this->raw = $raw;
        $this->country = $country;

        $this->analyze($raw, $enclosure);
    }

    /**
     * Analysiert das rohe Feld und setzt die Eigenschaften.
     *
     * @param string $raw       Das rohe Feld.
     * @param string $enclosure Das Einschlusszeichen.
     * @return void
     */
    private function analyze(string $raw, string $enclosure): void {
        $enc = preg_quote($enclosure, '/');
        $trimmed = trim($raw);

        // Frühzeitiger Sonderfall: reines Quote-Feld
        // Beispiele: "", """", """""", usw.
        if (preg_match('/^' . $enc . '+$/', $trimmed)) {
            $this->quoted = true;
            $this->typedValue = '';
            $this->enclosureRepeat = intdiv(strlen($trimmed), 2);
            return;
        }

        $matches = [];

        if (preg_match('/^(' . $enc . '+)(.*?)(?:' . $enc . '+)$/s', $trimmed, $matches)) {
            $this->quoted = true;

            $startRun = strlen($matches[1]);
            $endRun = 0;
            if (preg_match('/(' . $enc . '+)$/', $trimmed, $endMatch)) {
                $endRun = strlen($endMatch[1]);
            }

            // Leeres Feld mit symmetrischen Quotes → intdiv
            if (trim($matches[2]) === '' && $startRun === $endRun) {
                $this->enclosureRepeat = intdiv($startRun, 2);
                $this->typedValue = '';
                return;
            }

            $this->enclosureRepeat = min($startRun, $endRun);

            $inner = $matches[2];

            // Asymmetrische Quote-Runs ausgleichen
            if ($startRun > $endRun) {
                $inner = str_repeat($enclosure, $startRun - $endRun) . $inner;
            } elseif ($endRun > $startRun) {
                $inner = $inner . str_repeat($enclosure, $endRun - $startRun);
            }

            $this->typedValue = $inner;
        } else {
            // Unquoted Field
            $this->quoted = false;
            $this->enclosureRepeat = 0;
            $this->analyzeUnquotedValue($trimmed);
        }
    }

    /**
     * Analysiert einen unquoted Wert und setzt typedValue sowie originalFormat.
     *
     * @param string $value Der zu analysierende Wert.
     * @return void
     */
    private function analyzeUnquotedValue(string $value): void {
        $this->typedValue = StringHelper::parseToTypedValue($value, $this->country);

        // Original-Format speichern für alle typisierten Werte (DateTime, Float, etc.)
        if ($this->typedValue instanceof DateTimeImmutable) {
            $this->originalFormat = DateHelper::detectDateTimeFormat($value, $this->country);
        } elseif (is_float($this->typedValue)) {
            // Float-Format erkennen (z.B. deutsche vs. US Schreibweise)
            $detectedFormat = NumberHelper::detectNumberFormat($value);
            if ($detectedFormat !== null) {
                // Format-Template speichern für korrekte Ausgabe
                $this->originalFormat = $detectedFormat;
            }
        }
    }

    /**
     * Prüft, ob das Feld quoted ist.
     */
    public function isQuoted(): bool {
        return $this->quoted;
    }

    /**
     * Prüft, ob das Feld leer ist.
     */
    public function isEmpty(): bool {
        return $this->typedValue === '';
    }

    /**
     * Gibt den typisierten Wert zurück.
     * Für unquoted Fields: int, float, bool, DateTimeImmutable oder string
     * Für quoted Fields: immer string
     */
    public function getTypedValue(): mixed {
        return $this->typedValue;
    }

    /**
     * Gibt zurück, wie oft das Enclosure um den Wert wiederholt wurde.
     */
    public function getEnclosureRepeat(): int {
        return $this->enclosureRepeat;
    }

    /**
     * Setzt, wie oft das Enclosure um den Wert wiederholt wird.
     */
    public function setEnclosureRepeat(int $count): void {
        $this->enclosureRepeat = max(0, $count);
    }

    /**
     * Gibt den Wert als String zurück.
     */
    public function getValue(): string {
        if ($this->typedValue instanceof DateTimeImmutable) {
            // Verwende das ursprüngliche Format wenn verfügbar
            if ($this->originalFormat) {
                return $this->typedValue->format($this->originalFormat);
            }
            return $this->typedValue->format('Y-m-d H:i:s');
        } elseif (is_float($this->typedValue) && $this->originalFormat !== null) {
            // Float mit erkanntem Format-Template formatieren
            return NumberHelper::formatNumberByTemplate($this->typedValue, $this->originalFormat);
        }
        return (string) $this->typedValue;
    }

    /**
     * Prüft, ob der Wert eine gültige Ganzzahl ist.
     */
    public function isInt(): bool {
        return is_int($this->typedValue);
    }

    /**
     * Prüft, ob der Wert eine gültige Fließkommazahl ist.
     */
    public function isFloat(): bool {
        return is_float($this->typedValue) || is_int($this->typedValue);
    }

    /**
     * Prüft, ob der Wert ein gültiger Boolean ist.
     */
    public function isBool(): bool {
        return is_bool($this->typedValue);
    }

    /**
     * Prüft, ob der Wert ein String ist.
     */
    public function isString(): bool {
        return is_string($this->typedValue);
    }

    /**
     * Prüft, ob der Wert ein gültiges Datum/Zeit ist.
     */
    public function isDateTime(?string $format = null): bool {
        if ($this->typedValue instanceof DateTimeImmutable) {
            return true;
        }

        // Custom Format prüfen
        if ($format && is_string($this->typedValue)) {
            return DateTimeImmutable::createFromFormat($format, $this->getValue()) !== false;
        }

        return false;
    }

    /**
     * Setzt den Wert des Feldes neu.
     */
    public function setValue(string $value): void {
        $this->raw = null;
        $this->originalFormat = null;

        if ($this->quoted) {
            $this->typedValue = $value;
        } else {
            $this->analyzeUnquotedValue($value);
        }
    }

    /**
     * Gibt den rohen Wert zurück (vor Analyse).
     */
    public function getRaw(): ?string {
        return $this->raw;
    }

    /**
     * Erstellt eine Kopie des Feldes mit einem neuen String-Wert.
     * Behält alle anderen Eigenschaften (quoted, enclosureRepeat, country) bei.
     * Bei unquoted Fields wird der Wert analysiert (Typ-Erkennung für Datum, Float, etc.).
     *
     * @param string $newValue Der neue Wert für das Feld.
     * @return static Eine neue Instanz mit dem geänderten Wert.
     */
    public function withValue(string $newValue): static {
        $clone = clone $this;
        $clone->raw = null;
        $clone->originalFormat = null;

        if (!$clone->quoted) {
            $clone->analyzeUnquotedValue($newValue);
        } else {
            $clone->typedValue = $newValue;
        }

        return $clone;
    }

    /**
     * Erstellt eine Kopie des Feldes mit einem bereits typisierten Wert.
     * Übernimmt den Wert direkt ohne Analyse.
     * Behält alle anderen Eigenschaften (quoted, enclosureRepeat, country) bei.
     *
     * @param mixed $newValue Der neue typisierte Wert (int, float, DateTimeImmutable, string, etc.).
     * @return static Eine neue Instanz mit dem geänderten Wert.
     */
    public function withTypedValue(mixed $newValue): static {
        $clone = clone $this;
        $clone->raw = null;
        $clone->typedValue = $newValue;
        return $clone;
    }

    /**
     * Erstellt eine Kopie des Feldes mit geänderter Quote-Eigenschaft.
     * Behält alle anderen Eigenschaften (typedValue, enclosureRepeat, country) bei.
     *
     * @param bool $quoted Ob das Feld gequotet sein soll.
     * @return static Eine neue Instanz mit der geänderten Quote-Eigenschaft.
     */
    public function withQuoted(bool $quoted): static {
        $clone = clone $this;
        $clone->quoted = $quoted;

        // Bei Wechsel von quoted zu unquoted: Typ-Analyse durchführen
        if (!$quoted && $this->quoted && is_string($this->typedValue)) {
            $clone->analyzeUnquotedValue($this->typedValue);
        }

        return $clone;
    }

    /**
     * Gibt den Wert als String zurück.
     */
    public function toString(?string $enclosure = null): string {
        $enclosure = $enclosure ?? self::DEFAULT_ENCLOSURE;

        $quoteLevel = max(1, $this->enclosureRepeat);
        $value = $this->getValue();

        if ($this->quoted) {
            $enc = str_repeat($enclosure, $quoteLevel);

            self::logWarningIf(
                str_contains($value, $enclosure),
                'Falsche CSV-Syntax: Value enthält Enclosure: "' . $value . '"'
            );

            return $enc . $value . $enc;
        }

        return $value;
    }

    /**
     * Gibt den Wert des Feldes als String zurück.
     */
    public function __toString(): string {
        return $this->toString();
    }
}
