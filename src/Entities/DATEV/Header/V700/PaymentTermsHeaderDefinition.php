<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTermsHeaderDefinition.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header\V700;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderDefinitionAbstract;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\PaymentTermsHeaderField;

/**
 * Definition für DATEV Zahlungsbedingungen-Header (V700).
 * Definiert die Struktur der Spaltenbeschreibungen für Zahlungsbedingungen-Daten.
 */
final class PaymentTermsHeaderDefinition extends HeaderDefinitionAbstract {
    /**
     * Liefert den Enum-Typ für die Header-Felder.
     *
     * @return class-string<PaymentTermsHeaderField>
     */
    public function getFieldEnum(): string {
        return PaymentTermsHeaderField::class;
    }

    /**
     * Liefert den Namen des Formats für Fehlermeldungen.
     */
    protected function getFormatName(): string {
        return 'Zahlungsbedingungen';
    }
}
