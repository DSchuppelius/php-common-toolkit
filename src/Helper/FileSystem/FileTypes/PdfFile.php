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

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileNotFoundException;
use Exception;

class PdfFile extends HelperAbstract {

    public static function getMetaData(string $file): array {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $command = sprintf("pdfinfo %s", escapeshellarg($file));
        $output = [];
        $resultCode = 0;

        Shell::executeShellCommand($command, $output, $resultCode);

        if ($resultCode !== 0) {
            self::$logger->error("Fehler beim Abrufen der PDF-Metadaten für $file.");
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
            self::$logger->warning("Keine Metadaten für Datei $file gefunden.");
        }

        return $metadata;
    }

    public static function isEncrypted(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
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
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $command = Shell::getPlatformSpecificCommand(
            sprintf("mutool info %s 2>&1 | grep -i 'error'", escapeshellarg($file)),
            sprintf('pdfinfo %s 2>&1 | findstr /R "Syntax.Error"', escapeshellarg($file))
        );

        $output = [];
        $resultCode = 0;
        $executionSuccess = Shell::executeShellCommand($command, $output, $resultCode, false, 1);

        if (!$executionSuccess) {
            self::$logger->error("Fehler bei der PDF-Validierung für $file.");
            return false;
        }

        // Wenn die Ausgabe Fehler enthält, ist die PDF ungültig
        foreach ($output as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'Syntax.Error') !== false) {
                self::$logger->error("Syntaxfehler in PDF erkannt: $file");
                return false;
            }
        }

        return true;
    }
}
