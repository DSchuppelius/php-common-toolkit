<?php
/*
 * Created on   : Wed May 07 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : Mt940FileTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Helper\FileSystem\FileTypes\Mt940File;
use Tests\Contracts\BaseTestCase;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;

class Mt940FileTest extends BaseTestCase {
    private string $testValidFile = __DIR__ . '/../../.samples/example.mt940';
    private string $testInvalidFile = __DIR__ . '/../../.samples/invalid.mt940';
    private string $testEmptyFile = __DIR__ . '/../../.samples/empty.mt940';

    public function testGetBlocksReturnsExpectedCount() {
        $blocks = Mt940File::getBlocks($this->testValidFile);
        $this->assertIsArray($blocks);
        $this->assertCount(1, $blocks);
    }

    public function testIsValidReturnsTrueForValidFile() {
        $this->assertTrue(Mt940File::isValid($this->testValidFile));
    }

    public function testIsValidReturnsFalseForInvalidFile() {
        $this->assertFalse(Mt940File::isValid($this->testInvalidFile));
    }

    public function testCountTransactionsReturnsCorrectNumber() {
        $count = Mt940File::countTransactions($this->testValidFile);
        $this->assertEquals(2, $count); // 2 Buchungen mit :61:
    }

    public function testEmptyFileThrowsException() {
        $blocks = Mt940File::getBlocks($this->testEmptyFile);
        $this->assertIsArray($blocks);
        $this->assertCount(0, $blocks);
    }

    public function testFileNotFoundThrowsException() {
        $this->expectException(FileNotFoundException::class);
        Mt940File::getBlocks('/nicht/vorhanden.mt940');
    }
}
