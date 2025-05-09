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
     * @throws FileNotFoundException Falls die Datei nicht existiert.
     * @throws Exception Falls das XML nicht geladen werden kann.
     */
    public static function getMetaData(string $file): array {
        self::checkDomExtension();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $xml = new DOMDocument();

        libxml_use_internal_errors(true);
        if (!$xml->load($file)) {
            self::logLibxmlErrors("Fehler beim Laden der XML-Datei: $file");
            throw new Exception("Fehler beim Laden der XML-Datei: $file");
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
     * @param string $file
     * @return boolean
     */
    public static function isWellFormed(string $file): bool {
        self::checkDomExtension();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $xml = new DOMDocument();
        libxml_use_internal_errors(true);
        $isWellFormed = $xml->load($file);

        if (!$isWellFormed) {
            self::logLibxmlErrors("XML ist nicht wohlgeformt: $file");
        }

        libxml_clear_errors();
        return $isWellFormed;
    }

    /**
     * Validiert eine XML-Datei gegen ein XSD-Schema.
     *
     * @param string $file
     * @param string $xsdSchema
     * @return boolean
     */
    public static function isValid(string $file, string $xsdSchema): bool {
        self::checkDomExtension();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        if (!File::exists($xsdSchema)) {
            self::logError("XSD-Schema $xsdSchema nicht gefunden.");
            throw new FileNotFoundException("XSD-Schema $xsdSchema nicht gefunden.");
        }

        $xml = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$xml->load($file)) {
            self::logLibxmlErrors("Fehler beim Laden der XML-Datei für Validierung: $file");
            return false;
        }

        $isValid = $xml->schemaValidate($xsdSchema);

        if (!$isValid) {
            self::logLibxmlErrors("XML-Datei $file entspricht nicht dem XSD-Schema $xsdSchema");
        } else {
            self::logInfo("XML-Datei $file entspricht dem XSD-Schema $xsdSchema.");
        }

        libxml_clear_errors();
        return $isValid;
    }

    /**
     * Protokolliert libxml-Fehler.
     *
     * @param string $errorMessage
     */
    private static function logLibxmlErrors(string $errorMessage): void {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            self::logError("$errorMessage - libxml Fehler: " . trim($error->message));
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

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $xml = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$xml->load($file)) {
            self::logLibxmlErrors("Fehler beim Laden der XML-Datei: $file");
            throw new Exception("Fehler beim Laden der XML-Datei: $file");
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
}