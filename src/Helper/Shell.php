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
     * Führt einen Shell-Befehl aus und liefert true/false anhand des Exit-Codes.
     */
    public static function executeShellCommand(string $command, array &$output = [], int &$resultCode = 0, bool $throwException = false, int $expectedResultCode = 0, bool $usePowerShell = false): bool {
        self::setLogger();

        // Vorabprüfung
        if (!function_exists('exec')) {
            $msg = "exec() ist deaktiviert. Der Befehl kann nicht ausgeführt werden.";
            self::logError("$msg Befehl: $command");
            throw new Exception($msg);
        }

        if (trim($command) === '') {
            $msg = "Es wurde kein Befehl übergeben. Bitte einen gültigen Befehl angeben.";
            self::logError($msg);
            throw new Exception($msg);
        }

        // Plattformabhängige Shell-Vorbereitung
        $command = self::buildPlatformCommand($command, $usePowerShell);

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
                self::logError($errorMessage);
                throw new Exception("$errorMessage Ausgabe: " . implode("\n", $output));
            }

            self::logWarning("$errorMessage (keine Exception geworfen)");
            return false;
        }

        return true;
    }

    /**
     * Führt einen Befehl aus und gibt die Ausgabe als String zurück.
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
     * Liefert den plattformspezifischen Befehl für eine CMD- oder PowerShell-Ausführung.
     */
    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand, bool $usePowerShell = false): string {
        $cmd = PlatformHelper::isWindows() ? $windowsCommand : $unixCommand;
        return self::buildPlatformCommand($cmd, $usePowerShell);
    }

    /**
     * Gibt alle konfigurierten ShellExecutables zurück.
     */
    public static function getConfiguredExecutables(): array {
        return self::getExecutableInstances('shellExecutables', ShellExecutable::class);
    }

    /**
     * Bereitet einen plattformspezifischen Befehl (CMD oder PowerShell) vor.
     */
    private static function buildPlatformCommand(string $command, bool $usePowerShell = false): string {
        if ($usePowerShell) {
            $shell = PlatformHelper::isWindows() ? 'powershell' : 'pwsh';
            return "$shell -ExecutionPolicy Bypass -Command " . escapeshellarg($command);
        }

        return $command;
    }
}
