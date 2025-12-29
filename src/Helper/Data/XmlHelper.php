<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use ERRORToolkit\Traits\ErrorLog;
use DOMDocument;
use DOMXPath;
use LibXMLError;
use InvalidArgumentException;
use RuntimeException;

/**
 * Helper-Klasse für XML-Verarbeitung und Validierung.
 *
 * Bietet Funktionen für:
 * - XML-Validierung gegen XSD-Schemas
 * - Pretty-Print Formatierung
 * - Namespace-Extraktion
 * - XML zu Array Konvertierung
 * - XPath-Abfragen
 * - SEPA/CAMT/PAIN XML spezifische Funktionen
 */
class XmlHelper extends HelperAbstract {
    use ErrorLog;

    /**
     * Validiert ein XML-Dokument auf syntaktische Korrektheit.
     *
     * @param string $xml Der XML-String
     * @return bool True wenn gültig, false andernfalls
     */
    public static function isValid(string $xml): bool {
        $doc = new DOMDocument();

        // Temporär XML-Fehler unterdrücken
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $result = $doc->loadXML($xml);
        $errors = libxml_get_errors();

        libxml_use_internal_errors($useInternalErrors);

        if (!$result || !empty($errors)) {
            foreach ($errors as $error) {
                self::logError("XML-Validierung: " . trim($error->message) . " (Zeile {$error->line})");
            }
            return false;
        }

        return true;
    }

    /**
     * Validiert XML gegen ein XSD-Schema.
     *
     * @param string $xml Der XML-String
     * @param string $xsdFile Pfad zur XSD-Schema-Datei
     * @return array{valid: bool, errors: string[]} Validierungsergebnis
     */
    public static function validateAgainstXsd(string $xml, string $xsdFile): array {
        if (!file_exists($xsdFile)) {
            $error = "XSD-Schema-Datei nicht gefunden: {$xsdFile}";
            self::logError($error);
            return ['valid' => false, 'errors' => [$error]];
        }

        $doc = new DOMDocument();

        // XML-Fehler sammeln
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$doc->loadXML($xml)) {
            $errors = self::getLibXmlErrors();
            libxml_use_internal_errors($useInternalErrors);
            return ['valid' => false, 'errors' => $errors];
        }

        $isValid = $doc->schemaValidate($xsdFile);
        $errors = $isValid ? [] : self::getLibXmlErrors();

        libxml_use_internal_errors($useInternalErrors);

