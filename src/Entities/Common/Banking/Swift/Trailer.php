<?php
/*
 * Created on   : Sat Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Trailer.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\Common\Banking\Swift;

/**
 * SWIFT FIN Trailer Block (Block 5)
 * 
 * Format: {5:{CHK:123456789ABC}{TNG:}{PDE:1348120811BANKFRPPAXXX2222123456}}
 * 
 * Mögliche Felder:
 * - CHK: Checksum (12 hex Zeichen)
 * - TNG: Training Message Flag
 * - PDE: Possible Duplicate Emission
 * - PDM: Possible Duplicate Message
 * - DLM: Delayed Message
 * - MRF: Message Reference
 * - SYS: System Originated Message
 */
final class Trailer {
    /**
     * @param array<string, string> $fields Die Trailer Felder (Tag => Wert)
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
     * Gibt die Checksum zurück
     */
    public function getChecksum(): ?string {
        return $this->getField('CHK');
    }

    /**
     * Prüft ob es sich um eine Training-Nachricht handelt
     */
    public function isTraining(): bool {
        return $this->hasField('TNG');
    }

    /**
     * Prüft ob es sich um eine mögliche Duplikatemission handelt
     */
    public function isPossibleDuplicateEmission(): bool {
        return $this->hasField('PDE');
    }

    /**
     * Prüft ob es sich um eine mögliche Duplikatnachricht handelt
     */
    public function isPossibleDuplicateMessage(): bool {
        return $this->hasField('PDM');
    }

    /**
     * Prüft ob es sich um eine verzögerte Nachricht handelt
     */
    public function isDelayed(): bool {
        return $this->hasField('DLM');
    }

    /**
     * Prüft ob es sich um eine System-generierte Nachricht handelt
     */
    public function isSystemOriginated(): bool {
        return $this->hasField('SYS');
    }

    /**
     * Prüft ob ein bestimmtes Feld vorhanden ist
     */
    public function hasField(string $tag): bool {
        return isset($this->fields[$tag]);
    }

    /**
     * Gibt den vollständigen Block 5 String zurück
     */
    public function __toString(): string {
        if (empty($this->fields)) {
            return '';
        }

        $content = '';
        foreach ($this->fields as $tag => $value) {
            $content .= '{' . $tag . ':' . $value . '}';
        }
        return '{5:' . $content . '}';
    }

    /**
     * Parst einen Block 5 String
     * 
     * @param string $raw Roher Block-Inhalt (ohne {5: und })
     */
    public static function parse(string $raw): self {
        $fields = [];

        // Parse nested {tag:value} pairs
        if (preg_match_all('/\{([A-Z]+):([^}]*)\}/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fields[$match[1]] = $match[2];
            }
        }

        return new self($fields);
    }
}
