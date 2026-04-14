<?php
/*
 * Created on   : Sat Mar 08 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Shell.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Entities\Executables\ShellExecutable;
use Exception;

class Shell extends HelperAbstract {


    /**
     * Führt einen Shell-Befehl aus und gibt den Exit-Code zurück.
     *
     * @param string $command Der auszuführende Befehl.
     * @param array $output Referenz auf ein Array, in dem die Ausgabe des Befehls gespeichert wird.
     * @param int $resultCode Referenz auf eine Variable, in der der Exit-Code gespeichert wird.
     * @param bool $throwException Ob eine Exception geworfen werden soll, wenn der Befehl fehlschlägt.
     * @param int $expectedResultCode Der erwartete Exit-Code des Befehls.
     * @param bool $usePowerShell Ob PowerShell verwendet werden soll (Windows).
     * @param bool $captureStderr Ob stderr nach stdout umgeleitet werden soll (2>&1). Standard: true.
     * @return bool True, wenn der Befehl erfolgreich war, andernfalls false.
     * @throws Exception Wenn der Befehl nicht ausgeführt werden kann.
     */
    public static function executeShellCommand(string $command, array &$output = [], int &$resultCode = 0, bool $throwException = false, int $expectedResultCode = 0, bool $usePowerShell = false, bool $captureStderr = true): bool {
        // Vorabprüfung
        if (!function_exists('exec')) {
            self::logErrorAndThrow(Exception::class, "exec() ist deaktiviert. Der Befehl kann nicht ausgeführt werden. Befehl: $command");
        }

        if (trim($command) === '') {
            self::logErrorAndThrow(Exception::class, "Es wurde kein Befehl übergeben. Bitte einen gültigen Befehl angeben.");
        }

        // Plattformabhängige Shell-Vorbereitung
        $command = self::buildPlatformCommand($command, $usePowerShell);

        // stderr nach stdout umleiten, falls gewünscht und nicht bereits vorhanden
        if ($captureStderr && !self::hasStderrRedirect($command)) {
            $command .= ' 2>&1';
        }

        // Ausführung
        exec($command, $output, $resultCode);

        // Logging
        self::logDebug("Befehl ausgeführt: $command");
        self::logDebug("Exit-Code: $resultCode");

        if (!empty($output)) {
            self::logDebug("Befehlsausgabe: " . implode("\n", $output));
        }

        // Fehlerbehandlung
        if ($resultCode !== $expectedResultCode) {
            $errorMessage = "Fehler bei der Ausführung des Kommandos: $command";

            if ($throwException) {
                self::logErrorAndThrow(Exception::class, "$errorMessage Ausgabe: " . implode("\n", $output));
            }

            return self::logWarningAndReturn(false, "$errorMessage (keine Exception geworfen)");
        }

        return true;
    }

    /**
     * Führt einen Shell-Befehl aus und gibt die Ausgabe zurück.
     *
     * @param string $command Der auszuführende Befehl.
     * @param bool $throwException Ob eine Exception geworfen werden soll, wenn der Befehl fehlschlägt.
     * @param int $expectedResultCode Der erwartete Exit-Code des Befehls.
     * @param bool $usePowerShell Ob PowerShell verwendet werden soll (Windows).
     * @return string Die Ausgabe des Befehls.
     */
    public static function executeShell(
        string $command,
        bool $throwException = false,
        int $expectedResultCode = 0,
        bool $usePowerShell = false
    ): string {
        $output = [];
        $resultCode = 0;

        if (self::executeShellCommand($command, $output, $resultCode, $throwException, $expectedResultCode, $usePowerShell)) {
            return implode("\n", $output);
        }

        return '';
    }

    /**
     * Gibt den plattformspezifischen Befehl zurück.
     *
     * @param string $unixCommand
     * @param string $windowsCommand
     * @param boolean $usePowerShell
     * @return string
     */
    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand, bool $usePowerShell = false): string {
        $cmd = Platform::isWindows() ? $windowsCommand : $unixCommand;
        return self::buildPlatformCommand($cmd, $usePowerShell);
    }

    /**
     * Gibt die konfigurierten Shell-Executables zurück.
     *
     * @return array
     */
    public static function getConfiguredExecutables(): array {
        return self::getExecutableInstances('shellExecutables', ShellExecutable::class);
    }

    /**
     * Baut den plattformspezifischen Befehl auf.
     *
     * @param string $command
     * @param boolean $usePowerShell
     * @return string
     */
    private static function buildPlatformCommand(string $command, bool $usePowerShell = false): string {
        if ($usePowerShell) {
            $shell = Platform::isWindows() ? 'powershell' : 'pwsh';
            return "$shell -ExecutionPolicy Bypass -Command " . escapeshellarg($command);
        }

        return $command;
    }

    /**
     * Prüft ob im Befehl bereits eine stderr-Umleitung vorhanden ist.
     * Erkennt Unix (2>&1, 2>/dev/null) und Windows (2>NUL) Varianten.
     *
     * @param string $command
     * @return bool
     */
    private static function hasStderrRedirect(string $command): bool {
        return str_contains($command, '2>&1')
            || str_contains($command, '2>/dev/null')
            || stripos($command, '2>NUL') !== false;
    }
}
