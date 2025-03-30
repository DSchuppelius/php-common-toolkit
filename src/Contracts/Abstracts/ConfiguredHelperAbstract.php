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

    protected static function getConfiguredCommand(string $commandName, array $params = []): ?string {
        $configLoader = self::getConfigLoader();
        $executable = $configLoader->getWithReplaceParams("shellExecutables", $commandName, $params, null);

        if (!$executable) {
            self::logError("Keine Konfiguration für '$commandName' gefunden.");
            return null;
        } elseif (empty($executable['path'])) {
            self::logError("Kein Pfad für '$commandName' in der Konfiguration gefunden.");
            return null;
        }

        $finalCommand = escapeshellarg($executable['path']) . ' ' . implode(' ', $executable['arguments'] ?? []);

        self::logDebug("Kommando generiert für '$commandName': $finalCommand");

        return $finalCommand;
    }

    /**
     * Initialisiert ConfigLoader, falls noch nicht geschehen
     */
    protected static function getConfigLoader(): ConfigLoader {
        self::setLogger();

        if (empty(static::CONFIG_FILE)) {
            throw new Exception("Fehler: CONFIG_FILE wurde nicht definiert in " . static::class);
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
