<?php
/*
 * Created on   : Mon Mar 31 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : JavaTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Entities\Executables\JavaExecutable;
use PHPUnit\Framework\TestCase;
use CommonToolkit\Helper\Java;

class JavaTest extends TestCase {

    public function testExecuteThrowsIfPathIsEmpty(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Für JAVA-Ausführung muss der Pfad zur JAR-Datei gesetzt sein.");

        Java::execute('');
    }

    public function testExecuteThrowsIfJarDoesNotExist(): void {
        $fakePath = '/nonexistent/path/to/fake.jar';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Die angegebene JAR-Datei existiert nicht");

        Java::execute($fakePath);
    }

    public function testExecuteClassThrowsIfValuesAreMissing(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Für CLASS-Ausführung müssen classpath, mainClass und runClass gesetzt sein.");

        Java::executeClass('', '', '');
    }

    public function testJavaExecutableThrowsIfJavaNotAvailable(): void {
        // Nur ausführen, wenn Java definitiv nicht installiert ist
        if (Java::exists()) {
            $this->markTestSkipped('Java ist installiert – dieser Test ist nur für den Offline-Fall.');
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("JAVA-Ausführung benötigt eine JAVA-Runtime.");

        $executable = new JavaExecutable([
            'path' => '/path/to/some.jar',
            'arguments' => ['arg1']
        ]);

        $executable->execute();
    }

    public function testJavaExecutableCommandStringBuild(): void {
        // Skippen, wenn Java nicht vorhanden oder Datei nicht existiert
        if (!Java::exists()) {
            $this->markTestSkipped("Java ist auf diesem System nicht installiert.");
        }

        // Beispielhafte Dummy-JAR-Datei vorbereiten (existierende Datei nötig)
        $dummyJar = __DIR__ . '/../../.samples/dummy.jar';

        $executable = new JavaExecutable([
            'path' => $dummyJar,
            'arguments' => ['--help']
        ]);

        $output = $executable->execute();
        $this->assertIsString($output);
        $this->assertStringContainsStringIgnoringCase('world', $output); // je nach Dummy-JAR
    }
}
