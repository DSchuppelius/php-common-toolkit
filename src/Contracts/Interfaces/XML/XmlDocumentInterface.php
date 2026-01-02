<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlDocumentInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Interfaces\XML;

use DOMDocument;

/**
 * Interface für XML-Dokumente.
 */
interface XmlDocumentInterface extends XmlNodeInterface {
    /**
     * Gibt die XML-Version zurück (z.B. "1.0").
     */
    public function getVersion(): string;

    /**
     * Gibt das Encoding zurück (z.B. "UTF-8").
     */
    public function getEncoding(): string;

    /**
     * Gibt das Root-Element zurück.
     */
    public function getRootElement(): XmlElementInterface;

    /**
     * Gibt das Dokument als DOMDocument zurück.
     */
    public function toDomDocument(): DOMDocument;

    /**
     * Speichert das Dokument in eine Datei.
     */
    public function toFile(string $filePath): void;

    /**
     * Validiert gegen ein XSD-Schema.
     * @return array{valid: bool, errors: string[]}
     */
    public function validateAgainstXsd(string $xsdFile): array;
}
