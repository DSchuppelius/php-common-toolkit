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

use CommonToolkit\Entities\Banking\Mt940\Document;
use CommonToolkit\Entities\Banking\Mt940\Transaction;
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
        $this->markTestIncomplete('Die Implementierung von Mt940File::countTransactions muss noch abgeschlossen werden.');

        $count = Mt940File::countTransactions($this->testValidFile);
        $this->assertEquals(2, $count); // 2 Buchungen mit :61:
    }

    public function testCountTransactionsMatchesGetTransactions() {
        $expected = Mt940File::getTransactions($this->testValidFile);
        $count = Mt940File::countTransactions($this->testValidFile);

        $this->assertEquals(count($expected), $count);
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

    public function testGetDocumentsReturnsArrayOfMt940Document() {
        $this->markTestIncomplete('Die Implementierung von Mt940File::getDocuments muss noch abgeschlossen werden.');

        $documents = Mt940File::getDocuments($this->testValidFile);

        $this->assertIsArray($documents);
        $this->assertNotEmpty($documents);

        foreach ($documents as $doc) {
            $this->assertInstanceOf(Document::class, $doc);
            $this->assertNotEmpty($doc->getTransactions());
        }
    }

    public function testGetTransactionsReturnsArrayOfTransactions() {
        $this->markTestIncomplete('Die Implementierung von Mt940File::getDocuments muss noch abgeschlossen werden.');

        $transactions = Mt940File::getTransactions($this->testValidFile);

        $this->assertIsArray($transactions);
        $this->assertCount(2, $transactions); // wie in countTransactions

        foreach ($transactions as $txn) {
            $this->assertInstanceOf(Transaction::class, $txn);
        }
    }
}