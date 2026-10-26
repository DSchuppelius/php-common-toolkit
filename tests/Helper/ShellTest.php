<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Helper;

use CommonToolkit\Helper\Shell;
use ERRORToolkit\LoggerRegistry;
use Exception;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Stringable;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;
use Tests\Contracts\BaseTestCase;

class ShellTest extends BaseTestCase {
    /** PHP ohne php.ini: Erweiterungen der Maschine (Xdebug + JIT) schreiben sonst Startwarnungen in die verglichene Ausgabe. */
    private const PHP = [PHP_BINARY, '-n'];

    protected function tearDown(): void {
        Shell::setDefaultTimeout(null);
        Shell::setTimeoutProgram(null);
        putenv('CKT_SHELL_PROBE');
        parent::tearDown();
    }

    public function test_execute_shell_command_success(): void {
        $unixCommand = 'echo "Hello World"';
        $windowsCommand = 'echo Hello World';

        $command = Shell::getPlatformSpecificCommand($unixCommand, $windowsCommand);

        $output = [];
        $success = Shell::executeShellCommand($command, $output);

        $this->assertTrue($success, "The shell command should be successful.");
        $this->assertNotEmpty($output, "The output should not be empty.");
    }

    public function test_execute_shell_command_failure(): void {
        $command = "invalidcommand 2>&1";

        $output = [];
        $resultCode = 0;

        $this->expectException(Exception::class);
        Shell::executeShellCommand($command, $output, $resultCode, true);
    }

    // --- Shell::execute (argv-Bruecke mit Zeitgrenze) -------------------------

    public function test_execute_liefert_zeilen_und_exit_code_wie_exec(): void {
        $output = ['vorher'];
        $resultCode = -1;

        $ok = Shell::execute([...self::PHP, '-r', 'echo "eins\nzwei  \n"; fwrite(STDERR, "fehler\n"); exit(2);'], $output, $resultCode);

        $this->assertFalse($ok);
        $this->assertSame(2, $resultCode);
        // stdout und stderr gemeinsam, Zeilen rechts getrimmt, an vorhandene Eintraege angehaengt (wie exec())
        $this->assertSame(['vorher', 'eins', 'zwei', 'fehler'], $output);
    }

    public function test_execute_reicht_standardeingabe_durch(): void {
        $output = [];
        $resultCode = 0;

        $ok = Shell::execute([...self::PHP, '-r', 'echo strtoupper(stream_get_contents(STDIN));'], $output, $resultCode, null, "hallo\n");

        $this->assertTrue($ok);
        $this->assertSame(0, $resultCode);
        $this->assertSame(['HALLO'], $output);
    }

    public function test_execute_meldet_zeitgrenze_als_fehlschlag(): void {
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        $ok = Shell::execute([...self::PHP, '-r', 'sleep(5);'], $output, $resultCode, 0.5);

        $this->assertFalse($ok);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertSame(['Zeitgrenze 0.5 s ueberschritten'], $output);
        $this->assertLessThan(4.0, microtime(true) - $started);
    }

    public function test_timeout_message_formatiert_sekunden_kompakt(): void {
        $this->assertSame('Zeitgrenze 0.5 s ueberschritten', Shell::timeoutMessage(0.5));
        $this->assertSame('Zeitgrenze 60 s ueberschritten', Shell::timeoutMessage(60.0));
        $this->assertSame('Zeitgrenze 2.25 s ueberschritten', Shell::timeoutMessage(2.25));
    }

    // --- executeShellCommand: Semantik wie exec() -----------------------------

