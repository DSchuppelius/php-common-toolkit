<?php
/*
 * Created on   : Wed Jan 22 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XLSX;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Repräsentiert ein XLSX-Dokument mit mehreren Arbeitsblättern.
 * Analog zu CommonToolkit\Entities\CSV\Document.
 * 
 * @implements IteratorAggregate<int, Sheet>
 */
class Document implements Countable, IteratorAggregate {
    /** @var Sheet[] */
    protected array $sheets;
    protected ?string $creator;
    protected ?string $title;
    protected ?string $description;
    protected ?\DateTimeInterface $created;
    protected ?\DateTimeInterface $modified;

    /**
     * @param Sheet[]                $sheets      Die Arbeitsblätter
     * @param string|null            $creator     Der Ersteller
     * @param string|null            $title       Der Titel
     * @param string|null            $description Die Beschreibung
     * @param \DateTimeInterface|null $created    Das Erstelldatum
     * @param \DateTimeInterface|null $modified   Das Änderungsdatum
     */
    public function __construct(
        array $sheets = [],
        ?string $creator = null,
        ?string $title = null,
        ?string $description = null,
        ?\DateTimeInterface $created = null,
        ?\DateTimeInterface $modified = null
    ) {
        $this->sheets      = array_values($sheets);
        $this->creator     = $creator;
        $this->title       = $title;
        $this->description = $description;
        $this->created     = $created;
        $this->modified    = $modified;
    }

    /**
     * Gibt alle Arbeitsblätter zurück.
     *
     * @return Sheet[]
     */
    public function getSheets(): array {
        return $this->sheets;
    }

    /**
     * Gibt ein Arbeitsblatt nach Index zurück (0-basiert).
     */
    public function getSheet(int $index): ?Sheet {
        return $this->sheets[$index] ?? null;
    }

    /**
     * Gibt ein Arbeitsblatt nach Name zurück.
     */
    public function getSheetByName(string $name): ?Sheet {
        foreach ($this->sheets as $sheet) {
            if ($sheet->getName() === $name) {
                return $sheet;
            }
        }
        return null;
    }

    /**
     * Gibt das erste Arbeitsblatt zurück.
     */
    public function getFirstSheet(): ?Sheet {
        return $this->sheets[0] ?? null;
    }

    /**
     * Gibt die Anzahl der Arbeitsblätter zurück.
     */
    public function count(): int {
        return count($this->sheets);
    }

    /**
     * Gibt die Namen aller Arbeitsblätter zurück.
     *
     * @return array<string>
     */
    public function getSheetNames(): array {
        return array_map(fn(Sheet $sheet) => $sheet->getName(), $this->sheets);
    }

    /**
     * Prüft ob ein Arbeitsblatt mit dem gegebenen Namen existiert.
     */
    public function hasSheet(string $name): bool {
        return $this->getSheetByName($name) !== null;
    }

    /**
     * Gibt den Ersteller zurück.
     */
    public function getCreator(): ?string {
        return $this->creator;
    }

    /**
     * Gibt den Titel zurück.
     */
    public function getTitle(): ?string {
        return $this->title;
    }

    /**
     * Gibt die Beschreibung zurück.
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * Gibt das Erstelldatum zurück.
     */
    public function getCreated(): ?\DateTimeInterface {
        return $this->created;
    }

    /**
     * Gibt das Änderungsdatum zurück.
     */
    public function getModified(): ?\DateTimeInterface {
        return $this->modified;
    }

    /**
     * Prüft ob alle Sheets konsistent sind (Header-Spalten = Daten-Spalten).
     */
    public function isConsistent(): bool {
        foreach ($this->sheets as $sheet) {
            if (!$sheet->isConsistent()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gibt die Gesamtanzahl aller Datenzeilen über alle Sheets zurück.
     */
    public function countTotalRows(): int {
        $total = 0;
        foreach ($this->sheets as $sheet) {
            $total += $sheet->count();
        }
        return $total;
    }

    /**
     * @return Traversable<int, Sheet>
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->sheets);
    }
}
