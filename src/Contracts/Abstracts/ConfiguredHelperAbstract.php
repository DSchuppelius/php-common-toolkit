<?php
/*
 * Created on   : Mon Mar 17 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : ConfiguredHelperAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use ConfigToolkit\CommandBuilder;
use ConfigToolkit\ConfigLoader;
use Exception;

/**
 * Abstrakte Basisklasse für Helper mit Executable-Konfiguration.
 * 
 * Nutzt den CommandBuilder aus dem ConfigToolkit für elegantes Command-Building.
 * Jede abgeleitete Klasse definiert ihre CONFIG_FILE Konstante.
 */
abstract class ConfiguredHelperAbstract extends HelperAbstract {
    protected const CONFIG_FILE = '';

    private static ?ConfigLoader $configLoader = null;

    /**
     * Statische Speicherung der CommandBuilder pro Klasse/Config-Datei.
     * @var array<string, CommandBuilder>
     */
    private static array $commandBuilders = [];

    /**
     * Gibt den vollständigen Befehl zurück, der in der Konfiguration definiert ist und fügt die Parameter hinzu.
     *
     * @param string $commandName Der Name des Kommandos.
     * @param array $params Die Parameter, die in der Konfiguration ersetzt werden sollen.
     * @param string $type Der Typ der Konfiguration (z.B. 'shellExecutables').
     * @return string|null Der vollständige Befehl oder null bei Fehler.
     */
    protected static function getConfiguredCommand(string $commandName, array $params = [], string $type = 'shellExecutables'): ?string {
        $builder = self::getCommandBuilder();
        $command = $builder->build($commandName, $params, [], $type);

        if ($command !== null) {
            self::logDebug("Kommando generiert für '$commandName': $command");
        }

        return $command;
    }

    /**
     * Gibt den vollständigen Java-Befehl zurück (java -jar ...).
     *
     * @param string $commandName Der Name des Java-Executables.
     * @param array $params Die Parameter, die in der Konfiguration ersetzt werden sollen.
     * @param string $javaSection Die Sektion für Java-Executables.
     * @return string|null Der vollständige Befehl oder null bei Fehler.
     */
    protected static function getConfiguredJavaCommand(string $commandName, array $params = [], string $javaSection = 'javaExecutables'): ?string {
        $builder = self::getCommandBuilder();
        $command = $builder->buildJava($commandName, $params, [], $javaSection);

        if ($command !== null) {
            self::logDebug("Java-Kommando generiert für '$commandName': $command");
        }

        return $command;
    }

    /**
     * Prüft ob ein Executable verfügbar ist.
     *
     * @param string $commandName Der Name des Kommandos.
     * @param string $type Der Typ der Konfiguration (z.B. 'shellExecutables').
     * @return bool True wenn das Executable verfügbar ist.
     */
    protected static function isExecutableAvailable(string $commandName, string $type = 'shellExecutables'): bool {
        $builder = self::getCommandBuilder();
        return $builder->isAvailable($commandName, $type);
    }

    /**
     * Gibt den Pfad eines Executables zurück.
     *
     * @param string $commandName Der Name des Kommandos.
     * @param string $type Der Typ der Konfiguration (z.B. 'shellExecutables').
     * @return string|null Der Pfad oder null wenn nicht gefunden.
     */
    protected static function getExecutablePath(string $commandName, string $type = 'shellExecutables'): ?string {
        $builder = self::getCommandBuilder();
        return $builder->getPath($commandName, $type);
    }

    /**
     * Holt die Konfiguration für ein bestimmtes Kommando und ersetzt Platzhalter.
     *
     * @param string $commandName Der Name des Kommandos.
     * @param array $params Die Parameter, die in der Konfiguration ersetzt werden sollen.
     * @param string $type Der Typ der Konfiguration (z.B. 'shellExecutables').
     * @return array|null Die Konfiguration des Executables oder null bei Fehler.
     */
    protected static function getResolvedExecutableConfig(string $commandName, array $params = [], string $type = 'shellExecutables'): ?array {
        $configLoader = self::getConfigLoader();
        $executable = $configLoader->getWithReplaceParams($type, $commandName, $params, null);

        if (!$executable) {
            return self::logErrorAndReturn(null, "Keine Konfiguration für '$commandName' gefunden in '$type'.");
        } elseif (empty($executable['path'])) {
            return self::logErrorAndReturn(null, "Kein Pfad für '$commandName' in der Konfiguration gefunden.");
        }

        return $executable;
    }

    /**
     * Holt alle Instanzen eines bestimmten Typs aus der Konfiguration.
     *
     * @param string $configKey Der Schlüssel in der Konfiguration.
     * @param string $class Der Klassentyp, den die Instanzen haben sollen.
     * @return array Ein Array von Instanzen des angegebenen Typs.
     */
    protected static function getExecutableInstances(string $configKey, string $class): array {
        $configLoader = self::getConfigLoader();
        $items = $configLoader->get($configKey);
        $executables = [];

        foreach ($items as $name => $config) {
            $executables[$name] = new $class($config);
        }

        return $executables;
    }

    /**
     * Gibt den CommandBuilder zurück, initialisiert ihn bei Bedarf.
     * WICHTIG: Jede Config-Datei bekommt ihren eigenen CommandBuilder.
     */
    protected static function getCommandBuilder(): CommandBuilder {
        $configFile = static::CONFIG_FILE;

        if (!isset(self::$commandBuilders[$configFile])) {
            $configLoader = self::getConfigLoader();
            self::$commandBuilders[$configFile] = new CommandBuilder($configLoader);
        }

        return self::$commandBuilders[$configFile];
    }

    /**
     * Initialisiert ConfigLoader, falls noch nicht geschehen
     */
    protected static function getConfigLoader(): ConfigLoader {
        if (empty(static::CONFIG_FILE)) {
            self::logErrorAndThrow(Exception::class, "Fehler: CONFIG_FILE wurde nicht definiert in " . static::class);
        }

        if (!self::$configLoader) {
            self::$configLoader = ConfigLoader::getInstance(self::$logger);
        }

        // Erst nach der Initialisierung prüfen, ob die Datei bereits geladen wurde
        if (!self::$configLoader->hasLoadedConfigFile(static::CONFIG_FILE)) {
            self::$configLoader->loadConfigFile(static::CONFIG_FILE);
        }

        return self::$configLoader;
    }

    /**
     * Setzt den CommandBuilder zurück (nützlich für Tests).
     */
    protected static function resetCommandBuilder(): void {
        $configFile = static::CONFIG_FILE;
        unset(self::$commandBuilders[$configFile]);
    }

    /**
     * Setzt alle CommandBuilder zurück (nützlich für Tests).
     */
    protected static function resetAllCommandBuilders(): void {
        self::$commandBuilders = [];
    }
}
