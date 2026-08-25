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
use CommonToolkit\Helper\Data\CSV\StringHelper as CSVStringHelper;
use CommonToolkit\Helper\Data\{DateHelper, NumberHelper, StringHelper};
use DateTimeImmutable;
use ERRORToolkit\Traits\ErrorLog;

class FieldAbstract implements FieldInterface {
    use ErrorLog;

    private mixed $typedValue;
    private bool $quoted = false;
    private int $enclosureRepeat = 0;
    private ?string $raw;
    private ?string $originalFormat = null;
    private CountryCode $country;

    /** @var string Leading whitespace für unquoted Fields (für Round-Trip-Erhaltung) */
    private string $leadingWhitespace = '';
    /** @var string Trailing whitespace für unquoted Fields (für Round-Trip-Erhaltung) */
    private string $trailingWhitespace = '';
    /** @var int Anzahl innerer Leerzeichen bei quoted leeren Feldern (für Round-Trip-Erhaltung) */
    private int $innerPadding = 0;

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
     */
    private function analyze(string $raw, string $enclosure): void {
        $trimmed = trim($raw);

        // Einzeichen-Enclosure (der Regelfall): Quote-Laeufe per String-Funktionen
        // bestimmen statt mit vier regulaeren Ausdruecken je Feld. Ein CSV-Import
        // erzeugt Millionen Felder; die Regex-Variante kostete davon den Loewenanteil.
        // Mehrzeichen- oder leeres Enclosure nimmt weiter den Regex-Weg.
        if (strlen($enclosure) === 1) {
            $length = strlen($trimmed);
            $startRun = strspn($trimmed, $enclosure);

            // Frühzeitiger Sonderfall: reines Quote-Feld
            // Beispiele: "", """", """""", usw.
            if ($length > 0 && $startRun === $length) {
                $this->quoted = true;
                $this->typedValue = '';
                $this->enclosureRepeat = intdiv($length, 2);
                return;
            }

            $endRun = $length - strlen(rtrim($trimmed, $enclosure));

            if ($startRun > 0 && $endRun > 0) {
                $this->quoted = true;
                // Bei quoted Fields: Whitespace außerhalb der Quotes ignorieren
                $inner = substr($trimmed, $startRun, $length - $startRun - $endRun);

                // Leeres Feld mit symmetrischen Quotes → intdiv
                // Innere Leerzeichen zählen für Round-Trip-Erhaltung
                if (trim($inner) === '' && $startRun === $endRun) {
                    $this->enclosureRepeat = intdiv($startRun, 2);
                    $this->innerPadding = strlen($inner);
                    $this->typedValue = '';
                    return;
                }

                $this->enclosureRepeat = min($startRun, $endRun);
                $this->typedValue = self::balanceQuoteRuns($inner, $startRun, $endRun, $enclosure);
                return;
            }

            $this->analyzeUnquoted($raw, $trimmed);
            return;
        }

        $enc = preg_quote($enclosure, '/');

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
            // Bei quoted Fields: Whitespace außerhalb der Quotes ignorieren

            $startRun = strlen($matches[1]);
            $endRun = 0;
            if (preg_match('/(' . $enc . '+)$/', $trimmed, $endMatch)) {
                $endRun = strlen($endMatch[1]);
            }

            // Leeres Feld mit symmetrischen Quotes → intdiv
            // Innere Leerzeichen zählen für Round-Trip-Erhaltung
            if (trim($matches[2]) === '' && $startRun === $endRun) {
                $this->enclosureRepeat = intdiv($startRun, 2);
                $this->innerPadding = strlen($matches[2]);
                $this->typedValue = '';
                return;
            }

            $this->enclosureRepeat = min($startRun, $endRun);
            $this->typedValue = self::balanceQuoteRuns($matches[2], $startRun, $endRun, $enclosure);
            return;
        }

