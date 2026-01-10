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
use ConfigToolkit\ConfigLoader;
use Exception;

abstract class ConfiguredHelperAbstract extends HelperAbstract {
    protected const CONFIG_FILE = '';
    private static ?ConfigLoader $configLoader = null;

    /**
     * Gibt den vollständigen Befehl zurück, der in der Konfiguration definiert ist und fügt die Parameter hinzu.
     *
     * @param string $commandName Der Name des Kommandos.
     * @param array $params Die Parameter, die in der Konfiguration ersetzt werden sollen.
     * @param string $type Der Typ der Konfiguration (z.B. 'shellExecutables').
     * @return string|null Der vollständige Befehl oder null bei Fehler.
     */
    protected static function getConfiguredCommand(string $commandName, array $params = [], string $type = 'shellExecutables'): ?string {
        $executable = self::getResolvedExecutableConfig($commandName, $params, $type);

        if (!$executable) {
            return null; // Fehler wurde bereits in getResolvedExecutableConfig() geloggt
        }

        $finalCommand = escapeshellarg($executable['path']) . ' ' . implode(' ', $executable['arguments'] ?? []);
        return self::logDebugAndReturn($finalCommand, "Kommando generiert für '$commandName': $finalCommand");
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
}
