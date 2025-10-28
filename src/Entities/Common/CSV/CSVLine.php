<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Entities\Common\CSV;

use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

class CSVLine {
    use ErrorLog;
    public const DEFAULT_DELIMITER = ',';

    /** @var CSVField[] */
    private array $fields = [];
    private string $delimiter;
    private string $enclosure;

    public function __construct(array $fields, string $delimiter = self::DEFAULT_DELIMITER, string $enclosure = CSVField::DEFAULT_ENCLOSURE) {
        $this->fields    = $fields;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    public static function fromString(
        string $line,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = CSVField::DEFAULT_ENCLOSURE
    ): self {
        if ($delimiter === '') {
            self::logError('CSV delimiter darf nicht leer sein');
            throw new RuntimeException('CSV delimiter darf nicht leer sein');
        }

        $fields = [];
        $current = '';
        $inQuotes = false;
        $quoteRun = 0;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            $next = $line[$i + 1] ?? '';
            $prev = $i > 0 ? $line[$i - 1] : '';

            $current .= $char;

            // --- Quote-Start / -End Erkennung ---
            if ($char === $enclosure) {
                $quoteRun++;

                // Start eines Quoted-Felds → wenn nicht inQuotes und davor Delimiter oder Zeilenanfang
                if (!$inQuotes && ($prev === '' || $prev === $delimiter)) {
                    $inQuotes = true;
                    $quoteRun = 1;
                    continue;
                }

                // Quote-Ende → wenn inQuotes und nächstes Zeichen ist Delimiter oder Zeilenende
                if ($inQuotes && ($next === $delimiter || $next === '' || $next === "\r" || $next === "\n")) {
                    $inQuotes = false;
                    $quoteRun = 0;
                    continue;
                }
            }

            // --- Ungültiges Quote mitten im unquoted Feld ---
            if (!$inQuotes && $char === $enclosure && ($prev !== $delimiter && $prev !== '')) {
                $message = sprintf('Ungültige CSV-Zeile – Quote in unquoted Feld bei Index %d (%s)', $i, substr($line, max(0, $i - 10), 20));
                self::logError($message);
                throw new RuntimeException($message);
            }

            // --- Feldabschluss bei Delimiter außerhalb Quotes ---
            if ($char === $delimiter && !$inQuotes) {
                $current = substr($current, 0, -1); // Delimiter entfernen
                $fields[] = new CSVField($current, $enclosure);
                $current = '';
                continue;
            }

            if (str_contains($current, $delimiter . $enclosure)) {
                self::logError('Ungültige CSV-Zeile – Delimiter nach Quote-Ende ohne neues Feld');
                throw new RuntimeException('Ungültige CSV-Zeile – Delimiter nach Quote-Ende ohne neues Feld');
            }
        }

        // --- Ungültig, wenn am Ende noch inQuotes ---
        if ($inQuotes) {
            self::logError('Ungültige CSV-Zeile – Feld nicht geschlossen (fehlendes Enclosure am Ende)');
            throw new RuntimeException('Ungültige CSV-Zeile – Feld nicht geschlossen (fehlendes Enclosure am Ende)');
        }

        // letztes Feld hinzufügen
        if ($current !== '' || str_ends_with($line, $delimiter)) {
            $fields[] = new CSVField($current, $enclosure);
        }

        return new self($fields, $delimiter, $enclosure);
    }


    // ---------------- Getter ----------------

    /**
     * @return CSVField[]
     */
    public function getFields(): array {
        return $this->fields;
    }

    public function getField(int $index): ?CSVField {
        return $this->fields[$index] ?? null;
    }

    public function countFields(): int {
        return count($this->fields);
    }

    public function countQuotedFields(): int {
        return count(array_filter($this->fields, fn(CSVField $f) => $f->isQuoted()));
    }

    public function getDelimiter(): string {
        return $this->delimiter;
    }

    public function getEnclosure(): string {
        return $this->enclosure;
    }

    // ---------------- CSV-spezifische Logik ----------------

    /**
     * Liefert [ 'strict' => int, 'non_strict' => int ] der Enclosure-Wiederholungen.
     */
    public function getEnclosureRepeatRange(bool $includeUnquoted = false): array {
        $repeats = array_map(fn(CSVField $f) => $f->getEnclosureRepeat(), $this->fields);

        // Wenn Unquoted-Felder NICHT berücksichtigt werden sollen
        $filtered = $includeUnquoted ? $repeats : array_filter($repeats, fn($v) => $v > 0);

        $strict     = 0;
        $non_strict = 0;

        if (!empty($filtered)) {
            $strict     = min($filtered);
            $non_strict = max($filtered);
        }

        $this->logDebug(sprintf('Enclosure Repeat Range ermittelt (includeUnquoted=%s): strict=%d, non_strict=%d', $includeUnquoted ? 'true' : 'false', $strict, $non_strict));

        return [$strict, $non_strict];
    }

    /**
     * Baut die Zeile exakt wieder zusammen, basierend auf den CSVField-Objekten.
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null): string {
        $delimiter = $delimiter ?? $this->delimiter;
        $enclosure = $enclosure ?? $this->enclosure;

        $parts = array_map(fn(CSVField $f) => $f->toString($enclosure), $this->fields);
        return implode($delimiter, $parts);
    }

    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Vergleicht zwei CSVLine-Objekte feldweise.
     */
    public function equals(CSVLine $other): bool {
        if ($this->delimiter !== $other->getDelimiter() || $this->enclosure !== $other->getEnclosure()) {
            return false;
        }

        if ($this->countFields() !== $other->countFields()) {
            return false;
        }

        foreach ($this->fields as $i => $field) {
            $otherField = $other->getField($i);
            if (!$otherField || $field->getValue() !== $otherField->getValue()) {
                return false;
            }
        }

        return true;
    }
}