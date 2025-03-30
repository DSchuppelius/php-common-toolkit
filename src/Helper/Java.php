<?php
/*
 * Created on   : Sun Mar 30 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Java.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use Exception;

class Java extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../config/common_executables.json';

    public static function execute(string $path, array $args = []): string {
        self::setLogger();

        if (empty($path)) {
            self::logError("Für JAVA-Ausführung muss der Pfad zur JAR-Datei gesetzt sein.");
            throw new Exception("Für JAVA-Ausführung muss der Pfad zur JAR-Datei gesetzt sein.");
        } elseif (File::exists($path) === false) {
            self::logError("Die angegebene JAR-Datei existiert nicht: $path");
            throw new Exception("Die angegebene JAR-Datei existiert nicht: $path");
        }

        $command = self::getConfiguredCommand("java", ["[PROGRAM]" => $path]);
        if (empty($command)) {
            self::logError("JAVA-Ausführung benötigt eine JAVA-Runtime. Bitte installieren Sie eine JAVA-Runtime.");
            throw new Exception("JAVA-Ausführung benötigt eine JAVA-Runtime.");
        }
        return Shell::executeShell($command . " " . implode(' ', array_map('escapeshellarg', $args)));
    }

    public static function executeClass(string $classPath, string $mainClass, string $runClass, array $args = []): string {
        self::setLogger();
        if (empty($classPath) || empty($mainClass) || empty($runClass)) {
            self::logError("Für CLASS-Ausführung müssen classpath, mainClass und runClass gesetzt sein.");
            throw new Exception("Für CLASS-Ausführung müssen classpath, mainClass und runClass gesetzt sein.");
        }

        $command = self::getConfiguredCommand("java-class", ["[CLASSPATH]" => escapeshellarg($classPath), "[MAINCLASS]" => escapeshellarg($mainClass), "[CLASS]" => escapeshellarg($runClass)]);
        if (empty($command)) {
            self::logError("JAVA Klassen-Ausführung benötigt eine JAVA-Runtime. Bitte installieren Sie eine JAVA-Runtime.");
            throw new Exception("JAVA Klassen-Ausführung benötigt eine JAVA-Runtime.");
        }
        return Shell::executeShell($command . " " . implode(' ', array_map('escapeshellarg', $args)));
    }
}
