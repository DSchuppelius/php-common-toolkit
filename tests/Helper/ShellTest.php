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
use Exception;
use Tests\Contracts\BaseTestCase;

class ShellTest extends BaseTestCase {
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

        $ok = Shell::execute([PHP_BINARY, '-r', 'echo "eins\nzwei  \n"; fwrite(STDERR, "fehler\n"); exit(2);'], $output, $resultCode);

        $this->assertFalse($ok);
        $this->assertSame(2, $resultCode);
        // stdout und stderr gemeinsam, Zeilen rechts getrimmt, an vorhandene Eintraege angehaengt (wie exec())
        $this->assertSame(['vorher', 'eins', 'zwei', 'fehler'], $output);
    }

    public function test_execute_reicht_standardeingabe_durch(): void {
        $output = [];
        $resultCode = 0;

        $ok = Shell::execute([PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));'], $output, $resultCode, null, "hallo\n");

        $this->assertTrue($ok);
        $this->assertSame(0, $resultCode);
        $this->assertSame(['HALLO'], $output);
    }

    public function test_execute_meldet_zeitgrenze_als_fehlschlag(): void {
        $output = [];
        $resultCode = 0;
        $started = microtime(true);

        $ok = Shell::execute([PHP_BINARY, '-r', 'sleep(5);'], $output, $resultCode, 0.5);

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
}
