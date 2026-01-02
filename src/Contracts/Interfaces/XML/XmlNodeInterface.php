<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlNodeInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\XML;

use DOMDocument;
use DOMNode;

/**
 * Interface für alle XML-Knoten (Elemente, Dokumente).
 */
interface XmlNodeInterface {
    /**
     * Gibt den Knoten als DOMNode zurück.
     */
    public function toDomNode(DOMDocument $doc): DOMNode;

    /**
     * Gibt den Knoten als XML-String zurück.
     */
    public function toString(): string;
}
