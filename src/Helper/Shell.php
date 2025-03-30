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
use Exception;

class Shell extends HelperAbstract {

    public static function executeShellCommand(string $command, array &$output = [], int &$resultCode = 0, bool $throwException = false, int $expectedResultCode = 0, bool $usePowerShell = false): bool {
        self::setLogger();

        // Prüfen, ob exec() erlaubt ist
        if (!function_exists('exec')) {
            self::logError("exec() ist auf diesem System deaktiviert. Befehl konnte nicht ausgeführt werden: $command");
            throw new Exception("exec() ist deaktiviert. Der Befehl kann nicht ausgeführt werden.");
        } else if (empty($command)) {
            self::logError("Es wurde kein Befehl übergeben. Bitte einen gültigen Befehl angeben.");
            throw new Exception("Es wurde kein Befehl übergeben. Bitte einen gültigen Befehl angeben.");
        }

        // Windows: cmd oder PowerShell
        if ($usePowerShell) {
            $shell = (PHP_OS_FAMILY === 'Windows') ? 'powershell' : 'pwsh';
            $command = "$shell -ExecutionPolicy Bypass -Command " . escapeshellarg($command);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $command = "cmd /c \"$command\"";
        }

        exec($command, $output, $resultCode);

        // Logging für Debug-Zwecke
        self::logDebug("Befehl ausgeführt: $command");
        self::logDebug("Exit-Code: $resultCode");
        if (!empty($output)) {
            self::logDebug("Befehlsausgabe: " . implode("\n", $output));
        }

        if ($resultCode !== $expectedResultCode) {
            $errorMessage = "Fehler bei der Ausführung des Kommandos: $command";

            if ($throwException) {
                self::logError($errorMessage);
                throw new Exception($errorMessage . " Ausgabe: " . implode("\n", $output));
            } else {
                self::logWarning("$errorMessage (keine Exception geworfen)");
                return false;
            }
        }

        return true;
    }

    public static function executeShell(string $command, bool $throwException = false, int $expectedResultCode = 0, bool $usePowerShell = false): string {
        $output = [];
        $resultCode = 0;

        if (self::executeShellCommand($command, $output, $resultCode, $throwException, $expectedResultCode, $usePowerShell)) {
            return implode("\n", $output);
        }

        return '';
    }

    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand, bool $usePowerShell = false): string {
        if ($usePowerShell) {
            $shell = (PHP_OS_FAMILY === 'Windows') ? 'powershell' : 'pwsh';
            return "$shell -ExecutionPolicy Bypass -Command " . escapeshellarg(PHP_OS_FAMILY === 'Windows' ? $windowsCommand : $unixCommand);
        }

        return PHP_OS_FAMILY === 'Windows' ? $windowsCommand : $unixCommand;
    }
}
