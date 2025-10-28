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

use RuntimeException;

class CSVLine {
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
            throw new RuntimeException('CSV delimiter darf nicht leer sein');
        }

        $fields = [];
        $current = '';
        $inQuotes = false;
        $quoteCount = 0;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === $enclosure) {
                $quoteCount++;
                $current .= $char;

                // Prüfe, ob dieser Quote-Runs geschlossen ist
                $next = $line[$i + 1] ?? '';
                if ($next !== $enclosure) {
                    // ungerade Anzahl => Wechsel zwischen inside/outside
                    if ($quoteCount % 2 !== 0) {
                        $inQuotes = !$inQuotes;
                    }
                    $quoteCount = 0;
                }
                continue;
            }

            if ($char === $delimiter && !$inQuotes) {
                $fields[] = new CSVField($current, $enclosure);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        // Letztes Feld hinzufügen
        $fields[] = new CSVField($current, $enclosure);

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
    public function getEnclosureRepeatRange(): array {
        $repeats = array_map(fn(CSVField $f) => $f->getEnclosureRepeat(), $this->fields);
        $positive = array_filter($repeats, fn($v) => $v > 0);

        return [
            'strict'     => empty($positive) ? 0 : min($positive),
            'non_strict' => empty($positive) ? 0 : max($positive),
        ];
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

    /**
     * Liefert Debug-Infos für Unit-Tests.
     */
    public function debug(): array {
        return [
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'repeats'   => $this->getEnclosureRepeatRange(),
            'fields'    => array_map(fn(CSVField $f) => [
                'raw'     => $f->getRaw(),
                'value'   => $f->getValue(),
                'quoted'  => $f->isQuoted(),
                'repeat'  => $f->getEnclosureRepeat(),
            ], $this->fields),
        ];
    }
}