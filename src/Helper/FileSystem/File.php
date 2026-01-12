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
use CommonToolkit\Traits\RealPathTrait;
use ERRORToolkit\Exceptions\FileSystem\FileExistsException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\FileSystem\FileNotWrittenException;
use ERRORToolkit\Exceptions\FileSystem\FolderNotFoundException;
use Exception;
use finfo;
use Generator;
use InvalidArgumentException;

class File extends ConfiguredHelperAbstract implements FileSystemInterface {
    use RealPathTrait;

    protected const CONFIG_FILE = __DIR__ . '/../../../config/common_executables.json';

    /** @var array<int, string> Windows-reservierte Gerätenamen, die nicht als Dateinamen verwendet werden können */
    public const WINDOWS_RESERVED_NAMES = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];

    /** @var array<string, string|false> Cache für chardet-Ergebnisse (Pfad → Encoding) */
    private static array $chardetCache = [];

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
            return self::logErrorAndReturn(false, "Kein Befehl für $commandName gefunden.");
        }

        $output = [];
        if (!Shell::executeShellCommand($command, $output) || empty($output)) {
            return self::logErrorAndReturn(false, "Fehler beim $commandName-Aufruf für $file");
        }

        $result = trim(implode("\n", $output));
        return self::logInfoAndReturn($result, "$commandName für $file erkannt: $result");
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

        return self::logErrorAndReturn(false, "MIME-Typ konnte nicht bestimmt werden: $file");
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

        return self::logErrorAndReturn(false, "MIME-Encoding konnte nicht bestimmt werden: $file");
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

        if (array_key_exists($file, self::$chardetCache)) {
            return self::logDebugAndReturn(self::$chardetCache[$file], "Chardet Cache-Hit für $file");
        }

        $content = self::readPartial($file);
        if ($content !== false) {
            $encodings = ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252', 'ASCII'];
            $detected = mb_detect_encoding($content, $encodings, true);
            if ($detected !== false) {
                self::logInfo("Zeichencodierung via PHP erkannt: $detected für $file");
                self::adjustLocaleBasedOnEncoding($detected);
                self::$chardetCache[$file] = $detected;
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

        // Ergebnis cachen (auch false für Fehlschläge)
        self::$chardetCache[$file] = $detected;
        return $detected;
    }

    /**
     * Leert den chardet-Cache.
     * Nützlich für Tests oder nach Dateiänderungen.
     */
    public static function clearChardetCache(): void {
        self::$chardetCache = [];
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
     * Prüft, ob ein Pfad durch die open_basedir-Einschränkung blockiert wird.
     * Delegiert an Folder::isBlockedByOpenBasedir().
     *
     * @param string $path Der zu prüfende Pfad.
     * @return bool True, wenn der Pfad durch open_basedir blockiert wird, andernfalls false.
     */
    public static function isBlockedByOpenBasedir(string $path): bool {
        return Folder::isBlockedByOpenBasedir($path);
    }

    /**
     * Überprüft, ob die Datei existiert.
     *
     * @param string $file Der Pfad zur Datei.
     * @return bool True, wenn die Datei existiert, andernfalls false.
     */
    public static function exists(string $file): bool {
        // Windows-reservierte Gerätenamen ignorieren (auch auf Linux für Samba-Kompatibilität)
        if (self::isWindowsReservedName($file)) {
            return self::logDebugAndReturn(false, "Windows-reservierter Gerätename ignoriert: $file");
        }

        // open_basedir-Prüfung (Logging erfolgt bereits in Folder::isBlockedByOpenBasedir)
        if (Folder::isBlockedByOpenBasedir($file)) {
            return false;
        }

        $result = file_exists($file);
        self::logDebugUnless($result, "Existenzprüfung der Datei: $file -> false");
        return $result;
    }

    /**
     * Liest den Inhalt der angegebenen Datei.
     *
     * @param string $file Der Pfad zur Datei oder eine URL (http/https).
     * @return string Der Inhalt der Datei oder URL.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler beim Lesen auftritt.
     */
    public static function read(string $file): string {
        // URL-Unterstützung: http:// und https:// Protokolle
        if (preg_match('#^https?://#i', $file)) {
            $content = @file_get_contents($file);
            if ($content === false) {
                self::logErrorAndThrow(Exception::class, "Fehler beim Abrufen der URL: $file");
            }
            return self::logDebugAndReturn($content, "URL erfolgreich abgerufen: $file");
        }

        $file = self::getRealPath($file);
        if (!self::exists($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht gefunden: $file");
        }
        $content = file_get_contents($file);
        if ($content === false) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Lesen der Datei: $file");
        }
        return self::logDebugAndReturn($content, "Datei erfolgreich gelesen: $file");
    }

    /**
     * Liest den Inhalt der angegebenen Datei und konvertiert ihn zu UTF-8.
     *
     * @param string $file Der Pfad zur Datei.
     * @return string Der Inhalt der Datei in UTF-8.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler beim Lesen auftritt.
     */
    public static function readAsUtf8(string $file): string {
        $content = self::read($file);

        // BOM-Erkennung via StringHelper
        $bomEncoding = StringHelper::detectBomEncoding($content);
        if ($bomEncoding !== null) {
            self::logDebug("$bomEncoding BOM erkannt: $file");
            $content = StringHelper::stripBom($content);

            // Falls bereits UTF-8, direkt zurückgeben
            if ($bomEncoding === 'UTF-8') {
                return $content;
            }

            // Von BOM-erkanntem Encoding zu UTF-8 konvertieren
            $converted = mb_convert_encoding($content, 'UTF-8', $bomEncoding);
            if ($converted === false) {
                return self::logWarningAndReturn($content, "Konvertierung von $bomEncoding zu UTF-8 fehlgeschlagen für $file, nutze Original");
            }
            return self::logInfoAndReturn($converted, "Datei von $bomEncoding zu UTF-8 konvertiert: $file");
        }

        // Encoding erkennen (kein BOM vorhanden)
        $encoding = self::chardet($file);

        if ($encoding === false || $encoding === 'UTF-8' || $encoding === 'ASCII') {
            return self::logDebugAndReturn($content, "Keine Konvertierung nötig für $file (Encoding: " . ($encoding ?: 'unbekannt') . ")");
        }

        // Zu UTF-8 konvertieren
        $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
        if ($converted === false) {
            return self::logWarningAndReturn($content, "Konvertierung von $encoding zu UTF-8 fehlgeschlagen für $file, nutze Original");
        }

        return self::logInfoAndReturn($converted, "Datei von $encoding zu UTF-8 konvertiert: $file");
    }

    /**
     * Liest einen Teil einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @param int $length Maximale Anzahl Bytes (Standard: 4096).
     * @param int $offset Startposition in Bytes (Standard: 0).
     * @return string|false Der gelesene Inhalt oder false bei Fehler.
     */
    public static function readPartial(string $file, int $length = 4096, int $offset = 0): string|false {
        $file = self::getRealExistingFile($file);
        if ($file === false) return false;

        $content = file_get_contents($file, false, null, $offset, $length);
        if ($content === false) {
            return self::logErrorAndReturn(false, "Fehler beim partiellen Lesen der Datei: $file");
        }
        return self::logDebugAndReturn($content, "Datei partiell gelesen ($length Bytes ab $offset): $file");
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
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht lesbar: $file");
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Öffnen der Datei für readLines: $file");
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
     * Liefert die Zeilen einer Textdatei als Generator zurück, konvertiert zu UTF-8.
     * Speichereffizient: Liest zeilenweise und konvertiert jede Zeile einzeln.
     *
     * @param string $file        Pfad zur Datei.
     * @param bool $skipEmpty     Leere Zeilen überspringen (Standard: false).
     * @param int|null $maxLines  Begrenzung auf Anzahl Zeilen (Standard: null = alle).
     * @param int $startLine      Startzeile (Standard: 1).
     * @return Generator<string>
     * @throws FileNotFoundException
     */
    public static function readLinesAsUtf8(string $file, bool $skipEmpty = false, ?int $maxLines = null, int $startLine = 1): Generator {
        $file = self::getRealPath($file);
        if (!self::isReadable($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht lesbar: $file");
        }

        // Encoding vorab erkennen (liest nur 4KB)
        $encoding = self::chardet($file);
        $needsConversion = $encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII';

        self::logDebugIf($needsConversion, "readLinesAsUtf8: Konvertierung von $encoding zu UTF-8 für $file");

        // BOM-Handling für erste Zeile
        $isFirstLine = true;

        $handle = fopen($file, 'r');
        if ($handle === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Öffnen der Datei für readLinesAsUtf8: $file");
        }

        $count = 0;
        $currentLine = 0;

        while (($line = fgets($handle)) !== false) {
            // BOM bei erster Zeile entfernen
            if ($isFirstLine) {
                $isFirstLine = false;
                $bomEncoding = StringHelper::detectBomEncoding($line);
                if ($bomEncoding !== null) {
                    $line = StringHelper::stripBom($line);
                    // BOM-Encoding hat Priorität
                    if ($bomEncoding !== 'UTF-8') {
                        $encoding = $bomEncoding;
                        $needsConversion = true;
                    } elseif ($bomEncoding === 'UTF-8') {
                        $needsConversion = false;
                    }
                }
            }

            $currentLine++;
            if ($currentLine < $startLine) {
                continue;
            }

            $line = rtrim($line, "\r\n");

            if ($skipEmpty && trim($line) === '') {
                continue;
            }

            // Konvertierung nur wenn nötig
            if ($needsConversion) {
                $converted = mb_convert_encoding($line, 'UTF-8', $encoding);
                if ($converted !== false) {
                    $line = $converted;
                }
            }

            yield $line;

            $count++;
            if ($maxLines !== null && $count >= $maxLines) {
                break;
            }
        }

        fclose($handle);
    }

    /**
     * Liest die Zeilen einer Textdatei als Array zurück, konvertiert zu UTF-8.
     *
     * @param string $file        Pfad zur Datei.
     * @param bool $skipEmpty     Leere Zeilen überspringen (Standard: false).
     * @param int|null $maxLines  Begrenzung auf Anzahl Zeilen (Standard: null = alle).
     * @param int $startLine      Startzeile (Standard: 1).
     * @return string[] Array mit den Zeilen der Datei in UTF-8.
     * @throws FileNotFoundException
     */
    public static function readLinesAsArrayUtf8(string $file, bool $skipEmpty = false, ?int $maxLines = null, int $startLine = 1): array {
        return iterator_to_array(self::readLinesAsUtf8($file, $skipEmpty, $maxLines, $startLine), false);
    }

    /**
     * Liest die letzten N Zeilen einer Datei (wie Unix 'tail').
     * Nutzt einen effizienten Algorithmus, der vom Ende der Datei rückwärts liest.
     *
     * @param string $file      Pfad zur Datei.
     * @param int $lines        Anzahl der Zeilen vom Ende (Standard: 10).
     * @param bool $skipEmpty   Leere Zeilen überspringen (Standard: false).
     * @return string[] Array mit den letzten Zeilen der Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden oder lesbar ist.
     * @throws InvalidArgumentException Wenn $lines <= 0.
     */
    public static function tail(string $file, int $lines = 10, bool $skipEmpty = false): array {
        if ($lines <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Anzahl der Zeilen muss größer als 0 sein.");
        }

        $file = self::getRealPath($file);
        if (!self::isReadable($file)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht lesbar: $file");
        }

        $fileSize = self::size($file);
        if ($fileSize === 0) {
            return self::logDebugAndReturn([], "Datei ist leer: $file");
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Öffnen der Datei für tail: $file");
        }

        $result = [];
        $buffer = '';
        $chunkSize = 4096;
        $position = $fileSize;

        // Vom Ende der Datei rückwärts lesen
        while ($position > 0 && count($result) < $lines) {
            $readSize = min($chunkSize, $position);
            $position -= $readSize;

            fseek($handle, $position);
            $chunk = fread($handle, $readSize);

            if ($chunk === false) {
                break;
            }

            $buffer = $chunk . $buffer;

            // Zeilen extrahieren
            $bufferLines = explode("\n", $buffer);

            // Die erste Zeile könnte unvollständig sein, behalten für nächste Iteration
            $buffer = array_shift($bufferLines);

            // Zeilen in umgekehrter Reihenfolge durchgehen
            for ($i = count($bufferLines) - 1; $i >= 0; $i--) {
                $line = rtrim($bufferLines[$i], "\r");

                if ($skipEmpty && trim($line) === '') {
                    continue;
                }

                array_unshift($result, $line);

                if (count($result) >= $lines) {
                    break 2;
                }
            }
        }

        // Restlichen Buffer verarbeiten (erste Zeile der Datei)
        if (count($result) < $lines && $buffer !== '') {
            $line = rtrim($buffer, "\r");
            if (!$skipEmpty || trim($line) !== '') {
                array_unshift($result, $line);
            }
        }

        fclose($handle);

        // Auf gewünschte Anzahl begrenzen
        if (count($result) > $lines) {
            $result = array_slice($result, -$lines);
        }

        return self::logDebugAndReturn($result, "Tail: " . count($result) . " Zeilen gelesen aus $file");
    }

    /**
     * Liest die letzten N Zeilen einer Datei und konvertiert zu UTF-8.
     *
     * @param string $file      Pfad zur Datei.
     * @param int $lines        Anzahl der Zeilen vom Ende (Standard: 10).
     * @param bool $skipEmpty   Leere Zeilen überspringen (Standard: false).
     * @return string[] Array mit den letzten Zeilen der Datei in UTF-8.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden oder lesbar ist.
     * @throws InvalidArgumentException Wenn $lines <= 0.
     */
    public static function tailAsUtf8(string $file, int $lines = 10, bool $skipEmpty = false): array {
        $result = self::tail($file, $lines, $skipEmpty);

        // Encoding der Datei bestimmen
        $encoding = self::chardet($file);
        if ($encoding === false || $encoding === 'UTF-8' || $encoding === 'ASCII') {
            return $result;
        }

        // Zeilen zu UTF-8 konvertieren
        return array_map(
            fn(string $line): string => mb_convert_encoding($line, 'UTF-8', $encoding) ?: $line,
            $result
        );
    }

    /**
     * Schreibt Daten in die angegebene Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $data Die zu schreibenden Daten.
     * @throws FileNotWrittenException Wenn die Datei nicht geschrieben werden kann.
     */
    public static function write(string $file, string $data): void {
        self::validateNotReservedName($file);

        $file = self::getRealPath($file);
        if (file_put_contents($file, $data) === false) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Schreiben in die Datei: $file");
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
            self::logErrorAndThrow(Exception::class, "Fehler beim Löschen der Datei: $file");
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
            self::logErrorAndThrow(FileNotFoundException::class, "Datei existiert nicht: $file");
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
            return self::logErrorAndReturn(false, "Datei existiert nicht: $file");
        }
        if (!is_readable($file)) {
            return self::logErrorAndReturn(false, "Datei ist nicht lesbar: $file");
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
            self::logErrorIf($logging, "Datei existiert nicht: $file");
            return false;
        }
        $handle = @fopen($file, 'r');
        if ($handle === false) {
            return self::logDebugAndReturn(false, "Datei ist noch nicht bereit zum Lesen: $file");
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
                return self::logWarningAndReturn(false, "Datei existiert nicht mehr während Wartezeit: $file");
            }
            if (time() - $start >= $timeout) {
                return self::logErrorAndReturn(false, "Timeout beim Warten auf Datei: $file");
            }
            sleep(1);
        }
        return self::logDebugAndReturn(true, "Datei ist bereit: $file");
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

        self::validateNotReservedName($destinationFile);

        if (!self::exists($sourceFile)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Quelldatei existiert nicht: $sourceFile");
        }

        if (self::exists($destinationFile) && !$overwrite) {
            self::logWarning("Zieldatei existiert und wird nicht überschrieben: $destinationFile");
            return;
        }

        if (!@copy($sourceFile, $destinationFile)) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Kopieren von $sourceFile nach $destinationFile");
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
        $destinationFolder = Folder::getRealPath($destinationFolder);
        $destinationFile = $destinationFolder . DIRECTORY_SEPARATOR . ($destinationFileName ?? basename($sourceFile));

        self::validateNotReservedName($destinationFolder);
        self::validateNotReservedName($destinationFile);

        if (!self::exists($sourceFile)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Quelldatei existiert nicht: $sourceFile");
        }

        if (!Folder::exists($destinationFolder)) {
            self::logErrorAndThrow(FolderNotFoundException::class, "Zielverzeichnis existiert nicht: $destinationFolder");
        }

        if (self::exists($destinationFile) && !$overwrite) {
            self::logWarning("Zieldatei existiert bereits und wird nicht überschrieben: $destinationFile");
            return;
        } elseif (self::exists($destinationFile) && $overwrite) {
            self::logWarning("Zieldatei existiert bereits und wird versucht zu überschreiben: $destinationFile");
        }

        if (!@rename($sourceFile, $destinationFile)) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Verschieben von $sourceFile nach $destinationFile");
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

        self::validateNotReservedName($newName);

        if (!self::exists($oldName)) {
            self::logErrorAndThrow(FileNotFoundException::class, "Datei zum Umbenennen nicht gefunden: $oldName");
        }

        if (self::exists($newName)) {
            self::logErrorAndThrow(FileExistsException::class, "Zieldatei existiert bereits: $newName");
        }

        if ($newName === basename($newName)) {
            $newName = dirname($oldName) . DIRECTORY_SEPARATOR . $newName;
        }

        if (!rename($oldName, $newName)) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Umbenennen von $oldName nach $newName");
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
        self::validateNotReservedName($file);

        if (self::exists($file)) {
            self::logErrorAndThrow(FileExistsException::class, "Datei existiert bereits: $file");
        }

        if (file_put_contents($file, $content) === false) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Erstellen der Datei: $file");
        }

        if (!chmod($file, $permissions)) {
            self::logErrorAndThrow(Exception::class, "Fehler beim Setzen von Rechten ($permissions) für Datei: $file");
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
            return self::logErrorAndReturn(false, "Datei nicht lesbar oder nicht vorhanden: $file");
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return self::logErrorAndReturn(false, "Fehler beim Öffnen der Datei: $file");
        }

        $keywordsString = is_array($keywords) ? implode(', ', $keywords) : $keywords;

        while (($line = fgets($handle)) !== false) {
            if (StringHelper::containsKeyword($line, $keywords, $mode, $caseSensitive)) {
                $matchingLine = trim($line);
                fclose($handle);
                return self::logInfoAndReturn(true, "Schlüsselwörter [$keywordsString] in Datei gefunden: $matchingLine");
            }
        }

        fclose($handle);
        return self::logDebugAndReturn(false, "Keine Übereinstimmung in Datei: $file");
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
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht lesbar: $file");
        }

        $lines = 0;
        $handle = fopen($file, "r");
        if ($handle === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Öffnen der Datei für Zeilenzählung: $file");
        }

        while (!feof($handle)) {
            $line = fgets($handle);
            if ($skipEmpty && trim($line) === '') continue;
            $lines++;
        }
        fclose($handle);
        return self::logInfoAndReturn($lines, "Anzahl der Zeilen in $file: $lines");
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
            self::logErrorAndThrow(FileNotFoundException::class, "Datei nicht lesbar: $file");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Lesen der Datei zur Zeichenzählung: $file");
        }

        $length = mb_strlen($content, $encoding);
        return self::logInfoAndReturn($length, "Anzahl der Zeichen in $file: $length");
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

    /**
     * Prüft ob der Dateiname ein Windows-reservierter Gerätename ist.
     * Diese Namen existieren unter Windows "virtuell" und file_exists() gibt fälschlicherweise true zurück.
     * Diese Prüfung ist auch auf Linux für Samba-Kompatibilität relevant.
     *
     * @param string $file Der Pfad zur Datei.
     * @return bool True wenn es ein reservierter Name ist.
     */
    public static function isWindowsReservedName(string $file): bool {
        $basename = pathinfo($file, PATHINFO_FILENAME);
        return in_array(strtoupper($basename), self::WINDOWS_RESERVED_NAMES, true);
    }

    /**
     * Liest die ersten N Zeilen einer Datei (wie Unix 'head').
     *
     * @param string $file      Pfad zur Datei.
     * @param int $lines        Anzahl der Zeilen vom Anfang (Standard: 10).
     * @param bool $skipEmpty   Leere Zeilen überspringen (Standard: false).
     * @return string[] Array mit den ersten Zeilen der Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden oder lesbar ist.
     * @throws InvalidArgumentException Wenn $lines <= 0.
     */
    public static function head(string $file, int $lines = 10, bool $skipEmpty = false): array {
        if ($lines <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Anzahl der Zeilen muss größer als 0 sein.");
        }

        return self::readLinesAsArray($file, $skipEmpty, $lines, 1);
    }

    /**
     * Liest die ersten N Zeilen einer Datei und konvertiert zu UTF-8.
     *
     * @param string $file      Pfad zur Datei.
     * @param int $lines        Anzahl der Zeilen vom Anfang (Standard: 10).
     * @param bool $skipEmpty   Leere Zeilen überspringen (Standard: false).
     * @return string[] Array mit den ersten Zeilen der Datei in UTF-8.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden oder lesbar ist.
     * @throws InvalidArgumentException Wenn $lines <= 0.
     */
    public static function headAsUtf8(string $file, int $lines = 10, bool $skipEmpty = false): array {
        if ($lines <= 0) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Anzahl der Zeilen muss größer als 0 sein.");
        }

        return self::readLinesAsArrayUtf8($file, $skipEmpty, $lines, 1);
    }

    /**
     * Hängt Daten an das Ende einer Datei an.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $data Die anzuhängenden Daten.
     * @param bool $newline Zeilenumbruch vor den Daten einfügen (Standard: false).
     * @throws FileNotWrittenException Wenn die Datei nicht geschrieben werden kann.
     */
    public static function append(string $file, string $data, bool $newline = false): void {
        self::validateNotReservedName($file);

        $file = self::getRealPath($file);
        $content = $newline && self::exists($file) && self::size($file) > 0 ? PHP_EOL . $data : $data;

        if (file_put_contents($file, $content, FILE_APPEND) === false) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Anhängen an die Datei: $file");
        }
        self::logDebug("Daten erfolgreich an Datei angehängt: $file");
    }

    /**
     * Fügt Daten am Anfang einer Datei ein.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $data Die einzufügenden Daten.
     * @param bool $newline Zeilenumbruch nach den Daten einfügen (Standard: false).
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     * @throws FileNotWrittenException Wenn die Datei nicht geschrieben werden kann.
     */
    public static function prepend(string $file, string $data, bool $newline = false): void {
        self::validateNotReservedName($file);

        $file = self::getRealPath($file);
        $existingContent = self::exists($file) ? self::read($file) : '';
        $content = $newline ? $data . PHP_EOL . $existingContent : $data . $existingContent;

        if (file_put_contents($file, $content) === false) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Voranstellen an die Datei: $file");
        }
        self::logDebug("Daten erfolgreich am Anfang der Datei eingefügt: $file");
    }

    /**
     * Erstellt eine leere Datei oder aktualisiert den Zeitstempel (wie Unix 'touch').
     *
     * @param string $file Der Pfad zur Datei.
     * @param int|null $time Optionaler Unix-Timestamp für die Modifikationszeit.
     * @param int|null $atime Optionaler Unix-Timestamp für die Zugriffszeit.
     * @throws FileNotWrittenException Wenn die Datei nicht erstellt/aktualisiert werden kann.
     */
    public static function touch(string $file, ?int $time = null, ?int $atime = null): void {
        self::validateNotReservedName($file);

        $file = self::getRealPath($file);
        $time = $time ?? time();
        $atime = $atime ?? $time;

        if (!touch($file, $time, $atime)) {
            self::logErrorAndThrow(FileNotWrittenException::class, "Fehler beim Touch der Datei: $file");
        }
        self::logDebug("Touch erfolgreich: $file");
    }

    /**
     * Gibt den Zeitpunkt der letzten Modifikation zurück.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Unix-Timestamp der letzten Modifikation.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function modifiedTime(string $file): int {
        $file = self::resolveFile($file);
        $time = filemtime($file);
        if ($time === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Abrufen der Modifikationszeit: $file");
        }
        return $time;
    }

    /**
     * Gibt den Erstellungszeitpunkt zurück (plattformabhängig).
     * Hinweis: Auf Unix-Systemen wird oft die ctime (inode change time) zurückgegeben.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Unix-Timestamp der Erstellung.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function createdTime(string $file): int {
        $file = self::resolveFile($file);
        $time = filectime($file);
        if ($time === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Abrufen der Erstellungszeit: $file");
        }
        return $time;
    }

    /**
     * Gibt den Zeitpunkt des letzten Zugriffs zurück.
     *
     * @param string $file Der Pfad zur Datei.
     * @return int Unix-Timestamp des letzten Zugriffs.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function accessTime(string $file): int {
        $file = self::resolveFile($file);
        $time = fileatime($file);
        if ($time === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Abrufen der Zugriffszeit: $file");
        }
        return $time;
    }

    /**
     * Berechnet den Hash einer Datei.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $algorithm Hash-Algorithmus (Standard: 'sha256'). Weitere: 'md5', 'sha1', 'sha512', etc.
     * @return string Der berechnete Hash.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     * @throws InvalidArgumentException Wenn der Algorithmus ungültig ist.
     */
    public static function hash(string $file, string $algorithm = 'sha256'): string {
        $file = self::resolveFile($file);

        if (!in_array($algorithm, hash_algos(), true)) {
            self::logErrorAndThrow(InvalidArgumentException::class, "Ungültiger Hash-Algorithmus: $algorithm");
        }

        $hash = hash_file($algorithm, $file);
        if ($hash === false) {
            self::logErrorAndThrow(FileNotFoundException::class, "Fehler beim Berechnen des Hash für: $file");
        }

        return self::logDebugAndReturn($hash, "Hash ($algorithm) berechnet für: $file");
    }

    /**
     * Vergleicht zwei Dateien auf Gleichheit.
     *
     * @param string $file1 Pfad zur ersten Datei.
     * @param string $file2 Pfad zur zweiten Datei.
     * @param bool $byHash Vergleich per Hash (Standard: true) oder byte-für-byte (false).
     * @param string $algorithm Hash-Algorithmus für Hash-Vergleich (Standard: 'sha256').
     * @return bool True wenn die Dateien identisch sind.
     * @throws FileNotFoundException Wenn eine der Dateien nicht existiert.
     */
    public static function compare(string $file1, string $file2, bool $byHash = true, string $algorithm = 'sha256'): bool {
        $file1 = self::resolveFile($file1);
        $file2 = self::resolveFile($file2);

        // Schneller Größenvergleich zuerst
        if (self::size($file1) !== self::size($file2)) {
            return false;
        }

        if ($byHash) {
            return self::hash($file1, $algorithm) === self::hash($file2, $algorithm);
        }

        // Byte-für-byte Vergleich
        $handle1 = fopen($file1, 'rb');
        $handle2 = fopen($file2, 'rb');

        if ($handle1 === false || $handle2 === false) {
            if ($handle1) fclose($handle1);
            if ($handle2) fclose($handle2);
            return false;
        }

        $equal = true;
        while (!feof($handle1) && !feof($handle2)) {
            if (fread($handle1, 8192) !== fread($handle2, 8192)) {
                $equal = false;
                break;
            }
        }

        // Prüfe ob beide Dateien am Ende sind
        if ($equal && (feof($handle1) !== feof($handle2))) {
            $equal = false;
        }

        fclose($handle1);
        fclose($handle2);

        return $equal;
    }

    /**
     * Prüft ob eine Datei leer ist (0 Bytes).
     *
     * @param string $file Der Pfad zur Datei.
     * @return bool True wenn die Datei leer ist.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function isEmpty(string $file): bool {
        return self::size($file) === 0;
    }

    /**
     * Filtert Zeilen einer Datei nach einem Pattern (wie Unix 'grep').
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $pattern Das Suchmuster (Regex oder String).
     * @param bool $isRegex Pattern als Regex interpretieren (Standard: false).
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: false).
     * @param bool $invert Nicht-passende Zeilen zurückgeben (Standard: false).
     * @return string[] Array mit den passenden Zeilen.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function grep(string $file, string $pattern, bool $isRegex = false, bool $caseSensitive = false, bool $invert = false): array {
        $file = self::resolveFile($file);
        $result = [];

        foreach (self::readLines($file) as $line) {
            $matches = false;

            if ($isRegex) {
                $flags = $caseSensitive ? '' : 'i';
                $matches = preg_match("/$pattern/$flags", $line) === 1;
            } else {
                $matches = $caseSensitive
                    ? str_contains($line, $pattern)
                    : str_contains(strtolower($line), strtolower($pattern));
            }

            if ($invert ? !$matches : $matches) {
                $result[] = $line;
            }
        }

        return self::logDebugAndReturn($result, "Grep: " . count($result) . " Zeilen gefunden in $file");
    }

    /**
     * Filtert Zeilen einer Datei nach einem Pattern und gibt Zeilennummern zurück.
     *
     * @param string $file Der Pfad zur Datei.
     * @param string $pattern Das Suchmuster (Regex oder String).
     * @param bool $isRegex Pattern als Regex interpretieren (Standard: false).
     * @param bool $caseSensitive Groß-/Kleinschreibung beachten (Standard: false).
     * @return array<int, string> Array mit Zeilennummer => Inhalt.
     * @throws FileNotFoundException Wenn die Datei nicht existiert.
     */
    public static function grepWithLineNumbers(string $file, string $pattern, bool $isRegex = false, bool $caseSensitive = false): array {
        $file = self::resolveFile($file);
        $result = [];
        $lineNumber = 0;

        foreach (self::readLines($file) as $line) {
            $lineNumber++;
            $matches = false;

            if ($isRegex) {
                $flags = $caseSensitive ? '' : 'i';
                $matches = preg_match("/$pattern/$flags", $line) === 1;
            } else {
                $matches = $caseSensitive
                    ? str_contains($line, $pattern)
                    : str_contains(strtolower($line), strtolower($pattern));
            }

            if ($matches) {
                $result[$lineNumber] = $line;
            }
        }

        return $result;
    }
}
