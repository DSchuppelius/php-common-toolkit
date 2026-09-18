<?php
/*
 * Created on   : Mon Oct 14 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Files.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;

class Files extends HelperAbstract {
    /**
     * Prüft, ob mindestens ein Pfad durch die open_basedir-Einschränkung blockiert wird.
     *
     * @param list<string> $paths Ein Array mit Pfaden.
     * @return bool True, wenn mindestens ein Pfad blockiert ist, andernfalls false.
     */
    public static function isBlockedByOpenBasedir(array $paths): bool {
        foreach ($paths as $path) {
            if (Folder::isBlockedByOpenBasedir($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt alle Pfade zurück, die durch open_basedir blockiert werden.
     *
     * @param list<string> $paths Ein Array mit Pfaden.
     * @return list<string> Ein Array mit den blockierten Pfaden.
     */
    public static function getBlockedByOpenBasedir(array $paths): array {
        $blocked = [];
        foreach ($paths as $path) {
            if (Folder::isBlockedByOpenBasedir($path)) {
                $blocked[] = $path;
            }
        }
        return $blocked;
    }

    /**
     * Gibt den absoluten Pfad zu mehreren Dateien zurück.
     *
     * @param list<string> $files Ein Array mit Dateipfaden.
     * @return list<string> Ein Array mit den absoluten Pfaden der Dateien.
     */
    public static function getRealpath(array $files): array {
        $realPaths = [];
        foreach ($files as $file) {
            $realPaths[] = File::getRealpath($file);
        }
        return $realPaths;
    }

    /**
     * Überprüft, ob alle angegebenen Dateien existieren.
     *
     * @param list<string> $files Ein Array mit Dateipfaden.
     * @return bool True, wenn alle Dateien existieren, sonst false.
     */
    public static function exists(array $files): bool {
        foreach ($files as $file) {
            if (!File::exists($file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Kopiert mehrere Dateien.
     *
     * @param array<string, string> $filePairs Ein Array mit Quell- und Ziel-Dateipaaren.
     * @param bool $overwrite Ob vorhandene Dateien überschrieben werden sollen.
     */
    public static function copy(array $filePairs, bool $overwrite = true): void {
        foreach ($filePairs as $sourceFile => $destinationFile) {
            File::copy($sourceFile, $destinationFile, $overwrite);
        }
    }

    /**
     * Verschiebt mehrere Dateien.
     *
     * @param array<string, string> $filePairs Ein Array mit alten und neuen Dateinamen.
     * @param bool $overwrite Ob vorhandene Dateien überschrieben werden sollen.
     */
    public static function move(array $filePairs, bool $overwrite = true): void {
        foreach ($filePairs as $oldName => $newName) {
            File::move($oldName, dirname($newName), basename($newName), $overwrite);
        }
    }

    /**
     * Benennt mehrere Dateien um.
     *
     * @param array<string, string> $filePairs Ein Array mit alten und neuen Dateinamen.
     */
    public static function rename(array $filePairs): void {
        foreach ($filePairs as $oldName => $newName) {
            File::rename($oldName, $newName);
        }
    }

    /**
     * Löscht mehrere Dateien.
     *
     * @param list<string> $files Ein Array mit Dateipfaden.
     */
    public static function delete(array $files): void {
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    /**
     * Liest Daten aus mehreren Dateien.
     *
     * @param list<string> $files Ein Array mit Dateipfaden.
     * @return array<string, string> Ein Array mit den Dateipfaden als Schlüsseln und den gelesenen Daten als Werten.
     */
    public static function read(array $files): array {
        $fileContents = [];
        foreach ($files as $file) {
            $fileContents[$file] = File::read($file);
        }
        return $fileContents;
    }

    /**
     * Schreibt Daten in mehrere Dateien.
     *
     * @param array<string, string> $fileData Ein Array mit Dateipfaden als Schlüsseln und den zu schreibenden Daten als Werten.
     */
    public static function write(array $fileData): void {
        foreach ($fileData as $file => $data) {
            File::write($file, $data);
        }
    }

    /**
     * Gibt alle Dateien in einem Verzeichnis zurück, die den angegebenen Kriterien entsprechen.
     *
     * @param string $directory Das Verzeichnis, in dem nach Dateien gesucht werden soll.
     * @param bool $recursive Ob rekursiv in Unterverzeichnissen gesucht werden soll.
     * @param list<string> $fileTypes Ein Array von Dateitypen (z.B. ['txt', 'jpg']), die berücksichtigt werden sollen.
     * @param string|null $regexPattern Ein regulärer Ausdruck, der auf den Dateinamen angewendet wird.
     * @param string|null $contains Ein String, der im Dateinamen enthalten sein muss.
     * @param bool $followSymlinks Ob rekursiv in verlinkte Verzeichnisse abgestiegen wird
     *                             (Standard: false; dann mit Schutz gegen Link-Zyklen).
     * @param (callable(string): bool)|null $skip Erhält den Pfad jedes Eintrags (Datei oder
     *                             Verzeichnis); true lässt ihn aus — ein Verzeichnis wird dann
     *                             gar nicht betreten (Ausschluss beim Abstieg statt Nachfiltern).
     * @return list<string> Ein Array mit den gefundenen Dateipfaden.
     */
    public static function get(string $directory, bool $recursive = false, array $fileTypes = [], ?string $regexPattern = null, ?string $contains = null, bool $followSymlinks = false, ?callable $skip = null): array {
        // open_basedir-Prüfung (Logging erfolgt bereits in Folder::isBlockedByOpenBasedir)
        if (Folder::isBlockedByOpenBasedir($directory)) {
            return [];
        }

        if (!Folder::exists($directory)) {
            return self::logErrorAndReturn([], "Das Verzeichnis $directory existiert nicht");
        }

        $real = realpath($directory);
        $visited = $real !== false ? [$real => true] : [];
        $result = self::collectFiles($directory, $recursive, $fileTypes, $regexPattern, $contains, $followSymlinks, $skip, $visited);

        if (empty($result)) {
            self::logDebug("Keine passenden Dateien gefunden im Verzeichnis: $directory");
        } else {
            self::logDebug("Es wurden Dateien im Verzeichnis: $directory gefunden");
        }

        return $result;
    }

    /**
     * @param list<string> $fileTypes
     * @param (callable(string): bool)|null $skip
     * @param array<string, true> $visited Bereits besuchte reale Pfade (Zyklusschutz).
     * @return list<string>
     */
    private static function collectFiles(string $directory, bool $recursive, array $fileTypes, ?string $regexPattern, ?string $contains, bool $followSymlinks, ?callable $skip, array &$visited): array {
        // @: unter strengen Error-Handlern (Laravel) wirft sonst schon die Warnung.
        $entries = @scandir($directory);
        if ($entries === false) {
            return self::logErrorAndReturn([], "Das Verzeichnis $directory kann nicht gelesen werden");
        }

        $result = [];
        foreach (array_diff($entries, ['.', '..']) as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if ($skip !== null && $skip($path)) {
                continue;
            }

            if (is_dir($path)) {
                if (!$recursive || (is_link($path) && !$followSymlinks)) {
                    continue;
                }
                $real = realpath($path);
                if ($real === false || isset($visited[$real])) {
                    continue;
                }
                $visited[$real] = true;
                $result = array_merge($result, self::collectFiles($path, true, $fileTypes, $regexPattern, $contains, $followSymlinks, $skip, $visited));
            } elseif (is_file($path)) {
                if (empty($fileTypes) || in_array(pathinfo($path, PATHINFO_EXTENSION), $fileTypes)) {
                    // Prüfe auf regulären Ausdruck und ob der Dateiname den String enthält
                    if ((is_null($regexPattern) || preg_match($regexPattern, $file)) &&
                        (is_null($contains) || stripos($file, $contains) !== false)
                    ) {
                        $result[] = $path;
                    }
                }
            }
        }

        return $result;
    }
}
