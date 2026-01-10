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
use CommonToolkit\Helper\FileSystem\Folder;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;
use ZipArchive;

class ZipFile extends HelperAbstract {
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
     * Erstellt eine ZIP-Datei aus mehreren Dateien.
     *
     * @param array $files Dateien, die ins ZIP-Archiv aufgenommen werden sollen.
     * @param string $destination Zielpfad für das ZIP-Archiv.
     * @return bool Erfolg oder Misserfolg.
     * @throws Exception Falls das Archiv nicht erstellt werden kann.
     */
    public static function create(array $files, string $destination): bool {
        self::checkZipExtension();

        $destination = File::getRealPath($destination);

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Erstellen des ZIP-Archivs: $destination");
        }

        foreach ($files as $file) {
            $file = File::getRealPath($file);
            if (!File::exists($file)) {
                self::logWarning("Datei nicht gefunden: $file - Datei wird übersprungen.");
                continue;
            }

            if (!$zip->addFile($file, basename($file))) {
                self::logErrorAndThrow(Exception::class, "Fehler beim Hinzufügen der Datei zum ZIP-Archiv: $file");
            }
        }

        if (!$zip->close()) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Abschließen des ZIP-Archivs: $destination");
        }

        return self::logInfoAndReturn(true, "ZIP-Archiv erfolgreich erstellt: $destination");
    }

    /**
     * Extrahiert eine ZIP-Datei in einen Zielordner.
     *
     * @param string $file ZIP-Datei, die extrahiert werden soll.
     * @param string $destinationFolder Zielverzeichnis.
     * @param bool $deleteSourceFile Ob die ZIP-Datei nach dem Extrahieren gelöscht werden soll.
     * @throws Exception Falls die Datei nicht extrahiert werden kann.
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

        if (!Folder::exists($destinationFolder) && !Folder::create($destinationFolder, 0755, true)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Erstellen des Zielverzeichnisses: $destinationFolder");
        }

        if (!$zip->extractTo($destinationFolder)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Extrahieren der ZIP-Datei: $file nach $destinationFolder");
        }

        $zip->close();
        self::logInfo("ZIP-Datei erfolgreich extrahiert: $file nach $destinationFolder");

        if ($deleteSourceFile) {
            File::delete($file);
        }
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
            self::logInfo("ZIP-Datei ist gültig: $file");
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
}
