<?php
/*
 * Created on   : Sun Nov 23 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : MetaHeaderFieldInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\DATEV;

/**
 * Gemeinsame Schnittstelle für alle Header-Felder (versioniert).
 */
interface MetaHeaderFieldInterface {
    /** Feldbezeichnung laut DATEV-Dokumentation. */
    public function label(): string;

    /** Regex-Validierungsmuster für das Feld. */
    public function pattern(): ?string;

    /** Reihenfolge des Felds im Header (1..N). */
    public function position(): int;

    /**
     * Gibt an, ob das Feld laut DATEV-Spezifikation gequotet sein muss.
     *
     * @see https://developer.datev.de/de/file-format/details/datev-format/format-description/header
     */
    public function isQuoted(): bool;
}