    public function test_execute_shell_command_liefert_dasselbe_wie_exec(): void {
        $this->requireUnix();
        // Leerzeilen bleiben, Leerraum am Zeilenende faellt weg, letzte Zeile ohne Umbruch zaehlt,
        // stderr kommt ueber das angehaengte 2>&1 in Schreibreihenfolge mit.
        $command = "{ printf 'a  \\n\\n b\\t\\r\\nletzte  '; printf '\\nfehler\\n' >&2; exit 3; }";

        $viaExec = ['vorher'];
        $execCode = 0;
        exec($command . ' 2>&1', $viaExec, $execCode);

        $viaShell = ['vorher'];
        $shellCode = 0;
        $ok = Shell::executeShellCommand($command, $viaShell, $shellCode);

        $this->assertFalse($ok);
        $this->assertSame(3, $shellCode);
        $this->assertSame($execCode, $shellCode);
        $this->assertSame($viaExec, $viaShell);
        $this->assertSame(['vorher', 'a', '', ' b', 'letzte', 'fehler'], $viaShell);
    }

    public function test_execute_shell_command_akzeptiert_erwarteten_exit_code(): void {
        $this->requireUnix();
        $output = [];
        $resultCode = 0;

        $this->assertTrue(Shell::executeShellCommand('exit 1', $output, $resultCode, false, 1));
        $this->assertSame(1, $resultCode);
    }

    public function test_execute_shell_command_gibt_putenv_variablen_weiter(): void {
        $this->requireUnix();
        // Wie exec(): auch zur Laufzeit gesetzte Variablen (z. B. eine ImageMagick-Policy) gelten
        putenv('CKT_SHELL_PROBE=gesetzt');
        $output = [];

        $this->assertTrue(Shell::executeShellCommand('echo "[$CKT_SHELL_PROBE]"', $output));
        $this->assertSame(['[gesetzt]'], $output);
    }

    // --- Zeitgrenze und Abbruch des Prozessbaums ------------------------------

    public function test_zeitgrenze_beendet_pipeline_samt_kindern(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        $ok = Shell::executeShellCommand("$marker | cat", $output, $resultCode, false, 0, false, true, 0.5);

        $this->assertFalse($ok);
        $this->assertLessThan(2.0, microtime(true) - $started);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertSame('Zeitgrenze 0.5 s ueberschritten', end($output));
        $this->assertSame([], $this->remainingProcesses($marker), 'Nach dem Abbruch darf kein Teil der Pipeline weiterlaufen.');
    }

    public function test_zeitgrenze_beendet_sh_c_enkel(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();
        $output = [];
        $resultCode = 0;

        $ok = Shell::executeShellCommand("sh -c '$marker; echo nie'", $output, $resultCode, false, 0, false, true, 0.5);

        $this->assertFalse($ok);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertNotContains('nie', $output);
        $this->assertSame([], $this->remainingProcesses($marker));
    }

    public function test_zeitgrenze_toetet_auch_prozesse_die_sigterm_ignorieren(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        // sleep erbt das ignorierte SIGTERM - erst SIGKILL nach der Nachfrist beendet es
        $ok = Shell::executeShellCommand("trap '' TERM; $marker", $output, $resultCode, false, 0, false, true, 0.5);

        $this->assertFalse($ok);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertLessThan(3.0, microtime(true) - $started);
        $this->assertSame([], $this->remainingProcesses($marker));
    }

    public function test_zeitgrenze_greift_auch_wenn_der_php_prozess_stirbt(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();
        // Der Befehl reist in der Umgebung, damit er nicht in der Befehlszeile des PHP-Prozesses steht
        $code = sprintf(
            'require %s; $o = []; $c = 0; CommonToolkit\Helper\Shell::executeShellCommand((string) getenv("CKT_SHELL_PROBE"), $o, $c, false, 0, false, true, 1.0);',
            var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true),
        );
        $php = new Process([...self::PHP, '-r', $code], null, ['CKT_SHELL_PROBE' => $marker], null, 30.0);
        $php->start();

        $this->waitFor(fn (): bool => $this->processesWith($marker) !== []);
        // Der Aufrufer stirbt hart (wie bei request_terminate_timeout oder kill -9)
        $php->signal(9);
        $php->wait();

