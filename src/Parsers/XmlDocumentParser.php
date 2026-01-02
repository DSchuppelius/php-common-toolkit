<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XML\Document;
use CommonToolkit\Entities\XML\Element;
use CommonToolkit\Helper\FileSystem\File;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

/**
 * Parser für XML-Dokumente.
 * 
 * Bietet erweiterte Parsing-Funktionen:
 * - XPath-Abfragen
 * - Namespace-Handling
 * - Streaming für große Dateien
 * - Validierung
 * 
 * @package CommonToolkit\Parsers
 */
class XmlDocumentParser extends HelperAbstract {

    /**
     * Parst einen XML-String zu einem Document.
     * 
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function fromString(string $xml): Document {
        return Document::fromString($xml);
    }

    /**
     * Parst eine XML-Datei zu einem Document.
     * 
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function fromFile(string $filePath): Document {
        $resolvedPath = self::resolveFile($filePath);
        return Document::fromFile($resolvedPath);
    }

    /**
     * Parst und validiert gegen XSD.
     * 
     * @param string $xml XML-Inhalt
     * @param string $xsdFile Pfad zur XSD-Datei
     * @return Document Das geparste Dokument
     * @throws RuntimeException Bei Validierungsfehlern
     */
    public static function fromStringWithValidation(string $xml, string $xsdFile): Document {
        $document = self::fromString($xml);
        $result = $document->validateAgainstXsd($xsdFile);

        if (!$result['valid']) {
            throw new RuntimeException(
                'XSD-Validierung fehlgeschlagen: ' . implode(', ', $result['errors'])
            );
        }

        return $document;
    }

    /**
     * Führt eine XPath-Abfrage aus und gibt Elemente zurück.
     * 
     * @param Document $document Das Dokument
     * @param string $xpath XPath-Ausdruck
     * @param array<string, string> $namespaces Namespace-Mapping (Prefix => URI)
     * @return Element[]
     */
    public static function xpath(Document $document, string $xpath, array $namespaces = []): array {
        $dom = $document->toDomDocument();
        $xpathObj = new DOMXPath($dom);

        // Namespaces registrieren
        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $result = $xpathObj->query($xpath);
        if ($result === false) {
            self::logError("Ungültiger XPath-Ausdruck: {$xpath}");
            return [];
        }

        $elements = [];
        foreach ($result as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = Element::fromDomElement($node);
            }
        }

