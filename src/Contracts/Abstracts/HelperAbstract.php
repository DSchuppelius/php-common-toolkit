<?php
/*
 * Created on   : Mon Oct 07 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : HelperAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts;

use CommonToolkit\Contracts\Interfaces\HelperInterface;
use ConfigToolkit\ConfigLoader;
use ERRORToolkit\Factories\ConsoleLoggerFactory;
use Exception;
use Psr\Log\LoggerInterface;

abstract class HelperAbstract implements HelperInterface {
    protected const CONFIG_FILE = '';

    protected static ?LoggerInterface $logger = null;
    private static ?ConfigLoader $configLoader = null;

    public static function setLogger(?LoggerInterface $logger = null): void {
        if (!is_null($logger)) {
            self::$logger = $logger;
        } elseif (is_null(self::$logger)) {
            self::$logger = ConsoleLoggerFactory::getLogger();
        }
    }

    public static function sanitize(string $filename): string {
        // Escape problematische Zeichen für Shell-Befehle (Windows & Linux)
        return preg_replace('/([ \'"()\[\]{}!$`])/', '\\\$1', $filename);
    }


    protected static function getConfiguredCommand(string $commandName, array $params = []): ?string {
        self::setLogger();

        $configLoader = self::getConfigLoader();
        $executable = $configLoader->getWithReplaceParams("shellExecutables", $commandName, $params, null);

        if (!$executable) {
            self::$logger->error("Keine Konfiguration für '$commandName' gefunden.");
            return null;
        } elseif (empty($executable['path'])) {
            self::$logger->error("Kein Pfad für '$commandName' in der Konfiguration gefunden.");
            return null;
        }

        $finalCommand = escapeshellarg($executable['path']) . ' ' . implode(' ', $executable['arguments'] ?? []);

        self::$logger->debug("Kommando generiert für '$commandName': $finalCommand");

        return $finalCommand;
    }

    /**
     * Initialisiert ConfigLoader, falls noch nicht geschehen
     */
    protected static function getConfigLoader(): ConfigLoader {
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