<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : UserHeader.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Swift;

/**
 * SWIFT FIN User Header Block (Block 3)
 * 
 * Format: {3:{108:MUR12345678901234}{119:STP}}
 * 
 * Enthält optionale User-spezifische Felder:
 * - 103: Service Type Identifier
 * - 108: Message User Reference (MUR)
 * - 111: Service Type Identifier (Request for Transfer)
 * - 113: Banking Priority
 * - 115: Addressee Information
 * - 119: Validation Flag (STP = Straight Through Processing)
 * - 121: Unique End-to-End Transaction Reference (UETR)
 * - 165: Payment Release Information
 * - 433: Sanctions Screening Info
 * - 434: Payment Controls Info
 */
final class UserHeader {
    /**
     * @param array<string, string> $fields Die User Header Felder (Tag => Wert)
     */
    public function __construct(
        private readonly array $fields = []
    ) {
    }

    /**
     * Gibt alle Felder zurück
     * 
     * @return array<string, string>
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * Gibt ein bestimmtes Feld zurück
     */
    public function getField(string $tag): ?string {
        return $this->fields[$tag] ?? null;
    }

    /**
     * Gibt die Service Type Identifier zurück
     */
    public function getServiceTypeId(): ?string {
        return $this->getField('103');
    }

    /**
     * Gibt die Message User Reference (MUR) zurück
     */
    public function getMur(): ?string {
        return $this->getField('108');
    }

    /**
     * Gibt das Banking Priority zurück
     */
    public function getBankingPriority(): ?string {
        return $this->getField('113');
    }

    /**
     * Gibt das Validation Flag zurück (z.B. STP)
     */
    public function getValidationFlag(): ?string {
        return $this->getField('119');
    }

    /**
     * Gibt die Unique End-to-End Transaction Reference (UETR) zurück
     */
    public function getUetr(): ?string {
        return $this->getField('121');
    }

    /**
     * Prüft ob STP (Straight Through Processing) aktiviert ist
     */
    public function isStp(): bool {
        return $this->getField('119') === 'STP';
    }

    /**
     * Prüft ob ein bestimmtes Feld vorhanden ist
     */
    public function hasField(string $tag): bool {
        return isset($this->fields[$tag]);
    }

    /**
     * Gibt den vollständigen Block 3 String zurück
     */
    public function __toString(): string {
        if (empty($this->fields)) {
            return '';
        }

        $content = '';
        foreach ($this->fields as $tag => $value) {
            $content .= '{' . $tag . ':' . $value . '}';
        }
        return '{3:' . $content . '}';
    }

    /**
     * Parst einen Block 3 String
     * 
     * @param string $raw Roher Block-Inhalt (ohne {3: und })
     */
    public static function parse(string $raw): self {
        $fields = [];

        // Parse nested {tag:value} pairs
        if (preg_match_all('/\{(\d+):([^}]*)\}/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fields[$match[1]] = $match[2];
            }
        }

        return new self($fields);
    }
}
