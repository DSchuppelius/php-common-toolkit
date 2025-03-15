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
        self::setLogger();

        if (!extension_loaded('dom')) {
            self::$logger->error("Die DOMDocument-Erweiterung ist nicht verfügbar. XML-Funktionen können nicht verwendet werden.");
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
            self::$logger->error("Datei $file nicht gefunden.");
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
     * Prüft, ob die XML-Datei wohlgeformt ist.
     */
    public static function isWellFormed(string $file): bool {
        self::checkDomExtension();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
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
     * Validiert eine XML-Datei anhand eines XSD-Schemas.
     */
    public static function isValid(string $file, string $xsdSchema): bool {
        self::checkDomExtension();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        if (!File::exists($xsdSchema)) {
            self::$logger->error("XSD-Schema $xsdSchema nicht gefunden.");
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
            self::$logger->info("XML-Datei $file entspricht dem XSD-Schema $xsdSchema.");
        }

        libxml_clear_errors();
        return $isValid;
    }

    /**
     * Loggt alle Fehler von libxml und gibt sie zurück.
     */
    private static function logLibxmlErrors(string $errorMessage): void {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            self::$logger->error("$errorMessage - libxml Fehler: " . trim($error->message));
        }
    }
}