        $this->analyzeUnquoted($raw, $trimmed);
    }

    /**
     * Gleicht asymmetrische Quote-Laeufe aus: der Ueberschuss der laengeren
     * Seite gehoert zum Inhalt. Quoted Fields bleiben als Literal-String
     * erhalten; getValue() liefert den inneren Wert unveraendert zurueck.
     */
    private static function balanceQuoteRuns(string $inner, int $startRun, int $endRun, string $enclosure): string {
        if ($startRun > $endRun) {
            return str_repeat($enclosure, $startRun - $endRun) . $inner;
        }
        if ($endRun > $startRun) {
            return $inner . str_repeat($enclosure, $endRun - $startRun);
        }

        return $inner;
    }

    /**
     * Unquoted Field: Whitespace für Round-Trip erhalten, Wert typisieren.
     */
    private function analyzeUnquoted(string $raw, string $trimmed): void {
        $this->quoted = false;
        $this->enclosureRepeat = 0;

        // Sonderfall: Feld besteht nur aus Whitespace — alles als
        // leadingWhitespace behandeln, sonst verdoppelte es sich beim Rekonstruieren.
        if ($trimmed === '') {
            $this->leadingWhitespace = $raw;
            $this->trailingWhitespace = '';
            $this->typedValue = '';
            return;
        }

        // Leading/Trailing Whitespace wie PCRE \s (Leerzeichen, \t, \n, \v, \f, \r —
        // anders als trim() ohne \0) extrahieren und speichern
        $whitespace = " \t\n\x0B\x0C\r";
        $this->leadingWhitespace = substr($raw, 0, strlen($raw) - strlen(ltrim($raw, $whitespace)));
        $this->trailingWhitespace = substr($raw, strlen(rtrim($raw, $whitespace)));

        $this->analyzeUnquotedValue($trimmed);
    }

    /**
     * Analysiert einen unquoted Wert und setzt typedValue sowie originalFormat.
     *
     * @param string $value Der zu analysierende Wert.
     */
    private function analyzeUnquotedValue(string $value): void {
        $this->typedValue = StringHelper::parseToTypedValue($value, $this->country);

        // Original-Format speichern für alle typisierten Werte (DateTime, Float, etc.)
        if ($this->typedValue instanceof DateTimeImmutable) {
            $this->originalFormat = DateHelper::detectDateTimeFormat($value, $this->country);
        } elseif (is_float($this->typedValue)) {
            // Excel-Präfix entfernen für korrekte Format-Erkennung
            $cleanedValue = CSVStringHelper::stripExcelTextPrefix($value);
            $detectedFormat = NumberHelper::detectNumberFormat($cleanedValue);
            if ($detectedFormat !== null) {
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
     * Prüft, ob das Feld null ist.
     */
    public function isNull(): bool {
        return $this->typedValue === null;
    }

    /**
     * Prüft, ob das Feld leer oder nur Whitespace enthält.
     */
    public function isBlank(): bool {
        if ($this->typedValue === null || $this->typedValue === '') {
            return true;
        }
        if (is_string($this->typedValue)) {
            return trim($this->typedValue) === '';
        }
        return false;
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
     *
     * Floats werden im Original-Format ausgegeben, wenn erkannt.
     * Falls kein Original-Format bekannt ist, wird das Country-Format verwendet.
     * Dies ermöglicht Round-Trip-Erhaltung bei CSV-Verarbeitung.
     */
    public function getValue(): string {
        if ($this->typedValue instanceof DateTimeImmutable) {
            // Verwende das ursprüngliche Format wenn verfügbar
            if ($this->originalFormat) {
                return $this->typedValue->format($this->originalFormat);
            }
            return $this->typedValue->format('Y-m-d H:i:s');
        } elseif (is_float($this->typedValue)) {
            // Dezimalstellen und Dezimaltrenner aus dem Originalformat ableiten
            $decimalSeparator = null;
            if ($this->originalFormat !== null && preg_match('/([.,])(0+)$/', $this->originalFormat, $m)) {
                $decimalSeparator = $m[1];
                $decimals = strlen($m[2]);
            } else {
                // Kein Originalformat → tatsächliche Dezimalstellen des Float-Werts nutzen
                // sprintf('%.10f') statt number_format(14) um Float-Precision-Artefakte zu vermeiden
                $str = rtrim(rtrim(sprintf('%.10f', $this->typedValue), '0'), '.');
                $dotPos = strpos($str, '.');
                $decimals = $dotPos !== false ? strlen($str) - $dotPos - 1 : 0;
            }

            // Original-Dezimalformat beibehalten für Round-Trip-Erhaltung
            // Der Dezimaltrenner ist der LETZTE Trenner vor den Dezimalstellen
            if ($decimalSeparator === '.') {
                return NumberHelper::toUSFormat($this->typedValue, $decimals);
            }
            if ($decimalSeparator === ',') {
                return NumberHelper::toGermanFormat($this->typedValue, $decimals);
            }

            // Fallback: Country-Format verwenden
            if ($this->country === CountryCode::Germany) {
                return NumberHelper::toGermanFormat($this->typedValue, $decimals);
            }
            return NumberHelper::toUSFormat($this->typedValue, $decimals);
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
     *
     * @param string|null $enclosure Das Enclosure-Zeichen (Standard: ")
     * @param bool $trimmed Wenn true, wird Whitespace entfernt; wenn false, bleibt er für Round-Trip erhalten
     * @return string Der formatierte Wert
     */
    public function toString(?string $enclosure = null, bool $trimmed = false): string {
        $enclosure = $enclosure ?? self::DEFAULT_ENCLOSURE;
        $value = $this->getValue();

        if ($this->quoted) {
            $quoteLevel = max(1, $this->enclosureRepeat);
            $enc = str_repeat($enclosure, $quoteLevel);

            // Innere Leerzeichen für Round-Trip wiederherstellen
            $innerValue = $value;
            if ($this->innerPadding > 0 && $value === '') {
                $innerValue = str_repeat(' ', $this->innerPadding);
            }

            self::logWarningIf(str_contains($innerValue, $enclosure), 'Falsche CSV-Syntax: Value enthält Enclosure: "' . $innerValue . '"');
            return $enc . $innerValue . $enc;
        }

        // Unquoted: Whitespace nur bei Round-Trip (trimmed=false) erhalten
        return $trimmed ? $value : $this->leadingWhitespace . $value . $this->trailingWhitespace;
    }

    /**
     * Gibt den Wert des Feldes als String zurück.
     */
    public function __toString(): string {
        return $this->toString();
    }
}
