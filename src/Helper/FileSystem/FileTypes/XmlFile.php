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
use ERRORToolkit\Exceptions\FileNotFoundException;

class XmlFile extends HelperAbstract {
    public static function getMetaData(string $file): array {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $xml = new DOMDocument();
        $metadata = [];

        libxml_use_internal_errors(true);
        if (!$xml->load($file)) {
            self::$logger->error("Fehler beim Laden der XML-Datei: $file");
            throw new Exception("Fehler beim Laden der XML-Datei: $file");
        }

        $metadata['RootElement'] = $xml->documentElement->tagName;
        $metadata['Encoding'] = $xml->encoding;
        $metadata['Version'] = $xml->xmlVersion;

        libxml_clear_errors();
        return $metadata;
    }

    public static function isWellFormed(string $file): bool {
        $xml = new DOMDocument();

        libxml_use_internal_errors(true);
        $isWellFormed = $xml->load($file);
        libxml_clear_errors();

        return $isWellFormed;
    }

    public static function isValid(string $file, string $xsdSchema): bool {
        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $xml = new DOMDocument();
        libxml_use_internal_errors(true);

        $xml->load($file);
        $isValid = $xml->schemaValidate($xsdSchema);

        if (!$isValid) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "Fehler: " . $error->message . "\n";
            }
            libxml_clear_errors();
        }

        return $isValid;
    }
}
