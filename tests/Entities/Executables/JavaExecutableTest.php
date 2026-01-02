<?php
/*
 * Created on   : Sun Jul 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JavaExecutableTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace Tests\Entities\Executables;

use CommonToolkit\Entities\Executables\JavaExecutable;
use CommonToolkit\Helper\Java;
use Tests\Contracts\BaseTestCase;
use Exception;

/**
 * Tests für die JavaExecutable Entity.
 */
class JavaExecutableTest extends BaseTestCase {
    public function testConstructorWithPath(): void {
        $executable = new JavaExecutable([
            'path' => '/path/to/app.jar'
        ]);

        $this->assertSame('/path/to/app.jar', (string)$executable);
    }

    public function testConstructorWithPathAndArguments(): void {
        $executable = new JavaExecutable([
            'path' => '/path/to/app.jar',
            'arguments' => ['-Xmx512m', '--config=/etc/app.conf']
        ]);

        $this->assertSame('/path/to/app.jar -Xmx512m --config=/etc/app.conf', (string)$executable);
    }

    public function testToString(): void {
        $executable = new JavaExecutable([
            'path' => '/path/to/tool.jar',
            'arguments' => ['--input', 'file.txt']
        ]);

        $this->assertSame('/path/to/tool.jar --input file.txt', (string)$executable);
    }

    public function testExecuteThrowsExceptionIfJavaNotAvailable(): void {
        if (Java::exists()) {
            $this->markTestSkipped('Java ist auf diesem System verfügbar. Test übersprungen.');
        }

        $executable = new JavaExecutable([
            'path' => '/path/to/nonexistent.jar'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Java ist auf diesem System nicht verfügbar.');

        $executable->execute();
    }

    public function testConstructorWithLinuxAndWindowsPath(): void {
        $executable = new JavaExecutable([
            'linuxPath' => '/opt/java/app.jar',
            'windowsPath' => 'C:\\Java\\app.jar'
        ]);

        // Auf Linux sollte linuxPath verwendet werden
        $this->assertStringContainsString('app.jar', (string)$executable);
    }

    public function testPlaceholderReplacement(): void {
        $executable = new JavaExecutable([
            'path' => '/path/to/app.jar',
            'arguments' => ['--input=[INPUT]', '--output=[OUTPUT]']
        ]);

        // Die Argumente werden bei execute() ersetzt
        // Hier prüfen wir nur die initiale Struktur
        $this->assertStringContainsString('[INPUT]', (string)$executable);
        $this->assertStringContainsString('[OUTPUT]', (string)$executable);
    }
}
