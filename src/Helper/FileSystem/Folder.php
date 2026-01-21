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
use DirectoryIterator;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Folder extends HelperAbstract implements FileSystemInterface {
    use RealPathTrait;

    /**
     * Prüft, ob ein Pfad durch die open_basedir-Einschränkung blockiert wird.
     *
     * @param string $path Der zu prüfende Pfad.
     * @return bool True, wenn der Pfad durch open_basedir blockiert wird, andernfalls false.
     */
    public static function isBlockedByOpenBasedir(string $path): bool {
        $openBasedir = ini_get('open_basedir');

        // Wenn open_basedir nicht gesetzt ist, ist alles erlaubt
        if (empty($openBasedir)) {
            return false;
        }

        // Absoluten Pfad ermitteln (ohne Symlink-Auflösung, da realpath bei Blockierung null zurückgibt)
        $absolutePath = $path;
        if (!preg_match('#^(/|[a-zA-Z]:)#', $path)) {
            $cwd = getcwd();
            if ($cwd === false) {
                self::logWarning("getcwd() fehlgeschlagen, kann open_basedir-Prüfung nicht durchführen für: $path");
                return false;
            }
            $absolutePath = $cwd . DIRECTORY_SEPARATOR . $path;
        }

        // Pfad normalisieren (. und .. auflösen)
        $absolutePath = self::normalizePathForOpenBasedir($absolutePath);

        // open_basedir kann mehrere Pfade enthalten, getrennt durch : (Linux) oder ; (Windows)
        $separator = DIRECTORY_SEPARATOR === '\\' ? ';' : ':';
        $allowedPaths = explode($separator, $openBasedir);

        foreach ($allowedPaths as $allowedPath) {
            $allowedPath = trim($allowedPath);
            if (empty($allowedPath)) {
                continue;
            }

            // Erlaubten Pfad normalisieren
            $normalizedAllowed = self::normalizePathForOpenBasedir($allowedPath);

            // Prüfen, ob der absolute Pfad mit dem erlaubten Pfad beginnt
            if (str_starts_with($absolutePath, $normalizedAllowed)) {
                return false;
            }
        }

        self::logWarning("Zugriff auf '$path' durch open_basedir blockiert. Erlaubte Pfade: $openBasedir");
        return true;
    }

    /**
     * Normalisiert einen Pfad für die open_basedir-Prüfung (löst . und .. auf, ohne realpath zu verwenden).
     *
     * @param string $path Der zu normalisierende Pfad.
     * @return string Der normalisierte Pfad.
     */
    private static function normalizePathForOpenBasedir(string $path): string {
        // Backslashes durch Slashes ersetzen für Konsistenz
        $path = str_replace('\\', '/', $path);

        // Doppelte Slashes entfernen
        $path = preg_replace('#/+#', '/', $path);

        // Pfadteile verarbeiten
        $parts = explode('/', $path);
        $result = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                if (!empty($result) && end($result) !== '') {
                    array_pop($result);
                }
            } elseif ($part !== '.' && $part !== '') {
                $result[] = $part;
            } elseif ($part === '' && empty($result)) {
                // Root-Slash beibehalten
                $result[] = '';
            }
        }

        $normalizedPath = implode('/', $result);

        // Trailing Slash hinzufügen, wenn es ein Verzeichnis ist (mit @ um Warnings zu unterdrücken)
        if (@is_dir($path) && !str_ends_with($normalizedPath, '/')) {
            $normalizedPath .= '/';
        }

        return $normalizedPath;
    }

    /**
     * Überprüft, ob ein Verzeichnis existiert.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return bool True, wenn das Verzeichnis existiert, andernfalls false.
     */
    public static function exists(string $directory): bool {
        // Windows-reservierte Gerätenamen ignorieren (auch auf Linux für Samba-Kompatibilität)
        if (File::isWindowsReservedName($directory)) {
            return self::logDebugAndReturn(false, "Windows-reservierter Gerätename ignoriert: $directory");
        }

        // open_basedir-Prüfung (Logging erfolgt bereits in isBlockedByOpenBasedir)
        if (self::isBlockedByOpenBasedir($directory)) {
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
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger Zielverzeichnisname (Windows-reservierter Name): $destinationDirectory");
        }

        if (!self::exists($sourceDirectory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Das Verzeichnis $sourceDirectory existiert nicht");
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
                self::logErrorAndThrow(Exception::class, "Fehler beim Erstellen des Verzeichnisses: $directory");
            }
            self::logDebug("Verzeichnis erstellt: $directory mit Berechtigungen $permissions");
        } else {
            self::logDebug("Verzeichnis existiert bereits: $directory");
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
            self::logErrorAndThrow(FolderNotFoundException::class, "Das Verzeichnis $oldName existiert nicht");
        }

        if (!rename($oldName, $newName)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Umbenennen des Verzeichnisses von $oldName nach $newName");
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
            self::logErrorAndThrow(FolderNotFoundException::class, "Das Verzeichnis $directory existiert nicht");
        }

        if ($recursive) {
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $file) {
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    self::delete($path, $recursive);
                } else {
                    unlink($path);
                    self::logDebug("Datei gelöscht: $path");
                }
            }
        }

        if (!rmdir($directory)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Löschen des Verzeichnisses $directory");
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
            self::logErrorAndThrow(FolderNotFoundException::class, "Das Verzeichnis $sourceDirectory existiert nicht");
        }

        if (!rename($sourceDirectory, $destinationDirectory)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Verschieben des Verzeichnisses von $sourceDirectory nach $destinationDirectory");
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
            return self::logErrorAndReturn([], "Das Verzeichnis $directory existiert nicht");
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

    /**
     * Berechnet die Gesamtgröße eines Verzeichnisses in Bytes.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Rekursiv die Größe berechnen (Standard: true).
     * @return int Die Größe in Bytes.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function size(string $directory, bool $recursive = true): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $size = 0;
        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return self::logDebugAndReturn($size, "Verzeichnisgröße berechnet: $directory = $size Bytes");
    }

    /**
     * Prüft ob ein Verzeichnis leer ist.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return bool True wenn das Verzeichnis leer ist.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function isEmpty(string $directory): bool {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $iterator = new DirectoryIterator($directory);
        foreach ($iterator as $item) {
            if (!$item->isDot()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Leert ein Verzeichnis (löscht alle Inhalte, behält das Verzeichnis).
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse rekursiv leeren (Standard: true).
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     * @throws Exception Wenn ein Fehler beim Löschen auftritt.
     */
    public static function clean(string $directory, bool $recursive = true): void {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $iterator = new DirectoryIterator($directory);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $path = $item->getPathname();
            if ($item->isDir()) {
                if ($recursive) {
                    self::delete($path, true);
                }
            } else {
                File::delete($path);
            }
        }

        self::logInfo("Verzeichnis geleert: $directory");
    }

    /**
     * Zählt die Anzahl der Dateien in einem Verzeichnis.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse einbeziehen (Standard: false).
     * @param array $extensions Nur bestimmte Dateierweiterungen zählen (leer = alle).
     * @return int Anzahl der Dateien.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function fileCount(string $directory, bool $recursive = false, array $extensions = []): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $count = 0;
        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (empty($extensions)) {
                $count++;
            } else {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($ext, array_map('strtolower', $extensions), true)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Zählt die Anzahl der Unterverzeichnisse.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse rekursiv zählen (Standard: false).
     * @return int Anzahl der Unterverzeichnisse.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function folderCount(string $directory, bool $recursive = false): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $count = 0;
        $iterator = $recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            )
            : new DirectoryIterator($directory);
        foreach ($iterator as $item) {
            if ($iterator instanceof \DirectoryIterator && $item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Findet Dateien nach einem Glob-Pattern.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param string $pattern Das Glob-Pattern (z.B. '*.txt', '*.{jpg,png}').
     * @param bool $recursive Rekursiv suchen (Standard: false).
     * @return string[] Array mit gefundenen Dateipfaden.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function findByPattern(string $directory, string $pattern, bool $recursive = false): array {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        if (!$recursive) {
            $results = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE);
            return $results !== false ? $results : [];
        }

        $results = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $results[] = $file->getPathname();
            }
        }

        return $results;
    }

    /**
     * Gibt die neueste Datei im Verzeichnis zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse einbeziehen (Standard: false).
     * @param array $extensions Nur bestimmte Dateierweiterungen (leer = alle).
     * @return string|null Pfad zur neuesten Datei oder null wenn leer.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function getNewest(string $directory, bool $recursive = false, array $extensions = []): ?string {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $newestFile = null;
        $newestTime = 0;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!empty($extensions)) {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (!in_array($ext, array_map('strtolower', $extensions), true)) {
                    continue;
                }
            }

            $mtime = $file->getMTime();
            if ($mtime > $newestTime) {
                $newestTime = $mtime;
                $newestFile = $file->getPathname();
            }
        }

        return $newestFile;
    }

    /**
     * Gibt die älteste Datei im Verzeichnis zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse einbeziehen (Standard: false).
     * @param array $extensions Nur bestimmte Dateierweiterungen (leer = alle).
     * @return string|null Pfad zur ältesten Datei oder null wenn leer.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function getOldest(string $directory, bool $recursive = false, array $extensions = []): ?string {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $oldestFile = null;
        $oldestTime = PHP_INT_MAX;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!empty($extensions)) {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (!in_array($ext, array_map('strtolower', $extensions), true)) {
                    continue;
                }
            }

            $mtime = $file->getMTime();
            if ($mtime < $oldestTime) {
                $oldestTime = $mtime;
                $oldestFile = $file->getPathname();
            }
        }

        return $oldestFile;
    }

    /**
     * Gibt die größte Datei im Verzeichnis zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse einbeziehen (Standard: false).
     * @return string|null Pfad zur größten Datei oder null wenn leer.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function getLargest(string $directory, bool $recursive = false): ?string {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $largestFile = null;
        $largestSize = 0;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $size = $file->getSize();
            if ($size > $largestSize) {
                $largestSize = $size;
                $largestFile = $file->getPathname();
            }
        }

        return $largestFile;
    }

    /**
     * Gibt den Zeitpunkt der letzten Modifikation eines Verzeichnisses zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return int Unix-Timestamp der letzten Modifikation.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function modifiedTime(string $directory): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $time = filemtime($directory);
        if ($time === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen der Modifikationszeit: $directory");
        }

        return $time;
    }

    /**
     * Gibt den Erstellungszeitpunkt eines Verzeichnisses zurück (plattformabhängig).
     * Hinweis: Auf Unix-Systemen wird oft die ctime (inode change time) zurückgegeben.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return int Unix-Timestamp der Erstellung.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function createdTime(string $directory): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $time = filectime($directory);
        if ($time === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen der Erstellungszeit: $directory");
        }

        return $time;
    }

    /**
     * Gibt den Zeitpunkt des letzten Zugriffs auf ein Verzeichnis zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return int Unix-Timestamp des letzten Zugriffs.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function accessTime(string $directory): int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $time = fileatime($directory);
        if ($time === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen der Zugriffszeit: $directory");
        }

        return $time;
    }

    /**
     * Überprüft, ob ein Verzeichnis lesbar ist.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return bool True, wenn das Verzeichnis lesbar ist, andernfalls false.
     */
    public static function isReadable(string $directory): bool {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            return self::logErrorAndReturn(false, "Verzeichnis existiert nicht: $directory");
        }
        if (!is_readable($directory)) {
            return self::logErrorAndReturn(false, "Verzeichnis ist nicht lesbar: $directory");
        }
        return true;
    }

    /**
     * Überprüft, ob ein Verzeichnis beschreibbar ist.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @return bool True, wenn das Verzeichnis beschreibbar ist, andernfalls false.
     */
    public static function isWritable(string $directory): bool {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            return self::logErrorAndReturn(false, "Verzeichnis existiert nicht: $directory");
        }
        if (!is_writable($directory)) {
            return self::logErrorAndReturn(false, "Verzeichnis ist nicht beschreibbar: $directory");
        }
        return true;
    }

    /**
     * Gibt die kleinste Datei im Verzeichnis zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $recursive Unterverzeichnisse einbeziehen (Standard: false).
     * @param bool $includeEmpty Leere Dateien (0 Bytes) einbeziehen (Standard: false).
     * @return string|null Pfad zur kleinsten Datei oder null wenn leer.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function getSmallest(string $directory, bool $recursive = false, bool $includeEmpty = false): ?string {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $smallestFile = null;
        $smallestSize = PHP_INT_MAX;

        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $size = $file->getSize();
            if (!$includeEmpty && $size === 0) {
                continue;
            }

            if ($size < $smallestSize) {
                $smallestSize = $size;
                $smallestFile = $file->getPathname();
            }
        }

        return $smallestFile;
    }

    /**
     * Gibt die Berechtigungen eines Verzeichnisses zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $octal Als Oktalzahl zurückgeben (Standard: true), sonst als Integer.
     * @return string|int Berechtigungen als Oktalstring (z.B. '0755') oder Integer.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function permissions(string $directory, bool $octal = true): string|int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $perms = fileperms($directory);
        if ($perms === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen der Berechtigungen: $directory");
        }

        // Nur die letzten 4 Oktalziffern (Berechtigungen ohne Dateityp)
        $perms = $perms & 0777;

        return $octal ? sprintf('%04o', $perms) : $perms;
    }

    /**
     * Gibt den Eigentümer eines Verzeichnisses zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $asName Als Benutzername zurückgeben (Standard: true), sonst als UID.
     * @return string|int Benutzername oder UID.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function owner(string $directory, bool $asName = true): string|int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $uid = fileowner($directory);
        if ($uid === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen des Eigentümers: $directory");
        }

        if (!$asName) {
            return $uid;
        }

        // posix_getpwuid nur auf Unix verfügbar
        if (function_exists('posix_getpwuid')) {
            $info = posix_getpwuid($uid);
            if ($info !== false && isset($info['name'])) {
                return $info['name'];
            }
        }

        return $uid;
    }

    /**
     * Gibt die Gruppe eines Verzeichnisses zurück.
     *
     * @param string $directory Der Pfad des Verzeichnisses.
     * @param bool $asName Als Gruppenname zurückgeben (Standard: true), sonst als GID.
     * @return string|int Gruppenname oder GID.
     * @throws FolderNotFoundException Wenn das Verzeichnis nicht existiert.
     */
    public static function group(string $directory, bool $asName = true): string|int {
        $directory = self::getRealPath($directory);
        if (!self::exists($directory)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Verzeichnis nicht gefunden: $directory");
        }

        $gid = filegroup($directory);
        if ($gid === false) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Fehler beim Abrufen der Gruppe: $directory");
        }

        if (!$asName) {
            return $gid;
        }

        // posix_getgrgid nur auf Unix verfügbar
        if (function_exists('posix_getgrgid')) {
            $info = posix_getgrgid($gid);
            if ($info !== false && isset($info['name'])) {
                return $info['name'];
            }
        }

        return $gid;
    }
}