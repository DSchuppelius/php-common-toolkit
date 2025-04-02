<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PdfFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;

class PdfFile extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/pdf_executables.json';

    public static function getMetaData(string $file): array {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $command = self::getConfiguredCommand("pdfinfo", ["[INPUT]" => escapeshellarg($file)]);
        $output = [];
        $resultCode = 0;

        if (empty($command)) {
            self::logError("pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
            throw new Exception("pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
        }

        Shell::executeShellCommand($command, $output, $resultCode);

        if ($resultCode !== 0) {
            self::logError("Fehler beim Abrufen der PDF-Metadaten für $file.");
            throw new Exception("Fehler beim Abrufen der PDF-Metadaten für $file");
        }

        $metadata = [];
        foreach ($output as $line) {
            if (strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $metadata[trim($parts[0])] = trim($parts[1]);
                }
            }
        }

        if (empty($metadata)) {
            self::logWarning("Keine Metadaten für Datei $file gefunden.");
        }

        return $metadata;
    }

    public static function isEncrypted(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $metadata = self::getMetaData($file);

        if (isset($metadata['Encrypted'])) {
            $encryptedValue = strtolower($metadata['Encrypted']);
            return strpos($encryptedValue, 'yes') !== false;
        }

        return false;
    }

    public static function isValid(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::logError("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $command = self::getConfiguredCommand("valid-pdf", ["[INPUT]" => escapeshellarg($file)]);
        $output = [];
        $resultCode = 0;

        if (empty($command)) {
            self::logError("mutool bzw. pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
            throw new Exception("mutool bzw. pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
        } elseif (!Shell::executeShellCommand($command, $output, $resultCode, false, 1)) {
            self::logError("Fehler bei der PDF-Validierung für $file.");
            return false;
        }

        // Wenn die Ausgabe Fehler enthält, ist die PDF ungültig
        foreach ($output as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'Syntax.Error') !== false) {
                self::logError("Syntaxfehler in PDF erkannt: $file");
                return false;
            }
        }

        return true;
    }
}
