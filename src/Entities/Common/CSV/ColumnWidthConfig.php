<?php
/*
 * Created on   : Mon Dec 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ColumnWidthConfig.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\CSV;

use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;

/**
 * Konfiguration für Spaltenbreiten in CSV-Dokumenten.
 * Ermöglicht das Festlegen von maximalen Zeichenbreiten für Spalten.
 */
class ColumnWidthConfig {
    use ErrorLog;

    /** @var array<string|int, int> Spaltenbreiten-Mapping */
    private array $columnWidths = [];

    /** @var int|null Standardbreite für alle Spalten */
    private ?int $defaultWidth = null;

    /** @var TruncationStrategy Abschneidungsstrategie */
    private TruncationStrategy $truncationStrategy = TruncationStrategy::TRUNCATE;

    public function __construct(?int $defaultWidth = null) {
        $this->defaultWidth = $defaultWidth;
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
     * @return string Gekürzter Wert
     */
    public function truncateValue(string $value, string|int $column): string {
        $maxWidth = $this->getColumnWidth($column);

        // Wenn keine Breite definiert oder NONE Strategie, keine Kürzung
        if ($maxWidth === null || $this->truncationStrategy === TruncationStrategy::NONE || mb_strlen($value) <= $maxWidth) {
            return $value;
        }

        if ($this->truncationStrategy === TruncationStrategy::ELLIPSIS && $maxWidth > 3) {
            return mb_substr($value, 0, $maxWidth - 3) . '...';
        }

        return mb_substr($value, 0, $maxWidth);
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
}