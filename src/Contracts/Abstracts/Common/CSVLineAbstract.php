<?php
/*
 * Created on   : Wed Oct 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CSVLineAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace CommonToolkit\Contracts\Abstracts\Common;

use CommonToolkit\Contracts\Interfaces\Common\CSVFieldInterface;
use CommonToolkit\Contracts\Interfaces\Common\CSVLineInterface;
use CommonToolkit\Helper\Data\StringHelper\CSVStringHelper;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

abstract class CSVLineAbstract implements CSVLineInterface {
    use ErrorLog;

    /** @var CSVFieldInterface[] */
    protected array $fields = [];
    protected string $delimiter;
    protected string $enclosure;

    public function __construct(array $fields, string $delimiter = self::DEFAULT_DELIMITER, string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE) {
        $this->fields    = array_map(function ($field) use ($enclosure) {
            return $field instanceof CSVFieldInterface
                ? $field
                : static::createField((string)$field, $enclosure);
        }, $fields);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    abstract protected static function createField(string $rawValue, string $enclosure): CSVFieldInterface;

    /**
     * Erstellt eine CSVLine-Instanz aus einer rohen CSV-Zeichenkette.
     *
     * @param string $line      Die rohe CSV-Zeichenkette.
     * @param string $delimiter Die Trennzeichen-Zeichenkette.
     * @param string $enclosure Die Einschlusszeichen-Zeichenkette.
     * @return static
     *
     * @throws RuntimeException
     */
    public static function fromString(string $line, string $delimiter = self::DEFAULT_DELIMITER, string $enclosure = CSVFieldInterface::DEFAULT_ENCLOSURE): static {
        if ($delimiter === '') {
            static::logError('CSV delimiter darf nicht leer sein');
            throw new RuntimeException('CSV delimiter darf nicht leer sein');
        }

        $fields = array_map(
            fn(string $raw) => static::createField($raw, $enclosure),
            CSVStringHelper::parseLineToFields($line, $delimiter, $enclosure)
        );

        return new static($fields, $delimiter, $enclosure);
    }

    /**
     * @return CSVFieldInterface[]
     */
    public function getFields(): array {
        return $this->fields;
    }

    public function getField(int $index): ?CSVFieldInterface {
        return $this->fields[$index] ?? null;
    }

    public function countFields(): int {
        return count($this->fields);
    }

    public function countQuotedFields(): int {
        return count(array_filter($this->fields, fn(CSVFieldInterface $f) => $f->isQuoted()));
    }

    public function getDelimiter(): string {
        return $this->delimiter;
    }

    public function getEnclosure(): string {
        return $this->enclosure;
    }

    /**
     * Liefert den Bereich der Einschluss-Wiederholungen in den Feldern dieser Zeile.
     *
     * @param bool $includeUnquoted Ob unquoted Felder berücksichtigt werden sollen.
     * @return array [min, max]
     */
    public function getEnclosureRepeatRange(bool $includeUnquoted = false): array {
        $repeats = array_map(fn($f) => $f->getEnclosureRepeat(), $this->fields);
        $filtered = $includeUnquoted ? $repeats : array_filter($repeats, fn($v) => $v > 0);

        return [min($filtered ?: [0]), max($filtered ?: [0])];
    }

    /**
     * Wandelt die CSV-Zeile in eine rohe CSV-Zeichenkette um.
     *
     * @param string|null $delimiter Das Trennzeichen. Wenn null, wird das Standard-Trennzeichen verwendet.
     * @param string|null $enclosure Das Einschlusszeichen. Wenn null, wird das Standard-Einschlusszeichen verwendet.
     * @return string
     */
    public function toString(?string $delimiter = null, ?string $enclosure = null): string {
        $delimiter = $delimiter ?? $this->delimiter;
        $enclosure = $enclosure ?? $this->enclosure;

        $parts = array_map(fn(CSVFieldInterface $f) => $f->toString($enclosure), $this->fields);
        return implode($delimiter, $parts);
    }

    /**
     * Wandelt die CSV-Zeile in eine rohe CSV-Zeichenkette um.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * Vergleicht diese CSV-Zeile mit einer anderen auf Gleichheit.
     *
     * @param CSVLineInterface $other Die andere CSV-Zeile zum Vergleichen.
     * @return bool
     */
    public function equals(CSVLineInterface $other): bool {
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