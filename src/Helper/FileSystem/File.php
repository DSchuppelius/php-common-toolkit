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
use Generator;

class File extends ConfiguredHelperAbstract implements FileSystemInterface {
    protected const CONFIG_FILE = __DIR__ . '/../../../config/common_executables.json';

    /**
     * Gibt den konfigurierten Shell-Befehl zurück.
     *
     * @param string $commandName Der Name des Befehls.
     * @param array $params Die Parameter für den Befehl.
     * @return string|null Der konfigurierte Befehl oder null, wenn nicht gefunden.
     */
    private static function getRealExistingFile(string $file): string|false {
        try {
            $file = self::resolveFile($file);
        } catch (FileNotFoundException) {
            return false;
        }
        return $file;
    }

    /**
     * Führt einen Shell-Befehl aus, um beispielsweise den MIME-Typ oder die Zeichencodierung zu erkennen.
     *
     * @param string $commandName Der Name des Befehls (z.B. 'mimetype', 'chardet').
     * @param string $file Der Pfad zur Datei.
     * @return string|false Der erkannte Typ oder false bei Fehler.
     */
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

    /**
     * Bestimmt den MIME-Typ einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string|false Der erkannte MIME-Typ oder false bei Fehler.
     */
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

    /**
     * Bestimmt die MIME-Encoding einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string|false Die erkannte MIME-Encoding oder false bei Fehler.
     */
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

    /**
     * Bestimmt die Zeichencodierung einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string|false Die erkannte Zeichencodierung oder false bei Fehler.
     */
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

    /**
     * Passt die Locale-Einstellung basierend auf der Zeichencodierung an.
     *
     * @param string $encoding Die erkannte Zeichencodierung.
     */
    private static function adjustLocaleBasedOnEncoding(string $encoding): void {
        if (str_contains($encoding, "UTF") || str_contains($encoding, "utf")) {
            setlocale(LC_CTYPE, "de_DE.UTF-8");
        } else {
            setlocale(LC_CTYPE, 'de_DE@euro', 'de_DE');
        }
    }

    /**
     * Überprüft, ob die Datei existiert.
     *
     * @param string $file Der Pfad zur Datei.
     * @return bool True, wenn die Datei existiert, andernfalls false.
     */
    public static function exists(string $file): bool {
        $result = file_exists($file);
        if (!$result) {
            self::logDebug("Existenzprüfung der Datei: $file -> false");
        }
        return $result;
    }

    /**
     * Gibt den realen Pfad der Datei zurück.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string Der reale Pfad der Datei.
     */
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

    /**
     * Liest den Inhalt der angegebenen Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string Der Inhalt der Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler beim Lesen auftritt.
     */
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

    /**
     * Liefert die Zeilen einer Textdatei als Generator zurück.
     *
     * @param string $file        Pfad zur Datei.
     * @param bool $skipEmpty     Leere Zeilen überspringen (Standard: false).
     * @param int|null $maxLines  Begrenzung auf Anzahl Zeilen (Standard: null = alle).
     * @param int $startLine      Startzeile (Standard: 1).
     * @return Generator<string>
     * @throws FileNotFoundException
     */
    public static function readLines(string $file, bool $skipEmpty = false, ?int $maxLines = null, int $startLine = 1): Generator {
        $file = self::getRealPath($file);
        if (!self::isReadable($file)) {
            throw new FileNotFoundException("Datei nicht lesbar: $file");
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            self::logError("Fehler beim Öffnen der Datei für readLines: $file");
            throw new FileNotFoundException("Fehler beim Öffnen: $file");
        }

        $count = 0;
        $currentLine = 0;

        while (($line = fgets($handle)) !== false) {
            $currentLine++;
            if ($currentLine < $startLine) {
                continue;
            } elseif ($skipEmpty && empty(trim($line))) {
                continue;
            }
            yield rtrim($line, "\r\n");

            $count++;
            if ($maxLines !== null && $count >= $maxLines) {
                break;
            }
        }

        fclose($handle);
    }

