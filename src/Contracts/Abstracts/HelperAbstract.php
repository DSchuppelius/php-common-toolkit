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
use CommonToolkit\Helper\FileSystem\File;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use ERRORToolkit\Factories\ConsoleLoggerFactory;
use ERRORToolkit\Traits\ErrorLog;
use Psr\Log\LoggerInterface;

abstract class HelperAbstract implements HelperInterface {
    use ErrorLog;

    /**
     * Gibt den Dateipfad zurück, wenn die Datei existiert.
     *
     * @param string $file Der Pfad zur CSV-Datei.
     * @throws FileNotFoundException Wenn die Datei nicht existiert oder nicht lesbar ist.
     */
    protected static function resolveFile(string $file): string {
        if (!File::exists($file)) {
            self::logError("Die Datei $file existiert nicht oder ist nicht lesbar.");
            throw new FileNotFoundException("Die Datei $file existiert nicht oder ist nicht lesbar.");
        }
        return File::getRealPath($file);
    }

    /**
     * Setzt den Logger für die Klasse.
     *
     * @param LoggerInterface|null $logger
     */
    public static function setLogger(?LoggerInterface $logger = null): void {
        if (!is_null($logger)) {
            self::$logger = $logger;
        } elseif (is_null(self::$logger)) {
            self::$logger = ConsoleLoggerFactory::getLogger();
        }
    }

    /**
     * Bereinigt den Dateinamen, um problematische Zeichen zu entfernen.
     *
     * @param string $filename
     * @return string
     */
    public static function sanitize(string $filename): string {
        // Escape problematische Zeichen für Shell-Befehle (Windows & Linux)
        return preg_replace('/([ \'"()\[\]{}!$`])/', '\\\$1', $filename);
    }
}