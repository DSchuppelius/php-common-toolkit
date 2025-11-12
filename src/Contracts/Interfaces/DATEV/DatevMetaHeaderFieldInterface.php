<?php

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

/**
 * Gemeinsame Schnittstelle für alle Header-Felder (versioniert).
 */
interface DatevMetaHeaderFieldInterface {
    /** Feldbezeichnung laut DATEV-Dokumentation. */
    public function label(): string;

    /** Regex-Validierungsmuster für das Feld. */
    public function pattern(): ?string;

    /** Reihenfolge des Felds im Header (1..N). */
    public function position(): int;
}
