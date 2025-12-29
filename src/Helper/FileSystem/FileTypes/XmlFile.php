<?php
/*
 * Created on   : Fri Oct 25 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XmlFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\Data\XmlHelper;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use DOMDocument;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;

class XmlFile extends HelperAbstract {

    /**
     * Prüft, ob die `DOMDocument`-Erweiterung verfügbar ist.
     *
     * @throws Exception Falls die Erweiterung nicht installiert ist.
     */
    private static function checkDomExtension(): void {
        if (!extension_loaded('dom')) {
            self::logError("Die DOMDocument-Erweiterung ist nicht verfügbar. XML-Funktionen können nicht verwendet werden.");
            throw new Exception("Die DOMDocument-Erweiterung ist nicht verfügbar. XML-Funktionen können nicht verwendet werden.");
        }
    }

    /**
     * Liest Metadaten aus einer XML-Datei.
     *
     * @param string $file Der Dateipfad
     * @return array{RootElement: string, Encoding: string, Version: string} Metadaten
     * @throws FileNotFoundException Falls die Datei nicht existiert.
     * @throws Exception Falls das XML nicht geladen werden kann.
     */
    public static function getMetaData(string $file): array {
        self::checkDomExtension();

        $resolvedFile = self::resolveFile($file);
        $xml = new DOMDocument();

        libxml_use_internal_errors(true);
        if (!$xml->load($resolvedFile)) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = trim($error->message);
            }
            libxml_clear_errors();

            $errorMsg = "Fehler beim Laden der XML-Datei: $file - " . implode(', ', $errorMessages);
            self::logError($errorMsg);
            throw new Exception($errorMsg);
        }

        $metadata = [
            'RootElement' => $xml->documentElement?->tagName ?? 'Unbekannt',
            'Encoding'    => $xml->encoding ?? 'Unbekannt',
            'Version'     => $xml->xmlVersion ?? 'Unbekannt'
        ];

        libxml_clear_errors();
        return $metadata;
    }

    /**
     * Prüft, ob eine XML-Datei wohlgeformt ist.
     *
     * @param string $file Der Dateipfad
     * @return bool True wenn wohlgeformt, false andernfalls
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function isWellFormed(string $file): bool {
        self::checkDomExtension();

        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::isValid($content);
        } catch (Exception $e) {
            self::logError("Fehler beim Lesen der Datei {$file}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validiert eine XML-Datei gegen ein XSD-Schema.
     *
     * @param string $file Der Dateipfad
     * @param string $xsdSchema Pfad zur XSD-Schema-Datei
     * @return bool True wenn gültig, false andernfalls
     * @throws FileNotFoundException Wenn eine der Dateien nicht existiert
     */
    public static function isValid(string $file, string $xsdSchema): bool {
        self::checkDomExtension();

        // Beide Dateien validieren
        $resolvedFile = self::resolveFile($file);
        $resolvedSchema = self::resolveFile($xsdSchema);

        try {
            $content = File::read($resolvedFile);
            $result = XmlHelper::validateAgainstXsd($content, $resolvedSchema);

            if (!$result['valid']) {
                foreach ($result['errors'] as $error) {
                    self::logError("XML-Validierung $file: $error");
                }
            } else {
                self::logInfo("XML-Datei $file entspricht dem XSD-Schema $xsdSchema.");
            }

            return $result['valid'];
        } catch (Exception $e) {
            self::logError("Fehler bei XML-Validierung {$file}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Zählt die Anzahl der Datensätze in einer XML-Datei.
     *
     * @param string $file Der Pfad zur XML-Datei.
     * @param string|null $elementName Der zu zählende Elementname (optional).
     *                                 Wird keiner angegeben, werden alle Kindelemente des Root gezählt.
     * @return int Anzahl der gefundenen Elemente.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     * @throws Exception Wenn die XML-Datei nicht geladen werden kann.
     */
    public static function countRecords(string $file, ?string $elementName = null): int {
        self::checkDomExtension();

        $resolvedFile = self::resolveFile($file);
        $xml = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$xml->load($resolvedFile)) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = trim($error->message);
            }
            libxml_clear_errors();

            $errorMsg = "Fehler beim Laden der XML-Datei: $file - " . implode(', ', $errorMessages);
            self::logError($errorMsg);
            throw new Exception($errorMsg);
        }

        $root = $xml->documentElement;
        if (!$root) {
            self::logError("Kein Root-Element gefunden in $file");
            return 0;
        }

        if ($elementName !== null) {
            $count = $root->getElementsByTagName($elementName)->length;
            self::logInfo("XML-Datei $file enthält $count <$elementName>-Element(e).");
        } else {
            $count = 0;
            foreach ($root->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $count++;
                }
            }
            self::logInfo("XML-Datei $file enthält $count direkte Kindelement(e) unter <$root->tagName>.");
        }

        libxml_clear_errors();
        return $count;
    }

    /**
     * Formatiert eine XML-Datei für bessere Lesbarkeit (Pretty-Print).
     *
     * @param string $file Der Dateipfad
     * @return string Der formatierte XML-Inhalt
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     * @throws Exception Bei Lesefehlern
     */
    public static function prettyFormat(string $file): string {
        $content = File::read(self::resolveFile($file));
        return XmlHelper::prettyFormat($content);
    }

    /**
     * Extrahiert alle Namespaces aus einer XML-Datei.
     *
     * @param string $file Der Dateipfad
     * @return array<string, string> Namespace-Mappings (prefix => uri)
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function extractNamespaces(string $file): array {
        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::extractNamespaces($content);
        } catch (Exception $e) {
            self::logError("Fehler beim Extrahieren der Namespaces aus {$file}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Konvertiert eine XML-Datei zu einem assoziativen Array.
     *
     * @param string $file Der Dateipfad
     * @param bool $preserveAttributes Ob Attribute beibehalten werden sollen
     * @return array Das konvertierte Array
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function toArray(string $file, bool $preserveAttributes = true): array {
        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::xmlToArray($content, $preserveAttributes);
        } catch (Exception $e) {
            self::logError("Fehler beim Konvertieren der XML-Datei {$file} zu Array: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Führt eine XPath-Abfrage auf einer XML-Datei aus.
     *
     * @param string $file Der Dateipfad
     * @param string $xpath Der XPath-Ausdruck
     * @param array<string, string> $namespaces Namespace-Registrierungen
     * @return array Array von gefundenen Werten
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function xpath(string $file, string $xpath, array $namespaces = []): array {
        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::xpath($content, $xpath, $namespaces);
        } catch (Exception $e) {
            self::logError("Fehler bei XPath-Abfrage auf {$file}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validiert SEPA XML-Datei gegen entsprechende XSD-Schemas.
     *
     * @param string $file Der Dateipfad
     * @param string $schemaDir Verzeichnis mit XSD-Schema-Dateien
     * @return array{valid: bool, errors: string[], messageType: string|null}
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function validateSepaXml(string $file, string $schemaDir): array {
        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::validateSepaXml($content, $schemaDir);
        } catch (Exception $e) {
            self::logError("Fehler bei SEPA-Validierung von {$file}: " . $e->getMessage());
            return ['valid' => false, 'errors' => [$e->getMessage()], 'messageType' => null];
        }
    }

    /**
     * Extrahiert SEPA Message ID aus XML-Datei.
     *
     * @param string $file Der Dateipfad
     * @return string|null Die Message ID oder null wenn nicht gefunden
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function extractSepaMessageId(string $file): ?string {
        try {
            $content = File::read(self::resolveFile($file));
            return XmlHelper::extractSepaMessageId($content);
        } catch (Exception $e) {
            self::logError("Fehler beim Extrahieren der SEPA Message ID aus {$file}: " . $e->getMessage());
            return null;
        }
    }
}
