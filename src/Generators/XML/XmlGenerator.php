<?php
/*
 * Created on   : Wed Jan 01 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlGenerator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Generators\XML;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Contracts\Interfaces\XML\XmlDocumentInterface;
use CommonToolkit\Contracts\Interfaces\XML\XmlElementInterface;
use CommonToolkit\Helper\FileSystem\File;
use DOMDocument;
use RuntimeException;

/**
 * Generator für XML-Dokumente.
 * 
 * Bietet erweiterte Generierungsfunktionen für XML-Dokumente:
 * - Pretty-Print Formatierung
 * - XSD-Schema-Location hinzufügen
 * - Namespace-Management
 * - Batch-Generierung
 * 
 * @package CommonToolkit\Generators\XML
 */
class XmlGenerator extends HelperAbstract {

    /**
     * Generiert XML-String aus einem Dokument.
     */
    public static function generate(XmlDocumentInterface $document, bool $formatOutput = true): string {
        $doc = $document->toDomDocument();
        $doc->formatOutput = $formatOutput;

        $xml = $doc->saveXML();
        if ($xml === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }

    /**
     * Generiert XML-String aus einem Element (ohne XML-Deklaration).
     */
    public static function generateElement(XmlElementInterface $element, bool $formatOutput = true): string {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = $formatOutput;
        $doc->appendChild($element->toDomNode($doc));

        $xml = $doc->saveXML($doc->documentElement);
        if ($xml === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren des Element-XML');
        }

        return $xml;
    }

    /**
     * Generiert XML mit Schema-Location.
     * 
     * @param XmlDocumentInterface $document Das Dokument
     * @param string $schemaLocation XSD Schema-Location (z.B. "urn:iso:std:iso:20022:tech:xsd:pain.001.003.03 pain.001.003.03.xsd")
     */
    public static function generateWithSchemaLocation(
        XmlDocumentInterface $document,
        string $schemaLocation,
        bool $formatOutput = true
    ): string {
        $doc = $document->toDomDocument();
        $doc->formatOutput = $formatOutput;

        if ($doc->documentElement !== null) {
            $doc->documentElement->setAttributeNS(
                'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation',
                $schemaLocation
            );
        }

        $xml = $doc->saveXML();
        if ($xml === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }

    /**
     * Speichert ein Dokument in eine Datei.
     * 
     * @throws RuntimeException Bei Fehlern
     */
    public static function toFile(XmlDocumentInterface $document, string $filePath, bool $formatOutput = true): void {
        $xml = self::generate($document, $formatOutput);
        File::write($filePath, $xml);
    }

    /**
     * Speichert mehrere Dokumente in Dateien.
     * 
     * @param array<string, XmlDocumentInterface> $documents Mapping von Dateipfad zu Dokument
     * @return array{success: string[], failed: string[]} Ergebnis
     */
    public static function batchToFile(array $documents, bool $formatOutput = true): array {
        $success = [];
        $failed = [];

        foreach ($documents as $filePath => $document) {
            try {
                self::toFile($document, $filePath, $formatOutput);
                $success[] = $filePath;
            } catch (\Exception $e) {
                self::logError("Fehler beim Speichern der XML-Datei {$filePath}: " . $e->getMessage());
                $failed[] = $filePath;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Generiert kompaktes XML (ohne Whitespace).
     */
    public static function generateCompact(XmlDocumentInterface $document): string {
        return self::generate($document, false);
    }

    /**
     * Generiert kanonisches XML (C14N).
     */
    public static function generateCanonical(XmlDocumentInterface $document): string {
        $doc = $document->toDomDocument();
        $canonical = $doc->C14N();

        if ($canonical === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren von kanonischem XML');
        }

        return $canonical;
    }

    /**
     * Generiert XML mit zusätzlichen Namespace-Deklarationen im Root.
     * 
     * @param array<string, string> $namespaces Mapping von Prefix zu URI
     */
    public static function generateWithNamespaces(
        XmlDocumentInterface $document,
        array $namespaces,
        bool $formatOutput = true
    ): string {
        $doc = $document->toDomDocument();
        $doc->formatOutput = $formatOutput;

        if ($doc->documentElement !== null) {
            foreach ($namespaces as $prefix => $uri) {
                $attrName = $prefix === '' ? 'xmlns' : "xmlns:{$prefix}";
                $doc->documentElement->setAttribute($attrName, $uri);
            }
        }

        $xml = $doc->saveXML();
        if ($xml === false) {
            self::logErrorAndThrow(RuntimeException::class, 'Fehler beim Generieren des XML-Strings');
        }

        return $xml;
    }
}
