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
use CommonToolkit\Helper\FileSystem\Files;
use CommonToolkit\Helper\FileSystem\Folder;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use InvalidArgumentException;
use ZipArchive;

class ZipFile extends HelperAbstract {
    /** @var array<string> Gültige MIME-Types für ZIP-Dateien */
    private const ZIP_MIME_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-zip',
    ];

    /**
     * Überprüft, ob die ZipArchive-Erweiterung vorhanden ist.
     * Falls nicht, wird ein Fehler geloggt und eine Exception geworfen.
     */
    private static function checkZipExtension(): void {
        if (!class_exists('ZipArchive')) {
            self::logErrorAndThrow(Exception::class, "PHP ZipArchive-Erweiterung fehlt. ZIP-Operationen nicht möglich.");
        }
    }

    /**
     * Prüft ob eine Datei anhand des MIME-Types eine ZIP-Datei ist.
     *
     * @param string $file Zu prüfende Datei.
     * @return bool True, wenn die Datei eine ZIP-Datei ist.
     */
    public static function isZipFile(string $file): bool {
        if (!File::exists($file)) {
            return false;
        }

        $mimeType = File::mimeType($file);
        if ($mimeType === false) {
            return false;
        }

        return in_array($mimeType, self::ZIP_MIME_TYPES, true);
    }

    /**
     * Prüft ob der Dateiname eine ZIP-Erweiterung hat.
     *
     * @param string $filename Der Dateiname (mit oder ohne Pfad).
     * @return bool True, wenn die Datei eine .zip-Erweiterung hat.
     */
    public static function hasZipExtension(string $filename): bool {
        return File::isExtension($filename, 'zip');
    }

    /**
     * Erstellt eine ZIP-Datei aus mehreren Dateien.
     *
     * @param array $files Dateien als Array von Strings (Dateipfade) oder Arrays mit 'path' und optionalem 'archiveName'.
     * @param string $destination Zielpfad für das ZIP-Archiv.
     * @return bool Erfolg oder Misserfolg.
     * @throws RuntimeException Falls das Archiv nicht erstellt werden kann.
     */
    public static function create(array $files, string $destination): bool {
        self::checkZipExtension();

        $destination = File::getRealPath($destination);

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Erstellen des ZIP-Archivs: $destination");
        }

        $addedCount = 0;
        foreach ($files as $fileEntry) {
            // Unterstütze sowohl einfache Strings als auch Arrays mit path/archiveName
            if (is_array($fileEntry)) {
                $filePath = $fileEntry['path'] ?? null;
                $archiveName = $fileEntry['archiveName'] ?? ($filePath !== null ? basename($filePath) : null);
            } else {
                $filePath = $fileEntry;
                $archiveName = basename($fileEntry);
            }

            if ($filePath === null) {
                self::logWarning("Ungültiger Dateieintrag übersprungen.");
                continue;
            }

            $filePath = File::getRealPath($filePath);
            if (!File::exists($filePath)) {
                self::logWarning("Datei nicht gefunden: $filePath - Datei wird übersprungen.");
                continue;
            }

            if (!$zip->addFile($filePath, $archiveName)) {
                self::logErrorAndThrow(Exception::class, "Fehler beim Hinzufügen der Datei zum ZIP-Archiv: $filePath");
            }
            $addedCount++;
        }

        if (!$zip->close()) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Abschließen des ZIP-Archivs: $destination");
        }

        return self::logInfoAndReturn(true, "ZIP-Archiv erfolgreich erstellt: $destination ($addedCount Dateien)");
    }

    /**
     * Erstellt eine ZIP-Datei aus einem Verzeichnis.
     *
     * @param string $sourceDir Das Quellverzeichnis.
     * @param string $destination Zielpfad für das ZIP-Archiv.
     * @param string|null $baseName Optionaler Basis-Ordnername im Archiv (null = kein Präfix).
     * @return bool Erfolg oder Misserfolg.
     * @throws FolderNotFoundException Falls das Quellverzeichnis nicht existiert.
     * @throws Exception Falls das Archiv nicht erstellt werden kann.
     */
    public static function createFromDirectory(string $sourceDir, string $destination, ?string $baseName = null): bool {
        $sourceDir = File::getRealPath($sourceDir);

        if (!Folder::exists($sourceDir)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Quellverzeichnis nicht gefunden: $sourceDir");
        }

        $files = [];
        $foundFiles = Files::get($sourceDir, true);

        foreach ($foundFiles as $filePath) {
            // Berechne relativen Pfad zum Quellverzeichnis
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            if ($baseName !== null) {
                $relativePath = $baseName . '/' . $relativePath;
            }

            $files[] = [
                'path' => $filePath,
                'archiveName' => $relativePath,
            ];
        }

        self::logDebug("Erstelle ZIP aus Verzeichnis: $sourceDir mit " . count($files) . " Dateien");
        return self::create($files, $destination);
    }

    /**
     * Extrahiert eine ZIP-Datei in einen Zielordner mit Zip-Slip-Schutz.
     *
     * @param string $file ZIP-Datei, die extrahiert werden soll.
     * @param string $destinationFolder Zielverzeichnis.
     * @param bool $deleteSourceFile Ob die ZIP-Datei nach dem Extrahieren gelöscht werden soll.
     * @throws Exception Falls die Datei nicht extrahiert werden kann.
     * @throws InvalidArgumentException Falls ein Path-Traversal-Angriff erkannt wird.
     */
    public static function extract(string $file, string $destinationFolder, bool $deleteSourceFile = true): void {
        self::checkZipExtension();

        $file = File::getRealPath($file);
        $destinationFolder = File::getRealPath($destinationFolder);

        if (!File::exists($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "ZIP-Datei nicht gefunden: $file");
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Öffnen der ZIP-Datei: $file");
        }

        if (!Folder::exists($destinationFolder)) {
            Folder::create($destinationFolder, 0755, true);
        }

        // Zip-Slip-Schutz: Jede Datei einzeln prüfen und extrahieren
        $destinationReal = realpath($destinationFolder);
        if ($destinationReal === false) {
            $zip->close();
            self::logErrorAndThrow(Exception::class, "Zielverzeichnis konnte nicht aufgelöst werden: $destinationFolder");
        }
        $destinationReal = rtrim($destinationReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }

            // Zieldatei-Pfad berechnen und normalisieren
            $targetPath = $destinationReal . $entryName;
            $targetPathReal = self::normalizePath($targetPath);

            // Zip-Slip-Prüfung: Ist der Zielpfad innerhalb des Zielverzeichnisses?
            if (!str_starts_with($targetPathReal, $destinationReal)) {
                $zip->close();
                self::logErrorAndThrow(
                    InvalidArgumentException::class,
                    "Zip-Slip-Angriff erkannt! Eintrag '$entryName' versucht außerhalb des Zielverzeichnisses zu schreiben."
                );
            }

            // Verzeichnis erstellen falls nötig
            $targetDir = dirname($targetPathReal);
            if (!Folder::exists($targetDir)) {
                Folder::create($targetDir, 0755, true);
            }

            // Ist es ein Verzeichnis? (endet mit /)
            if (str_ends_with($entryName, '/')) {
                continue;
            }

            // Datei extrahieren
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                $zip->close();
                self::logErrorAndThrow(Exception::class, "Fehler beim Lesen des Eintrags: $entryName");
            }

            File::write($targetPathReal, $content);
        }

        $zip->close();;
        self::logInfo("ZIP-Datei erfolgreich extrahiert: $file nach $destinationFolder");

        if ($deleteSourceFile) {
            File::delete($file);
        }
    }

    /**
     * Normalisiert einen Pfad und löst . und .. auf (ohne realpath, da Datei noch nicht existiert).
     *
     * @param string $path Der zu normalisierende Pfad.
     * @return string Der normalisierte Pfad.
     */
    private static function normalizePath(string $path): string {
        // Backslashes durch Slashes ersetzen
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

        return implode(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * Prüft, ob eine ZIP-Datei gültig ist.
     *
     * @param string $file Zu prüfende ZIP-Datei.
     * @return bool True, wenn die Datei gültig ist, sonst False.
     * @throws FileNotFoundException Falls die Datei nicht existiert.
     */
    public static function isValid(string $file): bool {
        self::checkZipExtension();

        $file = File::getRealPath($file);

        if (!File::exists($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht gefunden: $file");
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);

        if ($result === true) {
            self::logDebug("ZIP-Datei ist gültig: $file");
            $zip->close();
            return true;
        }

        // Fehlercodes besser behandeln
        $errorMessages = [
            ZipArchive::ER_NOZIP => "Die Datei ist keine gültige ZIP-Datei: $file",
            ZipArchive::ER_INCONS => "Das ZIP-Archiv ist inkonsistent: $file",
            ZipArchive::ER_MEMORY => "Speicherproblem beim Öffnen des ZIP-Archivs: $file",
            ZipArchive::ER_READ => "Fehler beim Lesen des ZIP-Archivs: $file",
            ZipArchive::ER_CRC => "CRC-Fehler im ZIP-Archiv: $file",
            ZipArchive::ER_OPEN => "Fehler beim Öffnen des ZIP-Archivs: $file"
        ];

        $errorMessage = $errorMessages[$result] ?? "Unbekannter Fehler beim Öffnen der ZIP-Datei: $file";
        return self::logErrorAndReturn(false, $errorMessage);
    }

    /**
     * Listet die Inhalte einer ZIP-Datei ohne Extraktion auf.
     *
     * @param string $file ZIP-Datei, deren Inhalte aufgelistet werden sollen.
     * @return array<int, array{name: string, size: int, compressedSize: int, isDirectory: bool}> Liste der Einträge.
     * @throws FileNotFoundException Falls die ZIP-Datei nicht existiert.
     * @throws Exception Falls die ZIP-Datei nicht geöffnet werden kann.
     */
    public static function listContents(string $file): array {
        self::checkZipExtension();

        $file = File::getRealPath($file);

        if (!File::exists($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "ZIP-Datei nicht gefunden: $file");
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);

        if ($result !== true) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Öffnen der ZIP-Datei: $file (Code: $result)");
        }

        $contents = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat !== false) {
                $contents[] = [
                    'name' => $stat['name'],
                    'size' => $stat['size'],
                    'compressedSize' => $stat['comp_size'],
                    'isDirectory' => str_ends_with($stat['name'], '/'),
                ];
            }
        }

        $zip->close();

        self::logDebug("ZIP-Inhalte aufgelistet: $file (" . count($contents) . " Einträge)");

        return $contents;
    }

    /**
     * Gibt eine lesbare Fehlermeldung für einen ZipArchive-Fehlercode zurück.
     *
     * @param int $errorCode Der Fehlercode von ZipArchive::open().
     * @return string Die Fehlermeldung.
     */
    public static function getErrorMessage(int $errorCode): string {
        return match ($errorCode) {
            ZipArchive::ER_EXISTS => "Datei existiert bereits",
            ZipArchive::ER_INCONS => "Inkonsistentes ZIP-Archiv",
            ZipArchive::ER_INVAL => "Ungültiges Argument",
            ZipArchive::ER_MEMORY => "Speicherfehler",
            ZipArchive::ER_NOENT => "Datei nicht gefunden",
            ZipArchive::ER_NOZIP => "Keine gültige ZIP-Datei",
            ZipArchive::ER_OPEN => "Datei konnte nicht geöffnet werden",
            ZipArchive::ER_READ => "Lesefehler",
            ZipArchive::ER_SEEK => "Seek-Fehler",
            ZipArchive::ER_CRC => "CRC-Prüfsummenfehler",
            default => "Unbekannter Fehler (Code: $errorCode)",
        };
    }
}
