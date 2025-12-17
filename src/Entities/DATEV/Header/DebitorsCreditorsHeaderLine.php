<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DebitorsCreditorsHeaderLine.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Header;

use CommonToolkit\Contracts\Abstracts\DATEV\HeaderLineAbstract;
use CommonToolkit\Contracts\Interfaces\Common\CSV\FieldInterface;
use CommonToolkit\Entities\DATEV\Document;
use CommonToolkit\Entities\DATEV\Header\V700\DebitorsCreditorsHeaderDefinition;
use CommonToolkit\Enums\DATEV\V700\DebitorsCreditorsHeaderField;

/**
 * DATEV Debitoren/Kreditoren Header-Zeile (Spaltenbeschreibungen).
 * Zweite Zeile im DATEV-Format nach dem MetaHeader.
 */
final class DebitorsCreditorsHeaderLine extends HeaderLineAbstract {
    /**
     * Factory-Methode für V700 Debitoren/Kreditoren Header.
     */
    public static function createV700(
        string $delimiter = Document::DEFAULT_DELIMITER,
        string $enclosure = FieldInterface::DEFAULT_ENCLOSURE
    ): self {
        return new self(new DebitorsCreditorsHeaderDefinition(), $delimiter, $enclosure);
    }

    /**
     * Prüft ob dieser Header zu V700 Debitoren/Kreditoren passt.
     */
    public function isV700DebitorsCreditorsHeader(): bool {
        return $this->isCompatibleWithEnum(DebitorsCreditorsHeaderField::class);
    }
}
