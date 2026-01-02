<?php
/*
 * Created on   : Thu Jan 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExtendedDOMDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders;

use CommonToolkit\Entities\XML\ExtendedDOMDocument;
use CommonToolkit\Generators\XML\ExtendedDOMDocumentGenerator;
use DOMElement;
use DOMNode;

/**
 * Fluent Builder für ExtendedDOMDocument.
 * 
 * Ermöglicht den strukturierten Aufbau von XML-Dokumenten direkt auf DOM-Ebene
 * mit einer intuitiven API. Im Gegensatz zum XmlDocumentBuilder arbeitet dieser
 * Builder direkt mit DOMElement-Objekten für bessere Performance bei großen
 * Dokumenten.
 * 
 * Beispiel:
 * ```php
 * $doc = ExtendedDOMDocumentBuilder::create('Document')
 *     ->withNamespace('urn:iso:std:iso:20022:tech:xsd:camt.053.001.02', 'camt')
 *     ->addElement('Header')
 *         ->addChild('MsgId', 'MSG-001')
 *         ->addChild('CreDtTm', '2026-01-02T10:00:00')
 *         ->end()
 *     ->addElement('Body')
 *         ->addChild('Amount', '1000.00')
 *             ->withAttribute('Ccy', 'EUR')
 *         ->end()
 *     ->build();
 * ```
 * 
 * @package CommonToolkit\Builders
 */
class ExtendedDOMDocumentBuilder {
    private ExtendedDOMDocument $doc;
    private DOMElement $root;
    private DOMElement $current;
    private ?string $namespaceUri = null;
    private ?string $prefix = null;

    /** @var DOMElement[] Stack für Navigations-Kontext */
    private array $elementStack = [];

    private function __construct(string $rootName) {
        $this->doc = new ExtendedDOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        $this->root = $this->doc->createElement($rootName);
        $this->doc->appendChild($this->root);
        $this->current = $this->root;
    }

    // =========================================================================
    // FACTORY
    // =========================================================================

    /**
     * Erstellt einen neuen Builder mit Root-Element.
     */
    public static function create(string $rootName): self {
        return new self($rootName);
    }

    /**
     * Erstellt einen Builder aus einem bestehenden ExtendedDOMDocument.
     */
    public static function fromDocument(ExtendedDOMDocument $doc): self {
        $builder = new self('temp');
        $builder->doc = $doc;

        if ($doc->documentElement !== null) {
            $builder->root = $doc->documentElement;
            $builder->current = $doc->documentElement;
        }

        return $builder;
    }

    // =========================================================================
    // KONFIGURATION
    // =========================================================================

    /**
     * Setzt den Standard-Namespace für neue Elemente.
     */
    public function withNamespace(string $namespaceUri, ?string $prefix = null): self {
        $this->namespaceUri = $namespaceUri;
        $this->prefix = $prefix;

        // Root-Element mit Namespace aktualisieren
        if ($this->namespaceUri !== null) {
            $newRoot = $this->doc->createElementNS(
                $this->namespaceUri,
                $this->prefix ? "{$this->prefix}:{$this->root->localName}" : $this->root->localName
            );

            // Kinder übertragen
            while ($this->root->firstChild) {
                $newRoot->appendChild($this->root->firstChild);
            }

            // Attribute übertragen
            foreach ($this->root->attributes as $attr) {
                $newRoot->setAttribute($attr->nodeName, $attr->nodeValue);
            }

            $this->doc->replaceChild($newRoot, $this->root);
            $this->root = $newRoot;
            $this->current = $newRoot;
        }

        return $this;
    }

    /**
     * Setzt das Encoding.
     */
    public function withEncoding(string $encoding): self {
        $this->doc->encoding = $encoding;
        return $this;
    }

    /**
     * Aktiviert/deaktiviert Pretty-Print.
     */
    public function withFormatOutput(bool $formatOutput): self {
        $this->doc->formatOutput = $formatOutput;
        return $this;
    }

    /**
     * Fügt ein Attribut zum aktuellen Element hinzu.
     */
    public function withAttribute(string $name, string $value): self {
        $this->current->setAttribute($name, $value);
        return $this;
    }

    /**
     * Fügt ein Namespace-Attribut zum aktuellen Element hinzu.
     */
    public function withAttributeNS(string $namespaceUri, string $qualifiedName, string $value): self {
        $this->current->setAttributeNS($namespaceUri, $qualifiedName, $value);
        return $this;
    }

    // =========================================================================
    // ELEMENT-ERSTELLUNG
    // =========================================================================

    /**
     * Fügt ein einfaches Kind-Element mit optionalem Wert hinzu.
     * Bleibt auf dem aktuellen Level.
     */
    public function addChild(string $name, ?string $value = null): self {
        $element = $this->createElement($name);

        if ($value !== null) {
            $element->textContent = $value;
        }

        $this->current->appendChild($element);
        return $this;
    }

