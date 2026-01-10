<?php
/*
 * Created on   : Sun Dec 29 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JsonFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\Data\JsonHelper;
use CommonToolkit\Helper\FileSystem\File;
use Exception;
use InvalidArgumentException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;

/**
 * Helper-Klasse für JSON-Datei-Verarbeitung.
 *
 * Bietet Funktionen für:
 * - JSON-Datei-Validierung und -Parsing
 * - Schema-Validierung gegen JSON Schema
 * - Pretty-Print und Minify von JSON-Dateien
 * - JSONPath-ähnliche Pfad-Extraktion aus Dateien
 * - Merge-Operationen zwischen JSON-Dateien
 * - Sichere JSON-Datei-Operationen
 */
class JsonFile extends HelperAbstract {

    /**
     * Prüft, ob eine JSON-Datei syntaktisch korrekt ist.
     *
     * @param string $file Der Dateipfad
     * @return bool True wenn gültig, false andernfalls
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function isValid(string $file): bool {
        try {
            $content = File::read(self::resolveFile($file));
            return JsonHelper::isValid($content);
        } catch (Exception $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Dekodiert eine JSON-Datei sicher mit ausführlicher Fehlerbehandlung.
     *
     * @param string $file Der Dateipfad
     * @param bool $associative Ob Objekte als assoziative Arrays zurückgegeben werden sollen
     * @param int $depth Maximale Verschachtelungstiefe
     * @return mixed Die dekodierte JSON-Struktur
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     * @throws InvalidArgumentException Bei ungültigem JSON
     */
    public static function decode(string $file, bool $associative = true, int $depth = 512): mixed {
        $content = File::read(self::resolveFile($file));
        return JsonHelper::decode($content, $associative, $depth);
    }

    /**
     * Speichert Daten als JSON-Datei mit sicherer Fehlerbehandlung.
     *
     * @param string $file Der Dateipfad zum Speichern
     * @param mixed $data Die zu kodierenden Daten
     * @param int $flags JSON-Encoding-Flags
     * @param int $depth Maximale Verschachtelungstiefe
     * @return bool True bei Erfolg, false bei Fehler
     */
    public static function encode(string $file, mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, int $depth = 512): bool {
        try {
            $json = JsonHelper::encode($data, $flags, $depth);

            // Stelle sicher, dass das Verzeichnis existiert
            $dir = dirname($file);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                    return self::logErrorAndReturn(false, "Konnte Verzeichnis nicht erstellen: {$dir}");
                }
            }

            $result = file_put_contents($file, $json);
            if ($result === false) {
                return self::logErrorAndReturn(false, "Konnte JSON nicht in Datei schreiben: {$file}");
            }