        return $elements;
    }

    /**
     * Führt eine XPath-Abfrage aus und gibt das erste Element zurück.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function xpathFirst(Document $document, string $xpath, array $namespaces = []): ?Element {
        $elements = self::xpath($document, $xpath, $namespaces);
        return $elements[0] ?? null;
    }

    /**
     * Führt eine XPath-Abfrage aus und gibt Textwerte zurück.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     * @return string[]
     */
    public static function xpathValues(Document $document, string $xpath, array $namespaces = []): array {
        $dom = $document->toDomDocument();
        $xpathObj = new DOMXPath($dom);

        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $result = $xpathObj->query($xpath);
        if ($result === false) {
            return [];
        }

        $values = [];
        foreach ($result as $node) {
            $values[] = $node->textContent;
        }

        return $values;
    }

    /**
     * Gibt den ersten XPath-Textwert zurück.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function xpathValue(Document $document, string $xpath, array $namespaces = [], ?string $default = null): ?string {
        $values = self::xpathValues($document, $xpath, $namespaces);
        return $values[0] ?? $default;
    }

    /**
     * Extrahiert Metadaten aus einem XML-Dokument.
     * 
     * @return array{rootElement: string, namespace: ?string, encoding: string, version: string, childCount: int}
     */
    public static function getMetadata(Document $document): array {
        $root = $document->getRootElement();

        return [
            'rootElement' => $root->getName(),
            'namespace' => $root->getNamespaceUri(),
            'encoding' => $document->getEncoding(),
            'version' => $document->getVersion(),
            'childCount' => $root->countChildren(),
        ];
    }

    /**
     * Extrahiert alle verwendeten Namespaces.
     * 
     * @return array<string, string> Mapping von Prefix zu URI
     */
    public static function extractNamespaces(Document $document): array {
        $dom = $document->toDomDocument();
        $xpath = new DOMXPath($dom);

        $namespaces = [];
        $nodes = $xpath->query('//namespace::*');

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $prefix = $node->localName;
                $uri = $node->nodeValue;

                if ($prefix !== 'xml' && $uri !== null) {
                    $namespaces[$prefix] = $uri;
                }
            }
        }

        return $namespaces;
    }

    /**
     * Konvertiert ein Dokument zu einem assoziativen Array.
     * 
     * @param bool $includeAttributes Attribute einbeziehen
     * @param bool $includeNamespaces Namespaces einbeziehen
     */
    public static function toArray(
        Document $document,
        bool $includeAttributes = true,
        bool $includeNamespaces = false
    ): array {
        return self::elementToArray($document->getRootElement(), $includeAttributes, $includeNamespaces);
    }

    /**
     * Konvertiert ein Element rekursiv zu einem Array.
     */
    private static function elementToArray(
        Element $element,
        bool $includeAttributes,
        bool $includeNamespaces
    ): array {
        $result = [];

        // Attribute hinzufügen
        if ($includeAttributes && !empty($element->getAttributes())) {
            $attrs = [];
            foreach ($element->getAttributes() as $attr) {
                $attrs[$attr->getName()] = $attr->getValue();
            }
            $result['@attributes'] = $attrs;
        }

        // Namespace hinzufügen
        if ($includeNamespaces && $element->getNamespaceUri() !== null) {
            $result['@namespace'] = $element->getNamespaceUri();
            if ($element->getPrefix() !== null) {
                $result['@prefix'] = $element->getPrefix();
            }
        }

        $children = $element->getChildren();

        if (empty($children)) {
            // Blattknoten: nur Wert
            $value = $element->getValue();
            if (empty($result)) {
                return $value !== null ? ['#value' => $value] : [];
            }
            if ($value !== null) {
                $result['#value'] = $value;
            }
            return $result;
        }

        // Kind-Elemente gruppieren
        $grouped = [];
        foreach ($children as $child) {
            $name = $child->getName();
            if (!isset($grouped[$name])) {
                $grouped[$name] = [];
            }
            $grouped[$name][] = $child;
        }

        foreach ($grouped as $name => $elements) {
            if (count($elements) === 1) {
                $result[$name] = self::elementToArray($elements[0], $includeAttributes, $includeNamespaces);
            } else {
                $result[$name] = array_map(
                    fn($el) => self::elementToArray($el, $includeAttributes, $includeNamespaces),
                    $elements
                );
            }
        }

        return $result;
    }

    /**
     * Zählt Elemente die einem XPath entsprechen.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function count(Document $document, string $xpath, array $namespaces = []): int {
        return count(self::xpath($document, $xpath, $namespaces));
    }

    /**
     * Prüft ob ein XPath-Ausdruck Treffer hat.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function exists(Document $document, string $xpath, array $namespaces = []): bool {
        return self::count($document, $xpath, $namespaces) > 0;
    }

    /**
     * Evaluiert einen XPath-Ausdruck und gibt das Ergebnis als String zurück.
     * 
     * Nützlich für XPath-Funktionen wie string(), concat(), etc.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function evaluate(Document $document, string $xpath, array $namespaces = [], ?string $default = null): ?string {
        $dom = $document->toDomDocument();
        $xpathObj = new DOMXPath($dom);

        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $result = $xpathObj->evaluate($xpath);

        if ($result === false || $result === '') {
            return $default;
        }

        return (string) $result;
    }

    /**
     * Evaluiert einen XPath-Ausdruck relativ zu einem Element.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     */
    public static function evaluateOnElement(
        Document $document,
        Element $element,
        string $xpath,
        array $namespaces = [],
        ?string $default = null
    ): ?string {
        $dom = $document->toDomDocument();
        $xpathObj = new DOMXPath($dom);

        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        // Finde das entsprechende DOM-Element
        $domElement = self::findDomElement($dom, $element);
        if ($domElement === null) {
            return $default;
        }

        $result = $xpathObj->evaluate($xpath, $domElement);

        if ($result === false || $result === '') {
            return $default;
        }

        return (string) $result;
    }

    /**
     * Führt XPath relativ zu einem Element aus.
     * 
     * @param array<string, string> $namespaces Namespace-Mapping
     * @return Element[]
     */
    public static function xpathOnElement(
        Document $document,
        Element $element,
        string $xpath,
        array $namespaces = []
    ): array {
        $dom = $document->toDomDocument();
        $xpathObj = new DOMXPath($dom);

        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $domElement = self::findDomElement($dom, $element);
        if ($domElement === null) {
            return [];
        }

        $result = $xpathObj->query($xpath, $domElement);
        if ($result === false) {
            return [];
        }

        $elements = [];
        foreach ($result as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = Element::fromDomElement($node);
            }
        }

        return $elements;
    }

    /**
     * Findet ein DOM-Element das zu einem Element passt.
     * 
     * Sucht basierend auf Name, Namespace und Position.
     */
    private static function findDomElement(DOMDocument $dom, Element $element): ?DOMElement {
        $xpath = new DOMXPath($dom);
        $name = $element->getName();
        $ns = $element->getNamespaceUri();

        // Einfache Suche: alle Elemente mit diesem Namen
        if ($ns !== null) {
            $xpath->registerNamespace('search', $ns);
            $nodes = $xpath->query("//search:{$name}");
        } else {
            $nodes = $xpath->query("//{$name}");
        }

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        // Bei mehreren Treffern: versuche über Attribute zu matchen
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                if (self::elementsMatch($node, $element)) {
                    return $node;
                }
            }
        }

        // Fallback: erstes Element
        $first = $nodes->item(0);
        return $first instanceof DOMElement ? $first : null;
    }

    /**
     * Prüft ob ein DOM-Element zu einem Element passt.
     */
    private static function elementsMatch(DOMElement $domElement, Element $element): bool {
        // Name und Namespace prüfen
        if ($domElement->localName !== $element->getName()) {
            return false;
        }

        if ($domElement->namespaceURI !== $element->getNamespaceUri()) {
            return false;
        }

        // Attribute vergleichen
        foreach ($element->getAttributes() as $attr) {
            $domAttr = $domElement->getAttribute($attr->getName());
            if ($domAttr !== $attr->getValue()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Erkennt automatisch Namespaces und gibt ein Mapping zurück.
     * 
     * Nützlich für ISO 20022 Dokumente wo Namespace-URIs variieren können.
     * 
     * @return array{namespace: ?string, prefix: string, namespaces: array<string, string>}
     */
    public static function detectNamespaces(Document $document): array {
        $root = $document->getRootElement();
        $namespaces = self::extractNamespaces($document);

        $mainNs = $root->getNamespaceUri();
        $prefix = '';

        if ($mainNs !== null) {
            $prefix = 'ns:';
        }

        return [
            'namespace' => $mainNs,
            'prefix' => $prefix,
            'namespaces' => $namespaces,
        ];
    }
}
