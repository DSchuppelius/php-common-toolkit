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
     * Gibt den absoluten Pfad zu mehreren Dateien zurück.
     *
     * @param array $files Ein Array mit Dateipfaden.
     * @return array Ein Array mit den absoluten Pfaden der Dateien.
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
     * @param array $files Ein Array mit Dateipfaden.
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
     * @param array $filePairs Ein Array mit Quell- und Ziel-Dateipaaren.
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
     * @param array $filePairs Ein Array mit alten und neuen Dateinamen.
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
     * @param array $filePairs Ein Array mit alten und neuen Dateinamen.
     */
    public static function rename(array $filePairs): void {
        foreach ($filePairs as $oldName => $newName) {
            File::rename($oldName, $newName);
        }
    }

    /**
     * Löscht mehrere Dateien.
     *
     * @param array $files Ein Array mit Dateipfaden.
     */
    public static function delete(array $files): void {
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    /**
     * Liest Daten aus mehreren Dateien.
     *
     * @param array $files Ein Array mit Dateipfaden.
     * @return array Ein Array mit den Dateipfaden als Schlüsseln und den gelesenen Daten als Werten.
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
     * @param array $fileData Ein Array mit Dateipfaden als Schlüsseln und den zu schreibenden Daten als Werten.
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
     * @param array $fileTypes Ein Array von Dateitypen (z.B. ['txt', 'jpg']), die berücksichtigt werden sollen.
     * @param string|null $regexPattern Ein regulärer Ausdruck, der auf den Dateinamen angewendet wird.
     * @param string|null $contains Ein String, der im Dateinamen enthalten sein muss.
     * @return array Ein Array mit den gefundenen Dateipfaden.
     */
    public static function get(string $directory, bool $recursive = false, array $fileTypes = [], ?string $regexPattern = null, ?string $contains = null): array {
        $result = [];
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if ($recursive && is_dir($path)) {
                $result = array_merge($result, self::get($path, true, $fileTypes, $regexPattern, $contains));
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

        if (empty($result)) {
            self::logInfo("Keine passenden Dateien gefunden im Verzeichnis: $directory");
        } else {
            self::logDebug("Es wurden Dateien im Verzeichnis: $directory gefunden");
        }

        return $result;
    }
}