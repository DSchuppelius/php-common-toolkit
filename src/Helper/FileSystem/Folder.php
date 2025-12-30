<?php
/*
 * Created on   : Sat Mar 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Folder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Contracts\Interfaces\FileSystemInterface;
use CommonToolkit\Traits\RealPathTrait;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use InvalidArgumentException;

class Folder extends HelperAbstract implements FileSystemInterface {
    use RealPathTrait;
    /**
     * Überprüft, ob ein Verzeichnis existiert.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return bool True, wenn das Verzeichnis existiert, andernfalls false.
     */
    public static function exists(string $directory): bool {
        // Windows-reservierte Gerätenamen ignorieren (auch auf Linux für Samba-Kompatibilität)
        if (File::isWindowsReservedName($directory)) {
            self::logDebug("Windows-reservierter Gerätename ignoriert: $directory");
            return false;
        }

        $result = is_dir($directory);

        if (!$result) {
            self::logDebug("Existenzprüfung des Verzeichnisses: $directory -> false");
        }

        return $result;
    }

    /**
     * Kopiert ein Verzeichnis und dessen Inhalt in ein anderes Verzeichnis.
     *
     * @param string $sourceDirectory Das Quellverzeichnis.
     * @param string $destinationDirectory Das Zielverzeichnis.
     * @param bool $recursive Ob rekursiv kopiert werden soll (Standard: false).
     * @throws FolderNotFoundException Wenn das Quellverzeichnis nicht existiert.
     * @throws Exception Wenn ein Fehler beim Kopieren auftritt.
     */
    public static function copy(string $sourceDirectory, string $destinationDirectory, bool $recursive = false): void {
        $sourceDirectory = self::getRealPath($sourceDirectory);
        $destinationDirectory = self::getRealPath($destinationDirectory);

        if (File::isWindowsReservedName($destinationDirectory)) {
            self::logError("Ungültiger Zielverzeichnisname (Windows-reservierter Name): $destinationDirectory");
            throw new InvalidArgumentException("Ungültiger Verzeichnisname: " . basename($destinationDirectory) . " ist ein Windows-reservierter Gerätename");
        }

        if (!self::exists($sourceDirectory)) {
            self::logError("Das Verzeichnis $sourceDirectory existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $sourceDirectory existiert nicht");
        }

        if (!self::exists($destinationDirectory)) {
            self::create($destinationDirectory);
        }

        $files = array_diff(scandir($sourceDirectory), ['.', '..']);

        foreach ($files as $file) {
            $sourcePath = $sourceDirectory . DIRECTORY_SEPARATOR . $file;
            $destinationPath = $destinationDirectory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                if ($recursive) {
                    self::copy($sourcePath, $destinationPath, true);
                } else {
                    self::create($destinationPath);
                }
            } else {
                File::copy($sourcePath, $destinationPath);
            }
        }

        self::logInfo("Verzeichnis kopiert von $sourceDirectory nach $destinationDirectory");
    }

    /**
     * Erstellt ein Verzeichnis mit den angegebenen Berechtigungen.
     *
     * @param string $directory Der Pfad des zu erstellenden Verzeichnisses.
     * @param int $permissions Die Berechtigungen für das Verzeichnis (Standard: 0755).
     * @param bool $recursive Ob rekursiv erstellt werden soll (Standard: false).
     * @throws Exception Wenn ein Fehler beim Erstellen des Verzeichnisses auftritt.
     */
    public static function create(string $directory, int $permissions = 0755, bool $recursive = false): void {
        $directory = self::getRealPath($directory);

        self::validateNotReservedName($directory);

        if (!self::exists($directory)) {
            if (!mkdir($directory, $permissions, $recursive)) {
                self::logError("Fehler beim Erstellen des Verzeichnisses: $directory");
                throw new Exception("Fehler beim Erstellen des Verzeichnisses $directory");
            }
            self::logDebug("Verzeichnis erstellt: $directory mit Berechtigungen $permissions");
        } else {
            self::logInfo("Verzeichnis existiert bereits: $directory");
        }
    }

    /**
     * Benennt ein Verzeichnis um.
     *
     * @param string $oldName Der alte Name des Verzeichnisses.
     * @param string $newName Der neue Name des Verzeichnisses.
     * @throws FolderNotFoundException Wenn das alte Verzeichnis nicht existiert.
     * @throws Exception Wenn ein Fehler beim Umbenennen auftritt.
     */
    public static function rename(string $oldName, string $newName): void {
        $oldName = self::getRealPath($oldName);
        $newName = self::getRealPath($newName);

        self::validateNotReservedName($newName);

        if (!self::exists($oldName)) {
            self::logError("Das Verzeichnis $oldName existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $oldName existiert nicht");
        }

        if (!rename($oldName, $newName)) {
            self::logError("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
        }

        self::logInfo("Verzeichnis umbenannt von $oldName zu $newName");
    }

    /**
     * Löscht ein Verzeichnis und alle darin enthaltenen Dateien und Unterverzeichnisse.
     *
     * @param string $directory Das zu löschende Verzeichnis.
     * @param bool $recursive Ob rekursiv gelöscht werden soll.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     * @throws Exception Wenn ein Fehler beim Löschen auftritt.
     */
    public static function delete(string $directory, bool $recursive = false): void {
        $directory = self::getRealPath($directory);

        if (!self::exists($directory)) {
            self::logError("Das Verzeichnis $directory existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $directory existiert nicht");
        }

        if ($recursive) {
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $file) {
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    self::delete($path, $recursive);
                } else {
                    unlink($path);
                    self::logInfo("Datei gelöscht: $path");
                }
            }
        }

        if (!rmdir($directory)) {
            self::logError("Fehler beim Löschen des Verzeichnisses $directory");
            throw new Exception("Fehler beim Löschen des Verzeichnisses $directory");
        }

        self::logInfo("Verzeichnis gelöscht: $directory");
    }

    /**
     * Verschiebt ein Verzeichnis von einem Ort zu einem anderen.
     *
     * @param string $sourceDirectory Das Quellverzeichnis.
     * @param string $destinationDirectory Das Zielverzeichnis.
     * @throws FolderNotFoundException Wenn das Quellverzeichnis nicht existiert.
     * @throws Exception Wenn ein Fehler beim Verschieben auftritt.
     */
    public static function move(string $sourceDirectory, string $destinationDirectory): void {
        $sourceDirectory = self::getRealPath($sourceDirectory);
        $destinationDirectory = self::getRealPath($destinationDirectory);

        self::validateNotReservedName($destinationDirectory);

        if (!self::exists($sourceDirectory)) {
            self::logError("Das Verzeichnis $sourceDirectory existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $sourceDirectory existiert nicht");
        }

        if (!rename($sourceDirectory, $destinationDirectory)) {
            self::logError("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
            throw new Exception("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
        }

        self::logInfo("Verzeichnis verschoben von $sourceDirectory nach $destinationDirectory");
    }

    /**
     * Gibt alle Unterverzeichnisse eines Verzeichnisses zurück.
     *
     * @param string $directory Das Verzeichnis, in dem nach Unterverzeichnissen gesucht werden soll.
     * @param bool $recursive Ob rekursiv in Unterverzeichnissen gesucht werden soll.
     * @return array Ein Array mit den gefundenen Unterverzeichnissen.
     */
    public static function get(string $directory, bool $recursive = false): array {
        $directory = self::getRealPath($directory);

        if (!self::exists($directory)) {
            self::logError("Das Verzeichnis $directory existiert nicht");
            return [];
        }

        $result = [];
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $result[] = $path;
                if ($recursive) {
                    $result = array_merge($result, self::get($path, true));
                }
            }
        }

        return $result;
    }

    /**
     * Überprüft, ob der angegebene Pfad ein absoluter Pfad ist.
     *
     * @param string $path Der zu überprüfende Pfad.
     * @return bool True, wenn der Pfad absolut ist, andernfalls false.
     */
    public static function isAbsolutePath(string $path): bool {
        return File::isAbsolutePath($path);
    }
}