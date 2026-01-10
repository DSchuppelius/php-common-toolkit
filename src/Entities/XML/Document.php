<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Document.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XML;

use CommonToolkit\Contracts\Interfaces\XML\XmlDocumentInterface;
use CommonToolkit\Contracts\Interfaces\XML\XmlElementInterface;
use CommonToolkit\Generators\XML\XmlGenerator;
use CommonToolkit\Helper\Data\XmlHelper;
use CommonToolkit\Helper\FileSystem\File;
use DOMDocument;
use DOMNode;
use InvalidArgumentException;

/**
 * Repräsentiert ein XML-Dokument.
 * 
 * Immutable Container für XML-Dokumente mit XSD-Validierung,
 * Pretty-Print und Datei-Export.
 * 
 * @package CommonToolkit\Entities\XML
 */
class Document implements XmlDocumentInterface {
    private string $version;
    private string $encoding;
    private XmlElementInterface $rootElement;
    private bool $formatOutput;

    /**
     * @param XmlElementInterface $rootElement Root-Element des Dokuments
     * @param string $version XML-Version (Standard: "1.0")
     * @param string $encoding Zeichencodierung (Standard: "UTF-8")
     * @param bool $formatOutput Pretty-Print aktivieren
     */
    public function __construct(
        XmlElementInterface $rootElement,
        string $version = '1.0',
        string $encoding = 'UTF-8',
        bool $formatOutput = true
    ) {
        $this->rootElement = $rootElement;
        $this->version = $version;
        $this->encoding = $encoding;
        $this->formatOutput = $formatOutput;
    }

    public function getVersion(): string {
        return $this->version;
    }

    public function getEncoding(): string {
        return $this->encoding;
    }

    public function getRootElement(): XmlElementInterface {
        return $this->rootElement;
    }

    /**
     * Gibt das Root-Element-Namen zurück.
     */
    public function getRootElementName(): string {
        return $this->rootElement->getName();
    }

    /**
     * Gibt den Namespace des Root-Elements zurück.
     */
    public function getNamespace(): ?string {
        return $this->rootElement->getNamespaceUri();
    }

    public function toDomDocument(): DOMDocument {
        $doc = new DOMDocument($this->version, $this->encoding);
        $doc->formatOutput = $this->formatOutput;

        $rootNode = $this->rootElement->toDomNode($doc);
        $doc->appendChild($rootNode);

        return $doc;
    }

    public function toDomNode(DOMDocument $doc): DOMNode {
        return $this->rootElement->toDomNode($doc);
    }

    public function toString(): string {
        return XmlGenerator::generate($this, $this->formatOutput);
    }

    /**
     * Generiert kompaktes XML ohne Formatierung.
     */
    public function toCompactString(): string {
        return XmlGenerator::generate($this, false);
    }

    /**
     * Generiert kanonisches XML (C14N) für Signaturen.
     */
    public function toCanonicalString(): string {
        return XmlGenerator::generateCanonical($this);
    }

    /**
     * Generiert XML mit Schema-Location Attribut.
     * 
     * @param string $schemaLocation z.B. "urn:iso:std:iso:20022:tech:xsd:pain.001.003.03 pain.001.003.03.xsd"
     */
    public function toStringWithSchemaLocation(string $schemaLocation): string {
        return XmlGenerator::generateWithSchemaLocation($this, $schemaLocation, $this->formatOutput);
    }

    /**
     * Generiert XML mit zusätzlichen Namespace-Deklarationen.
     * 
     * @param array<string, string> $namespaces Mapping von Prefix zu URI
     */
    public function toStringWithNamespaces(array $namespaces): string {
        return XmlGenerator::generateWithNamespaces($this, $namespaces, $this->formatOutput);
    }

    public function toFile(string $filePath): void {
        XmlGenerator::toFile($this, $filePath, $this->formatOutput);
    }

    public function validateAgainstXsd(string $xsdFile): array {
        return XmlHelper::validateAgainstXsd($this->toString(), $xsdFile);
    }

    /**
     * Validiert das Dokument gegen ein XSD-Schema und gibt typisiertes Ergebnis zurück.
     * 
     * @param string $xsdFile Pfad zur XSD-Schema-Datei
     * @return XsdValidationResult Typisiertes Validierungsergebnis
     */
    public function validateAgainstXsdTyped(string $xsdFile): XsdValidationResult {
        return XmlHelper::validateAgainstXsdTyped($this->toString(), $xsdFile);
    }

    /**
     * Prüft ob das Dokument wohlgeformt ist.
     */
    public function isWellFormed(): bool {
        return XmlHelper::isValid($this->toString());
    }

    // ===== Builder-Methoden =====

    /**
     * Erstellt eine Kopie mit neuem Root-Element.
     */
    public function withRootElement(XmlElementInterface $rootElement): self {
        return new self($rootElement, $this->version, $this->encoding, $this->formatOutput);
    }

    /**
     * Erstellt eine Kopie mit aktiviertem/deaktiviertem Pretty-Print.
     */
    public function withFormatOutput(bool $formatOutput): self {
        return new self($this->rootElement, $this->version, $this->encoding, $formatOutput);
    }

    /**
     * Erstellt eine Kopie mit neuem Encoding.
     */
    public function withEncoding(string $encoding): self {
        return new self($this->rootElement, $this->version, $encoding, $this->formatOutput);
    }

    // ===== Factory-Methoden =====

    /**
     * Erstellt ein Dokument aus einem XML-String.
     * 
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function fromString(string $xml): self {
        $doc = new DOMDocument();

        libxml_use_internal_errors(true);
        $result = $doc->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!$result || !empty($errors)) {
            $errorMessages = array_map(
                fn($e) => trim($e->message),
                $errors
            );
            throw new InvalidArgumentException(
                'Ungültiges XML: ' . implode(', ', $errorMessages)
            );
        }

        if ($doc->documentElement === null) {
            throw new InvalidArgumentException('XML hat kein Root-Element');
        }

        $rootElement = Element::fromDomElement($doc->documentElement);

        return new self(
            $rootElement,
            $doc->xmlVersion ?? '1.0',
            $doc->encoding ?? 'UTF-8'
        );
    }

    /**
     * Erstellt ein Dokument aus einer Datei.
     * 
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function fromFile(string $filePath): self {
        $content = File::read($filePath);
        return self::fromString($content);
    }

    /**
     * Erstellt ein Dokument aus einem DOMDocument.
     */
    public static function fromDomDocument(DOMDocument $doc): self {
        if ($doc->documentElement === null) {
            throw new InvalidArgumentException('DOMDocument hat kein Root-Element');
        }

        $rootElement = Element::fromDomElement($doc->documentElement);

        return new self(
            $rootElement,
            $doc->xmlVersion ?? '1.0',
            $doc->encoding ?? 'UTF-8',
            $doc->formatOutput
        );
    }

    /**
     * Erstellt ein leeres Dokument mit einem Root-Element.
     */
    public static function create(string $rootName, ?string $namespaceUri = null, ?string $prefix = null): self {
        $rootElement = new Element($rootName, null, $namespaceUri, $prefix);
        return new self($rootElement);
    }
}