    /**
     * Liest die Zeilen einer Textdatei als Array zurück.
     *
     * @param string $file        Pfad zur Datei.
     * @param bool $skipEmpty     Leere Zeilen überspringen (Standard: false).
     * @param int|null $maxLines  Begrenzung auf Anzahl Zeilen (Standard: null = alle).
     * @param int $startLine      Startzeile (Standard: 1).
     * @return string[] Array mit den Zeilen der Datei.
     * @throws FileNotFoundException
     */
    public static function readLinesAsArray(string $file, bool $skipEmpty = false, ?int $maxLines = null, int $startLine = 1): array {
        return iterator_to_array(self::readLines($file, $skipEmpty, $maxLines, $startLine), false);
    }

    /**
     * Schreibt Daten in die angegebene Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $data Die zu schreibenden Daten.
     * @throws FileNotWrittenException Wenn die Datei nicht geschrieben werden kann.
     */
    public static function write(string $file, string $data): void {
        $file = self::getRealPath($file);
        if (file_put_contents($file, $data) === false) {
            self::logError("Fehler beim Schreiben in die Datei: $file");
            throw new FileNotWrittenException("Fehler beim Schreiben in: $file");
        }
        self::logInfo("Daten erfolgreich in Datei geschrieben: $file");
    }

    /**
     * Löscht die angegebene Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @throws Exception Wenn die Datei nicht gelöscht werden kann.
     */
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

