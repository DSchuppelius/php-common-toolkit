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

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Contracts\Interfaces\FileSystemInterface;
use CommonToolkit\Helper\PlatformHelper;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileExistsException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FileNotWrittenException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use finfo;

class File extends ConfiguredHelperAbstract implements FileSystemInterface {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/common_executables.json';

    /**
     * Holt den realen Pfad aus einer Pfadangabe und loggt diese, falls der Pfad angepasst wurde.
     */
    public static function getRealPath(string $file): string {
        if (self::exists($file)) {
            $realPath = realpath($file);

            if ($realPath === false) {
                self::logDebug("Konnte Pfad nicht auflösen: $file");
                return $file;
            }

            // Falls realpath() den Pfad ändert, loggen
            if ($realPath !== $file) {
                self::logInfo("Pfad wurde normalisiert: " . self::shortenByCommonPath($file, $realPath) . " -> $realPath");
            }
            return $realPath;
        }

        self::logDebug("Pfad existiert nicht, unverändert zurückgeben: $file");
        return $file;
    }


    public static function mimeType(string $file): string|false {
        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            return false;
        }

        $file = self::getRealPath($file);
        $result = false;

        if (class_exists('finfo')) {
            self::logInfo("Nutze finfo für Erkennung des MIME-Typs: $file");
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $result = $finfo->file($file);
        } elseif (function_exists('mime_content_type')) {
            self::logInfo("Nutze mime_content_type für Erkennung des MIME-Typs: $file");
            $result = @mime_content_type($file);
        }

        if ($result === false && PlatformHelper::isLinux()) {
            self::logWarning("Nutze Shell für Erkennung des MIME-Typs: $file");
            $result = self::mimeTypeByShell($file);
        }

        // Falls keine Methode den MIME-Typ bestimmen konnte
        if ($result === false) {
            self::logError("Konnte MIME-Typ nicht bestimmen: $file");
        }

