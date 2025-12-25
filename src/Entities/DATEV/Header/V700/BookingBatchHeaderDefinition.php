<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderDefinitionAbstract;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\BookingBatchHeaderField;

/**
 * Definition für DATEV BookingBatch-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für BookingBatch-Daten.
 */
final class BookingBatchHeaderDefinition extends HeaderDefinitionAbstract {
    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<BookingBatchHeaderField>
     */
    public function getFieldEnum(): string {
        return BookingBatchHeaderField::class;
    }

    /**
     * Liefert den Namen des Formats für Fehlermeldungen.
     */
    protected function getFormatName(): string {
        return 'Buchungsstapel';
    }
}
