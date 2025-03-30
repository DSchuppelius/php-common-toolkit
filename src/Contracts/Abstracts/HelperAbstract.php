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
use ERRORToolkit\Factories\ConsoleLoggerFactory;
use ERRORToolkit\Traits\ErrorLog;
use Psr\Log\LoggerInterface;

abstract class HelperAbstract implements HelperInterface {
    use ErrorLog;

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
}
