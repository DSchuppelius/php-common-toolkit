<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : File.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Contracts\Interfaces\FileSystemInterface;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileExistsException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FileNotWrittenException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use finfo;

class File extends HelperAbstract implements FileSystemInterface {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/common_executables.json';

    /**
     * Holt den realen Pfad aus einer Pfadangabe und loggt diese, falls der Pfad angepasst wurde.
     */
    public static function getRealPath(string $file): string {
        self::setLogger();

        if (self::exists($file)) {
            $realPath = realpath($file);

            if ($realPath === false) {
                self::$logger->debug("Konnte Pfad nicht auflösen: $file");
                return $file;
            }

            // Falls realpath() den Pfad ändert, loggen
            if ($realPath !== $file) {
                self::$logger->info("Pfad wurde normalisiert: $file -> $realPath");
            }
            return $realPath;
        }

        self::$logger->debug("Pfad existiert nicht, unverändert zurückgeben: $file");
        return $file;
    }


    public static function mimeType(string $filename): string|false {
        self::setLogger();

        $result = false;
        if (function_exists('finfo_open')) {
            self::$logger->info("Nutze finfo für Erkennung des mime-types: $filename");
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $result = $finfo->file($filename);
        } elseif (function_exists('mime_content_type')) {
            self::$logger->info("Nutze mime_content_type für Erkennung des mime-types: $filename");
            $result = @mime_content_type($filename);
        }

        if (false === $result && PHP_OS_FAMILY === 'Linux') {
            self::$logger->warning("Nutze Shell für Erkennung des mime-types: $filename");
            $result = self::mimeTypeByShell($filename);
        }

        return $result;
    }

    private static function mimeTypeByShell(string $file): string|false {
        self::setLogger();

        $file = self::getRealPath($file);

        $result = false;

        if (!self::exists($file)) {
            self::$logger->error("Datei existiert nicht: $file");
            return $result;
        }
        $command = self::getConfiguredCommand("mimetype", ["[INPUT]" => escapeshellarg($file)]);
        $output = [];
        $success = Shell::executeShellCommand($command, $output);

        if (!$success || empty($output)) {
            self::$logger->error("Problem bei der Bestimmung des MIME-Typs für $file");
            throw new Exception("Problem bei der Bestimmung des MIME-Typs für $file");
        }

        if (!empty($output)) {
            $result = trim(implode("\n", $output));
            self::$logger->info("MIME-Typ für $file: " . $result);
        }
        return $result;
    }

    public static function exists(string $file): bool {
        self::setLogger();

        self::$logger->debug("Prüfe Existenz der Datei: $file");

        return file_exists($file);
    }

    public static function copy(string $sourceFile, string $destinationFile, bool $overwrite = true): void {
        self::setLogger();

        $sourceFile = self::getRealPath($sourceFile);
        $destinationFile = self::getRealPath($destinationFile);

        if (!self::exists($sourceFile)) {
            self::$logger->error("Die Datei $sourceFile existiert nicht");
            throw new FileNotFoundException("Die Datei $sourceFile existiert nicht");
        }

        if (self::exists($destinationFile)) {
            if (!$overwrite) {
                self::$logger->info("Die Datei $destinationFile existiert bereits und wird nicht überschrieben.");
                return;
            }
            self::$logger->warning("Die Datei $destinationFile existiert bereits und wird überschrieben.");
        }

        if (!@copy($sourceFile, $destinationFile)) {
            if (self::exists($destinationFile) && filesize($destinationFile) === 0) {
                unlink($destinationFile);
                self::$logger->warning("0-Byte-Datei $destinationFile nach fehlgeschlagenem Kopieren gelöscht.");
            }

            self::$logger->info("Zweiter Versuch, die Datei $sourceFile nach $destinationFile zu kopieren.");
            if (!@copy($sourceFile, $destinationFile)) {
                self::$logger->error("Fehler beim erneuten Kopieren der Datei von $sourceFile nach $destinationFile");
                throw new FileNotWrittenException("Fehler beim erneuten Kopieren der Datei von $sourceFile nach $destinationFile");
            }
        }

        self::$logger->info("Datei von $sourceFile nach $destinationFile kopiert");
    }

    public static function create(string $file, int $permissions = 0644, string $content = ''): void {
        self::setLogger();

        $file = self::getRealPath($file);

        if (self::exists($file)) {
            self::$logger->error("Die Datei $file existiert bereits");
            throw new FileNotFoundException("Die Datei $file existiert bereits");
        }

        if (file_put_contents($file, $content) === false) {
            self::$logger->error("Fehler beim Erstellen der Datei $file");
            throw new FileNotWrittenException("Fehler beim Erstellen der Datei $file");
        }

        if (!chmod($file, $permissions)) {
            self::$logger->error("Fehler beim Setzen der Berechtigungen $permissions für die Datei $file");
            throw new Exception("Fehler beim Setzen der Berechtigungen für die Datei $file");
        }

        self::$logger->info("Datei erstellt: $file mit Berechtigungen $permissions");
    }