    /**
     * Fügt ein Kind-Element hinzu und navigiert hinein.
     * Verwende end() um zurückzukehren.
     */
    public function addElement(string $name, ?string $value = null): self {
        $element = $this->createElement($name);

        if ($value !== null) {
            $element->textContent = $value;
        }

        $this->current->appendChild($element);
        $this->elementStack[] = $this->current;
        $this->current = $element;

        return $this;
    }

    /**
     * Navigiert zum übergeordneten Element zurück.
     */
    public function end(): self {
        if (!empty($this->elementStack)) {
            $this->current = array_pop($this->elementStack);
        }
        return $this;
    }

    /**
     * Navigiert zurück zum Root-Element.
     */
    public function toRoot(): self {
        $this->elementStack = [];
        $this->current = $this->root;
        return $this;
    }

    /**
     * Fügt ein Kind-Element mit spezifischem Namespace hinzu.
     */
    public function addChildNS(string $namespaceUri, string $name, ?string $value = null, ?string $prefix = null): self {
        $qualifiedName = $prefix ? "{$prefix}:{$name}" : $name;
        $element = $this->doc->createElementNS($namespaceUri, $qualifiedName);

        if ($value !== null) {
            $element->textContent = $value;
        }

        $this->current->appendChild($element);
        return $this;
    }

    /**
     * Fügt mehrere Kind-Elemente aus einem Array hinzu.
     * 
     * @param array<string, string|null> $elements Mapping von Name zu Wert
     */
    public function addChildren(array $elements): self {
        foreach ($elements as $name => $value) {
            $this->addChild($name, $value);
        }
        return $this;
    }

    /**
     * Fügt ein CDATA-Section hinzu.
     */
    public function addCData(string $data): self {
        $cdata = $this->doc->createCDATASection($data);
        $this->current->appendChild($cdata);
        return $this;
    }

    /**
     * Fügt einen Kommentar hinzu.
     */
    public function addComment(string $comment): self {
        $commentNode = $this->doc->createComment($comment);
        $this->current->appendChild($commentNode);
        return $this;
    }

    /**
     * Fügt einen bestehenden DOMNode hinzu (wird importiert).
     */
    public function addNode(DOMNode $node): self {
        $imported = $this->doc->importNode($node, true);
        $this->current->appendChild($imported);
        return $this;
    }

    // =========================================================================
    // KONDITIONALE BUILDER
    // =========================================================================

    /**
     * Fügt ein Element nur hinzu, wenn die Bedingung erfüllt ist.
     */
    public function addChildIf(bool $condition, string $name, ?string $value = null): self {
        if ($condition) {
            $this->addChild($name, $value);
        }
        return $this;
    }

    /**
     * Fügt ein Element nur hinzu, wenn der Wert nicht null/leer ist.
     */
    public function addChildIfNotEmpty(string $name, ?string $value): self {
        if ($value !== null && $value !== '') {
            $this->addChild($name, $value);
        }
        return $this;
    }

    /**
     * Führt einen Callback aus, wenn die Bedingung erfüllt ist.
     * 
     * @param callable(self): self $callback
     */
    public function when(bool $condition, callable $callback): self {
        if ($condition) {
            return $callback($this);
        }
        return $this;
    }

    /**
     * Iteriert über ein Array und führt für jedes Element einen Callback aus.
     * 
     * @template T
     * @param T[] $items
     * @param callable(self, T, int): self $callback
     */
    public function each(array $items, callable $callback): self {
        foreach ($items as $index => $item) {
            $callback($this, $item, $index);
        }
        return $this;
    }

    // =========================================================================
    // HILFSMETHODEN
    // =========================================================================

    /**
     * Erstellt ein Element mit oder ohne Namespace.
     */
    private function createElement(string $name): DOMElement {
        if ($this->namespaceUri !== null) {
            $qualifiedName = $this->prefix ? "{$this->prefix}:{$name}" : $name;
            return $this->doc->createElementNS($this->namespaceUri, $qualifiedName);
        }

        return $this->doc->createElement($name);
    }

    /**
     * Gibt das aktuelle Element zurück.
     */
    public function getCurrent(): DOMElement {
        return $this->current;
    }

    /**
     * Gibt das Root-Element zurück.
     */
    public function getRoot(): DOMElement {
        return $this->root;
    }

    /**
     * Gibt die aktuelle Verschachtelungstiefe zurück.
     */
    public function getDepth(): int {
        return count($this->elementStack);
    }

    // =========================================================================
    // BUILD
    // =========================================================================

    /**
     * Baut und gibt das ExtendedDOMDocument zurück.
     */
    public function build(): ExtendedDOMDocument {
        return $this->doc;
    }

    /**
     * Baut und gibt den XML-String zurück.
     * Delegiert an ExtendedDOMDocumentGenerator.
     */
    public function toString(bool $formatOutput = true): string {
        return ExtendedDOMDocumentGenerator::generate($this->doc, $formatOutput);
    }

    /**
     * Baut und speichert in eine Datei.
     * Delegiert an ExtendedDOMDocumentGenerator.
     */
    public function toFile(string $filePath, bool $formatOutput = true): void {
        ExtendedDOMDocumentGenerator::toFile($this->doc, $filePath, $formatOutput);
    }
}
