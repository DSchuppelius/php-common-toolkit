<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : GLAccountDescriptionHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderDefinitionAbstract;
use CommonToolkit\Enums\DATEV\V700\GLAccountDescriptionHeaderField;

/**
 * Definition für DATEV Kontenbeschriftungen-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für Kontenbeschriftungen-Daten.
 */
final class GLAccountDescriptionHeaderDefinition extends HeaderDefinitionAbstract {
    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<GLAccountDescriptionHeaderField>
     */
    public function getFieldEnum(): string {
        return GLAccountDescriptionHeaderField::class;
    }

    /**
     * Liefert den Namen des Formats für Fehlermeldungen.
     */
    protected function getFormatName(): string {
        return 'Kontenbeschriftungen';
    }
}
