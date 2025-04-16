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
use CommonToolkit\Enums\SearchMode;
use CommonToolkit\Helper\Data\StringHelper;
use CommonToolkit\Helper\Platform;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileExistsException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FileNotWrittenException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use finfo;

class File extends ConfiguredHelperAbstract implements FileSystemInterface {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/common_executables.json';

    private static function getRealExistingFile(string $file): string|false {
        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            return false;
        }
        return self::getRealPath($file);
    }

    private static function detectViaShell(string $commandName, string $file): string|false {
        $command = self::getConfiguredCommand($commandName, ['[INPUT]' => escapeshellarg($file)]);
        if (empty($command)) {
            self::logError("Kein Befehl für $commandName gefunden.");
            return false;
        }

        $output = [];
        if (!Shell::executeShellCommand($command, $output) || empty($output)) {
            self::logError("Fehler beim $commandName-Aufruf für $file");
            return false;
        }

        $result = trim(implode("\n", $output));
        self::logInfo("$commandName für $file erkannt: $result");
        return $result;
    }

    public static function mimeType(string $file): string|false {
        $file = self::getRealExistingFile($file);
        if ($file === false) return false;

        if (class_exists('finfo')) {
            self::logInfo("Nutze finfo für MIME-Typ: $file");
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $result = $finfo->file($file);
            if ($result !== false) return $result;
        }

        if (Platform::isLinux()) {
            self::logWarning("Fallback via Shell für MIME-Typ: $file");
            return self::detectViaShell('mimetype', $file);
        }

        self::logError("MIME-Typ konnte nicht bestimmt werden: $file");
        return false;
    }

    public static function mimeEncoding(string $file): string|false {
        $file = self::getRealExistingFile($file);
        if ($file === false) return false;

        if (class_exists('finfo')) {
            self::logInfo("Nutze finfo für MIME-Encoding: $file");
            $finfo = new finfo(FILEINFO_MIME_ENCODING);
            $result = $finfo->file($file);
            if ($result !== false) return $result;
        }

        if (Platform::isLinux()) {
            self::logWarning("Fallback via Shell für MIME-Encoding: $file");
            return self::detectViaShell('mime-encoding', $file);
        }

        self::logError("MIME-Encoding konnte nicht bestimmt werden: $file");
        return false;
    }

    public static function chardet(string $file): string|false {
        $file = self::getRealExistingFile($file);
        if ($file === false) return false;

        $content = file_get_contents($file, false, null, 0, 4096);
        if ($content !== false) {
            $encodings = ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252', 'ASCII'];
            $detected = mb_detect_encoding($content, $encodings, true);
            if ($detected !== false) {
                self::logInfo("Zeichencodierung via PHP erkannt: $detected für $file");
                self::adjustLocaleBasedOnEncoding($detected);
                return $detected;
            }
        }

        self::logWarning("Fallback via Shell (chardet/uchardet) für $file");
        $detected = self::detectViaShell('chardet', $file) ?: self::detectViaShell('uchardet', $file);

        if ($detected !== false) {
            $detected = trim($detected);
            if ($detected === "ISO-8859-1" || $detected === "MacRoman") {
                $detected = "ISO-8859-15";
            } elseif ($detected === "None") {
                $detected = "UTF-8";
            }
            self::adjustLocaleBasedOnEncoding($detected);
            self::logDebug("Shell-basierte Zeichencodierung erkannt: $detected für $file");
        }

        return $detected;
    }

    private static function adjustLocaleBasedOnEncoding(string $encoding): void {
        if (str_contains($encoding, "UTF") || str_contains($encoding, "utf")) {
            setlocale(LC_CTYPE, "de_DE.UTF-8");
        } else {
            setlocale(LC_CTYPE, 'de_DE@euro', 'de_DE');
        }
    }

    public static function exists(string $file): bool {

        $result = file_exists($file);
        if (!$result) {
            self::logDebug("Existenzprüfung der Datei: $file -> false");
        }
        return $result;
    }

    public static function getRealPath(string $file): string {
        if (self::exists($file)) {
            $realPath = realpath($file);
            if ($realPath === false) {
                self::logDebug("Konnte Pfad nicht auflösen: $file");
                return $file;
            }
            if ($realPath !== $file) {
                self::logInfo("Pfad wurde normalisiert: ... -> $realPath");
            }
            return $realPath;
        }
        self::logDebug("Pfad existiert nicht, unverändert zurückgeben: $file");
        return $file;
    }

    public static function read(string $file): string {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logError("Datei nicht gefunden: $file");
            throw new FileNotFoundException("Datei nicht gefunden: $file");
        }
        $content = file_get_contents($file);
        if ($content === false) {
            self::logError("Fehler beim Lesen der Datei: $file");
            throw new Exception("Fehler beim Lesen: $file");
        }
        self::logDebug("Datei erfolgreich gelesen: $file");
        return $content;
    }

    public static function write(string $file, string $data): void {
        $file = self::getRealPath($file);
        if (file_put_contents($file, $data) === false) {
            self::logError("Fehler beim Schreiben in die Datei: $file");
            throw new FileNotWrittenException("Fehler beim Schreiben in: $file");
        }
        self::logInfo("Daten erfolgreich in Datei geschrieben: $file");
    }

    public static function delete(string $file): void {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logNotice("Datei nicht gefunden, wird nicht gelöscht: $file");
            return;
        }
        if (!unlink($file)) {
            self::logError("Fehler beim Löschen der Datei: $file");
            throw new Exception("Fehler beim Löschen: $file");
        }
        self::logDebug("Datei gelöscht: $file");
    }

    public static function size(string $file): int {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            throw new FileNotFoundException("Datei nicht gefunden: $file");
        }
        return filesize($file);
    }

    public static function isReadable(string $file): bool {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            return false;
        }
        if (!is_readable($file)) {
            self::logError("Datei ist nicht lesbar: $file");
            return false;
        }
        return true;
    }

    public static function isReady(string $file, bool $logging = true): bool {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            if ($logging) self::logError("Datei existiert nicht: $file");
            return false;
        }
        $handle = @fopen($file, 'r');
        if ($handle === false) {
            self::logDebug("Datei ist noch nicht bereit zum Lesen: $file");
            return false;
        }
        fclose($handle);
        return true;
    }

    public static function wait4Ready(string $file, int $timeout = 30): bool {
        $file = self::getRealPath($file);
        $start = time();
        while (!self::isReady($file, false)) {
            if (!self::exists($file)) {
                self::logWarning("Datei existiert nicht mehr während Wartezeit: $file");
                return false;
            }
            if (time() - $start >= $timeout) {
                self::logError("Timeout beim Warten auf Datei: $file");
                return false;
            }
            sleep(1);
        }
        self::logDebug("Datei ist bereit: $file");
        return true;
    }

    public static function copy(string $sourceFile, string $destinationFile, bool $overwrite = true): void {
        $sourceFile = self::getRealPath($sourceFile);
        $destinationFile = self::getRealPath($destinationFile);

        if (!self::exists($sourceFile)) {
            self::logError("Quelldatei existiert nicht: $sourceFile");
            throw new FileNotFoundException("Datei nicht gefunden: $sourceFile");
        }

        if (self::exists($destinationFile) && !$overwrite) {
            self::logInfo("Zieldatei existiert und wird nicht überschrieben: $destinationFile");
            return;
        }

        if (!@copy($sourceFile, $destinationFile)) {
            self::logError("Fehler beim Kopieren von $sourceFile nach $destinationFile");
            throw new FileNotWrittenException("Fehler beim Kopieren von $sourceFile nach $destinationFile");
        }

        self::logInfo("Datei erfolgreich kopiert: $sourceFile -> $destinationFile");
    }

    public static function move(string $sourceFile, string $destinationFolder, ?string $destinationFileName = null, bool $overwrite = true): void {
        $sourceFile = self::getRealPath($sourceFile);
        $destinationFolder = self::getRealPath($destinationFolder);
        $destinationFile = $destinationFolder . DIRECTORY_SEPARATOR . ($destinationFileName ?? basename($sourceFile));

        if (!self::exists($sourceFile)) {
            self::logError("Quelldatei existiert nicht: $sourceFile");
            throw new FileNotFoundException("Datei nicht gefunden: $sourceFile");
        }

        if (!self::exists($destinationFolder)) {
            self::logError("Zielverzeichnis existiert nicht: $destinationFolder");
            throw new FolderNotFoundException("Zielordner nicht gefunden: $destinationFolder");
        }

        if (self::exists($destinationFile) && !$overwrite) {
            self::logInfo("Zieldatei existiert bereits und wird nicht überschrieben: $destinationFile");
            return;
        } elseif (self::exists($destinationFile) && $overwrite) {
            self::logInfo("Zieldatei existiert bereits und wird versucht zu überschreiben: $destinationFile");
        }

        if (!@rename($sourceFile, $destinationFile)) {
            self::logError("Fehler beim Verschieben von $sourceFile nach $destinationFile");
            throw new FileNotWrittenException("Fehler beim Verschieben nach $destinationFile");
        }

        self::logDebug("Datei verschoben: $sourceFile -> $destinationFile");
    }

    public static function rename(string $oldName, string $newName): void {
        $oldName = self::getRealPath($oldName);
        $newName = self::getRealPath($newName);

        if (!self::exists($oldName)) {
            self::logError("Datei zum Umbenennen nicht gefunden: $oldName");
            throw new FileNotFoundException("Die Datei $oldName existiert nicht");
        }

        if (self::exists($newName)) {
            self::logError("Zieldatei existiert bereits: $newName");
            throw new FileExistsException("Die Datei $newName existiert bereits");
        }

        if ($newName === basename($newName)) {
            $newName = dirname($oldName) . DIRECTORY_SEPARATOR . $newName;
        }

        if (!rename($oldName, $newName)) {
            self::logError("Fehler beim Umbenennen von $oldName nach $newName");
            throw new FileNotWrittenException("Fehler beim Umbenennen der Datei von $oldName nach $newName");
        }

        self::logDebug("Datei umbenannt von $oldName zu $newName");
    }

    public static function create(string $file, int $permissions = 0644, string $content = ''): void {
        if (self::exists($file)) {
            self::logError("Datei existiert bereits: $file");
            throw new FileExistsException("Datei existiert bereits: $file");
        }

        if (file_put_contents($file, $content) === false) {
            self::logError("Fehler beim Erstellen der Datei: $file");
            throw new FileNotWrittenException("Fehler beim Erstellen: $file");
        }

        if (!chmod($file, $permissions)) {
            self::logError("Fehler beim Setzen von Rechten ($permissions) für Datei: $file");
            throw new Exception("Fehler beim Setzen von Rechten für $file");
        }

        self::logInfo("Datei erstellt: $file mit Rechten $permissions");
    }

    public static function isAbsolutePath(string $path): bool {
        if (DIRECTORY_SEPARATOR === '/' && str_starts_with($path, '/')) {
            return true;
        }

        if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[a-zA-Z]:\\\\/', $path)) {
            return true;
        }

        if (DIRECTORY_SEPARATOR === '\\' && str_starts_with($path, '\\\\')) {
            return true;
        }

        return false;
    }

    public static function containsKeyword(string $file, array|string $keywords, ?string &$matchingLine = null, SearchMode $mode = SearchMode::CONTAINS, bool $caseSensitive = false): bool {
        if (!self::isReadable($file)) {
            self::logError("Datei nicht lesbar oder nicht vorhanden: $file");
            return false;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            self::logError("Fehler beim Öffnen der Datei: $file");
            return false;
        }

        $keywordsString = is_array($keywords) ? implode(', ', $keywords) : $keywords;

        while (($line = fgets($handle)) !== false) {
            if (StringHelper::containsKeyword($line, $keywords, $mode, $caseSensitive)) {
                $matchingLine = trim($line);
                fclose($handle);
                self::logInfo("Schlüsselwörter [$keywordsString] in Datei gefunden: $matchingLine");
                return true;
            }
        }

        fclose($handle);
        self::logDebug("Keine Übereinstimmung in Datei: $file");
        return false;
    }
}
