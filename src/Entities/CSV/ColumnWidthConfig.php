<?php
/*
 * Created on   : Tue Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ColumnWidthConfig.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\CSV;

use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

/**
 * Konfiguration für Spaltenbreiten in CSV-Dokumenten.
 * Ermöglicht das Festlegen von maximalen/festen Zeichenbreiten für Spalten.
 * 
 * Features:
 * - Spaltenweise Breiten (nach Name oder Index)
 * - Standard-Breite für alle nicht explizit konfigurierten Spalten
 * - Abschneidungsstrategien (TRUNCATE, ELLIPSIS, NONE)
 * - Padding-Unterstützung für feste Spaltenbreiten
 */
class ColumnWidthConfig {
    use ErrorLog;

    /** @var array<string|int, int> Spaltenbreiten-Mapping */
    private array $columnWidths = [];

    /** @var int|null Standardbreite für alle Spalten */
    private ?int $defaultWidth = null;

    /** @var TruncationStrategy Abschneidungsstrategie */
    private TruncationStrategy $truncationStrategy = TruncationStrategy::TRUNCATE;

    /** @var bool Ob Werte mit Leerzeichen aufgefüllt werden sollen (für feste Spaltenbreiten) */
    private bool $enablePadding = false;

    /** @var string Padding-Zeichen (Standard: Leerzeichen) */
    private string $paddingChar = ' ';

    /** @var int Padding-Richtung: STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH */
    private int $paddingType = STR_PAD_RIGHT;

    public function __construct(?int $defaultWidth = null, TruncationStrategy $strategy = TruncationStrategy::TRUNCATE) {
        $this->defaultWidth = $defaultWidth;
        $this->truncationStrategy = $strategy;
    }

    /**
     * Setzt die Breite für eine spezifische Spalte.
     * 
     * @param string|int $column Spaltenname oder Index
     * @param int $width Maximale Breite in Zeichen
     * @return $this
     * @throws RuntimeException
     */
    public function setColumnWidth(string|int $column, int $width): self {
        if ($width < 1) {
            $this->logError('Spaltenbreite muss mindestens 1 Zeichen betragen', ['column' => $column, 'width' => $width]);
            throw new RuntimeException('Spaltenbreite muss mindestens 1 Zeichen betragen');
        }

        $this->columnWidths[$column] = $width;
        return $this;
    }

    /**
     * Setzt Breiten für mehrere Spalten gleichzeitig.
     * 
     * @param array<string|int, int> $widths Spalten-Breiten-Mapping
     * @return $this
     */
    public function setColumnWidths(array $widths): self {
        foreach ($widths as $column => $width) {
            $this->setColumnWidth($column, $width);
        }
        return $this;
    }

    /**
     * Gibt die konfigurierte Breite für eine Spalte zurück.
     * 
     * @param string|int $column Spaltenname oder Index
     * @return int|null Breite oder null wenn nicht konfiguriert
     */
    public function getColumnWidth(string|int $column): ?int {
        return $this->columnWidths[$column] ?? $this->defaultWidth;
    }

    /**
     * Setzt die Standardbreite für alle nicht explizit konfigurierten Spalten.
     * 
     * @param int|null $width Standardbreite oder null zum Deaktivieren
     * @return $this
     */
    public function setDefaultWidth(?int $width): self {
        if ($width !== null && $width < 1) {
            $this->logError('Standardbreite muss mindestens 1 Zeichen betragen', ['width' => $width]);
            throw new RuntimeException('Standardbreite muss mindestens 1 Zeichen betragen');
        }

        $this->defaultWidth = $width;
        return $this;
    }

    /**
     * Gibt die Standardbreite zurück.
     * 
     * @return int|null
     */
    public function getDefaultWidth(): ?int {
        return $this->defaultWidth;
    }

    /**
     * Setzt die Abschneidungsstrategie.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie
     * @return $this
     */
    public function setTruncationStrategy(TruncationStrategy $strategy): self {
        $this->truncationStrategy = $strategy;
        return $this;
    }

