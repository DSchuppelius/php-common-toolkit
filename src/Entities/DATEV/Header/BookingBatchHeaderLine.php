<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingBatchHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Entities\DATEV\Document;
use CommonToolkit\Entities\DATEV\Header\V700\BookingBatchHeaderDefinition;
use CommonToolkit\Enums\DATEV\V700\BookingBatchHeaderField;

/**
 * DATEV BookingBatch Header-Zeile (Spaltenbeschreibungen).
 * Zweite Zeile im DATEV-Format nach dem MetaHeader.
 * Versionsunabhängig - arbeitet mit HeaderDefinitionInterface.
 */
final class BookingBatchHeaderLine extends HeaderLineAbstract {
    /**
     * Factory-Methode für V700 BookingBatch Header.
     */
    public static function createV700(
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ): self {
        return new self(new BookingBatchHeaderDefinition(), $delimiter, $enclosure);
    }

    /**
     * Prüft ob dieser Header zu V700 BookingBatch passt.
     */
    public function isV700BookingHeader(): bool {
        return $this->isCompatibleWithEnum(BookingBatchHeaderField::class);
    }

    /**
     * Prüft ob dieser Header zu V700 BookingBatch passt.
     */
    public function isV700BookingBatchHeader(): bool {
        return $this->isCompatibleWithEnum(BookingBatchHeaderField::class);
    }
}
