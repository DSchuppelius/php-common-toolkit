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
use CommonToolkit\Entities\Executables\JavaExecutable;
use CommonToolkit\Helper\FileSystem\File;
use Exception;

class Java extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../config/common_executables.json';

    /**
     * Führt eine JAR-Datei aus.
     *
     * @param string $path Der Pfad zur JAR-Datei.
     * @param array $args Zusätzliche Argumente für die Ausführung.
     * @return string Die Ausgabe der Ausführung.
     * @throws Exception Wenn ein Fehler auftritt.
     */
    public static function execute(string $path, array $args = []): string {
        if (empty($path)) {
            self::logErrorAndThrow(Exception::class, "Für JAVA-Ausführung muss der Pfad zur JAR-Datei gesetzt sein.");
        } elseif (File::exists($path) === false) {
            self::logErrorAndThrow(Exception::class, "Die angegebene JAR-Datei existiert nicht: $path");
        }

        $command = self::getConfiguredCommand("java-program", ["[PROGRAM]" => $path]);
        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "JAVA-Ausführung benötigt eine JAVA-Runtime. Bitte installieren Sie eine JAVA-Runtime.");
        }
        return Shell::executeShell($command . " " . implode(' ', array_map('escapeshellarg', $args)));
    }

    /**
     * Führt eine Java-Klasse aus.
     *
     * @param string $classPath Der Pfad zur Klasse.
     * @param string $mainClass Der Hauptklasse-Pfad.
     * @param string $runClass Der Klassenname, der ausgeführt werden soll.
     * @param array $args Zusätzliche Argumente für die Ausführung.
     * @return string Die Ausgabe der Ausführung.
     * @throws Exception Wenn ein Fehler auftritt.
     */
    public static function executeClass(string $classPath, string $mainClass, string $runClass, array $args = []): string {
        if (empty($classPath) || empty($mainClass) || empty($runClass)) {
            self::logErrorAndThrow(Exception::class, "Für CLASS-Ausführung müssen classpath, mainClass und runClass gesetzt sein.");
        }

        $command = self::getConfiguredCommand("java-class", ["[CLASSPATH]" => escapeshellarg($classPath), "[MAINCLASS]" => escapeshellarg($mainClass), "[CLASS]" => escapeshellarg($runClass)]);
        if (empty($command)) {
            self::logErrorAndThrow(Exception::class, "JAVA Klassen-Ausführung benötigt eine JAVA-Runtime. Bitte installieren Sie eine JAVA-Runtime.");
        }
        return Shell::executeShell($command . " " . implode(' ', array_map('escapeshellarg', $args)));
    }

    /**
     * Überprüft, ob Java installiert ist.
     *
     * @return bool True, wenn Java installiert ist, andernfalls false.
     */
    public static function exists(): bool {
        // Versuche den Java-Befehl aus der Konfiguration zu ermitteln
        $command = self::getConfiguredCommand("java") . "-version 2>&1";
        $output = [];

        if (Shell::executeShellCommand($command, $output)) {
            return self::logDebugAndReturn(true, "Java ist verfügbar: " . implode("\n", $output));
        }

        return self::logWarningAndReturn(false, "Java wurde nicht gefunden oder ist nicht lauffähig.");
    }

    /**
     * Gibt die konfigurierten Java-Executables zurück.
     *
     * @return array Ein Array mit den konfigurierten Java-Executables.
     */
    public static function getConfiguredExecutables(): array {
        return self::getExecutableInstances('javaExecutables', JavaExecutable::class);
    }
}
