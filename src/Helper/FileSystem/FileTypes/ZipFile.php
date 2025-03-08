<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ZipFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Exceptions\FileNotFoundException;
use Exception;
use ZipArchive;

class ZipFile extends HelperAbstract {
    public static function create(array $files, string $destination): bool {
        self::setLogger();
        $zip = new ZipArchive();

        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            self::$logger->error("Fehler beim Erstellen des ZIP-Archivs: $destination");
            throw new Exception("Fehler beim Erstellen des ZIP-Archivs: $destination");
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                self::$logger->error("Datei nicht gefunden: $file");
                continue;
            }

            $zip->addFile($file, basename($file));
        }

        $zip->close();
        self::$logger->info("ZIP-Archiv erfolgreich erstellt: $destination");

        return true;
    }

    public static function extract(string $file, string $destinationFolder, bool $deleteSourceFile = true): void {
        self::setLogger();
        $zip = new ZipArchive();

        if ($zip->open($file) === true) {
            $zip->extractTo($destinationFolder);
            $zip->close();
            self::$logger->info("ZIP-Datei erfolgreich extrahiert: $file nach $destinationFolder");

            if ($deleteSourceFile) {
                File::delete($file);
            }
        } else {
            self::$logger->error("Fehler beim Extrahieren der ZIP-Datei: $file");
            throw new Exception("Fehler beim Extrahieren der ZIP-Datei: $file");
        }
    }

    public static function isValid(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei $file nicht gefunden.");
            throw new FileNotFoundException("Datei $file nicht gefunden.");
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);

        if ($result === true) {
            self::$logger->info("ZIP-Datei ist gültig: $file");
            $zip->close();
            return true;
        } else {
            switch ($result) {
                case ZipArchive::ER_NOZIP:
                    self::$logger->error("Die Datei ist keine gültige ZIP-Datei: $file");
                    break;
                case ZipArchive::ER_INCONS:
                    self::$logger->error("Das ZIP-Archiv ist inkonsistent: $file");
                    break;
                case ZipArchive::ER_MEMORY:
                    self::$logger->error("Speicherproblem beim Öffnen des ZIP-Archivs: $file");
                    break;
                default:
                    self::$logger->error("Unbekannter Fehler beim Öffnen des ZIP-Archivs: $file");
            }
            return false;
        }
    }
}