        $this->assertNotSame([], $this->processesWith($marker), 'Das Werkzeug lebt nach dem Tod des Aufrufers zunaechst weiter.');
        $this->assertSame([], $this->remainingProcesses($marker, 4.0), 'GNU timeout beendet es trotzdem nach Ablauf der Zeitgrenze.');
    }

    public function test_zeitgrenze_ohne_gnu_timeout_bricht_trotzdem_ab(): void {
        $this->requireUnix();
        Shell::setTimeoutProgram(false);
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        $ok = Shell::executeShellCommand('exec sleep 5', $output, $resultCode, false, 0, false, true, 0.5);

        $this->assertFalse($ok);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertSame(['Zeitgrenze 0.5 s ueberschritten'], $output);
        $this->assertLessThan(2.0, microtime(true) - $started);
    }

    public function test_zeitgrenze_wirft_auf_wunsch(): void {
        $this->requireUnix();
        $output = [];
        $resultCode = 0;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Zeitgrenze 0.5 s ueberschritten');
        Shell::executeShellCommand('sleep 5', $output, $resultCode, true, 0, false, true, 0.5);
    }

    public function test_standard_zeitgrenze_ist_prozessweit_einstellbar(): void {
        $this->requireUnix();
        $this->assertSame(Shell::DEFAULT_TIMEOUT, Shell::getDefaultTimeout());
        $this->assertSame(180.0, Shell::DEFAULT_TIMEOUT);

        Shell::setDefaultTimeout(0.5);
        $this->assertSame(0.5, Shell::getDefaultTimeout());
        $this->assertSame('', Shell::executeShell('sleep 5; echo nie'));

        Shell::setDefaultTimeout(null);
        $this->assertSame(Shell::DEFAULT_TIMEOUT, Shell::getDefaultTimeout());
    }

    public function test_execute_shell_reicht_eigene_zeitgrenze_durch(): void {
        $this->requireUnix();
        $started = microtime(true);

        $this->assertSame('', Shell::executeShell('sleep 5; echo nie', timeout: 0.5));
        $this->assertSame('ok', Shell::executeShell('echo ok', timeout: 5.0));
        $this->assertLessThan(3.0, microtime(true) - $started);
    }

    /**
     * @return array<string, array{0: float}>
     */
    public static function ungueltigeZeitgrenzen(): array {
        return ['null' => [0.0], 'negativ' => [-1.0], 'unendlich' => [INF], 'nan' => [NAN]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeZeitgrenzen')]
    public function test_ungueltige_zeitgrenze_wird_abgelehnt(float $timeout): void {
        $this->expectException(InvalidArgumentException::class);
        Shell::setDefaultTimeout($timeout);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeZeitgrenzen')]
    public function test_ungueltige_zeitgrenze_je_aufruf_wird_abgelehnt(float $timeout): void {
        $output = [];
        $resultCode = 0;

        $this->expectException(InvalidArgumentException::class);
        Shell::executeShellCommand('echo x', $output, $resultCode, false, 0, false, true, $timeout);
    }

    public function test_run_beendet_bei_zeitgrenze_den_ganzen_baum(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();

        $result = Shell::run(['/bin/sh', '-c', "$marker | cat"], 0.5);

        $this->assertTrue($result->timedOut);
        $this->assertNull($result->exitCode);
        $this->assertSame([], $this->remainingProcesses($marker));
    }

    // --- Keine unbegrenzten Laeufe (2.10) --------------------------------------

    public function test_run_ohne_zeitgrenze_nimmt_die_standard_zeitgrenze(): void {
        $this->requireUnix();
        Shell::setDefaultTimeout(0.5);

        $result = Shell::run([...self::PHP, '-r', 'sleep(5);'], null);

        $this->assertTrue($result->timedOut, 'null heisst seit 2.10 Standard-Zeitgrenze, nicht unbegrenzt');
        $this->assertLessThan(4.0, $result->duration);
    }

    public function test_execute_ohne_zeitgrenze_nimmt_die_standard_zeitgrenze(): void {
        $this->requireUnix();
        Shell::setDefaultTimeout(0.5);
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        $ok = Shell::execute([...self::PHP, '-r', 'sleep(5);'], $output, $resultCode);

        $this->assertFalse($ok);
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
        $this->assertSame(['Zeitgrenze 0.5 s ueberschritten'], $output);
        $this->assertLessThan(4.0, microtime(true) - $started);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeZeitgrenzen')]
    public function test_run_lehnt_ungueltige_zeitgrenze_ab(float $timeout): void {
        $this->expectException(InvalidArgumentException::class);
        Shell::run([...self::PHP, '-r', 'echo 1;'], $timeout);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeZeitgrenzen')]
    public function test_execute_lehnt_ungueltige_zeitgrenze_ab(float $timeout): void {
        $output = [];
        $resultCode = 0;

        $this->expectException(InvalidArgumentException::class);
        Shell::execute([...self::PHP, '-r', 'echo 1;'], $output, $resultCode, $timeout);
    }

    public function test_skalierte_zeitgrenze_waechst_mit_der_arbeit(): void {
        // Grundwert plus Zuschlag je Einheit
        $this->assertSame(380.0, Shell::scaledTimeout(20.0, 10.0));
        $this->assertSame(320.0, Shell::scaledTimeout(2.0, 10.0, 300.0));
        // Deckel
        $this->assertSame(Shell::MAX_SCALED_TIMEOUT, Shell::scaledTimeout(1.0e9, 10.0));
        $this->assertSame(500.0, Shell::scaledTimeout(1.0e9, 10.0, 300.0, 500.0));
        // Negative Mengen zaehlen wie 0
        $this->assertSame(300.0, Shell::scaledTimeout(-5.0, 10.0, 300.0));
        // Nie unter der prozessweiten Standard-Zeitgrenze
        $this->assertSame(Shell::DEFAULT_TIMEOUT, Shell::scaledTimeout(0.0, 1.0, 10.0));
        Shell::setDefaultTimeout(3600.0);
        $this->assertSame(3600.0, Shell::scaledTimeout(2.0, 10.0, 300.0));
        Shell::setDefaultTimeout(0.5);
        $this->assertSame(0.75, Shell::scaledTimeout(5.0, 0.05));
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: float|null, 3: float}>
     */
    public static function ungueltigeSkalierungen(): array {
        return [
            'menge unendlich' => [INF, 1.0, null, Shell::MAX_SCALED_TIMEOUT],
            'zuschlag nan' => [1.0, NAN, null, Shell::MAX_SCALED_TIMEOUT],
            'grundwert 0' => [1.0, 1.0, 0.0, Shell::MAX_SCALED_TIMEOUT],
            'deckel negativ' => [1.0, 1.0, null, -1.0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeSkalierungen')]
    public function test_skalierte_zeitgrenze_lehnt_ungueltige_angaben_ab(float $units, float $perUnit, ?float $base, float $cap): void {
        $this->expectException(InvalidArgumentException::class);
        Shell::scaledTimeout($units, $perUnit, $base, $cap);
    }

    // --- Meldungen ohne Befehlszeile (2.10) ------------------------------------

    public function test_exception_nennt_programm_exit_code_und_ausgabeende_ohne_befehlszeile(): void {
        $this->requireUnix();
        $output = [];
        $resultCode = 0;
        $lines = implode('; ', array_map(static fn (int $i): string => "echo zeile$i", range(1, 7)));
        $command = "sh -c '$lines; exit 3' geheimer-pfad-4711.pdf";

        try {
            Shell::executeShellCommand($command, $output, $resultCode, true);
            $this->fail('Exception erwartet');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->assertStringStartsWith('sh endete mit Exit-Code 3 (erwartet 0)', $message);
            $this->assertStringNotContainsString('geheimer-pfad-4711', $message);
            $this->assertStringNotContainsString('echo', $message);
            // Nur die letzten fuenf Zeilen
            $this->assertStringNotContainsString('zeile2', $message);
            $this->assertStringEndsWith("Letzte Ausgabe:\nzeile3\nzeile4\nzeile5\nzeile6\nzeile7", $message);
        }
        $this->assertSame(3, $resultCode);
    }

    public function test_exit_code_null_bewertet_den_exit_code_nicht(): void {
        $this->requireUnix();
        $output = [];
        $resultCode = 0;
        $messages = $this->captureLog(function () use (&$output, &$resultCode): void {
            $this->assertTrue(Shell::executeShellCommand("sh -c 'echo urteil; exit 1'", $output, $resultCode, expectedResultCode: null));
        });

        $this->assertSame(1, $resultCode);
        $this->assertSame(['urteil'], $output);
        $this->assertSame([], array_values(array_filter($messages, static fn (array $m): bool => $m[0] !== 'debug')), 'Exit 1 ist hier ein Urteil, keine Warnung');
        $this->assertSame('urteil', Shell::executeShell("sh -c 'echo urteil; exit 1'", expectedResultCode: null));

        // Zeitgrenze und Startfehler bleiben Fehler
        $this->assertFalse(Shell::executeShellCommand('sleep 5', $output, $resultCode, expectedResultCode: null, timeout: 0.5));
        $this->assertSame(Shell::EXIT_TIMEOUT, $resultCode);
    }

    public function test_exception_kuerzt_lange_ausgabezeilen(): void {
        $this->requireUnix();
        $output = [];
        $resultCode = 0;

        try {
            Shell::executeShellCommand('printf "%0500d\n" 0; exit 1', $output, $resultCode, true);
            $this->fail('Exception erwartet');
        } catch (Exception $e) {
            $this->assertStringEndsWith(str_repeat('0', 300) . ' ...', $e->getMessage());
        }
    }

    public function test_warnung_und_zeitgrenze_nennen_nur_den_programmnamen(): void {
        $this->requireUnix();
        $messages = $this->captureLog(function (): void {
            $output = [];
            $resultCode = 0;
            Shell::executeShellCommand("GEHEIM=wert sh -c 'exit 2' /kunden/beleg-4711.pdf", $output, $resultCode);
            Shell::executeShellCommand("sleep 5 # /kunden/beleg-4711.pdf", $output, $resultCode, timeout: 0.5);
        });

        $warnings = array_values(array_filter($messages, static fn (array $m): bool => $m[0] === 'warning'));
        $this->assertCount(2, $warnings, print_r($messages, true));
        $this->assertSame('sh endete mit Exit-Code 2 (erwartet 0)', $warnings[0][1]);
        $this->assertSame('sleep: Zeitgrenze 0.5 s ueberschritten, Prozessbaum beendet', $warnings[1][1]);
        foreach ($messages as [$level, $message]) {
            if ($level !== 'debug') {
                $this->assertStringNotContainsString('beleg-4711', $message, "$level darf die Befehlszeile nicht zeigen");
                $this->assertStringNotContainsString('GEHEIM', $message);
            }
        }
        // DEBUG darf die ganze Zeile zeigen
        $this->assertNotEmpty(array_filter($messages, static fn (array $m): bool => $m[0] === 'debug' && str_contains($m[1], 'beleg-4711')));
    }

    public function test_startfehler_meldet_den_grund_ohne_befehlszeile(): void {
        $reason = new ReflectionMethod(Shell::class, 'startFailureReason');
        $failed = new ProcessStartFailedException(new Process(['werkzeug', '--passwort=geheim', '/kunden/beleg.pdf']), 'fork failed');

        $this->assertStringContainsString('geheim', $failed->getMessage(), 'Symfony nimmt die Befehlszeile auf');
        $this->assertSame('fork failed', $reason->invoke(null, $failed));
    }

    public function test_run_meldet_echten_exit_code_124_nicht_als_zeitgrenze(): void {
        $this->requireUnix();

        $result = Shell::run(['/bin/sh', '-c', 'exit 124'], 30.0);

        $this->assertFalse($result->timedOut);
        $this->assertSame(124, $result->exitCode);
    }

    // --- executeParallel ------------------------------------------------------

    public function test_execute_parallel_liefert_exit_codes_und_bricht_haenger_ab(): void {
        $this->requireTreeKill();
        $marker = $this->sleepMarker();
        $started = microtime(true);

        $codes = Shell::executeParallel([
            'ok' => 'exit 0',
            'drei' => 'exit 3',
            'haengt' => "$marker | cat",
        ], 3, 0.5);

        $this->assertSame(['ok' => 0, 'drei' => 3, 'haengt' => Shell::EXIT_TIMEOUT], $this->sortedLike(['ok', 'drei', 'haengt'], $codes));
        $this->assertLessThan(3.0, microtime(true) - $started);
        $this->assertSame([], $this->remainingProcesses($marker));
    }

    public function test_execute_parallel_arbeitet_mehr_befehle_als_plaetze_ab(): void {
        $this->requireUnix();
        $commands = [];
        $expected = [];
        for ($i = 0; $i < 6; $i++) {
            $commands["k$i"] = "exit $i";
            $expected["k$i"] = $i;
        }

        $codes = Shell::executeParallel($commands, 2, 30.0);

        ksort($codes);
        $this->assertSame($expected, $codes);
        $this->assertSame([], Shell::executeParallel([]));
    }

    // --- Hilfen ---------------------------------------------------------------

    /**
     * Sammelt die Log-Eintraege waehrend $action als [Stufe, Meldung].
     *
     * @return list<array{0: string, 1: string}>
     */
    private function captureLog(callable $action): array {
        $logger = new class extends AbstractLogger {
            /** @var list<array{0: string, 1: string}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void {
                $this->records[] = [(string) $level, (string) $message];
            }
        };

        LoggerRegistry::setLogger($logger);
        try {
            $action();
        } finally {
            LoggerRegistry::resetLogger();
        }

        return $logger->records;
    }

    private function requireUnix(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Die Befehle sind sh-Syntax.');
        }
    }

    private function requireTreeKill(): void {
        $this->requireUnix();
        if (!is_dir('/proc/self')) {
            $this->markTestSkipped('Die Prozessliste wird ueber /proc gelesen.');
        }
        $probe = new Process(['timeout', '--version']);
        $probe->run();
        if (!$probe->isSuccessful() || !str_contains($probe->getOutput(), 'coreutils')) {
            $this->markTestSkipped('GNU timeout (coreutils) fehlt.');
        }
    }

    /**
     * Eindeutiger sleep-Aufruf, an dem sich die Prozesse dieses Tests erkennen lassen.
     */
    private function sleepMarker(): string {
        return sprintf('sleep %d.%04d', 30 + random_int(0, 9), random_int(0, 9999));
    }

    /**
     * @return list<string> Befehlszeilen laufender Prozesse, die $marker enthalten.
     */
    private function processesWith(string $marker): array {
        $found = [];
        foreach (glob('/proc/[0-9]*/cmdline') ?: [] as $file) {
            $cmdline = @file_get_contents($file);
            if (is_string($cmdline) && str_contains(str_replace("\0", ' ', $cmdline), $marker)) {
                $found[] = trim(str_replace("\0", ' ', $cmdline));
            }
        }

        return $found;
    }

    /**
     * Wartet, bis kein Prozess mit $marker mehr laeuft (Zombies haben keine Befehlszeile).
     *
     * @return list<string> Was nach Ablauf noch laeuft.
     */
    private function remainingProcesses(string $marker, float $seconds = 2.0): array {
        $this->waitFor(fn (): bool => $this->processesWith($marker) === [], $seconds);

        return $this->processesWith($marker);
    }

    private function waitFor(callable $condition, float $seconds = 2.0): void {
        $deadline = microtime(true) + $seconds;
        while (!$condition() && microtime(true) < $deadline) {
            usleep(20000);
        }
    }

    /**
     * @param list<string> $keys
     * @param array<string|int, int> $values
     * @return array<string, int>
     */
    private function sortedLike(array $keys, array $values): array {
        $sorted = [];
        foreach ($keys as $key) {
            $sorted[$key] = $values[$key] ?? -999;
        }

        return $sorted;
    }
}
