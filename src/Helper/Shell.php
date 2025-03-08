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
            self::$logger->error("exec() ist auf diesem System deaktiviert. Befehl konnte nicht ausgeführt werden: $command");
            throw new Exception("exec() ist deaktiviert. Der Befehl kann nicht ausgeführt werden.");
        }

        // Windows: cmd oder PowerShell
        if (PHP_OS_FAMILY === 'Windows') {
            if ($usePowerShell) {
                $command = "powershell -ExecutionPolicy Bypass -Command " . escapeshellarg($command);
            } else {
                $command = "cmd /c " . escapeshellarg($command);
            }
        }

        exec($command, $output, $resultCode);

        // Logging für Debug-Zwecke
        self::$logger->debug("Befehl ausgeführt: $command");
        self::$logger->debug("Exit-Code: $resultCode");
        if (!empty($output)) {
            self::$logger->debug("Befehlsausgabe: " . implode("\n", $output));
        }

        if ($resultCode !== $expectedResultCode) {
            $errorMessage = "Fehler bei der Ausführung des Kommandos: $command";

            if ($throwException) {
                self::$logger->error($errorMessage);
                throw new Exception($errorMessage . " Ausgabe: " . implode("\n", $output));
            } else {
                self::$logger->warning("$errorMessage (keine Exception geworfen)");
                return false;
            }
        }

        return true;
    }

    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand, bool $usePowerShell = false): string {
        if (PHP_OS_FAMILY === 'Windows') {
            return $usePowerShell ? "powershell -ExecutionPolicy Bypass -Command " . escapeshellarg($windowsCommand) : $windowsCommand;
        }
        return $unixCommand;
    }
}
