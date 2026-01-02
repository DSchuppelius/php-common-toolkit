<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DomainXmlDocumentAbstract.php
 * License      : MIT
 * License Uri  : https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\XML;

use CommonToolkit\Contracts\Interfaces\XML\XmlDocumentInterface;
use CommonToolkit\Contracts\Interfaces\XML\XmlElementInterface;
use CommonToolkit\Entities\XML\Document as XmlDocument;
use DOMDocument;
use DOMNode;

/**
 * Abstrakte Basisklasse für domänenspezifische XML-Dokumente.
 * 
 * Diese Klasse bietet eine Grundlage für Domänen-Dokumente wie:
 * - Banking: CAMT, Pain, MT-Formate
 * - Buchhaltung: DATEV, XRechnung
 * - Andere XML-basierte Standards
 * 
 * Nutzt die generische Document Entity aus CommonToolkit als interne
 * Repräsentation und delegiert alle XmlDocumentInterface-Methoden dorthin.
 * 
 * Implementierende Klassen müssen nur die abstrakte Methode getDefaultXml() 
 * bereitstellen, alle anderen Methoden werden automatisch über das
 * gecachte XmlDocument bereitgestellt.
 * 
 * @package CommonToolkit\Contracts\Abstracts\XML
 */
abstract class DomainXmlDocumentAbstract implements XmlDocumentInterface {
    /**
     * Gecachtes XmlDocument (generische Entity) für Delegation.
     */
    private ?XmlDocument $cachedDocument = null;

    /**
     * Generiert die XML-Ausgabe für dieses Dokument mit Standardparametern.
     * 
     * Diese Methode wird für alle XmlDocumentInterface-Methoden verwendet.
     * Domänenspezifische Klassen können zusätzliche toXml() Methoden mit
     * Parametern anbieten (z.B. toXml(CamtVersion $version)).
     * 
     * @return string Das generierte XML
     */
    abstract protected function getDefaultXml(): string;

    // =========================================================================
    // Document Entity Delegation
    // =========================================================================

    /**
     * Gibt das interne XmlDocument zurück (generische Entity).
     * 
     * Lazy-Loading mit automatischem Caching.
     */
    public function toXmlDocument(): XmlDocument {
        if ($this->cachedDocument === null) {
            $this->cachedDocument = XmlDocument::fromString($this->getDefaultXml());
        }
        return $this->cachedDocument;
    }

    // =========================================================================
    // XmlDocumentInterface Implementation (delegiert an Document Entity)
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getVersion(): string {
        return $this->toXmlDocument()->getVersion();
    }

    /**
     * @inheritDoc
     */
    public function getEncoding(): string {
        return $this->toXmlDocument()->getEncoding();
    }

    /**
     * @inheritDoc
     */
    public function getRootElement(): XmlElementInterface {
        return $this->toXmlDocument()->getRootElement();
    }

    /**
     * @inheritDoc
     */
    public function toDomDocument(): DOMDocument {
        return $this->toXmlDocument()->toDomDocument();
    }

    /**
     * @inheritDoc
     */
    public function toDomNode(DOMDocument $doc): DOMNode {
        return $this->toXmlDocument()->toDomNode($doc);
    }

    /**
     * @inheritDoc
     */
    public function toString(): string {
        return $this->toXmlDocument()->toString();
    }

    /**
     * @inheritDoc
     */
    public function toFile(string $filePath): void {
        $this->toXmlDocument()->toFile($filePath);
    }

    /**
     * @inheritDoc
     */
    public function validateAgainstXsd(string $xsdFile): array {
        return $this->toXmlDocument()->validateAgainstXsd($xsdFile);
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    /**
     * Invalidiert den XML-Cache.
     * 
     * Muss aufgerufen werden, wenn sich die Daten des Dokuments ändern.
     */
    protected function invalidateCache(): void {
        $this->cachedDocument = null;
    }

    /**
     * Prüft ob das Document gecacht ist.
     */
    protected function isCached(): bool {
        return $this->cachedDocument !== null;
    }
}
