<?php
/*
 * Created on   : Thu Jul 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : QuotingStyle.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\CSV;

/**
 * Enum für CSV-Quoting-Strategien beim Encodieren von Feldern/Zeilen.
 *
 * Steuert, wann ein Feld beim Encodieren in Enclosures gesetzt wird
 * (vgl. {@see \CommonToolkit\Helper\Data\CSV\StringHelper::encodeField()}).
 */
enum QuotingStyle: string {
    /**
     * RFC-4180-minimal: Nur quoten, wenn das Feld Delimiter, Enclosure
     * oder Zeilenumbruch (\n / \r) enthält.
     */
    case MINIMAL = 'minimal';

    /**
     * Jedes Feld wird immer in Enclosures gesetzt.
     */
    case ALWAYS = 'always';

    /**
     * Byte-kompatibel zu PHPs fputcsv(): Quotet zusätzlich bei Escape-Zeichen,
     * Tab (\t) und Leerzeichen; verdoppelt Enclosures nur, wenn sie nicht
     * unmittelbar auf das Escape-Zeichen folgen.
     */
    case FPUTCSV = 'fputcsv';
}