            return self::logInfoAndReturn(true, "JSON erfolgreich gespeichert: {$file}");
        } catch (InvalidArgumentException $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Formatiert eine JSON-Datei für bessere Lesbarkeit (Pretty-Print).
     *
     * @param string $file Der Dateipfad
     * @param string|null $outputFile Optional: Pfad für die formatierte Ausgabe (überschreibt Originaldatei wenn null)
     * @return bool True bei Erfolg, false bei Fehler
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function prettyPrint(string $file, ?string $outputFile = null): bool {
        try {
            $content = File::read(self::resolveFile($file));
            $prettyJson = JsonHelper::prettyPrint($content);

            $targetFile = $outputFile ?? $file;
            $result = file_put_contents($targetFile, $prettyJson);

            if ($result === false) {
                return self::logErrorAndReturn(false, "Konnte Pretty-Print JSON nicht speichern: {$targetFile}");
            }

            return self::logInfoAndReturn(true, "JSON Pretty-Print gespeichert: {$targetFile}");
        } catch (Exception $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Minimiert eine JSON-Datei durch Entfernen überflüssiger Leerzeichen.
     *
     * @param string $file Der Dateipfad
     * @param string|null $outputFile Optional: Pfad für die minimierte Ausgabe (überschreibt Originaldatei wenn null)
     * @return bool True bei Erfolg, false bei Fehler
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function minify(string $file, ?string $outputFile = null): bool {
        try {
            $content = File::read(self::resolveFile($file));
            $minifiedJson = JsonHelper::minify($content);

            $targetFile = $outputFile ?? $file;
            $result = file_put_contents($targetFile, $minifiedJson);

            if ($result === false) {
                return self::logErrorAndReturn(false, "Konnte minified JSON nicht speichern: {$targetFile}");
            }

            return self::logInfoAndReturn(true, "JSON minified und gespeichert: {$targetFile}");
        } catch (Exception $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Extrahiert einen Wert aus einer JSON-Datei anhand eines Pfades.
     *
     * @param string $file Der Dateipfad
     * @param string $path Pfad im Format 'data.transactions[0].amount'
     * @return mixed Der extrahierte Wert oder null wenn nicht gefunden
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function extractPath(string $file, string $path): mixed {
        try {
            $content = File::read(self::resolveFile($file));
            return JsonHelper::extractPath($content, $path);
        } catch (Exception $e) {
            self::logException($e);
            return null;
        }
    }

    /**
     * Validiert eine JSON-Datei gegen ein JSON Schema.
     *
     * @param string $file Der Dateipfad
     * @param array|string $schema Das JSON Schema als Array oder Pfad zu Schema-Datei
     * @return array{valid: bool, errors: string[]} Validierungsergebnis
     * @throws FileNotFoundException Wenn eine der Dateien nicht existiert
     */
    public static function validateSchema(string $file, array|string $schema): array {
        try {
            $content = File::read(self::resolveFile($file));

            // Wenn Schema ein String ist, behandle es als Dateipfad
            if (is_string($schema)) {
                $schemaContent = File::read(self::resolveFile($schema));
                $schema = JsonHelper::decode($schemaContent);
            }

            return JsonHelper::validateSchema($content, $schema);
        } catch (Exception $e) {
            return [
                'valid' => false,
                'errors' => ['Fehler bei Schema-Validierung: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Merged zwei JSON-Dateien rekursiv.
     *
     * @param string $file1 Pfad zur ersten JSON-Datei
     * @param string $file2 Pfad zur zweiten JSON-Datei
     * @param string|null $outputFile Optional: Pfad für das Ergebnis (überschreibt erste Datei wenn null)
     * @return bool True bei Erfolg, false bei Fehler
     * @throws FileNotFoundException Wenn eine der Dateien nicht existiert
     */
    public static function merge(string $file1, string $file2, ?string $outputFile = null): bool {
        try {
            $content1 = File::read(self::resolveFile($file1));
            $content2 = File::read(self::resolveFile($file2));

            $mergedJson = JsonHelper::merge($content1, $content2);

            $targetFile = $outputFile ?? $file1;
            $result = file_put_contents($targetFile, $mergedJson);

            if ($result === false) {
                return self::logErrorAndReturn(false, "Konnte gemergtes JSON nicht speichern: {$targetFile}");
            }

            return self::logInfoAndReturn(true, "JSON-Dateien erfolgreich gemergt: {$targetFile}");
        } catch (Exception $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Filtert sensible Daten aus einer JSON-Datei für Logging/Debug-Zwecke.
     *
     * @param string $file Der Dateipfad
     * @param string|null $outputFile Optional: Pfad für die maskierte Ausgabe (überschreibt Originaldatei wenn null)
     * @param array $sensitiveFields Array von Feldnamen die maskiert werden sollen
     * @param string $mask Der Maskierungsstring
     * @return bool True bei Erfolg, false bei Fehler
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function maskSensitiveData(
        string $file,
        ?string $outputFile = null,
        array $sensitiveFields = ['password', 'pin', 'cvv', 'token'],
        string $mask = '***'
    ): bool {
        try {
            $content = File::read(self::resolveFile($file));
            $maskedJson = JsonHelper::maskSensitiveData($content, $sensitiveFields, $mask);

            $targetFile = $outputFile ?? $file;
            $result = file_put_contents($targetFile, $maskedJson);

            if ($result === false) {
                return self::logErrorAndReturn(false, "Konnte maskiertes JSON nicht speichern: {$targetFile}");
            }

            return self::logInfoAndReturn(true, "Sensitive Daten in JSON maskiert: {$targetFile}");
        } catch (Exception $e) {
            self::logException($e);
            return false;
        }
    }

    /**
     * Liest JSON-Metadaten aus einer Datei.
     *
     * @param string $file Der Dateipfad
     * @return array{fileSize: int, isValid: bool, elementCount: int, depth: int} Metadaten
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function getMetaData(string $file): array {
        $resolvedFile = self::resolveFile($file);

        try {
            $content = File::read($resolvedFile);
            $fileSize = filesize($resolvedFile) ?: 0;
            $isValid = JsonHelper::isValid($content);

            $elementCount = 0;
            $depth = 0;

            if ($isValid) {
                $data = JsonHelper::decode($content);
                $elementCount = self::countElements($data);
                $depth = self::calculateDepth($data);
            }

            return [
                'fileSize' => $fileSize,
                'isValid' => $isValid,
                'elementCount' => $elementCount,
                'depth' => $depth
            ];
        } catch (Exception $e) {
            self::logException($e);
            return [
                'fileSize' => filesize($resolvedFile) ?: 0,
                'isValid' => false,
                'elementCount' => 0,
                'depth' => 0
            ];
        }
    }

    /**
     * Zählt die Anzahl der Elemente in einer JSON-Struktur rekursiv.
     *
     * @param mixed $data Die JSON-Daten
     * @return int Anzahl der Elemente
     */
    private static function countElements(mixed $data): int {
        if (is_array($data)) {
            $count = count($data);
            foreach ($data as $value) {
                if (is_array($value) || is_object($value)) {
                    $count += self::countElements($value);
                }
            }
            return $count;
        } elseif (is_object($data)) {
            $count = count((array)$data);
            foreach ($data as $value) {
                if (is_array($value) || is_object($value)) {
                    $count += self::countElements($value);
                }
            }
            return $count;
        }

        return 1;
    }

    /**
     * Berechnet die maximale Verschachtelungstiefe einer JSON-Struktur.
     *
     * @param mixed $data Die JSON-Daten
     * @param int $currentDepth Aktuelle Tiefe
     * @return int Maximale Tiefe
     */
    private static function calculateDepth(mixed $data, int $currentDepth = 0): int {
        if (!is_array($data) && !is_object($data)) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth;
        $items = is_array($data) ? $data : (array)$data;

        foreach ($items as $value) {
            if (is_array($value) || is_object($value)) {
                $depth = self::calculateDepth($value, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    /**
     * Konvertiert eine JSON-Datei zu einem assoziativen Array.
     *
     * @param string $file Der Dateipfad
     * @return array Das konvertierte Array
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function toArray(string $file): array {
        try {
            $content = File::read(self::resolveFile($file));
            $data = JsonHelper::decode($content, true);
            return is_array($data) ? $data : [$data];
        } catch (Exception $e) {
            self::logException($e);
            return [];
        }
    }

    /**
     * Erstellt eine Backup-Kopie einer JSON-Datei.
     *
     * @param string $file Der Dateipfad
     * @param string|null $backupSuffix Suffix für Backup-Datei (Standard: .backup)
     * @return string|false Pfad zur Backup-Datei oder false bei Fehler
     * @throws FileNotFoundException Wenn die Datei nicht existiert
     */
    public static function backup(string $file, ?string $backupSuffix = null): string|false {
        $resolvedFile = self::resolveFile($file);
        $backupSuffix ??= '.backup';
        $backupFile = $resolvedFile . $backupSuffix;

        if (copy($resolvedFile, $backupFile)) {
            return self::logInfoAndReturn($backupFile, "Backup erstellt: {$backupFile}");
        } else {
            return self::logErrorAndReturn(false, "Konnte Backup nicht erstellen: {$backupFile}");
        }
    }
}
