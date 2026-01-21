<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PdfFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\InvalidPasswordException;
use Exception;

class PdfFile extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/pdf_executables.json';

    /**
     * Gibt die Metadaten einer PDF-Datei zurück.
     *
     * @param string $file Der Pfad zur PDF-Datei.
     * @return array Ein Array mit den Metadaten der PDF-Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler beim Abrufen der Metadaten auftritt.
     */
    public static function getMetaData(string $file, ?string $password = null): array {
        $file = self::resolveFile($file);

        $args = [
            "[INPUT]" => escapeshellarg($file),
            "[PASSWORD]" => $password ? "-opw " . escapeshellarg($password) : ""
        ];

        $command = self::getConfiguredCommand("pdfinfo", $args);
        $output = [];
        $resultCode = 0;

        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
        }

        Shell::executeShellCommand($command, $output, $resultCode);

        if ($resultCode !== 0) {
            foreach ($output as $line) {
                $lower = strtolower($line);
                if (str_contains($lower, 'incorrect password')) {
                    self::logErrorAndThrow(InvalidPasswordException::class, "Falsches Passwort für PDF-Datei: $file");
                }
                if (str_contains($lower, 'syntax error') || str_contains($lower, 'error')) {
                    self::logErrorAndThrow(Exception::class, "Fehlerhafte PDF-Struktur: $file - $line");
                }
            }
            self::logErrorAndThrow(Exception::class, "Fehler beim Abrufen der PDF-Metadaten für $file. (Exit-Code: $resultCode)");
        }

        $metadata = [];
        foreach ($output as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $metadata[trim($key)] = trim($value);
            }
        }

        if (empty($metadata)) {
            self::logWarning("Keine Metadaten für Datei $file gefunden.");
        }

        return $metadata;
    }


    /**
     * Überprüft, ob die PDF-Datei verschlüsselt ist.
     *
     * @param string $file Der Pfad zur PDF-Datei.
     * @return bool True, wenn die PDF-Datei verschlüsselt ist, andernfalls false.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler bei der Überprüfung auftritt.
     */
    public static function isEncrypted(string $file): bool {
        $file = self::resolveFile($file);

        // Standardprüfung über pdfinfo-Metadaten
        try {
            $metadata = self::getMetaData($file);
            if (isset($metadata['Encrypted'])) {
                $encryptedValue = strtolower($metadata['Encrypted']);
                return str_contains($encryptedValue, 'yes') || str_contains($encryptedValue, 'true') || str_contains($encryptedValue, 'aes') || str_contains($encryptedValue, 'rc4') || str_contains($encryptedValue, '128') || str_contains($encryptedValue, '256');
            }
        } catch (InvalidPasswordException $e) {
            return true;
        }

        // Fallback über qpdf --check (Konfiguration)
        $command = self::getConfiguredCommand("pdf-check", ["[INPUT]" => escapeshellarg($file)]);
        if (empty($command)) {
            return self::logWarningAndReturn(false, "pdf-check nicht konfiguriert, keine Fallback-Prüfung möglich.");
        }

        $output = [];
        $resultCode = 0;
        Shell::executeShellCommand($command, $output, $resultCode);

        foreach ($output as $line) {
            $lower = strtolower($line);

            // Nur positive Befunde akzeptieren
            if (
                str_contains($lower, 'encrypted') &&
                !str_contains($lower, 'not encrypted') &&
                !str_contains($lower, 'unencrypted')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Überprüft, ob die PDF-Datei gültig ist.
     *
     * @param string $file Der Pfad zur PDF-Datei.
     * @return bool True, wenn die PDF-Datei gültig ist, andernfalls false.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler bei der Validierung auftritt.
     */
    public static function isValid(string $file): bool {
        $file = self::resolveFile($file);

        $command = self::getConfiguredCommand("valid-pdf", ["[INPUT]" => escapeshellarg($file)]);
        $output = [];
        $resultCode = 0;

        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "mutool bzw. pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
        } elseif (!Shell::executeShellCommand($command, $output, $resultCode, false, 1)) {
            return self::logErrorAndReturn(false, "Fehler bei der PDF-Validierung für $file.");
        }

        // Wenn die Ausgabe Fehler enthält, ist die PDF ungültig
        foreach ($output as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'Syntax.Error') !== false) {
                return self::logErrorAndReturn(false, "Syntaxfehler in PDF erkannt: $file");
            }
        }

        return true;
    }

    /**
     * Entschlüsselt eine verschlüsselte PDF-Datei.
     *
     * @param string $inputFile  Pfad zur verschlüsselten PDF-Datei.
     * @param string $outputFile Pfad zur entschlüsselten Zieldatei.
     * @param string|null $password Passwort zum Entschlüsseln.
     * @return bool True, wenn erfolgreich.
     * @throws FileNotFoundException
     * @throws Exception
     */
    public static function decrypt(string $inputFile, string $outputFile, ?string $password = null): bool {
        $inputFile = self::resolveFile($inputFile);

        $command = self::getConfiguredCommand(
            "pdf-decrypt",
            [
                "[INPUT]"  => escapeshellarg($inputFile),
                "[OUTPUT]" => escapeshellarg($outputFile),
                "[PASS]" => escapeshellarg($password ?? '')
            ]
        );

        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "pdf-decrypt wurde nicht konfiguriert oder ist nicht installiert.");
        }

        $output = [];
        $resultCode = 0;
        if (!Shell::executeShellCommand($command, $output, $resultCode)) {
            return self::logErrorAndReturn(false, "Fehler beim Entschlüsseln der PDF-Datei $inputFile.");
        }

        return File::exists($outputFile);
    }

    /**
     * Verschlüsselt eine PDF-Datei mit Passwortschutz.
     *
     * @param string $inputFile  Pfad zur Quell-PDF-Datei.
     * @param string $outputFile Pfad zur verschlüsselten Zieldatei.
     * @param string|null $userPass   Benutzerpasswort.
     * @param string|null $ownerPass Optionales Besitzerpasswort.
     * @param string|null $permissions z. B. '--allow=print,copy'
     * @return bool True, wenn erfolgreich.
     * @throws FileNotFoundException
     * @throws Exception
     */
    public static function encrypt(string $inputFile, string $outputFile, ?string $userPass = null, ?string $ownerPass = null, ?string $permissions = null): bool {
        $inputFile = self::resolveFile($inputFile);

        $params = [
            "[INPUT]"  => escapeshellarg($inputFile),
            "[OUTPUT]" => escapeshellarg($outputFile),
            "[UPASS]" => escapeshellarg($userPass ?? ''),
            "[OPASS]" => escapeshellarg($ownerPass ?? $userPass ?? ''),
            "[PERM]"   => trim((string)($permissions ?? ''))
        ];

        $command = self::getConfiguredCommand("pdf-encrypt", $params);
        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "pdf-encrypt wurde nicht konfiguriert oder ist nicht installiert.");
        }

        $output = [];
        $resultCode = 0;
        if (!Shell::executeShellCommand($command, $output, $resultCode)) {
            return self::logErrorAndReturn(false, "Fehler beim Verschlüsseln der PDF-Datei $inputFile.");
        }

        return File::exists($outputFile);
    }
}