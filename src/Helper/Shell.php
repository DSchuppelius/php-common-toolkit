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
use Symfony\Component\Process\Exception\{ProcessSignaledException, ProcessTimedOutException};
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Startet externe Programme - jeder Weg hier kennt eine Zeitgrenze.
 *
 * Unter Linux/Unix laeuft ein begrenzter Befehl unter GNU `timeout` in einer
 * eigenen Prozessgruppe: Nach Ablauf geht SIGTERM an die ganze Gruppe
 * (Pipelines, `sh -c`-Kinder, Enkel), nach einer kurzen Nachfrist SIGKILL.
 * Das greift auch dann, wenn der aufrufende PHP-Prozess vorher stirbt. Die
 * Gruppe bleibt in der Sitzung des Aufrufers - wer den Aufrufer samt Sitzung
 * beendet (`pkill -s`, systemd), trifft die Werkzeuge mit. Unter Windows
 * beendet Symfony Process den Prozessbaum (taskkill /T).
 *
 * Direkte Prozessaufrufe (exec, proc_open, Backticks ...) gehoeren nur in
 * diese Klasse; die PHPStan-Regel {@see \CommonToolkit\PHPStan\NoDirectProcessCallRule}
 * meldet sie ueberall sonst.
 */
class Shell extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../config/common_executables.json';

    /** Exit-Code bei Zeitueberschreitung (wie GNU timeout). */
    public const EXIT_TIMEOUT = 124;

    /**
     * Zeitgrenze in Sekunden fuer executeShellCommand(), executeShell() und
     * executeParallel(), wenn der Aufrufer keine eigene angibt. Prozessweit
     * aenderbar ueber {@see setDefaultTimeout()}.
     */
    public const DEFAULT_TIMEOUT = 180.0;

    /** Deckel fuer kurze Proben und Infoabfragen (pdfinfo, identify, file, --version). */
    public const TIMEOUT_PROBE = 30.0;

    /** Laengste Nachfrist zwischen SIGTERM und SIGKILL beim Abbruch. */
    private const KILL_GRACE = 5.0;

    /** Spielraum der PHP-seitigen Notbremse ueber Zeitgrenze und Nachfrist hinaus. */
    private const SAFETY_MARGIN = 5.0;

    /** Exit-Codes von GNU timeout nach Ablauf: 124 (SIGTERM genuegte) bzw. 137 (SIGKILL war noetig). */
    private const TIMEOUT_EXIT_CODES = [124, 137];

    /** Obergrenze gleichzeitiger Prozesse in executeParallel(). */
    private const MAX_PARALLEL = 16;

    /** Prozessweite Standard-Zeitgrenze; null = DEFAULT_TIMEOUT. */
    private static ?float $defaultTimeout = null;

    /** GNU/uutils timeout; false = nicht vorhanden, null = noch nicht geprueft. */
    private static string|false|null $timeoutProgram = null;

    /** @var resource|false|null Fehlerausgabe des PHP-Prozesses fuer nicht erfasstes stderr. */
    private static $stderr = null;

    /**
     * Setzt die Standard-Zeitgrenze prozessweit, z. B. im Worker fuer lange
     * OCR-Laeufe; null stellt {@see DEFAULT_TIMEOUT} wieder her.
     *
     * Eine Einstellung "unbegrenzt" gibt es bewusst nicht: genau sie liess ein
     * haengendes Werkzeug den Worker dauerhaft blockieren. Wer laenger braucht,
     * gibt eine grosse, aber endliche Zahl an.
     *
     * @param float|null $seconds Sekunden (> 0, endlich) oder null.
     * @throws InvalidArgumentException Bei nicht positiver oder nicht endlicher Angabe.
     */
    public static function setDefaultTimeout(?float $seconds): void {
        self::$defaultTimeout = $seconds === null ? null : self::validTimeout($seconds);
    }

    /**
     * Die aktuell gueltige Standard-Zeitgrenze in Sekunden.
     */
    public static function getDefaultTimeout(): float {
        return self::$defaultTimeout ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * Startet ein Programm mit Argumentliste — ohne Shell, daher ohne
     * Quoting-Probleme und ohne Injektion über Argumente.
     *
     * Anders als {@see executeShellCommand()} gibt es eigene
     * Umgebungsvariablen (z. B. ein Passwort, das nicht in der Prozessliste
     * erscheinen soll) und Streaming der Ausgabe. Geloggt wird nur der
     * Programmname — Argumente und Umgebung können Geheimnisse enthalten.
     *
     * Ein Timeout wirft nicht, sondern steht im Ergebnis (`timedOut`); beendet
     * wird dann der ganze Prozessbaum des Programms.
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

        $argv = array_map('strval', $command);
        $program = basename((string) $command[array_key_first($command)]);
        // 0 und unendlich hiessen bei Symfony schon immer "ohne Grenze"
        $bounded = $timeout !== null && is_finite($timeout) && $timeout > 0.0;
        $wrapped = false;
        if ($bounded) {
            [$argv, $wrapped] = self::boundArgv($argv, $timeout);
        }

        $process = new Process($argv, $cwd, self::inheritedEnv($env), $input, $wrapped ? self::safetyTimeout((float) $timeout) : $timeout);
        $started = microtime(true);
        $streamedOutput = $onOutput !== null;

        $callback = $streamedOutput
            ? static function (string $type, string $buffer) use ($onOutput): void {
                $onOutput($buffer, $type === Process::ERR);
            }
        : null;

        $timedOut = self::runBounded($process, $wrapped, $bounded ? $timeout : null, $callback, true);

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
     * Fuehrt eine Argumentliste ohne Shell aus und liefert das Ergebnis in der Form
     * von {@see executeShellCommand()}: Ausgabezeilen, Exit-Code und Erfolg als bool.
     *
     * Bruecke fuer Helper, die bisher exec() ohne Zeitgrenze nutzten: stdout und
     * stderr landen in Ankunftsreihenfolge in $output (wie bei `2>&1`), Zeilen ohne
     * Zeilenende, angehaengt an vorhandene Eintraege (wie exec()). Bei
     * Zeitueberschreitung ist das Ergebnis false, $resultCode ist EXIT_TIMEOUT und
     * $output endet mit "Zeitgrenze N s ueberschritten".
     *
     * @param list<string> $command Programm und Argumente, z. B. ['ffmpeg', '-i', $datei].
     * @param list<string> $output Referenz: Ausgabezeilen (stdout + stderr).
     * @param int $resultCode Referenz: Exit-Code bzw. EXIT_TIMEOUT.
     * @param float|null $timeout Sekunden bis zum Abbruch; null = unbegrenzt.
     * @param string|null $input Daten fuer die Standardeingabe.
     * @param string|null $cwd Arbeitsverzeichnis.
     * @return bool True bei Exit-Code 0 ohne Zeitueberschreitung.
     * @throws InvalidArgumentException Wenn keine Programmangabe uebergeben wird.
     */
    public static function execute(array $command, array &$output = [], int &$resultCode = 0, ?float $timeout = null, ?string $input = null, ?string $cwd = null): bool {
        $buffer = '';
        $result = self::run(
            $command,
            $timeout,
            null,
            $cwd,
            static function (string $chunk) use (&$buffer): void {
                $buffer .= $chunk;
            },
            $input,
        );

        $trimmed = rtrim($buffer, "\r\n");
        if ($trimmed !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $trimmed) ?: [] as $line) {
                $output[] = rtrim($line);
            }
            self::logDebug("Befehlsausgabe: $trimmed");
        }

        if ($result->timedOut) {
            $resultCode = self::EXIT_TIMEOUT;
            $output[] = self::timeoutMessage((float) $timeout);
            return false;
        }

        $resultCode = (int) $result->exitCode;

        return $result->isSuccessful();
    }

    /**
     * Einheitlicher Hinweistext fuer eine ueberschrittene Zeitgrenze, z. B.
     * "Zeitgrenze 0.5 s ueberschritten" oder "Zeitgrenze 60 s ueberschritten".
     */
    public static function timeoutMessage(float $timeout): string {
        $seconds = rtrim(rtrim(sprintf('%.3F', $timeout), '0'), '.');

        return sprintf('Zeitgrenze %s s ueberschritten', $seconds);
    }

    /**
     * Führt einen Shell-Befehl aus und gibt den Exit-Code zurück.
     *
     * Verhalten wie exec(): $output erhält die Zeilen der Standardausgabe ohne
     * Zeilenende und ohne Leerraum am Zeilenende, angehängt an vorhandene
     * Einträge; stderr landet dort nur über `2>&1` (Standard, siehe
     * $captureStderr), sonst in der Fehlerausgabe des PHP-Prozesses. Die
     * Umgebung des PHP-Prozesses gilt samt putenv()-Änderungen.
     *
     * Nach Ablauf der Zeitgrenze endet der ganze Prozessbaum (auch Pipelines
     * und deren Kinder); das Ergebnis ist dann false, $resultCode ist
     * {@see EXIT_TIMEOUT} und die letzte Zeile von $output ist
     * {@see timeoutMessage()}.
     *
     * @param string $command Der auszuführende Befehl.
     * @param list<string> $output Referenz auf ein Array, in dem die Ausgabe des Befehls gespeichert wird.
     * @param int $resultCode Referenz auf eine Variable, in der der Exit-Code gespeichert wird.
     * @param bool $throwException Ob eine Exception geworfen werden soll, wenn der Befehl fehlschlägt oder die Zeitgrenze greift.
     * @param int $expectedResultCode Der erwartete Exit-Code des Befehls.
     * @param bool $usePowerShell Ob PowerShell verwendet werden soll (Windows).
     * @param bool $captureStderr Ob stderr nach stdout umgeleitet werden soll (2>&1). Standard: true.
     * @param float|null $timeout Zeitgrenze in Sekunden; null = {@see getDefaultTimeout()}.
     * @return bool True, wenn der Befehl erfolgreich war, andernfalls false.
     * @throws Exception Wenn der Befehl nicht ausgeführt werden kann.
     * @throws InvalidArgumentException Bei nicht positiver oder nicht endlicher Zeitgrenze.
     */
    public static function executeShellCommand(string $command, array &$output = [], int &$resultCode = 0, bool $throwException = false, int $expectedResultCode = 0, bool $usePowerShell = false, bool $captureStderr = true, ?float $timeout = null): bool {
        // Vorabprüfung
        if (!function_exists('proc_open')) {
            self::logErrorAndThrow(Exception::class, "proc_open() ist deaktiviert. Der Befehl kann nicht ausgeführt werden. Befehl: $command");
        }

        if (trim($command) === '') {
            self::logErrorAndThrow(Exception::class, "Es wurde kein Befehl übergeben. Bitte einen gültigen Befehl angeben.");
        }

        $timeout = $timeout === null ? self::getDefaultTimeout() : self::validTimeout($timeout);

        // Plattformabhängige Shell-Vorbereitung
        $command = self::buildPlatformCommand($command, $usePowerShell);

        // stderr nach stdout umleiten, falls gewünscht und nicht bereits vorhanden
        if ($captureStderr && !self::hasStderrRedirect($command)) {
            $command .= ' 2>&1';
        }

        // Ausführung: Zeilen schon während des Laufs zerlegen, damit große
        // Ausgaben nicht zusätzlich im Puffer von Symfony Process stehen
        [$process, $wrapped] = self::shellProcess($command, $timeout);
        $pending = '';
        $callback = static function (string $type, string $chunk) use (&$output, &$pending, $process): void {
            if ($type === Process::ERR) {
                self::passThroughStderr($chunk);
            } else {
                $pending .= $chunk;
                $end = strrpos($pending, "\n");
                if ($end !== false) {
                    foreach (explode("\n", substr($pending, 0, $end)) as $line) {
                        $output[] = self::trimOutputLine($line);
                    }
                    $pending = substr($pending, $end + 1);
                }
            }
            $process->clearOutput();
            $process->clearErrorOutput();
        };

        $timedOut = self::runBounded($process, $wrapped, $timeout, $callback, false);
        if ($pending !== '') {
            $output[] = self::trimOutputLine($pending);
        }

        // Logging
        self::logDebug("Befehl ausgeführt: $command");

        if ($timedOut) {
            $resultCode = self::EXIT_TIMEOUT;
            if (!empty($output)) {
                self::logDebug("Befehlsausgabe: " . implode("\n", $output));
            }
            $output[] = self::timeoutMessage($timeout);
            // Nur der Programmname: die Befehlszeile kann Pfade und Inhalte tragen
            $message = sprintf('%s: %s, Prozessbaum beendet', self::programName($command), self::timeoutMessage($timeout));

            if ($throwException) {
                self::logErrorAndThrow(Exception::class, $message);
            }

            return self::logWarningAndReturn(false, $message);
        }

        $resultCode = $process->getExitCode() ?? -1;
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
     * @param float|null $timeout Zeitgrenze in Sekunden; null = {@see getDefaultTimeout()}.
     * @return string Die Ausgabe des Befehls; leer bei Fehlschlag oder Zeitüberschreitung.
     */
    public static function executeShell(
        string $command,
        bool $throwException = false,
        int $expectedResultCode = 0,
        bool $usePowerShell = false,
        ?float $timeout = null
    ): string {
        $output = [];
        $resultCode = 0;

        if (self::executeShellCommand($command, $output, $resultCode, $throwException, $expectedResultCode, $usePowerShell, true, $timeout)) {
            return implode("\n", $output);
        }

        return '';
    }

    /**
     * Führt mehrere Shell-Befehle nebenläufig aus, jeden mit eigener
     * Zeitgrenze, und liefert die Exit-Codes unter denselben Schlüsseln.
     *
     * Gedacht für unabhängige Arbeit je Datei oder Seite (z. B. OCR je Seite).
     * Die Ausgaben werden verworfen - die Aufrufer lesen ihre Ergebnisse aus
     * Dateien. Ein Befehl, der die Zeitgrenze überschreitet, endet samt
     * Prozessbaum und meldet {@see EXIT_TIMEOUT}; einer, der nicht startet, -1.
     *
     * @param array<string|int, string> $commands Befehle je Schlüssel.
     * @param int|null $maxParallel Höchstzahl gleichzeitiger Prozesse; null = Kernzahl (höchstens 16).
     * @param float|null $timeout Zeitgrenze je Befehl in Sekunden; null = {@see getDefaultTimeout()}.
     * @return array<string|int, int> Exit-Code je Schlüssel.
     * @throws InvalidArgumentException Bei nicht positiver oder nicht endlicher Zeitgrenze.
     */
    public static function executeParallel(array $commands, ?int $maxParallel = null, ?float $timeout = null): array {
        if ($commands === []) {
            return [];
        }

        $timeout = $timeout === null ? self::getDefaultTimeout() : self::validTimeout($timeout);
        $limit = max(1, min($maxParallel ?? Platform::getCpuCoreCount(), self::MAX_PARALLEL));

        $pending = $commands;
        /** @var array<string|int, array{process: Process, wrapped: bool, pid: int|null, started: float}> $running */
        $running = [];
        $exitCodes = [];

        while ($pending !== [] || $running !== []) {
            while ($pending !== [] && count($running) < $limit) {
                $key = array_key_first($pending);
                $command = $pending[$key];
                unset($pending[$key]);

                [$process, $wrapped] = self::shellProcess($command, $timeout);
                $process->disableOutput();
                $started = microtime(true);
                try {
                    $process->start();
                } catch (Throwable $e) {
                    self::logWarning(sprintf('Prozess %s nicht startbar: %s', self::programName($command), $e->getMessage()));
                    $exitCodes[$key] = -1;
                    continue;
                }
                $running[$key] = ['process' => $process, 'wrapped' => $wrapped, 'pid' => $process->getPid(), 'started' => $started];
            }

            $finished = false;
            foreach ($running as $key => $entry) {
                $process = $entry['process'];
                try {
                    $process->checkTimeout();
                } catch (ProcessTimedOutException) {
                    if ($entry['wrapped'] && $entry['pid'] !== null) {
                        self::killProcessGroup($entry['pid']);
                    }
                    $exitCodes[$key] = self::EXIT_TIMEOUT;
                    unset($running[$key]);
                    $finished = true;
                    self::logWarning(sprintf('%s: %s, Prozessbaum beendet', self::programName($commands[$key]), self::timeoutMessage($timeout)));
                    continue;
                }

                if ($process->isRunning()) {
                    continue;
                }

                $exitCode = $process->getExitCode() ?? -1;
                if (self::expiredByTimeoutProgram($entry['wrapped'], $timeout, $exitCode, $entry['started'])) {
                    $exitCode = self::EXIT_TIMEOUT;
                    self::logWarning(sprintf('%s: %s, Prozessbaum beendet', self::programName($commands[$key]), self::timeoutMessage($timeout)));
                }
                $exitCodes[$key] = $exitCode;
                unset($running[$key]);
                $finished = true;
            }

            if (!$finished && $running !== []) {
                usleep(20000);
            }
        }

        return $exitCodes;
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
     * Legt fest, ob begrenzte Befehle unter GNU timeout laufen (Prozessgruppe,
     * Abbruch des ganzen Baums). Nur fuer Tests: false erzwingt den Rueckfall
     * ohne timeout, null prueft beim naechsten Aufruf erneut.
     *
     * @internal
     */
    public static function setTimeoutProgram(string|false|null $program): void {
        self::$timeoutProgram = $program;
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

    /**
     * Prozess fuer eine Shell-Befehlszeile: unter Unix `/bin/sh -c` wie exec(),
     * unter Windows ueber cmd.
     *
     * @return array{0: Process, 1: bool} Prozess und ob GNU timeout ihn begrenzt.
     */
    private static function shellProcess(string $command, float $timeout): array {
        if (Platform::isWindows()) {
            return [Process::fromShellCommandline($command, null, self::inheritedEnv(null), null, $timeout), false];
        }

        [$argv, $wrapped] = self::boundArgv(['/bin/sh', '-c', $command], $timeout);

        return [new Process($argv, null, self::inheritedEnv(null), null, $wrapped ? self::safetyTimeout($timeout) : $timeout), $wrapped];
    }

    /**
     * Stellt GNU timeout voran: eigene Prozessgruppe, nach Ablauf SIGTERM an die
     * ganze Gruppe, nach der Nachfrist SIGKILL. Ohne passendes timeout bleibt die
     * Argumentliste unveraendert; dann bricht Symfony Process nach $timeout ab und
     * trifft nur den direkten Kindprozess.
     *
     * @param list<string> $argv
     * @return array{0: list<string>, 1: bool} Argumentliste und ob timeout vorangestellt wurde.
     */
    private static function boundArgv(array $argv, float $timeout): array {
        $program = self::timeoutProgram();
        if ($program === null) {
            return [$argv, false];
        }

        return [[$program, '-k', self::formatSeconds(self::killGrace($timeout)), self::formatSeconds($timeout), ...$argv], true];
    }

    /**
     * Startet den Prozess, wartet und beendet bei Zeitueberschreitung den ganzen
     * Prozessbaum.
     *
     * @param (callable(string, string): void)|null $callback
     * @param bool $throwOnSignal Ob ein von aussen per Signal beendeter Prozess als
     *        Exception durchgereicht wird (Symfony-Verhalten von run()); sonst zaehlt
     *        der Exit-Code 128 + Signal wie in der Shell.
     * @return bool Ob die Zeitgrenze gegriffen hat.
     */
    private static function runBounded(Process $process, bool $wrapped, ?float $timeout, ?callable $callback, bool $throwOnSignal): bool {
        $started = microtime(true);
        $process->start($callback);
        $pid = $process->getPid();

        try {
            $process->wait();
        } catch (ProcessTimedOutException) {
            // Notbremse: Symfony hat den direkten Kindprozess gestoppt, die
            // Prozessgruppe von GNU timeout folgt.
            if ($wrapped && $pid !== null) {
                self::killProcessGroup($pid);
            }

            return true;
        } catch (ProcessSignaledException $e) {
            // Braucht es nach der Nachfrist SIGKILL, trifft GNU timeout damit die
            // eigene Gruppe und also auch sich selbst
            if (self::expiredByTimeoutProgram($wrapped, $timeout, $process->getExitCode(), $started)) {
                return true;
            }
            if ($throwOnSignal) {
                throw $e;
            }

            return false;
        }

        return self::expiredByTimeoutProgram($wrapped, $timeout, $process->getExitCode(), $started);
    }

    /**
     * Ob GNU timeout den Prozess beendet hat: Exit 124 (SIGTERM genuegte) bzw.
     * 137 (SIGKILL), und die Zeitgrenze ist verstrichen - ein Programm, das
     * selbst mit 124 endet, zaehlt nicht als Zeitueberschreitung.
     */
    private static function expiredByTimeoutProgram(bool $wrapped, ?float $timeout, ?int $exitCode, float $started): bool {
        return $wrapped && $timeout !== null
            && in_array($exitCode, self::TIMEOUT_EXIT_CODES, true)
            && microtime(true) - $started >= $timeout;
    }

    /**
     * GNU timeout (auch uutils) gefunden? Andere timeout-Programme (BusyBox,
     * Windows) beenden die Prozessgruppe nicht und werden nicht verwendet.
     */
    private static function timeoutProgram(): ?string {
        if (self::$timeoutProgram === null) {
            self::$timeoutProgram = false;
            if (!Platform::isWindows()) {
                $probe = new Process(['timeout', '--version'], null, null, null, self::TIMEOUT_PROBE);
                try {
                    $probe->run();
                    if ($probe->isSuccessful() && str_contains($probe->getOutput(), 'coreutils')) {
                        self::$timeoutProgram = 'timeout';
                    }
                } catch (Throwable) {
                    // nicht vorhanden oder nicht startbar
                }

                if (self::$timeoutProgram === false) {
                    self::logWarning('GNU timeout (coreutils) fehlt: bei Zeitueberschreitung endet nur der direkte Kindprozess, nicht dessen Kinder.');
                }
            }
        }

        return self::$timeoutProgram === false ? null : self::$timeoutProgram;
    }

    /**
     * Beendet alle Prozesse einer Prozessgruppe (SIGKILL).
     */
    private static function killProcessGroup(int $processGroupId): void {
        if ($processGroupId <= 1) {
            return;
        }

        if (function_exists('posix_kill')) {
            @posix_kill(-$processGroupId, 9);
            return;
        }

        try {
            (new Process(['kill', '-KILL', '--', '-' . $processGroupId], null, null, null, self::TIMEOUT_PROBE))->run();
        } catch (Throwable) {
            // Gruppe existiert nicht mehr
        }
    }

    /**
     * Umgebung wie bei exec(): die echte Prozessumgebung samt putenv()-Aenderungen.
     *
     * Symfony Process gaebe von sich aus nur die beim Start bekannten Variablen
     * weiter (eine per putenv() gesetzte ImageMagick-Policy ginge verloren).
     * getenv() ohne Namen liefert unter FastCGI auch die Request-Variablen
     * (Header, URI ...); die gehoeren nicht in die Umgebung eines Werkzeugs und
     * werden mit false ausgeschlossen - nur was getenv($name, true) aus der
     * echten Umgebung liest, geht weiter. $extra ergaenzt bzw. ueberschreibt.
     *
     * @param array<string, string>|null $extra
     * @return array<string, string|false>
     */
    private static function inheritedEnv(?array $extra): array {
        $env = [];
        foreach (array_keys(getenv() + $_ENV + $_SERVER) as $name) {
            $env[(string) $name] = getenv((string) $name, true);
        }

        return ($extra ?? []) + $env;
    }

    private static function validTimeout(float $seconds): float {
        if (!is_finite($seconds) || $seconds <= 0.0) {
            self::logErrorAndThrow(InvalidArgumentException::class, sprintf('Die Zeitgrenze muss eine positive, endliche Zahl sein (erhalten: %s).', var_export($seconds, true)));
        }

        return $seconds;
    }

    private static function killGrace(float $timeout): float {
        return min(self::KILL_GRACE, $timeout);
    }

    private static function safetyTimeout(float $timeout): float {
        return $timeout + self::killGrace($timeout) + self::SAFETY_MARGIN;
    }

    /**
     * Sekunden fuer GNU timeout, auf Millisekunden aufgerundet und nie 0
     * (0 hiesse dort "ohne Grenze").
     */
    private static function formatSeconds(float $seconds): string {
        $millis = max(1, (int) ceil($seconds * 1000));
        $text = sprintf('%d.%03d', intdiv($millis, 1000), $millis % 1000);

        return rtrim(rtrim($text, '0'), '.');
    }

    /**
     * Zeile wie bei exec(): ohne Leerraum am Ende (isspace).
     */
    private static function trimOutputLine(string $line): string {
        return rtrim($line, " \t\n\r\v\f");
    }

    /**
     * Nicht erfasstes stderr geht wie bei exec() an die Fehlerausgabe des PHP-Prozesses.
     */
    private static function passThroughStderr(string $chunk): void {
        if (self::$stderr === null) {
            self::$stderr = @fopen('php://stderr', 'wb');
        }
        if (self::$stderr !== false) {
            @fwrite(self::$stderr, $chunk);
        }
    }

    /**
     * Programmname einer Befehlszeile fuer Meldungen - ohne vorangestellte
     * Variablen (VAR=wert) und ohne Argumente, die Pfade oder Inhalte tragen.
     */
    private static function programName(string $command): string {
        if (preg_match('/^\s*(?:[A-Za-z_][A-Za-z0-9_]*=(?:\'[^\']*\'|"[^"]*"|\S*)\s+)*(\S+)/', $command, $match) === 1) {
            return basename(trim($match[1], '\'"'));
        }

        return 'Befehl';
    }
}
