<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTermsHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Entities\DATEV\Document;
use CommonToolkit\Entities\DATEV\Header\V700\PaymentTermsHeaderDefinition;
use CommonToolkit\Enums\DATEV\V700\PaymentTermsHeaderField;

/**
 * DATEV Zahlungsbedingungen Header-Zeile (Spaltenbeschreibungen).
 * Zweite Zeile im DATEV-Format nach dem MetaHeader.
 */
final class PaymentTermsHeaderLine extends HeaderLineAbstract {
    /**
     * Factory-Methode für V700 PaymentTerms Header.
     */
    public static function createV700(
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ): self {
        return new self(new PaymentTermsHeaderDefinition(), $delimiter, $enclosure);
    }

    /**
     * Prüft ob dieser Header zu V700 PaymentTerms passt.
     */
    public function isV700PaymentTermsHeader(): bool {
        return $this->isCompatibleWithEnum(PaymentTermsHeaderField::class);
    }
}
