<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ShellExecutableTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\Executables;

use CommonToolkit\Entities\Executables\ShellExecutable;
use Tests\Contracts\BaseTestCase;

/**
 * Tests für die ShellExecutable Entity.
 */
class ShellExecutableTest extends BaseTestCase {
    public function test_constructor_with_path(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo',
        ]);

        $this->assertSame('/usr/bin/echo', (string) $executable);
    }

    public function test_constructor_with_path_and_arguments(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo',
            'arguments' => ['Hello', 'World'],
        ]);

        $this->assertSame('/usr/bin/echo Hello World', (string) $executable);
    }

    public function test_constructor_with_linux_path(): void {
        $executable = new ShellExecutable([
            'linuxPath' => '/usr/bin/cat',
            'windowsPath' => 'C:\\Windows\\System32\\cmd.exe',
        ]);
        if (PHP_OS_FAMILY === 'Windows') {
            // Auf Windows wird windowsPath verwendet

            $this->assertStringContainsString('cmd.exe', (string) $executable);
        } else {
            $this->assertStringContainsString('cat', (string) $executable);
        }
    }

    public function test_constructor_with_linux_arguments(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo',
            'linuxArguments' => ['-n', 'Test'],
            'windowsArguments' => ['/C', 'echo Test'],
        ]);

        if (PHP_OS_FAMILY === 'Windows') {
            // Auf Windows sollte windowsArguments verwendet werden
            $this->assertStringContainsString('/C', (string) $executable);
        } else {
            // Auf Linux sollte linuxArguments verwendet werden
            $this->assertStringContainsString('-n', (string) $executable);
        }
    }

    public function test_execute_echo_command(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Shell-Ausführung mit cmd.exe erfordert spezielle Behandlung auf Windows');
        }

        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['TestOutput'],
        ]);

        $result = $executable->execute();

        $this->assertStringContainsString('TestOutput', $result);
    }

    public function test_execute_with_override_args(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Shell-Ausführung mit cmd.exe erfordert spezielle Behandlung auf Windows');
        }

        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['[MESSAGE]'],
        ]);

        $result = $executable->execute(['[MESSAGE]' => 'OverriddenMessage']);

        $this->assertStringContainsString('OverriddenMessage', $result);
    }

    public function test_execute_with_additional_args(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Shell-Ausführung mit cmd.exe erfordert spezielle Behandlung auf Windows');
        }

        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['First'],
        ]);
        $result = $executable->execute(['Second']);
        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Second', $result);
    }

    public function test_to_string(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/test',
            'arguments' => ['-f', '/path/to/file'],
        ]);

        $string = (string) $executable;

        $this->assertSame('/usr/bin/test -f /path/to/file', $string);
    }

    public function test_empty_arguments(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/true',
        ]);

        $this->assertSame('/bin/true', (string) $executable);
    }

    public function test_placeholder_replacement(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Shell-Ausführung mit cmd.exe erfordert spezielle Behandlung auf Windows');
        }

        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['Input: [INPUT]', 'Output: [OUTPUT]'],
        ]);

        $result = $executable->execute([
            '[INPUT]' => 'source.txt',
            '[OUTPUT]' => 'dest.txt',
        ]);

        $this->assertStringContainsString('source.txt', $result);
        $this->assertStringContainsString('dest.txt', $result);
    }
}
