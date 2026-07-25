<?php
/*
 * Created on   : Thu Apr 03 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BaseTestCase.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

namespace Tests\Contracts;

use ERRORToolkit\Factories\ConsoleLoggerFactory;
use ERRORToolkit\LoggerRegistry;
use ERRORToolkit\Traits\ErrorLog;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase {
    use ErrorLog;

    protected function setUp(): void {
        parent::setUp();

        LoggerRegistry::setLogger(ConsoleLoggerFactory::getLogger());
    }

    /**
     * Liest eine Datei als String und schlägt fehl, wenn sie nicht lesbar ist.
     * Vermeidet `string|false`-Verkettungen in den Tests.
     */
    protected function readFile(string $path): string {
        $content = file_get_contents($path);
        if ($content === false) {
            self::fail("Datei nicht lesbar: $path");
        }
        return $content;
    }
}