    /**
     * Gibt die Größe der Datei in Bytes zurück.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Die Größe der Datei in Bytes.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     */
    public static function size(string $file): int {
        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            throw new FileNotFoundException("Datei nicht gefunden: $file");
        }
        return filesize($file);
    }

    /**
     * Überprüft, ob die Datei lesbar ist.
     *
     * @param string $file Der Pfad zur Datei.
     * @return bool True, wenn die Datei lesbar ist, andernfalls false.
     */
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

    /**
     * Überprüft, ob die Datei bereit ist, gelesen zu werden.
     *
     * @param string $file Der Pfad zur Datei.
     * @param bool $logging Ob Protokollierung aktiviert ist (Standard: true).
     * @return bool True, wenn die Datei bereit ist, andernfalls false.
     */
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

    /**
     * Wartet, bis die Datei bereit ist.
     *
     * @param string $file Der Pfad zur Datei.
     * @param int $timeout Die maximale Wartezeit in Sekunden (Standard: 30).
     * @return bool True, wenn die Datei bereit ist, andernfalls false.
     */
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

    /**
     * Kopiert eine Datei in einen anderen Ordner.
     *
     * @param string $sourceFile Der Pfad zur Quelldatei.
     * @param string $destinationFile Der Zielpfad.
     * @param bool $overwrite Ob die Zieldatei überschrieben werden soll (Standard: true).
     * @throws FileNotFoundException Wenn die Quelldatei nicht gefunden wird.
     * @throws FileNotWrittenException Wenn die Datei nicht kopiert werden kann.
     */
    public static function copy(string $sourceFile, string $destinationFile, bool $overwrite = true): void {
        $sourceFile = self::getRealPath($sourceFile);
        $destinationFile = self::getRealPath($destinationFile);

        if (!self::exists($sourceFile)) {
            self::logError("Quelldatei existiert nicht: $sourceFile");
            throw new FileNotFoundException("Datei nicht gefunden: $sourceFile");
        }

        if (self::exists($destinationFile) && !$overwrite) {
            self::logWarning("Zieldatei existiert und wird nicht überschrieben: $destinationFile");
            return;
        }

        if (!@copy($sourceFile, $destinationFile)) {
            self::logError("Fehler beim Kopieren von $sourceFile nach $destinationFile");
            throw new FileNotWrittenException("Fehler beim Kopieren von $sourceFile nach $destinationFile");
        }

        self::logInfo("Datei erfolgreich kopiert: $sourceFile -> $destinationFile");
    }

    /**
     * Verschiebt eine Datei in einen anderen Ordner.
     *
     * @param string $sourceFile Der Pfad zur Quelldatei.
     * @param string $destinationFolder Der Zielordner.
     * @param string|null $destinationFileName Der Name der Zieldatei (optional).
     * @param bool $overwrite Ob die Zieldatei überschrieben werden soll (Standard: true).
     * @throws FileNotFoundException Wenn die Quelldatei nicht gefunden wird.
     * @throws FolderNotFoundException Wenn das Zielverzeichnis nicht gefunden wird.
     * @throws FileNotWrittenException Wenn die Datei nicht verschoben werden kann.
     */
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
            self::logWarning("Zieldatei existiert bereits und wird nicht überschrieben: $destinationFile");
            return;
        } elseif (self::exists($destinationFile) && $overwrite) {
            self::logWarning("Zieldatei existiert bereits und wird versucht zu überschreiben: $destinationFile");
        }

        if (!@rename($sourceFile, $destinationFile)) {
            self::logError("Fehler beim Verschieben von $sourceFile nach $destinationFile");
            throw new FileNotWrittenException("Fehler beim Verschieben nach $destinationFile");
        }

        self::logDebug("Datei verschoben: $sourceFile -> $destinationFile");
    }

    /**
     * Benennt eine Datei um.
     *
     * @param string $oldName Der alte Name der Datei.
     * @param string $newName Der neue Name der Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws FileExistsException Wenn die Zieldatei bereits existiert.
     * @throws FileNotWrittenException Wenn die Datei nicht umbenannt werden kann.
     */
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

    /**
     * Erstellt eine Datei mit dem angegebenen Inhalt und den Berechtigungen.
     *
     * @param string $file Der Pfad zur Datei.
     * @param int $permissions Die Berechtigungen für die Datei (Standard: 0644).
     * @param string $content Der Inhalt der Datei (Standard: leer).
     * @throws FileExistsException Wenn die Datei bereits existiert.
     * @throws FileNotWrittenException Wenn die Datei nicht geschrieben werden kann.
     * @throws Exception Wenn ein Fehler beim Setzen der Berechtigungen auftritt.
     */
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

    /**
     * Überprüft, ob der angegebene Pfad ein absoluter Pfad ist.
     *
     * @param string $path Der zu überprüfende Pfad.
     * @return bool True, wenn der Pfad absolut ist, andernfalls false.
     */
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

    /**
     * Überprüft, ob die Datei ein bestimmtes Schlüsselwort bzw. eine Liste von Schlüsselwörtern enthält.
     *
     * @param string $file Der Pfad zur Datei.
     * @param array|string $keywords Die Schlüsselwörter, nach denen gesucht werden soll.
     * @param string|null $matchingLine Die Zeile, die das Schlüsselwort enthält (optional).
     * @param SearchMode $mode Der Suchmodus (Standard: CONTAINS).
     * @param bool $caseSensitive Ob die Suche Groß-/Kleinschreibung beachten soll (Standard: false).
     * @return bool True, wenn das Schlüsselwort gefunden wurde, andernfalls false.
     */
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

    /**
     * Ermittelt die Anzahl der Textzeilen in einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Die Anzahl der Zeilen.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     */
    public static function lineCount(string $file, bool $skipEmpty = false): int {
        $file = self::getRealPath($file);
        if (!self::isReadable($file)) {
            throw new FileNotFoundException("Datei nicht lesbar: $file");
        }

        $lines = 0;
        $handle = fopen($file, "r");
        if ($handle === false) {
            self::logError("Fehler beim Öffnen der Datei für Zeilenzählung: $file");
            throw new FileNotFoundException("Fehler beim Öffnen: $file");
        }

        while (!feof($handle)) {
            $line = fgets($handle);
            if ($skipEmpty && trim($line) === '') continue;
            $lines++;
        }
        fclose($handle);
        self::logInfo("Anzahl der Zeilen in $file: $lines");
        return $lines;
    }

    /**
     * Ermittelt die Anzahl der Zeichen (Bytes) in einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Die Anzahl der Zeichen (Bytes).
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     */
    public static function charCount(string $file, string $encoding = "UTF-8"): int {
        $file = self::getRealPath($file);
        if (!self::isReadable($file)) {
            throw new FileNotFoundException("Datei nicht lesbar: $file");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            self::logError("Fehler beim Lesen der Datei zur Zeichenzählung: $file");
            throw new FileNotFoundException("Fehler beim Lesen: $file");
        }

        $length = mb_strlen($content, $encoding);
        self::logInfo("Anzahl der Zeichen in $file: $length");
        return $length;
    }

    /**
     * Gibt die Dateierweiterung zurück.
     *
     * @param string $file
     * @return string
     */
    public static function extension(string $file): string {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Gibt den Dateinamen zurück.
     *
     * @param string $file
     * @param bool $withExtension
     * @return string
     */
    public static function filename(string $file, bool $withExtension = true): string {
        return $withExtension ? basename($file) : pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Gibt das Verzeichnis der Datei zurück.
     *
     * @param string $file
     * @return string
     */
    public static function directory(string $file): string {
        return dirname($file);
    }

    /**
     * Überprüft, ob die Datei eine bestimmte Erweiterung hat.
     *
     * @param string $file
     * @param array|string $extensions  Endung(en), optional mit führendem Punkt
     * @param bool $caseSensitive
     * @return bool
     */
    public static function isExtension(string $file, array|string $extensions, bool $caseSensitive = false): bool {
        // aktuelle Endung der Datei ermitteln
        $fileExt = ltrim(self::extension($file), '.');

        // Eingaben normalisieren → Punkt vorne entfernen
        if (is_array($extensions)) {
            $extensions = array_map(fn($ext) => ltrim($ext, '.'), $extensions);
        } else {
            $extensions = ltrim($extensions, '.');
        }

        if (!$caseSensitive) {
            $fileExt    = strtolower($fileExt);
            $extensions = is_array($extensions) ? array_map('strtolower', $extensions) : strtolower($extensions);
        }

        if (is_array($extensions)) {
            return in_array($fileExt, $extensions, true);
        }
        return $fileExt === $extensions;
    }

    /**
     * Ändert die Dateierweiterung.
     *
     * @param string $file
     * @param string $newExtension
     * @return string
     */
    public static function changeExtension(string $file, string $newExtension): string {
        $dir = self::directory($file);
        $filename = self::filename($file, false);
        return $dir . DIRECTORY_SEPARATOR . $filename . '.' . ltrim($newExtension, '.');
    }

    /**
     * Fügt einen Anhang an den Dateinamen an.
     *
     * @param string $file
     * @param string $appendix
     * @return string
     */
    public static function appendToFilename(string $file, string $appendix): string {
        $dir = self::directory($file);
        $filename = self::filename($file, false);
        $extension = self::extension($file);
        return $dir . DIRECTORY_SEPARATOR . $filename . $appendix . ($extension ? '.' . $extension : '');
    }

    /**
     * Fügt einen Präfix an den Dateinamen an.
     *
     * @param string $file
     * @param string $prefix
     * @return string
     */
    public static function prependToFilename(string $file, string $prefix): string {
        $dir = self::directory($file);
        $filename = self::filename($file, false);
        $extension = self::extension($file);
        return $dir . DIRECTORY_SEPARATOR . $prefix . $filename . ($extension ? '.' . $extension : '');
    }

    /**
     * Überprüft, ob die Datei einen bestimmten MIME-Typ hat.
     *
     * @param string $file
     * @param array|string $mimeTypes
     * @param bool $caseSensitive
     * @return bool
     */
    public static function isMimeType(string $file, array|string $mimeTypes, bool $caseSensitive = false): bool {
        $fileMimeType = self::mimeType($file);
        if ($fileMimeType === false) {
            return false;
        }
        if (!$caseSensitive) {
            $fileMimeType = strtolower($fileMimeType);
            $mimeTypes = is_array($mimeTypes) ? array_map('strtolower', $mimeTypes) : strtolower($mimeTypes);
        }
        if (is_array($mimeTypes)) {
            return in_array($fileMimeType, $mimeTypes, true);
        }
        return $fileMimeType === $mimeTypes;
    }
}