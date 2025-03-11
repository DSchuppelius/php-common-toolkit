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
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;

class Folder extends HelperAbstract implements FileSystemInterface {

    public static function exists(string $directory): bool {
        $directory = File::getRealPath($directory);
        self::setLogger();
        $exists = is_dir($directory);
        self::$logger->debug("Überprüfung ob Verzeichnis existiert: $directory - " . ($exists ? 'Ja' : 'Nein'));
        return $exists;
    }

    public static function copy(string $sourceDirectory, string $destinationDirectory, bool $recursive = false): void {
        self::setLogger();
        $sourceDirectory = File::getRealPath($sourceDirectory);
        $destinationDirectory = File::getRealPath($destinationDirectory);

        if (!self::exists($sourceDirectory)) {
            self::$logger->error("Das Verzeichnis $sourceDirectory existiert nicht");
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

        self::$logger->info("Verzeichnis kopiert von $sourceDirectory nach $destinationDirectory");
    }

    public static function create(string $directory, int $permissions = 0755): void {
        self::setLogger();
        $directory = File::getRealPath($directory);

        if (!self::exists($directory)) {
            if (!mkdir($directory, $permissions, true)) {
                self::$logger->error("Fehler beim Erstellen des Verzeichnisses: $directory");
                throw new Exception("Fehler beim Erstellen des Verzeichnisses $directory");
            }
            self::$logger->debug("Verzeichnis erstellt: $directory mit Berechtigungen $permissions");
        } else {
            self::$logger->info("Verzeichnis existiert bereits: $directory");
        }
    }

    public static function rename(string $oldName, string $newName): void {
        self::setLogger();
        $oldName = File::getRealPath($oldName);
        $newName = File::getRealPath($newName);

        if (!self::exists($oldName)) {
            self::$logger->error("Das Verzeichnis $oldName existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $oldName existiert nicht");
        }

        if (!rename($oldName, $newName)) {
            self::$logger->error("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
            throw new Exception("Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
        }

        self::$logger->info("Verzeichnis umbenannt von $oldName zu $newName");
    }

    public static function delete(string $directory, bool $recursive = false): void {
        self::setLogger();
        $directory = File::getRealPath($directory);

        if (!self::exists($directory)) {
            self::$logger->error("Das Verzeichnis $directory existiert nicht");
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
                    self::$logger->info("Datei gelöscht: $path");
                }
            }
        }

        if (!rmdir($directory)) {
            self::$logger->error("Fehler beim Löschen des Verzeichnisses $directory");
            throw new Exception("Fehler beim Löschen des Verzeichnisses $directory");
        }

        self::$logger->info("Verzeichnis gelöscht: $directory");
    }

    public static function move(string $sourceDirectory, string $destinationDirectory): void {
        self::setLogger();
        $sourceDirectory = File::getRealPath($sourceDirectory);
        $destinationDirectory = File::getRealPath($destinationDirectory);

        if (!self::exists($sourceDirectory)) {
            self::$logger->error("Das Verzeichnis $sourceDirectory existiert nicht");
            throw new FolderNotFoundException("Das Verzeichnis $sourceDirectory existiert nicht");
        }

        if (!rename($sourceDirectory, $destinationDirectory)) {
            self::$logger->error("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
            throw new Exception("Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
        }

        self::$logger->info("Verzeichnis verschoben von $sourceDirectory nach $destinationDirectory");
    }

    public static function get(string $directory, bool $recursive = false): array {
        $directory = File::getRealPath($directory);
        $result = [];
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if ($recursive) {
                    $result = array_merge($result, self::get($path, true));
                }
                $result[] = $path;
            }
        }

        return $result;
    }
}
