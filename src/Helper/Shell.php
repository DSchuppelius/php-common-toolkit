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

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Entities\Executables\{ShellExecutable, ShellResult};
use Exception;
use InvalidArgumentException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class Shell extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../config/common_executables.json';

    /**
     * Startet ein Programm mit Argumentliste — ohne Shell, daher ohne
     * Quoting-Probleme und ohne Injektion über Argumente.
     *
     * Anders als {@see executeShellCommand()} gibt es Timeout, eigene
     * Umgebungsvariablen (z. B. ein Passwort, das nicht in der Prozessliste
     * erscheinen soll) und Streaming der Ausgabe. Geloggt wird nur der
     * Programmname — Argumente und Umgebung können Geheimnisse enthalten.
     *
     * Ein Timeout wirft nicht, sondern steht im Ergebnis (`timedOut`).
     *
     * @param list<string> $command Programm und Argumente, z. B. ['tar', '-xf', $archiv].
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt.
     * @param array<string, string>|null $env Zusätzliche Umgebungsvariablen (ergänzen die geerbten).
     * @param string|null $cwd Arbeitsverzeichnis.
     * @param (callable(string $buffer, bool $isErrorOutput): void)|null $onOutput Erhält die Ausgabe
     *        stückweise; die Standardausgabe landet dann nicht im Ergebnis.
     * @param string|null $input Daten für die Standardeingabe.
     * @return ShellResult Exit-Code, Ausgaben, Timeout und Laufzeit.
     * @throws InvalidArgumentException Wenn keine Programmangabe übergeben wird.
     */
    public static function run(array $command, ?float $timeout = 60.0, ?array $env = null, ?string $cwd = null, ?callable $onOutput = null, ?string $input = null): ShellResult {
        if ($command === [] || trim((string) $command[array_key_first($command)]) === '') {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Shell::run() braucht mindestens den Programmnamen.');
        }

        $program = basename((string) $command[array_key_first($command)]);
        $process = new Process(array_map('strval', $command), $cwd, $env, $input, $timeout);
        $started = microtime(true);
        $streamedOutput = $onOutput !== null;

        $callback = $streamedOutput
            ? static function (string $type, string $buffer) use ($onOutput): void {
                $onOutput($buffer, $type === Process::ERR);
            }
        : null;

        try {
            $process->run($callback);
            $timedOut = false;
        } catch (ProcessTimedOutException) {
            $timedOut = true;
        }

        $duration = microtime(true) - $started;
        $result = new ShellResult(
            $timedOut ? null : $process->getExitCode(),
            $streamedOutput ? '' : $process->getOutput(),
            $process->getErrorOutput(),
            $timedOut,
            $duration,
        );

        if ($timedOut) {
            self::logWarning(sprintf('Prozess %s nach %.1f s abgebrochen (Timeout)', $program, $duration));
        } else {
            self::logDebug(sprintf('Prozess %s beendet: Exit %d nach %.1f s', $program, (int) $result->exitCode, $duration));
        }

        return $result;
    }

    /**
     * Führt einen Shell-Befehl aus und gibt den Exit-Code zurück.
     *
     * @param string $command Der auszuführende Befehl.
     * @param list<string> $output Referenz auf ein Array, in dem die Ausgabe des Befehls gespeichert wird.
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
     */
    public static function getPlatformSpecificCommand(string $unixCommand, string $windowsCommand, bool $usePowerShell = false): string {
        $cmd = Platform::isWindows() ? $windowsCommand : $unixCommand;
        return self::buildPlatformCommand($cmd, $usePowerShell);
    }

    /**
     * Gibt die konfigurierten Shell-Executables zurück.
     *
     * @return array<array-key, ShellExecutable>
     */
    public static function getConfiguredExecutables(): array {
        return self::getExecutableInstances('shellExecutables', ShellExecutable::class);
    }

    /**
     * Baut den plattformspezifischen Befehl auf.
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
     */
    private static function hasStderrRedirect(string $command): bool {
        return str_contains($command, '2>&1')
            || str_contains($command, '2>/dev/null')
            || stripos($command, '2>NUL') !== false;
    }
}
