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
use PHPUnit\Framework\TestCase;

class ShellTest extends TestCase {

    public function testExecuteShellCommandSuccess() {
        $unixCommand = 'echo "Hello World"';
        $windowsCommand = 'echo Hello World';

        $command = Shell::getPlatformSpecificCommand($unixCommand, $windowsCommand);

        $output = [];
        $success = Shell::executeShellCommand($command, $output);

        $this->assertTrue($success, "The shell command should be successful.");
        $this->assertNotEmpty($output, "The output should not be empty.");
    }

    public function testExecuteShellCommandFailure() {
        $command = "invalidcommand 2>&1";

        $output = [];
        $resultCode = 0;

        $this->expectException(Exception::class);
        Shell::executeShellCommand($command, $output, $resultCode, true);
    }
}
