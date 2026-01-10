<?php
/*
 * Created on   : Thu Jan 02 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ExtendedDOMDocumentGenerator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Generators\XML;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\XML\ExtendedDOMDocument;
use CommonToolkit\Helper\FileSystem\File;
use DOMElement;
use DOMNode;
use Exception;
use RuntimeException;

/**
 * Generator für ExtendedDOMDocument.
 * 
 * Bietet erweiterte Generierungsfunktionen:
 * - Pretty-Print Formatierung
 * - XSD-Schema-Location hinzufügen
 * - Namespace-Management
 * - Kanonisches XML (C14N)
 * - Batch-Generierung
 * - Fragment-Generierung
 * 
 * @package CommonToolkit\Generators\XML
 */
class ExtendedDOMDocumentGenerator extends HelperAbstract {

    // =========================================================================
    // STRING-GENERIERUNG
    // =========================================================================

    /**
     * Generiert XML-String aus einem ExtendedDOMDocument.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param bool $formatOutput Pretty-Print aktivieren
     * @return string Der XML-String
     */
    public static function generate(ExtendedDOMDocument $document, bool $formatOutput = true): string {
        $document->formatOutput = $formatOutput;

        $xml = $document->saveXML();
        if ($xml === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }

    /**
     * Generiert kompaktes XML (ohne Whitespace/Formatierung).
     */
    public static function generateCompact(ExtendedDOMDocument $document): string {
        $document->formatOutput = false;
        $document->preserveWhiteSpace = false;

        $xml = $document->saveXML();
        return $xml !== false ? $xml : '';
    }

    /**
     * Generiert kanonisches XML (C14N).
     * Nützlich für digitale Signaturen.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param bool $exclusive Exklusiv-Modus (für Signatur-Kompatibilität)
     * @param bool $withComments Kommentare einschließen
     */
    public static function generateCanonical(
        ExtendedDOMDocument $document,
        bool $exclusive = false,
        bool $withComments = false
    ): string {
        $canonical = $document->C14N($exclusive, $withComments);

        if ($canonical === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren von kanonischem XML');
        }

        return $canonical;
    }

    /**
     * Generiert nur das Root-Element (ohne XML-Deklaration).
     */
    public static function generateFragment(ExtendedDOMDocument $document, bool $formatOutput = true): string {
        $document->formatOutput = $formatOutput;

        if ($document->documentElement === null) {
            return '';
        }

        $xml = $document->saveXML($document->documentElement);
        return $xml !== false ? $xml : '';
    }

    /**
     * Generiert ein einzelnes Element als XML-String.
     */
    public static function generateElement(DOMElement $element, bool $formatOutput = true): string {
        $doc = new ExtendedDOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = $formatOutput;

        $imported = $doc->importNode($element, true);
        $doc->appendChild($imported);

        $xml = $doc->saveXML($doc->documentElement);
        return $xml !== false ? $xml : '';
    }

    // =========================================================================
    // MIT SCHEMA-LOCATION
    // =========================================================================

    /**
     * Generiert XML mit xsi:schemaLocation Attribut.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $schemaLocation Schema-Location (z.B. "urn:iso:std:iso:20022:tech:xsd:pain.001.003.03 pain.001.003.03.xsd")
     */
    public static function generateWithSchemaLocation(
        ExtendedDOMDocument $document,
        string $schemaLocation,
        bool $formatOutput = true
    ): string {
        $document->formatOutput = $formatOutput;

        if ($document->documentElement !== null) {
            $document->documentElement->setAttributeNS(
                'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation',
                $schemaLocation
            );
        }

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }

    /**
     * Generiert XML mit xsi:noNamespaceSchemaLocation Attribut.
     */
    public static function generateWithNoNamespaceSchemaLocation(
        ExtendedDOMDocument $document,
        string $schemaLocation,
        bool $formatOutput = true
    ): string {
        $document->formatOutput = $formatOutput;

        if ($document->documentElement !== null) {
            $document->documentElement->setAttributeNS(
                'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:noNamespaceSchemaLocation',
                $schemaLocation
            );
        }

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }

    // =========================================================================
    // DATEI-OPERATIONEN
    // =========================================================================

    /**
     * Speichert ein Dokument in eine Datei.
     * 
     * @param ExtendedDOMDocument $document Das Dokument
     * @param string $filePath Pfad zur Zieldatei
     * @param bool $formatOutput Pretty-Print aktivieren
     * @throws RuntimeException Bei Fehlern
     */
    public static function toFile(
        ExtendedDOMDocument $document,
        string $filePath,
        bool $formatOutput = true
    ): void {
        $document->formatOutput = $formatOutput;

        $result = $document->save($filePath);
        if ($result === false) {
            self::logErrorAndThrow(RuntimeException::class, "Fehler beim Speichern der XML-Datei: {$filePath}");
        }

        self::logInfo("XML-Datei gespeichert: {$filePath} ({$result} Bytes)");
    }

    /**
     * Speichert mehrere Dokumente in Dateien.
     * 
     * @param array<string, ExtendedDOMDocument> $documents Mapping von Dateipfad zu Dokument
     * @param bool $formatOutput Pretty-Print aktivieren
     * @return array{success: string[], failed: string[]} Ergebnis
     */
    public static function batchToFile(array $documents, bool $formatOutput = true): array {
        $success = [];
        $failed = [];

        foreach ($documents as $filePath => $document) {
            try {
                self::toFile($document, $filePath, $formatOutput);
                $success[] = $filePath;
            } catch (Exception $e) {
                self::logError("Fehler beim Speichern: {$filePath} - " . $e->getMessage());
                $failed[] = $filePath;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    // =========================================================================
    // TRANSFORMATIONEN
    // =========================================================================

    /**
     * Entfernt alle Whitespace-Textknoten (minimiert XML).
     */
    public static function stripWhitespace(ExtendedDOMDocument $document): ExtendedDOMDocument {
        $xpath = $document->getXPath();
        $textNodes = $xpath->query('//text()');

        if ($textNodes !== false) {
            $toRemove = [];
            foreach ($textNodes as $node) {
                if (trim($node->nodeValue) === '') {
                    $toRemove[] = $node;
                }
            }
            foreach ($toRemove as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        return $document;
    }

    /**
     * Entfernt alle Kommentare aus dem Dokument.
     */
    public static function stripComments(ExtendedDOMDocument $document): ExtendedDOMDocument {
        $xpath = $document->getXPath();
        $comments = $xpath->query('//comment()');

        if ($comments !== false) {
            $toRemove = [];
            foreach ($comments as $comment) {
                $toRemove[] = $comment;
            }
            foreach ($toRemove as $comment) {
                $comment->parentNode?->removeChild($comment);
            }
        }

        return $document;
    }

    /**
     * Fügt einen Standard-Namespace-Prefix zu allen Elementen hinzu.
     * Nützlich wenn ein Dokument ohne Prefix erstellt wurde.
     */
    public static function addNamespacePrefix(
        ExtendedDOMDocument $document,
        string $prefix,
        string $namespaceUri
    ): ExtendedDOMDocument {
        $newDoc = new ExtendedDOMDocument('1.0', 'UTF-8');
        $newDoc->formatOutput = $document->formatOutput;

        if ($document->documentElement !== null) {
            $newRoot = self::cloneElementWithPrefix(
                $document->documentElement,
                $newDoc,
                $prefix,
                $namespaceUri
            );
            $newDoc->appendChild($newRoot);
        }

        return $newDoc;
    }

    /**
     * Klont ein Element mit neuem Namespace-Prefix.
     */
    private static function cloneElementWithPrefix(
        DOMElement $element,
        ExtendedDOMDocument $doc,
        string $prefix,
        string $namespaceUri
    ): DOMElement {
        $newElement = $doc->createElementNS($namespaceUri, "{$prefix}:{$element->localName}");

        // Attribute kopieren
        foreach ($element->attributes as $attr) {
            if ($attr->namespaceURI !== null) {
                $newElement->setAttributeNS($attr->namespaceURI, $attr->nodeName, $attr->nodeValue);
            } else {
                $newElement->setAttribute($attr->nodeName, $attr->nodeValue);
            }
        }

        // Kinder rekursiv verarbeiten
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $newChild = self::cloneElementWithPrefix($child, $doc, $prefix, $namespaceUri);
                $newElement->appendChild($newChild);
            } elseif ($child->nodeType === XML_TEXT_NODE) {
                $newElement->appendChild($doc->createTextNode($child->nodeValue ?? ''));
            } elseif ($child->nodeType === XML_CDATA_SECTION_NODE) {
                $newElement->appendChild($doc->createCDATASection($child->nodeValue ?? ''));
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $newElement->appendChild($doc->createComment($child->nodeValue ?? ''));
            }
        }

        return $newElement;
    }

    // =========================================================================
    // MERGE & SPLIT
    // =========================================================================

    /**
     * Merged mehrere Dokumente in ein neues Dokument.
     * 
     * @param string $rootName Name des neuen Root-Elements
     * @param ExtendedDOMDocument[] $documents Zu mergende Dokumente
     * @param string|null $namespaceUri Optional: Namespace für das neue Root-Element
     */
    public static function merge(
        string $rootName,
        array $documents,
        ?string $namespaceUri = null
    ): ExtendedDOMDocument {
        $merged = new ExtendedDOMDocument('1.0', 'UTF-8');
        $merged->formatOutput = true;

        if ($namespaceUri !== null) {
            $root = $merged->createElementNS($namespaceUri, $rootName);
        } else {
            $root = $merged->createElement($rootName);
        }
        $merged->appendChild($root);

        foreach ($documents as $doc) {
            if ($doc->documentElement !== null) {
                $imported = $merged->importNode($doc->documentElement, true);
                $root->appendChild($imported);
            }
        }

        return $merged;
    }

    /**
     * Extrahiert alle Elemente mit einem bestimmten Namen als separate Dokumente.
     * 
     * @param ExtendedDOMDocument $document Das Quell-Dokument
     * @param string $elementName Der Element-Name zum Extrahieren
     * @return ExtendedDOMDocument[]
     */
    public static function split(ExtendedDOMDocument $document, string $elementName): array {
        $documents = [];
        $elements = $document->getElementsByTagName($elementName);

        foreach ($elements as $element) {
            $newDoc = new ExtendedDOMDocument('1.0', 'UTF-8');
            $newDoc->formatOutput = true;
            $imported = $newDoc->importNode($element, true);
            $newDoc->appendChild($imported);
            $documents[] = $newDoc;
        }

        return $documents;
    }
}
