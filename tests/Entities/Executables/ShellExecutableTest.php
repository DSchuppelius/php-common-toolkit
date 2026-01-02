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
    public function testConstructorWithPath(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo'
        ]);

        $this->assertSame('/usr/bin/echo', (string)$executable);
    }

    public function testConstructorWithPathAndArguments(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo',
            'arguments' => ['Hello', 'World']
        ]);

        $this->assertSame('/usr/bin/echo Hello World', (string)$executable);
    }

    public function testConstructorWithLinuxPath(): void {
        // Linux spezifischer Pfad (nur auf Linux getestet)
        $executable = new ShellExecutable([
            'linuxPath' => '/usr/bin/cat',
            'windowsPath' => 'C:\\Windows\\System32\\cmd.exe'
        ]);

        // Auf Linux sollte linuxPath verwendet werden
        $this->assertStringContainsString('cat', (string)$executable);
    }

    public function testConstructorWithLinuxArguments(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/echo',
            'linuxArguments' => ['-n', 'Test'],
            'windowsArguments' => ['/C', 'echo Test']
        ]);

        // Auf Linux sollte linuxArguments verwendet werden
        $this->assertStringContainsString('-n', (string)$executable);
    }

    public function testExecuteEchoCommand(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['TestOutput']
        ]);

        $result = $executable->execute();

        $this->assertStringContainsString('TestOutput', $result);
    }

    public function testExecuteWithOverrideArgs(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['[MESSAGE]']
        ]);

        $result = $executable->execute(['[MESSAGE]' => 'OverriddenMessage']);

        $this->assertStringContainsString('OverriddenMessage', $result);
    }

    public function testExecuteWithAdditionalArgs(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['First']
        ]);

        $result = $executable->execute(['Second']);

        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Second', $result);
    }

    public function testToString(): void {
        $executable = new ShellExecutable([
            'path' => '/usr/bin/test',
            'arguments' => ['-f', '/path/to/file']
        ]);

        $string = (string)$executable;

        $this->assertSame('/usr/bin/test -f /path/to/file', $string);
    }

    public function testEmptyArguments(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/true'
        ]);

        $this->assertSame('/bin/true', (string)$executable);
    }

    public function testPlaceholderReplacement(): void {
        $executable = new ShellExecutable([
            'path' => '/bin/echo',
            'arguments' => ['Input: [INPUT]', 'Output: [OUTPUT]']
        ]);

        $result = $executable->execute([
            '[INPUT]' => 'source.txt',
            '[OUTPUT]' => 'dest.txt'
        ]);

        $this->assertStringContainsString('source.txt', $result);
        $this->assertStringContainsString('dest.txt', $result);
    }
}
