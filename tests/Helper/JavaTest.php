<?php

declare(strict_types=1);

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
}
