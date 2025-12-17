<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : VariousAddressesHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Entities\DATEV\Document;
use CommonToolkit\Entities\DATEV\Header\V700\VariousAddressesHeaderDefinition;
use CommonToolkit\Enums\DATEV\V700\VariousAddressesHeaderField;

/**
 * DATEV Diverse Adressen Header-Zeile (Spaltenbeschreibungen).
 * Zweite Zeile im DATEV-Format nach dem MetaHeader.
 */
final class VariousAddressesHeaderLine extends HeaderLineAbstract {
    /**
     * Factory-Methode für V700 VariousAddresses Header.
     */
    public static function createV700(
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ): self {
        return new self(new VariousAddressesHeaderDefinition(), $delimiter, $enclosure);
    }

    /**
     * Prüft ob dieser Header zu V700 VariousAddresses passt.
     */
    public function isV700VariousAddressesHeader(): bool {
        return $this->isCompatibleWithEnum(VariousAddressesHeaderField::class);
    }
}