        return ['valid' => $isValid, 'errors' => $errors];
    }

    /**
     * Sammelt LibXML-Fehler und formatiert sie als String-Array.
     *
     * @return string[] Array von Fehlermeldungen
     */
    private static function getLibXmlErrors(): array {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $level = match ($error->level) {
                LIBXML_ERR_WARNING => 'Warning',
                LIBXML_ERR_ERROR => 'Error',
                LIBXML_ERR_FATAL => 'Fatal Error',
                default => 'Unknown'
            };

            $errors[] = "{$level}: " . trim($error->message) . " (Zeile {$error->line}, Spalte {$error->column})";
        }
        libxml_clear_errors();

        return $errors;
    }

    /**
     * Formatiert XML für bessere Lesbarkeit (Pretty-Print).
     *
     * @param string $xml Der XML-String
     * @return string Der formatierte XML-String
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function prettyFormat(string $xml): string {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        if (!$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Ungültiger XML-String');
        }

        $formatted = $doc->saveXML();
        if ($formatted === false) {
            throw new RuntimeException('XML-Formatierung fehlgeschlagen');
        }

        return $formatted;
    }

    /**
     * Extrahiert alle Namespaces aus einem XML-Dokument.
     *
     * @param string $xml Der XML-String
     * @return array<string, string> Array von Namespace-Prefix zu URI Mappings
     */
    public static function extractNamespaces(string $xml): array {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            self::logError('Fehler beim Laden des XML für Namespace-Extraktion');
            return [];
        }

        $xpath = new DOMXPath($doc);
        $namespaces = [];

        // Root-Element Namespaces
        $root = $doc->documentElement;
        if ($root !== null) {
            foreach ($xpath->query('namespace::*', $root) as $namespace) {
                $prefix = $namespace->localName === 'xmlns' ? '' : $namespace->localName;
                $namespaces[$prefix] = $namespace->nodeValue;
            }
        }

        return $namespaces;
    }

    /**
     * Konvertiert XML zu einem assoziativen Array.
     *
     * @param string $xml Der XML-String
     * @param bool $preserveAttributes Ob Attribute beibehalten werden sollen
     * @return array Das konvertierte Array
     * @throws InvalidArgumentException Bei ungültigem XML
     */
    public static function xmlToArray(string $xml, bool $preserveAttributes = true): array {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Ungültiger XML-String');
        }

        return self::nodeToArray($doc->documentElement, $preserveAttributes);
    }

    /**
     * Konvertiert einen DOM-Knoten rekursiv zu einem Array.
     *
     * @param \DOMNode|null $node Der DOM-Knoten
     * @param bool $preserveAttributes Ob Attribute beibehalten werden sollen
     * @return mixed Das konvertierte Array oder der Wert
     */
    private static function nodeToArray(?\DOMNode $node, bool $preserveAttributes): mixed {
        if ($node === null) {
            return null;
        }

        $result = [];

        // Attribute hinzufügen
        if ($preserveAttributes && $node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $result['@' . $attribute->name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes()) {
            $children = [];

            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $text = trim($child->textContent);
                    if ($text !== '') {
                        return empty($result) ? $text : array_merge($result, ['_value' => $text]);
                    }
                } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                    $childArray = self::nodeToArray($child, $preserveAttributes);
                    $children[$child->nodeName][] = $childArray;
                }
            }

            foreach ($children as $name => $values) {
                $result[$name] = count($values) === 1 ? $values[0] : $values;
            }
        }

        return $result;
    }

    /**
     * Führt eine XPath-Abfrage auf XML aus.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck
     * @param array<string, string> $namespaces Namespace-Registrierungen (prefix => uri)
     * @return array Array von gefundenen Werten
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpath(string $xml, string $xpath, array $namespaces = []): array {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Ungültiger XML-String');
        }

        $xpathObj = new DOMXPath($doc);

        // Namespaces registrieren
        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $nodes = $xpathObj->query($xpath);
        if ($nodes === false) {
            throw new InvalidArgumentException('Ungültiger XPath-Ausdruck');
        }

        $results = [];
        foreach ($nodes as $node) {
            $results[] = $node->nodeValue;
        }

        return $results;
    }

    /**
     * Extrahiert SEPA Message ID aus CAMT oder PAIN XML.
     *
     * @param string $xml Das SEPA XML-Dokument
     * @return string|null Die Message ID oder null wenn nicht gefunden
     */
    public static function extractSepaMessageId(string $xml): ?string {
        try {
            // Automatische Namespace-Erkennung
            $namespaces = self::extractNamespaces($xml);
            $defaultNs = $namespaces[''] ?? '';

            if (empty($defaultNs)) {
                // Fallback: häufige SEPA Namespaces
                $commonNamespaces = [
                    'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02',
                    'urn:iso:std:iso:20022:tech:xsd:camt.052.001.02',
                    'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
                    'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02'
                ];

                foreach ($commonNamespaces as $ns) {
                    try {
                        $result = self::xpath($xml, '//msg:GrpHdr/msg:MsgId', ['msg' => $ns]);
                        if (!empty($result)) {
                            return $result[0];
                        }
                    } catch (InvalidArgumentException) {
                        continue;
                    }
                }
            } else {
                $result = self::xpath($xml, '//msg:GrpHdr/msg:MsgId', ['msg' => $defaultNs]);
                if (!empty($result)) {
                    return $result[0];
                }
            }

            return null;
        } catch (InvalidArgumentException $e) {
            self::logError("Fehler bei SEPA Message ID Extraktion: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validiert SEPA XML gegen die entsprechenden XSD-Schemas.
     *
     * @param string $xml Das SEPA XML-Dokument
     * @param string $schemaDir Verzeichnis mit XSD-Schema-Dateien
     * @return array{valid: bool, errors: string[], messageType: string|null}
     */
    public static function validateSepaXml(string $xml, string $schemaDir): array {
        // Message-Type aus Root-Element erkennen
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            return ['valid' => false, 'errors' => ['Ungültiger XML'], 'messageType' => null];
        }

        $root = $doc->documentElement;
        $messageType = $root?->localName;

        if ($messageType === null) {
            return ['valid' => false, 'errors' => ['Kein Root-Element gefunden'], 'messageType' => null];
        }

        // Schema-Datei basierend auf Message-Type bestimmen
        $schemaMap = [
            'BkToCstmrStmt' => 'camt.053.001.02.xsd',      // CAMT053
            'BkToCstmrAcctRpt' => 'camt.052.001.02.xsd',   // CAMT052
            'CstmrCdtTrfInitn' => 'pain.001.001.03.xsd',   // PAIN001
            'CstmrDrctDbtInitn' => 'pain.008.001.02.xsd',  // PAIN008
        ];

        $schemaFile = $schemaMap[$messageType] ?? null;
        if ($schemaFile === null) {
            return [
                'valid' => false,
                'errors' => ["Unbekannter SEPA Message-Type: {$messageType}"],
                'messageType' => $messageType
            ];
        }

        $schemaPath = rtrim($schemaDir, '/\\') . DIRECTORY_SEPARATOR . $schemaFile;
        $result = self::validateAgainstXsd($xml, $schemaPath);
        $result['messageType'] = $messageType;

        return $result;
    }

    /**
     * Konvertiert ein Array zu XML.
     *
     * @param array $array Das zu konvertierende Array
     * @param string $rootElement Name des Root-Elements
     * @param string $encoding XML-Encoding
     * @return string Der XML-String
     */
    public static function arrayToXml(array $array, string $rootElement = 'root', string $encoding = 'UTF-8'): string {
        $doc = new DOMDocument('1.0', $encoding);
        $doc->formatOutput = true;

        $root = $doc->createElement($rootElement);
        $doc->appendChild($root);

        self::arrayToXmlRecursive($array, $doc, $root);

        $xml = $doc->saveXML();
        if ($xml === false) {
            throw new RuntimeException('XML-Generierung fehlgeschlagen');
        }

        return $xml;
    }

    /**
     * Rekursive Helper-Funktion für Array zu XML Konvertierung.
     *
     * @param array $array Das Array
     * @param DOMDocument $doc Das DOM-Dokument
     * @param \DOMElement $parent Das Parent-Element
     */
    private static function arrayToXmlRecursive(array $array, DOMDocument $doc, \DOMElement $parent): void {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $element = $doc->createElement((string)$key);
                $parent->appendChild($element);
                self::arrayToXmlRecursive($value, $doc, $element);
            } else {
                $element = $doc->createElement((string)$key, htmlspecialchars((string)$value));
                $parent->appendChild($element);
            }
        }
    }

    /**
     * Entfernt XML-Kommentare aus einem XML-String.
     *
     * @param string $xml Der XML-String
     * @return string XML ohne Kommentare
     */
    public static function removeComments(string $xml): string {
        return preg_replace('/<!--.*?-->/s', '', $xml) ?? $xml;
    }

    /**
     * Komprimiert XML durch Entfernen überflüssiger Leerzeichen.
     *
     * @param string $xml Der XML-String
     * @return string Komprimiertes XML
     */
    public static function minify(string $xml): string {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            self::logError('Fehler beim XML-Minify: Ungültiges XML');
            return $xml;
        }

        $doc->preserveWhiteSpace = false;
        $minified = $doc->saveXML();

        if ($minified === false) {
            self::logError('Fehler beim XML-Minify: Speichern fehlgeschlagen');
            return $xml;
        }

        // Zusätzlich Zeilenumbrüche zwischen Elementen entfernen
        return preg_replace('/>\s+</', '><', $minified) ?? $minified;
    }
}
