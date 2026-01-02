<?php
/*
 * Created on   : Thu Jan 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExtendedDOMDocumentParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XML\ExtendedDOMDocument;
use CommonToolkit\Helper\FileSystem\File;
use DOMElement;
use DOMNode;
use RuntimeException;

/**
 * Parser für ExtendedDOMDocument.
 * 
 * Bietet statische Factory-Methoden und Hilfsfunktionen für ExtendedDOMDocument:
 * - Factory-Methoden (fromString, fromFile)
 * - XSD-Validierung
 * - Namespace-Extraktion
 * - Metadaten-Extraktion
 * 
 * Für instanzbasierte XPath-Operationen nutze ExtendedDOMDocument direkt.
 * 
 * @package CommonToolkit\Parsers
 */
class ExtendedDOMDocumentParser extends HelperAbstract {

    // =========================================================================
    // FACTORY METHODEN
    // =========================================================================

    /**
     * Parst einen XML-String zu einem ExtendedDOMDocument.
     * 
     * @param string $xml Der XML-Inhalt
     * @throws RuntimeException Bei ungültigem XML
     */
    public static function fromString(string $xml): ExtendedDOMDocument {
        $doc = new ExtendedDOMDocument('1.0', 'UTF-8');
        $doc->initializeFromString($xml);
        return $doc;
    }

    /**
     * Parst eine XML-Datei zu einem ExtendedDOMDocument.
     * 
     * @param string $filePath Pfad zur XML-Datei
     * @throws RuntimeException Bei ungültigem XML oder Dateifehler
     */
    public static function fromFile(string $filePath): ExtendedDOMDocument {
        $resolvedPath = self::resolveFile($filePath);
        $content = file_get_contents($resolvedPath);

        if ($content === false) {
            $errorMessage = "Fehler beim Lesen der XML-Datei: {$resolvedPath}";
            self::logError($errorMessage);
            throw new RuntimeException($errorMessage);
        }

        return self::fromString($content);
    }

    /**
     * Erstellt ein ExtendedDOMDocument aus einem bestehenden DOMDocument.
     * 
     * @param \DOMDocument $dom Das bestehende DOMDocument
     * @return ExtendedDOMDocument Das konvertierte Dokument
     */
    public static function fromDOMDocument(\DOMDocument $dom): ExtendedDOMDocument {
        $doc = new ExtendedDOMDocument('1.0', 'UTF-8');

        // Importiere das Root-Element
        if ($dom->documentElement !== null) {
            $importedNode = $doc->importNode($dom->documentElement, true);
            $doc->appendChild($importedNode);
        }

        $doc->initializeXPath();
        return $doc;
    }

    /**
     * Parst und validiert gegen XSD.
     * 
     * @param string $xml XML-Inhalt
     * @param string $xsdFile Pfad zur XSD-Datei
     * @return ExtendedDOMDocument Das geparste und validierte Dokument
     * @throws RuntimeException Bei Validierungsfehlern
     */
    public static function fromStringWithValidation(string $xml, string $xsdFile): ExtendedDOMDocument {
        $document = self::fromString($xml);
        $result = self::validateAgainstXsd($document, $xsdFile);

        if (!$result['valid']) {
            throw new RuntimeException(
                'XSD-Validierung fehlgeschlagen: ' . implode(', ', $result['errors'])
            );
        }

        return $document;
    }

    /**
     * Parst eine Datei und validiert gegen XSD.
     * 
     * @param string $filePath Pfad zur XML-Datei
     * @param string $xsdFile Pfad zur XSD-Datei
     * @return ExtendedDOMDocument Das geparste und validierte Dokument
     * @throws RuntimeException Bei Validierungsfehlern
     */
    public static function fromFileWithValidation(string $filePath, string $xsdFile): ExtendedDOMDocument {
        $document = self::fromFile($filePath);
        $result = self::validateAgainstXsd($document, $xsdFile);

        if (!$result['valid']) {
            throw new RuntimeException(
                'XSD-Validierung fehlgeschlagen: ' . implode(', ', $result['errors'])
            );
        }

        return $document;
    }

    // =========================================================================
    // VALIDIERUNG
    // =========================================================================

