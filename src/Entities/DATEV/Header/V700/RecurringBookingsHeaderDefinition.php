<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : RecurringBookingsHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderDefinitionAbstract;
use CommonToolkit\Enums\DATEV\V700\RecurringBookingsHeaderField;

/**
 * Definition für DATEV Wiederkehrende Buchungen-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für Wiederkehrende Buchungen-Daten.
 */
final class RecurringBookingsHeaderDefinition extends HeaderDefinitionAbstract {
    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<RecurringBookingsHeaderField>
     */
    public function getFieldEnum(): string {
        return RecurringBookingsHeaderField::class;
    }

    /**
     * Liefert den Namen des Formats für Fehlermeldungen.
     */
    protected function getFormatName(): string {
        return 'Wiederkehrende Buchungen';
    }
}