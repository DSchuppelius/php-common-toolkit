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
use CommonToolkit\Entities\XML\XsdValidationResult;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Traits\ErrorLog;
use DOMDocument;
use DOMElement;
use DOMXPath;
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
        if (!File::exists($xsdFile)) {
            $error = "XSD-Schema-Datei nicht gefunden: {$xsdFile}";
            return self::logErrorAndReturn(['valid' => false, 'errors' => [$error]], $error);
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
     * Validiert XML gegen ein XSD-Schema und gibt ein typisiertes Ergebnis zurück.
     *
     * @param string $xml Der XML-String
     * @param string $xsdFile Pfad zur XSD-Schema-Datei
     * @return XsdValidationResult Typisiertes Validierungsergebnis
     */
    public static function validateAgainstXsdTyped(string $xml, string $xsdFile): XsdValidationResult {
        $result = self::validateAgainstXsd($xml, $xsdFile);

        return new XsdValidationResult(
            valid: $result['valid'],
            errors: $result['errors'],
            xsdFile: $xsdFile
        );
    }

    /**
     * Sammelt LibXML-Fehler und formatiert sie als String-Array.
     *
     * @return string[] Array von Fehlermeldungen
     */
    public static function getLibXmlErrors(): array {
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
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültiger XML-String');
        }

        $formatted = $doc->saveXML();
        if ($formatted === false) {
            self::logErrorAndThrow(RuntimeException::class, 'XML-Formatierung fehlgeschlagen');
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
            return self::logErrorAndReturn([], 'Fehler beim Laden des XML für Namespace-Extraktion');
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
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültiger XML-String');
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
     * Führt eine XPath-Abfrage aus und gibt die DOM-Nodes zurück.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck
     * @param array<string, string> $namespaces Namespace-Registrierungen (prefix => uri)
     * @return DOMElement[] Array von gefundenen DOM-Elementen
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpathNodes(string $xml, string $xpath, array $namespaces = []): array {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Ungültiger XML-String');
        }

        $xpathObj = new DOMXPath($doc);

        foreach ($namespaces as $prefix => $uri) {
            $xpathObj->registerNamespace($prefix, $uri);
        }

        $nodeList = $xpathObj->query($xpath);
        if ($nodeList === false) {
            throw new InvalidArgumentException('Ungültiger XPath-Ausdruck');
        }

        $results = [];
        foreach ($nodeList as $node) {
            if ($node instanceof DOMElement) {
                $results[] = $node;
            }
        }

        return $results;
    }

    /**
     * Führt eine XPath-Abfrage mit automatischer Namespace-Erkennung aus und gibt DOM-Nodes zurück.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck (mit 'ns:' Prefix für Namespace-Elemente)
     * @return DOMElement[] Array von gefundenen DOM-Elementen
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpathAutoNodes(string $xml, string $xpath): array {
        $namespaces = self::extractNamespaces($xml);
        $defaultNs = $namespaces[''] ?? null;

        $nsMapping = [];
        if ($defaultNs !== null) {
            $nsMapping['ns'] = $defaultNs;
        }

        foreach ($namespaces as $prefix => $uri) {
            if ($prefix !== '' && $prefix !== 'xml') {
                $nsMapping[$prefix] = $uri;
            }
        }

        return self::xpathNodes($xml, $xpath, $nsMapping);
    }

    /**
     * Führt eine XPath-Abfrage aus und gibt das erste Ergebnis zurück.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck
     * @param array<string, string> $namespaces Namespace-Registrierungen (prefix => uri)
     * @return string|null Das erste Ergebnis oder null wenn nicht gefunden
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpathFirst(string $xml, string $xpath, array $namespaces = []): ?string {
        $results = self::xpath($xml, $xpath, $namespaces);
        return $results[0] ?? null;
    }

    /**
     * Führt eine XPath-Abfrage mit automatischer Namespace-Erkennung aus.
     * 
     * Erkennt den Default-Namespace aus dem XML und registriert ihn als 'ns'.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck (mit 'ns:' Prefix für Namespace-Elemente)
     * @return array Array von gefundenen Werten
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpathAuto(string $xml, string $xpath): array {
        $namespaces = self::extractNamespaces($xml);
        $defaultNs = $namespaces[''] ?? null;

        $nsMapping = [];
        if ($defaultNs !== null) {
            $nsMapping['ns'] = $defaultNs;
        }

        // Alle anderen Namespaces auch registrieren
        foreach ($namespaces as $prefix => $uri) {
            if ($prefix !== '' && $prefix !== 'xml') {
                $nsMapping[$prefix] = $uri;
            }
        }

        return self::xpath($xml, $xpath, $nsMapping);
    }

    /**
     * Führt eine XPath-Abfrage mit automatischer Namespace-Erkennung aus und gibt das erste Ergebnis zurück.
     *
     * @param string $xml Der XML-String
     * @param string $xpath Der XPath-Ausdruck (mit 'ns:' Prefix für Namespace-Elemente)
     * @return string|null Das erste Ergebnis oder null wenn nicht gefunden
     * @throws InvalidArgumentException Bei ungültigem XML oder XPath
     */
    public static function xpathAutoFirst(string $xml, string $xpath): ?string {
        $results = self::xpathAuto($xml, $xpath);
        return $results[0] ?? null;
    }

    /**
     * Extrahiert SEPA Message ID aus CAMT oder PAIN XML.
     * 
     * Unterstützt alle gängigen ISO 20022 Formate:
     * - CAMT.052 (Intraday Report) - Versionen 001.02 bis 001.12
     * - CAMT.053 (Statement) - Versionen 001.02 bis 001.12
     * - CAMT.054 (Debit/Credit Notification) - Versionen 001.02 bis 001.12
     * - PAIN.001 (Customer Credit Transfer) - Versionen 001.03 bis 001.12
     * - PAIN.002 (Payment Status Report) - Versionen 001.03 bis 001.14
     * - PAIN.008 (Customer Direct Debit) - Versionen 001.02 bis 001.11
     *
     * @param string $xml Das SEPA XML-Dokument
     * @return string|null Die Message ID oder null wenn nicht gefunden
     */
    public static function extractSepaMessageId(string $xml): ?string {
        try {
            // Versuche zuerst mit automatischer Namespace-Erkennung
            $result = self::xpathAutoFirst($xml, '//ns:GrpHdr/ns:MsgId');
            if ($result !== null) {
                return $result;
            }

            // Fallback: Ohne Namespace (für ältere Formate)
            $result = self::xpathFirst($xml, '//GrpHdr/MsgId');
            if ($result !== null) {
                return $result;
            }

            return null;
        } catch (InvalidArgumentException $e) {
            return self::logErrorAndReturn(null, "Fehler bei SEPA Message ID Extraktion: " . $e->getMessage());
        }
    }

    /**
     * Validiert SEPA XML gegen die entsprechenden XSD-Schemas.
     * 
     * Erkennt automatisch das Schema aus dem Namespace des Dokuments.
     * Unterstützt CAMT.052, CAMT.053, CAMT.054, PAIN.001, PAIN.002, PAIN.008.
     *
     * @param string $xml Das SEPA XML-Dokument
     * @param string $schemaDir Verzeichnis mit XSD-Schema-Dateien
     * @return array{valid: bool, errors: string[], messageType: string|null, schemaVersion: string|null}
     */
    public static function validateSepaXml(string $xml, string $schemaDir): array {
        $doc = new DOMDocument();
        if (!$doc->loadXML($xml)) {
            return ['valid' => false, 'errors' => ['Ungültiger XML'], 'messageType' => null, 'schemaVersion' => null];
        }

        $root = $doc->documentElement;
        $messageType = $root?->localName;

        if ($messageType === null) {
            return ['valid' => false, 'errors' => ['Kein Root-Element gefunden'], 'messageType' => null, 'schemaVersion' => null];
        }

        // Namespace aus Root-Element extrahieren
        $namespace = $root->namespaceURI ?? $root->getAttribute('xmlns');

        // Schema-Version aus Namespace extrahieren (z.B. camt.053.001.08 aus urn:iso:std:iso:20022:tech:xsd:camt.053.001.08)
        $schemaVersion = null;
        if (preg_match('/:(camt|pain)\.\d{3}\.\d{3}\.\d{2}$/', $namespace, $matches)) {
            $schemaVersion = substr($namespace, strrpos($namespace, ':') + 1);
        }

        // Message-Type zu Schema-Prefix Mapping
        $messageTypeMap = [
            // CAMT-Formate
            'BkToCstmrStmt' => 'camt.053',       // Bank to Customer Statement
            'BkToCstmrAcctRpt' => 'camt.052',    // Bank to Customer Account Report
            'BkToCstmrDbtCdtNtfctn' => 'camt.054', // Debit/Credit Notification
            // PAIN-Formate
            'CstmrCdtTrfInitn' => 'pain.001',    // Customer Credit Transfer Initiation
            'CstmrPmtStsRpt' => 'pain.002',      // Customer Payment Status Report
            'CstmrDrctDbtInitn' => 'pain.008',   // Customer Direct Debit Initiation
            // Document-Wrapper
            'Document' => null,                  // Benötigt Schema-Version aus Namespace
        ];

        $schemaPrefix = $messageTypeMap[$messageType] ?? null;

        // Wenn Document-Wrapper, Prefix aus Schema-Version extrahieren
        if ($messageType === 'Document' && $schemaVersion !== null) {
            $schemaPrefix = substr($schemaVersion, 0, 8); // z.B. "camt.053"
        }

        if ($schemaPrefix === null && $schemaVersion === null) {
            return [
                'valid' => false,
                'errors' => ["Unbekannter SEPA Message-Type: {$messageType}"],
                'messageType' => $messageType,
                'schemaVersion' => null
            ];
        }

        // Schema-Datei bestimmen
        $schemaFile = $schemaVersion !== null
            ? "{$schemaVersion}.xsd"
            : null;

        // Fallback: Versuche Schema-Datei im Verzeichnis zu finden
        if ($schemaFile === null && $schemaPrefix !== null) {
            $schemaDir = rtrim($schemaDir, '/\\');
            $pattern = $schemaDir . DIRECTORY_SEPARATOR . "{$schemaPrefix}.001.*.xsd";
            $files = glob($pattern);

            if (!empty($files)) {
                // Neueste Version verwenden
                rsort($files);
                $schemaFile = basename($files[0]);
            }
        }

        if ($schemaFile === null) {
            return [
                'valid' => false,
                'errors' => ["Kein passendes XSD-Schema gefunden für: {$messageType}"],
                'messageType' => $messageType,
                'schemaVersion' => $schemaVersion
            ];
        }

        $schemaPath = rtrim($schemaDir, '/\\') . DIRECTORY_SEPARATOR . $schemaFile;
        $result = self::validateAgainstXsd($xml, $schemaPath);
        $result['messageType'] = $messageType;
        $result['schemaVersion'] = $schemaVersion;

        return $result;
    }

    /**
     * Konvertiert ein Array zu XML.
     *
     * @param array $array Das zu konvertierende Array
     * @param string $rootElement Name des Root-Elements
     * @param string $encoding XML-Encoding
     * @param string|null $namespaceUri Optionale Namespace-URI für das Root-Element
     * @param string|null $namespacePrefix Optionaler Namespace-Prefix
     * @return string Der XML-String
     */
    public static function arrayToXml(
        array $array,
        string $rootElement = 'root',
        string $encoding = 'UTF-8',
        ?string $namespaceUri = null,
        ?string $namespacePrefix = null
    ): string {
        $doc = new DOMDocument('1.0', $encoding);
        $doc->formatOutput = true;

        // Root-Element mit optionalem Namespace erstellen
        if ($namespaceUri !== null) {
            $qualifiedName = $namespacePrefix !== null
                ? "{$namespacePrefix}:{$rootElement}"
                : $rootElement;
            $root = $doc->createElementNS($namespaceUri, $qualifiedName);
        } else {
            $root = $doc->createElement($rootElement);
        }
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
     * Unterstützt Attribute via '@'-Prefix und Textinhalt via '_value'.
     * Beispiel: ['element' => ['@id' => '123', '_value' => 'text']]
     *        => <element id="123">text</element>
     *
     * @param array $array Das Array
     * @param DOMDocument $doc Das DOM-Dokument
     * @param DOMElement $parent Das Parent-Element
     */
    private static function arrayToXmlRecursive(array $array, DOMDocument $doc, DOMElement $parent): void {
        foreach ($array as $key => $value) {
            $keyStr = (string) $key;

            // Attribute (mit @ Prefix) direkt am Parent setzen
            if (str_starts_with($keyStr, '@')) {
                $attrName = substr($keyStr, 1);
                $parent->setAttribute($attrName, (string) $value);
                continue;
            }

            // Textinhalt (_value) direkt als Text-Node hinzufügen
            if ($keyStr === '_value') {
                $parent->appendChild($doc->createTextNode((string) $value));
                continue;
            }

            if (is_array($value)) {
                // Prüfe ob es ein numerisches Array ist (mehrere gleichnamige Elemente)
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        $element = $doc->createElement($keyStr);
                        $parent->appendChild($element);
                        if (is_array($item)) {
                            self::arrayToXmlRecursive($item, $doc, $element);
                        } else {
                            $element->appendChild($doc->createTextNode((string) $item));
                        }
                    }
                } else {
                    $element = $doc->createElement($keyStr);
                    $parent->appendChild($element);
                    self::arrayToXmlRecursive($value, $doc, $element);
                }
            } else {
                $element = $doc->createElement($keyStr);
                $element->appendChild($doc->createTextNode((string) $value));
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
            return self::logErrorAndReturn($xml, 'Fehler beim XML-Minify: Ungültiges XML');
        }

        $doc->preserveWhiteSpace = false;
        $minified = $doc->saveXML();

        if ($minified === false) {
            return self::logErrorAndReturn($xml, 'Fehler beim XML-Minify: Speichern fehlgeschlagen');
        }

        // Zusätzlich Zeilenumbrüche zwischen Elementen entfernen
        return preg_replace('/>\s+</', '><', $minified) ?? $minified;
    }
}