        return $result;
    }

    private static function mimeTypeByShell(string $file): string|false {
        $file = self::getRealPath($file);
        $result = false;

        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            return $result;
        }
        $command = self::getConfiguredCommand("mimetype", ["[INPUT]" => escapeshellarg($file)]);
        $output = [];
        $success = Shell::executeShellCommand($command, $output);

        if (!$success || empty($output)) {
            self::logError("Problem bei der Bestimmung des MIME-Typs für $file");
            throw new Exception("Problem bei der Bestimmung des MIME-Typs für $file");
        }

        if (!empty($output)) {
            $result = trim(implode("\n", $output));
            self::logInfo("MIME-Typ für $file: " . $result);
        }
        return $result;
    }

    private static function shortenByCommonPath(string $originalPath, string $normalizedPath): string {
        // Normalisieren für einheitliche Darstellung
        $originalParts = explode(DIRECTORY_SEPARATOR, $originalPath);
        $normalizedParts = explode(DIRECTORY_SEPARATOR, $normalizedPath);

        // Finde den gemeinsamen Teil beider Pfade
        $commonParts = [];
        foreach ($originalParts as $index => $part) {
            if (isset($normalizedParts[$index]) && $normalizedParts[$index] === $part) {
                $commonParts[] = $part;
            } else {
                break;
            }
        }

        // Entferne den gemeinsamen Teil aus dem ursprünglichen Pfad
        $relativePath = array_slice($originalParts, count($commonParts));

        // Falls der gekürzte Pfad leer ist, einfach nur ein Punkt anzeigen
        return empty($relativePath) ? '.' : '...' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativePath);
    }

    public static function exists(string $file): bool {
        self::setLogger();
        $result = file_exists($file);
        if (!$result) {
            self::logDebug("Existenzprüfung der Datei: $file -> false");
        }

        return $result;
    }

    public static function copy(string $sourceFile, string $destinationFile, bool $overwrite = true): void {
        $sourceFile = self::getRealPath($sourceFile);
        $destinationFile = self::getRealPath($destinationFile);

        if (!self::exists($sourceFile)) {
            self::logError("Die Datei $sourceFile existiert nicht");
            throw new FileNotFoundException("Die Datei $sourceFile existiert nicht");
        }

        if (self::exists($destinationFile)) {
            if (!$overwrite) {
                self::logInfo("Die Datei $destinationFile existiert bereits und wird nicht überschrieben.");
                return;
            }
            self::logWarning("Die Datei $destinationFile existiert bereits und wird überschrieben.");
        }

        if (!@copy($sourceFile, $destinationFile)) {
            if (self::exists($destinationFile) && filesize($destinationFile) === 0) {
                unlink($destinationFile);
                self::logWarning("0-Byte-Datei $destinationFile nach fehlgeschlagenem Kopieren gelöscht.");
            }

            self::logInfo("Zweiter Versuch, die Datei $sourceFile nach $destinationFile zu kopieren.");
            if (!@copy($sourceFile, $destinationFile)) {
                self::logError("Fehler beim erneuten Kopieren der Datei von $sourceFile nach $destinationFile");
                throw new FileNotWrittenException("Fehler beim erneuten Kopieren der Datei von $sourceFile nach $destinationFile");
            }
        }

        self::logInfo("Datei von $sourceFile nach $destinationFile kopiert");
    }

    public static function create(string $file, int $permissions = 0644, string $content = ''): void {
        $file = self::getRealPath($file);

        if (self::exists($file)) {
            self::logError("Die Datei $file existiert bereits");
            throw new FileNotFoundException("Die Datei $file existiert bereits");
        }

        if (file_put_contents($file, $content) === false) {
            self::logError("Fehler beim Erstellen der Datei $file");
            throw new FileNotWrittenException("Fehler beim Erstellen der Datei $file");
        }

        if (!chmod($file, $permissions)) {
            self::logError("Fehler beim Setzen der Berechtigungen $permissions für die Datei $file");
            throw new Exception("Fehler beim Setzen der Berechtigungen für die Datei $file");
        }

        self::logInfo("Datei erstellt: $file mit Berechtigungen $permissions");
    }

    public static function rename(string $oldName, string $newName): void {
        $oldName = self::getRealPath($oldName);
        $newName = self::getRealPath($newName);

        if (!self::exists($oldName)) {
            self::logError("Die Datei $oldName existiert nicht");
            throw new FileNotFoundException("Die Datei $oldName existiert nicht");
        } elseif (self::exists($newName)) {
            self::logError("Die Datei $newName existiert bereits");
            throw new FileExistsException("Die Datei $newName existiert bereits");
        }

        if ($newName == basename($newName)) {
            $newName = dirname($oldName) . DIRECTORY_SEPARATOR . $newName;
        }

        if (!rename($oldName, $newName)) {
            self::logError("Fehler beim Umbenennen der Datei von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen der Datei von $oldName nach $newName");
        }

        self::logDebug("Datei umbenannt von $oldName zu $newName");
    }

    public static function move(string $sourceFile, string $destinationFolder, ?string $destinationFileName = null, bool $overwrite = true): void {
        $sourceFile = self::getRealPath($sourceFile);
        $destinationFolder = self::getRealPath($destinationFolder);

        $destinationFile = $destinationFolder . DIRECTORY_SEPARATOR . (is_null($destinationFileName) ? basename($sourceFile) : $destinationFileName);

        if (!self::exists($sourceFile)) {
            self::logError("Die Datei $sourceFile existiert nicht");
            throw new FileNotFoundException("Die Datei $sourceFile existiert nicht");
        } elseif (!self::exists($destinationFolder)) {
            self::logError("Das Zielverzeichnis $destinationFolder existiert nicht");
            throw new FolderNotFoundException("Das Zielverzeichnis $destinationFolder existiert nicht");
        }

        if (self::exists($destinationFile)) {
            if (!$overwrite) {
                self::logInfo("Die Datei $destinationFile existiert bereits und wird nicht überschrieben.");
                return;
            }
            self::logWarning("Die Datei $destinationFile existiert bereits und wird überschrieben.");
        }

        if (!@rename($sourceFile, $destinationFile)) {
            if (self::exists($destinationFile) && filesize($destinationFile) === 0) {
                unlink($destinationFile);
                self::logWarning("0-Byte-Datei $destinationFile nach fehlgeschlagenem Verschieben gelöscht.");
            }

            self::logInfo("Zweiter Versuch, die Datei $sourceFile nach $destinationFile zu verschieben.");
            if (!@rename($sourceFile, $destinationFile)) {
                self::logError("Fehler beim erneuten Verschieben der Datei von $sourceFile nach $destinationFile");
                throw new FileNotWrittenException("Fehler beim erneuten Verschieben der Datei von $sourceFile nach $destinationFile");
            }
        }

        self::logDebug("Datei von $sourceFile zu $destinationFile verschoben");
    }

    public static function delete(string $file): void {
        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::logNotice("Die zu löschende Datei: $file existiert nicht");
            return;
        }

        if (!unlink($file)) {
            self::logError("Fehler beim Löschen der Datei: $file");
            throw new Exception("Fehler beim Löschen der Datei: $file");
        }

        self::logDebug("Datei gelöscht: $file");
    }

    public static function size(string $file): int {
        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::logError("Die Datei $file existiert nicht");
            throw new FileNotFoundException("Die Datei $file existiert nicht");
        }

        return filesize($file);
    }

    public static function read(string $file): string {
        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::logError("Die Datei $file existiert nicht");
            throw new FileNotFoundException("Die Datei $file existiert nicht");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            self::logError("Fehler beim Lesen der Datei $file");
            throw new Exception("Fehler beim Lesen der Datei $file");
        }

        self::logDebug("Datei erfolgreich gelesen: $file");
        return $content;
    }

    public static function write(string $file, string $data): void {
        $file = self::getRealPath($file);

        if (file_put_contents($file, $data) === false) {
            self::logError("Fehler beim Schreiben in die Datei $file");
            throw new FileNotWrittenException("Fehler beim Schreiben in die Datei $file");
        }

        self::logInfo("Daten in Datei gespeichert: $file");
    }

    public static function isReadable(string $file): bool {
        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            self::logError("Die Datei $file existiert nicht");
            return false;
        }

        if (!is_readable($file)) {
            self::logError("Die Datei $file ist nicht lesbar");
            return false;
        }

        return true;
    }

    public static function isReady(string $file, bool $logging = true): bool {
        $file = self::getRealPath($file);

        if (!self::exists($file)) {
            if ($logging) {
                self::logError("Die Datei $file existiert nicht");
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
        $file = self::getRealPath($file);

        $start = time();
        if (!self::exists($file)) {
            self::logInfo("Datei $file existiert nicht.");
            return false;
        }

        while (!self::isReady($file, false)) {
            if (self::exists($file)) {
                if (time() - $start >= $timeout) {
                    self::logError("Timeout beim Warten auf die Datei $file");
                    return false;
                }

                sleep(1);
            } else {
                self::logInfo("Datei $file existiert nicht mehr.");
                return false;
            }
        }
        return true;
    }
}
