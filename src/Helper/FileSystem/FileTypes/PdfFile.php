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
use CommonToolkit\Helper\FileSystem\{File, Folder};
use CommonToolkit\Helper\{Platform, Shell};
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Exceptions\InvalidPasswordException;
use Exception;
use InvalidArgumentException;

/**
 * PDF-Metadaten, Prüfung und Passwortschutz über pdfinfo, mutool und qpdf.
 *
 * Passwörter gehen nie über die Befehlszeile (dort stünden sie für jeden
 * lokalen Nutzer lesbar in der Prozessliste): qpdf bekommt sie über eine
 * Argumentdatei (`@datei`, Rechte 0600, privater Temp-Ordner, sofort wieder
 * gelöscht). pdfinfo kennt nur `-opw`/`-upw`; geschützte Metadaten liest
 * {@see getMetaData()} deshalb aus einer mit qpdf entschlüsselten Kopie.
 */
class PdfFile extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/pdf_executables.json';

    /**
     * Gibt die Metadaten einer PDF-Datei zurück.
     *
     * Mit Passwort: Öffnet pdfinfo die Datei ohne Passwort (ungeschützt oder nur
     * Besitzerpasswort), kommen die Metadaten von dort. Sonst liest pdfinfo eine
     * mit qpdf entschlüsselte Kopie in einem privaten Temp-Ordner; `Encrypted`
     * ist dann `yes`. Dieser Weg braucht qpdf.
     *
     * @param string $file Der Pfad zur PDF-Datei.
     * @param string|null $password Benutzer- oder Besitzerpasswort.
     * @return array<string, mixed> Ein Array mit den Metadaten der PDF-Datei.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws InvalidPasswordException Wenn das Passwort nicht passt.
     * @throws Exception Wenn ein Fehler beim Abrufen der Metadaten auftritt.
     */
    public static function getMetaData(string $file, ?string $password = null): array {
        $file = self::resolveFile($file);

        if ($password !== null && $password !== '') {
            return self::getProtectedMetaData($file, $password);
        }

        $command = self::getConfiguredCommand('pdfinfo', ["[INPUT]" => $file]);
        $output = [];
        $resultCode = 0;

        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "pdfinfo wurde nicht konfiguriert oder ist nicht installiert.");
        }

        Shell::executeShellCommand($command, $output, $resultCode, timeout: Shell::TIMEOUT_PROBE);

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
        $command = self::getConfiguredCommand("pdf-check", ["[INPUT]" => $file]);
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

        $command = self::getConfiguredCommand("valid-pdf", ["[INPUT]" => $file]);
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

        $output = [];
        if (!self::runDecrypt($inputFile, $outputFile, $password, $output)) {
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

        // Leere Passwörter sind leere Zeilen der Argumentdatei
        $owner = $ownerPass ?? $userPass;
        $arguments = ['--encrypt', $userPass ?? '', $owner ?? '', '256'];
        $permissionText = trim((string) ($permissions ?? ''));
        if ($permissionText !== '') {
            array_push($arguments, ...(preg_split('/\s+/', $permissionText) ?: []));
        }
        $arguments[] = '--';

        $dir = self::privateTempDir();
        try {
            $command = self::getConfiguredCommand("pdf-encrypt", [
                "[ARGFILE]" => '@' . self::writeArgumentFile($dir, $arguments),
                "[INPUT]" => $inputFile,
                "[OUTPUT]" => $outputFile,
            ]);
            if (empty($command)) {
                self::logErrorAndThrow(Exception::class, "pdf-encrypt wurde nicht konfiguriert oder ist nicht installiert.");
            }

            $output = [];
            $resultCode = 0;
            if (!Shell::executeShellCommand($command, $output, $resultCode)) {
                return self::logErrorAndReturn(false, "Fehler beim Verschlüsseln der PDF-Datei $inputFile.");
            }
        } finally {
            Folder::delete($dir, true);
        }

        return File::exists($outputFile);
    }

    /**
     * Metadaten einer geschützten Datei, ohne das Passwort auf die Befehlszeile zu legen.
     *
     * @return array<string, mixed>
     */
    private static function getProtectedMetaData(string $file, string $password): array {
        try {
            return self::getMetaData($file);
        } catch (InvalidPasswordException) {
            // Benutzerpasswort nötig: entschlüsselte Kopie lesen
        }

        $dir = self::privateTempDir();
        try {
            $copy = $dir . DIRECTORY_SEPARATOR . 'entschluesselt.pdf';
            $output = [];
            if (!self::runDecrypt($file, $copy, $password, $output) || !File::exists($copy)) {
                if (stripos(implode("\n", $output), 'invalid password') !== false) {
                    self::logErrorAndThrow(InvalidPasswordException::class, "Falsches Passwort für PDF-Datei: $file");
                }
                self::logErrorAndThrow(Exception::class, "Geschützte PDF-Metadaten nicht lesbar (qpdf nötig): $file");
            }

            $metadata = self::getMetaData($copy);
            $metadata['Encrypted'] = 'yes';

            return $metadata;
        } finally {
            Folder::delete($dir, true);
        }
    }

    /**
     * qpdf --decrypt; ein Passwort reist in einer Argumentdatei.
     *
     * @param list<string> $output Ausgabezeilen von qpdf.
     */
    private static function runDecrypt(string $inputFile, string $outputFile, ?string $password, array &$output): bool {
        $dir = $password !== null && $password !== '' ? self::privateTempDir() : null;
        try {
            $command = self::getConfiguredCommand("pdf-decrypt", [
                "[ARGFILE]" => $dir !== null ? '@' . self::writeArgumentFile($dir, ['--password=' . $password]) : '',
                "[INPUT]" => $inputFile,
                "[OUTPUT]" => $outputFile,
            ]);
            if (empty($command)) {
                self::logErrorAndThrow(Exception::class, "pdf-decrypt wurde nicht konfiguriert oder ist nicht installiert.");
            }

            $resultCode = 0;

            return Shell::executeShellCommand($command, $output, $resultCode);
        } finally {
            if ($dir !== null) {
                Folder::delete($dir, true);
            }
        }
    }

    /**
     * Privater Temp-Ordner (zufälliger Name, 0700) für Argumentdateien und Kopien.
     */
    private static function privateTempDir(): string {
        $dir = Platform::getTempDirectory() . DIRECTORY_SEPARATOR . 'pdffile_' . bin2hex(random_bytes(8));
        Folder::create($dir, 0700);

        return $dir;
    }

    /**
     * Schreibt qpdf-Argumente zeilenweise in eine Datei mit Rechten 0600.
     *
     * @param list<string> $arguments
     * @throws InvalidArgumentException Wenn ein Argument einen Zeilenumbruch enthält.
     */
    private static function writeArgumentFile(string $dir, array $arguments): string {
        foreach ($arguments as $argument) {
            if (strpbrk($argument, "\r\n\0") !== false) {
                self::logErrorAndThrow(InvalidArgumentException::class, 'Passwörter und Argumente für qpdf dürfen keinen Zeilenumbruch enthalten.');
            }
        }

        return File::createTemp(implode("\n", $arguments) . "\n", 'qpdf-args-', null, 0600, $dir);
    }
}