    public static function rename(string $oldName, string $newName): void {
        self::setLogger();

        $oldName = self::getRealPath($oldName);
        $newName = self::getRealPath($newName);

        if (!self::exists($oldName)) {
            self::$logger->error("Die Datei $oldName existiert nicht");
            throw new FileNotFoundException("Die Datei $oldName existiert nicht");
        } elseif (self::exists($newName)) {
            self::$logger->error("Die Datei $newName existiert bereits");
            throw new FileExistsException("Die Datei $newName existiert bereits");
        }

        if ($newName == basename($newName)) {
            $newName = dirname($oldName) . DIRECTORY_SEPARATOR . $newName;
        }

        if (!rename($oldName, $newName)) {
            self::$logger->error("Fehler beim Umbenennen der Datei von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen der Datei von $oldName nach $newName");
        }

        self::$logger->debug("Datei umbenannt von $oldName zu $newName");
    }

    public static function move(string $sourceFile, string $destinationFolder, ?string $destinationFileName = null, bool $overwrite = true): void {
        self::setLogger();

        $sourceFile = self::getRealPath($sourceFile);
        $destinationFolder = self::getRealPath($destinationFolder);

        $destinationFile = $destinationFolder . DIRECTORY_SEPARATOR . (is_null($destinationFileName) ? basename($sourceFile) : $destinationFileName);

        if (!self::exists($sourceFile)) {
            self::$logger->error("Die Datei $sourceFile existiert nicht");
            throw new FileNotFoundException("Die Datei $sourceFile existiert nicht");
        } elseif (!self::exists($destinationFolder)) {
            self::$logger->error("Das Zielverzeichnis $destinationFolder existiert nicht");
            throw new FolderNotFoundException("Das Zielverzeichnis $destinationFolder existiert nicht");
        }

        if (self::exists($destinationFile)) {
            if (!$overwrite) {
                self::$logger->info("Die Datei $destinationFile existiert bereits und wird nicht überschrieben.");
                return;
            }
            self::$logger->warning("Die Datei $destinationFile existiert bereits und wird überschrieben.");
        }

        if (!@rename($sourceFile, $destinationFile)) {
            if (self::exists($destinationFile) && filesize($destinationFile) === 0) {
                unlink($destinationFile);
                self::$logger->warning("0-Byte-Datei $destinationFile nach fehlgeschlagenem Verschieben gelöscht.");
            }

            self::$logger->info("Zweiter Versuch, die Datei $sourceFile nach $destinationFile zu verschieben.");
            if (!@rename($sourceFile, $destinationFile)) {
                self::$logger->error("Fehler beim erneuten Verschieben der Datei von $sourceFile nach $destinationFile");
                throw new FileNotWrittenException("Fehler beim erneuten Verschieben der Datei von $sourceFile nach $destinationFile");
            }
        }

        self::$logger->debug("Datei von $sourceFile zu $destinationFile verschoben");
    }

    public static function delete(string $file): void {
        self::setLogger();

        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::$logger->notice("Die zu löschende Datei: $file existiert nicht");
            return;
        }

        if (!unlink($file)) {
            self::$logger->error("Fehler beim Löschen der Datei: $file");
            throw new Exception("Fehler beim Löschen der Datei: $file");
        }

        self::$logger->debug("Datei gelöscht: $file");
    }

    public static function size(string $file): int {
        self::setLogger();

        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::$logger->error("Die Datei $file existiert nicht");
            throw new FileNotFoundException("Die Datei $file existiert nicht");
        }

        return filesize($file);
    }

    public static function read(string $file): string {
        self::setLogger();

        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::$logger->error("Die Datei $file existiert nicht");
            throw new FileNotFoundException("Die Datei $file existiert nicht");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            self::$logger->error("Fehler beim Lesen der Datei $file");
            throw new Exception("Fehler beim Lesen der Datei $file");
        }

        self::$logger->debug("Datei erfolgreich gelesen: $file");
        return $content;
    }

    public static function write(string $file, string $data): void {
        self::setLogger();

        $file = self::getRealPath($file);

        if (file_put_contents($file, $data) === false) {
            self::$logger->error("Fehler beim Schreiben in die Datei $file");
            throw new FileNotWrittenException("Fehler beim Schreiben in die Datei $file");
        }

        self::$logger->info("Daten in Datei gespeichert: $file");
    }

    public static function isReadable(string $file): bool {
        self::setLogger();

        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::$logger->error("Die Datei $file existiert nicht");
            return false;
        }

        if (!is_readable($file)) {
            self::$logger->error("Die Datei $file ist nicht lesbar");
            return false;
        }

        return true;
    }

    public static function isReady(string $file, bool $logging = true): bool {
        self::setLogger();

        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            if ($logging) {
                self::$logger->error("Die Datei $file existiert nicht");
            }
            return false;
        }

        $handle = @fopen($file, 'r');
        if ($handle === false) {
            return false;
        }
        fclose($handle);
        return true;
    }

    public static function wait4Ready(string $file, int $timeout = 30): bool {
        self::setLogger();

        $file = self::getRealPath($file);

        $start = time();
        if (!self::exists($file)) {
            self::$logger->info("Datei $file existiert nicht.");
            return false;
        }

        while (!self::isReady($file, false)) {
            if (self::exists($file)) {
                if (time() - $start >= $timeout) {
                    self::$logger->error("Timeout beim Warten auf die Datei $file");
                    return false;
                }

                sleep(1);
            } else {
                self::$logger->info("Datei $file existiert nicht mehr.");
                return false;
            }
        }
        return true;
    }
}
