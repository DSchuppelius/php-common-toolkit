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
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        LoggerRegistry::setLogger(ConsoleLoggerFactory::getLogger());
    }
}