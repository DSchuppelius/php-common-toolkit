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

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Entities\Executables\JavaExecutable;
use CommonToolkit\Helper\Java;
use Tests\Contracts\BaseTestCase;
use ConfigToolkit\ConfigLoader;

class JavaTest extends BaseTestCase {

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

    public function testJavaExecutableReplacesPlaceholdersCorrectly(): void {
        if (!Java::exists()) {
            $this->markTestSkipped('Java ist auf diesem System nicht installiert.');
        }

        $configFile = __DIR__ . '/../test-configs/executables_config.json';

        if (!file_exists($configFile)) {
            $this->markTestSkipped('Testkonfigurationsdatei nicht vorhanden.');
        }

        // Reset ConfigLoader (Singleton) & ConfiguredHelperAbstract::$configLoader
        ConfigLoader::resetInstance();

        $ref = new ReflectionClass(ConfiguredHelperAbstract::class);
        $prop = $ref->getProperty('configLoader');
        $prop->setAccessible(true);
        $prop->setValue(null, null); // static::$configLoader = null;

        $loader = ConfigLoader::getInstance();
        $loader->loadConfigFile($configFile, true, true);

        // hole Executable
        $executables = Java::getConfiguredExecutables();

        $this->assertArrayHasKey('echoTest', $executables);
        $executable = $executables['echoTest'];
        $executableReflection = new ReflectionClass($executable);
        $pathProperty = $executableReflection->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($executable, realpath(__DIR__ . '/../../.samples/echoargs.jar'));

        $output = $executable->execute(['[INPUT]' => 'Hallo Welt']);

        $this->assertIsString($output);
        $this->assertStringContainsString('Hallo Welt', $output);
    }
}