    /**
     * Validiert ein ExtendedDOMDocument gegen ein XSD-Schema.
     * 
     * @param ExtendedDOMDocument $document Das zu validierende Dokument
     * @param string $xsdFile Pfad zur XSD-Datei
     * @return array{valid: bool, errors: string[]}
     */
    public static function validateAgainstXsd(ExtendedDOMDocument $document, string $xsdFile): array {
        $xsdPath = self::resolveFile($xsdFile);

        libxml_use_internal_errors(true);
        $valid = $document->schemaValidate($xsdPath);
        $errors = [];

        if (!$valid) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message) . " (Zeile {$error->line})";
            }
        }

        libxml_clear_errors();

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Prüft ob ein XML-String wohlgeformt ist.
     * 
     * @param string $xml Der XML-Inhalt
     * @return array{valid: bool, errors: string[]}
     */
    public static function isWellFormed(string $xml): array {
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $valid = $doc->loadXML($xml);
        $errors = [];

        if (!$valid) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message) . " (Zeile {$error->line})";
            }
        }

        libxml_clear_errors();

        return ['valid' => $valid, 'errors' => $errors];
    }

    // =========================================================================
    // METADATEN
    // =========================================================================

    /**
     * Extrahiert Metadaten aus einem ExtendedDOMDocument.
     * 
     * @return array{rootElement: string, namespace: ?string, encoding: ?string, version: ?string, childCount: int}
     */
    public static function getMetadata(ExtendedDOMDocument $document): array {
        $root = $document->documentElement;

        return [
            'rootElement' => $root?->localName ?? '',
            'namespace' => $document->getNamespace(),
            'encoding' => $document->encoding,
            'version' => $document->xmlVersion,
            'childCount' => $root?->childNodes->length ?? 0,
        ];
    }

    /**
     * Extrahiert alle verwendeten Namespaces.
     * 
     * @return array<string, string> Mapping von Prefix zu URI
     */
    public static function extractNamespaces(ExtendedDOMDocument $document): array {
        $xpath = $document->getXPath();
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
     * Extrahiert alle Attributnamen eines Elements.
     * 
     * @return string[]
     */
    public static function getAttributeNames(DOMElement $element): array {
        $names = [];
        foreach ($element->attributes as $attr) {
            $names[] = $attr->nodeName;
        }
        return $names;
    }

    // =========================================================================
    // HILFSMETHODEN
    // =========================================================================

    /**
     * Zählt Elemente mit einem bestimmten Tag-Namen.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $tagName Der Tag-Name (ohne Namespace-Prefix)
     * @return int Anzahl der gefundenen Elemente
     */
    public static function countElements(ExtendedDOMDocument $document, string $tagName): int {
        return $document->getElementsByTagName($tagName)->length;
    }

    /**
     * Prüft ob ein Element mit einem bestimmten Tag-Namen existiert.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $tagName Der Tag-Name
     * @return bool True wenn mindestens ein Element existiert
     */
    public static function hasElement(ExtendedDOMDocument $document, string $tagName): bool {
        return self::countElements($document, $tagName) > 0;
    }

    /**
     * Gibt das erste Element mit einem bestimmten Tag-Namen zurück.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $tagName Der Tag-Name
     * @return DOMElement|null Das erste Element oder null
     */
    public static function getFirstElement(ExtendedDOMDocument $document, string $tagName): ?DOMElement {
        $elements = $document->getElementsByTagName($tagName);
        $first = $elements->item(0);
        return $first instanceof DOMElement ? $first : null;
    }

    /**
     * Extrahiert den Text-Inhalt eines Elements via Tag-Name.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $tagName Der Tag-Name
     * @param string|null $default Standardwert wenn nicht gefunden
     * @return string|null Der Text-Inhalt oder der Standardwert
     */
    public static function getElementText(ExtendedDOMDocument $document, string $tagName, ?string $default = null): ?string {
        $element = self::getFirstElement($document, $tagName);
        return $element !== null ? $element->textContent : $default;
    }

    /**
     * Klont ein ExtendedDOMDocument.
     * 
     * @param ExtendedDOMDocument $document Das zu klonende Dokument
     * @return ExtendedDOMDocument Das geklonte Dokument
     */
    public static function clone(ExtendedDOMDocument $document): ExtendedDOMDocument {
        $xml = $document->saveXML();
        return self::fromString($xml ?: '');
    }
}
