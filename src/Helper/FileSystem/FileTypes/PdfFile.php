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
            throw new Exception("Fehler beim Abrufen der PDF-Metadaten für $file");
        }

        $metadata = [];
        foreach ($output as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $metadata[trim($key)] = trim($value);
            }
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
        return isset($metadata['Encrypted']) && $metadata['Encrypted'] === 'yes';
    }

    public static function isValid(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $command = Shell::getPlatformSpecificCommand(
            sprintf("mutool info %s 2>&1 | grep error", escapeshellarg($file)),
            sprintf('pdfinfo %s 2>&1 | findstr /R "Syntax.Error"', escapeshellarg($file))
        );
        $output = [];
        $resultCode = 0;

        return File::exists($file) && Shell::executeShellCommand($command, $output, $resultCode, false, 1) && empty($output);
    }
}