    /**
     * Gibt die Abschneidungsstrategie zurück.
     * 
     * @return TruncationStrategy
     */
    public function getTruncationStrategy(): TruncationStrategy {
        return $this->truncationStrategy;
    }

    /**
     * Kürzt einen Wert basierend auf der konfigurierten Spaltenbreite.
     * 
     * @param string $value Zu kürzender Wert
     * @param string|int $column Spaltenname oder Index
     * @return string Gekürzter (und ggf. gepadter) Wert
     */
    public function truncateValue(string $value, string|int $column): string {
        $maxWidth = $this->getColumnWidth($column);

        // Wenn keine Breite definiert, Wert unverändert zurückgeben
        if ($maxWidth === null) {
            return $value;
        }

        $currentLength = mb_strlen($value);

        // Kürzung anwenden falls nötig
        if ($currentLength > $maxWidth && $this->truncationStrategy !== TruncationStrategy::NONE) {
            $value = match ($this->truncationStrategy) {
                TruncationStrategy::ELLIPSIS => $maxWidth > 3
                    ? mb_substr($value, 0, $maxWidth - 3) . '...'
                    : mb_substr($value, 0, $maxWidth),
                TruncationStrategy::TRUNCATE => mb_substr($value, 0, $maxWidth),
                default => $value,
            };
            $currentLength = mb_strlen($value);
        }

        // Padding anwenden falls aktiviert
        if ($this->enablePadding && $currentLength < $maxWidth) {
            $value = mb_str_pad($value, $maxWidth, $this->paddingChar, $this->paddingType);
        }

        return $value;
    }

    /**
     * Aktiviert/Deaktiviert Padding für feste Spaltenbreiten.
     * 
     * @param bool $enable Padding aktivieren
     * @param string $char Padding-Zeichen (Standard: Leerzeichen)
     * @param int $type Padding-Richtung (STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH)
     * @return $this
     */
    public function setPadding(bool $enable, string $char = ' ', int $type = STR_PAD_RIGHT): self {
        $this->enablePadding = $enable;
        $this->paddingChar = $char;
        $this->paddingType = $type;
        return $this;
    }

    /**
     * Gibt zurück, ob Padding aktiviert ist.
     * 
     * @return bool
     */
    public function isPaddingEnabled(): bool {
        return $this->enablePadding;
    }

    /**
     * Prüft, ob für die gegebene Spalte eine Breite konfiguriert ist.
     * 
     * @param string|int $column Spaltenname oder Index
     * @return bool
     */
    public function hasWidthConfig(string|int $column): bool {
        return isset($this->columnWidths[$column]) || $this->defaultWidth !== null;
    }

    /**
     * Gibt alle konfigurierten Spaltenbreiten zurück.
     * 
     * @return array<string|int, int>
     */
    public function getAllColumnWidths(): array {
        return $this->columnWidths;
    }

    /**
     * Prüft ob überhaupt eine Breitenkonfiguration vorhanden ist.
     * 
     * @return bool
     */
    public function hasAnyConfig(): bool {
        return !empty($this->columnWidths) || $this->defaultWidth !== null;
    }

    /**
     * Erstellt eine Kopie der Konfiguration.
     * 
     * @return self
     */
    public function clone(): self {
        $clone = new self($this->defaultWidth, $this->truncationStrategy);
        $clone->columnWidths = $this->columnWidths;
        $clone->enablePadding = $this->enablePadding;
        $clone->paddingChar = $this->paddingChar;
        $clone->paddingType = $this->paddingType;
        return $clone;
    }

    /**
     * Gibt eine String-Repräsentation der Konfiguration zurück.
     */
    public function __toString(): string {
        $parts = [];

        if ($this->defaultWidth !== null) {
            $parts[] = "default: {$this->defaultWidth}";
        }

        foreach ($this->columnWidths as $column => $width) {
            $parts[] = "{$column}: {$width}";
        }

        $parts[] = "strategy: {$this->truncationStrategy->value}";

        if ($this->enablePadding) {
            $parts[] = "padding: on";
        }

        return 'ColumnWidthConfig[' . implode(', ', $parts) . ']';
    }
}
