<?php
/*
 * Created on   : Sun Dec 15 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DocumentInfo.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV;

use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

/**
 * Informationen über ein DATEV-Format.
 * Kapselt alle relevanten Details eines erkannten DATEV-Formats.
 */
final readonly class DocumentInfo {
    public function __construct(
        private Category $category,
        private int $version,
        private ?string $definitionClass = null
    ) {
    }

    /**
     * Gibt das Category-Enum zurück.
     */
    public function getCategory(): Category {
        return $this->category;
    }

    /**
     * Gibt den Format-Typ als String zurück.
     */
    public function getType(): string {
        return $this->category->nameValue();
    }

    /**
     * Gibt die Kategorie-Nummer zurück.
     */
    public function getCategoryNumber(): int {
        return $this->category->value;
    }

    /**
     * Gibt die Format-Version zurück.
     */
    public function getVersion(): int {
        return $this->version;
    }

    /**
     * Gibt die Definition-Klasse zurück (falls implementiert).
     */
    public function getDefinitionClass(): ?string {
        return $this->definitionClass;
    }

    /**
     * Prüft, ob das Format unterstützt wird.
     */
    public function isSupported(): bool {
        return $this->definitionClass !== null;
    }

    /**
     * Gibt eine String-Repräsentation zurück.
     */
    public function __toString(): string {
        $supported = $this->isSupported() ? 'unterstützt' : 'nicht unterstützt';
        return "{$this->getType()} v{$this->version} ({$supported})";
    }
}